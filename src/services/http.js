import axios from 'axios'

// Base URL: in dev, Vite proxies /api to the Slim backend (see vite.config.js).
// In production set VITE_API_BASE to the deployed API origin.
const baseURL = import.meta.env.VITE_API_BASE || '/api'

const http = axios.create({
  baseURL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 15000,
})

// Request interceptor — attach Authorization: Bearer <token> on every call.
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('notied_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor — on 401, clear the session and bounce to /login.
// We import the store lazily to avoid a circular dependency at module load.
let onUnauthorized = null
export function registerUnauthorizedHandler(fn) {
  onUnauthorized = fn
}

http.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401 && onUnauthorized) {
      onUnauthorized()
    }
    return Promise.reject(error)
  }
)

export default http
