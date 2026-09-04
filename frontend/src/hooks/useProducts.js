import { useCallback, useEffect, useState } from 'react'
import { api } from '@/lib/api.js'
import { useAuth } from '@/context/AuthContext.jsx'
import { useDebounced } from '@/hooks/useDebounced.js'

export function useProducts({ onError }) {
  const { token } = useAuth()

  const [products, setProducts] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [sort, setSort] = useState('created_at')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const debouncedSearch = useDebounced(search)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data, meta: responseMeta } = await api.listProducts(token, {
        search: debouncedSearch,
        status,
        sort,
        order: sort === 'name' || sort === 'sku' ? 'asc' : 'desc',
        page,
        per_page: perPage,
      })
      setProducts(data)
      setMeta(responseMeta)
    } catch (e) {
      onError(e)
    } finally {
      setLoading(false)
    }
  }, [token, debouncedSearch, status, sort, page, perPage, onError])

  useEffect(() => {
    load()
  }, [load])

  // Any filter change invalidates the current page number.
  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, sort, perPage])

  return {
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
    page,
    setPage,
    perPage,
    setPerPage,
  }
}
