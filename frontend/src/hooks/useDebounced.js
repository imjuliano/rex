import { useEffect, useState } from 'react'

/**
 * Delays propagation of a fast-changing value so typing in a search box
 * does not fire one request per keystroke.
 */
export function useDebounced(value, delay = 350) {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])

  return debounced
}
