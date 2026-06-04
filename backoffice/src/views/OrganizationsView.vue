<template>
  <div class="organizations-view">
    <div class="header-actions">
      <!-- Search & Filters -->
      <div class="filters-card glass-card">
        <div class="filter-group">
          <Search :size="18" class="search-icon" />
          <input
            v-model="filters.search"
            type="text"
            class="glass-input search-input"
            placeholder="Cari berdasarkan nama EO atau email..."
            @input="handleSearch"
          />
        </div>
        <div class="filter-group select-group">
          <select v-model="filters.status" class="glass-input status-select" @change="fetchOrganizations(1)">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="suspended">Ditangguhkan</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>
      </div>

      <button class="btn btn-primary" @click="openCreateModal">
        <Plus :size="18" />
        Tambah EO Baru
      </button>
    </div>

    <!-- Alert Messages -->
    <transition name="fade">
      <div v-if="alert.message" :class="['alert', `alert-${alert.type}`]">{{ alert.message }}</div>
    </transition>

    <!-- Table Card -->
    <div class="glass-card table-container" v-if="!loading">
      <div class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>Nama EO</th>
              <th>Plan</th>
              <th>Email Kontak</th>
              <th>Phone</th>
              <th>Kuota User</th>
              <th>Kuota Event</th>
              <th>Status</th>
              <th style="text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="org in organizations.data" :key="org.id">
              <td>
                <div style="font-weight: 600;">{{ org.name }}</div>
                <div style="font-size: 11px; color: var(--text-muted);">Slug: {{ org.slug }}</div>
              </td>
              <td>
                <span class="plan-badge-inline" :class="org.plan">
                  {{ formatPlanLabel(org.plan) }}
                </span>
              </td>
              <td>{{ org.email }}</td>
              <td>{{ org.phone || '-' }}</td>
              <td><strong>{{ org.max_users }}</strong> user</td>
              <td><strong>{{ org.max_events }}</strong> event</td>
              <td>
                <span :class="['badge', `badge-${org.status}`]">{{ formatStatus(org.status) }}</span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="btn-icon" title="Lihat Statistik" @click="showOrgDetails(org)">
                    <Eye :size="16" />
                  </button>
                  <button class="btn-icon" title="Edit EO" @click="openEditModal(org)">
                    <Edit :size="16" />
                  </button>
                  <button
                    v-if="org.status === 'active'"
                    class="btn-icon text-warning"
                    title="Suspend EO"
                    @click="updateOrgStatus(org, 'suspended')"
                  >
                    <Ban :size="16" />
                  </button>
                  <button
                    v-if="org.status === 'suspended'"
                    class="btn-icon text-success"
                    title="Aktifkan EO"
                    @click="updateOrgStatus(org, 'active')"
                  >
                    <CheckCircle2 :size="16" />
                  </button>
                  <button
                    v-if="org.status === 'pending'"
                    class="btn-icon text-success"
                    title="Setujui &amp; Aktifkan EO"
                    @click="updateOrgStatus(org, 'active')"
                  >
                    <CheckCircle2 :size="16" />
                  </button>
                  <button
                    v-if="org.slug !== 'default'"
                    class="btn-icon text-danger"
                    title="Nonaktifkan EO"
                    @click="confirmDeactivate(org)"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!organizations.data?.length">
              <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                Tidak ada data Event Organizer ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="organizations.last_page > 1">
        <button
          class="page-btn"
          :disabled="organizations.current_page === 1"
          @click="fetchOrganizations(organizations.current_page - 1)"
        >
          <ChevronLeft :size="18" />
        </button>
        <button
          v-for="page in organizations.last_page"
          :key="page"
          :class="['page-btn', { active: organizations.current_page === page }]"
          @click="fetchOrganizations(page)"
        >
          {{ page }}
        </button>
        <button
          class="page-btn"
          :disabled="organizations.current_page === organizations.last_page"
          @click="fetchOrganizations(organizations.current_page + 1)"
        >
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-else class="loading-state">
      <div class="spinner"></div>
      <p style="margin-top: 12px; color: var(--text-secondary);">Memuat daftar Event Organizer...</p>
    </div>

    <!-- Create EO Modal -->
    <div class="modal-overlay" v-if="modals.create">
      <div class="modal-box">
        <div class="modal-header">
          <h2>Tambah Event Organizer Baru</h2>
          <button class="btn-icon" @click="modals.create = false"><X :size="20" /></button>
        </div>
        <form @submit.prevent="handleCreateOrg">
          <div v-if="modalError" class="alert alert-error">{{ modalError }}</div>

          <h3 class="section-subtitle">Info Organisasi</h3>
          <div class="form-row">
            <div class="form-group">
              <label>Nama Event Organizer</label>
              <input v-model="createForm.name" type="text" class="glass-input" placeholder="Masukkan nama Event Organizer" required />
            </div>
            <div class="form-group">
              <label>Plan Layanan</label>
              <select v-model="createForm.plan" class="glass-input" @change="onPlanChange(createForm.plan, 'create')" required>
                <option value="free">Free Plan</option>
                <option value="business">Business Plan</option>
                <option value="enterprise">Enterprise Plan</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Kontak</label>
              <input v-model="createForm.email" type="email" class="glass-input" placeholder="kontak@perusahaan.com" required />
            </div>
            <div class="form-group">
              <label>No. Telepon</label>
              <input v-model="createForm.phone" type="text" class="glass-input" placeholder="081234567890" />
            </div>
          </div>
          <div class="form-group">
            <label>Alamat Kantor</label>
            <textarea v-model="createForm.address" class="glass-input" rows="2" placeholder="Masukkan alamat lengkap kantor..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Limit Kuota User</label>
              <input v-model.number="createForm.max_users" type="number" class="glass-input" min="1" required />
            </div>
            <div class="form-group">
              <label>Limit Kuota Event</label>
              <input v-model.number="createForm.max_events" type="number" class="glass-input" min="1" required />
            </div>
          </div>

          <h3 class="section-subtitle" style="margin-top: 24px;">Akun Superadmin Pertama</h3>
          <div class="form-group">
            <label>Nama Lengkap Admin</label>
            <input v-model="createForm.admin_name" type="text" class="glass-input" placeholder="Masukkan nama lengkap penanggung jawab" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Admin (Login)</label>
              <input v-model="createForm.admin_email" type="email" class="glass-input" placeholder="admin@perusahaan.com" required />
            </div>
            <div class="form-group">
              <label>Password Admin</label>
              <input v-model="createForm.admin_password" type="password" class="glass-input" placeholder="Masukkan kata sandi baru" required />
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px;">
            <button type="button" class="btn btn-glass" @click="modals.create = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="modalLoading">
              <Loader2 v-if="modalLoading" :size="16" class="spinner-icon" />
              Simpan EO & Admin
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit EO Modal -->
    <div class="modal-overlay" v-if="modals.edit">
      <div class="modal-box">
        <div class="modal-header">
          <h2>Edit Event Organizer</h2>
          <button class="btn-icon" @click="modals.edit = false"><X :size="20" /></button>
        </div>
        <form @submit.prevent="handleUpdateOrg">
          <div v-if="modalError" class="alert alert-error">{{ modalError }}</div>

          <div class="form-group">
            <label>Nama Event Organizer</label>
            <input v-model="editForm.name" type="text" class="glass-input" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Kontak</label>
              <input v-model="editForm.email" type="email" class="glass-input" required />
            </div>
            <div class="form-group">
              <label>No. Telepon</label>
              <input v-model="editForm.phone" type="text" class="glass-input" />
            </div>
          </div>
          <div class="form-group">
            <label>Alamat Kantor</label>
            <textarea v-model="editForm.address" class="glass-input" rows="2"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Limit Kuota User</label>
              <input v-model.number="editForm.max_users" type="number" class="glass-input" min="1" required />
            </div>
            <div class="form-group">
              <label>Limit Kuota Event</label>
              <input v-model.number="editForm.max_events" type="number" class="glass-input" min="1" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Plan Layanan</label>
              <select v-model="editForm.plan" class="glass-input" @change="onPlanChange(editForm.plan, 'edit')" required>
                <option value="free">Free Plan</option>
                <option value="business">Business Plan</option>
                <option value="enterprise">Enterprise Plan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status Langganan</label>
              <select v-model="editForm.status" class="glass-input" required>
                <option value="active">Aktif</option>
                <option value="suspended">Ditangguhkan</option>
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

    <!-- EO Details & Aggregated Stats Modal -->
    <div class="modal-overlay" v-if="modals.details">
      <div class="modal-box" style="max-width: 500px;">
        <div class="modal-header">
          <h2>Statistik Ringkasan EO</h2>
          <button class="btn-icon" @click="modals.details = false"><X :size="20" /></button>
        </div>
        <div v-if="detailLoading" style="text-align: center; padding: 30px;">
          <div class="spinner"></div>
        </div>
        <div v-else>
          <div class="detail-profile">
            <h3>{{ activeOrgDetails.organization.name }}</h3>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 12px;">Terdaftar sejak: {{ formatDate(activeOrgDetails.organization.created_at) }}</p>
            <div style="display: flex; gap: 8px; justify-content: center; align-items: center; margin-bottom: 16px;">
              <span :class="['badge', `badge-${activeOrgDetails.organization.status}`]">{{ formatStatus(activeOrgDetails.organization.status) }}</span>
              <span class="plan-badge-inline" :class="activeOrgDetails.organization.plan">
                {{ formatPlanLabel(activeOrgDetails.organization.plan) }}
              </span>
            </div>
          </div>

          <div class="stats-panel" style="margin-top: 24px;">
            <h4 class="panel-subtitle">Aktivitas Resource</h4>
            <div class="stat-row">
              <span>Penggunaan Akun User</span>
              <strong>{{ activeOrgDetails.stats.active_users }} aktif / {{ activeOrgDetails.stats.total_users }} total (Limit: {{ activeOrgDetails.organization.max_users }})</strong>
            </div>
            <div class="stat-row">
              <span>Jumlah Event Managed</span>
              <strong>{{ activeOrgDetails.stats.total_events }} total (Limit: {{ activeOrgDetails.organization.max_events }})</strong>
            </div>
            <div class="stat-row">
              <span>Event Berjalan / Aktif</span>
              <strong>{{ activeOrgDetails.stats.active_events }} event</strong>
            </div>
            <div class="stat-row">
              <span>Event Selesai</span>
              <strong>{{ activeOrgDetails.stats.completed_events }} event</strong>
            </div>
          </div>

          <div class="privacy-notice alert alert-glass" style="margin-top: 20px; font-size: 12px; color: var(--text-secondary); background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">
            <AlertTriangle :size="14" style="margin-right: 6px; display: inline; vertical-align: middle;" />
            Sesuai kebijakan privasi data, detail kegiatan (nama event, task, dilaporkan keuangan) tidak dapat diakses oleh admin platform.
          </div>

          <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button class="btn btn-glass" @click="modals.details = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Search, Plus, Edit, Eye, Trash2, X, ChevronLeft, ChevronRight, Ban, CheckCircle2, AlertTriangle, Loader2 } from 'lucide-vue-next'
