<template>
  <div class="bg-white p-4 rounded border">
    <form @submit.prevent="submit">
      <div class="form-layout">
        <div class="form-main">
          <div class="form-fields">
            <div class="form-field">
              <label>Titel</label>
              <input v-model="form.title" placeholder="Titel eingeben" class="w-full form-input" required />
            </div>

            <div class="form-field">
              <label>Datum</label>
              <input
                ref="dateInput"
                v-model="form.date"
                placeholder="Datum waehlen"
                class="w-full form-input cursor-pointer"
                required
                readonly
              />
            </div>

            <div class="form-field">
              <label>Uhrzeit</label>
              <input
                ref="timeInput"
                v-model="form.time"
                placeholder="Uhrzeit waehlen"
                class="w-full form-input cursor-pointer"
                readonly
              />
            </div>

            <div class="form-field">
              <label>Modus</label>
              <input v-model="form.mode" class="w-full form-input" />
            </div>

            <div class="form-field">
              <label>Startgebühr</label>
              <div class="input-with-suffix">
                <input
                  v-model="form.fee"
                  class="w-full form-input form-input-with-suffix"
                  inputmode="numeric"
                  @input="sanitizeFee"
                />
                <span class="input-suffix">€</span>
              </div>
            </div>

            <div class="form-field">
              <label>Organisator</label>
              <input v-model="form.organizer" class="w-full form-input" />
            </div>

            <div class="form-field">
              <label>Ort</label>
              <input v-model="form.location" class="w-full form-input" />
            </div>

            <div class="form-field form-field-span-2">
              <label>Anmeldung</label>
              <input v-model="form.registrationInfo" class="w-full form-input" />
            </div>

            <div v-if="isAdmin" class="form-field form-field-span-2">
              <label>Ersteller</label>
              <select v-model="form.user_id" class="w-full form-input bg-blue-50" :disabled="!users || users.length === 0">
                <option :value="null">-- Ersteller waehlen --</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.email }})
                </option>
              </select>
              <p v-if="!users || users.length === 0" class="text-xs text-amber-600 mt-1">Keine Benutzer geladen.</p>
            </div>

            <div v-if="!isAdmin" class="form-field form-field-span-2">
              <label>Ersteller</label>
              <input :value="currentUserName || 'Aktueller Benutzer'" class="w-full form-input bg-gray-100 text-gray-600" readonly />
            </div>

            <div class="form-field form-field-span-2">
              <label>Beschreibung</label>
              <textarea v-model="form.description" class="w-full form-input form-textarea"></textarea>
            </div>
          </div>

          <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
          <div v-if="success" class="text-sm text-green-600 mt-2">{{ success }}</div>

          <div class="mt-4 flex gap-2 form-actions-desktop">
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              :disabled="loading"
            >
              {{ loading ? 'Speichern...' : isEdit ? 'Ändern' : 'Erstellen' }}
            </button>
            <button type="button" @click="cancel" class="px-4 py-2 border rounded hover:bg-gray-100">
              Abbrechen
            </button>
          </div>
        </div>

        <aside class="flyer-panel">
          <label class="block mb-2 text-sm">Flyer (optional)</label>
          <div v-if="preview" class="flyer-preview-wrap">
            <img :src="preview" class="flyer-preview-large" />
            <button @click.prevent="removeFile" class="px-2 py-1 border rounded hover:bg-gray-100 mt-2">
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

          <div class="mt-3 form-actions-mobile">
            <button
              type="submit"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              :disabled="loading"
            >
              {{ loading ? 'Speichern...' : isEdit ? 'Ändern' : 'Erstellen' }}
            </button>
            <button
              type="button"
              @click="cancel"
              class="w-full mt-2 px-4 py-2 border rounded hover:bg-gray-100"
            >
              Abbrechen
            </button>
          </div>
        </aside>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed, nextTick } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { German } from 'flatpickr/dist/l10n/de.js'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'

type TournamentFormData = {
  title: string
  date: string
  time: string
  mode: string
  fee: string
  organizer: string
  location: string
  registrationInfo: string
  description: string
  flyer: string
  id: number | null
  user_id: number | null
}

const props = withDefaults(
  defineProps<{
    initial?: Record<string, any> | null
    users?: Array<{ id: number; name: string; email: string }>
    isAdmin?: boolean
  }>(),
  {
    initial: null,
    users: () => [],
    isAdmin: false,
  },
)

