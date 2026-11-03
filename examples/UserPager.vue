<template>
  <div class="pager">
    <table v-if="rows.length">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.IDpengguna">
          <td>{{ r.IDpengguna }}</td>
          <td>{{ r.nama }}</td>
          <td>{{ r.emel }}</td>
        </tr>
      </tbody>
    </table>
    <p v-else>No records.</p>

    <div class="controls">
      <button :disabled="page <= 1 || loading" @click="go(page - 1)">Prev</button>
      <span>Page {{ page }}<template v-if="info.total_page"> / {{ info.total_page }}</template></span>
      <button :disabled="!hasNext || loading" @click="go(page + 1)">Next</button>
      <label><input type="checkbox" v-model="blind" @change="go(1)" /> blind mode</label>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({ endpoint: { type: String, default: '/examples/paging-manual.php' } })

const page = ref(1)
const perPage = ref(10)
const blind = ref(false)
const rows = ref([])
const info = ref({ total_row: 0, total_page: 0, last_page: 0 })
const loading = ref(false)
const error = ref(null)

const hasNext = computed(() => {
  if (blind.value) return rows.value.length === perPage.value && info.value.last_page !== 1
  return page.value < (info.value.total_page || 1)
})

async function go(p) {
  if (p < 1) return
  loading.value = true
  error.value = null
  try {
    const url = `${props.endpoint}?page=${p}&per_page=${perPage.value}&blind=${blind.value}`
    const res = await fetch(url)
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    rows.value = data.rows || []
    page.value = data.page
    info.value = data.info || info.value
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(() => go(1))
</script>

<style scoped>
.pager { font-family: system-ui, sans-serif; }
table { border-collapse: collapse; width: 100%; margin-bottom: .5rem; }
td, th { border: 1px solid #ccc; padding: .25rem .5rem; }
.controls { display: flex; gap: .5rem; align-items: center; }
.err { color: #c00; }
</style>
