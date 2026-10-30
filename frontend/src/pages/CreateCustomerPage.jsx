
import { useState } from 'react';
import { customersApi } from '../api/customers.api';

export default function CreateCustomerPage({ onCreated, onCancel }) {
  const [form, setForm] = useState({
    full_name: '',
    phone: '',
    email: '',
    address: '',
  });

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  function handleChange(event) {
    const { name, value } = event.target;

    setForm((previous) => ({
      ...previous,
      [name]: value,
    }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setSaving(true);
    setError('');

    try {
      const customer = await customersApi.create(form);
      onCreated(customer);
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <main className="container">
      <h1>Tạo hồ sơ khách hàng</h1>
      <p>Nhập thông tin khách hàng mới vào hệ thống.</p>

      <form className="customer-form" onSubmit={handleSubmit}>
        <label>
          Họ và tên *
          <input
            name="full_name"
            value={form.full_name}
            onChange={handleChange}
            required
            maxLength={150}
          />
        </label>

        <label>
          Số điện thoại *
          <input
            name="phone"
            type="tel"
            value={form.phone}
            onChange={handleChange}
            required
          />
        </label>

        <label>
          Email
          <input
            name="email"
            type="email"
            value={form.email}
            onChange={handleChange}
          />
        </label>

        <label>
          Địa chỉ
          <input
            name="address"
            value={form.address}
            onChange={handleChange}
            maxLength={255}
          />
        </label>

        {error && (
          <p className="form-error" role="alert">{error}</p>
        )}

        <div className="form-actions">
          <button type="button" onClick={onCancel}>
            Hủy
          </button>

          <button type="submit" disabled={saving}>
            {saving ? 'Đang lưu...' : 'Lưu hồ sơ'}
          </button>
        </div>
      </form>
    </main>
  );
}
