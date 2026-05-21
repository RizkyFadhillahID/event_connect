<template>
  <div class="events-page">
    <!-- Header -->
    <div class="page-header glass-card">
      <div class="header-left">
        <h2>Manajemen Event</h2>
        <p>Kelola semua event dan tim personel</p>
      </div>
      <button v-if="auth.canManageEvents" class="btn btn-primary" @click="openCreate">
        <Plus :size="18" />
        Tambah Event
      </button>
    </div>

    <!-- Filters -->
    <div class="filters glass-card">
      <input v-model="search" class="glass-input" placeholder="Cari nama event atau lokasi..." @input="fetchEvents(1)" style="max-width:320px"/>
      <select v-model="filterStatus" class="glass-input" @change="fetchEvents(1)" style="max-width:180px">
        <option value="">Semua Status</option>
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="glass-card table-container">
      <div v-if="loading" class="loading-state">
        <Loader2 :size="24" class="spinner-icon" />
        <span>Memuat data...</span>
      </div>
      <div v-else class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama Event</th>
              <th>Lokasi</th>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Personel</th>
              <th>Task</th>
              <th>Budget</th>
              <th v-if="auth.canManageEvents">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="events.length === 0">
              <td :colspan="auth.canManageEvents ? 10 : 9" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada data event</td>
            </tr>
            <tr v-for="(ev, idx) in events" :key="ev.id" class="clickable-row" @click="openDetail(ev)">
              <td style="color:var(--text-muted)">{{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}</td>
              <td>
                <div class="event-cell">
                  <div class="event-icon" :class="`icon-${ev.status}`">
                    <Calendar :size="18" />
                  </div>
                  <div>
                    <div style="font-weight:500">{{ ev.name }}</div>
                    <div style="font-size:12px;color:var(--text-muted)">{{ ev.creator?.name || '—' }}</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--text-secondary);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ ev.location }}</td>
              <td style="white-space:nowrap">
                <div style="font-size:13px">{{ formatDate(ev.start_date) }}</div>
                <div style="font-size:11px;color:var(--text-muted)" v-if="ev.end_date !== ev.start_date">s/d {{ formatDate(ev.end_date) }}</div>
              </td>
              <td>
                <span v-if="ev.category" style="font-size:12px;color:var(--text-secondary)">{{ ev.category }}</span>
                <span v-else style="color:var(--text-muted)">—</span>
              </td>
              <td><span class="badge" :class="`badge-${ev.status}`">{{ statusLabel(ev.status) }}</span></td>
              <td>
                <div v-if="ev.personnel?.length" class="personnel-avatars">
                  <div v-for="p in ev.personnel.slice(0,4)" :key="p.id" class="p-avatar" :title="p.name">{{ p.name.charAt(0) }}</div>
                  <div v-if="ev.personnel.length > 4" class="p-avatar more">+{{ ev.personnel.length - 4 }}</div>
                </div>
                <span v-else style="color:var(--text-muted);font-size:12px">—</span>
              </td>
              <td>
                <RouterLink
                  :to="{ path: '/tasks', query: { event_id: ev.id } }"
                  class="task-count-badge"
                  @click.stop
                  :title="'Lihat tasks event ini'"
                >
                  <CheckSquare :size="13" />
                  {{ eventTaskCounts[ev.id] ?? '…' }}
                </RouterLink>
              </td>
              <td style="white-space:nowrap">
                <span v-if="ev.budget" style="font-size:13px">{{ formatCurrency(ev.budget) }}</span>
                <span v-else style="color:var(--text-muted)">—</span>
              </td>
              <td v-if="auth.canManageEvents" @click.stop>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-glass btn-sm" @click="openEdit(ev)" title="Edit">
                    <Edit :size="16" />
                  </button>
                  <button class="btn btn-danger btn-sm" @click="confirmDelete(ev)" title="Hapus">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="pagination">
        <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchEvents(pagination.current_page - 1)">
          <ChevronLeft :size="18" />
        </button>
        <button v-for="p in pagination.last_page" :key="p" class="page-btn" :class="{ active: p === pagination.current_page }" @click="fetchEvents(p)">{{ p }}</button>
        <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchEvents(pagination.current_page + 1)">
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Detail Modal (view) -->
    <Transition name="fade">
      <div v-if="detailEvent" class="modal-overlay" @click.self="detailEvent = null">
        <div class="modal-box" style="max-width:680px">
          <div class="modal-header">
            <h2>Detail Event</h2>
            <button class="btn btn-glass btn-sm" @click="detailEvent = null" title="Tutup">
              <X :size="18" />
            </button>
          </div>
          <div class="detail-grid">
            <div class="detail-section">
              <div class="detail-badge-row">
                <span class="badge" :class="`badge-${detailEvent.status}`">{{ statusLabel(detailEvent.status) }}</span>
                <span v-if="detailEvent.category" class="category-tag">{{ detailEvent.category }}</span>
              </div>
              <h3 class="detail-title">{{ detailEvent.name }}</h3>
              <p class="detail-desc">{{ detailEvent.description || 'Tidak ada deskripsi.' }}</p>
              <div class="detail-meta-list">
                <div class="dm-item">
                  <MapPin :size="16" />
                  <span>{{ detailEvent.location }}</span>
                </div>
                <div class="dm-item">
                  <Calendar :size="16" />
                  <span>{{ formatDate(detailEvent.start_date) }} — {{ formatDate(detailEvent.end_date) }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.start_time">
                  <Clock :size="16" />
                  <span>{{ detailEvent.start_time }} — {{ detailEvent.end_time }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.budget">
                  <DollarSign :size="16" />
                  <span>{{ formatCurrency(detailEvent.budget) }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.expected_participants">
                  <Users :size="16" />
                  <span>{{ detailEvent.expected_participants.toLocaleString('id-ID') }} peserta</span>
                </div>
                <div class="dm-item">
                  <User :size="16" />
                  <span>Dibuat oleh: {{ detailEvent.creator?.name }}</span>
                </div>
              </div>
            </div>
            <div class="detail-section">
              <h4 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--text-secondary)">TIM PERSONEL ({{ detailEvent.personnel?.length || 0 }})</h4>
              <div class="personnel-list">
                <div v-if="!detailEvent.personnel?.length" style="color:var(--text-muted);font-size:13px">Belum ada personel</div>
                <div v-for="p in detailEvent.personnel" :key="p.id" class="personnel-item">
                  <div class="p-avatar-lg">{{ p.name.charAt(0) }}</div>
                  <div>
                    <div style="font-size:13px;font-weight:500">{{ p.name }}</div>
                    <div style="font-size:11px;color:var(--text-muted)">{{ p.pivot?.role_in_event || roleLabel(p.role) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tasks section -->
            <div class="detail-section">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <h4 style="font-size:14px;font-weight:600;color:var(--text-secondary)">TASK & WORKFLOW</h4>
                <RouterLink :to="{ path: '/tasks', query: { event_id: detailEvent.id } }" class="btn btn-glass btn-sm" style="font-size:11px">
                  <CheckSquare :size="14" />
                  Semua Task
                </RouterLink>
              </div>
              <div v-if="loadingEventTasks" style="padding:16px;text-align:center">
                <Loader2 :size="20" class="spinner-icon" />
              </div>
              <div v-else>
                <div v-if="eventTaskStats" class="task-stats-bar">
                  <div class="tstat" style="color:#67e8f9"><span class="tstat-val">{{ eventTaskStats.total }}</span><span>Total</span></div>
                  <div class="tstat" style="color:#fcd34d"><span class="tstat-val">{{ eventTaskStats.in_progress }}</span><span>Berjalan</span></div>
                  <div class="tstat" style="color:#a5b4fc"><span class="tstat-val">{{ eventTaskStats.review }}</span><span>Review</span></div>
                  <div class="tstat" style="color:#6ee7b7"><span class="tstat-val">{{ eventTaskStats.completed }}</span><span>Selesai</span></div>
                  <div class="tstat" style="color:#fca5a5"><span class="tstat-val">{{ eventTaskStats.overdue }}</span><span>Overdue</span></div>
                </div>
                <div v-if="eventTaskStats?.total" class="task-progress-wrap">
                  <div class="task-progress-bar">
                    <div class="task-progress-fill" :style="`width:${eventTaskStats.progress}%`"></div>
                  </div>
                  <span style="font-size:11px;color:var(--text-muted)">{{ eventTaskStats.progress }}% selesai</span>
                </div>
                <div v-if="!eventTasks.length" style="color:var(--text-muted);font-size:13px;padding:8px 0">
                  Belum ada task
                </div>
                <div v-for="t in eventTasks.slice(0, 5)" :key="t.id" class="event-task-item">
                  <span class="priority-dot-sm" :class="`pdot-${t.priority}`"></span>
                  <span class="event-task-title">{{ t.title }}</span>
                  <span class="badge event-task-status" :class="`task-badge-${t.status}`">{{ statusLabel2(t.status) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Create/Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-box" style="max-width:700px">
          <div class="modal-header">
            <h2>{{ editId ? 'Edit Event' : 'Buat Event Baru' }}</h2>
            <button class="btn btn-glass btn-sm" @click="closeModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <transition name="fade">
            <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          </transition>

          <form @submit.prevent="submitForm">
            <!-- Basic info -->
            <div class="form-group">
              <label>Nama Event *</label>
              <input v-model="form.name" class="glass-input" required placeholder="Festival Budaya Nusantara 2026"/>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="glass-input" rows="3" placeholder="Deskripsi singkat tentang event ini..."></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Lokasi *</label>
                <input v-model="form.location" class="glass-input" required placeholder="Jakarta Convention Center"/>
              </div>
              <div class="form-group">
                <label>Kategori</label>
                <input v-model="form.category" class="glass-input" placeholder="Conference, Cultural, Seminar..."/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Mulai *</label>
                <input v-model="form.start_date" type="date" class="glass-input" required/>
              </div>
              <div class="form-group">
                <label>Tanggal Selesai *</label>
                <input v-model="form.end_date" type="date" class="glass-input" required/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jam Mulai</label>
                <input v-model="form.start_time" type="time" class="glass-input"/>
              </div>
              <div class="form-group">
                <label>Jam Selesai</label>
                <input v-model="form.end_time" type="time" class="glass-input"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Status *</label>
                <select v-model="form.status" class="glass-input" required>
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Estimasi Peserta</label>
                <input v-model.number="form.expected_participants" type="number" class="glass-input" min="0" placeholder="500"/>
              </div>
            </div>
            <div class="form-group">
              <label>Budget (Rp)</label>
              <input v-model.number="form.budget" type="number" class="glass-input" min="0" placeholder="500000000"/>
            </div>

            <!-- Personnel -->
            <div class="personnel-section">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <label style="margin-bottom:0">Tim Personel</label>
                <button type="button" class="btn btn-glass btn-sm" @click="addPersonnel">
                  <Plus :size="16" />
                  Tambah
                </button>
              </div>
              <div v-if="form.personnel.length === 0" style="color:var(--text-muted);font-size:13px;padding:12px 0">Belum ada personel ditambahkan</div>
              <div v-for="(p, idx) in form.personnel" :key="idx" class="personnel-row">
                <select v-model="p.user_id" class="glass-input" style="flex:1">
                  <option value="">Pilih User</option>
                  <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ roleLabel(u.role) }})</option>
                </select>
                <input v-model="p.role_in_event" class="glass-input" style="flex:1" placeholder="Peran dalam event"/>
                <button type="button" class="btn btn-danger btn-sm" @click="removePersonnel(idx)" title="Hapus">
                  <X :size="16" />
                </button>
              </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>{{ editId ? 'Simpan Perubahan' : 'Buat Event' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Delete confirm -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width:400px;text-align:center">
          <div style="margin-bottom:16px">
            <AlertCircle :size="48" style="margin:0 auto; color: #f59e0b;" />
          </div>
          <h2 style="margin-bottom:12px">Hapus Event?</h2>
          <p style="color:var(--text-secondary);margin-bottom:24px">Event <strong>{{ deleteTarget.name }}</strong> akan dihapus permanen beserta data personelnya.</p>
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
import { ref, onMounted, watch } from 'vue'
import { Plus, Calendar, Edit, Trash2, ChevronLeft, ChevronRight, X, MapPin, Clock, DollarSign, Users, User, AlertCircle, Loader2, CheckSquare } from 'lucide-vue-next'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const router = useRouter()
const events = ref([])
const loading = ref(true)
const search = ref('')
const filterStatus = ref('')
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10 })
const availableUsers = ref([])

const showModal = ref(false)
const editId = ref(null)
const form = ref(defaultForm())
const formError = ref('')
const submitting = ref(false)
const deleteTarget = ref(null)
const detailEvent = ref(null)
const eventTaskCounts = ref({})
const eventTasks = ref([])
const eventTaskStats = ref(null)
const loadingEventTasks = ref(false)

const statuses = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Aktif' },
  { value: 'ongoing', label: 'Berlangsung' },
  { value: 'completed', label: 'Selesai' },
  { value: 'cancelled', label: 'Dibatalkan' },
]

const roleLabels = {
  superadmin: 'Super Admin', project_manager: 'Project Manager', staff: 'Staff / Personnel',
  event_planner: 'Event Planner', promotion_team: 'Promotion Team', partnership_manager: 'Partnership Manager',
  budgeting: 'Budgeting', operations_team: 'Operations Team', creative_team: 'Creative Team',
  rundown_coordinator: 'Rundown Coordinator', talent_coordinator: 'Talent Coordinator',
  registration_guest_management: 'Registration & Guest Mgmt', technical_team: 'Technical Team',
  documentation_team: 'Documentation Team', liaison_officer: 'Liaison Officer',
}
function roleLabel(r) { return roleLabels[r] || r }
function statusLabel2(s) {
  const map = { pending: 'Pending', in_progress: 'Progress', review: 'Review', completed: 'Selesai', cancelled: 'Batal' }
  return map[s] || s
}

function statusLabel(s) { return statuses.find(x => x.value === s)?.label || s }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }
function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v) }
function normalizeTimeInput(v) {
  if (!v) return ''
  return String(v).substring(0, 5)
}

