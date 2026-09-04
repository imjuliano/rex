import { useState } from 'react'
import {
  Badge,
  Button,
  ConfirmDialog,
  Empty,
  Field,
  Input,
  Modal,
  Pagination,
  Panel,
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
import { useProducts } from '../hooks/useProducts.js'

const BLANK = { name: '', sku: '', points_per_unit: '' }

export function Products({ notify, onError }) {
  const { token } = useAuth()
  const {
    products,
    meta,
    loading,
    load,
    search,
    setSearch,
    status,
    setStatus,
    sort,
    setSort,
    setPage,
    setPerPage,
  } = useProducts({ onError })
  const [saving, setSaving] = useState(false)

  const [modalOpen, setModalOpen] = useState(false)
  const [editingId, setEditingId] = useState(null)
  const [form, setForm] = useState(BLANK)
  const [productToDelete, setProductToDelete] = useState(null)

  const openCreate = () => {
    setForm(BLANK)
    setEditingId(null)
    setModalOpen(true)
  }

  const openEdit = (p) => {
    setForm({ name: p.name, sku: p.sku, points_per_unit: String(p.points_per_unit) })
    setEditingId(p.id)
    setModalOpen(true)
  }

  const closeModal = () => {
    setModalOpen(false)
    setEditingId(null)
    setForm(BLANK)
  }

  const submit = async (e) => {
    e.preventDefault()
    setSaving(true)
    const body = {
      name: form.name.trim(),
      sku: form.sku.trim(),
      points_per_unit: Number(form.points_per_unit),
    }
    try {
      if (editingId) {
        await api.updateProduct(token, editingId, body)
        notify(`Produto "${body.name}" atualizado.`)
      } else {
        await api.createProduct(token, body)
        notify(`Produto "${body.name}" cadastrado.`)
      }
      closeModal()
      load()
    } catch (err) {
      onError({ ...err, message: friendlyError(err) })
    } finally {
      setSaving(false)
    }
  }

  const toggleActive = async (p) => {
    try {
      if (p.active) {
        await api.deactivateProduct(token, p.id)
        notify(`"${p.name}" inativado.`)
      } else {
        await api.updateProduct(token, p.id, { active: true })
        notify(`"${p.name}" reativado.`)
      }
      load()
    } catch (e) {
      onError({ ...e, message: friendlyError(e) })
    }
  }

  const confirmRemove = (p) => setProductToDelete(p)

  const doRemove = async () => {
    if (!productToDelete) return
    try {
      await api.softDeleteProduct(token, productToDelete.id)
      notify(`"${productToDelete.name}" excluído.`)
      setProductToDelete(null)
      load()
    } catch (e) {
      onError({ ...e, message: friendlyError(e) })
    }
  }

  const summary = meta.summary ?? {}

  return (
    <div className="stack">
      <div className="stats">
        <Stat label="Catálogo" value={summary.total_products ?? 0} unit="skus" tone="magenta" />
        <Stat label="Ativos" value={summary.active_products ?? 0} unit="skus" tone="green" />
        <Stat
          label="Média de pontos"
          value={summary.avg_points_per_unit_active ?? 0}
          unit="pts/un"
          foot="Considera apenas ativos"
        />
      </div>

      <Panel
        title="Produtos"
        subtitle={`${meta.total ?? 0} registro(s) no catálogo`}
        flush
        actions={
          <Button variant="primary" size="sm" onClick={openCreate}>
            <IconPlus />
            Novo produto
          </Button>
        }
      >
        <Toolbar>
          <SearchInput value={search} onChange={setSearch} placeholder="Nome ou SKU…" />
          <Select value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="">Todos os status</option>
            <option value="active">Ativos</option>
            <option value="inactive">Inativos</option>
          </Select>
          <Select value={sort} onChange={(e) => setSort(e.target.value)}>
            <option value="created_at">Mais recentes</option>
            <option value="name">Nome</option>
            <option value="sku">SKU</option>
            <option value="points_per_unit">Pontos</option>
          </Select>
        </Toolbar>

        {loading ? (
          <TableSkeleton cols={5} />
        ) : products.length === 0 ? (
          <Empty
            title="Nenhum produto"
            subtitle={
              search || status
                ? 'Nenhum resultado para o filtro atual.'
                : 'Cadastre o primeiro SKU do catálogo.'
            }
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Produto</th>
                  <th>SKU</th>
                  <th className="table__num">Pontos / un</th>
                  <th>Status</th>
                  <th>Criado em</th>
                  <th style={{ textAlign: 'right' }}>Ações</th>
                </tr>
              </thead>
              <tbody>
                {products.map((p) => (
                  <tr key={p.id}>
                    <td>
                      <div className="cell-strong">{p.name}</div>
                      <div className="cell-dim">#{p.id}</div>
                    </td>
                    <td>
                      <span className="sku">{p.sku}</span>
                    </td>
                    <td className="table__num cell-strong">{p.points_per_unit}</td>
                    <td>
                      <Badge tone={p.active ? 'green' : 'muted'}>
                        {p.active ? 'ativo' : 'inativo'}
                      </Badge>
                    </td>
                    <td className="cell-dim">
                      {new Date(p.created_at).toLocaleDateString('pt-BR')}
                    </td>
                    <td>
                      <div className="table__actions">
                        <Button variant="ghost" size="sm" onClick={() => openEdit(p)}>
                          Editar
                        </Button>
                        <Button
                          variant={p.active ? 'danger' : 'outline'}
                          size="sm"
                          onClick={() => toggleActive(p)}
                        >
                          {p.active ? 'Inativar' : 'Reativar'}
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => confirmRemove(p)}
                          title="Excluir permanentemente"
                        >
                          Excluir
                        </Button>
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
        open={modalOpen}
        onClose={closeModal}
        title={editingId ? `Editar produto #${editingId}` : 'Novo produto'}
        subtitle={
          editingId
            ? 'Alterações valem para vendas futuras; o ledger histórico não muda.'
            : 'Pontos por unidade definem o crédito na venda.'
        }
      >
        <form onSubmit={submit}>
          <div className="form-grid">
            <Field label="Nome">
              <Input
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                placeholder="Smartphone X"
                maxLength={LIMITS.productName}
                required
              />
            </Field>
            <Field label="SKU" hint="único">
              <Input
                value={form.sku}
                onChange={(e) => setForm({ ...form, sku: e.target.value })}
                placeholder="PHONE-001"
                maxLength={LIMITS.productSku}
                required
              />
            </Field>
            <Field label="Pontos por unidade">
              <Input
                type="number"
                min="0"
                value={form.points_per_unit}
                onChange={(e) => setForm({ ...form, points_per_unit: e.target.value })}
                placeholder="50"
                required
              />
            </Field>
          </div>
          <div className="form-actions">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Cancelar
            </Button>
            <Button type="submit" variant="primary" loading={saving}>
              {editingId ? 'Salvar alterações' : 'Cadastrar produto'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={productToDelete !== null}
        title="Excluir produto"
        message={`Excluir "${productToDelete?.name}" permanentemente? Essa ação não pode ser desfeita.`}
        onConfirm={doRemove}
        onCancel={() => setProductToDelete(null)}
        confirmText="Excluir"
        cancelText="Cancelar"
        tone="danger"
      />
    </div>
  )
}