const emit = defineEmits(['created', 'updated', 'cancel'])
const toast = useToastStore()
const auth = useAuthStore()
const isEdit = ref(false)
const createInitialForm = (): TournamentFormData => ({
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
})
const form = ref<TournamentFormData>(createInitialForm())
const loading = ref(false)
const error = ref('')
const success = ref('')
const currentUserName = computed(() => auth.user?.name || '')
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
      altInputClass: 'form-input form-input-enhanced',
      locale: German,
      allowInput: true,
      defaultDate: form.value.date || undefined,
    })
  }
}

function initTimePicker() {
  if (timeInput.value) {
    if (timePicker) timePicker.destroy()
    timePicker = flatpickr(timeInput.value, {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i',
      altFormat: 'H:i',
      altInputClass: 'form-input form-input-enhanced',
      locale: German,
      time_24hr: true,
      allowInput: true,
      defaultDate: form.value.time ? `2000-01-01 ${form.value.time}` : undefined,
    })
  }
}

async function initPickersStable() {
  await nextTick()
  initDatePicker()
  initTimePicker()
}

function defaultUserIdForCurrentRole() {
  if (props.isAdmin) return null
  return auth.user?.id ? Number(auth.user.id) : null
}

watch(
  () => props.initial,
  async (v: Record<string, any> | null) => {
    if (v) {
      isEdit.value = true
      form.value = {
        ...createInitialForm(),
        ...v,
        user_id: props.isAdmin ? (v.user_id ?? null) : defaultUserIdForCurrentRole(),
      }
      if (v.flyer) preview.value = v.flyer
      await initPickersStable()
    } else {
      isEdit.value = false
      form.value = {
        ...createInitialForm(),
        user_id: defaultUserIdForCurrentRole(),
      }
      preview.value = null
      await initPickersStable()
    }
  },
  { immediate: true },
)

onMounted(() => {
  initPickersStable()
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

function sanitizeFee() {
  form.value.fee = String(form.value.fee ?? '').replace(/[^\d]/g, '')
}

function validate() {
  if (!form.value.title || !form.value.date) {
    error.value = 'Titel und Datum sind erforderlich'
    return false
  }
  if (form.value.fee && !/^\d+$/.test(String(form.value.fee))) {
    error.value = 'Startgebühr muss eine ganze Zahl sein'
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
          ...createInitialForm(),
          user_id: defaultUserIdForCurrentRole(),
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
.form-layout {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
  gap: 1rem;
  align-items: start;
}

.flyer-panel {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 0.75rem;
  background: #fafafa;
}

.flyer-preview-wrap {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.flyer-preview-large {
  width: 100%;
  min-height: 260px;
  max-height: 420px;
  object-fit: contain;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #ffffff;
}

.form-actions-mobile {
  display: none;
}

.form-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-field:first-child,
.form-field-span-2 {
  grid-column: 1 / -1;
}

.form-field label {
  font-size: 0.875rem;
  color: #4b5563;
  font-weight: 500;
}

.form-input {
  min-height: 46px;
  width: 100%;
  padding: 0.7rem 0.8rem;
  font-size: 0.95rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  box-sizing: border-box;
  background-color: #fff;
  line-height: 1.2;
}

.input-with-suffix {
  position: relative;
}

.form-input-with-suffix {
  padding-right: 2.2rem;
}

.input-suffix {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-size: 0.95rem;
  pointer-events: none;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
  min-height: 180px;
  resize: vertical;
}

@media (max-width: 960px) {
  .form-layout {
    grid-template-columns: 1fr;
  }

  .form-fields {
    grid-template-columns: 1fr;
  }

  .form-field:first-child,
  .form-field-span-2 {
    grid-column: auto;
  }
}

:deep(.form-input-enhanced) {
  min-height: 46px;
  width: 100%;
  padding: 0.7rem 0.8rem;
  font-size: 0.95rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  box-sizing: border-box;
  background-color: #fff;
  line-height: 1.2;
  border: 1px solid #d1d5db !important;
  border-radius: 0.5rem !important;
  box-shadow: none !important;
}

:deep(.form-input-enhanced:focus) {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.form-input-enhanced[readonly]) {
  background-color: #fff;
  cursor: pointer;
}

@media (max-width: 768px) {
  .flyer-panel {
    padding: 0.65rem;
  }

  .flyer-preview-large {
    min-height: 140px;
    max-height: 220px;
  }

  .form-actions-desktop {
    display: none;
  }

  .form-actions-mobile {
    display: block;
  }
}

:deep(.flatpickr-calendar) {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
