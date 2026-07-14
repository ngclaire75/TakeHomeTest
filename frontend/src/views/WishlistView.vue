<script setup>
import { useWishlistStore } from '../stores/wishlist'
import ProductCard from '../components/ProductCard.vue'

const wishlist = useWishlistStore()
</script>

<template>
  <section class="wishlist container">
    <header class="wl-head">
      <h1>Wishlist Saya</h1>
      <p class="label">{{ wishlist.count }} produk tersimpan · tetap ada meski browser di-refresh</p>
    </header>

    <div v-if="wishlist.count === 0" class="empty">
      <p>Wishlist Anda masih kosong.</p>
      <p class="hint">Tekan ikon hati atau tombol “+ Wishlist” pada produk di katalog untuk menyimpannya di sini.</p>
      <RouterLink to="/" class="btn">Jelajahi Katalog</RouterLink>
    </div>

    <div v-else class="grid">
      <ProductCard v-for="(p, i) in wishlist.items" :key="p.id" :product="p" :index="i" :remove-label="true" />
    </div>
  </section>
</template>

<style scoped>
.wishlist {
  padding-top: 3rem;
  min-height: 55vh;
}

.wl-head {
  text-align: center;
  margin-bottom: 2.2rem;
}

.wl-head h1 {
  font-size: clamp(1.9rem, 4.5vw, 2.8rem);
  font-weight: 640;
  letter-spacing: -0.03em;
  margin-bottom: 0.5rem;
}

.empty {
  text-align: center;
  padding: 4rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
  color: var(--ink-soft);
}

.empty .hint {
  font-size: 0.85rem;
  color: var(--ink-faint);
  max-width: 42ch;
}

.empty .btn {
  margin-top: 0.8rem;
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
</style>
