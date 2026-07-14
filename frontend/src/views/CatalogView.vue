<script setup>
import { ref, onMounted, watch } from 'vue'
import { fetchProducts } from '../api'
import ProductCard from '../components/ProductCard.vue'

const products = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref(null)

const q = ref('')
const activeRange = ref('all')
const page = ref(1)

const ranges = [
  { key: 'all', label: 'Semua', min: null, max: null },
  { key: 'lt5', label: '< Rp 5 jt', min: 1000, max: 5000000 },
  { key: '5to25', label: 'Rp 5–25 jt', min: 5000000, max: 25000000 },
  { key: 'gt25', label: '> Rp 25 jt', min: 25000000, max: null },
]

const catalogTotal = ref(0)

async function load() {
  loading.value = true
  error.value = null
  try {
    const range = ranges.find((r) => r.key === activeRange.value)
    const data = await fetchProducts({
      page: page.value,
      q: q.value,
      minPrice: range.min,
      maxPrice: range.max,
    })
    products.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total }
    if (!catalogTotal.value && !q.value && activeRange.value === 'all') {
      catalogTotal.value = data.total
    }
  } catch (e) {
    error.value = 'Gagal memuat data katalog. Pastikan API backend berjalan.'
  } finally {
    loading.value = false
  }
}

function setRange(key) {
  activeRange.value = key
  page.value = 1
}

function goPage(p) {
  if (p < 1 || p > meta.value.last_page) return
  page.value = p
  document.getElementById('katalog')?.scrollIntoView({ behavior: 'smooth' })
}

let searchTimer
watch(q, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, 350)
})

watch([activeRange, page], load)

onMounted(load)

const categoryTiles = [
  { big: 'SURYA', caption: 'Hemat energi', desc: 'PJU tenaga surya all-in-one dengan panel dan baterai terintegrasi.', query: 'surya' },
  { big: 'LED', caption: 'Cahaya maksimal', desc: 'Luminer LED efikasi tinggi untuk pencahayaan jalan umum.', query: 'led' },
  { big: 'TIANG', caption: 'Terpasang lengkap', desc: 'Paket pengadaan dan pemasangan lengkap dengan tiang oktagonal.', query: 'tiang' },
]

function searchTile(query) {
  q.value = query
  document.getElementById('katalog')?.scrollIntoView({ behavior: 'smooth' })
}

function tileMove(event) {
  const big = event.currentTarget.querySelector('.tile-big')
  if (!big) return
  const rect = big.getBoundingClientRect()
  big.style.setProperty('--mx', `${event.clientX - rect.left}px`)
}
</script>

