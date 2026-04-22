<template>
  <section class="users-admin">
    <div v-if="!isAdmin" class="error">Nur Administratoren dürfen die Benutzerverwaltung sehen.</div>
    <template v-else>
    <div class="header">
      <div>
        <h1>Benutzerverwaltung</h1>
        <p class="subtitle">Alle Benutzer verwalten, neue anlegen oder Passwörter zurücksetzen.</p>
      </div>
      <button @click="openCreate" class="btn-primary">+ Neuen Benutzer anlegen</button>
    </div>

    <div v-if="loading" class="loading">Laden...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="table-container">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Rolle</th>
            <th>Erstellt</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td class="id-cell">{{ u.id }}</td>
            <td class="name-cell">{{ u.name }}</td>
            <td class="email-cell">{{ u.email }}</td>
            <td>
              <span :class="['role-badge', u.role]">{{ u.role }}</span>
            </td>
            <td class="date-cell">{{ formatDate(u.created_at) }}</td>
            <td class="actions-cell">
              <button @click="openEdit(u)" class="btn-edit" title="Bearbeiten">✏️</button>
              <button
                @click="openResetPassword(u)"
                class="btn-password"
                title="Passwort zurücksetzen"
              >
                🔑
              </button>
              <button @click="confirmDelete(u)" class="btn-delete" title="Löschen">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingUser ? 'Benutzer bearbeiten' : 'Neuen Benutzer anlegen' }}</h2>
          <button @click="closeModal" class="close-btn">✕</button>
        </div>
        <form @submit.prevent="saveUser" class="modal-body" @click.stop>
          <div class="form-group">
            <label>Name *</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Max Mustermann"
              @click.stop
            />
          </div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="form.email" type="email" placeholder="max@example.de" @click.stop />
          </div>
          <div class="form-group">
            <label>Rolle *</label>
            <select v-model="form.role" required @click.stop>
              <option value="user">Benutzer</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div v-if="!editingUser" class="form-group">
            <label>Passwort *</label>
            <input
              v-model="form.password"
              type="password"
              :required="!editingUser"
              placeholder="Mindestens 8 Zeichen"
              @click.stop
            />
          </div>
          <div class="form-group">
            <label>Telefon</label>
            <input v-model="form.phone" type="tel" placeholder="+49 123 456789" @click.stop />
          </div>
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Abbrechen</button>
            <button type="submit" class="btn-submit" :disabled="saving">
              {{ saving ? 'Speichern...' : 'Speichern' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reset Password Modal -->
    <div v-if="showPasswordModal" class="modal-backdrop" @mousedown.self="closePasswordModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Passwort zurücksetzen</h2>
          <button @click="closePasswordModal" class="close-btn">✕</button>
        </div>
        <form @submit.prevent="resetPassword" class="modal-body" @click.stop>
          <p class="reset-info">
            Neues Passwort für <strong>{{ resetUser?.name }}</strong> festlegen.
          </p>
          <div class="form-group">
            <label>Neues Passwort *</label>
            <input
              v-model="newPassword"
              type="password"
              required
              placeholder="Mindestens 8 Zeichen"
              @click.stop
            />
          </div>
          <div class="form-group">
            <label>Passwort bestätigen *</label>
            <input
              v-model="confirmPassword"
              type="password"
              required
              placeholder="Passwort wiederholen"
              @click.stop
            />
          </div>
          <div v-if="passwordError" class="form-error">{{ passwordError }}</div>
          <div class="modal-footer">
            <button type="button" @click="closePasswordModal" class="btn-cancel">Abbrechen</button>
            <button type="submit" class="btn-submit" :disabled="resetting">
              {{ resetting ? 'Zurücksetzen...' : 'Passwort setzen' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-backdrop" @mousedown.self="closeDeleteModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Benutzer löschen</h2>
          <button @click="closeDeleteModal" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <p class="delete-warning">
            Möchtest du den Benutzer <strong>{{ deleteUser?.name }}</strong> wirklich löschen?
          </p>
          <p class="delete-subwarning">Diese Aktion kann nicht rückgängig gemacht werden!</p>
          <div class="modal-footer">
            <button @click="closeDeleteModal" class="btn-cancel">Abbrechen</button>
            <button @click="doDelete" class="btn-danger" :disabled="deleting">
              {{ deleting ? 'Löschen...' : 'Ja, löschen' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'

const toast = useToastStore()
const auth = useAuthStore()
const isAdmin = computed(() => !!(auth.user && (auth.user.role === 'admin' || auth.user.is_admin)))

const users = ref<any[]>([])
const loading = ref(false)
const error = ref('')

// Modal states
const showModal = ref(false)
const showPasswordModal = ref(false)
const showDeleteModal = ref(false)

// Form states
const editingUser = ref<any>(null)
const resetUser = ref<any>(null)
const deleteUser = ref<any>(null)

const form = ref({
  name: '',
  email: '',
  role: 'user',
  password: '',
  phone: '',
})

const newPassword = ref('')
const confirmPassword = ref('')
const formError = ref('')
const passwordError = ref('')
const saving = ref(false)
const resetting = ref(false)
const deleting = ref(false)

async function load() {
  if (!isAdmin.value) {
    users.value = []
    loading.value = false
    error.value = ''
    return
  }
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/get_users.php', { credentials: 'include' })
    if (res.ok) {
      users.value = await res.json()
    } else {
      error.value = 'Fehler beim Laden der Benutzer'
    }
  } catch (e) {
    error.value = 'Netzwerkfehler'
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('de-DE')
}

function openCreate() {
  editingUser.value = null
  form.value = { name: '', email: '', role: 'user', password: '', phone: '' }
  formError.value = ''
  showModal.value = true
}

function openEdit(user: any) {
  editingUser.value = user
  form.value = {
    name: user.name || '',
    email: user.email || '',
    role: user.role || 'user',
    password: '',
    phone: user.phone || '',
  }
  formError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingUser.value = null
}

async function saveUser() {
  formError.value = ''

  saving.value = true
  try {
    const url = editingUser.value ? '/api/update_user.php' : '/api/create_user.php'
    const body: any = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      phone: form.value.phone,
    }

    if (editingUser.value) {
      body.id = editingUser.value.id
    }

    if (form.value.password) {
      body.password = form.value.password
    }

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(body),
    })

    const j = await res.json()
    if (res.ok && j.ok) {
      toast.push(editingUser.value ? 'Benutzer aktualisiert' : 'Benutzer erstellt', 'success')
      closeModal()
      await load()
    } else {
      formError.value = j.error || 'Fehler beim Speichern'
    }
  } catch (e) {
    formError.value = 'Netzwerkfehler'
  } finally {
    saving.value = false
  }
}

function openResetPassword(user: any) {
  resetUser.value = user
  newPassword.value = ''
  confirmPassword.value = ''
  passwordError.value = ''
  showPasswordModal.value = true
}

function closePasswordModal() {
  showPasswordModal.value = false
  resetUser.value = null
}

async function resetPassword() {
  passwordError.value = ''

  if (newPassword.value.length < 8) {
    passwordError.value = 'Passwort muss mindestens 8 Zeichen haben'
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    passwordError.value = 'Passwörter stimmen nicht überein'
    return
  }

  resetting.value = true
  try {
    const res = await fetch('/api/reset_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        user_id: resetUser.value.id,
        password: newPassword.value,
      }),
    })

    const j = await res.json()
    if (res.ok && j.ok) {
      toast.push('Passwort erfolgreich geändert', 'success')
      closePasswordModal()
    } else {
      passwordError.value = j.error || 'Fehler beim Zurücksetzen'
    }
  } catch (e) {
    passwordError.value = 'Netzwerkfehler'
  } finally {
    resetting.value = false
  }
}

