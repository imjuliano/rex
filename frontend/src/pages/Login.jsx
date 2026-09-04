import { useState } from 'react'
import { Brand } from '../components/Brand.jsx'
import { Alert, Button, Field, Input } from '../components/ui.jsx'
import { friendlyError } from '../lib/errors.js'
import { LIMITS } from '../lib/limits.js'
import { useAuth } from '../context/AuthContext.jsx'

export function Login() {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email.trim(), password)
    } catch (err) {
      setError(friendlyError(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth">
      <section className="auth__stage">
        <span className="auth__glow auth__glow--green" />
        <span className="auth__glow auth__glow--magenta" />

        <div style={{ position: 'relative', zIndex: 1 }}>
          <Brand />
        </div>

        <div className="auth__hero">
          <div className="eyebrow">Acenda sua força de</div>
          <h1 className="display">Vendas</h1>
          <p className="auth__hero-sub">
            Plataforma de incentivo por pontos. Campanhas com verba controlada, motor de pontuação
            auditável e carteira do vendedor construída sobre um ledger imutável.
          </p>
        </div>

        <div className="auth__metrics">
          <div>
            <div className="auth__metric-value">100%</div>
            <div className="auth__metric-label">Ledger como fonte da verdade</div>
          </div>
          <div>
            <div className="auth__metric-value">ACID</div>
            <div className="auth__metric-label">Crédito e verba atômicos</div>
          </div>
          <div>
            <div className="auth__metric-value">JWT</div>
            <div className="auth__metric-label">Acesso por papel</div>
          </div>
        </div>
      </section>

      <section className="auth__panel">
        <div className="auth__form-head">
          <div className="eyebrow">Área restrita</div>
          <h2>Entrar</h2>
          <p>Use suas credenciais para acessar o painel.</p>
        </div>

        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {error && <Alert tone="error">{error}</Alert>}

          <Field label="E-mail">
            <Input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="voce@rex.test"
              autoComplete="username"
              maxLength={LIMITS.userEmail}
              required
            />
          </Field>

          <Field label="Senha">
            <Input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              autoComplete="current-password"
              maxLength={LIMITS.password}
              required
            />
          </Field>

          <Button type="submit" variant="primary" block arrow loading={loading}>
            {loading ? 'Validando' : 'Acessar painel'}
          </Button>
        </form>

        <div className="auth__hint" style={{ borderTop: 0, paddingTop: 0 }}>
          <div className="auth__hint-title">Credenciais para teste</div>
          <p style={{ color: 'var(--cream-dim)', fontSize: 12.5, lineHeight: 1.5, margin: 0 }}>
            Os usuários de seed estão listados no README. Em produção, use o backend para criar
            contas com senhas fortes.
          </p>
        </div>
      </section>
    </div>
  )
}
