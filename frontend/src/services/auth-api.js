import { request } from './api-client.js'

export const login = (email, password) =>
  request('/auth/login', { method: 'POST', body: { email, password }, allowRenew: false })

export const refresh = () => request('/auth/refresh', { method: 'POST', allowRenew: false })

export const logout = () => request('/auth/logout', { method: 'POST', allowRenew: false })

export function decodeJwt(token) {
  try {
    const part = token.split('.')[1]
    const json = atob(part.replace(/-/g, '+').replace(/_/g, '/'))
    return JSON.parse(decodeURIComponent(escape(json)))
  } catch {
    return null
  }
}