function confirmDelete(user: any) {
  deleteUser.value = user
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  deleteUser.value = null
}

async function doDelete() {
  deleting.value = true
  try {
    const res = await fetch('/api/delete_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ id: deleteUser.value.id }),
    })

    const j = await res.json()
    if (res.ok && j.ok) {
      toast.push('Benutzer gelöscht', 'success')
      closeDeleteModal()
      await load()
    } else {
      toast.push(j.error || 'Fehler beim Löschen', 'error')
    }
  } catch (e) {
    toast.push('Netzwerkfehler', 'error')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  if (isAdmin.value) load()
})
</script>

<style scoped>
.users-admin {
  padding: 1.5rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.header h1 {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0;
  color: #111827;
}

.subtitle {
  color: #6b7280;
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
}

.btn-primary {
  background: #10b981;
  color: white;
  border: none;
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #059669;
}

.loading,
.error {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.error {
  color: #ef4444;
}

.table-container {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th {
  background: #f9fafb;
  padding: 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.users-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.875rem;
  color: #374151;
}

.users-table tbody tr:hover {
  background: #f9fafb;
}

.id-cell {
  color: #9ca3af;
  font-family: monospace;
}

.name-cell {
  font-weight: 500;
  color: #111827;
}

.email-cell {
  color: #4b5563;
}

.date-cell {
  color: #6b7280;
  font-size: 0.8125rem;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.role-badge.admin {
  background: #dbeafe;
  color: #1d4ed8;
}

.role-badge.user {
  background: #f3f4f6;
  color: #4b5563;
}

.actions-cell {
  white-space: nowrap;
}

.btn-edit,
.btn-password,
.btn-delete {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  margin-right: 0.5rem;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.btn-edit:hover,
.btn-password:hover {
  opacity: 1;
}

.btn-delete:hover {
  opacity: 1;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 50;
}

.modal {
  background: white;
  border-radius: 1rem;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  color: #111827;
}

.close-btn {
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  border: none;
  background: #f3f4f6;
  color: #6b7280;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #e5e7eb;
  color: #111827;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.375rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.reset-info {
  color: #374151;
  margin: 0 0 1rem 0;
}

.delete-warning {
  color: #111827;
  font-size: 1rem;
  margin: 0 0 0.5rem 0;
}

.delete-subwarning {
  color: #ef4444;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
}

.form-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #fef2f2;
  border-radius: 0.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-cancel {
  padding: 0.625rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
  color: #374151;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-cancel:hover {
  background: #f9fafb;
}

.btn-submit {
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 0.5rem;
  background: #3b82f6;
  color: white;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-submit:hover {
  background: #2563eb;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-danger {
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 0.5rem;
  background: #ef4444;
  color: white;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-danger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
  }

  .users-table {
    font-size: 0.8125rem;
  }

  .users-table th,
  .users-table td {
    padding: 0.75rem 0.5rem;
  }
}
</style>
