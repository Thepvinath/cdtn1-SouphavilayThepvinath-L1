
const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

// Lay CSRF token tu PHP Session
export async function getCsrfToken() {
  const response = await fetch(`${BASE_URL}/auth/csrf`, {
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error('Khong the lay CSRF token');
  }

  const body = await response.json();
  const token = body.data?.csrf_token;

  if (!token) {
    throw new Error('CSRF token khong hop le');
  }

  return token;
}

// Goi API va tu dong gui CSRF token cho request thay doi du lieu
export async function request(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase();

  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  // POST, PUT, PATCH, DELETE can CSRF token
  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
    headers['X-CSRF-Token'] = await getCsrfToken();
  }

  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    method,
    headers,
    credentials: 'same-origin',
  });

  const body = await response.json();

  if (!response.ok) {
    throw new Error(
      body.message || 'Khong the ket noi den he thong'
    );
  }

  return body.data ?? body;
}