function defaultForm() {
  return { name: '', description: '', location: '', start_date: '', end_date: '', start_time: '', end_time: '', status: 'draft', budget: '', category: '', expected_participants: '', personnel: [] }
}

async function fetchEvents(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/events', { params: { page, search: search.value, status: filterStatus.value } })
    events.value = res.data.data
    pagination.value = { current_page: res.data.current_page, last_page: res.data.last_page, per_page: res.data.per_page }
    // prefetch task counts for visible events
    res.data.data.forEach(ev => {
      if (!(ev.id in eventTaskCounts.value)) {
        api.get(`/events/${ev.id}/tasks`).then(r => {
          eventTaskCounts.value[ev.id] = r.data.stats?.total ?? 0
        }).catch(() => { eventTaskCounts.value[ev.id] = 0 })
      }
    })
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal memuat data event.'
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  const res = await api.get('/users-list')
  availableUsers.value = res.data
}

async function openDetail(ev) {
  detailEvent.value = ev
  eventTasks.value = []
  eventTaskStats.value = null
  loadingEventTasks.value = true
  try {
    const res = await api.get(`/events/${ev.id}/tasks`)
    eventTasks.value = res.data.tasks || []
    eventTaskStats.value = res.data.stats || null
    eventTaskCounts.value[ev.id] = res.data.stats?.total ?? 0
  } finally {
    loadingEventTasks.value = false
  }
}

