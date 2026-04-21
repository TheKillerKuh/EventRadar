<template>
  <section class="max-w-md mx-auto">
    <AdminLogin @login="onLogin" />
  </section>
</template>

<script setup lang="ts">
import AdminLogin from '../components/AdminLogin.vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function onLogin(user: any) {
  // set auth immediately and navigate
  auth.user = user
  await router.push('/admin')
}
</script>

<style scoped></style>
