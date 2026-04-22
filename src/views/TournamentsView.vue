<template>
  <section>
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">Turniere</h1>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="mobile-filter-toggle">
        <button type="button" class="mobile-filter-btn" @click="showMobileFilters = !showMobileFilters">
          {{ showMobileFilters ? 'Filter ausblenden' : 'Filter anzeigen' }}
        </button>
      </div>
      <div class="filter-controls" :class="{ 'mobile-collapsed': !showMobileFilters }">
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
          <details class="filter-modes-details">
            <summary class="filter-select filter-mode-summary">
              {{
                filterModes.length
                  ? `${filterModes.length} Modus${filterModes.length !== 1 ? 'e' : ''}`
                  : 'Alle Modi'
              }}
            </summary>
            <div class="filter-modes-menu">
              <label v-for="mode in uniqueModes" :key="mode" class="mode-option">
                <input v-model="filterModes" type="checkbox" :value="mode" />
                <span>{{ mode }}</span>
              </label>
              <button type="button" class="mode-reset-btn" @click="filterModes = []">Zurücksetzen</button>
            </div>
          </details>
        </div>
        <div class="filter-date-wrapper">
          <input
            id="filter-date-from"
            ref="filterDateFromRef"
            v-model="filterDateFrom"
            type="text"
            placeholder="Von"
            class="filter-date-input"
            readonly
          />
        </div>
        <div class="filter-date-wrapper">
          <input
            id="filter-date-to"
            ref="filterDateToRef"
            v-model="filterDateTo"
            type="text"
            placeholder="Bis"
            class="filter-date-input"
            readonly
          />
        </div>
        <span v-if="dateFilterError" class="date-range-error">{{ dateFilterError }}</span>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { German } from 'flatpickr/dist/l10n/de.js'
import { useTournamentsStore } from '../stores/tournaments'
import TournamentCard from '../components/TournamentCard.vue'
import TournamentListItem from '../components/TournamentListItem.vue'
import TournamentModal from '../components/TournamentModal.vue'

const store = useTournamentsStore()
const listView = ref(false)
const showMobileFilters = ref(false)
const selected = ref<any>(null)
const filterText = ref('')
const filterModes = ref<string[]>([])
const filterDateFromRef = ref<HTMLInputElement | null>(null)
const filterDateToRef = ref<HTMLInputElement | null>(null)

const today = new Date()
today.setHours(0, 0, 0, 0)
const nextYear = new Date(today)
nextYear.setFullYear(nextYear.getFullYear() + 1)
const toIsoDate = (date: Date) => date.toISOString().slice(0, 10)

const filterDateFrom = ref(toIsoDate(today))
const filterDateTo = ref(toIsoDate(nextYear))

const tournaments = computed(() => store.list())
let fromPicker: flatpickr.Instance | null = null
let toPicker: flatpickr.Instance | null = null

function initDatePickers() {
  if (filterDateFromRef.value) {
    if (fromPicker) fromPicker.destroy()
    fromPicker = flatpickr(filterDateFromRef.value, {
      dateFormat: 'Y-m-d',
      altFormat: 'd.m.Y',
      altInput: true,
      altInputClass: 'filter-date-alt-input',
      locale: German,
      allowInput: true,
      defaultDate: filterDateFrom.value,
      onChange: (_selectedDates, dateStr) => {
        filterDateFrom.value = dateStr
      },
    })
  }

  if (filterDateToRef.value) {
    if (toPicker) toPicker.destroy()
    toPicker = flatpickr(filterDateToRef.value, {
      dateFormat: 'Y-m-d',
      altFormat: 'd.m.Y',
      altInput: true,
      altInputClass: 'filter-date-alt-input',
      locale: German,
      allowInput: true,
      defaultDate: filterDateTo.value,
      onChange: (_selectedDates, dateStr) => {
        filterDateTo.value = dateStr
      },
    })
  }
}

