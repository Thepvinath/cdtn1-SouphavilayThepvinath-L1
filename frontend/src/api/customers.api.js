
import { request } from './client';

export const customersApi = {
  getAll: (keyword = '') => {
    const params = new URLSearchParams();

    if (keyword.trim()) {
      params.set('search', keyword.trim());
    }

    const query = params.toString();

    return request(
      `/customers${query ? `?${query}` : ''}`
    );
  },

  getById: (id) => request(`/customers/${id}`),

  create: (data) =>
    request('/customers', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
    getDuplicates: () => request('/customers/duplicates'),
};
