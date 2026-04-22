<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal">
      <button @click="close" class="close-btn">✕</button>

      <div class="modal-body">
        <div v-if="t?.flyer" class="flyer-container">
          <img :src="t.flyer" alt="flyer" class="flyer-image" />
        </div>

        <div class="details-list">
          <div class="detail-row">
            <span class="label">Titel</span>
            <span class="value title">{{ t?.title }}</span>
          </div>

          <div class="detail-row">
            <span class="label">Datum</span>
            <span class="value">{{ t?.date }} {{ t?.time || '' }}</span>
          </div>

          <div class="detail-row">
            <span class="label">Modus</span>
            <span class="value">{{ t?.mode }}</span>
          </div>

          <div class="detail-row">
            <span class="label">Startgeld</span>
            <span class="value">{{ t?.fee }} EUR</span>
          </div>

          <div class="detail-row">
            <span class="label">Ort</span>
            <span class="value">{{ t?.location }}</span>
          </div>

          <div v-if="t?.organizer" class="detail-row">
            <span class="label">Veranstalter</span>
            <span class="value">{{ t?.organizer }}</span>
          </div>

          <div v-if="t?.registrationInfo" class="detail-row">
            <span class="label">Anmeldung</span>
            <span class="value">{{ t?.registrationInfo }}</span>
          </div>

          <div v-if="t?.description" class="detail-row">
            <span class="label">Beschreibung</span>
            <span class="value">{{ t?.description }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Tournament } from '../stores/tournaments'
defineProps<{ t?: Tournament }>()
const emit = defineEmits(['close'])
function close() {
  emit('close')
}
</script>

<style scoped>
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
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  position: relative;
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
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
  z-index: 10;
}

.close-btn:hover {
  background: #e5e7eb;
  color: #111827;
}

.modal-body {
  display: flex;
  gap: 2rem;
  padding: 2rem;
  padding-right: 3.5rem;
}

.flyer-container {
  flex-shrink: 0;
  width: 300px;
}

.flyer-image {
  width: 100%;
  height: auto;
  border-radius: 0.75rem;
  object-fit: contain;
}

.details-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.value {
  font-size: 0.9375rem;
  color: #374151;
}

.value.title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

@media (max-width: 640px) {
  .modal-body {
    flex-direction: column;
    padding: 1.5rem;
    padding-right: 1.5rem;
  }

  .flyer-container {
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
  }
}
</style>
