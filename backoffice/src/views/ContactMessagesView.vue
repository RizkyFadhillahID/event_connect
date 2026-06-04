<template>
  <div class="contact-messages-page">
    <div class="glass-card table-container">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <Loader2 class="spinner-icon" :size="24" />
        <span>Memuat data inbox pesan...</span>
      </div>

      <!-- Main Content -->
      <div v-else>
        <div class="table-wrap">
          <table class="glass-table">
            <thead>
              <tr>
                <th style="width: 180px;">Nama &amp; Email Pengirim</th>
                <th style="width: 200px;">Subjek</th>
                <th>Pesan Pertanyaan / Keluhan</th>
                <th style="width: 150px;">Tanggal Dikirim</th>
                <th style="width: 80px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="messages.length === 0">
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">
                  Inbox kosong. Belum ada pesan contact us yang masuk.
                </td>
              </tr>
              <tr v-for="msg in messages" :key="msg.id">
                <td>
                  <div class="sender-cell">
                    <span class="sender-name">{{ msg.name }}</span>
                    <span class="sender-email">{{ msg.email }}</span>
                  </div>
                </td>
                <td>
                  <div class="subject-cell" :title="msg.subject">
                    {{ msg.subject }}
                  </div>
                </td>
                <td>
                  <div class="message-cell" :title="msg.message">
                    {{ msg.message }}
                  </div>
                </td>
                <td style="font-size: 13px; color: var(--text-secondary);">
                  {{ formatDate(msg.created_at) }}
                </td>
                <td>
                  <div style="display: flex; gap: 6px;">
                    <button class="btn btn-glass btn-sm" @click="showDetails(msg)" title="Lihat Detail Pesan">
                      <Eye :size="16" style="color: var(--primary-light);" />
                    </button>
                    <button class="btn btn-danger btn-sm" @click="confirmDelete(msg)" title="Hapus Pesan">
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination">
          <button 
            class="page-btn" 
            :disabled="pagination.current_page === 1" 
            @click="fetchMessages(pagination.current_page - 1)"
          >
            <ChevronLeft :size="18" />
          </button>
          <button 
            v-for="p in pagination.last_page" 
            :key="p" 
            class="page-btn" 
            :class="{ active: p === pagination.current_page }" 
            @click="fetchMessages(p)"
          >
            {{ p }}
          </button>
          <button 
            class="page-btn" 
            :disabled="pagination.current_page === pagination.last_page" 
            @click="fetchMessages(pagination.current_page + 1)"
          >
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width: 400px; text-align: center;">
          <AlertCircle :size="48" style="margin: 0 auto 16px; color: var(--danger);" />
          <h2 style="margin-bottom: 12px;">Hapus Pesan?</h2>
          <p style="color: var(--text-secondary); margin-bottom: 24px;">
            Pesan dari <strong>{{ deleteTarget.name }}</strong> dengan subjek "{{ deleteTarget.subject }}" akan dihapus permanen.
          </p>
          <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-glass" @click="deleteTarget = null">Batal</button>
            <button class="btn btn-danger" @click="doDelete" :disabled="submitting">
              <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
              <span v-else>Ya, Hapus</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Loader2, Trash2, ChevronLeft, ChevronRight, AlertCircle, Eye } from 'lucide-vue-next'
import api from '../api/axios'

const router = useRouter()
const messages = ref([])
const loading = ref(true)
const submitting = ref(false)
const deleteTarget = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10 })

function showDetails(msg) {
  router.push(`/contact-messages/${msg.id}`)
}

async function fetchMessages(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/contact-messages', { params: { page } })
    messages.value = res.data.data
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      per_page: res.data.per_page
    }
  } catch (err) {
    console.error('Gagal mengambil data inbox pesan:', err)
  } finally {
    loading.value = false
  }
}

function formatDate(isoString) {
  if (!isoString) return '-'
  return new Date(isoString).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function confirmDelete(msg) {
  deleteTarget.value = msg
}

async function doDelete() {
  if (!deleteTarget.value) return
  submitting.value = true
  try {
    await api.delete(`/contact-messages/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchMessages(pagination.value.current_page)
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus pesan.')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchMessages()
})
</script>

<style scoped>
.contact-messages-page {
  display: flex;
  flex-direction: column;
}
.table-container {
  padding: 0 0 20px;
}
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px;
  color: var(--text-secondary);
}
.sender-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.sender-name {
  font-weight: 600;
  color: #fff;
}
.sender-email {
  font-size: 11px;
  color: var(--text-muted);
}
.subject-cell {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
.message-cell {
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-height: 1.5;
  font-size: 13.5px;
  max-height: 40px;
  max-width: 500px;
}
</style>
