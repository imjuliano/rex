/**
 * Frontend mirror of backend/src/Validation/Limits.php.
 *
 * Changing the PHP source without touching this file is a bug. Keep them
 * in sync so maxLength and validation messages always match the database.
 */

export const LIMITS = {
  userName: 255,
  userEmail: 255,
  password: 1024,
  productName: 100,
  productSku: 100,
  campaignName: 255,
  saleExternalId: 255,
  budgetTotalMax: 2_147_483_647,
  pointsPerUnitMax: 2_147_483_647,
  quantityMax: 1_000_000,
  unitValueMax: 99_999_999.99,
}

/**
 * @param {keyof LIMITS} key
 * @param {number} [fallback]
 */
export function limit(key, fallback) {
  return LIMITS[key] ?? fallback
}
