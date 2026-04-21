import { defineStore } from 'pinia'
import { ref } from 'vue'
import router from '../router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any | null>(null)
  const loading = ref(false)

  async function fetchSession() {
    loading.value = true
    try {
      const res = await fetch('/api/session.php', { credentials: 'include' })
      const j = await res.json()
      if (res.ok && j.ok && j.user) user.value = j.user
      else user.value = null
    } catch (e) {
      user.value = null
    } finally {
      loading.value = false
    }
  }

  async function login(email: string, password: string) {
    const res = await fetch('/api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email, password })
    })
    const j = await res.json()
    if (res.ok && j.user) {
      user.value = j.user
      return { ok: true, user: j.user }
    }
    return { ok: false, error: j.error }
  }

  async function logout() {
    await fetch('/api/logout.php', { credentials: 'include' })
    user.value = null
    // stay on same page
  }

  return { user, loading, fetchSession, login, logout }
})
