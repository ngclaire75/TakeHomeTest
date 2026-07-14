<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useWishlistStore } from './stores/wishlist'

const wishlist = useWishlistStore()
const route = useRoute()
const marqueeItems = Array(10).fill('Jalan terang, kota lebih aman · TerangAPJ')

const TICKER_HEIGHT = 30
const scrolled = ref(false)

function onScroll() {
  scrolled.value = window.scrollY > TICKER_HEIGHT
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})

onUnmounted(() => window.removeEventListener('scroll', onScroll))

const isHome = computed(() => route.name === 'catalog')
const navClass = computed(() => ({
  overlay: isHome.value && !scrolled.value,
  pinned: isHome.value && scrolled.value,
}))
</script>

<template>
  <div class="marquee" aria-hidden="true">
    <div class="marquee-track">
      <span v-for="(item, i) in marqueeItems" :key="i">{{ item }}</span>
    </div>
  </div>

  <header class="nav" :class="navClass">
    <nav class="nav-left">
      <RouterLink to="/#statement">
        <svg width="18" height="18" viewBox="0 0 80 80" fill="none" focusable="false" aria-hidden="true">
          <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="6">
            <path fill="currentColor" d="M17.713 31.916A2.25 2.25 0 0 1 19.938 30h40.124a2.25 2.25 0 0 1 2.225 1.916l5.325 35.5A2.25 2.25 0 0 1 65.387 70H14.613a2.25 2.25 0 0 1-2.225-2.584z" />
            <path d="M52 38V22c0-6.627-5.373-12-12-12v0c-6.627 0-12 5.373-12 12v16" />
          </g>
        </svg>
        Katalog
      </RouterLink>
      <a href="#tentang">
        <svg width="18" height="18" viewBox="0 0 24 24" focusable="false" aria-hidden="true">
          <path fill="currentColor" d="M20 6h-1v8c0 .55-.45 1-1 1H6v1c0 1.1.9 2 2 2h10l4 4V8c0-1.1-.9-2-2-2m-3 5V4c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v13l4-4h9c1.1 0 2-.9 2-2" />
        </svg>
        Tentang Produk
      </a>
    </nav>
    <RouterLink to="/" class="brand">Terang<sup>APJ</sup></RouterLink>
    <div class="nav-right">
      <RouterLink to="/wishlist" class="wish-pill">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" focusable="false" aria-hidden="true" data-name="heart-default">
          <path fill="currentColor" fill-rule="evenodd" d="M7.681 4.443c-2.814 0-4.286 2.543-4.286 5.054 0 3.399 5.9 8.014 8.607 9.806 2.705-1.774 8.603-6.36 8.603-9.806 0-2.511-1.45-5.054-4.22-5.054-2.694 0-3.731 2.489-3.741 2.514l-.61 1.508-.667-1.482c-.046-.102-1.19-2.54-3.686-2.54M11.998 21l-.363-.23C11.242 20.518 2 14.585 2 9.496c0-1.666.506-3.236 1.424-4.422C4.476 3.718 5.948 3 7.681 3c2.17 0 3.564 1.322 4.303 2.312C12.704 4.317 14.091 3 16.385 3 20.032 3 22 6.347 22 9.497c0 5.16-9.244 11.027-9.638 11.274z" clip-rule="evenodd" />
        </svg>
        Wishlist
      </RouterLink>
    </div>
  </header>

  <main>
    <RouterView />
  </main>

  <div v-if="wishlist.savedPopupVisible" class="wl-popup-backdrop">
    <div class="wl-popup" role="alertdialog" aria-label="Notifikasi wishlist">
      <button class="wl-popup-close" aria-label="Tutup" @click="wishlist.dismissPopup()">&times;</button>
      <p>Produk Telah Tersimpan<br />Di Wishlist</p>
    </div>
  </div>

  <footer class="footer" id="tentang">
    <div class="container footer-grid">
      <div class="footer-news">
        <p class="label">Newsletter TerangAPJ</p>
        <form class="news-form" @submit.prevent>
          <input type="email" placeholder="Alamat Email" aria-label="Alamat email" />
          <button class="btn small" type="submit">Berlangganan</button>
        </form>
        <p class="footer-fine">
          Tetap terinformasi soal produk penerangan jalan terbaru di katalog pemerintah.
          Dengan berlangganan, Anda menyetujui kebijakan privasi kami dan dapat berhenti kapan saja.
        </p>
      </div>
      <div class="footer-links">
        <div>
          <p class="label">Tautan</p>
          <a href="/">Semua Produk</a>
          <a href="/wishlist">Wishlist Saya</a>
          <a href="https://katalog.inaproc.id" target="_blank" rel="noopener">Katalog INAPROC</a>
        </div>
        <div>
          <p class="label">Info</p>
          <a href="#tentang">Tentang</a>
          <a href="https://bantuan.inaproc.id" target="_blank" rel="noopener">Bantuan</a>
          <a href="#tentang">Kontak</a>
        </div>
      </div>
    </div>
    <div class="container footer-base">
      <span>© 2026 TerangAPJ — Data produk bersumber dari Katalog Elektronik INAPROC</span>
    </div>
  </footer>
