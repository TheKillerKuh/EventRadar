<template>
  <section class="p-6">
    <h1 class="text-2xl font-bold">Verwaltung</h1>
    <p class="text-sm text-gray-600 mt-1">Hier verwaltest du deine Turniere.</p>

    <div v-if="!auth.user" class="mt-6">
      <p>Bitte melde dich im <a href="/login" class="text-blue-600 hover:underline">Login</a> an, um deine Turniere zu verwalten.</p>
    </div>

    <div v-else class="mt-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="font-semibold">Angemeldet als {{ auth.user.name }} ({{ auth.user.email }})</div>
          <div class="text-sm text-gray-600">Rolle: {{ auth.user.role }}</div>
        </div>
        <div class="flex items-center gap-3">
          <button @click="refreshList" class="px-3 py-1 border rounded">Aktualisieren</button>
        </div>
      </div>

      <MyTournaments ref="listRef" />

      <TournamentForm @created="onCreated" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import MyTournaments from '../components/MyTournaments.vue';
import TournamentForm from '../components/TournamentForm.vue';

const auth = useAuthStore();
const listRef = ref();

function onCreated() {
  listRef.value?.refresh?.();
}

function refreshList() {
  listRef.value?.refresh?.();
}
</script>

<style scoped></style>
