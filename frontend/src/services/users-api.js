import { request } from './api-client.js'

export const listUsers = (token, query) => request('/users', { token, query })
export const searchUsers = (token, search, role, page = 1, perPage = 20) =>
  request('/users', { token, query: { search, role, page, per_page: perPage } })
export const createUser = (token, body) => request('/users', { method: 'POST', token, body })
export const updateUser = (token, id, body) =>
  request(`/users/${id}`, { method: 'PUT', token, body })
export const softDeleteUser = (token, id) =>
  request(`/users/${id}`, { method: 'DELETE', token })
