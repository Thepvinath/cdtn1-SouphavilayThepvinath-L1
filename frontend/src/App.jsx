
import { useEffect, useState } from 'react';
import { customersApi } from './api/customers.api';
import { authApi } from './api/auth.api';

import LoginPage from './pages/LoginPage';
import CreateCustomerPage from './pages/CreateCustomerPage';
import DuplicatesPage from './pages/DuplicatesPage';

export default function App() {
  // Authentication
  const [user, setUser] = useState(null);
  const [authLoading, setAuthLoading] = useState(true);
  const [authError, setAuthError] = useState('');
  const [loggingOut, setLoggingOut] = useState(false);

  // Navigation
  const [page, setPage] = useState('list');

  // Customer List
  const [customers, setCustomers] = useState([]);
  const [searchInput, setSearchInput] = useState('');
  const [keyword, setKeyword] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [refresh, setRefresh] = useState(0);

  // Kiem tra session khi mo website
  useEffect(() => {
    let cancelled = false;

    authApi.me()
      .then((data) => {
        if (!cancelled) {
          setUser(data);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setUser(null);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setAuthLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  // Tai danh sach khach hang sau khi login
  useEffect(() => {
    if (!user) {
      return;
    }

    let cancelled = false;

    customersApi.getAll(keyword)
      .then((data) => {
        if (!cancelled) {
          setCustomers(data);
          setError('');
        }
      })
      .catch((err) => {
        if (!cancelled) {
          setCustomers([]);
          setError(err.message);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [user, keyword, refresh]);

  function reloadCustomers() {
    setLoading(true);
    setRefresh((value) => value + 1);
  }

  function handleSearch(event) {
    event.preventDefault();
    setKeyword(searchInput);
    reloadCustomers();
  }

  function handleLogin(loggedInUser) {
    setUser(loggedInUser);
    setAuthError('');
    setPage('list');
    setLoading(true);
    setSuccess('');
  }

  async function handleLogout() {
    if (loggingOut) return;

    setLoggingOut(true);
    setAuthError('');

    try {
      await authApi.logout();

      setUser(null);
      setCustomers([]);
      setPage('list');
      setKeyword('');
      setSearchInput('');
      setSuccess('');
      setError('');
      setLoading(true);
    } catch (err) {
      setAuthError(err.message || 'Đăng xuất thất bại');
    } finally {
      setLoggingOut(false);
    }
  }

  function handleCreated(customer) {
    setSuccess(
      `Đã tạo hồ sơ khách hàng ${customer.full_name} thành công!`
    );

    setPage('list');
    setSearchInput('');
    setKeyword('');
    reloadCustomers();
  }

  function clearSearch() {
    setSearchInput('');
    setKeyword('');
    reloadCustomers();
  }

  if (authLoading) {
    return (
      <div className="auth-loading" role="status">
        Đang kiểm tra phiên đăng nhập...
      </div>
    );
  }

  return (
    <div className="app">
      <header className="header">
        <h2>SMART CRM</h2>

        <span>Mekong Mobile</span>
      </header>

      {!user ? (
        <LoginPage onLogin={handleLogin} />
      ) : (
        <>
          <nav className="nav">
            <button
              type="button"
              className="nav-link"
              onClick={() => setPage('list')}
            >
              Trang chủ
            </button>

            <button
              type="button"
              className="nav-link"
              onClick={() => setPage('list')}
            >
              Khách hàng
            </button>

            <button
              type="button"
              className="nav-link"
              onClick={() => setPage('duplicates')}
            >
              Hồ sơ trùng
            </button>

            <div className="nav-user">
              <span>
                {user.full_name}
                {' – '}
                {user.role_name}
              </span>

              <button
                type="button"
                className="logout-button"
                onClick={handleLogout}
                disabled={loggingOut}
              >
                {loggingOut ? 'Đang thoát...' : 'Đăng xuất'}
              </button>
            </div>
          </nav>

          {authError && (
            <div className="auth-error" role="alert">
              {authError}
            </div>
          )}

          {page === 'create' ? (
            <CreateCustomerPage
              onCreated={handleCreated}
              onCancel={() => setPage('list')}
            />
          ) : page === 'duplicates' ? (
            <DuplicatesPage
              onBack={() => setPage('list')}
            />
          ) : (
            <main className="container">
              <div className="page-heading">
                <div>
                  <h1>Danh sách khách hàng</h1>
                  <p>
                    Quản lý và tra cứu hồ sơ khách hàng
                  </p>
                </div>

                <button
                  type="button"
                  onClick={() => {
                    setSuccess('');
                    setPage('create');
                  }}
                >
                  + Tạo khách hàng
                </button>
              </div>

              {success && (
                <div
                  className="success-message"
                  role="status"
                >
                  {success}
                </div>
              )}

              <form
                className="search-bar"
                onSubmit={handleSearch}
              >
                <input
                  type="text"
                  placeholder="Tìm theo họ tên hoặc số điện thoại..."
                  value={searchInput}
                  onChange={(event) =>
                    setSearchInput(event.target.value)
                  }
                />

                <button type="submit">
                  Tìm kiếm
                </button>
              </form>

              {loading ? (
                <div className="message">
                  Đang tải dữ liệu...
                </div>
              ) : error ? (
                <div className="message error" role="alert">
                  <p>{error}</p>

                  <button
                    type="button"
                    onClick={reloadCustomers}
                  >
                    Thử lại
                  </button>
                </div>
              ) : customers.length === 0 ? (
                <div className="message">
                  <p>Không tìm thấy khách hàng phù hợp.</p>

                  <button
                    type="button"
                    onClick={clearSearch}
                  >
                    Xóa bộ lọc
                  </button>
                </div>
              ) : (
                <div className="table-wrapper">
                  <table>
                    <thead>
                      <tr>
                        <th>Mã KH</th>
                        <th>Họ và tên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Trạng thái</th>
                      </tr>
                    </thead>

                    <tbody>
                      {customers.map((customer) => (
                        <tr key={customer.customer_id}>
                          <td>KH{customer.customer_id}</td>
                          <td>{customer.full_name}</td>
                          <td>{customer.phone}</td>
                          <td>{customer.email || '-'}</td>
                          <td>
                            <span className="status">
                              {customer.status}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </main>
          )}
        </>
      )}
    </div>
  );
}
