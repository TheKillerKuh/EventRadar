<template>
  <div class="card" @click="open">
    <div class="flex items-start gap-6">
      <div
        v-if="t.flyer && !hideFlyer"
        class="h-64 flex-shrink-0 bg-gray-50 p-2 rounded-lg shadow-sm"
      >
        <img :src="t.flyer" alt="flyer" class="w-full h-full object-contain rounded" />
      </div>
      <div class="flex-1">
        <h3 class="text-xl font-bold text-black mb-2">{{ t.title }}</h3>
        <div class="text-base text-gray-700 font-medium mb-3">{{ localizedDate }}</div>
        <div class="space-y-2">
          <div class="text-sm text-gray-600">{{ t.mode }} — €{{ t.fee }}</div>
          <div class="text-sm text-gray-700 font-medium">{{ t.organizer }}</div>
          <div class="text-sm text-gray-600">{{ t.location }}</div>
          <div
            v-if="t.registrationInfo"
            class="text-sm text-gray-600 mt-3 pt-3 border-t border-gray-200"
          >
            <span class="font-medium text-gray-700">Kontakt:</span> {{ t.registrationInfo }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Tournament } from '../stores/tournaments'
const props = defineProps<{ t: Tournament; onOpen?: (id: number) => void; hideFlyer?: boolean }>()
const emit = defineEmits(['open'])
function open() {
  emit('open', props.t.id)
  props.onOpen?.(props.t.id)
}
// Format date to DD.MM.YYYY
function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return d && m && y ? `${d}.${m}.${y}` : dateStr
}

const localizedDate = computed(() => `${formatDate(props.t.date)} ${props.t.time}`)
</script>

<style scoped>
.card {
  @apply border border-gray-200 p-6 rounded-lg cursor-pointer bg-white transition-all;
}
.card:hover {
  @apply shadow-lg border-gray-300;
}
.card h3 {
  margin: 0;
}
</style>
