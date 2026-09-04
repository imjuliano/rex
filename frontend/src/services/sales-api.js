import { downloadBlob, request } from './api-client.js'

export const listSales = (token, query) => request('/sales', { token, query })
export const exportSales = (token, sellerId) =>
  downloadBlob('/sales/export', token, { seller_id: sellerId })
export const createSale = (token, body) => request('/sales', { method: 'POST', token, body })
export const batchSales = (token, sales) =>
  request('/sales/batch', { method: 'POST', token, body: { sales } })
export const cancelSale = (token, externalId) =>
  request(`/sales/${encodeURIComponent(externalId)}/cancel`, { method: 'POST', token })
