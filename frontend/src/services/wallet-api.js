import { request } from './api-client.js'

export const wallet = (token, query) => request('/me/wallet', { token, query })
