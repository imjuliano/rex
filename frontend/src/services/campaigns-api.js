import { request } from './api-client.js'

export const listCampaigns = (token, query) => request('/campaigns', { token, query })
export const createCampaign = (token, body) =>
  request('/campaigns', { method: 'POST', token, body })
export const updateCampaign = (token, id, body) =>
  request(`/campaigns/${id}`, { method: 'PUT', token, body })
export const closeCampaign = (token, id) =>
  request(`/campaigns/${id}`, { method: 'DELETE', token })
export const searchCampaigns = (token, search, page = 1, perPage = 20) =>
  request('/campaigns', { token, query: { search, status: 'active', page, per_page: perPage } })
