
export const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

export const CONNECTION_ERROR =
  'Không thể kết nối đến hệ thống. Vui lòng kiểm tra kết nối và thử lại.';

// Goi fetch; loi mang (backend tat, mat ket noi) -> thong bao de hieu
export async function send(url, options = {}) {
  try {
    return await fetch(url, options);
  } catch {
    throw new Error(CONNECTION_ERROR);
  }
}

// Doc phan hoi an toan (BUG-04):
// khong goi response.json() truc tiep, vi phan hoi rong hoac khong phai JSON
// (vi du proxy tra 500/502 khi backend tat) se nem "Unexpected end of JSON input".
export async function parseResponse(response) {
  const text = await response.text();
  let body = null;

  if (text) {
    try {
      body = JSON.parse(text);
    } catch {
      body = null;
    }
  }

  if (!response.ok) {
    if (body?.message) {
      throw new Error(body.message);
    }

    throw new Error(
      response.status >= 500
        ? CONNECTION_ERROR
        : `Yêu cầu thất bại (HTTP ${response.status})`
    );
  }

  if (body === null) {
    throw new Error('Phản hồi từ hệ thống không hợp lệ.');
  }

  return body;
}

// Lay CSRF token tu PHP Session
export async function getCsrfToken() {
  const response = await send(`${BASE_URL}/auth/csrf`, {
    credentials: 'same-origin',
  });

  const body = await parseResponse(response);
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

  const response = await send(`${BASE_URL}${path}`, {
    ...options,
    method,
    headers,
    credentials: 'same-origin',
  });

  const body = await parseResponse(response);

  return body.data ?? body;
}