<template>
  <!-- Hero -->
  <section class="hero">
    <div class="hero-overlay">
      <p class="hero-eyebrow">INAPROC</p>
      <p class="hero-title">Lampu LED</p>
      <a href="#katalog" class="hero-link">Lihat Katalog</a>
    </div>
  </section>

  <!-- Brand statement -->
  <section class="statement container" id="statement">
    <h1>Terang<sup>APJ</sup></h1>
    <p>
      TerangAPJ adalah katalog yang menampilkan {{ catalogTotal || 120 }} produk Alat Penerangan
      Jalan (APJ) hasil pengumpulan data dari Katalog Elektronik INAPROC. Setiap produk memuat
      nama, vendor, lokasi, harga, jumlah terjual, nilai TKDN, serta spesifikasi teknis seperti
      daya, lumen, efikasi, dan voltase bila tersedia. Daftar di bawah dapat ditelusuri dengan
      pencarian nama produk dan filter rentang harga.
    </p>
  </section>

  <!-- Toolbar: search + price range pills -->
  <section class="toolbar container" id="katalog">
    <div class="pills" role="tablist" aria-label="Filter rentang harga">
      <button
        v-for="r in ranges"
        :key="r.key"
        class="pill"
        :class="{ active: activeRange === r.key }"
        @click="setRange(r.key)"
      >
        {{ r.label }}
      </button>
    </div>
    <input
      v-model="q"
      class="search"
      type="search"
      placeholder="Cari nama produk…"
      aria-label="Cari produk"
    />
  </section>

  <!-- Grid -->
  <section class="grid-wrap container">
    <p class="label result-line" v-if="!loading && !error">
      Menampilkan {{ products.length }} dari {{ meta.total }} produk · Halaman
      {{ meta.current_page }} / {{ meta.last_page }}
    </p>

    <div v-if="error" class="empty">
      <p>{{ error }}</p>
      <button class="btn small" @click="load">Coba Lagi</button>
    </div>

    <div v-else-if="loading" class="grid">
      <div v-for="i in 8" :key="i" class="skeleton" />
    </div>

    <div v-else-if="products.length === 0" class="empty">
      <p>Tidak ada produk yang cocok dengan pencarian Anda.</p>
    </div>

    <div v-else class="grid">
      <ProductCard v-for="(p, i) in products" :key="p.id" :product="p" :index="i" />
    </div>

    <!-- Pagination -->
    <nav class="pager" v-if="!loading && !error && meta.last_page > 1" aria-label="Navigasi halaman">
      <button class="pager-arrow" :disabled="meta.current_page === 1" aria-label="Halaman sebelumnya" @click="goPage(meta.current_page - 1)">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" focusable="false" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12 0L4.73232 6.45304C4.28525 6.81984 4 7.37663 4 8C4 8.62336 4.28524 9.18015 4.73232 9.54696L12 16L8 8L12 0Z"></path>
        </svg>
      </button>
      <button
        v-for="p in meta.last_page"
        :key="p"
        class="pill"
        :class="{ active: p === meta.current_page }"
        @click="goPage(p)"
      >
        {{ p }}
      </button>
      <button class="pager-arrow flip" :disabled="meta.current_page === meta.last_page" aria-label="Halaman berikutnya" @click="goPage(meta.current_page + 1)">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" focusable="false" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12 0L4.73232 6.45304C4.28525 6.81984 4 7.37663 4 8C4 8.62336 4.28524 9.18015 4.73232 9.54696L12 16L8 8L12 0Z"></path>
        </svg>
      </button>
    </nav>
  </section>

  <!-- Category tiles -->
  <section class="tiles container">
    <button v-for="t in categoryTiles" :key="t.big" class="tile" @click="searchTile(t.query)" @mousemove="tileMove">
      <span class="tile-big">{{ t.big }}</span>
      <span class="tile-caption">{{ t.caption }}</span>
      <span class="tile-desc">{{ t.desc }}</span>
      <span class="btn small tile-btn">Cari {{ t.big }}</span>
    </button>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  height: calc(100svh - 30px - 55px);
  min-height: 480px;
  background: #dadbde url('/section-bg.png') center / cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 55px;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 1.4rem;
  text-align: center;
  padding: 1rem 1rem 4.5rem;
}

.hero-eyebrow {
  color: #fff;
  font-size: 0.625rem;
  font-weight: 400;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  margin-bottom: -0.8rem;
}

.hero-title {
  color: #fff;
  font-size: 2rem;
  font-weight: 330;
  letter-spacing: 0.01em;
  line-height: 1.15;
  text-wrap: balance;
}

.hero-link {
  position: relative;
  display: inline-block;
  overflow: hidden;
  color: #fff;
  font-size: 1rem;
  font-weight: 450;
  padding-bottom: 3px;
}

.hero-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 1px;
  background: currentColor;
  animation: line-in 0.35s ease both;
}

.hero-link:hover::after {
  animation: line-out 0.35s ease both;
}

@keyframes line-out {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(110%);
    opacity: 0;
  }
}

