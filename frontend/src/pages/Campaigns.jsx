import { useCallback, useEffect, useState } from 'react'
import {
  Badge,
  Button,
  Empty,
  Field,
  Input,
  Modal,
  Pagination,
  Panel,
  Progress,
  SearchInput,
  Select,
  Stat,
  TableSkeleton,
  Toolbar,
} from '../components/ui.jsx'
import { IconPlus } from '../components/icons.jsx'
import { api } from '../lib/api.js'
import { friendlyError } from '../lib/errors.js'
import { LIMITS } from '../lib/limits.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useDebounced } from '../hooks/useDebounced.js'

const BLANK = { name: '', budget_total: '', starts_at: '', ends_at: '' }

function toSqlDate(value) {
  // input[type=datetime-local] -> "2026-01-01T08:30" => "2026-01-01 08:30:00"
  if (!value) return ''
  const [d, t = '00:00'] = value.split('T')
  const parts = t.split(':')
  return `${d} ${parts[0] ?? '00'}:${parts[1] ?? '00'}:${parts[2] ?? '00'}`
}

/** ISO 8601 -> value accepted by input[type=datetime-local]. */
function toLocalInput(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function fmt(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusLabel(c) {
  if (c.accepting_sales) return { tone: 'green', text: 'recebendo' }
  if (c.status === 'closed') return { tone: 'muted', text: 'encerrada' }
  if (c.budget.exhausted) return { tone: 'red', text: 'verba esgotada' }
  return { tone: 'amber', text: 'fora do período' }
}

export function Campaigns({ notify, onError }) {
  const { token } = useAuth()
  const [campaigns, setCampaigns] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(BLANK)

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [sort, setSort] = useState('created_at')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const debouncedSearch = useDebounced(search)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data, meta } = await api.listCampaigns(token, {
        search: debouncedSearch,
        status,
        sort,
        order: sort === 'name' ? 'asc' : 'desc',
        page,
        per_page: perPage,
      })
      setCampaigns(data)
      setMeta(meta)
    } catch (e) {
      onError({ ...e, message: friendlyError(e) })
    } finally {
      setLoading(false)
    }
  }, [token, debouncedSearch, status, sort, page, perPage, onError])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, sort, perPage])

  const openCreate = () => {
    setForm(BLANK)
    setEditing(null)
    setModalOpen(true)
  }

  const openEdit = (c) => {
    setForm({
      name: c.name,
      budget_total: String(c.budget.total),
      starts_at: toLocalInput(c.period.starts_at),
      ends_at: toLocalInput(c.period.ends_at),
    })
    setEditing(c)
    setModalOpen(true)
  }

  const closeModal = () => {
    setModalOpen(false)
    setEditing(null)
    setForm(BLANK)
  }

  const submit = async (e) => {
    e.preventDefault()
    setSaving(true)
    const body = {
      name: form.name.trim(),
      budget_total: Number(form.budget_total),
      starts_at: toSqlDate(form.starts_at),
      ends_at: toSqlDate(form.ends_at),
    }
    try {
      if (editing) {
        await api.updateCampaign(token, editing.id, body)
        notify(`Campanha "${body.name}" atualizada.`)
      } else {
        await api.createCampaign(token, body)
        notify(`Campanha "${body.name}" criada.`)
      }
      closeModal()
      load()
    } catch (err) {
      onError({ ...err, message: friendlyError(err) })
    } finally {
      setSaving(false)
    }
  }

  const toggleStatus = async (c) => {
    try {
      if (c.status === 'active') {
        await api.closeCampaign(token, c.id)
        notify(`Campanha "${c.name}" encerrada. Não recebe novas vendas.`)
      } else {
        await api.updateCampaign(token, c.id, { status: 'active' })
        notify(`Campanha "${c.name}" reaberta.`)
      }
      load()
    } catch (err) {
      onError({ ...err, message: friendlyError(err) })
    }
  }

  const summary = meta.summary ?? {}
  // Points already credited are final, so the budget can never shrink past them.
  const minBudget = editing?.budget.used ?? 1

  return (
    <div className="stack">
      <div className="stats">
        <Stat
          label="Campanhas ativas"
          value={summary.active_campaigns ?? 0}
          tone="green"
          foot={`${summary.total_campaigns ?? 0} no total`}
        />
        <Stat
          label="Verba consumida"
          value={(summary.budget_used ?? 0).toLocaleString('pt-BR')}
          unit="pts"
          tone="magenta"
          foot={`de ${(summary.budget_total ?? 0).toLocaleString('pt-BR')} pts alocados`}
        />
        <Stat
          label="Verba disponível"
          value={(summary.budget_remaining ?? 0).toLocaleString('pt-BR')}
          unit="pts"
          tone="amber"
          foot={`${summary.budget_usage_pct ?? 0}% consumido no total`}
        />
      </div>

      <Panel
        title="Campanhas"
        subtitle={`${meta.total ?? 0} registro(s)`}
        flush
        actions={
          <Button variant="primary" size="sm" onClick={openCreate}>
            <IconPlus />
            Nova campanha
          </Button>
        }
      >
        <Toolbar>
          <SearchInput value={search} onChange={setSearch} placeholder="Nome da campanha…" />
          <Select value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="">Todos os status</option>
            <option value="active">Ativas</option>
            <option value="closed">Encerradas</option>
          </Select>
          <Select value={sort} onChange={(e) => setSort(e.target.value)}>
            <option value="created_at">Mais recentes</option>
            <option value="name">Nome</option>
            <option value="ends_at">Data de fim</option>
            <option value="budget_used">Verba consumida</option>
          </Select>
        </Toolbar>

        {loading ? (
          <TableSkeleton cols={6} />
        ) : campaigns.length === 0 ? (
          <Empty
            title="Nenhuma campanha"
            subtitle={
              search || status
                ? 'Nenhum resultado para o filtro atual.'
                : 'Crie uma campanha para começar a pontuar.'
            }
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Campanha</th>
                  <th style={{ minWidth: 190 }}>Consumo da verba</th>
                  <th className="table__num">Disponível</th>
                  <th>Período</th>
                  <th>Status</th>
                  <th style={{ textAlign: 'right' }}>Ações</th>
                </tr>
              </thead>
              <tbody>
                {campaigns.map((c) => {
                  const badge = statusLabel(c)
                  return (
                    <tr key={c.id}>
                      <td>
                        <div className="cell-strong">{c.name}</div>
                        <div className="cell-dim">#{c.id}</div>
                      </td>
                      <td>
                        <Progress used={c.budget.used} total={c.budget.total} />
                      </td>
                      <td className="table__num cell-strong">
                        {c.budget.remaining.toLocaleString('pt-BR')}
                      </td>
                      <td className="cell-dim">
                        {fmt(c.period.starts_at)} → {fmt(c.period.ends_at)}
                        {c.period.days_remaining > 0 && (
                          <div>
                            <strong>{c.period.days_remaining} dia(s) restante(s)</strong>
                          </div>
                        )}
                      </td>
                      <td>
                        {/* accepting_sales is derived server-side: active + in period + budget left */}
                        <Badge tone={badge.tone}>{badge.text}</Badge>
                      </td>
                      <td>
                        <div className="table__actions">
                          <Button variant="ghost" size="sm" onClick={() => openEdit(c)}>
                            Editar
                          </Button>
                          <Button
                            variant={c.status === 'active' ? 'danger' : 'outline'}
                            size="sm"
                            onClick={() => toggleStatus(c)}
                          >
                            {c.status === 'active' ? 'Encerrar' : 'Reabrir'}
                          </Button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}

        <Pagination meta={meta} onPageChange={setPage} onPerPageChange={setPerPage} />
      </Panel>

      <Modal
        open={modalOpen}
        onClose={closeModal}
        size="md"
        title={editing ? `Editar campanha #${editing.id}` : 'Nova campanha'}
        subtitle={
          editing
            ? `${editing.budget.used.toLocaleString('pt-BR')} pts já creditados — a verba não pode ser reduzida abaixo desse valor.`
            : 'Verba em pontos. Vendas que estouram o limite são rejeitadas por completo.'
        }
      >
        <form onSubmit={submit}>
          <div className="form-grid">
            <Field label="Nome">
              <Input
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                placeholder="Campanha Verão"
                maxLength={LIMITS.campaignName}
                required
              />
            </Field>
            <Field
              label="Verba total"
              hint={editing ? `mínimo ${minBudget.toLocaleString('pt-BR')} pts` : 'pontos'}
            >
              <Input
                type="number"
                min={minBudget}
                max={LIMITS.budgetTotalMax}
                value={form.budget_total}
                onChange={(e) => setForm({ ...form, budget_total: e.target.value })}
                placeholder="10000"
                required
              />
            </Field>
            <Field label="Início">
              <Input
                type="datetime-local"
                value={form.starts_at}
                onChange={(e) => setForm({ ...form, starts_at: e.target.value })}
                max={form.ends_at || undefined}
                required
              />
            </Field>
            <Field label="Fim">
              <Input
                type="datetime-local"
                value={form.ends_at}
                onChange={(e) => setForm({ ...form, ends_at: e.target.value })}
                min={form.starts_at || undefined}
                required
              />
            </Field>
          </div>
          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Cancelar
            </Button>
            <Button type="submit" variant="primary" loading={saving}>
              {editing ? 'Salvar alterações' : 'Criar campanha'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
