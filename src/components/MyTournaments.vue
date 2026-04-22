<template>
  <section class="mt-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-medium">Meine Turniere</h3>
      <div>
        <button @click="openCreate" class="bg-green-600 text-white px-3 py-1 rounded">
          Turnier erstellen
        </button>
      </div>
    </div>

    <div class="mt-3 filter-bar">
      <div class="mobile-filter-toggle">
        <button type="button" class="mobile-filter-btn" @click="showMobileFilters = !showMobileFilters">
          {{ showMobileFilters ? 'Filter ausblenden' : 'Filter anzeigen' }}
        </button>
      </div>
      <div v-if="isAdmin" class="view-toggle">
        <label class="inline-flex items-center gap-2">
          <input type="radio" v-model="viewMode" value="mine" />
          <span class="text-sm">Meine Turniere</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <input type="radio" v-model="viewMode" value="all" />
          <span class="text-sm">Alle Turniere</span>
        </label>
      </div>

      <div class="filter-controls" :class="{ 'mobile-collapsed': !showMobileFilters }">
        <div class="filter-input-wrapper">
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

      <div class="refresh-wrap">
        <button @click="load" class="px-3 py-1 border rounded hover:bg-gray-100 transition-colors">
          Aktualisieren
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-sm text-gray-600 mt-3">Lade...</div>
    <div v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</div>
    <div v-if="!loading && filteredTournaments.length === 0" class="text-sm text-gray-600 mt-3">
      Keine Turniere gefunden.
    </div>

    <ul class="space-y-3 mt-3">
      <li v-for="t in filteredTournaments" :key="t.id" class="p-4 border rounded bg-white tournament-card">
        <div class="flex justify-between items-start gap-3">
          <div>
            <div class="font-semibold text-lg">{{ t.title || '-' }}</div>
            <div
              v-if="auth.user && (auth.user.role === 'admin' || auth.user.is_admin)"
              class="text-xs text-gray-500"
            >
              Ersteller: {{ t.user_name || t.user_email || t.user_id || '-' }}
            </div>
          </div>
          <div class="flex flex-col items-end gap-2">
            <div class="flex gap-2">
              <button @click="startEdit(t)" class="px-2 py-1 border rounded">Edit</button>
              <button @click="doDelete(t.id)" class="px-2 py-1 bg-red-600 text-white rounded">
                Delete
              </button>
            </div>
          </div>
        </div>

        <div class="tournament-details-text mt-1">
          <div class="detail-line">
            {{ formatDate(t.date) || '-' }}<span class="detail-sep">-</span>{{ t.time || '-' }}
            <span class="detail-gap">{{ t.location || '-' }}</span>
          </div>
          <div class="detail-line">Startgeld: {{ formatFee(t.fee) }}</div>
          <div class="detail-line">Kontaktdaten: {{ t.registrationInfo || '-' }}</div>
          <div class="detail-line">Beschreibung: {{ t.description || '-' }}</div>
        </div>
      </li>
    </ul>

    <Modal v-model:modelValue="showModal">
      <template #title>
        <h3 class="text-lg font-semibold">Turnier ändern</h3>
      </template>
      <div v-if="editing">
        <TournamentForm :initial="editing" :users="users" :isAdmin="isAdmin" @updated="onUpdatedModal" @cancel="closeModal" />
      </div>
      <div v-else>
        <TournamentForm :users="users" :isAdmin="isAdmin" @created="onCreatedModal" @cancel="closeModal" />
      </div>
    </Modal>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { German } from 'flatpickr/dist/l10n/de.js'
import TournamentForm from './TournamentForm.vue'
import Modal from './Modal.vue'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'

const toast = useToastStore()
const auth = useAuthStore()
const tournaments = ref<any[]>([])
const loading = ref(false)
const error = ref('')
const editing = ref<any | null>(null)
const showModal = ref(false)
const showMobileFilters = ref(false)
const viewMode = ref<'mine' | 'all'>('mine')
const users = ref<any[]>([])
const filterText = ref('')
const filterModes = ref<string[]>([])
const filterDateFromRef = ref<HTMLInputElement | null>(null)
const filterDateToRef = ref<HTMLInputElement | null>(null)
const isAdmin = computed(() => !!(auth.user && (auth.user.role === 'admin' || auth.user.is_admin)))
const toIsoDate = (date: Date) => date.toISOString().slice(0, 10)
const today = new Date()
today.setHours(0, 0, 0, 0)
const nextYear = new Date(today)
nextYear.setFullYear(nextYear.getFullYear() + 1)
const filterDateFrom = ref(toIsoDate(today))
const filterDateTo = ref(toIsoDate(nextYear))
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

const dateFilterError = computed(() => {
  if (!filterDateFrom.value || !filterDateTo.value) return ''
  return filterDateFrom.value > filterDateTo.value
    ? 'Das Von-Datum darf nicht nach dem Bis-Datum liegen.'
    : ''
})

const uniqueModes = computed(() => {
  const modes = new Set(tournaments.value.map((t) => t.mode).filter(Boolean))
  return Array.from(modes).sort()
})

