import { useCallback, useEffect, useState } from 'react'
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
import { IconPlus, IconRefresh } from '../components/icons.jsx'
import { api } from '../lib/api.js'
import { friendlyError } from '../lib/errors.js'
import { LIMITS } from '../lib/limits.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useDebounced } from '../hooks/useDebounced.js'

const BLANK = { name: '', email: '', role: 'seller', password: '' }
const ROLES = [
  { value: 'admin', label: 'Admin' },
  { value: 'seller', label: 'Vendedor' },
]

function generatePassword() {
  const letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  const numbers = '0123456789'
  const symbols = '!@#$%&*'
  const all = letters + numbers + symbols
  const length = 12
  let out = ''
  out += letters[Math.floor(Math.random() * letters.length)]
  out += numbers[Math.floor(Math.random() * numbers.length)]
  out += symbols[Math.floor(Math.random() * symbols.length)]
  for (let i = out.length; i < length; i++) {
    out += all[Math.floor(Math.random() * all.length)]
  }
  return out
    .split('')
    .sort(() => Math.random() - 0.5)
    .join('')
}

export function Users({ notify, onError }) {
  const { token, user } = useAuth()
  const [users, setUsers] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  const [modalOpen, setModalOpen] = useState(false)
  const [editingId, setEditingId] = useState(null)
  const [form, setForm] = useState(BLANK)
  const [userToDelete, setUserToDelete] = useState(null)
  const [showPassword, setShowPassword] = useState(false)

  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const debouncedSearch = useDebounced(search)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data, meta } = await api.listUsers(token, {
        search: debouncedSearch,
        role,
        page,
        per_page: perPage,
      })
      setUsers(data)
      setMeta(meta)
    } catch (e) {
      onError(e)
    } finally {
      setLoading(false)
    }
  }, [token, debouncedSearch, role, page, perPage, onError])

  useEffect(() => {
    load()
  }, [load])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, role, perPage])

  const openCreate = () => {
    setForm({ ...BLANK, password: generatePassword() })
    setEditingId(null)
    setShowPassword(false)
    setModalOpen(true)
  }

  const openEdit = (u) => {
    setForm({ name: u.name, email: u.email, role: u.role, password: '' })
    setEditingId(u.id)
    setShowPassword(false)
    setModalOpen(true)
  }

  const closeModal = () => {
    setModalOpen(false)
    setEditingId(null)
    setForm(BLANK)
    setShowPassword(false)
  }

  const generateAndShow = () => {
    setForm({ ...form, password: generatePassword() })
    setShowPassword(true)
  }

  const submit = async (e) => {
    e.preventDefault()
    setSaving(true)

    const body = {
      name: form.name.trim(),
      email: form.email.trim().toLowerCase(),
      role: form.role,
    }

    if (form.password.trim().length > 0) {
      body.password = form.password
    }

    try {
      if (editingId) {
        await api.updateUser(token, editingId, body)
        notify('Usuário atualizado com sucesso.', 'success')
      } else {
        await api.createUser(token, { ...body, password: form.password })
        notify('Usuário criado com sucesso.', 'success')
      }
      closeModal()
      load()
    } catch (e) {
      onError(e)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (u) => {
    try {
      await api.softDeleteUser(token, u.id)
      notify('Usuário excluído.', 'success')
      load()
    } catch (e) {
      onError(e)
    } finally {
      setUserToDelete(null)
    }
  }

  const canEdit = (u) => u.id !== user.id

  return (
    <div className="stack">
      <Panel
        title="Usuários"
        subtitle="Crie vendedores e administradores"
        actions={
          <Button onClick={openCreate}>
            <IconPlus width="14" height="14" /> Novo usuário
          </Button>
        }
      >
        <Toolbar>
          <SearchInput
            value={search}
            onChange={setSearch}
            placeholder="Buscar por nome ou e-mail…"
          />
          <Select value={role} onChange={(e) => setRole(e.target.value)}>
            <option value="">Todos os papéis</option>
            {ROLES.map((r) => (
              <option key={r.value} value={r.value}>
                {r.label}
              </option>
            ))}
          </Select>
        </Toolbar>

        <div className="stats" style={{ margin: '18px 22px 0' }}>
          <Stat label="Total" value={meta.total ?? 0} />
          <Stat label="Vendedores" value={meta.summary?.sellers ?? 0} />
          <Stat label="Admins" value={meta.summary?.admins ?? 0} />
        </div>

        {loading ? (
          <TableSkeleton rows={perPage} cols={5} />
        ) : users.length === 0 ? (
          <Empty message="Nenhum usuário encontrado." />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>E-mail</th>
                  <th>Papel</th>
                  <th>Criado em</th>
                  <th style={{ width: 130 }}></th>
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id}>
                    <td>{u.name}</td>
                    <td className="mono-num">{u.email}</td>
                    <td>
                      <Badge tone={u.role === 'admin' ? 'amber' : 'muted'}>
                        {u.role === 'admin' ? 'Admin' : 'Vendedor'}
                      </Badge>
                    </td>
                    <td className="mono-num">
                      {new Date(u.created_at).toLocaleDateString('pt-BR')}
                    </td>
                    <td>
                      <Button variant="ghost" size="sm" onClick={() => openEdit(u)}>
                        Editar
                      </Button>
                      {canEdit(u) && (
                        <Button variant="danger" size="sm" onClick={() => setUserToDelete(u)}>
                          Excluir
                        </Button>
                      )}
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
        title={editingId ? 'Editar usuário' : 'Novo usuário'}
        size="sm"
      >
        <form onSubmit={submit}>
          <div className="modal__body stack" style={{ '--gap': '16px' }}>
            <Field label="Nome" required>
              <Input
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                maxLength={LIMITS.userName}
                required
              />
            </Field>

            <Field label="E-mail" required>
              <Input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                maxLength={LIMITS.userEmail}
                required
              />
            </Field>

            <Field label="Papel" required>
              <Select
                value={form.role}
                onChange={(e) => setForm({ ...form, role: e.target.value })}
                required
              >
                {ROLES.map((r) => (
                  <option key={r.value} value={r.value}>
                    {r.label}
                  </option>
                ))}
              </Select>
            </Field>

            <Field label={editingId ? 'Nova senha (deixe em branco para manter)' : 'Senha'}>
              <div style={{ display: 'flex', gap: 8 }}>
                <Input
                  type={showPassword ? 'text' : 'password'}
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  placeholder={editingId ? '••••••••' : ''}
                  minLength={8}
                />
                <Button
                  type="button"
                  variant="outline"
                  onClick={generateAndShow}
                  title="Gerar senha"
                >
                  <IconRefresh width="14" height="14" />
                </Button>
              </div>
            </Field>
          </div>

          <div className="modal__footer">
            <Button type="button" variant="ghost" onClick={closeModal}>
              Cancelar
            </Button>
            <Button type="submit" loading={saving}>
              {editingId ? 'Salvar' : 'Criar'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={!!userToDelete}
        onClose={() => setUserToDelete(null)}
        onConfirm={() => remove(userToDelete)}
        title="Excluir usuário"
      >
        Deseja excluir <strong>{userToDelete?.name}</strong> ({userToDelete?.email})?
      </ConfirmDialog>
    </div>
  )
}
