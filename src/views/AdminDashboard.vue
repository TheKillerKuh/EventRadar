<template>
  <section class="admin-section">
    <div class="admin-header">
      <h1 class="text-2xl font-bold">Verwaltung</h1>
      <div class="user-info">
        <span class="user-name">{{ auth.user?.name }}</span>
        <span class="user-role">{{ auth.user?.role }}</span>
      </div>
    </div>

    <!-- Admin Navigation -->
    <div class="admin-nav">
      <button 
        :class="['nav-btn', { active: activeTab === 'tournaments' }]" 
        @click="activeTab = 'tournaments'"
      >
        🎯 Turniere
      </button>
      <button 
        :class="['nav-btn', { active: activeTab === 'users' }]" 
        @click="activeTab = 'users'"
      >
        👥 Benutzer
      </button>
    </div>

    <!-- Tournaments Tab -->
    <div v-if="activeTab === 'tournaments'" class="tab-content">
      <p class="text-sm text-gray-600 mb-4">Hier verwaltest du deine Turniere.</p>
      <MyTournaments ref="listRef" />
    </div>

    <!-- Users Tab -->
    <div v-if="activeTab === 'users'" class="tab-content">
      <UsersAdminView />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import MyTournaments from '../components/MyTournaments.vue'
import UsersAdminView from './UsersAdminView.vue'

const auth = useAuthStore()
const listRef = ref()
const activeTab = ref('tournaments')
</script>

<style scoped>
.admin-section {
  padding: 1.5rem;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-name {
  font-weight: 600;
  color: #111827;
}

.user-role {
  padding: 0.25rem 0.75rem;
  background: #dbeafe;
  color: #1d4ed8;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.admin-nav {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.nav-btn {
  padding: 0.75rem 1.25rem;
  border: none;
  background: none;
  color: #6b7280;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.nav-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.nav-btn.active {
  background: #3b82f6;
  color: white;
}

.tab-content {
  margin-top: 1rem;
}
</style>
