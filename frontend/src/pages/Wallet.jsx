import { useCallback, useEffect, useState } from 'react'
import {
  Badge,
  Button,
  Empty,
  Pagination,
  Panel,
  SearchInput,
  Select,
  Stat,
  TableSkeleton,
  Toolbar,
} from '../components/ui.jsx'
import { IconRefresh } from '../components/icons.jsx'
import { api } from '../lib/api.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useDebounced } from '../hooks/useDebounced.js'

function fmtDateTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function Wallet({ onError }) {
  const { token, user } = useAuth()
  const [entries, setEntries] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const debouncedSearch = useDebounced(search)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data, meta } = await api.wallet(token, {
        search: debouncedSearch,
        type,
        page,
        per_page: perPage,
      })
      setEntries(data)
      setMeta(meta)
    } catch (e) {
      onError(e)
    } finally {
      setLoading(false)
    }
  }, [token, debouncedSearch, type, page, perPage, onError])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, type, perPage])

  // The summary always reflects the entire ledger, never the visible page,
  // so filtering never makes the balance look different than it is.
  const s = meta.summary ?? {}

  return (
    <div className="stack">
      <div className="balance">
        <span className="balance__glow" />
        <div className="eyebrow" style={{ position: 'relative' }}>
          Saldo disponível · {user.email}
        </div>
        <div className="balance__value mono-num">
          {loading && !meta.summary ? '—' : (s.balance ?? 0).toLocaleString('pt-BR')}
          <span className="balance__unit">pontos</span>
        </div>
        <div className="balance__meta">
          <div>
            <div className="stat__label">Creditado</div>
            <div style={{ fontFamily: 'var(--cond)', fontSize: 19, fontWeight: 700 }}>
              +{(s.credited ?? 0).toLocaleString('pt-BR')}
            </div>
          </div>
          <div>
            <div className="stat__label">Estornado</div>
            <div style={{ fontFamily: 'var(--cond)', fontSize: 19, fontWeight: 700 }}>
              −{(s.debited ?? 0).toLocaleString('pt-BR')}
            </div>
          </div>
          <div>
            <div className="stat__label">Movimentos</div>
            <div style={{ fontFamily: 'var(--cond)', fontSize: 19, fontWeight: 700 }}>
              {s.total_entries ?? 0}
            </div>
          </div>
        </div>
      </div>

      <div className="stats">
        <Stat label="Vendas creditadas" value={s.credit_entries ?? 0} tone="green" />
        <Stat label="Cancelamentos" value={s.debit_entries ?? 0} tone="magenta" />
        <Stat label="Ticket médio em pontos" value={s.avg_points_per_credit ?? 0} unit="pts" />
      </div>

      <Panel
        title="Extrato"
        subtitle="Ledger imutável — o saldo é a soma de créditos menos débitos."
        flush
        actions={
          <Button variant="ghost" size="sm" onClick={load} loading={loading}>
            {!loading && <IconRefresh />}
            Atualizar
          </Button>
        }
      >
        <Toolbar>
          <SearchInput value={search} onChange={setSearch} placeholder="Descrição da venda…" />
          <Select value={type} onChange={(e) => setType(e.target.value)}>
            <option value="">Créditos e débitos</option>
            <option value="credit">Somente créditos</option>
            <option value="debit">Somente débitos</option>
          </Select>
        </Toolbar>

        {loading ? (
          <TableSkeleton cols={5} />
        ) : entries.length === 0 ? (
          <Empty
            title="Extrato vazio"
            subtitle={
              search || type
                ? 'Nenhum lançamento para o filtro atual.'
                : 'Assim que uma venda sua for aprovada, os pontos aparecem aqui.'
            }
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Descrição</th>
                  <th className="table__num">Pontos</th>
                  <th className="table__num">Campanha</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                {entries.map((e) => (
                  <tr key={e.id}>
                    <td>
                      <Badge tone={e.type === 'credit' ? 'green' : 'red'}>
                        {e.type === 'credit' ? 'crédito' : 'débito'}
                      </Badge>
                    </td>
                    <td className="cell-strong">{e.description}</td>
                    <td
                      className="table__num cell-strong"
                      style={{ color: e.type === 'credit' ? 'var(--green)' : 'var(--red)' }}
                    >
                      {e.signed_points > 0 ? '+' : '−'}
                      {Math.abs(e.signed_points).toLocaleString('pt-BR')}
                    </td>
                    <td className="table__num cell-dim">#{e.campaign_id}</td>
                    <td className="cell-dim">{fmtDateTime(e.created_at)}</td>
                  </tr>
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
