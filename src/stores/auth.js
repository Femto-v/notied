import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, profileApi } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('notied_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('notied_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)

  function persist() {
    if (token.value) localStorage.setItem('notied_token', token.value)
    else localStorage.removeItem('notied_token')
    if (user.value) localStorage.setItem('notied_user', JSON.stringify(user.value))
    else localStorage.removeItem('notied_user')
  }

  function setSession(data) {
    token.value = data.token
    user.value = data.user
    persist()
  }

  async function login(credentials) {
    const data = await authApi.login(credentials)
    setSession(data)
    return data
  }

  async function register(payload) {
    const data = await authApi.register(payload)
    setSession(data)
    return data
  }

  async function refreshProfile() {
    user.value = await profileApi.me()
    persist()
  }

  function logout() {
    token.value = null
    user.value = null
    persist()
  }

  // Called by the 401 interceptor.
  function clearSession() {
    logout()
  }

  return {
    token, user, isAuthenticated,
    login, register, logout, clearSession, refreshProfile, setSession,
  }
})
