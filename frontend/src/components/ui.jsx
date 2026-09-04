import { useEffect, useRef, useState, useCallback } from 'react'
import { createPortal } from 'react-dom'
import { useDebounced } from '../hooks/useDebounced.js'
import { IconAlert, IconArrowRight, IconCheck } from './icons.jsx'

export function Button({
  variant = 'default',
  size,
  block,
  arrow,
  loading,
  children,
  className = '',
  ...rest
}) {
  const classes = [
    'btn',
    variant !== 'default' && `btn--${variant}`,
    size === 'sm' && 'btn--sm',
    block && 'btn--block',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <button className={classes} disabled={loading || rest.disabled} {...rest}>
      {loading && <span className="spinner" />}
      {children}
      {arrow && !loading && <IconArrowRight className="btn__arrow" />}
    </button>
  )
}

export function Field({ label, hint, children }) {
  return (
    <div className="field">
      <span className="field__label">
        {label}
        {hint && <span style={{ color: 'var(--muted)', letterSpacing: 0 }}> · {hint}</span>}
      </span>
      {children}
    </div>
  )
}

export function Input(props) {
  return <input className="input" {...props} />
}

export function Textarea(props) {
  return <textarea className="input" style={{ minHeight: 120, resize: 'vertical' }} {...props} />
}

export function FileInput({ onFileSelect, accept, label, fileName = '' }) {
  const ref = useRef(null)
  return (
    <div className="file-input">
      <input
        ref={ref}
        type="file"
        accept={accept}
        onChange={(e) => {
          const file = e.target.files?.[0]
          if (file) onFileSelect?.(file)
        }}
        className="file-input__native"
      />
      <Button type="button" variant="outline" size="sm" onClick={() => ref.current?.click()}>
        {label}
      </Button>
      <span className="file-input__name">{fileName || 'Nenhum arquivo selecionado'}</span>
    </div>
  )
}

export function Select({ children, ...rest }) {
  return (
    <select className="select" {...rest}>
      {children}
    </select>
  )
}

export function Panel({ title, subtitle, actions, flush, children }) {
  return (
    <section className="panel">
      {(title || actions) && (
        <header className="panel__head">
          <div>
            {title && <h3 className="panel__head-title">{title}</h3>}
            {subtitle && <p className="panel__head-sub">{subtitle}</p>}
          </div>
          {actions}
        </header>
      )}
      <div className={flush ? 'panel__body panel__body--flush' : 'panel__body'}>{children}</div>
    </section>
  )
}

export function Stat({ label, value, unit, foot, tone }) {
  return (
    <div className={`stat${tone ? ` stat--${tone}` : ''}`}>
      <div className="stat__label">{label}</div>
      <div className="stat__value mono-num">
        {value}
        {unit && <span className="stat__unit">{unit}</span>}
      </div>
      {foot && <div className="stat__foot">{foot}</div>}
    </div>
  )
}

export function Badge({ tone = 'muted', children }) {
  return <span className={`badge badge--${tone}`}>{children}</span>
}

export function Progress({ used, total }) {
  const pct = total > 0 ? Math.min(100, (used / total) * 100) : 0
  const tone = pct >= 100 ? 'red' : pct >= 75 ? 'amber' : null
  return (
    <div className="progress">
      <div className="progress__track">
        <div
          className={`progress__fill${tone ? ` progress__fill--${tone}` : ''}`}
          style={{ width: `${pct}%` }}
        />
      </div>
      <div className="progress__legend">
        <span>
          {used.toLocaleString('pt-BR')} / {total.toLocaleString('pt-BR')}
        </span>
        <span>{pct.toFixed(0)}%</span>
      </div>
    </div>
  )
}

export function Alert({ tone = 'error', children }) {
  const Icon = tone === 'error' ? IconAlert : IconCheck
  return (
    <div className={`alert alert--${tone}`}>
      <Icon className="alert__icon" />
      <div>{children}</div>
    </div>
  )
}

