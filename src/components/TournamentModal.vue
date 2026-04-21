<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal max-w-4xl w-full max-h-[90vh] overflow-auto">
      <div class="p-4 bg-white rounded-md shadow">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div v-if="t?.flyer" class="flex justify-center md:justify-start">
              <img :src="t.flyer" alt="flyer" class="w-full md:w-[66%] max-h-[45vh] object-contain rounded" />
            </div>
            <div>
              <h2 class="text-2xl font-semibold">{{ t?.title }}</h2>
              <div class="text-sm text-gray-600 mt-1">{{ t?.date }} {{ t?.time }} — {{ t?.mode }}</div>
              <div class="mt-3 text-sm">{{ t?.location }}</div>
              <div class="mt-2 text-sm font-medium">Startgeld: €{{ t?.fee }}</div>
              <p class="mt-4 text-gray-700 whitespace-pre-line">{{ t?.description }}</p>

              <ul class="text-sm space-y-2 mt-4">
                <li v-if="t?.user_name"><strong>Owner:</strong> {{ t.user_name }} ({{ t.user_email }})</li>
                <li v-else-if="t?.user_email"><strong>Owner:</strong> {{ t.user_email }}</li>
                <li v-if="t?.organizer"><strong>Organisator:</strong> {{ t.organizer }}</li>
                <li v-if="t?.registrationInfo"><strong>Anmeldung:</strong> {{ t.registrationInfo }}</li>
                <li v-if="t?.calendar_event_id"><strong>Calendar Event ID:</strong> {{ t.calendar_event_id }}</li>
              </ul>
            </div>
          </div>

        <div class="mt-4 text-right">
          <button @click="close" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Schließen</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Tournament } from '../stores/tournaments'
const props = defineProps<{ t?: Tournament }>()
const emit = defineEmits(['close'])
function close() { emit('close') }
</script>

<style scoped>
.modal-backdrop { @apply fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center }
.modal { }
.modal > .p-6 { }
</style>
