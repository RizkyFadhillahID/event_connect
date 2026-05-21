<template>
  <div class="users-page">
    <!-- Header -->
    <div class="page-header glass-card">
      <div class="header-left">
        <h2>Manajemen Akun</h2>
        <p>Kelola semua akun pengguna sistem Event Connect</p>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <Plus :size="18" />
        Tambah User
      </button>
    </div>

    <!-- Filters -->
    <div class="filters glass-card">
      <input v-model="search" class="glass-input" placeholder="Cari nama atau email..." @input="fetchUsers(1)" style="max-width:320px"/>
      <select v-model="filterRole" class="glass-input" @change="fetchUsers(1)" style="max-width:220px">
        <option value="">Semua Role</option>
        <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="glass-card table-container">
      <div v-if="loading" class="loading-state"><Loader2 class="spinner-icon" :size="24" /><span>Memuat data...</span></div>
      <div v-else class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Telepon</th>
              <th>Role</th>
              <th>Status</th>
              <th>Bergabung</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="users.length === 0">
              <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada data pengguna</td>
            </tr>
            <tr v-for="(user, idx) in users" :key="user.id">
              <td style="color:var(--text-muted)">{{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar-sm">{{ user.name.charAt(0).toUpperCase() }}</div>
                  <span style="font-weight:500">{{ user.name }}</span>
                </div>
              </td>
              <td style="color:var(--text-secondary)">{{ user.email }}</td>
              <td style="color:var(--text-secondary)">{{ user.phone || '—' }}</td>
              <td><span class="role-pill">{{ roleLabel(user.role) }}</span></td>
              <td><span class="badge" :class="`badge-${user.status}`">{{ user.status }}</span></td>
              <td style="color:var(--text-muted);font-size:12px">{{ formatDate(user.created_at) }}</td>
              <td>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-glass btn-sm" @click="openEdit(user)" title="Edit">
                    <Edit :size="16" />
                  </button>
                  <button class="btn btn-danger btn-sm" @click="confirmDelete(user)" title="Hapus">
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
        <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchUsers(pagination.current_page - 1)">
          <ChevronLeft :size="18" />
        </button>
        <button
          v-for="p in pagination.last_page"
          :key="p"
          class="page-btn"
          :class="{ active: p === pagination.current_page }"
          @click="fetchUsers(p)"
        >{{ p }}</button>
        <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchUsers(pagination.current_page + 1)">
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-box">
          <div class="modal-header">
            <h2>{{ editId ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h2>
            <button class="btn btn-glass btn-sm" @click="closeModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <transition name="fade">
            <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          </transition>

          <form @submit.prevent="submitForm">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Lengkap *</label>
                <input v-model="form.name" class="glass-input" required placeholder="John Doe"/>
              </div>
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" class="glass-input" placeholder="08xxxxxxxxxx"/>
              </div>
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input v-model="form.email" type="email" class="glass-input" required placeholder="email@example.com"/>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Role *</label>
                <select v-model="form.role" class="glass-input" required>
                  <option value="">Pilih Role</option>
                  <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status *</label>
                <select v-model="form.status" class="glass-input" required>
                  <option value="active">Aktif</option>
                  <option value="inactive">Nonaktif</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Password {{ editId ? '(kosongkan jika tidak diubah)' : '*' }}</label>
              <input v-model="form.password" type="password" class="glass-input" :required="!editId" placeholder="Min 8 karakter"/>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
              <button type="button" class="btn btn-glass" @click="closeModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>{{ editId ? 'Simpan Perubahan' : 'Buat Akun' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirm -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width:400px;text-align:center">
          <div style="margin-bottom:16px">
            <AlertCircle :size="48" style="margin:0 auto; color: #f59e0b;" />
          </div>
          <h2 style="margin-bottom:12px">Hapus Pengguna?</h2>
          <p style="color:var(--text-secondary);margin-bottom:24px">Akun <strong>{{ deleteTarget.name }}</strong> akan dihapus permanen.</p>
          <div style="display:flex;gap:10px;justify-content:center">
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
import { Plus, Edit, Trash2, ChevronLeft, ChevronRight, X, AlertCircle, Loader2 } from 'lucide-vue-next'
import api from '../api/axios'

const users = ref([])
const loading = ref(true)
const search = ref('')
const filterRole = ref('')
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15 })

const showModal = ref(false)
const editId = ref(null)
const form = ref(defaultForm())
const formError = ref('')
const submitting = ref(false)
const deleteTarget = ref(null)

function defaultForm() {
  return { name: '', email: '', phone: '', role: '', status: 'active', password: '' }
}

const roles = [
  { value: 'superadmin', label: 'Super Admin' },
  { value: 'project_manager', label: 'Project Manager' },
  { value: 'staff', label: 'Staff / Personnel' },
]

function roleLabel(v) { return roles.find(r => r.value === v)?.label || v }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/users', { params: { page, search: search.value, role: filterRole.value } })
    users.value = res.data.data
    pagination.value = { current_page: res.data.current_page, last_page: res.data.last_page, per_page: res.data.per_page }
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editId.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}
function openEdit(user) {
  editId.value = user.id
  form.value = { name: user.name, email: user.email, phone: user.phone || '', role: user.role, status: user.status, password: '' }
  formError.value = ''
  showModal.value = true
}
function closeModal() { showModal.value = false }

async function submitForm() {
  formError.value = ''
  submitting.value = true
  try {
    if (editId.value) {
      await api.put(`/users/${editId.value}`, form.value)
    } else {
      await api.post('/users', form.value)
    }
    closeModal()
    fetchUsers(pagination.value.current_page)
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      formError.value = Object.values(errs).flat().join(' ')
    } else {
      formError.value = e.response?.data?.message || 'Terjadi kesalahan.'
    }
  } finally {
    submitting.value = false
  }
}

function confirmDelete(user) { deleteTarget.value = user }
async function doDelete() {
  submitting.value = true
  try {
    await api.delete(`/users/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetchUsers(pagination.value.current_page)
  } finally {
    submitting.value = false
  }
}

onMounted(() => fetchUsers())
</script>

<style scoped>
.users-page { display: flex; flex-direction: column; gap: 16px; }
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px;
}
.header-left h2 { font-size: 18px; font-weight: 600; }
.header-left p { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

.filters { display: flex; gap: 12px; padding: 16px 20px; flex-wrap: wrap; align-items: center; }
.table-container { padding: 0 0 20px; }

.avatar-sm {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.role-pill {
  display: inline-block;
  padding: 3px 10px;
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.3);
  border-radius: 20px;
  font-size: 11px;
  color: #a5b4fc;
  white-space: nowrap;
}
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 48px; color: var(--text-secondary); }
</style>
