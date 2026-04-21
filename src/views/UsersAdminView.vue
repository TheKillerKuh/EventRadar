<template>
  <section>
    <h1>Benutzerverwaltung</h1>
    <p>Liste aller Benutzer (Passwort-Hashes werden nicht angezeigt).</p>

    <table v-if="users.length" border="1" cellpadding="6" cellspacing="0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Rolle</th>
          <th>Telefon</th>
          <th>Erstellt</th>
          <th>Aktualisiert</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.id }}</td>
          <td>{{ u.name }}</td>
          <td>{{ u.email }}</td>
          <td>{{ u.role }}</td>
          <td>{{ u.phone }}</td>
          <td>{{ u.created_at }}</td>
          <td>{{ u.updated_at }}</td>
        </tr>
      </tbody>
    </table>

    <div v-else>
      Keine Benutzer gefunden.
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const users = ref<any[]>([])

async function load() {
  try {
    const res = await fetch('/api/get_users.php')
    if (res.ok) users.value = await res.json()
  } catch (e) {
    // ignore
  }
}

onMounted(load)
</script>

<style scoped>
table { width: 100%; border-collapse: collapse }
th { text-align: left }
</style>
