<template>
  <div class="card" @click="open">
    <div class="card-row">
      <div
        v-if="t.flyer && !hideFlyer"
        class="flyer-box flex-shrink-0 bg-gray-50 p-2 rounded-lg shadow-sm"
      >
        <img :src="t.flyer" alt="flyer" class="w-full h-full object-contain rounded" />
      </div>
      <div class="flex-1 min-w-0">
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
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  box-sizing: border-box;
}
.card:hover {
  @apply shadow-lg border-gray-300;
}

.card-row {
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  width: 100%;
  min-width: 0;
}
.card h3 {
  margin: 0;
  overflow-wrap: anywhere;
}

.card .text-sm,
.card .text-base {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.flyer-box {
  width: 16rem;
  height: 16rem;
}

@media (max-width: 768px) {
  .card {
    padding: 1rem;
  }

  .card-row {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
  }

  .flyer-box {
    width: min(55vw, 11rem);
    height: min(55vw, 11rem);
    align-self: center;
  }
}
</style>
