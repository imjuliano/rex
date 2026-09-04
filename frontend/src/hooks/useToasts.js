import { useCallback, useRef, useState } from 'react'

export function useToasts(ttl = 4200) {
  const [items, setItems] = useState([])
  const seq = useRef(0)

  const dismiss = useCallback((id) => {
    setItems((prev) => prev.filter((t) => t.id !== id))
  }, [])

  const push = useCallback(
    (message, tone = 'success') => {
      const id = ++seq.current
      setItems((prev) => [...prev, { id, message, tone }])
      setTimeout(() => dismiss(id), ttl)
    },
    [dismiss, ttl],
  )

  return { items, push, dismiss }
}
