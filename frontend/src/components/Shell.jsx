import { Brand } from './Brand.jsx'
import { Button } from './ui.jsx'
import { IconLogout } from './icons.jsx'
import { useAuth } from '../context/AuthContext.jsx'

function initials(email = '') {
  return email.slice(0, 2).toUpperCase()
}

export function Shell({ nav, active, onNavigate, page, children }) {
  const { user, logout } = useAuth()

  return (
    <div className="shell">
      <aside className="sidebar">
        <div className="sidebar__brand">
          <Brand />
        </div>

        {nav.map((group) => (
          <div key={group.title}>
            <div className="sidebar__group-label">{group.title}</div>
            <nav className="nav">
              {group.items.map((item) => (
                <button
                  key={item.key}
                  className={`nav__item${active === item.key ? ' nav__item--active' : ''}`}
                  onClick={() => onNavigate(item.key)}
                  aria-current={active === item.key ? 'page' : undefined}
                >
                  <item.icon />
                  {item.label}
                </button>
              ))}
            </nav>
          </div>
        ))}

        <div className="sidebar__footer">
          <div className="usercard">
            <div className="avatar">{initials(user.email)}</div>
            <div className="usercard__meta">
              <div className="usercard__name" title={user.email}>
                {user.email}
              </div>
              <div className="usercard__role">{user.role}</div>
            </div>
          </div>
          <Button variant="ghost" size="sm" block onClick={logout}>
            <IconLogout width="14" height="14" />
            Encerrar sessão
          </Button>
        </div>
      </aside>

      <div className="main">
        <div className="magenta-strip" />
        <header className="topbar">
          <div className="topbar__inner">
            <div>
              <div className="topbar__crumb">
                REX / {user.role === 'admin' ? 'Administração' : 'Vendedor'}
              </div>
              <h1 className="topbar__title">{page.title}</h1>
            </div>
            {page.actions}
          </div>
        </header>
        <main className="content">{children}</main>
      </div>
    </div>
  )
}
