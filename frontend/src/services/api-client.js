const BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080'

/**
 * Mirrors the backend error envelope:
 * { error: ERROR_CODE, message, status, trace_id, details? }
 */
export class ApiError extends Error {
  constructor({ message, status, code, details, traceId }) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.details = details ?? {}
    this.traceId = traceId
  }

  /** True when the backend refused because the state already exists. */
  get isIdempotentConflict() {
    return this.code === 'SALE_ALREADY_EXISTS' || this.code === 'SALE_ALREADY_CANCELED'
  }
}

function buildQuery(params) {
  if (!params) return ''
  const search = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null || value === '') continue
    search.append(key, String(value))
  }
  const qs = search.toString()
  return qs ? `?${qs}` : ''
}

/**
 * Access tokens only live 15 minutes, so an expired one is routine rather than
 * exceptional. AuthContext registers a renewer here and every request retries
 * itself once with a fresh token, which keeps the pages unaware that tokens
 * rotate at all.
 */
let renewAccessToken = null

export function setAccessTokenRenewer(fn) {
  renewAccessToken = fn
}

/** Codes that mean "this access token is stale", i.e. worth one retry. */
const STALE_TOKEN_CODES = new Set(['INVALID_TOKEN', 'MISSING_TOKEN'])

function isStaleToken(status, code) {
  return status === 401 && STALE_TOKEN_CODES.has(code)
}

async function parseError(res) {
  const payload = await res.json().catch(() => ({}))
  return new ApiError({
    message: payload.message || payload.error || `Erro HTTP ${res.status}`,
    status: res.status,
    code: payload.error || 'UNKNOWN',
    details: payload.details,
    traceId: payload.trace_id,
  })
}

async function send(path, { method = 'GET', token, body, query } = {}) {
  const headers = {}
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (token) headers.Authorization = `Bearer ${token}`

  try {
    return await fetch(`${BASE}${path}${buildQuery(query)}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      // Required for the HttpOnly refresh cookie to travel on /auth calls.
      credentials: 'include',
    })
  } catch {
    throw new ApiError({
      message: 'Não foi possível falar com a API. O backend está no ar?',
      status: 0,
      code: 'NETWORK_ERROR',
    })
  }
}

export async function request(path, options = {}) {
  const { allowRenew = true, ...rest } = options

  let res = await send(path, rest)

  if (res.status === 401 && allowRenew && renewAccessToken) {
    const { code } = await peekError(res)
    if (isStaleToken(res.status, code)) {
      const fresh = await renewAccessToken()
      // No fresh token means the refresh chain is gone; fall through and let
      // the original 401 surface so the UI can send the user back to login.
      if (fresh) res = await send(path, { ...rest, token: fresh })
    }
  }

  if (res.status === 204) return { data: null, meta: {}, links: {} }

  if (!res.ok) throw await parseError(res)

  const payload = await res.json().catch(() => ({}))

  // Every success is { data, meta?, links? }; normalise so callers can
  // destructure without guarding for the optional keys.
  return { data: payload.data, meta: payload.meta ?? {}, links: payload.links ?? {} }
}

/** Reads the error code without consuming the body of the original response. */
async function peekError(res) {
  const payload = await res
    .clone()
    .json()
    .catch(() => ({}))
  return { code: payload.error || 'UNKNOWN' }
}

export async function downloadBlob(path, token, query) {
  let res = await send(path, { token, query })

  if (res.status === 401 && renewAccessToken) {
    const { code } = await peekError(res)
    if (isStaleToken(res.status, code)) {
      const fresh = await renewAccessToken()
      if (fresh) res = await send(path, { token: fresh, query })
    }
  }

  if (!res.ok) throw await parseError(res)

  return res.blob()
}
