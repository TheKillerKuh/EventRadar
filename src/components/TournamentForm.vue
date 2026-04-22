<template>
  <div class="bg-white p-4 rounded border">
    <h3 class="font-medium mb-3">{{ isEdit ? 'Turnier aendern' : 'Neues Turnier erstellen' }}</h3>
    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <input
          v-model="form.title"
          placeholder="Titel"
          class="w-full border rounded px-2 py-1"
          required
        />
        <div class="relative">
          <input
            ref="dateInput"
            v-model="form.date"
            placeholder="Datum waehlen"
            class="w-full border rounded px-2 py-1 cursor-pointer"
            required
            readonly
          />
        </div>
        <div class="relative">
          <input
            ref="timeInput"
            v-model="form.time"
            placeholder="Uhrzeit waehlen"
            class="w-full border rounded px-2 py-1 cursor-pointer"
            readonly
          />
        </div>
        <input v-model="form.mode" placeholder="Modus" class="w-full border rounded px-2 py-1" />
        <input
          v-model="form.fee"
          placeholder="Startgebuehr"
          class="w-full border rounded px-2 py-1"
        />
        <input
          v-model="form.organizer"
          placeholder="Organisator"
          class="w-full border rounded px-2 py-1"
        />
        <input v-model="form.location" placeholder="Ort" class="w-full border rounded px-2 py-1" />
        <input
          v-model="form.registrationInfo"
          placeholder="Anmeldung"
          class="w-full border rounded px-2 py-1"
        />

        <div v-if="isAdmin && users && users.length > 0" class="md:col-span-2">
          <label class="block text-sm text-gray-600 mb-1">Turnier-Besitzer</label>
          <select v-model="form.user_id" class="w-full border rounded px-2 py-1 bg-blue-50">
            <option :value="null">-- Kein Besitzer --</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
          <p v-if="form.user_id" class="text-xs text-green-600 mt-1">
            Besitzer wird auf "{{ selectedUserName }}" geaendert
          </p>
        </div>

        <div v-if="!isAdmin && form.user_id" class="md:col-span-2">
          <p class="text-sm text-gray-500">Besitzer: {{ ownerName }}</p>
        </div>
      </div>

      <textarea
        v-model="form.description"
        placeholder="Beschreibung"
        class="w-full border rounded px-2 py-1 mt-3"
      ></textarea>

      <div class="mt-3">
        <label class="block mb-1 text-sm">Flyer (optional)</label>
        <div v-if="preview" class="flex items-center gap-3 mb-2">
          <img :src="preview" class="h-24 object-contain border rounded" />
          <button @click.prevent="removeFile" class="px-2 py-1 border rounded hover:bg-gray-100">
            Entfernen
          </button>
        </div>
        <div
          v-else
          @drop.prevent="handleDrop"
          @dragover.prevent
          class="border-dashed border-2 border-gray-300 p-4 rounded text-center"
        >
          <div class="text-sm text-gray-600">
            Ziehe ein Bild hierher oder
            <label class="text-blue-600 underline cursor-pointer"
              ><input type="file" accept="image/*" class="hidden" @change="handleFileChange" />
              auswaehlen</label
            >
          </div>
        </div>
        <div v-if="uploading" class="text-sm text-gray-600 mt-2">Lade hoch...</div>
      </div>

      <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
      <div v-if="success" class="text-sm text-green-600 mt-2">{{ success }}</div>

      <div class="mt-4 flex gap-2">
        <button
          type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          :disabled="loading"
        >
          {{ loading ? 'Speichern...' : isEdit ? 'Aendern' : 'Erstellen' }}
        </button>
        <button type="button" @click="cancel" class="px-4 py-2 border rounded hover:bg-gray-100">
          Abbrechen
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import German from 'flatpickr/dist/l10n/de.js'
import { useToastStore } from '../stores/toast'

const props = defineProps({
  initial: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  isAdmin: { type: Boolean, default: false },
})

const emit = defineEmits(['created', 'updated', 'cancel'])
const toast = useToastStore()
const isEdit = ref(false)
const form = ref({
  title: '',
  date: '',
  time: '',
  mode: '',
  fee: '',
  organizer: '',
  location: '',
  registrationInfo: '',
  description: '',
  flyer: '',
  id: null,
  user_id: null as number | null,
})
const loading = ref(false)
const error = ref('')
const success = ref('')
const ownerName = computed(() => {
  const u = (props.users as any[]).find((x: any) => x.id === form.value.user_id)
  return u ? u.name : ''
})
const selectedUserName = computed(() => {
  const u = (props.users as any[]).find((x: any) => x.id === form.value.user_id)
  return u ? u.name + ' (' + u.email + ')' : ''
})
const preview = ref<string | null>(null)
const uploading = ref(false)
const dateInput = ref<HTMLInputElement | null>(null)
const timeInput = ref<HTMLInputElement | null>(null)

