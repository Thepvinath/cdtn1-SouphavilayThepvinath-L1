import { useState } from 'react';
import { authApi } from '../api/auth.api';

export default function LoginPage({ onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(event) {
    event.preventDefault();

    if (loading) return;

    setLoading(true);
    setError('');

    try {
      const user = await authApi.login(email, password);

      setPassword('');
      onLogin(user);
    } catch (err) {
      setError(err.message || 'Đăng nhập thất bại');
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="login-page">
      <div className="login-card">
        <div className="login-heading">
          <h1>SMART CRM</h1>
          <p>Mekong Mobile</p>
        </div>

        <h2>Đăng nhập hệ thống</h2>
        <p className="login-description">
          Vui lòng đăng nhập để quản lý hồ sơ khách hàng.
        </p>

        <form onSubmit={handleSubmit}>
          <label htmlFor="login-email">
            Email
          </label>

          <input
            id="login-email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            placeholder="Nhập email"
            autoComplete="username"
            required
          />

          <label htmlFor="login-password">
            Mật khẩu
          </label>

          <input
            id="login-password"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            placeholder="Nhập mật khẩu"
            autoComplete="current-password"
            required
          />

          {error && (
            <div className="login-error" role="alert">
              {error}
            </div>
          )}

          <button
            type="submit"
            className="login-button"
            disabled={loading}
          >
            {loading ? 'Đang đăng nhập...' : 'Đăng nhập'}
          </button>
        </form>

        <p className="login-footer">
          Smart CRM – Customer Profile Management
        </p>
      </div>
    </main>
  );
}
