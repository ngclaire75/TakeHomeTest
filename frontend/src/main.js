import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import CatalogView from './views/CatalogView.vue'
import WishlistView from './views/WishlistView.vue'
import './style.css'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'catalog', component: CatalogView },
    { path: '/wishlist', name: 'wishlist', component: WishlistView },
  ],
  scrollBehavior(to) {
    if (to.hash) {
      return { el: to.hash, top: to.hash === '#statement' ? 110 : 0, behavior: 'smooth' }
    }
    return { top: 0 }
  },
})

createApp(App).use(createPinia()).use(router).mount('#app')
