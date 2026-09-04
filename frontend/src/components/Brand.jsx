import { IconBull } from './icons.jsx'

export function Brand({ tagline = 'Vendeu, ganhou' }) {
  return (
    <div className="brand">
      <IconBull style={{ color: 'var(--cream)' }} />
      <div>
        <div style={{ display: 'flex', alignItems: 'flex-end', gap: 4 }}>
          <span className="brand__word">REX</span>
          <span className="brand__dot" />
        </div>
        <div className="brand__tag">{tagline}</div>
      </div>
    </div>
  )
}