const filteredTournaments = computed(() => {
  if (dateFilterError.value) return []
  return tournaments.value.filter((t) => {
    const matchesText =
      !filterText.value ||
      String(t.title || '')
        .toLowerCase()
        .includes(filterText.value.toLowerCase()) ||
      String(t.organizer || '')
        .toLowerCase()
        .includes(filterText.value.toLowerCase()) ||
      String(t.location || '')
        .toLowerCase()
        .includes(filterText.value.toLowerCase())
    const matchesMode = !filterModes.value.length || filterModes.value.includes(t.mode)
    const tournamentDate = t.date ? new Date(t.date) : null
    const fromDate = filterDateFrom.value ? new Date(filterDateFrom.value) : null
    const toDate = filterDateTo.value ? new Date(filterDateTo.value) : null
    const matchesFrom = !fromDate || (!!tournamentDate && tournamentDate >= fromDate)
    const matchesTo = !toDate || (!!tournamentDate && tournamentDate <= toDate)
    return matchesText && matchesMode && matchesFrom && matchesTo
  })
})

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return d && m && y ? d + '.' + m + '.' + y : dateStr
}

function formatFee(fee: unknown) {
  if (fee === null || fee === undefined || String(fee).trim() === '') return '-'
  const value = Number(fee)
  if (Number.isNaN(value)) return '-'
  return `${Math.round(value)} €`
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const adminCheck = isAdmin.value
    if (!adminCheck) viewMode.value = 'mine'
    const url =
      adminCheck && viewMode.value === 'all' ? '/api/get_tournaments.php' : '/api/get_my_tournaments.php'
    const res = await fetch(url, { credentials: 'include' })
    const j = await res.json()
    if (!res.ok) {
      error.value = j.error || 'Fehler'
    } else {
      tournaments.value = Array.isArray(j) ? j : (j.tournaments ?? [])
    }
  } catch (e) {
    error.value = 'Netzwerkfehler'
  } finally {
    loading.value = false
  }
}

async function loadUsersIfAdmin() {
  if (!isAdmin.value) {
    users.value = []
    return
  }
  try {
    const r = await fetch('/api/get_users.php', { credentials: 'include' })
    const j = await r.json()
    if (r.ok) users.value = j
  } catch (e) {
    /* ignore */
  }
}

onMounted(() => {
  initDatePickers()
  loadUsersIfAdmin()
  load()
})

watch(viewMode, () => {
  load()
})

watch(
  () => auth.user,
  async () => {
    if (isAdmin.value) {
      await loadUsersIfAdmin()
    } else {
      users.value = []
      viewMode.value = 'mine'
    }
    await load()
  },
)

onUnmounted(() => {
  if (fromPicker) fromPicker.destroy()
  if (toPicker) toPicker.destroy()
})

function startEdit(t: any) {
  editing.value = { ...t }
  showModal.value = true
}

function openCreate() {
  editing.value = null
  showModal.value = true
}

function cancelEdit() {
  editing.value = null
}

async function onUpdated() {
  editing.value = null
  await load()
}

async function onUpdatedModal() {
  showModal.value = false
  editing.value = null
  toast.push('Turnier aktualisiert', 'success')
  await load()
}

async function onCreatedModal() {
  showModal.value = false
  editing.value = null
  toast.push('Turnier erstellt', 'success')
  await load()
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function doDelete(id: number) {
  if (!confirm('Turnier wirklich loeschen?')) return
  try {
    const res = await fetch('/api/delete_tournament.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ id }),
    })
    const j = await res.json()
    if (!res.ok) {
      alert(j.error || 'Loeschen fehlgeschlagen')
    } else {
      await load()
    }
  } catch (e) {
    alert('Netzwerkfehler')
  }
}

defineExpose({ refresh: load })
</script>

<style scoped>
.filter-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.view-toggle {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
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

.refresh-wrap {
  margin-left: auto;
}

.filter-input-wrapper {
  min-width: 250px;
}

.filter-input {
  width: 100%;
  height: 40px;
  padding: 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  box-sizing: border-box;
}

.filter-select-wrapper,
.filter-date-wrapper {
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

.filter-date-input {
  width: 100%;
}

.filter-select {
  width: 100%;
  height: 40px;
  padding: 0.625rem 2rem 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background-color: white;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.filter-select:focus,
.filter-input:focus,
:deep(.filter-date-alt-input:focus) {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.date-range-error {
  font-size: 0.75rem;
  color: #dc2626;
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

.tournament-card {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.tournament-details-text {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.detail-line {
  font-size: 0.93rem;
  color: #1f2937;
  line-height: 1.4;
  white-space: pre-wrap;
}

.detail-sep {
  margin: 0 0.35rem;
}

.detail-gap {
  margin-left: 0.85rem;
}

@media (max-width: 1024px) {
  .refresh-wrap {
    margin-left: 0;
  }
}

@media (max-width: 768px) {
  .filter-bar,
  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .mobile-filter-toggle {
    display: block;
    width: 100%;
  }

  .filter-controls.mobile-collapsed {
    display: none;
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
  }
}
</style>