function openCreate() {
  editId.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}
function openEdit(ev) {
  editId.value = ev.id
  form.value = {
    name: ev.name, description: ev.description || '', location: ev.location,
    start_date: ev.start_date?.substring(0, 10) || '', end_date: ev.end_date?.substring(0, 10) || '',
    start_time: normalizeTimeInput(ev.start_time), end_time: normalizeTimeInput(ev.end_time),
    status: ev.status, budget: ev.budget || '', category: ev.category || '',
    expected_participants: ev.expected_participants || '',
    personnel: ev.personnel?.map(p => ({ user_id: p.id, role_in_event: p.pivot?.role_in_event || '' })) || [],
  }
  formError.value = ''
  showModal.value = true
}
function closeModal() { showModal.value = false }

function addPersonnel() { form.value.personnel.push({ user_id: '', role_in_event: '' }) }
function removePersonnel(idx) { form.value.personnel.splice(idx, 1) }

async function submitForm() {
  formError.value = ''
  submitting.value = true
  const payload = { ...form.value }
  payload.start_time = normalizeTimeInput(payload.start_time)
  payload.end_time = normalizeTimeInput(payload.end_time)
  if (!payload.budget) delete payload.budget
  if (!payload.expected_participants) delete payload.expected_participants
  if (!payload.start_time) delete payload.start_time
  if (!payload.end_time) delete payload.end_time

  const selectedIds = payload.personnel.filter(p => p.user_id).map(p => String(p.user_id))
  if (new Set(selectedIds).size !== selectedIds.length) {
    formError.value = 'User personel tidak boleh duplikat dalam satu event.'
    submitting.value = false
    return
  }

  payload.personnel = payload.personnel.filter(p => p.user_id)
  try {
    if (editId.value) {
      await api.put(`/events/${editId.value}`, payload)
    } else {
      await api.post('/events', payload)
    }
    closeModal()
    fetchEvents(pagination.value.current_page)
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) formError.value = Object.values(errs).flat().join(' ')
    else formError.value = e.response?.data?.message || 'Terjadi kesalahan.'
  } finally {
    submitting.value = false
  }
}

