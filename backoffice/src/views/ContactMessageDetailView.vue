<template>
  <div class="message-detail-page">
    <!-- Back Navigation & Action Bar -->
    <div class="action-bar">
      <button class="btn btn-glass" @click="goBack">
        <ArrowLeft :size="16" style="margin-right: 6px;" />
        Kembali ke Inbox
      </button>
      <button v-if="message" class="btn btn-danger" @click="confirmDelete">
        <Trash2 :size="16" style="margin-right: 6px;" />
        Hapus Pesan
      </button>
    </div>

    <!-- Main Glass Card Container -->
    <div class="glass-card detail-card">
      <div v-if="loading" class="loading-state">
        <Loader2 class="spinner-icon" :size="28" />
        <span>Memuat detail pesan...</span>
      </div>

      <div v-else-if="!message" class="error-state">
        <AlertCircle :size="48" style="color: var(--danger); margin-bottom: 12px;" />
        <h3>Pesan Tidak Ditemukan</h3>
        <p>Pesan yang Anda cari tidak ada atau telah dihapus.</p>
        <button class="btn btn-glass" style="margin-top: 16px;" @click="goBack">Kembali ke Inbox</button>
      </div>

      <div v-else class="detail-layout">
        <!-- Message Header -->
        <div class="detail-header">
          <div class="avatar-gradient">
            {{ message.name ? message.name.charAt(0).toUpperCase() : 'M' }}
          </div>
          <div class="sender-info">
            <h3 class="sender-name">{{ message.name }}</h3>
            <a :href="'mailto:' + message.email" class="sender-email">
              <Mail :size="14" style="margin-right: 6px; display: inline-block; vertical-align: middle;" />
              {{ message.email }}
            </a>
          </div>
          <div class="date-info">
            <Calendar :size="14" style="margin-right: 6px; display: inline-block; vertical-align: middle;" />
            {{ formatDate(message.created_at) }}
          </div>
        </div>

        <div class="detail-body">
          <!-- Subject Section -->
          <div class="info-section">
            <span class="detail-label">Subjek</span>
            <h2 class="message-subject">{{ message.subject }}</h2>
          </div>

          <!-- Content Section -->
          <div class="info-section">
            <span class="detail-label">Isi Pesan / Keluhan</span>
            <div class="message-content-box">
              {{ message.message }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <Transition name="fade">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-box" style="max-width: 400px; text-align: center;">
          <AlertCircle :size="48" style="margin: 0 auto 16px; color: var(--danger);" />
          <h2 style="margin-bottom: 12px;">Hapus Pesan?</h2>
          <p style="color: var(--text-secondary); margin-bottom: 24px;">
            Pesan dari <strong>{{ message?.name }}</strong> dengan subjek "{{ message?.subject }}" akan dihapus secara permanen.
          </p>
          <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-glass" @click="showDeleteModal = false">Batal</button>
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
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Trash2, Mail, Calendar, Loader2, AlertCircle } from 'lucide-vue-next'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()
const message = ref(null)
const loading = ref(true)
const submitting = ref(false)
const showDeleteModal = ref(false)

function goBack() {
  router.push('/contact-messages')
}

async function fetchDetail() {
  loading.value = true
  try {
    const res = await api.get(`/contact-messages/${route.params.id}`)
    message.value = res.data
  } catch (err) {
    console.error('Gagal mengambil detail pesan:', err)
  } finally {
    loading.value = false
  }
}

function formatDate(isoString) {
  if (!isoString) return '-'
  return new Date(isoString).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function confirmDelete() {
  showDeleteModal.value = true
}

async function doDelete() {
  if (!message.value) return
  submitting.value = true
  try {
    await api.delete(`/contact-messages/${message.value.id}`)
    showDeleteModal.value = false
    router.push('/contact-messages')
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus pesan.')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchDetail()
})
</script>

<style scoped>
.message-detail-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-card {
  padding: 32px;
  min-height: 300px;
  display: flex;
  flex-direction: column;
}

.loading-state, .error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex: 1;
  padding: 60px 0;
  color: var(--text-secondary);
}

.detail-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--glass-border);
  flex-wrap: wrap;
}

.avatar-gradient {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 700;
  color: #fff;
  box-shadow: 0 4px 15px rgba(14, 165, 233, 0.2);
}

.sender-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 200px;
}

.sender-name {
  font-size: 20px;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.sender-email {
  color: var(--primary-light);
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
  display: inline-block;
  width: fit-content;
}

.sender-email:hover {
  color: #fff;
}

.date-info {
  color: var(--text-muted);
  font-size: 14px;
}

.detail-body {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-top: 28px;
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.message-subject {
  font-size: 22px;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.message-content-box {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--glass-border);
  padding: 20px 24px;
  border-radius: 14px;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 15px;
  color: var(--text-secondary);
  line-height: 1.7;
  min-height: 150px;
}

@media (max-width: 640px) {
  .detail-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .date-info {
    width: 100%;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 12px;
  }
}
</style>