let datePicker: flatpickr.Instance | null = null
let timePicker: flatpickr.Instance | null = null

function initDatePicker() {
  if (dateInput.value) {
    if (datePicker) datePicker.destroy()
    datePicker = flatpickr(dateInput.value, {
      dateFormat: 'Y-m-d',
      altFormat: 'd.m.Y',
      altInput: true,
      locale: German,
      allowInput: true,
    })
  }
}

function initTimePicker() {
  if (timeInput.value) {
    if (timePicker) timePicker.destroy()
    timePicker = flatpickr(timeInput.value, {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i:S',
      altFormat: 'H:i',
      locale: German,
      time_24hr: true,
      allowInput: true,
    })
  }
}

watch(
  () => props.initial,
  (v) => {
    if (v) {
      isEdit.value = true
      form.value = { ...v, user_id: v.user_id ?? null }
      if (v.flyer) preview.value = v.flyer
      setTimeout(() => {
        initDatePicker()
        initTimePicker()
        if (v.date && datePicker) datePicker.setDate(v.date)
        if (v.time && timePicker) timePicker.setDate('2000-01-01 ' + v.time)
      }, 200)
    } else {
      isEdit.value = false
      form.value = {
        title: '',
        date: '',
        time: '',
        mode: '',
        fee: '',
        organizer: '',
        location: '',
        registrationInfo: '',
        description: '',
        flyer: '',
        id: null,
        user_id: null,
      }
      preview.value = null
      setTimeout(() => {
        initDatePicker()
        initTimePicker()
      }, 100)
    }
  },
  { immediate: true },
)

onMounted(() => {
  setTimeout(() => {
    initDatePicker()
    initTimePicker()
  }, 300)
})

onUnmounted(() => {
  if (datePicker) datePicker.destroy()
  if (timePicker) timePicker.destroy()
})

async function uploadFile(file: File) {
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('flyer', file)
    const res = await fetch('/api/upload_flyer.php', {
      method: 'POST',
      body: fd,
      credentials: 'include',
    })
    const j = await res.json()
    if (res.ok && j && j.ok) {
      form.value.flyer = j.file
      preview.value = j.thumbnail || j.file
    } else {
      error.value = j && j.error ? j.error : 'Upload failed'
      toast.push(error.value, 'error')
    }
  } catch (e) {
    error.value = 'Netzwerkfehler'
    toast.push(error.value, 'error')
  } finally {
    uploading.value = false
  }
}

function handleDrop(e: DragEvent) {
  const dt = e.dataTransfer
  if (!dt) return
  const file = dt.files[0]
  if (file) uploadFile(file)
}

function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files || !input.files[0]) return
  uploadFile(input.files[0])
}

function removeFile() {
  form.value.flyer = ''
  preview.value = null
}

function validate() {
  if (!form.value.title || !form.value.date) {
    error.value = 'Titel und Datum sind erforderlich'
    return false
  }
  if (form.value.fee && isNaN(Number(String(form.value.fee).replace(',', '.')))) {
    error.value = 'Gebuehr muss eine Zahl sein'
    return false
  }
  return true
}

async function submit() {
  error.value = ''
  success.value = ''
  if (!validate()) return
  loading.value = true
  try {
    const url = isEdit.value ? '/api/update_tournament.php' : '/api/create_tournament.php'
    const body = { tournament: { ...form.value } }
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(body),
    })
    const j = await res.json()
    if (!res.ok) {
      error.value = j.error || 'Fehler'
      toast.push(error.value, 'error')
    } else {
      success.value = isEdit.value ? 'Geaendert' : 'Erstellt'
      toast.push(success.value, 'success')
      emit(isEdit.value ? 'updated' : 'created')
      if (!isEdit.value) {
        form.value = {
          title: '',
          date: '',
          time: '',
          mode: '',
          fee: '',
          organizer: '',
          location: '',
          registrationInfo: '',
          description: '',
          flyer: '',
          id: null,
          user_id: null,
        }
        preview.value = null
      }
    }
  } catch (e) {
    error.value = 'Netzwerkfehler'
  } finally {
    loading.value = false
  }
}

function cancel() {
  emit('cancel')
}
</script>

<style scoped>
:deep(.flatpickr-calendar) {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
