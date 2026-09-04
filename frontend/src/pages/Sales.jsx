import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  Alert,
  Badge,
  Button,
  Combobox,
  Empty,
  Field,
  FileInput,
  Input,
  Modal,
  Pagination,
  Panel,
  SearchInput,
  Select,
  Stat,
  TableSkeleton,
  Textarea,
  Toolbar,
} from '../components/ui.jsx'
import { IconPlus } from '../components/icons.jsx'
import { api } from '../lib/api.js'
import { friendlyError } from '../lib/errors.js'
import { LIMITS } from '../lib/limits.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useDebounced } from '../hooks/useDebounced.js'

const BLANK = {
  external_id: '',
  campaign_id: '',
  seller_id: '',
  product_id: '',
  quantity: '1',
  unit_value: '',
}

const CSV_COLUMNS = [
  'external_id',
  'campaign_id',
  'seller_id',
  'product_id',
  'quantity',
  'unit_value',
]

const SAMPLE_CSV = `${CSV_COLUMNS.join(',')}
VENDA-2026-B1,1,2,1,3,199.90
VENDA-2026-B2,1,3,2,5,89.90`

/** Error codes are stable; their English messages are not meant for end users. */
const FRIENDLY_ERROR = {
  PRODUCT_INACTIVE: 'produto inativo',
  PRODUCT_NOT_FOUND: 'produto não encontrado',
  SELLER_NOT_FOUND: 'vendedor não encontrado',
  CAMPAIGN_NOT_FOUND: 'campanha não encontrada',
  CAMPAIGN_NOT_ACTIVE: 'campanha encerrada',
  CAMPAIGN_OUT_OF_PERIOD: 'fora do período da campanha',
  INSUFFICIENT_BUDGET: 'verba insuficiente',
  INVALID_FIELD: 'campo inválido',
  MISSING_FIELD: 'campo obrigatório ausente',
  DATABASE_ERROR: 'falha ao salvar no banco',
}

function friendlyBatchSummary(meta) {
  if (!meta) return null
  const parts = [`${meta.created} criadas`]
  if (meta.skipped) parts.push(`${meta.skipped} já existentes`)
  if (meta.errors) parts.push(`${meta.errors} com erro`)
  if (meta.points_credited) parts.push(`+${meta.points_credited.toLocaleString('pt-BR')} pts`)
  return parts.join(' · ')
}

function groupedErrors(results) {
  const grouped = results
    .filter((r) => r.status === 'error')
    .reduce((acc, r) => {
      const label = FRIENDLY_ERROR[r.code] ?? r.code
      acc[label] = (acc[label] ?? 0) + 1
      return acc
    }, {})
  return Object.entries(grouped)
    .map(([label, count]) => `${count} ${label}`)
    .join(', ')
}

function fmtDateTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const money = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

