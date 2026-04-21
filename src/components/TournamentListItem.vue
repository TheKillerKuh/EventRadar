<template>
  <div class="list-item" @click="open">
    <img v-if="t.flyer" :src="t.flyer" alt="flyer" class="w-20 h-16 object-cover rounded" />
    <div class="meta flex-1">
      <h4 class="text-md font-medium">{{ t.title }}</h4>
      <div class="text-sm text-gray-600">{{ t.organizer }} · {{ t.location }}</div>
      <div class="text-sm text-gray-700 mt-1">{{ t.date }} {{ t.time }} — €{{ t.fee }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Tournament } from '../stores/tournaments'
const props = defineProps<{ t: Tournament }>()
const emit = defineEmits(['open'])
function open() { emit('open', props.t.id) }
</script>

<style scoped>
.list-item { @apply flex gap-4 p-3 border-b border-gray-100 cursor-pointer items-center }
.list-item img { object-fit: cover }
.meta h4 { margin:0 }
</style>
