const BASE_URL = import.meta.env.VITE_API_BASE || 'http://127.0.0.1:8123/api'

export async function fetchProducts({ page = 1, q = '', minPrice = null, maxPrice = null } = {}) {
  const params = new URLSearchParams()
  params.set('page', page)
  if (q) params.set('q', q)
  if (minPrice !== null) params.set('min_price', minPrice)
  if (maxPrice !== null) params.set('max_price', maxPrice)

  const res = await fetch(`${BASE_URL}/products?${params}`)
  if (!res.ok) throw new Error(`API error ${res.status}`)
  return res.json()
}

export function formatPrice(value) {
  const n = Number(value)
  if (!n || n < 1000) return 'Hubungi Vendor'
  return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 })
}

export function localImage(url) {
  if (!url) return ''
  return '/img/' + url.split('/').pop()
}