export function Sales({ notify, onError }) {
  const { token } = useAuth()

  const [selectedCampaign, setSelectedCampaign] = useState(null)
  const [selectedSeller, setSelectedSeller] = useState(null)
  const [selectedProduct, setSelectedProduct] = useState(null)

  const [modal, setModal] = useState(null) // 'create' | 'cancel' | 'import' | 'export'
  const [form, setForm] = useState(BLANK)
  const [posting, setPosting] = useState(false)
  const [cancelId, setCancelId] = useState('')
  const [canceling, setCanceling] = useState(false)

  const [exportSellerId, setExportSellerId] = useState('')
  const [selectedExportSeller, setSelectedExportSeller] = useState(null)
  const [exporting, setExporting] = useState(false)

  const [batchCsv, setBatchCsv] = useState('')
  const [batchFileName, setBatchFileName] = useState('')
  const [batching, setBatching] = useState(false)
  const [batchResult, setBatchResult] = useState(null)

  const [sales, setSales] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const debouncedSearch = useDebounced(search)

  const loadSales = useCallback(async () => {
    setLoading(true)
    try {
      const { data, meta } = await api.listSales(token, {
        search: debouncedSearch,
        status,
        page,
        per_page: perPage,
      })
      setSales(data)
      setMeta(meta)
    } catch (e) {
      onError({ ...e, message: friendlyError(e) })
    } finally {
      setLoading(false)
    }
  }, [token, debouncedSearch, status, page, perPage, onError])

  useEffect(() => {
    loadSales()
  }, [loadSales])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, perPage])

  const refreshAll = () => loadSales()

  const exportSales = async (e) => {
    e.preventDefault()
    if (!exportSellerId) {
      notify('Selecione um vendedor para exportar.', 'error')
      return
    }
    setExporting(true)
    try {
      const blob = await api.exportSales(token, Number(exportSellerId))
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `sales-seller-${exportSellerId}.csv`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
      closeModal()
      notify('CSV exportado com sucesso.')
    } catch (err) {
      onError({ ...err, message: friendlyError(err) })
    } finally {
      setExporting(false)
    }
  }

  const closeModal = () => {
    setModal(null)
    setExportSellerId('')
    setSelectedExportSeller(null)
  }

  const preview = useMemo(() => {
    const qty = Number(form.quantity) || 0
    const points = selectedProduct ? qty * selectedProduct.points_per_unit : 0
    if (!selectedCampaign) return { points, fits: true }
    const left = selectedCampaign.budget.remaining
    return { points, left, fits: points <= left }
  }, [form.quantity, selectedProduct, selectedCampaign])

  const submit = async (e) => {
    e.preventDefault()
    setPosting(true)
    const externalId = form.external_id.trim()
    try {
      const { data } = await api.createSale(token, {
        external_id: externalId,
        campaign_id: Number(form.campaign_id),
        seller_id: Number(form.seller_id),
        product_id: Number(form.product_id),
        quantity: Number(form.quantity),
        unit_value: Number(form.unit_value),
      })
      notify(
        `Venda ${externalId} aprovada: +${data.points} pts · restam ` +
          `${data.campaign.budget_remaining.toLocaleString('pt-BR')} pts na campanha.`,
      )
      setForm(BLANK)
      setSelectedProduct(null)
      setSelectedCampaign(null)
      setSelectedSeller(null)
      closeModal()
      refreshAll()
    } catch (err) {
      if (err.isIdempotentConflict) {
        notify(`Venda ${externalId} já registrada — nenhum ponto duplicado.`, 'error')
      } else {
        onError({ ...err, message: friendlyError(err) })
      }
    } finally {
      setPosting(false)
    }
  }

  const cancelByExternalId = async (externalId, { silentIdempotent = false } = {}) => {
    try {
      const { data } = await api.cancelSale(token, externalId)
      notify(`Venda ${externalId} cancelada: −${data.points_reversed} pts estornados.`)
      refreshAll()
      return true
    } catch (err) {
      if (err.isIdempotentConflict && !silentIdempotent) {
        notify(`Venda ${externalId} já estava cancelada — sem estorno duplicado.`, 'error')
      } else {
        onError({ ...err, message: friendlyError(err) })
      }
      return false
    }
  }

  const cancel = async (e) => {
    e.preventDefault()
    setCanceling(true)
    const ok = await cancelByExternalId(cancelId.trim())
    if (ok) {
      setCancelId('')
      closeModal()
    }
    setCanceling(false)
  }

  /** Tolerates BOM, CRLF, quoted values and a reordered header. */
  function parseCsv(text) {
    const lines = text
      .replace(/^\uFEFF/, '')
      .split(/\r?\n/)
      .filter((l) => l.trim() !== '')
    if (lines.length < 2) {
      return { rows: [], error: 'O arquivo precisa de cabeçalho e ao menos uma linha.' }
    }

    const header = lines[0].split(',').map((h) => h.trim().replace(/^"|"$/g, '').toLowerCase())
    const missing = CSV_COLUMNS.filter((c) => !header.includes(c))
    if (missing.length) {
      return { rows: [], error: `Colunas ausentes no cabeçalho: ${missing.join(', ')}.` }
    }

    const index = Object.fromEntries(CSV_COLUMNS.map((c) => [c, header.indexOf(c)]))
    const rows = []
    for (let i = 1; i < lines.length; i++) {
      const cols = lines[i].split(',').map((v) => v.trim().replace(/^"|"$/g, ''))
      const pick = (key) => cols[index[key]]
      if (!pick('external_id')) continue
      rows.push({
        external_id: pick('external_id'),
        campaign_id: Number(pick('campaign_id')),
        seller_id: Number(pick('seller_id')),
        product_id: Number(pick('product_id')),
        quantity: Number(pick('quantity')),
        unit_value: Number(pick('unit_value')),
      })
    }
    return { rows, error: rows.length ? null : 'Nenhuma linha válida encontrada.' }
  }

  const runBatch = async (e) => {
    e.preventDefault()
    const { rows, error } = parseCsv(batchCsv)
    if (error) {
      notify(error, 'error')
      return
    }
    setBatching(true)
    setBatchResult(null)
    try {
      const { data, meta } = await api.batchSales(token, rows)
      setBatchResult({ ...meta, results: data.results })
      notify(friendlyBatchSummary(meta), meta.errors ? 'error' : undefined)
      refreshAll()
    } catch (err) {
      onError({ ...err, message: friendlyError(err) })
    } finally {
      setBatching(false)
    }
  }

  const summary = meta.summary ?? {}

  return (
    <div className="stack">
      <div className="stats">
        <Stat label="Vendas no filtro" value={summary.matching_sales ?? 0} tone="magenta" />
        <Stat label="Aprovadas" value={summary.approved ?? 0} tone="green" />
        <Stat
          label="Faturamento bruto"
          value={money(summary.gross_value ?? 0)}
          foot={`${summary.canceled ?? 0} venda(s) cancelada(s)`}
        />
      </div>

      <Panel
        title="Vendas lançadas"
        subtitle={`${meta.total ?? 0} registro(s)`}
        flush
        actions={
          <div className="panel__actions">
            <Button variant="ghost" size="sm" onClick={() => setModal('export')}>
              Exportar CSV
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setModal('import')}>
              Importar CSV
            </Button>
            <Button variant="outline" size="sm" onClick={() => setModal('cancel')}>
              Cancelar por ID
            </Button>
            <Button variant="primary" size="sm" onClick={() => setModal('create')}>
              <IconPlus />
              Lançar venda
            </Button>
          </div>
        }
      >
        <Toolbar>
          <SearchInput
            value={search}
            onChange={setSearch}
            placeholder="External ID, produto ou vendedor…"
          />
          <Select value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="">Todos os status</option>
            <option value="approved">Aprovadas</option>
            <option value="canceled">Canceladas</option>
          </Select>
        </Toolbar>

        {loading ? (
          <TableSkeleton cols={7} />
        ) : sales.length === 0 ? (
          <Empty
            title="Nenhuma venda"
            subtitle={
              search || status
                ? 'Nenhum resultado para o filtro atual.'
                : 'Use "Lançar venda" para registrar a primeira.'
            }
            mark="LOG"
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>External ID</th>
                  <th>Vendedor</th>
                  <th>Produto</th>
                  <th className="table__num">Pontos</th>
                  <th className="table__num">Valor</th>
                  <th>Status</th>
                  <th>Data</th>
                  <th style={{ textAlign: 'right' }}>Ações</th>
                </tr>
              </thead>
              <tbody>
                {sales.map((s) => (
                  <tr key={s.id}>
                    <td>
                      <span className="sku">{s.external_id}</span>
                    </td>
                    <td className="cell-strong">{s.seller.name}</td>
                    <td>
                      <div className="cell-strong">{s.product.name}</div>
                      <div className="cell-dim">
                        {s.quantity} × {s.product.points_per_unit} pts
                      </div>
                    </td>
                    <td
                      className="table__num cell-strong"
                      style={{ color: s.status === 'approved' ? 'var(--green)' : 'var(--muted)' }}
                    >
                      {s.status === 'approved' ? '+' : ''}
                      {s.points}
                    </td>
                    <td className="table__num">{money(s.total_value)}</td>
                    <td>
                      <Badge tone={s.status === 'approved' ? 'green' : 'muted'}>
                        {s.status === 'approved' ? 'aprovada' : 'cancelada'}
                      </Badge>
                    </td>
                    <td className="cell-dim">{fmtDateTime(s.created_at)}</td>
                    <td>
                      <div className="table__actions">
                        {s.status === 'approved' && (
                          <Button
                            variant="danger"
                            size="sm"
                            onClick={() => cancelByExternalId(s.external_id)}
                          >
                            Cancelar
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <Pagination meta={meta} onPageChange={setPage} onPerPageChange={setPerPage} />
      </Panel>

      <Modal
        open={modal === 'create'}
        onClose={closeModal}
        size="md"
        title="Lançar venda"
        subtitle="O external_id é a chave de idempotência: reenviar não pontua em dobro."
      >
        <form onSubmit={submit}>
          <div className="form-grid">
            <Field label="External ID" hint="único">
              <Input
                value={form.external_id}
                onChange={(e) => setForm({ ...form, external_id: e.target.value })}
                placeholder="VENDA-2026-0001"
                maxLength={LIMITS.saleExternalId}
                required
              />
            </Field>
            <Field label="Campanha" hint="somente as que aceitam vendas">
              <Combobox
                id="campaign"
                value={form.campaign_id}
                onChange={(v) => setForm({ ...form, campaign_id: v })}
                onSelect={setSelectedCampaign}
                loadOptions={(search, page) => api.searchCampaigns(token, search, page)}
                format={(c) =>
                  `${c.name} · ${c.budget.remaining.toLocaleString('pt-BR')} pts livres`
                }
                placeholder="Buscar campanha…"
                required
              />
            </Field>
            <Field label="Vendedor">
              <Combobox
                id="seller"
                value={form.seller_id}
                onChange={(v) => setForm({ ...form, seller_id: v })}
                onSelect={setSelectedSeller}
                loadOptions={(search, page) => api.searchUsers(token, search, 'seller', page)}
                format={(u) => `${u.name} — ${u.email}`}
                placeholder="Buscar vendedor…"
                required
              />
            </Field>
            <Field label="Produto">
              <Combobox
                id="product"
                value={form.product_id}
                onChange={(v) => setForm({ ...form, product_id: v })}
                onSelect={setSelectedProduct}
                loadOptions={(search, page) => api.searchProducts(token, search, page)}
                format={(p) => `${p.name} · ${p.points_per_unit} pts/un`}
                placeholder="Buscar produto…"
                required
              />
            </Field>
            <Field label="Quantidade">
              <Input
                type="number"
                min="1"
                max={LIMITS.quantityMax}
                value={form.quantity}
                onChange={(e) => setForm({ ...form, quantity: e.target.value })}
                required
              />
            </Field>
            <Field label="Valor unitário" hint="R$">
              <Input
                type="number"
                step="0.01"
                min="0"
                max={LIMITS.unitValueMax}
                value={form.unit_value}
                onChange={(e) => setForm({ ...form, unit_value: e.target.value })}
                placeholder="199.90"
                required
              />
            </Field>
          </div>

          {selectedProduct && (
            <div style={{ marginTop: 18 }}>
              {preview.fits ? (
                <Alert tone="success">
                  Crédito previsto: <strong>{preview.points} pts</strong>
                  {selectedCampaign &&
                    ` · restam ${preview.left.toLocaleString('pt-BR')} pts na campanha`}
                </Alert>
              ) : (
                <Alert tone="error">
                  {preview.points} pts excedem a verba disponível (
                  {preview.left.toLocaleString('pt-BR')} pts). A venda será rejeitada por completo.
                </Alert>
              )}
            </div>
          )}

          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Fechar
            </Button>
            <Button type="submit" variant="primary" arrow loading={posting}>
              Lançar venda
            </Button>
          </div>
        </form>
      </Modal>

      <Modal
        open={modal === 'cancel'}
        onClose={closeModal}
        title="Cancelar venda"
        subtitle="Gera um débito no ledger e devolve a verba à campanha."
      >
        <form onSubmit={cancel}>
          <Field label="External ID da venda">
            <Input
              value={cancelId}
              onChange={(e) => setCancelId(e.target.value)}
              placeholder="VENDA-2026-0001"
              maxLength={LIMITS.saleExternalId}
              required
            />
          </Field>
          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Fechar
            </Button>
            <Button type="submit" variant="danger" loading={canceling}>
              Cancelar venda
            </Button>
          </div>
        </form>
      </Modal>

      <Modal
        open={modal === 'import'}
        onClose={closeModal}
        size="md"
        title="Importar lote (CSV)"
        subtitle={
          <>
            Cabeçalho: {CSV_COLUMNS.join(',')} ·{' '}
            <a
              href="http://localhost:8080/sales-sample.csv"
              download="sales-sample.csv"
              className="link"
            >
              Baixar exemplo
            </a>
          </>
        }
      >
        <form onSubmit={runBatch}>
          <Field label="Arquivo CSV" hint=".csv ou .txt">
            <FileInput
              accept=".csv,.txt"
              label="Escolher arquivo"
              fileName={batchFileName}
              onFileSelect={(file) => {
                setBatchFileName(file.name)
                const reader = new FileReader()
                reader.onload = (ev) => setBatchCsv(ev.target?.result ?? '')
                reader.readAsText(file)
              }}
            />
            <div style={{ marginTop: 10 }}>
              <Textarea
                value={batchCsv}
                onChange={(e) => setBatchCsv(e.target.value)}
                placeholder={SAMPLE_CSV}
                rows={6}
              />
            </div>
          </Field>

          {batchResult && (
            <div style={{ marginTop: 18 }}>
              <Alert tone={batchResult.errors ? 'error' : 'success'}>
                {friendlyBatchSummary(batchResult)}
                {batchResult.errors > 0 && (
                  <div style={{ marginTop: 4, color: 'var(--cream-dim)' }}>
                    {groupedErrors(batchResult.results)}
                  </div>
                )}
              </Alert>
            </div>
          )}

          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Fechar
            </Button>
            <Button type="submit" variant="primary" arrow loading={batching}>
              Enviar lote
            </Button>
          </div>
        </form>
      </Modal>

      <Modal
        open={modal === 'export'}
        onClose={closeModal}
        size="md"
        title="Exportar vendas"
        subtitle="Selecione o vendedor para gerar o CSV no mesmo formato da importação."
      >
        <form onSubmit={exportSales}>
          <Field label="Vendedor">
            <Combobox
              id="export-seller"
              value={exportSellerId}
              onChange={setExportSellerId}
              onSelect={setSelectedExportSeller}
              loadOptions={(search, page) => api.searchUsers(token, search, 'seller', page)}
              format={(u) => `${u.name} — ${u.email}`}
              placeholder="Buscar vendedor…"
              required
            />
          </Field>

          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Fechar
            </Button>
            <Button type="submit" variant="primary" arrow loading={exporting}>
              Exportar CSV
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
