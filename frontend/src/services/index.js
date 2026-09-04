import * as auth from './auth-api.js'
import * as products from './products-api.js'
import * as campaigns from './campaigns-api.js'
import * as sales from './sales-api.js'
import * as users from './users-api.js'
import * as wallet from './wallet-api.js'
import * as audit from './audit-api.js'

export const api = {
  ...auth,
  ...products,
  ...campaigns,
  ...sales,
  ...users,
  ...wallet,
  ...audit,
}

export { setAccessTokenRenewer } from './api-client.js'
