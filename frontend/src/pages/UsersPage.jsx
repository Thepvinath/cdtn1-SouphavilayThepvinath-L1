import { useEffect, useState } from 'react';
import { usersApi } from '../api/users.api';

const blank = { full_name: '', email: '', role_name: 'SALES_STAFF', password: '' };

export default function UsersPage({ currentUser }) {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [form, setForm] = useState(null);
  const [editing, setEditing] = useState(null);
  const [revision, setRevision] = useState(0);

  useEffect(() => {
    let cancelled = false;
    usersApi.list().then((data) => {
      if (!cancelled) setUsers(data);
    }).catch((err) => {
      if (!cancelled) setError(err.message);
    }).finally(() => {
      if (!cancelled) setLoading(false);
    });
    return () => { cancelled = true; };
  }, [revision]);

  function reload() {
    setLoading(true);
    setError('');
    setRevision((value) => value + 1);
  }

  async function edit(id) {
    setBusy(true);
    setError('');
    try {
      const data = await usersApi.detail(id);
      setEditing(id);
      setForm({ full_name: data.full_name, email: data.email, role_name: data.role_name, password: '' });
    } catch (err) { setError(err.message); }
    finally { setBusy(false); }
  }

  async function save(event) {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      const data = { ...form };
      if (editing && !data.password) delete data.password;
      if (editing) await usersApi.update(editing, data);
      else await usersApi.create(data);
      setForm(null);
      setNotice('Đã lưu nhân viên.');
      reload();
    } catch (err) { setError(err.message); }
    finally { setBusy(false); }
  }

  async function disable(employee) {
    if (!window.confirm(`Vô hiệu hóa ${employee.full_name}?`)) return;
    setBusy(true);
    setError('');
    try {
      await usersApi.disable(employee.user_id);
      setNotice('Đã vô hiệu hóa nhân viên.');
      reload();
    } catch (err) { setError(err.message); }
    finally { setBusy(false); }
  }

  return <main className="container">
    <div className="page-heading"><h1>Quản lý nhân viên</h1>
      <button disabled={busy || loading} onClick={() => { setEditing(null); setForm({ ...blank }); setError(''); setNotice(''); }}>+ Tạo nhân viên</button>
    </div>
    {notice && <p role="status">{notice}</p>}
    {error && <div role="alert"><p>{error}</p><button disabled={busy} onClick={reload}>Thử lại</button></div>}
    {form && <form className="customer-form" onSubmit={save}>
      <h2>{editing ? 'Sửa nhân viên' : 'Tạo nhân viên'}</h2>
      <fieldset disabled={busy}>
        <label>Họ và tên<input required maxLength={150} value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} /></label>
        <label>Email<input required type="email" maxLength={150} value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></label>
        <label>Vai trò<select value={form.role_name} onChange={(e) => setForm({ ...form, role_name: e.target.value })}>
          {['SALES_STAFF', 'STORE_MANAGER', 'MARKETING'].map((role) => <option key={role}>{role}</option>)}
        </select></label>
        <label>Mật khẩu {editing ? '(để trống để giữ nguyên)' : '(bắt buộc)'}<input type="password" autoComplete="new-password" required={!editing} minLength={12} maxLength={72} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></label>
        <p>Mật khẩu từ 12–72 byte.</p>
        <button type="submit">{busy ? 'Đang lưu...' : 'Lưu'}</button>
        <button type="button" onClick={() => setForm(null)}>Hủy</button>
      </fieldset>
    </form>}
    {loading ? <p role="status">Đang tải nhân viên...</p> : !error && users.length === 0 ? <p>Chưa có nhân viên.</p> : <div className="table-wrapper"><table>
      <thead><tr><th>ID</th><th>Họ và tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody>{users.map((employee) => <tr key={employee.user_id}>
        <td>{employee.user_id}</td><td>{employee.full_name}</td><td>{employee.email}</td><td>{employee.role_name}</td>
        <td>{Number(employee.is_active) ? 'Hoạt động' : 'Vô hiệu hóa'}</td>
        <td><button disabled={busy} onClick={() => edit(employee.user_id)}>Chi tiết / Sửa</button>
          <button disabled={busy || !Number(employee.is_active) || Number(employee.user_id) === Number(currentUser.user_id)} onClick={() => disable(employee)}>Vô hiệu hóa</button></td>
      </tr>)}</tbody>
    </table></div>}
  </main>;
}