</template>

<style scoped>
.marquee {
  background: var(--ink);
  color: #fff;
  overflow: hidden;
  font-size: 0.68rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  height: 30px;
  display: flex;
  align-items: center;
}

.marquee-track {
  display: flex;
  gap: 3rem;
  white-space: nowrap;
  width: max-content;
  animation: scroll 40s linear infinite;
}

@keyframes scroll {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

.nav {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  padding: 1rem 24px;
  background: var(--bg);
  color: var(--ink);
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid var(--line);
  transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
}

.nav.overlay {
  position: absolute;
  top: 30px;
  left: 0;
  right: 0;
  background: transparent;
  color: #fff;
  border-bottom-color: transparent;
}

.nav.overlay:hover {
  background: var(--bg);
  color: var(--ink);
}

.nav.pinned {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

.nav-left {
  display: flex;
  gap: 1.4rem;
  font-size: 0.95rem;
}

.nav-left a {
  color: inherit;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.nav-left a svg {
  flex: none;
}

.nav-left a:hover {
  opacity: 0.6;
}

.brand {
  font-size: 2.6rem;
  font-weight: 640;
  letter-spacing: -0.03em;
}

.brand sup {
  font-size: 0.55rem;
  letter-spacing: 0.08em;
  font-weight: 500;
  margin-left: 2px;
}

.nav-right {
  display: flex;
  justify-content: flex-end;
}

.wish-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid currentColor;
  border-radius: 999px;
  padding: 0.4rem 0.9rem;
  font-size: 0.95rem;
  font-weight: 400;
  color: inherit;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.nav:not(.overlay) .wish-pill:hover,
.nav.overlay:hover .wish-pill:hover {
  background: var(--ink);
  color: #fff;
}

.nav.overlay:not(:hover) .wish-pill:hover {
  background: #fff;
  color: var(--ink);
}

.wl-popup-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 14, 12, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.wl-popup {
  position: relative;
  background: #fff;
  color: var(--ink);
  border: 1px solid var(--ink);
  border-radius: var(--radius);
  padding: 2rem;
  max-width: 340px;
  width: 100%;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-size: 1.15rem;
  font-weight: 500;
  box-shadow: 0 18px 50px rgba(15, 14, 12, 0.25);
  animation: popup-zoom 0.3s ease both;
}

.wl-popup-close {
  position: absolute;
  top: 6px;
  right: 12px;
  border: none;
  background: transparent;
  color: var(--ink);
  font-size: 1.5rem;
  line-height: 1;
  padding: 4px;
}

.wl-popup-close:hover {
  opacity: 0.6;
}

@keyframes popup-zoom {
  from {
    transform: scale(0.55);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .wl-popup {
    animation: none;
  }
}

.footer {
  background: var(--ink);
  color: #eceae6;
  margin-top: 5rem;
  padding: 3.5rem 0 2rem;
}

.footer .label {
  color: #9b9994;
  margin-bottom: 1rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 3rem;
}

.news-form {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  border: 1px solid #4a4946;
  border-radius: 999px;
  padding: 0.3rem 0.3rem 0.3rem 1.1rem;
  max-width: 430px;
  margin-bottom: 1rem;
}

.news-form input {
  flex: 1;
  min-width: 0;
  background: transparent;
  border: none;
  outline: none;
  color: #fff;
  font-size: 0.82rem;
  font-family: inherit;
}

.news-form .btn {
  background: #fff;
  color: var(--ink);
}

.footer-fine {
  font-size: 0.7rem;
  color: #8b8a85;
  max-width: 46ch;
}

.footer-links {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.footer-links a {
  display: block;
  font-size: 0.82rem;
  margin-bottom: 0.55rem;
  color: #d8d6d1;
}

.footer-links a:hover {
  color: #fff;
}

.footer-base {
  margin-top: 2.5rem;
  padding-top: 1.4rem;
  font-size: 0.72rem;
  color: #8b8a85;
}

@media (max-width: 760px) {
  .nav {
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
  }
  .nav-left {
    gap: 0.9rem;
    font-size: 0.72rem;
  }
  .brand {
    font-size: 1.2rem;
    justify-self: center;
  }
  .footer-grid {
    grid-template-columns: 1fr;
  }
}
</style>
