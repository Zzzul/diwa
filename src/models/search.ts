export type SearchFilter = {
  name: string
  label: string
  options: { value: string; label: string }[]
}

export type SearchResult = {
  rank: number
  name: string
  slug: string
  popularity: number
  description: string
}
