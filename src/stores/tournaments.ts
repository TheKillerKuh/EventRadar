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
  const tournaments = ref<Tournament[]>([
    {
      id: 1,
      title: 'Beach Cup 2er Mixed',
      date: '2026-06-12',
      time: '10:00',
      mode: '2er Mixed',
      fee: 20,
      organizer: 'VC Example',
      location: 'Hamburg, Sporthalle A',
      registrationInfo: 'Anmeldung per Mail an info@example.com',
      flyer: '',
      description: 'Ein gemütliches 2er Mixed Turnier für Hobbyspielerinnen und Hobbyspieler.',
    },
    {
      id: 2,
      title: 'Sommerturnier 4er Mixed',
      date: '2026-07-03',
      time: '09:30',
      mode: '4er Mixed',
      fee: 30,
      organizer: 'VolleyClub',
      location: 'Berlin, Beachanlage West',
      registrationInfo: 'Online-Anmeldung auf der Veranstalterseite',
      flyer: '',
      description: 'Offenes Turnier mit Bratwurst und guter Laune.',
    },
  ])

  function list() {
    return tournaments.value
  }

  function getById(id: number) {
    return tournaments.value.find((t) => t.id === id)
  }

  return { tournaments, list, getById }
})
