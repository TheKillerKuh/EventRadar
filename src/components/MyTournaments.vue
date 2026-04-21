<template>
  <section class="mt-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-medium">Meine Turniere</h3>
      <div>
        <button @click="openCreate" class="bg-green-600 text-white px-3 py-1 rounded">Turnier erstellen</button>
      </div>
    </div>

    <div class="mt-3">
      <!-- Admin controls: view mode and owner filter -->
      <div v-if="auth.user && (auth.user.role === 'admin' || auth.user.is_admin)" class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2">
          <input type="radio" v-model="viewMode" value="mine" />
          <span class="text-sm">Meine</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <input type="radio" v-model="viewMode" value="all" />
          <span class="text-sm">Alle</span>
        </label>

        <div v-if="viewMode === 'all'" class="ml-4">
          <label class="text-sm">Filter Owner:</label>
          <select v-model="ownerFilter" class="ml-2 border rounded px-2 py-1">
            <option value="">Alle</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-sm text-gray-600 mt-3">Lade...</div>
    <div v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</div>
    <div v-if="!loading && tournaments.length === 0" class="text-sm text-gray-600 mt-3">Keine Turniere gefunden.</div>

    <ul class="space-y-3 mt-3">
      <li v-for="t in tournaments" :key="t.id" class="p-3 border rounded bg-white">
        <div class="flex justify-between items-start gap-3">
          <div>
            <div class="font-semibold">{{ t.title }}</div>
            <div class="text-sm text-gray-600">{{ t.date }} {{ t.time || '' }} — {{ t.location }}</div>
            <div v-if="auth.user && (auth.user.role === 'admin' || auth.user.is_admin)" class="text-xs text-gray-500">Owner: {{ t.user_name || t.user_email || t.user_id || '—' }}</div>
          </div>
          <div class="flex flex-col items-end gap-2">
            <div class="text-sm text-gray-500">ID: {{ t.id }}</div>
            <div class="flex gap-2">
              <button @click="startEdit(t)" class="px-2 py-1 border rounded">Edit</button>
              <button @click="doDelete(t.id)" class="px-2 py-1 bg-red-600 text-white rounded">Delete</button>
            </div>
          </div>
        </div>
        <p class="mt-2 text-sm text-gray-700">{{ t.description }}</p>
      </li>
    </ul>

    

    <Modal v-model:modelValue="showModal">
      <template #title>
        <h3 class="text-lg font-semibold">Edit Turnier</h3>
      </template>
      <div v-if="editing">
        <TournamentForm :initial="editing" @updated="onUpdatedModal" @cancel="closeModal" />
      </div>
      <div v-else>
        <TournamentForm @created="onCreatedModal" @cancel="closeModal" />
      </div>
    </Modal>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import TournamentForm from './TournamentForm.vue';
import Modal from './Modal.vue';
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'
const toast = useToastStore()
const auth = useAuthStore()
const tournaments = ref<any[]>([]);
const loading = ref(false);
const error = ref('');
const editing = ref<any|null>(null);
const showModal = ref(false);
const viewMode = ref<'mine'|'all'>('mine');
const users = ref<any[]>([]);
const ownerFilter = ref<string | number | null>('');

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const isAdmin = !!(auth.user && (auth.user.role === 'admin' || auth.user.is_admin));
    let url = isAdmin && viewMode.value === 'all' ? '/api/get_tournaments.php' : '/api/get_my_tournaments.php';
    if (isAdmin && viewMode.value === 'all' && ownerFilter.value) {
      url += '?owner=' + encodeURIComponent(String(ownerFilter.value));
    }
    const res = await fetch(url, { credentials: 'include' });
    const j = await res.json();
    if (!res.ok) { error.value = j.error || 'Fehler'; }
    else { tournaments.value = Array.isArray(j) ? j : (j.tournaments || []); }
  } catch (e) { error.value = 'Netzwerkfehler'; }
  finally { loading.value = false; }
}

onMounted(() => load());

// reactively reload when viewMode or ownerFilter changes
watch(viewMode, () => {
  // reset owner filter when switching to 'mine'
  if (viewMode.value === 'mine') ownerFilter.value = '';
  load();
});

watch(ownerFilter, () => {
  load();
});

// load user list for owner filter when auth becomes admin
watch(() => auth.user, async (u) => {
  if (u && (u.role === 'admin' || u.is_admin)) {
    try {
      const r = await fetch('/api/get_users.php', { credentials: 'include' });
      const j = await r.json();
      if (r.ok) users.value = j;
    } catch (e) { /* ignore */ }
  }
});

function startEdit(t: any) {
  editing.value = { ...t };
  showModal.value = true;
}

function openCreate() {
  editing.value = null;
  showModal.value = true;
}

function cancelEdit() { editing.value = null; }

async function onUpdated() {
  editing.value = null;
  await load();
}

async function onUpdatedModal() {
  showModal.value = false;
  editing.value = null;
  toast.push('Turnier aktualisiert', 'success')
  await load();
}

async function onCreatedModal() {
  showModal.value = false;
  editing.value = null;
  toast.push('Turnier erstellt', 'success')
  await load();
}

function closeModal() { showModal.value = false; editing.value = null }

async function doDelete(id: number) {
  if (!confirm('Turnier wirklich löschen?')) return;
  try {
    const res = await fetch('/api/delete_tournament.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ id })
    });
    const j = await res.json();
    if (!res.ok) { alert(j.error || 'Löschen fehlgeschlagen'); }
    else { await load(); }
  } catch (e) { alert('Netzwerkfehler'); }
}

defineExpose({ refresh: load });
</script>

<style scoped></style>
