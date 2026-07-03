export function parseLimit(
  raw: string | undefined,
  def: number,
  max: number
): number {
  if (raw === undefined) return def
  const n = Number.parseInt(raw, 10)
  if (!Number.isFinite(n) || n < 1) return def
  return Math.min(n, max)
}
