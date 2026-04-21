<template>
  <div class="card" @click="open">
    <div class="flex items-start gap-4">
      <div v-if="t.flyer" class="w-56 h-44 flex-shrink-0 bg-gray-50 p-1 rounded">
        <img :src="t.flyer" alt="flyer" class="w-full h-full object-contain rounded" />
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-black">{{ t.title }}</h3>
        <div class="text-sm text-gray-600">{{ localizedDate }}</div>
        <div class="text-sm mt-2">{{ t.mode }} — €{{ t.fee }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ t.organizer }} · {{ t.location }}</div>
      </div>
      <div class="self-start">
        <button @click.stop="open" class="px-3 py-1 bg-black text-white rounded hover:opacity-90">Details</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Tournament } from '../stores/tournaments'
const props = defineProps<{ t: Tournament; onOpen?: (id: number) => void }>()
const emit = defineEmits(['open'])
function open() {
  emit('open', props.t.id)
  props.onOpen?.(props.t.id)
}
const localizedDate = computed(() => `${props.t.date} ${props.t.time}`)
</script>

<style scoped>
.card { @apply border border-gray-200 p-4 rounded-md cursor-pointer bg-white }
.card h3 { margin:0 }
</style>
