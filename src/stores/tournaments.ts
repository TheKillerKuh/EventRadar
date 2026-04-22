import { defineStore } from 'pinia'
import { ref } from 'vue'

export type Tournament = {
  id: number
  title: string
  date: string // ISO date
  time: string
  mode: string
  fee: number
  organizer: string
  location: string
  registrationInfo: string
  flyer?: string
  description?: string
}

export const useTournamentsStore = defineStore('tournaments', () => {
  const tournaments = ref<Tournament[]>([])

  function list() {
    return tournaments.value
  }

  function getById(id: number) {
    return tournaments.value.find((t) => t.id === id)
  }

  return { tournaments, list, getById }
})
