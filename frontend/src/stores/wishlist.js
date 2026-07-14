import { defineStore } from 'pinia'

const STORAGE_KEY = 'terang_wishlist_v1'

function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

export const useWishlistStore = defineStore('wishlist', {
  state: () => ({
    items: load(),
    savedPopupVisible: false,
  }),
  getters: {
    count: (state) => state.items.length,
    has: (state) => (id) => state.items.some((p) => p.id === id),
  },
  actions: {
    toggle(product) {
      const idx = this.items.findIndex((p) => p.id === product.id)
      if (idx >= 0) {
        this.items.splice(idx, 1)
      } else {
        this.items.push(product)
        this.savedPopupVisible = true
      }
      this.persist()
    },
    dismissPopup() {
      this.savedPopupVisible = false
    },
    remove(id) {
      this.items = this.items.filter((p) => p.id !== id)
      this.persist()
    },
    persist() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items))
    },
  },
})
