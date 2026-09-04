import { request } from './api-client.js'

export const listAuditLogs = (token, module, query) =>
  request(`/audit/${module}`, { token, query })