const dateRangeError = computed(() => {
  if (!filterDateFrom.value || !filterDateTo.value) return ''
  return filterDateFrom.value > filterDateTo.value
    ? 'Das Von-Datum darf nicht nach dem Bis-Datum liegen.'
    : ''
})
const dateFilterError = computed(() => dateRangeError.value)

const uniqueModes = computed(() => {
  const modes = new Set(tournaments.value.map((t) => t.mode).filter(Boolean))
  return Array.from(modes).sort()
})

const filteredTournaments = computed(() => {
  if (dateFilterError.value) return []

  return tournaments.value.filter((t) => {
    const matchesText =
      !filterText.value ||
      t.title.toLowerCase().includes(filterText.value.toLowerCase()) ||
      t.organizer.toLowerCase().includes(filterText.value.toLowerCase()) ||
      t.location.toLowerCase().includes(filterText.value.toLowerCase())
    const matchesMode = !filterModes.value.length || filterModes.value.includes(t.mode)
    const tournamentDate = t.date ? new Date(t.date) : null
    const fromDate = filterDateFrom.value ? new Date(filterDateFrom.value) : null
    const toDate = filterDateTo.value ? new Date(filterDateTo.value) : null
    const matchesFrom = !fromDate || (!!tournamentDate && tournamentDate >= fromDate)
    const matchesTo = !toDate || (!!tournamentDate && tournamentDate <= toDate)
    return matchesText && matchesMode && matchesFrom && matchesTo
  })
})

function openModal(id: number) {
  selected.value = store.getById(id) || null
}

onMounted(async () => {
  initDatePickers()

  try {
    const res = await fetch('/api/get_tournaments.php')
    if (res.ok) {
      const data = await res.json()
      if (Array.isArray(data) && data.length) {
        // Replace store contents with API data.
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
    // Ignore fetch errors; list stays empty when nothing is loaded.
  }
})

onUnmounted(() => {
  if (fromPicker) fromPicker.destroy()
  if (toPicker) toPicker.destroy()
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
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
}

.mobile-filter-toggle {
  display: none;
}

.mobile-filter-btn {
  width: 100%;
  height: 40px;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: #f9fafb;
  font-size: 0.875rem;
  color: #111827;
}

.date-range-error {
  font-size: 0.75rem;
  color: #dc2626;
  white-space: nowrap;
  align-self: center;
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
  height: 40px;
  padding: 0.625rem 0.75rem 0.625rem 2.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  box-sizing: border-box;
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

.filter-modes-details {
  position: relative;
}

.filter-mode-summary {
  list-style: none;
}

.filter-mode-summary::-webkit-details-marker {
  display: none;
}

.filter-modes-menu {
  position: absolute;
  top: calc(100% + 0.35rem);
  left: 0;
  width: 240px;
  max-height: 220px;
  overflow-y: auto;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  padding: 0.5rem;
  z-index: 30;
}

.mode-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem;
  font-size: 0.875rem;
  color: #374151;
}

.mode-reset-btn {
  margin-top: 0.35rem;
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #f9fafb;
  font-size: 0.75rem;
  padding: 0.35rem 0.5rem;
}

.filter-date-wrapper {
  display: flex;
  min-width: 180px;
}

.filter-date-input {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background-color: white;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}

.filter-date-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.filter-date-alt-input) {
  width: 100%;
  height: 40px;
  padding: 0.625rem 2rem 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background-color: white;
  box-sizing: border-box;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

:deep(.filter-date-alt-input[readonly]) {
  cursor: pointer;
}

:deep(.filter-date-alt-input:focus) {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select {
  width: 100%;
  height: 40px;
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

  .mobile-filter-toggle {
    display: block;
  }

  .filter-controls.mobile-collapsed {
    display: none;
  }

  .filter-right {
    justify-content: space-between;
  }

  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-input-wrapper,
  .filter-select-wrapper,
  .filter-date-wrapper {
    min-width: 100%;
    width: 100%;
  }

  .filter-modes-menu {
    width: 100%;
    position: static;
    margin-top: 0.35rem;
    box-shadow: none;
  }

  .date-range-error {
    align-self: flex-start;
    white-space: normal;
  }
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
