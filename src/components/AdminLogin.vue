<template>
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Anmelden</h2>
    <form @submit.prevent="submit">
      <label class="block mb-2">
        <span class="text-sm">Benutzername</span>
        <input
          v-model="username"
          type="text"
          class="w-full border rounded px-2 py-1"
          required
          placeholder="Dein Benutzername"
        />
      </label>
      <label class="block mb-4">
        <span class="text-sm">Passwort</span>
        <input
          v-model="password"
          type="password"
          class="w-full border rounded px-2 py-1"
          required
        />
      </label>
      <div class="flex items-center justify-between">
        <button class="bg-blue-600 text-white px-4 py-2 rounded" :disabled="loading">
          {{ loading ? '...' : 'Login' }}
        </button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
const emit = defineEmits(['login'])

const username = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const res = await fetch('/api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ name: username.value, password: password.value }),
    })
    const j = await res.json()
    if (!res.ok) {
      error.value = j.error || 'Login fehlgeschlagen'
    } else {
      emit('login', j.user)
    }
  } catch (e) {
    error.value = 'Netzwerkfehler'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
