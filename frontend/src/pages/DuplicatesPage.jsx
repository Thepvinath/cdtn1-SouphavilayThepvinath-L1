import { useEffect, useState } from 'react';
import { customersApi } from '../api/customers.api';
import { mergeCustomers } from '../api/merge.api';

export default function DuplicatesPage({ onBack }) {
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [refresh, setRefresh] = useState(0);

  // FR5 - Merge states
  const [masterId, setMasterId] = useState('');
  const [sourceId, setSourceId] = useState('');
  const [merging, setMerging] = useState(false);
  const [mergeError, setMergeError] = useState('');
  const [success, setSuccess] = useState('');

  // FR4 - Load duplicate customers
  useEffect(() => {
    let cancelled = false;

    customersApi.getDuplicates()
      .then((data) => {
        if (cancelled) return;

        const list = Array.isArray(data)
          ? data
          : Array.isArray(data?.data)
            ? data.data
            : [];

        setCustomers(list);
        setError('');
      })
      .catch((err) => {
        if (cancelled) return;

        setCustomers([]);
        setError(err.message || 'Không thể tải hồ sơ trùng');
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [refresh]);

  // Refresh duplicate list
  function retry() {
    setLoading(true);
    setError('');
    setMergeError('');
    setRefresh((value) => value + 1);
  }

  // Select master customer
  function selectMaster(id) {
    setMasterId(id);
    setMergeError('');
    setSuccess('');

    // Master and Source cannot be the same customer
    if (id === sourceId) {
      setSourceId('');
    }
  }

  // Select source customer
  function selectSource(id) {
    setSourceId(id);
    setMergeError('');
    setSuccess('');

    if (id === masterId) {
      setMasterId('');
    }
  }

  const master = customers.find(
    (customer) => String(customer.customer_id) === masterId
  );

  const source = customers.find(
    (customer) => String(customer.customer_id) === sourceId
  );

  // This is only a preliminary UI check.
  // The backend must independently verify the normalized phone.
  const sameDuplicateGroup = !master || !source
    ? false
    : master.phone_normalized && source.phone_normalized
      ? master.phone_normalized === source.phone_normalized
      : Boolean(
          master.phone &&
          source.phone &&
          master.phone === source.phone
        );

  const canMerge =
    Boolean(master && source) &&
    masterId !== sourceId &&
    master.status === 'ACTIVE' &&
    source.status === 'ACTIVE' &&
    sameDuplicateGroup &&
    !merging &&
    !loading;

  // FR5 - Submit merge request
  async function handleMerge() {
    if (!canMerge) {
      setMergeError(
        'Vui lòng chọn hai hồ sơ trùng khác nhau để gộp.'
      );
      return;
    }

    const confirmed = window.confirm(
      `Xác nhận gộp hồ sơ?\n\n` +
      `Hồ sơ chính: KH${master.customer_id} - ${master.full_name}\n` +
      `Hồ sơ trùng: KH${source.customer_id} - ${source.full_name}\n\n` +
      `Hồ sơ trùng sẽ được chuyển sang trạng thái MERGED.\n` +
      `Bạn có chắc chắn muốn tiếp tục?`
    );

    if (!confirmed) return;

    setMerging(true);
    setMergeError('');
    setSuccess('');

    try {
      const result = await mergeCustomers(
        Number(masterId),
        Number(sourceId)
      );

      if (result?.data?.status !== 'SUCCESS') {
        throw new Error(
          'Phản hồi gộp hồ sơ không xác nhận thành công.'
        );
      }

      setSuccess(
        `Đã gộp KH${sourceId} vào KH${masterId} thành công!`
      );

      setMasterId('');
      setSourceId('');

      // Refresh duplicate list after successful merge
      retry();
    } catch (err) {
      setMergeError(
        err.message || 'Có lỗi xảy ra khi gộp hồ sơ.'
      );
    } finally {
      setMerging(false);
    }
  }

  return (
    <main className="container">
      <div className="page-heading">
        <div>
          <h1>Hồ sơ khách hàng nghi trùng</h1>
          <p>
            Phát hiện, so sánh và gộp hồ sơ khách hàng
            có số điện thoại chuẩn hóa trùng nhau
          </p>
        </div>

        <button
          type="button"
          onClick={onBack}
          disabled={merging}
        >
          Quay lại danh sách
        </button>
      </div>

      {success && (
        <div
          className="message"
          role="status"
          style={{
            background: '#e7f6eb',
            color: '#08713d',
            marginBottom: 16
          }}
        >
          {success}
        </div>
      )}

      {loading ? (
        <div className="message" role="status">
          Đang kiểm tra hồ sơ trùng...
        </div>
      ) : error ? (
        <div className="message error" role="alert">
          <p>{error}</p>
          <button type="button" onClick={retry}>
            Thử lại
          </button>
        </div>
      ) : customers.length === 0 ? (
        <div className="message">
          <h3>Không phát hiện hồ sơ trùng</h3>
          <p>
            Hiện chưa có hồ sơ ACTIVE có số điện thoại
            chuẩn hóa trùng nhau.
          </p>
          <button type="button" onClick={retry}>
            Kiểm tra lại
          </button>
        </div>
      ) : (
        <>
          {/* FR4 - Duplicate customer table */}
          <div className="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Mã KH</th>
                  <th>Họ và tên</th>
                  <th>Số điện thoại</th>
                  <th>Email</th>
                  <th>Trạng thái</th>
                  <th>Hồ sơ chính</th>
                  <th>Hồ sơ trùng</th>
                </tr>
              </thead>

              <tbody>
                {customers.map((customer) => {
                  const id = String(customer.customer_id);

                  return (
                    <tr key={id}>
                      <td>KH{customer.customer_id}</td>
                      <td>{customer.full_name}</td>
                      <td>{customer.phone}</td>
                      <td>{customer.email || '-'}</td>
                      <td>{customer.status}</td>

                      <td>
                        <input
                          type="radio"
                          name="merge-master"
                          value={id}
                          aria-label={`Chọn KH${id} làm hồ sơ chính`}
                          checked={masterId === id}
                          disabled={merging}
                          onChange={() => selectMaster(id)}
                        />
                      </td>

                      <td>
                        <input
                          type="radio"
                          name="merge-source"
                          value={id}
                          aria-label={`Chọn KH${id} làm hồ sơ trùng`}
                          checked={sourceId === id}
                          disabled={merging}
                          onChange={() => selectSource(id)}
                        />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* FR5 - Compare selected customers */}
          <section
            style={{
              background: '#ffffff',
              border: '1px solid #dce1e7',
              borderRadius: 8,
              padding: 20,
              marginTop: 24
            }}
          >
            <h2 style={{ marginTop: 0 }}>
              So sánh và gộp hồ sơ
            </h2>

            <p>
              Chọn một hồ sơ chính và một hồ sơ trùng
              trong bảng phía trên.
            </p>

            <div
              style={{
                display: 'grid',
                gridTemplateColumns:
                  'repeat(auto-fit, minmax(min(100%, 250px), 1fr))',
                gap: 16,
                marginTop: 20,
                marginBottom: 20
              }}
            >
              <div
                style={{
                  border: '1px solid #dce1e7',
                  borderRadius: 8,
                  padding: 16
                }}
              >
                <h3>Hồ sơ chính (Master)</h3>

                {master ? (
                  <>
                    <p><strong>Mã KH:</strong> KH{master.customer_id}</p>
                    <p><strong>Họ tên:</strong> {master.full_name}</p>
                    <p><strong>SĐT:</strong> {master.phone}</p>
                    <p><strong>Email:</strong> {master.email || '-'}</p>
                    <p><strong>Trạng thái:</strong> {master.status}</p>
                  </>
                ) : (
                  <p>Chưa chọn hồ sơ chính.</p>
                )}
              </div>

              <div
                style={{
                  border: '1px solid #dce1e7',
                  borderRadius: 8,
                  padding: 16
                }}
              >
                <h3>Hồ sơ trùng (Source)</h3>

                {source ? (
                  <>
                    <p><strong>Mã KH:</strong> KH{source.customer_id}</p>
                    <p><strong>Họ tên:</strong> {source.full_name}</p>
                    <p><strong>SĐT:</strong> {source.phone}</p>
                    <p><strong>Email:</strong> {source.email || '-'}</p>
                    <p><strong>Trạng thái:</strong> {source.status}</p>
                  </>
                ) : (
                  <p>Chưa chọn hồ sơ trùng.</p>
                )}
              </div>
            </div>

            {master && source && !sameDuplicateGroup && (
              <p
                role="alert"
                style={{ color: '#c62828' }}
              >
                Hai hồ sơ không thuộc cùng nhóm số điện thoại
                nghi trùng. Vui lòng chọn lại.
              </p>
            )}

            <p style={{ color: '#64748b' }}>
              Hồ sơ chính sẽ được giữ lại. Hồ sơ trùng
              sẽ chuyển sang trạng thái MERGED.
            </p>

            {mergeError && (
              <div
                className="message error"
                role="alert"
                style={{ marginBottom: 16 }}
              >
                {mergeError}
              </div>
            )}

            <button
              type="button"
              onClick={handleMerge}
              disabled={!canMerge}
              style={{
                opacity: canMerge ? 1 : 0.5,
                cursor: canMerge ? 'pointer' : 'not-allowed'
              }}
            >
              {merging ? 'Đang gộp hồ sơ...' : 'Xác nhận gộp hồ sơ'}
            </button>

            <button
              type="button"
              disabled={merging}
              onClick={() => {
                setMasterId('');
                setSourceId('');
                setMergeError('');
              }}
              style={{ marginLeft: 12 }}
            >
              Hủy lựa chọn
            </button>
          </section>
        </>
      )}
    </main>
  );
}
