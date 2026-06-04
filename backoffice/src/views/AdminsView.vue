<template>
  <div class="admins-view">
    <div class="header-actions">
      <!-- Search & Filters -->
      <div class="filters-card glass-card">
        <div class="filter-group">
          <Search :size="18" class="search-icon" />
          <input
            v-model="filters.search"
            type="text"
            class="glass-input search-input"
            placeholder="Cari berdasarkan nama atau email..."
            @input="handleSearch"
          />
        </div>
        <div class="filter-group select-group">
          <select v-model="filters.role" class="glass-input status-select" @change="fetchAdmins(1)">
            <option value="">Semua Role</option>
            <option value="owner">Platform Owner</option>
            <option value="admin">Platform Admin</option>
            <option value="support">Platform Support</option>
          </select>
        </div>
      </div>

      <button class="btn btn-primary" @click="openCreateModal">
        <Plus :size="18" />
        Tambah Admin Baru
      </button>
    </div>

    <!-- Alert Messages -->
    <transition name="fade">
      <div v-if="alert.message" :class="['alert', `alert-${alert.type}`]">{{ alert.message }}</div>
    </transition>

    <!-- Table Card -->
    <div class="table-card glass-card" v-if="!loading">
      <div class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>Nama Admin</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Tgl Dibuat</th>
              <th style="text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="admin in admins.data" :key="admin.id" :class="{ 'current-user': admin.id === auth.admin?.id }">
              <td>
                <div style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                  {{ admin.name }}
                  <span v-if="admin.id === auth.admin?.id" class="me-pill">Anda</span>
                </div>
              </td>
              <td>{{ admin.email }}</td>
              <td>
                <span :class="['badge', `badge-${admin.role}`]">{{ formatRole(admin.role) }}</span>
              </td>
              <td>
                <span :class="['badge', admin.status === 'active' ? 'badge-active' : 'badge-inactive']">
                  {{ admin.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>{{ formatDate(admin.created_at) }}</td>
              <td>
                <div class="actions-cell" v-if="admin.id !== auth.admin?.id">
                  <button class="btn-icon" title="Edit Admin" @click="openEditModal(admin)">
                    <Edit :size="16" />
                  </button>
                  <button class="btn-icon text-danger" title="Hapus Admin" @click="confirmDelete(admin)">
                    <Trash2 :size="16" />
                  </button>
                </div>
                <div class="actions-cell" v-else>
                  <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No actions</span>
                </div>
              </td>
            </tr>
            <tr v-if="!admins.data?.length">
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                Tidak ada data Admin Platform ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="admins.last_page > 1">
        <button
          class="page-btn"
          :disabled="admins.current_page === 1"
          @click="fetchAdmins(admins.current_page - 1)"
        >
          <ChevronLeft :size="18" />
        </button>
        <button
          v-for="page in admins.last_page"
          :key="page"
          :class="['page-btn', { active: admins.current_page === page }]"
          @click="fetchAdmins(page)"
        >
          {{ page }}
        </button>
        <button
          class="page-btn"
          :disabled="admins.current_page === admins.last_page"
          @click="fetchAdmins(admins.current_page + 1)"
        >
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-else class="loading-state">
      <div class="spinner"></div>
      <p style="margin-top: 12px; color: var(--text-secondary);">Memuat daftar Admin Platform...</p>
    </div>

    <!-- Create Admin Modal -->
    <div class="modal-overlay" v-if="modals.create">
      <div class="modal-box" style="max-width: 500px;">
        <div class="modal-header">
          <h2>Tambah Admin Platform Baru</h2>
          <button class="btn-icon" @click="modals.create = false"><X :size="20" /></button>
        </div>
        <form @submit.prevent="handleCreateAdmin">
          <div v-if="modalError" class="alert alert-error">{{ modalError }}</div>

          <div class="form-group">
            <label>Nama Lengkap</label>
            <input v-model="createForm.name" type="text" class="glass-input" placeholder="Masukkan nama lengkap" required />
          </div>
          <div class="form-group">
            <label>Email Platform</label>
            <input v-model="createForm.email" type="email" class="glass-input" placeholder="nama@eventconnect.com" required />
          </div>
          <div class="form-group">
            <label>Password</label>
            <input v-model="createForm.password" type="password" class="glass-input" placeholder="Masukkan kata sandi baru" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Role</label>
              <select v-model="createForm.role" class="glass-input" required>
                <option value="admin">Platform Admin</option>
                <option value="owner">Platform Owner</option>
                <option value="support">Platform Support</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status Akun</label>
              <select v-model="createForm.status" class="glass-input" required>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px;">
            <button type="button" class="btn btn-glass" @click="modals.create = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="modalLoading">
              <Loader2 v-if="modalLoading" :size="16" class="spinner-icon" />
              Simpan Admin
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal-overlay" v-if="modals.edit">
      <div class="modal-box" style="max-width: 500px;">
        <div class="modal-header">
          <h2>Edit Admin Platform</h2>
          <button class="btn-icon" @click="modals.edit = false"><X :size="20" /></button>
        </div>
        <form @submit.prevent="handleUpdateAdmin">
          <div v-if="modalError" class="alert alert-error">{{ modalError }}</div>

          <div class="form-group">
            <label>Nama Lengkap</label>
            <input v-model="editForm.name" type="text" class="glass-input" required />
          </div>
          <div class="form-group">
            <label>Email Platform</label>
            <input v-model="editForm.email" type="email" class="glass-input" required />
          </div>
          <div class="form-group">
            <label>Ganti Password (Opsional)</label>
            <input v-model="editForm.password" type="password" class="glass-input" placeholder="Kosongkan jika tidak ingin mengubah kata sandi" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Role</label>
              <select v-model="editForm.role" class="glass-input" required>
                <option value="admin">Platform Admin</option>
                <option value="owner">Platform Owner</option>
                <option value="support">Platform Support</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status Akun</label>
              <select v-model="editForm.status" class="glass-input" required>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px;">
            <button type="button" class="btn btn-glass" @click="modals.edit = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="modalLoading">
              <Loader2 v-if="modalLoading" :size="16" class="spinner-icon" />
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Search, Plus, Edit, Trash2, X, ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const loading = ref(true)
const modalLoading = ref(false)
const modalError = ref('')

const filters = ref({ search: '', role: '' })
const admins = ref({ data: [], current_page: 1, last_page: 1 })

const alert = ref({ message: '', type: 'success' })
function showAlert(message, type = 'success') {
  alert.value = { message, type }
  setTimeout(() => {
    alert.value.message = ''
  }, 4000)
}

const modals = ref({ create: false, edit: false })
const createForm = ref({ name: '', email: '', password: '', role: 'admin', status: 'active' })
const editForm = ref({ id: null, name: '', email: '', password: '', role: 'admin', status: 'active' })

let searchTimeout = null
function handleSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchAdmins(1)
  }, 400)
}

