import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { registerUnauthorizedHandler } from './services/http'
import { useAuthStore } from './stores/auth'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Wire the axios 401 interceptor to the auth store + router.
const auth = useAuthStore()
registerUnauthorizedHandler(() => {
  auth.clearSession()
  router.push({ name: 'login' })
})

app.mount('#app')
