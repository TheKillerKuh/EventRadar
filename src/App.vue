<script setup lang="ts"></script>

<template>
  <div id="app">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="text-lg font-bold">EventRadar</div>
        <nav class="space-x-4">
          <router-link to="/" class="text-gray-700 hover:underline">Turniere</router-link>
          <router-link to="/calendar" class="text-gray-700 hover:underline">Kalender</router-link>
          <template v-if="!auth.loading">
            <router-link v-if="!auth.user" to="/login" class="text-gray-700 hover:underline">Login</router-link>
            <span v-else class="inline-flex items-center gap-3">
              <router-link to="/admin" class="text-gray-700 hover:underline">{{ auth.user.name }}</router-link>
              <button @click="doLogout" class="text-sm px-2 py-1 border rounded">Logout</button>
            </span>
          </template>
        </nav>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
      <router-view />
    </main>
    <Toasts />
  </div>
</template>

<style scoped>
a { color: #111; text-decoration: none }
a:hover { text-decoration: underline }
</style>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'
import Toasts from './components/Toasts.vue'

const auth = useAuthStore()
const router = useRouter()

onMounted(() => {
  auth.fetchSession()
})

function goToManage() {
  router.push('/admin')
}

async function doLogout() {
  await auth.logout()
  // navigate to home without reloading so Pinia state persists cleared
  router.push('/')
}
</script>
