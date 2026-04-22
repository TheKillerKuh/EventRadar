<template>
  <div class="table-container">
    <table class="tournament-table">
      <thead>
        <tr>
          <th>Titel</th>
          <th>Datum</th>
          <th>Uhrzeit</th>
          <th>Modus</th>
          <th>Startgeld</th>
          <th>Veranstalter</th>
          <th>Ort</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in tournaments" :key="t.id" @click="open(t.id)">
          <td class="title-cell">{{ t.title }}</td>
          <td>{{ formatDate(t.date) }}</td>
          <td>{{ t.time || '—' }}</td>
          <td>{{ t.mode || '—' }}</td>
          <td>{{ t.fee ? '€' + t.fee : '—' }}</td>
          <td>{{ t.organizer || '—' }}</td>
          <td>{{ t.location || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import type { Tournament } from '../stores/tournaments'
const props = defineProps<{ tournaments: Tournament[] }>()
const emit = defineEmits(['open'])
function open(id: number) {
  emit('open', id)
}

// Format date to DD.MM.YYYY
function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  return d && m && y ? `${d}.${m}.${y}` : dateStr
}
</script>

<style scoped>
.table-container {
  width: 100%;
  overflow-x: auto;
}

.tournament-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tournament-table th {
  background-color: #f3f4f6;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.tournament-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  color: #6b7280;
  font-size: 0.875rem;
}

.tournament-table tbody tr {
  cursor: pointer;
  transition: background-color 0.2s;
}

.tournament-table tbody tr:hover {
  background-color: #f9fafb;
}

.title-cell {
  font-weight: 600;
  color: #111827;
}

@media (max-width: 1024px) {
  .tournament-table {
    font-size: 0.75rem;
  }

  .tournament-table th,
  .tournament-table td {
    padding: 0.75rem 0.5rem;
  }
}
</style>
