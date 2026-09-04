import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react'
import { api, setAccessTokenRenewer } from '../lib/api.js'

const AuthContext = createContext(null)

/** Renew this far ahead of expiry so no request races the deadline. */
const RENEW_MARGIN_MS = 60_000

/** Pre-refresh-token builds kept the access token here. Left over, it is dead weight. */
const LEGACY_STORAGE_KEY = 'rex.token'

const EMPTY_SESSION = { token: '', user: null, expiresAt: 0 }

function toSession({ token, user, expires_in: expiresIn }) {
  return { token, user, expiresAt: Date.now() + expiresIn * 1000 }
}

/**
 * The access token is deliberately kept in memory only.
 *
 * Persisting it would hand any XSS payload a working credential; the refresh
 * token that survives a reload lives in an HttpOnly cookie that JavaScript
 * cannot read. The cost is that a reload starts with no token, so the provider
 * asks the server to re-issue one before rendering anything.
 */
export function AuthProvider({ children }) {
  const [session, setSession] = useState(EMPTY_SESSION)
  const [bootstrapping, setBootstrapping] = useState(true)

  // Collapses concurrent renewals onto a single /auth/refresh call. Without it,
  // two parallel 401s would each spend the cookie and the second would look
  // like a replayed token.
  const inFlightRenewal = useRef(null)

  const applySession = useCallback((data) => {
    const next = toSession(data)
    setSession(next)
    return next.token
  }, [])

  const clearSession = useCallback(() => setSession(EMPTY_SESSION), [])

  const renew = useCallback(async () => {
    if (inFlightRenewal.current) return inFlightRenewal.current

    inFlightRenewal.current = (async () => {
      try {
        const { data } = await api.refresh()
        return applySession(data)
      } catch {
        // The chain is gone (expired, revoked or replayed): the session is over.
        clearSession()
        return null
      } finally {
        inFlightRenewal.current = null
      }
    })()

    return inFlightRenewal.current
  }, [applySession, clearSession])

  // Let api.js renew transparently on a 401. Registered without teardown on
  // purpose: the provider outlives every consumer, and unregistering midway
  // would drop retries that are already in flight.
  useEffect(() => {
    setAccessTokenRenewer(renew)
  }, [renew])

  // Restore the session from the cookie on first paint.
  useEffect(() => {
    localStorage.removeItem(LEGACY_STORAGE_KEY)

    let cancelled = false
    renew().finally(() => {
      if (!cancelled) setBootstrapping(false)
    })

    return () => {
      cancelled = true
    }
  }, [renew])

  // Renew ahead of expiry so the user never waits on a retry round-trip.
  useEffect(() => {
    if (!session.token) return undefined

    const delay = Math.max(session.expiresAt - Date.now() - RENEW_MARGIN_MS, 0)
    const timer = setTimeout(renew, delay)

    return () => clearTimeout(timer)
  }, [session.token, session.expiresAt, renew])

  const login = useCallback(
    async (email, password) => {
      const { data } = await api.login(email, password)
      if (!data?.token) throw new Error('Resposta de login inválida do servidor.')
      applySession(data)
    },
    [applySession],
  )

  const logout = useCallback(async () => {
    try {
      // Revokes the whole token family server-side and clears the cookie, so
      // the session cannot be resumed from another tab.
      await api.logout()
    } catch {
      // A failed revoke must not strand the user in a logged-in shell.
    } finally {
      clearSession()
    }
  }, [clearSession])

  const value = useMemo(
    () => ({
      token: session.token,
      user: session.user,
      isAuthenticated: Boolean(session.token),
      bootstrapping,
      login,
      logout,
    }),
    [session, bootstrapping, login, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth deve ser usado dentro de AuthProvider')
  return ctx
}
