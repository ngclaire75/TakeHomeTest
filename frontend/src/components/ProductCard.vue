<script setup>
import { computed } from 'vue'
import { formatPrice, localImage } from '../api'
import { useWishlistStore } from '../stores/wishlist'

const props = defineProps({
  product: { type: Object, required: true },
  index: { type: Number, default: 0 },
  removeLabel: { type: Boolean, default: false },
})

const wishlist = useWishlistStore()
const saved = computed(() => wishlist.has(props.product.id))

const chip = computed(() => {
  const attrs = props.product.dynamic_attributes
  if (attrs?.daya_watt) return `${Math.round(attrs.daya_watt)} WATT`
  return 'APJ'
})

const chipNo = computed(() => String((props.index % 60) + 1).padStart(2, '0'))
</script>

<template>
  <article class="card">
    <div class="card-media">
      <span class="chip">{{ chip }} <i>{{ chipNo }}</i></span>
      <button
        class="heart"
        :class="{ active: saved }"
        :aria-label="saved ? 'Hapus dari wishlist' : 'Simpan ke wishlist'"
        @click="wishlist.toggle(product)"
      >
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" focusable="false" aria-hidden="true" data-name="heart-default">
          <path fill="currentColor" fill-rule="evenodd" d="M7.681 4.443c-2.814 0-4.286 2.543-4.286 5.054 0 3.399 5.9 8.014 8.607 9.806 2.705-1.774 8.603-6.36 8.603-9.806 0-2.511-1.45-5.054-4.22-5.054-2.694 0-3.731 2.489-3.741 2.514l-.61 1.508-.667-1.482c-.046-.102-1.19-2.54-3.686-2.54M11.998 21l-.363-.23C11.242 20.518 2 14.585 2 9.496c0-1.666.506-3.236 1.424-4.422C4.476 3.718 5.948 3 7.681 3c2.17 0 3.564 1.322 4.303 2.312C12.704 4.317 14.091 3 16.385 3 20.032 3 22 6.347 22 9.497c0 5.16-9.244 11.027-9.638 11.274z" clip-rule="evenodd" />
          <path class="heart-solid" fill="currentColor" d="M11.998 21l-.363-.23C11.242 20.518 2 14.585 2 9.496c0-1.666.506-3.236 1.424-4.422C4.476 3.718 5.948 3 7.681 3c2.17 0 3.564 1.322 4.303 2.312C12.704 4.317 14.091 3 16.385 3 20.032 3 22 6.347 22 9.497c0 5.16-9.244 11.027-9.638 11.274z" />
        </svg>
      </button>
      <img :src="localImage(product.image_url)" :alt="product.name" loading="lazy" @error="$event.target.style.opacity = 0" />
    </div>
    <div class="card-body">
      <p class="vendor">{{ product.vendor }}</p>
      <h3 class="name" :title="product.name">{{ product.name }}</h3>
      <div class="meta">
        <span class="price">{{ formatPrice(product.price) }}</span>
        <span class="tkdn" v-if="product.tkdn_value">TKDN {{ Math.round(product.tkdn_value) }}%</span>
      </div>
      <p class="sold" v-if="product.unit_sold > 0">Terjual {{ product.unit_sold.toLocaleString('id-ID') }}</p>
      <p class="sold" v-else>{{ product.location }}</p>
      <div class="actions">
        <a class="btn ghost small" :href="product.detail_url" target="_blank" rel="noopener">Lihat Produk</a>
        <button class="btn small" @click="wishlist.toggle(product)">
          <svg v-if="!saved" class="btn-ico" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" focusable="false" aria-hidden="true">
            <path d="M8 2.5v11M2.5 8h11" />
          </svg>
          {{ saved ? (removeLabel ? 'Hapus Wishlist' : 'Tersimpan') : 'Wishlist' }}
        </button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  background: var(--surface);
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--line);
}

.card-media {
  position: relative;
  aspect-ratio: 1 / 1;
  background: var(--tile);
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-media img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  mix-blend-mode: multiply;
  padding: 1.4rem;
}

.chip {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 2;
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  font-weight: 600;
  background: #fff;
  border-radius: 999px;
  padding: 0.3rem 0.7rem;
  border: 1px solid var(--line);
}

.chip i {
  font-style: normal;
  color: var(--ink-faint);
  margin-left: 4px;
}

.heart {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid var(--ink);
  background: #fff;
  color: var(--ink);
  display: flex;
  align-items: center;
  justify-content: center;
}

.heart .heart-solid {
  display: none;
}

.heart:hover .heart-solid {
  display: block;
}


.card-body {
  padding: 0.95rem 1rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  flex: 1;
}

.vendor {
  font-size: 0.64rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--ink-faint);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.name {
  font-size: 0.88rem;
  font-weight: 560;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.3em;
}

.meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.15rem;
}

.price {
  font-size: 0.86rem;
  font-weight: 620;
}

.tkdn {
  font-size: 0.62rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  background: var(--tile);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  white-space: nowrap;
}

.sold {
  font-size: 0.7rem;
  color: var(--ink-faint);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 0.7rem;
}

.actions .btn {
  flex: 1;
  white-space: nowrap;
}
</style>
