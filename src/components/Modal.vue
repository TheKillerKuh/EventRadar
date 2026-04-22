<template>
  <div v-if="open" class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" @click="close"></div>
    <div class="modal-shell bg-white rounded shadow-lg z-10 max-w-5xl w-full p-4">
      <div class="flex justify-between items-center mb-3">
        <slot name="title"><h3 class="text-lg font-semibold">Modal</h3></slot>
        <button @click="close" class="text-gray-600 px-2">✕</button>
      </div>
      <div class="modal-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps({ modelValue: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue'])
const open = computed(() => props.modelValue)

function close() { emit('update:modelValue', false) }
</script>

<style scoped>
.modal-shell {
  max-height: calc(100vh - 2rem);
  overflow: hidden;
}

.modal-content {
  max-height: calc(100vh - 8rem);
  overflow-y: auto;
  overflow-x: hidden;
}

@media (max-width: 768px) {
  .modal-backdrop {
    align-items: flex-start;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }

  .modal-shell {
    width: calc(100vw - 1rem);
    max-height: calc(100vh - 1.5rem);
    padding: 0.875rem;
  }

  .modal-content {
    max-height: calc(100vh - 7rem);
  }
}
</style>
