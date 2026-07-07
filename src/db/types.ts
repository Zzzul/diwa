export type NewsItem = {
  id: string
  date: string | null
  is_new: boolean | null
  type: string | null
  headline: string | null
  headline_slug: string | null
  headline_url: string | null
  logo: string | null
  screenshot: string | null
  rating: number | null
  text: string | null
  text_html: string | null
  links: { url: string | null; text: string; href: string | null }[]
  distribution: string | null
  release_type: string | null
  month: string | null
  year: string | null
  scraped_at: string
}
