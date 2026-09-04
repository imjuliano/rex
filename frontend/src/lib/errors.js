/**
 * Error code → human-readable, Portuguese copy.
 *
 * The backend contract stays in English (ErrorCode is machine-readable);
 * this is the UI boundary translation layer. It branches on `error` and
 * interpolates `details` so the user sees only what matters.
 */

const DEFAULT = 'Não foi possível completar a ação. Tente novamente.'

const MESSAGES = {
  INVALID_JSON_BODY: 'O corpo da requisição não é um JSON válido.',
  MISSING_FIELD: ({ fields = [] }) =>
    fields.length === 1
      ? `O campo "${fields[0]}" é obrigatório.`
      : `Campos obrigatórios: ${fields.map((f) => `"${f}"`).join(', ')}.`,
  INVALID_FIELD: ({ field, expected }) => {
    if (field === 'ends_at' && expected?.includes('after')) {
      return 'A data de fim deve ser posterior à data de início.'
    }
    if (field === 'starts_at' && expected?.includes('valid date')) {
      return 'A data de início não é válida.'
    }
    if (field === 'ends_at' && expected?.includes('valid date')) {
      return 'A data de fim não é válida.'
    }
    return expected ? `O campo "${field}" deve ser ${expected}.` : `O campo "${field}" é inválido.`
  },
  VALUE_TOO_LONG: ({ field, max_length }) =>
    `O campo "${field}" pode ter no máximo ${max_length} caracteres.`,
  VALUE_OUT_OF_RANGE: ({ field, min, max }) =>
    `O campo "${field}" deve estar entre ${formatNumber(min)} e ${formatNumber(max)}.`,
  DATE_IN_THE_PAST: ({ field }) =>
    field === 'ends_at'
      ? 'A data de fim deve estar no futuro.'
      : 'A data informada não pode estar no passado.',
  NO_FIELDS_TO_UPDATE: 'Nenhum campo foi enviado para atualização.',
  MISSING_TOKEN: 'Sessão expirada. Faça login novamente.',
  INVALID_TOKEN: 'Sessão inválida. Faça login novamente.',
  MISSING_REFRESH_TOKEN: 'Sessão encerrada. Faça login novamente.',
  INVALID_REFRESH_TOKEN: 'Sua sessão expirou. Faça login novamente.',
  REFRESH_TOKEN_REUSED:
    'Sua sessão foi encerrada por segurança: o token de renovação foi reutilizado. Faça login novamente.',
  INVALID_CREDENTIALS: 'E-mail ou senha incorretos.',
  FORBIDDEN_ROLE: 'Você não tem permissão para essa ação.',
  ROUTE_NOT_FOUND: 'Recurso não encontrado.',
  SALE_NOT_FOUND: 'Venda não encontrada.',
  PRODUCT_NOT_FOUND: 'Produto não encontrado.',
  CAMPAIGN_NOT_FOUND: 'Campanha não encontrada.',
  SELLER_NOT_FOUND: 'Vendedor não encontrado.',
  DUPLICATE_SKU: 'Já existe um produto com esse SKU.',
  DUPLICATE_ENTRY: 'Registro duplicado.',
  SALE_ALREADY_EXISTS: 'Essa venda já foi lançada.',
  SALE_ALREADY_CANCELED: 'Essa venda já foi cancelada.',
  CAMPAIGN_ALREADY_CLOSED: 'Essa campanha já foi encerrada.',
  CONCURRENT_UPDATE: 'Conflito de atualização. Tente novamente.',
  INSUFFICIENT_BUDGET: ({ requested_points, available_points }) =>
    `A venda precisa de ${requested_points} pts, mas a campanha tem ${available_points} pts disponíveis.`,
  CAMPAIGN_NOT_ACTIVE: 'A campanha está encerrada e não pode receber vendas.',
  CAMPAIGN_OUT_OF_PERIOD: 'A venda está fora do período da campanha.',
  PRODUCT_INACTIVE: 'O produto está inativo e não pode ser vendido.',
  NEGATIVE_BUDGET: 'Cancelar essa venda deixaria a verba negativa.',
  BUDGET_BELOW_COMMITTED: ({ minimum_allowed }) =>
    `A verba não pode ser menor que ${minimum_allowed} pts já creditados.`,
  LEDGER_INCONSISTENT: 'Inconsistência no histórico de pontos.',
  DATABASE_UNAVAILABLE: 'Banco de dados indisponível. Tente novamente em instantes.',
  DATABASE_ERROR: 'Falha ao salvar. Tente novamente.',
  INTERNAL_ERROR: 'Erro interno. Tente novamente.',
  NETWORK_ERROR: 'Não foi possível falar com a API. O backend está no ar?',
}

function formatNumber(value) {
  const num = Number(value)
  if (Number.isNaN(num)) return value
  if (num >= 1_000_000_000) return num.toLocaleString('pt-BR')
  return Number.isInteger(num)
    ? String(num)
    : num.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
}

export function friendlyError(err) {
  const code = err?.code || err?.error || 'UNKNOWN'
  const details = err?.details || {}
  const builder = MESSAGES[code]
  if (builder) {
    return typeof builder === 'function' ? builder(details) : builder
  }
  return err?.message && err.message !== err?.code ? err.message : DEFAULT
}

/**
 * Short label for batch CSV error grouping. Keys must stay in sync with ErrorCode.
 */
export const FRIENDLY_ERROR_CODE = {
  PRODUCT_INACTIVE: 'produto inativo',
  PRODUCT_NOT_FOUND: 'produto não encontrado',
  SELLER_NOT_FOUND: 'vendedor não encontrado',
  CAMPAIGN_NOT_FOUND: 'campanha não encontrada',
  CAMPAIGN_NOT_ACTIVE: 'campanha encerrada',
  CAMPAIGN_OUT_OF_PERIOD: 'fora do período da campanha',
  INSUFFICIENT_BUDGET: 'verba insuficiente',
  INVALID_FIELD: 'campo inválido',
  MISSING_FIELD: 'campo obrigatório',
  VALUE_TOO_LONG: 'texto muito longo',
  VALUE_OUT_OF_RANGE: 'valor fora do limite',
  DATE_IN_THE_PAST: 'data no passado',
  SALE_ALREADY_EXISTS: 'venda já existe',
  SALE_ALREADY_CANCELED: 'venda já cancelada',
  DATABASE_ERROR: 'falha ao salvar',
}
