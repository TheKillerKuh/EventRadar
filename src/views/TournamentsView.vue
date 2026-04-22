<template>
  <section>
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">Turniere</h1>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="filter-controls">
        <div class="filter-input-wrapper">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="filter-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <input v-model="filterText" type="text" placeholder="Suchen..." class="filter-input" />
        </div>
        <div class="filter-select-wrapper">
          <select v-model="filterMode" class="filter-select">
            <option value="">Alle Modi</option>
            <option v-for="mode in uniqueModes" :key="mode" :value="mode">{{ mode }}</option>
          </select>
        </div>
        <span class="results-count"
          >{{ filteredTournaments.length }} Turnier{{
            filteredTournaments.length !== 1 ? 'e' : ''
          }}</span
        >
      </div>
      <div class="toggle-wrapper">
        <!-- Grid Icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="view-icon"
          :class="{ inactive: listView }"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
          />
        </svg>
        <!-- Toggle Button -->
        <div @click="listView = !listView" class="toggle-switch">
          <div class="toggle-slider" :class="{ 'list-mode': listView }"></div>
        </div>
        <!-- List Icon -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="view-icon"
          :class="{ inactive: !listView }"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          />
        </svg>
      </div>
    </div>

    <div v-if="!listView" class="grid">
      <TournamentCard v-for="t in filteredTournaments" :key="t.id" :t="t" @open="openModal" />
    </div>

    <div v-else class="list">
      <TournamentListItem :tournaments="filteredTournaments" @open="openModal" />
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
const selected = ref<any>(null)
const filterText = ref('')
const filterMode = ref('')

const tournaments = computed(() => store.list())

const uniqueModes = computed(() => {
  const modes = new Set(tournaments.value.map((t) => t.mode).filter(Boolean))
  return Array.from(modes).sort()
})

const filteredTournaments = computed(() => {
  return tournaments.value.filter((t) => {
    const matchesText =
      !filterText.value ||
      t.title.toLowerCase().includes(filterText.value.toLowerCase()) ||
      t.organizer.toLowerCase().includes(filterText.value.toLowerCase()) ||
      t.location.toLowerCase().includes(filterText.value.toLowerCase())
    const matchesMode = !filterMode.value || t.mode === filterMode.value
    return matchesText && matchesMode
  })
})

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
        store.tournaments = data.map((d: any, i: number) => ({
          id: Number(d.id ?? i + 1),
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
  } catch {
    // ignore fetch errors; local sample data remains
  }
})
</script>

<style scoped>
.page-header {
  margin-bottom: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0;
  color: #111827;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem 1.25rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.results-count {
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
  margin-left: auto;
  padding-right: 1rem;
}

.toggle-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #374151;
  transition: color 0.2s;
}

.view-icon.inactive {
  color: #9ca3af;
}

.toggle-switch {
  position: relative;
  width: 3.5rem;
  height: 2rem;
  background-color: #e5e7eb;
  border-radius: 9999px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.toggle-slider {
  position: absolute;
  top: 0.25rem;
  left: 0.25rem;
  width: 1.5rem;
  height: 1.5rem;
  background-color: white;
  border-radius: 9999px;
  transition: left 0.3s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-slider.list-mode {
  left: 1.75rem;
}

.filter-input-wrapper {
  position: relative;
  min-width: 250px;
}

.filter-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1.25rem;
  height: 1.25rem;
  color: #9ca3af;
}

.filter-input {
  width: 100%;
  padding: 0.625rem 0.75rem 0.625rem 2.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}

.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select-wrapper {
  min-width: 180px;
}

.filter-select {
  width: 100%;
  padding: 0.625rem 2rem 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background-color: white;
  cursor: pointer;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
.list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (max-width: 768px) {
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-right {
    justify-content: space-between;
  }

  .filter-controls {
    flex-direction: column;
  }

  .filter-input-wrapper {
    min-width: 100%;
  }
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
