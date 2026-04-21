<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white rounded shadow-lg z-10 max-w-2xl w-full p-4">
      <div class="flex justify-between items-center mb-3">
        <slot name="title"><h3 class="text-lg font-semibold">Modal</h3></slot>
        <button @click="close" class="text-gray-600 px-2">✕</button>
      </div>
      <div>
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits, computed } from 'vue'
const props = defineProps({ modelValue: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue'])
const open = computed(() => props.modelValue)

function close() { emit('update:modelValue', false) }
</script>

<style scoped></style>