export function Empty({ title, subtitle, mark = 'REX' }) {
  return (
    <div className="empty">
      <div className="empty__mark">{mark}</div>
      <div className="empty__title">{title}</div>
      {subtitle && <div className="empty__sub">{subtitle}</div>}
    </div>
  )
}

export function TableSkeleton({ rows = 4, cols = 4 }) {
  return (
    <div style={{ padding: 22, display: 'flex', flexDirection: 'column', gap: 14 }}>
      {Array.from({ length: rows }).map((_, r) => (
        <div key={r} style={{ display: 'flex', gap: 14 }}>
          {Array.from({ length: cols }).map((_, c) => (
            <div key={c} className="skeleton" style={{ flex: c === 0 ? 2 : 1 }} />
          ))}
        </div>
      ))}
    </div>
  )
}

/**
 * Builds a page window of at most `size` numbers centred on the current page,
 * so a 40-page list does not render 40 buttons.
 */
function pageWindow(page, totalPages, size = 5) {
  const half = Math.floor(size / 2)
  let start = Math.max(1, page - half)
  const end = Math.min(totalPages, start + size - 1)
  start = Math.max(1, end - size + 1)
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
}

export function Pagination({ meta, onPageChange, onPerPageChange, perPageOptions = [10, 20, 50] }) {
  const total = meta?.total ?? 0
  if (!total) return null

  const page = meta.page ?? 1
  const perPage = meta.per_page ?? 10
  const totalPages = meta.total_pages ?? 1
  const from = (page - 1) * perPage + 1
  const to = Math.min(page * perPage, total)
  const pages = pageWindow(page, totalPages)

  return (
    <div className="pagination">
      <div className="pagination__left">
        <span className="pagination__info">
          {from}–{to} de {total}
        </span>
        {onPerPageChange && (
          <label className="pagination__size">
            <span>por página</span>
            <select
              className="select"
              value={perPage}
              onChange={(e) => onPerPageChange(Number(e.target.value))}
            >
              {perPageOptions.map((n) => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </select>
          </label>
        )}
      </div>

      {totalPages > 1 && (
        <div className="pagination__controls">
          <Button
            variant="ghost"
            size="sm"
            disabled={page <= 1}
            onClick={() => onPageChange(1)}
            title="Primeira página"
          >
            ««
          </Button>
          <Button
            variant="ghost"
            size="sm"
            disabled={page <= 1}
            onClick={() => onPageChange(page - 1)}
          >
            Anterior
          </Button>

          {pages[0] > 1 && <span className="pagination__gap">…</span>}
          {pages.map((n) => (
            <button
              key={n}
              type="button"
              className={`pagination__num${n === page ? ' pagination__num--active' : ''}`}
              onClick={() => onPageChange(n)}
              aria-current={n === page ? 'page' : undefined}
            >
              {n}
            </button>
          ))}
          {pages[pages.length - 1] < totalPages && <span className="pagination__gap">…</span>}

          <Button
            variant="ghost"
            size="sm"
            disabled={page >= totalPages}
            onClick={() => onPageChange(page + 1)}
          >
            Próxima
          </Button>
          <Button
            variant="ghost"
            size="sm"
            disabled={page >= totalPages}
            onClick={() => onPageChange(totalPages)}
            title="Última página"
          >
            »»
          </Button>
        </div>
      )}
    </div>
  )
}

/**
 * Accessible dialog rendered in a portal so no parent's overflow or
 * transform can clip it.
 */
export function Modal({ open, onClose, title, subtitle, size = 'sm', children }) {
  const panelRef = useRef(null)
  const restoreFocusTo = useRef(null)
  const onCloseRef = useRef(onClose)
  onCloseRef.current = onClose

  useEffect(() => {
    if (!open) return

    restoreFocusTo.current = document.activeElement
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    const onKeyDown = (e) => {
      if (e.key === 'Escape') onCloseRef.current()
    }
    document.addEventListener('keydown', onKeyDown)

    const focusTimer = setTimeout(() => {
      const focusable = panelRef.current?.querySelector(
        'input:not([type=hidden]), select, textarea, button:not(.modal__close)',
      )
      focusable?.focus()
    }, 40)

    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.body.style.overflow = previousOverflow
      clearTimeout(focusTimer)
      // Returning focus to the trigger keeps keyboard navigation coherent.
      if (restoreFocusTo.current instanceof HTMLElement) restoreFocusTo.current.focus()
    }
  }, [open])

  if (!open) return null

  return createPortal(
    <div
      className="modal"
      onMouseDown={(e) => {
        if (e.target === e.currentTarget) onClose()
      }}
    >
      <div
        ref={panelRef}
        className={`modal__panel modal__panel--${size}`}
        role="dialog"
        aria-modal="true"
        aria-label={title}
      >
        <header className="modal__head">
          <div>
            <h3 className="modal__title">{title}</h3>
            {subtitle && <p className="modal__sub">{subtitle}</p>}
          </div>
          <button type="button" className="modal__close" onClick={onClose} aria-label="Fechar">
            ×
          </button>
        </header>
        <div className="modal__body">{children}</div>
      </div>
    </div>,
    document.body,
  )
}

export function Toolbar({ children }) {
  return <div className="toolbar">{children}</div>
}

export function SearchInput({ value, onChange, placeholder = 'Buscar…' }) {
  return (
    <input
      className="input toolbar__search"
      type="search"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
    />
  )
}

export function Combobox({
  id,
  value,
  onChange,
  onSelect,
  loadOptions,
  placeholder,
  required,
  disabled,
  format = (x) => x.name,
}) {
  const [items, setItems] = useState([])
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState(null)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(false)
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(false)
  const [highlighted, setHighlighted] = useState(-1)
  const requestId = useRef(0)
  const wrapperRef = useRef(null)
  const sentinelRef = useRef(null)
  const inputRef = useRef(null)
  const menuId = `${id || 'cb'}-menu`
  const debouncedSearch = useDebounced(search, 220)

  const allItems =
    selected && !items.find((i) => String(i.id) === String(selected.id))
      ? [selected, ...items]
      : items

  const load = useCallback(
    async (q, p, append = false) => {
      if (!open) return
      const thisId = ++requestId.current
      setLoading(true)
      try {
        const { data, meta } = await loadOptions(q.trim(), p)
        if (requestId.current !== thisId) return
        const mapped = data.map((d) => ({ ...d, label: format(d) }))
        setItems((prev) => (append ? [...prev, ...mapped] : mapped))
        setHasMore(meta.total_pages > p)
      } finally {
        setLoading(false)
      }
    },
    [loadOptions, format, open],
  )

  useEffect(() => {
    if (open) {
      setPage(1)
      load(debouncedSearch, 1)
      setHighlighted(-1)
    } else {
      setItems([])
      setHighlighted(-1)
    }
  }, [open, debouncedSearch, load])

  useEffect(() => {
    if (page > 1) load(debouncedSearch, page, true)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page])

  // Infinite scroll.
  useEffect(() => {
    if (!open || !sentinelRef.current || !hasMore || loading) return
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          setPage((p) => p + 1)
        }
      },
      { root: null, rootMargin: '0px', threshold: 0.1 },
    )
    observer.observe(sentinelRef.current)
    return () => observer.disconnect()
  }, [open, hasMore, loading])

  // Close when clicking outside the whole combobox.
  useEffect(() => {
    if (!open) return
    const onDocClick = (e) => {
      if (!wrapperRef.current?.contains(e.target)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', onDocClick)
    return () => document.removeEventListener('mousedown', onDocClick)
  }, [open])

  // Sync with external value.
  useEffect(() => {
    if (selected && String(selected.id) === String(value)) return
    if (!value) {
      setSelected(null)
      setSearch('')
      return
    }
    const known = allItems.find((i) => String(i.id) === String(value))
    if (known) {
      setSelected(known)
      setSearch(known.label)
      return
    }
    setSelected({ id: value, label: String(value) })
    setSearch(String(value))
  }, [value])

  const select = (item) => {
    if (!item) return
    setSelected(item)
    setSearch(item.label)
    setOpen(false)
    onChange?.(String(item.id))
    onSelect?.(item)
    inputRef.current?.focus()
  }

  const handleKeyDown = (e) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setOpen(true)
      setHighlighted((h) => Math.min(h + 1, allItems.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setOpen(true)
      setHighlighted((h) => Math.max(h - 1, 0))
    } else if (e.key === 'Enter') {
      e.preventDefault()
      if (highlighted >= 0 && allItems[highlighted]) {
        select(allItems[highlighted])
      }
    } else if (e.key === 'Escape') {
      setOpen(false)
      inputRef.current?.focus()
    }
  }

  return (
    <div className="combobox" ref={wrapperRef}>
      <input
        ref={inputRef}
        id={id}
        className="input combobox__input"
        type="text"
        autoComplete="off"
        aria-autocomplete="list"
        aria-expanded={open}
        aria-controls={menuId}
        aria-activedescendant={
          open && highlighted >= 0 ? `${id || 'cb'}-opt-${highlighted}` : undefined
        }
        value={search}
        onChange={(e) => {
          setSearch(e.target.value)
          setOpen(true)
          setPage(1)
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={handleKeyDown}
        onClick={() => setOpen(true)}
        placeholder={placeholder}
        required={required}
        disabled={disabled}
      />
      <div
        id={menuId}
        className={`combobox__menu${open ? ' combobox__menu--open' : ''}`}
        role="listbox"
      >
        {open && (
          <>
            {allItems.length === 0 && !loading && (
              <div className="combobox__empty">Nenhum resultado</div>
            )}
            {allItems.map((item, idx) => (
              <div
                key={item.id}
                id={`${id || 'cb'}-opt-${idx}`}
                role="option"
                aria-selected={selected && String(selected.id) === String(item.id)}
                className={`combobox__item${
                  highlighted === idx ? ' combobox__item--active' : ''
                }${selected && String(selected.id) === String(item.id) ? ' combobox__item--selected' : ''}`}
                onClick={() => select(item)}
              >
                {item.label}
              </div>
            ))}
            {(loading || hasMore) && (
              <div ref={sentinelRef} className="combobox__spinner">
                {loading && <span className="spinner" />}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  )
}

export function ConfirmDialog({
  open,
  title = 'Confirmar',
  message,
  onConfirm,
  onCancel,
  confirmText = 'Confirmar',
  cancelText = 'Cancelar',
  tone = 'danger',
}) {
  return (
    <Modal open={open} onClose={onCancel} size="sm" title={title}>
      <div className="confirm-dialog__body">
        <p>{message}</p>
      </div>
      <div className="form-actions" style={{ marginTop: 0 }}>
        <Button type="button" variant="ghost" onClick={onCancel}>
          {cancelText}
        </Button>
        <Button
          type="button"
          variant={tone === 'danger' ? 'danger' : 'primary'}
          onClick={onConfirm}
        >
          {confirmText}
        </Button>
      </div>
    </Modal>
  )
}

export function Toasts({ items, onDismiss }) {
  if (!items.length) return null
  return (
    <div className="toasts">
      {items.map((t) => (
        <div
          key={t.id}
          className={`toast${t.tone === 'error' ? ' toast--error' : ''}`}
          onClick={() => onDismiss(t.id)}
          role="status"
        >
          {t.tone === 'error' ? (
            <IconAlert style={{ color: 'var(--red)', flex: 'none', marginTop: 2 }} />
          ) : (
            <IconCheck style={{ color: 'var(--green)', flex: 'none', marginTop: 2 }} />
          )}
          <div>
            <div className="toast__title">{t.tone === 'error' ? 'Falhou' : 'Feito'}</div>
            <div className="toast__msg">{t.message}</div>
          </div>
        </div>
      ))}
    </div>
  )
}
