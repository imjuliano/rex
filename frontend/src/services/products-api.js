import { request } from './api-client.js'

export const listProducts = (token, query) => request('/products', { token, query })
export const createProduct = (token, body) => request('/products', { method: 'POST', token, body })
export const updateProduct = (token, id, body) =>
  request(`/products/${id}`, { method: 'PUT', token, body })
export const deactivateProduct = (token, id) =>
  request(`/products/${id}`, { method: 'DELETE', token })
export const softDeleteProduct = (token, id) =>
  request(`/products/${id}/delete`, { method: 'POST', token })
export const searchProducts = (token, search, page = 1, perPage = 20) =>
  request('/products', {
    token,
    query: { search, sort: 'name', order: 'asc', page, per_page: perPage },
  })
