import { request } from './client';

export const usersApi = {
  list: () => request('/users'),
  detail: (id) => request(`/users/${id}`),
  create: (data) => request('/users', { method: 'POST', body: JSON.stringify(data) }),
  update: (id, data) => request(`/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  disable: (id) => request(`/users/${id}`, { method: 'DELETE' }),
};
