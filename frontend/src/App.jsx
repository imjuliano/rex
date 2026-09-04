import { useCallback, useState } from 'react'
import { Brand } from './components/Brand.jsx'
import { Shell } from './components/Shell.jsx'
import { Toasts } from './components/ui.jsx'
import {
  IconAudit,
  IconBox,
  IconPeople,
  IconReceipt,
  IconTarget,
  IconWallet,
} from './components/icons.jsx'
import { useAuth } from './context/AuthContext.jsx'
import { friendlyError } from './lib/errors.js'
import { useToasts } from './hooks/useToasts.js'
import { Login } from './pages/Login.jsx'
import { Products } from './pages/Products.jsx'
import { Campaigns } from './pages/Campaigns.jsx'
import { Sales } from './pages/Sales.jsx'
import { Wallet } from './pages/Wallet.jsx'
import { Users } from './pages/Users.jsx'
import { Auditoria } from './pages/Auditoria.jsx'

const ADMIN_NAV = [
  {
    title: 'Operação',
    items: [
      { key: 'products', label: 'Produtos', icon: IconBox, title: 'Catálogo de produtos' },
      { key: 'campaigns', label: 'Campanhas', icon: IconTarget, title: 'Campanhas e verba' },
      { key: 'sales', label: 'Vendas', icon: IconReceipt, title: 'Lançamento de vendas' },
    ],
  },
  {
    title: 'Admin',
    items: [
      { key: 'auditoria', label: 'Auditoria', icon: IconAudit, title: 'Registro de auditoria' },
      { key: 'users', label: 'Usuários', icon: IconPeople, title: 'Gestão de usuários' },
    ],
  },
]

const SELLER_NAV = [
  {
    title: 'Minha conta',
    items: [{ key: 'wallet', label: 'Carteira', icon: IconWallet, title: 'Minha carteira' }],
  },
]

/**
 * Shown while the provider trades the refresh cookie for an access token.
 * Without it, every reload would flash the login screen before the session
 * comes back.
 */
function Bootstrapping() {
  return (
    <div className="auth" style={{ placeItems: 'center' }}>
      <section
        className="auth__panel"
        style={{ display: 'grid', gap: 18, justifyItems: 'center', textAlign: 'center' }}
      >
        <Brand />
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, color: 'var(--cream-dim)' }}>
          <span className="spinner" style={{ borderTopColor: 'var(--cream)' }} />
          <span style={{ fontSize: 13 }}>Restaurando sessão</span>
        </div>
      </section>
    </div>
  )
}

export default function App() {
  const { isAuthenticated, user, logout, bootstrapping } = useAuth()
  const { items, push, dismiss } = useToasts()
  const [active, setActive] = useState(null)

  const handleError = useCallback(
    (err) => {
      // api.js already tried to renew the access token, so a 401 reaching here
      // means the refresh chain itself is gone.
      if (err?.status === 401) {
        logout()
        push('Sessão expirada. Entre novamente.', 'error')
        return
      }
      // 5xx carries a correlation id so the user can quote it in a bug report.
      const suffix = err?.status >= 500 && err?.traceId ? ` (ref ${err.traceId})` : ''
      push(`${friendlyError(err)}${suffix}`, 'error')
    },
    [logout, push],
  )

  if (bootstrapping) {
    return <Bootstrapping />
  }

  if (!isAuthenticated) {
    return (
      <>
        <Login />
        <Toasts items={items} onDismiss={dismiss} />
      </>
    )
  }

  const nav = user.role === 'admin' ? ADMIN_NAV : SELLER_NAV
  const allItems = nav.flatMap((g) => g.items)
  const current = allItems.find((n) => n.key === active) ?? allItems[0]

  const pages = {
    products: <Products notify={push} onError={handleError} />,
    campaigns: <Campaigns notify={push} onError={handleError} />,
    sales: <Sales notify={push} onError={handleError} />,
    wallet: <Wallet onError={handleError} />,
    users: <Users notify={push} onError={handleError} />,
    auditoria: <Auditoria onError={handleError} />,
  }

  return (
    <>
      <Shell nav={nav} active={current.key} onNavigate={setActive} page={{ title: current.title }}>
        {pages[current.key]}
      </Shell>
      <Toasts items={items} onDismiss={dismiss} />
    </>
  )
}