@keyframes line-in {
  from {
    transform: translateX(-110%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-link::after,
  .hero-link:hover::after {
    animation: none;
  }
}

.statement {
  text-align: center;
  padding: 4.5rem 20px 3rem;
  max-width: 780px;
}

.statement h1 {
  font-size: clamp(2.8rem, 7vw, 4.6rem);
  font-weight: 640;
  letter-spacing: -0.04em;
  margin-bottom: 1.1rem;
}

.statement h1 sup {
  font-size: 0.25em;
  letter-spacing: 0.1em;
  font-weight: 500;
  vertical-align: super;
}

.statement p {
  color: var(--ink-soft);
  font-size: 0.95rem;
  max-width: 58ch;
  margin: 0 auto;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  padding-bottom: 1.2rem;
  scroll-margin-top: 90px;
}

.pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.pill {
  border: 1px solid var(--line);
  background: var(--surface);
  border-radius: 999px;
  padding: 0.45rem 1rem;
  font-size: 0.78rem;
  color: var(--ink-soft);
  transition: all 0.12s ease;
}

.pill:hover:not(:disabled):not(.active) {
  border-color: var(--ink);
  color: var(--ink);
}

.pill.active {
  background: var(--ink);
  border-color: var(--ink);
  color: #fff;
}

.pill:disabled {
  opacity: 0.35;
  cursor: default;
}

.search {
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--surface);
  padding: 0.55rem 1.2rem;
  font-size: 0.85rem;
  font-family: inherit;
  min-width: min(300px, 100%);
  outline: none;
}

.search:focus {
  border-color: var(--ink);
}

.search:placeholder-shown {
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="%23131313" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20l-3-3"/></g></svg>');
  background-repeat: no-repeat;
  background-position: right 13px center;
  background-size: 15px 15px;
}

.search::-webkit-search-cancel-button {
  -webkit-appearance: none;
  appearance: none;
  height: 13px;
  width: 13px;
  background: var(--ink);
  -webkit-mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M3 3l10 10M13 3L3 13" stroke="black" stroke-width="2" stroke-linecap="round" fill="none"/></svg>') center / contain no-repeat;
  mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M3 3l10 10M13 3L3 13" stroke="black" stroke-width="2" stroke-linecap="round" fill="none"/></svg>') center / contain no-repeat;
  cursor: pointer;
  margin-right: -8px;
}

.result-line {
  margin-top: 3.5rem;
  margin-bottom: 0.9rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

@media (max-width: 1080px) {
  .grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 780px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 460px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.skeleton {
  aspect-ratio: 3 / 4.4;
  border-radius: var(--radius);
  background: linear-gradient(100deg, var(--tile) 40%, #efedea 50%, var(--tile) 60%);
  background-size: 200% 100%;
  animation: shimmer 1.3s infinite;
}

@keyframes shimmer {
  to { background-position: -200% 0; }
}

.empty {
  text-align: center;
  padding: 4rem 1rem;
  color: var(--ink-soft);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.pager {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  margin-top: 2rem;
}

.pager .pill {
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.pager-arrow {
  border: none;
  background: transparent;
  padding: 0.45rem 0.6rem;
  display: inline-flex;
  align-items: center;
  color: var(--ink);
  transition: opacity 0.12s ease;
}

.pager-arrow:hover:not(:disabled) {
  opacity: 0.6;
}

.pager-arrow:disabled {
  opacity: 0.25;
  cursor: default;
}

.pager-arrow.flip svg {
  transform: scaleX(-1);
}

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
  padding: 4.5rem 20px 1rem;
}

.stat-quote {
  font-size: 0.8rem;
  color: var(--ink-soft);
  margin-bottom: 0.8rem;
  line-height: 1.55;
}

@media (max-width: 900px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 520px) {
  .stats {
    grid-template-columns: 1fr;
  }
}

.tiles {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  padding-top: 7rem;
}

.tile {
  position: relative;
  border: none;
  border-radius: var(--radius);
  background: #131313;
  color: #fff;
  min-height: 380px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1.4rem;
  text-align: left;
  overflow: hidden;
}

.tile-big {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -60%);
  font-size: clamp(2.6rem, 5.5vw, 4rem);
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #fff;
  z-index: 1;
  background: radial-gradient(
    circle 120px at var(--mx, 50%) var(--my, 50%),
    #ffffff 0%,
    #ffffff 55%,
    rgba(255, 255, 255, 0.4) 72%,
    #131313 88%
  );
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: #ffffff;
  transition: -webkit-text-fill-color 0.9s ease;
}

.tile:hover:not(:has(.tile-btn:hover)) .tile-big {
  -webkit-text-fill-color: rgba(255, 255, 255, 0);
  transition: -webkit-text-fill-color 0.45s ease;
}

@media (prefers-reduced-motion: reduce) {
  .tile-big {
    transition: none;
  }
}

.tile-caption {
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #c9c7c2;
}

.tile-desc {
  font-size: 0.8rem;
  color: #a5a39e;
  max-width: 30ch;
}

.tile-btn {
  margin-top: 0.5rem;
  background: #fff;
  color: var(--ink);
}

.tile-btn:hover {
  color: #fff;
}

@media (max-width: 780px) {
  .tiles {
    grid-template-columns: 1fr;
  }
  .tile {
    min-height: 260px;
  }
}
</style>