async function fetchAdmins(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      search: filters.value.search,
      role: filters.value.role
    }
    const res = await api.get('/admins', { params })
    admins.value = res.data
  } catch (err) {
    showAlert('Gagal memuat data Admin Platform.', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  createForm.value = { name: '', email: '', password: '', role: 'admin', status: 'active' }
  modalError.value = ''
  modals.value.create = true
}

async function handleCreateAdmin() {
  modalLoading.value = true
  modalError.value = ''
  try {
    await api.post('/admins', createForm.value)
    modals.value.create = false
    showAlert('Admin Platform berhasil dibuat.')
    fetchAdmins(1)
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Gagal membuat admin baru.'
  } finally {
    modalLoading.value = false
  }
}

function openEditModal(admin) {
  editForm.value = { ...admin, password: '' }
  modalError.value = ''
  modals.value.edit = true
}

async function handleUpdateAdmin() {
  modalLoading.value = true
  modalError.value = ''
  try {
    await api.put(`/admins/${editForm.value.id}`, editForm.value)
    modals.value.edit = false
    showAlert('Admin Platform berhasil diupdate.')
    fetchAdmins(admins.value.current_page)
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Gagal mengupdate admin.'
  } finally {
    modalLoading.value = false
  }
}

async function confirmDelete(admin) {
  if (!confirm(`Apakah Anda yakin ingin menghapus admin platform "${admin.name}"? Tindakan ini permanen.`)) return

  try {
    const res = await api.delete(`/admins/${admin.id}`)
    showAlert(res.data.message || 'Admin Platform berhasil dihapus.')
    fetchAdmins(admins.value.current_page)
  } catch (err) {
    showAlert(err.response?.data?.message || 'Gagal menghapus admin.', 'error')
  }
}

const roleLabels = {
  owner: 'Platform Owner',
  admin: 'Platform Admin',
  support: 'Platform Support',
}

function formatRole(role) {
  return roleLabels[role] || role
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchAdmins(1)
})
</script>

<style scoped>
.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.filters-card {
  display: flex;
  padding: 8px 16px;
  gap: 16px;
  align-items: center;
  flex: 1;
  max-width: 600px;
}
.filter-group {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
}
.search-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
}
.search-input {
  padding-left: 38px;
}
.select-group {
  max-width: 200px;
}
.status-select {
  padding-top: 10px;
  padding-bottom: 10px;
}

.table-card {
  padding: 24px;
}
.current-user td {
  background: rgba(14, 165, 233, 0.05);
}
.me-pill {
  background: var(--primary);
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

.actions-cell {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}
.btn-icon {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--glass-border);
  color: var(--text-secondary);
  border-radius: 8px;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-icon:hover {
  background: rgba(255,255,255,0.12);
  color: var(--text-primary);
}
.text-danger { color: #f87171 !important; }
.text-danger:hover { background: rgba(239,68,68,0.2) !important; }

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}
</style>
