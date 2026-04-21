import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<{id:number, type:string, text:string}[]>([])
  let next = 1

  function push(text: string, type = 'info', ms = 4000) {
    const id = next++
    toasts.value.push({ id, type, text })
    setTimeout(() => { remove(id) }, ms)
  }

  function remove(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  return { toasts, push, remove }
})
