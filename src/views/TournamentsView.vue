<template>
  <section>
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h1>Turniere</h1>
      <div>
        <label><input type="checkbox" v-model="listView" /> Listenansicht</label>
      </div>
    </div>

    <div v-if="!listView" class="grid">
      <TournamentCard v-for="t in tournaments" :key="t.id" :t="t" @open="openModal" />
    </div>

    <div v-else class="list">
      <TournamentListItem v-for="t in tournaments" :key="t.id" :t="t" @open="openModal" />
    </div>

    <TournamentModal v-if="selected" :t="selected" @close="selected = null" />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useTournamentsStore } from '../stores/tournaments'
import TournamentCard from '../components/TournamentCard.vue'
import TournamentListItem from '../components/TournamentListItem.vue'
import TournamentModal from '../components/TournamentModal.vue'

const store = useTournamentsStore()
const listView = ref(false)
const selected = ref(null as any)

const tournaments = computed(() => store.list())

function openModal(id: number) {
  selected.value = store.getById(id) || null
}

onMounted(async () => {
  try {
    const res = await fetch('/api/get_tournaments.php')
    if (res.ok) {
      const data = await res.json()
      if (Array.isArray(data) && data.length) {
        // replace sample data with API data
        (store.tournaments as any) = data.map((d: any, i: number) => ({
          id: Number(d.id ?? i+1),
          title: d.title ?? 'Untitled',
          date: d.date ?? '',
          time: d.time ?? '',
          mode: d.mode ?? '',
          fee: Number(d.fee ?? 0),
          organizer: d.organizer ?? '',
          location: d.location ?? '',
          registrationInfo: d.registrationInfo ?? '',
          flyer: d.flyer ?? '',
          description: d.description ?? '',
        }))
      }
    }
  } catch (e) {
    // ignore fetch errors; local sample data remains
  }
})
</script>

<style scoped>
.grid { display:grid; grid-template-columns: repeat(2, minmax(320px, 1fr)); gap:1rem }
.list { display:flex;flex-direction:column }

@media (max-width: 640px) {
  .grid { grid-template-columns: 1fr }
}
</style>
