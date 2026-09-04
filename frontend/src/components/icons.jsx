const base = {
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.9,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
}

export const IconArrowRight = (p) => (
  <svg {...base} width="15" height="15" {...p}>
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
)

export const IconBox = (p) => (
  <svg {...base} {...p}>
    <path d="M21 8v8a2 2 0 0 1-1 1.73l-7 4a2 2 0 0 1-2 0l-7-4A2 2 0 0 1 3 16V8a2 2 0 0 1 1-1.73l7-4a2 2 0 0 1 2 0l7 4A2 2 0 0 1 21 8Z" />
    <path d="m3.3 7 8.7 5 8.7-5M12 22V12" />
  </svg>
)

export const IconTarget = (p) => (
  <svg {...base} {...p}>
    <circle cx="12" cy="12" r="9" />
    <circle cx="12" cy="12" r="5" />
    <circle cx="12" cy="12" r="1.4" />
  </svg>
)

export const IconReceipt = (p) => (
  <svg {...base} {...p}>
    <path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2Z" />
    <path d="M9 8h6M9 12h6" />
  </svg>
)

export const IconWallet = (p) => (
  <svg {...base} {...p}>
    <path d="M20 12V8a2 2 0 0 0-2-2H5a2 2 0 0 1 0-4h12v4" />
    <path d="M3 6v12a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-4h-5a2 2 0 0 1 0-4h5" />
  </svg>
)

export const IconAudit = (p) => (
  <svg {...base} {...p}>
    <circle cx="11" cy="11" r="8" />
    <path d="m21 21-4.3-4.3" />
  </svg>
)

export const IconPeople = (p) => (
  <svg {...base} {...p}>
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
    <circle cx="9" cy="7" r="4" />
    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
  </svg>
)

export const IconLogout = (p) => (
  <svg {...base} {...p}>
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
    <path d="m16 17 5-5-5-5M21 12H9" />
  </svg>
)

export const IconAlert = (p) => (
  <svg {...base} width="16" height="16" {...p}>
    <circle cx="12" cy="12" r="9" />
    <path d="M12 8v4M12 16h.01" />
  </svg>
)

export const IconCheck = (p) => (
  <svg {...base} width="16" height="16" {...p}>
    <circle cx="12" cy="12" r="9" />
    <path d="m8.5 12.5 2.5 2.5 4.5-5" />
  </svg>
)

export const IconRefresh = (p) => (
  <svg {...base} width="14" height="14" {...p}>
    <path d="M21 12a9 9 0 1 1-3-6.7L21 8" />
    <path d="M21 3v5h-5" />
  </svg>
)

export const IconPlus = (p) => (
  <svg {...base} width="14" height="14" {...p}>
    <path d="M12 5v14M5 12h14" />
  </svg>
)

export const IconBull = (p) => (
  <svg viewBox="0 0 32 26" fill="currentColor" width="26" height="21" {...p}>
    <path d="M3 1c2.6 0 4 1.5 4.6 3.4h9.2C18.6 2 21.4 0 25 0c1.3 0 2.6.3 3.8.9l-1 2c-.9-.4-1.8-.6-2.8-.6-2.6 0-4.6 1.4-5.7 3.4h4.4l1.2 3.7h3.6l-1.3 3.3h-2.8l1 8.3h-3.5l-.8-6.6-2 1.4V26h-3.4v-7l-4.6-1.5V26H8v-9.7C5.6 14.7 4 12 4 8.8V4.7C4 3.9 3.6 3.4 3 3.4H0V1h3Z" />
  </svg>
)
