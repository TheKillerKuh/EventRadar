<script setup lang="ts"></script>

<template>
  <div id="app">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="text-lg font-bold">EventRadar</div>
        <nav class="space-x-4">
          <router-link to="/" class="nav-link">Turniere</router-link>
          <router-link to="/calendar" class="nav-link">Kalender</router-link>
          <template v-if="!auth.loading">
            <router-link v-if="!auth.user" to="/login" class="nav-link">Login</router-link>
            <span v-else class="inline-flex items-center gap-3">
              <router-link to="/admin" class="nav-link">{{ auth.user.name }}</router-link>
              <button @click="doLogout" class="text-sm px-2 py-1 border rounded hover:bg-gray-100">Logout</button>
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

.nav-link {
  @apply text-gray-600 hover:text-gray-900 px-3 py-2 transition-all relative;
  text-decoration: none;
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  transition: width 0.3s ease;
}

.nav-link.router-link-active,
.nav-link.router-link-exact-active {
  @apply text-gray-900 font-semibold;
}

.nav-link.router-link-active::after,
.nav-link.router-link-exact-active::after {
  width: 100%;
}

.nav-link:hover {
  @apply text-gray-900;
}

.nav-link:hover::after {
  width: 100%;
}
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