import api from '../api/axios'

const loading = ref(true)
const modalLoading = ref(false)
const detailLoading = ref(false)
const modalError = ref('')

const filters = ref({ search: '', status: '' })
const organizations = ref({ data: [], current_page: 1, last_page: 1 })

const alert = ref({ message: '', type: 'success' })
function showAlert(message, type = 'success') {
  alert.value = { message, type }
  setTimeout(() => {
    alert.value.message = ''
  }, 4000)
}

const modals = ref({ create: false, edit: false, details: false })
const createForm = ref({
  name: '', email: '', phone: '', address: '', max_users: 5, max_events: 3, plan: 'free',
  admin_name: '', admin_email: '', admin_password: ''
})
const editForm = ref({ id: null, name: '', email: '', phone: '', address: '', plan: 'free', max_users: 5, max_events: 3, status: 'active' })
const activeOrgDetails = ref({ organization: {}, stats: {} })

function formatPlanLabel(plan) {
  const plans = { free: 'Free', business: 'Business', enterprise: 'Enterprise' }
  return plans[plan] || plan
}

function onPlanChange(planType, formType) {
  const planLimits = {
    free: { max_users: 5, max_events: 3 },
    business: { max_users: 25, max_events: 50 },
    enterprise: { max_users: 9999, max_events: 9999 }
  }
  const limits = planLimits[planType] || planLimits.free
  if (formType === 'create') {
    createForm.value.max_users = limits.max_users
    createForm.value.max_events = limits.max_events
  } else if (formType === 'edit') {
    editForm.value.max_users = limits.max_users
    editForm.value.max_events = limits.max_events
  }
}

