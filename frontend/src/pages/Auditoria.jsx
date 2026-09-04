import { useCallback, useEffect, useState } from 'react'
import {
  Badge,
  Button,
  Empty,
  Input,
  Pagination,
  Panel,
  SearchInput,
  Select,
  TableSkeleton,
  Toolbar,
} from '../components/ui.jsx'
import { api } from '../lib/api.js'
import { friendlyError } from '../lib/errors.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useDebounced } from '../hooks/useDebounced.js'

const MODULES = [
  { key: 'products', label: 'Produtos' },
  { key: 'campaigns', label: 'Campanhas' },
  { key: 'sales', label: 'Vendas' },
  { key: 'users', label: 'Usuários' },
  { key: 'auth', label: 'Autenticação' },
]

export function Auditoria({ onError }) {
  const { token } = useAuth()
  const [logs, setLogs] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)

  const [module, setModule] = useState('products')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [action, setAction] = useState('')
  const [search, setSearch] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [expanded, setExpanded] = useState(null)

  const debouncedSearch = useDebounced(search)

  const load = useCallback(async () => {
    setLoading(true)
    setExpanded(null)
    try {
      const query = { page, per_page: perPage }
      if (action) query.action = action
      if (debouncedSearch) query.search = debouncedSearch
      if (from) query.from = from
      if (to) query.to = to

      const { data, meta } = await api.listAuditLogs(token, module, query)
      setLogs(data)
      setMeta(meta)
    } catch (e) {
      onError({ ...e, message: friendlyError(e) })
    } finally {
      setLoading(false)
    }
  }, [token, module, page, perPage, action, debouncedSearch, from, to, onError])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    setPage(1)
  }, [module, action, debouncedSearch, from, to])

  const actions = meta.summary?.actions ?? []
  const toneFor = (label = '') => {
    if (label.includes('excluído') || label.includes('cancelada') || label.includes('falho'))
      return 'red'
    if (
      label.includes('criado') ||
      label.includes('criada') ||
      label.includes('ativado') ||
      label.includes('bem-sucedido')
    )
      return 'green'
    if (
      label.includes('atualizado') ||
      label.includes('atualizada') ||
      label.includes('encerrada') ||
      label.includes('inativado')
    )
      return 'amber'
    return 'muted'
  }

  return (
    <div className="stack">
      <div className="tabs">
        {MODULES.map((m) => (
          <button
            key={m.key}
            className={`tab${module === m.key ? ' tab--active' : ''}`}
            onClick={() => setModule(m.key)}
          >
            {m.label}
          </button>
        ))}
      </div>

      <Panel
        title={`Auditoria - ${MODULES.find((m) => m.key === module)?.label}`}
        subtitle={`${meta.total ?? 0} registro(s)`}
        flush
      >
        <Toolbar>
          <SearchInput value={search} onChange={setSearch} placeholder="Buscar entity id…" />
          <Select value={action} onChange={(e) => setAction(e.target.value)}>
            <option value="">Todas as ações</option>
            {actions.map((a) => (
              <option key={a.value} value={a.value}>
                {a.label}
              </option>
            ))}
          </Select>
          <Input
            type="datetime-local"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            placeholder="De"
            title="De"
          />
          <Input
            type="datetime-local"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            placeholder="Até"
            title="Até"
          />
        </Toolbar>

        {loading ? (
          <TableSkeleton rows={perPage} cols={5} />
        ) : logs.length === 0 ? (
          <Empty message="Nenhum registro de auditoria encontrado." />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Data/Hora</th>
                  <th>Ação</th>
                  <th>Ator</th>
                  <th>Entidade</th>
                  <th>IP</th>
                  <th style={{ width: 80 }}></th>
                </tr>
              </thead>
              <tbody>
                {logs.map((log) => (
                  <>
                    <tr key={log.id} className={expanded === log.id ? 'row--expanded' : ''}>
                      <td className="mono-num">
                        {new Date(log.occurred_at).toLocaleString('pt-BR')}
                      </td>
                      <td>
                        <Badge tone={toneFor(log.action?.label)}>{log.action?.label}</Badge>
                      </td>
                      <td>
                        {log.actor_email ?? '—'}
                        {log.actor_role && <span className="muted"> · {log.actor_role}</span>}
                      </td>
                      <td className="mono-num">{log.entity_id}</td>
                      <td className="mono-num">{log.ip_address ?? '—'}</td>
                      <td>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setExpanded(expanded === log.id ? null : log.id)}
                        >
                          {expanded === log.id ? 'Fechar' : 'Detalhes'}
                        </Button>
                      </td>
                    </tr>
                    {expanded === log.id && (
                      <tr>
                        <td colSpan={6} className="audit-detail">
                          <div className="audit-detail__block">
                            <strong>PAYLOAD</strong>
                            <pre>{JSON.stringify(log.payload, null, 2)}</pre>
                          </div>
                          {log.diff && (
                            <div className="audit-detail__block">
                              <strong>DIFF</strong>
                              <pre>{JSON.stringify(log.diff, null, 2)}</pre>
                            </div>
                          )}
                          <div className="audit-detail__meta">
                            <span>ID: {log.id}</span>
                            <span>correlation: {log.correlation_id ?? '—'}</span>
                            <span>user-agent: {log.user_agent ?? '—'}</span>
                          </div>
                        </td>
                      </tr>
                    )}
                  </>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <Pagination meta={meta} onPageChange={setPage} onPerPageChange={setPerPage} />
      </Panel>
    </div>
  )
}