function confirmDelete(ev) { deleteTarget.value = ev }
async function doDelete() {
  submitting.value = true
  try {
    await api.delete(`/events/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchEvents(pagination.value.current_page)
  } catch (e) {
    const message = e.response?.data?.message || 'Gagal menghapus event.'
    formError.value = message
    window.alert(message)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchEvents()
  if (auth.canManageEvents) loadUsers()
})

// Close all modals when navigating away
watch(() => router.currentRoute.value.path, () => {
  detailEvent.value = null
  showModal.value = false
  deleteTarget.value = null
})
</script>

<style scoped>
.events-page { display: flex; flex-direction: column; gap: 16px; }
.page-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; }
.header-left h2 { font-size: 18px; font-weight: 600; }
.header-left p { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }
.filters { display: flex; gap: 12px; padding: 16px 20px; flex-wrap: wrap; align-items: center; }
.table-container { padding: 0 0 20px; }
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 48px; color: var(--text-secondary); }

.clickable-row { cursor: pointer; }

.event-cell { display: flex; align-items: center; gap: 10px; }
.event-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.icon-active { background: rgba(16,185,129,0.2); }
.icon-draft { background: rgba(107,114,128,0.2); }
.icon-ongoing { background: rgba(6,182,212,0.2); }
.icon-completed { background: rgba(99,102,241,0.2); }
.icon-cancelled { background: rgba(239,68,68,0.2); }

.personnel-avatars { display: flex; gap: -4px; }
.p-avatar {
  width: 26px; height: 26px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
  border: 2px solid rgba(30,27,75,0.8);
  margin-left: -4px;
}
.p-avatar:first-child { margin-left: 0; }
.p-avatar.more { background: rgba(255,255,255,0.15); font-size: 9px; }

/* Detail */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
.detail-badge-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.category-tag { display: inline-block; padding: 3px 10px; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 20px; font-size: 11px; color: #67e8f9; }
.detail-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
.detail-desc { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.6; }
.detail-meta-list { display: flex; flex-direction: column; gap: 8px; }
.dm-item { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: var(--text-secondary); }
.personnel-list { display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto; }
.personnel-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.05); border-radius: 10px; }
.p-avatar-lg { width: 34px; height: 34px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }

/* Personnel form */
.personnel-section { background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 14px; padding: 16px; margin-bottom: 4px; }
.personnel-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }

/* Task integration */
.task-count-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; color: #a5b4fc; font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
.task-count-badge:hover { background: rgba(99,102,241,0.3); }
.task-stats-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.tstat { display: flex; flex-direction: column; align-items: center; gap: 2px; background: rgba(255,255,255,0.05); border-radius: 10px; padding: 8px 14px; min-width: 60px; }
.tstat-val { font-size: 20px; font-weight: 700; }
.tstat span:last-child { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
.task-progress-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.task-progress-bar { flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
.task-progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); border-radius: 3px; transition: width 0.5s; }
.event-task-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 8px; background: rgba(255,255,255,0.04); margin-bottom: 6px; }
.priority-dot-sm { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.pdot-urgent { background: #ef4444; }
.pdot-high   { background: #f59e0b; }
.pdot-medium { background: #6366f1; }
.pdot-low    { background: #6b7280; }
.event-task-title { flex: 1; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.event-task-status { font-size: 10px; font-weight: 700; padding: 2px 7px; }
.task-badge-pending    { background:rgba(107,114,128,0.2); color:#d1d5db; }
.task-badge-in_progress{ background:rgba(6,182,212,0.2);  color:#67e8f9; }
.task-badge-review     { background:rgba(245,158,11,0.2); color:#fcd34d; }
.task-badge-completed  { background:rgba(16,185,129,0.2); color:#6ee7b7; }
.task-badge-cancelled  { background:rgba(239,68,68,0.2);  color:#fca5a5; }
</style>