let searchTimeout = null
function handleSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchOrganizations(1)
  }, 400)
}

async function fetchOrganizations(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      search: filters.value.search,
      status: filters.value.status
    }
    const res = await api.get('/organizations', { params })
    organizations.value = res.data
  } catch (err) {
    showAlert('Gagal memuat data Event Organizer.', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  createForm.value = {
    name: '', email: '', phone: '', address: '', max_users: 5, max_events: 3, plan: 'free',
    admin_name: '', admin_email: '', admin_password: ''
  }
  modalError.value = ''
  modals.value.create = true
}

async function handleCreateOrg() {
  modalLoading.value = true
  modalError.value = ''
  try {
    await api.post('/organizations', createForm.value)
    modals.value.create = false
    showAlert('Event Organizer dan Admin berhasil didaftarkan.')
    fetchOrganizations(1)
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Gagal membuat organisasi baru.'
  } finally {
    modalLoading.value = false
  }
}

function openEditModal(org) {
  editForm.value = { ...org }
  modalError.value = ''
  modals.value.edit = true
}

async function handleUpdateOrg() {
  modalLoading.value = true
  modalError.value = ''
  try {
    await api.put(`/organizations/${editForm.value.id}`, editForm.value)
    modals.value.edit = false
    showAlert('Event Organizer berhasil diupdate.')
    fetchOrganizations(organizations.value.current_page)
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Gagal mengupdate organisasi.'
  } finally {
    modalLoading.value = false
  }
}

async function showOrgDetails(org) {
  modals.value.details = true
  detailLoading.value = true
  try {
    const res = await api.get(`/organizations/${org.id}`)
    activeOrgDetails.value = res.data
  } catch (err) {
    showAlert('Gagal mengambil statistik organisasi.', 'error')
    modals.value.details = false
  } finally {
    detailLoading.value = false
  }
}

async function updateOrgStatus(org, status) {
  const statusLabels = { active: 'mengaktifkan', suspended: 'menangguhkan', inactive: 'menonaktifkan' }
  let label = statusLabels[status]
  if (status === 'active' && org.status === 'pending') {
    label = 'menyetujui dan mengaktifkan'
  }
  if (!confirm(`Apakah Anda yakin ingin ${label} EO "${org.name}"?`)) return

  try {
    await api.patch(`/organizations/${org.id}/status`, { status })
    showAlert(`Status EO "${org.name}" berhasil diubah menjadi ${formatStatus(status)}.`)
    fetchOrganizations(organizations.value.current_page)
  } catch (err) {
    showAlert('Gagal mengubah status organisasi.', 'error')
  }
}

async function confirmDeactivate(org) {
  if (!confirm(`PERINGATAN: Apakah Anda yakin ingin menonaktifkan EO "${org.name}"? Pengguna dari EO ini tidak akan bisa login lagi.`)) return

  try {
    await api.delete(`/organizations/${org.id}`)
    showAlert(`EO "${org.name}" berhasil dinonaktifkan.`)
    fetchOrganizations(organizations.value.current_page)
  } catch (err) {
    showAlert('Gagal menonaktifkan organisasi.', 'error')
  }
}

function formatStatus(status) {
  const labels = {
    active: 'Aktif',
    suspended: 'Ditangguhkan',
    inactive: 'Nonaktif',
    pending: 'Menunggu Persetujuan'
  }
  return labels[status] || status
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
  fetchOrganizations(1)
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

.table-container {
  padding: 0 0 20px;
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
.text-warning { color: #fbbf24 !important; }
.text-warning:hover { background: rgba(245,158,11,0.2) !important; }
.text-success { color: #34d399 !important; }
.text-success:hover { background: rgba(16,185,129,0.2) !important; }

.section-subtitle {
  font-size: 14px;
  font-weight: 600;
  color: var(--primary-light);
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding-bottom: 6px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

/* Detail Modal Styles */
.detail-profile {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.detail-profile h3 {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 4px;
}
.panel-subtitle {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-bottom: 12px;
}
.stat-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  font-size: 14px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.stat-row:last-child {
  border-bottom: none;
}
.stat-row span {
  color: var(--text-secondary);
}

.plan-badge-inline {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.1);
  display: inline-block;
  white-space: nowrap;
}
.plan-badge-inline.free {
  background: rgba(156, 163, 175, 0.2);
  color: #d1d5db;
}
.plan-badge-inline.business {
  background: rgba(14, 165, 233, 0.2);
  color: var(--primary-light);
  border-color: rgba(14, 165, 233, 0.4);
}
.plan-badge-inline.enterprise {
  background: rgba(217, 70, 239, 0.2);
  color: #f5d0fe;
  border-color: rgba(217, 70, 239, 0.4);
}
</style>
