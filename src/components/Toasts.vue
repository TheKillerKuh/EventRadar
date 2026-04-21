<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <div v-for="t in store.toasts" :key="t.id" :class="['px-4 py-2 rounded shadow', t.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white']">
      {{ t.text }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from '../stores/toast'
const store = useToastStore()
</script>

<style scoped></style>
