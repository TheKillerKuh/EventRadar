<template>
  <div class="mt-6 bg-white p-4 rounded border">
    <h3 class="font-medium mb-3">{{ isEdit ? 'Turnier ändern' : 'Neues Turnier erstellen' }}</h3>
    <form @submit.prevent="submit">
      <div v-if="isEdit && preview" class="flex flex-col md:flex-row gap-4">
        <div class="md:w-1/3 flex-shrink-0">
          <div class="border rounded p-2">
            <img :src="preview" alt="flyer" class="w-full h-64 object-contain" />
            <div class="mt-2 text-center">
              <button @click.prevent="removeFile" class="px-2 py-1 border rounded">Entfernen</button>
            </div>
          </div>
        </div>
        <div class="md:flex-1">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input v-model="form.title" placeholder="Titel" class="w-full border rounded px-2 py-1" required />
            <input v-model="form.date" type="date" class="w-full border rounded px-2 py-1" required />
            <input v-model="form.time" type="time" class="w-full border rounded px-2 py-1" />
            <input v-model="form.mode" placeholder="Modus" class="w-full border rounded px-2 py-1" />
            <input v-model="form.fee" placeholder="Startgebühr" class="w-full border rounded px-2 py-1" />
            <input v-model="form.organizer" placeholder="Organisator" class="w-full border rounded px-2 py-1" />
            <input v-model="form.location" placeholder="Ort" class="w-full border rounded px-2 py-1" />
            <input v-model="form.registrationInfo" placeholder="Anmeldung" class="w-full border rounded px-2 py-1" />
          </div>
          <textarea v-model="form.description" placeholder="Beschreibung" class="w-full border rounded px-2 py-1 mt-3"></textarea>
          <div class="mt-3">
            <label class="block mb-1 text-sm">Neues Bild hochladen (optional)</label>
            <div @drop.prevent="handleDrop" @dragover.prevent class="border-dashed border-2 border-gray-300 p-4 rounded text-center">
              <div class="text-sm text-gray-600">Ziehe ein Bild hierher oder <label class="text-blue-600 underline cursor-pointer"><input type="file" accept="image/*" class="hidden" @change="handleFileChange"> auswählen</label></div>
            </div>
            <div v-if="uploading" class="text-sm text-gray-600 mt-2">Lade hoch...</div>
          </div>
        </div>
      </div>
      <div v-else>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <input v-model="form.title" placeholder="Titel" class="w-full border rounded px-2 py-1" required />
          <input v-model="form.date" type="date" class="w-full border rounded px-2 py-1" required />
          <input v-model="form.time" type="time" class="w-full border rounded px-2 py-1" />
          <input v-model="form.mode" placeholder="Modus" class="w-full border rounded px-2 py-1" />
          <input v-model="form.fee" placeholder="Startgebühr" class="w-full border rounded px-2 py-1" />
          <input v-model="form.organizer" placeholder="Organisator" class="w-full border rounded px-2 py-1" />
          <input v-model="form.location" placeholder="Ort" class="w-full border rounded px-2 py-1" />
          <input v-model="form.registrationInfo" placeholder="Anmeldung" class="w-full border rounded px-2 py-1" />
        </div>
        <textarea v-model="form.description" placeholder="Beschreibung" class="w-full border rounded px-2 py-1 mt-3"></textarea>
        <div class="mt-3">
          <label class="block mb-1 text-sm">Flyer (optional)</label>
          <div @drop.prevent="handleDrop" @dragover.prevent class="border-dashed border-2 border-gray-300 p-4 rounded text-center">
            <div v-if="!preview">Ziehe ein Bild hierher oder <label class="text-blue-600 underline cursor-pointer"><input type="file" accept="image/*" class="hidden" @change="handleFileChange"> auswählen</label></div>
            <div v-else class="flex items-center gap-3 justify-center">
              <img :src="preview" class="h-24 object-contain" />
              <button @click.prevent="removeFile" class="px-2 py-1 border rounded">Entfernen</button>
            </div>
          </div>
          <div v-if="uploading" class="text-sm text-gray-600 mt-2">Lade hoch...</div>
        </div>
      </div>
      <div class="mt-3 flex items-center gap-3">
        <button class="bg-green-600 text-white px-4 py-2 rounded" :disabled="loading || uploading">{{ loading ? '...' : (isEdit ? 'Turnier ändern' : 'Turnier erstellen') }}</button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
const emit = defineEmits(['created','updated','cancel']);
import { useToastStore } from '../stores/toast'
const toast = useToastStore()
const props = defineProps({ initial: { type: Object, default: null } });

const isEdit = ref(false);
const form = ref({ title: '', date: '', time: '', mode: '', fee: '', organizer: '', location: '', registrationInfo: '', description: '', flyer: '', id: null });
const loading = ref(false);
const error = ref('');
const success = ref('');
const preview = ref<string | null>(null);
const uploading = ref(false);

async function uploadFile(file: File) {
  uploading.value = true;
  try {
    const fd = new FormData();
    fd.append('flyer', file);
    const res = await fetch('/api/upload_flyer.php', { method: 'POST', body: fd, credentials: 'include' });
    const j = await res.json();
    if (res.ok && j && j.ok) {
      form.value.flyer = j.file;
      preview.value = j.thumbnail || j.file;
    } else {
      error.value = (j && j.error) ? j.error : 'Upload failed';
      toast.push(error.value, 'error');
    }
  } catch (e) { error.value = 'Netzwerkfehler'; toast.push(error.value, 'error'); }
  finally { uploading.value = false; }
}

function handleDrop(e: DragEvent) {
  const dt = e.dataTransfer;
  if (!dt) return;
  const file = dt.files[0];
  if (file) uploadFile(file);
}

function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement;
  if (!input.files || !input.files[0]) return;
  uploadFile(input.files[0]);
}

function removeFile() { form.value.flyer = ''; preview.value = null; }

watch(() => props.initial, (v) => {
  if (v) {
    isEdit.value = true;
    form.value = { ...v };
    if (v.flyer) preview.value = v.flyer;
    } else {
    isEdit.value = false;
    form.value = { title: '', date: '', time: '', mode: '', fee: '', organizer: '', location: '', registrationInfo: '', description: '', flyer: '', id: null };
  }
}, { immediate: true });

function validate() {
  if (!form.value.title || !form.value.date) { error.value = 'Titel und Datum sind erforderlich'; return false; }
  if (form.value.fee && isNaN(Number(String(form.value.fee).replace(',', '.')))) { error.value = 'Gebühr muss eine Zahl sein'; return false; }
  return true;
}

async function submit() {
  error.value = '';
  success.value = '';
  if (!validate()) return;
  loading.value = true;
  try {
    const url = isEdit.value ? '/api/update_tournament.php' : '/api/create_tournament.php';
    const body = isEdit.value ? { tournament: form.value } : { tournament: { ...form.value } };
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(body)
    });
    const j = await res.json();
      if (!res.ok) { error.value = j.error || 'Fehler'; toast.push(error.value, 'error') }
    else {
      success.value = isEdit.value ? 'Geändert' : 'Erstellt';
      toast.push(success.value, 'success')
      emit(isEdit.value ? 'updated' : 'created');
      if (!isEdit.value) {
        form.value = { title: '', date: '', time: '', mode: '', fee: '', organizer: '', location: '', registrationInfo: '', description: '', flyer: '', id: null };
      }
    }
  } catch (e) { error.value = 'Netzwerkfehler'; }
  finally { loading.value = false; }
}

function cancel() {
  emit('cancel');
}
</script>

<style scoped></style>
