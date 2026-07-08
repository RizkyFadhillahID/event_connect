<template>
  <div class="rundown-page">

    <!-- ───────────────────────── FILTERS ───────────────────────── -->
    <div class="filters glass-card">
      <select v-model="filters.event_id" class="glass-input" @change="onEventChange" style="max-width:220px">
        <option value="">Pilih Event...</option>
        <option v-for="ev in eventsList" :key="ev.id" :value="ev.id">{{ ev.name }}</option>
      </select>
      <input v-model="filters.search" class="glass-input" placeholder="Cari sesi..." @input="debounceFetch" style="max-width:220px" />
      <select v-model="filters.category" class="glass-input" @change="fetchRundowns" style="max-width:160px">
        <option value="">Semua Kategori</option>
        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
      </select>
      <select v-model="filters.status" class="glass-input" @change="fetchRundowns" style="max-width:160px">
        <option value="">Semua Status</option>
        <option v-for="s in rundownStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
      <div style="display:flex;gap:10px;align-items:center; margin-left: auto;">
        <div class="view-toggle">
          <button class="toggle-btn" :class="{ active: viewMode === 'timeline' }" @click="viewMode = 'timeline'" title="Timeline">
            <Clock :size="18" />
          </button>
          <button class="toggle-btn" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'" title="List">
            <List :size="18" />
          </button>
        </div>
        <button v-if="filters.event_id" class="btn btn-glass print-hide" @click="exportPDF" title="Cetak / Simpan PDF" style="display: inline-flex; align-items: center; gap: 6px;">
          <Printer :size="18" /> Cetak PDF
        </button>
        <button v-if="canManage" class="btn btn-primary" @click="openCreate">
          <Plus :size="18" /> Tambah Sesi
        </button>
      </div>
    </div>

    <!-- ───────────────────────── DATE TABS ───────────────────────── -->
    <div v-if="filters.event_id && eventDates.length > 0" class="date-tabs glass-card">
      <button class="date-tab" :class="{ active: !filters.event_date }" @click="filters.event_date = ''; fetchRundowns()">
        Semua Hari
      </button>
      <button
        v-for="d in eventDates" :key="d"
        class="date-tab"
        :class="{ active: filters.event_date === d }"
        @click="filters.event_date = d; fetchRundowns()"
      >
        {{ formatDateTab(d) }}
      </button>
    </div>

    <!-- ───────────────────────── NO EVENT SELECTED ───────────────────────── -->
    <div v-if="!filters.event_id" class="empty-state glass-card">
      <CalendarClock :size="48" style="opacity:0.4" />
      <p>Pilih event terlebih dahulu untuk melihat rundown.</p>
    </div>

    <!-- ───────────────────────── LOADING ───────────────────────── -->
    <div v-else-if="loading" class="loading-state glass-card">
      <Loader2 :size="28" class="spinner-icon" />
      <span>Memuat rundown...</span>
    </div>

    <!-- ───────────────────────── EMPTY ───────────────────────── -->
    <div v-else-if="rundowns.length === 0" class="empty-state glass-card">
      <CalendarClock :size="48" style="opacity:0.4" />
      <p>Belum ada sesi rundown untuk filter ini.</p>
      <button v-if="canManage" class="btn btn-primary" @click="openCreate">
        <Plus :size="16" /> Tambah Sesi Pertama
      </button>
    </div>

    <!-- ───────────────────────── TIMELINE VIEW ───────────────────────── -->
    <template v-else-if="viewMode === 'timeline'">
      <div v-for="(dayItems, dayDate) in groupedByDate" :key="dayDate" class="day-block">
        <div class="day-header">
          <Calendar :size="16" />
          {{ formatDayHeader(dayDate) }}
          <span class="day-count">{{ dayItems.length }} sesi</span>
        </div>
        <div class="timeline-track">
          <div
            v-for="item in dayItems" :key="item.id"
            class="timeline-item"
            :class="[`status-${item.status}`, { 'has-warning': item.has_unfinished_dependencies }]"
            @click="openDetail(item)"
          >
            <!-- Time column -->
            <div class="time-col">
              <span class="time-start">{{ item.start_time }}</span>
              <div class="time-bar">
                <div class="time-bar-fill" :style="{ background: statusColor(item.status) }"></div>
              </div>
              <span class="time-end">{{ item.end_time }}</span>
            </div>

            <!-- Content -->
            <div class="item-content">
              <div class="item-top">
                <span v-if="item.category" class="category-badge" :style="{ background: categoryColor(item.category) }">
                  {{ item.category }}
                </span>
                <span class="status-badge" :class="`badge-${item.status}`">{{ statusLabel(item.status) }}</span>
                <AlertTriangle v-if="item.has_unfinished_dependencies" :size="14" class="warn-icon" title="Ada task dependency yang belum selesai" />
              </div>
              <h4 class="item-title">{{ item.title }}</h4>
              <div class="item-meta">
                <span v-if="item.pic"><User :size="12" /> {{ item.pic.name }}</span>
                <span v-if="item.location_note"><MapPin :size="12" /> {{ item.location_note }}</span>
                <span v-if="item.delay_minutes > 0" class="delay-tag"><Clock :size="12" /> Delay {{ item.delay_minutes }}m</span>
              </div>
            </div>

            <!-- Quick status (only if can update) -->
            <div v-if="canUpdateStatus(item)" class="quick-actions" @click.stop>
              <select class="status-select-mini" :value="item.status" @change="quickStatus(item, $event.target.value)">
                <option v-for="s in rundownStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ───────────────────────── LIST VIEW ───────────────────────── -->
    <div v-else class="glass-card table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Sesi</th>
            <th>Kategori</th>
            <th>PIC</th>
            <th>Status</th>
            <th>Dep.</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in rundowns" :key="item.id">
            <td>{{ formatDateShort(item.event_date) }}</td>
            <td class="time-cell">{{ item.start_time }} – {{ item.end_time }}</td>
            <td>
              <span class="item-title-cell">{{ item.title }}</span>
              <span v-if="item.delay_minutes > 0" class="delay-tag-sm">+{{ item.delay_minutes }}m</span>
            </td>
            <td>
              <span v-if="item.category" class="category-badge-sm" :style="{ background: categoryColor(item.category) }">{{ item.category }}</span>
              <span v-else class="text-dim">-</span>
            </td>
            <td>{{ item.pic ? item.pic.name : '-' }}</td>
            <td>
              <span class="status-badge" :class="`badge-${item.status}`">{{ statusLabel(item.status) }}</span>
              <AlertTriangle v-if="item.has_unfinished_dependencies" :size="12" class="warn-icon" title="Dependency belum selesai" />
            </td>
            <td>
              <span v-if="item.has_unfinished_dependencies" class="text-warn">⚠</span>
              <span v-else class="text-ok">✓</span>
            </td>
            <td class="action-cell">
              <button class="btn-icon" @click="openDetail(item)" title="Detail"><Eye :size="14" /></button>
              <button v-if="canManage" class="btn-icon" @click="openEdit(item)" title="Edit"><Pencil :size="14" /></button>
              <button v-if="canDelete" class="btn-icon btn-danger" @click="confirmDelete(item)" title="Hapus"><Trash2 :size="14" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ CREATE / EDIT MODAL ═══════════════════ -->
    <div v-if="showFormModal" class="modal-overlay" @click.self="closeForm">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h3><CalendarClock :size="20" /> {{ editId ? 'Edit Sesi Rundown' : 'Tambah Sesi Rundown' }}</h3>
          <button class="btn-icon" @click="closeForm"><X :size="18" /></button>
        </div>

        <div class="modal-body">
          <div class="form-grid">
            <!-- Event (read-only when already filtered) -->
            <div class="form-group">
              <label>Event *</label>
              <select v-model="form.event_id" class="glass-input" :disabled="!!filters.event_id">
                <option value="">Pilih Event</option>
                <option v-for="ev in eventsList" :key="ev.id" :value="ev.id">{{ ev.name }}</option>
              </select>
            </div>

            <!-- Event date -->
            <div class="form-group">
              <label>Tanggal Sesi *</label>
              <input type="date" v-model="form.event_date" class="glass-input" />
            </div>

            <!-- Title -->
            <div class="form-group full-width">
              <label>Nama Sesi *</label>
              <input type="text" v-model="form.title" class="glass-input" placeholder="Opening Ceremony" />
            </div>

            <!-- Category -->
            <div class="form-group">
              <label>Kategori</label>
              <select v-model="form.category" class="glass-input">
                <option value="">Tanpa Kategori</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>

            <!-- PIC -->
            <div class="form-group">
              <label>PIC (Person In Charge)</label>
              <select v-model="form.pic_id" class="glass-input">
                <option value="">Tanpa PIC</option>
                <option v-for="p in personnelList" :key="p.id" :value="p.id">{{ p.name }} ({{ p.role }})</option>
              </select>
            </div>

            <!-- Start time -->
            <div class="form-group">
              <label>Mulai *</label>
              <input type="time" v-model="form.start_time" class="glass-input" />
            </div>

            <!-- End time -->
            <div class="form-group">
              <label>Selesai *</label>
              <input type="time" v-model="form.end_time" class="glass-input" />
            </div>

            <!-- Location note -->
            <div class="form-group">
              <label>Lokasi (dalam venue)</label>
              <input type="text" v-model="form.location_note" class="glass-input" placeholder="Stage Utama, Area Lobby" />
            </div>

            <!-- Order -->
            <div class="form-group">
              <label>Urutan</label>
              <input type="number" v-model.number="form.order_number" class="glass-input" min="0" />
            </div>

            <!-- Description -->
            <div class="form-group full-width">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="glass-input" rows="2" placeholder="Deskripsi singkat sesi..."></textarea>
            </div>

            <!-- Notes -->
            <div class="form-group full-width">
              <label>Catatan Teknis</label>
              <textarea v-model="form.notes" class="glass-input" rows="2" placeholder="Catatan untuk tim teknis, talent, dll..."></textarea>
            </div>

            <!-- Dependencies -->
            <div class="form-group full-width">
              <label>Task Dependency (Harus Selesai Sebelum Sesi Ini)</label>
              <div class="dep-selector">
                <select v-model="selectedDepTask" class="glass-input" style="max-width:320px">
                  <option value="">Pilih task...</option>
                  <option v-for="t in availableTasksForDep" :key="t.id" :value="t.id">
                    [{{ t.status }}] {{ t.title }}
                  </option>
                </select>
                <button class="btn btn-secondary" type="button" @click="addDepTask" :disabled="!selectedDepTask">
                  <Plus :size="14" /> Tambah
                </button>
              </div>
              <div v-if="form.dependency_task_ids.length" class="dep-list">
                <div v-for="tid in form.dependency_task_ids" :key="tid" class="dep-chip">
                  <CheckSquare :size="12" />
                  {{ getTaskTitle(tid) }}
                  <button @click="removeDepTask(tid)" class="dep-remove"><X :size="10" /></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeForm">Batal</button>
          <button class="btn btn-primary" @click="saveForm" :disabled="saving">
            <Loader2 v-if="saving" :size="14" class="spinner-icon" />
            {{ saving ? 'Menyimpan...' : (editId ? 'Simpan Perubahan' : 'Buat Sesi') }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ DETAIL MODAL ═══════════════════ -->
    <div v-if="showDetailModal && detailItem" class="modal-overlay" @click.self="closeDetail">
      <div class="modal modal-xl">
        <div class="modal-header">
          <div>
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:4px">
              <span v-if="detailItem.category" class="category-badge" :style="{ background: categoryColor(detailItem.category) }">{{ detailItem.category }}</span>
              <span class="status-badge" :class="`badge-${detailItem.status}`">{{ statusLabel(detailItem.status) }}</span>
              <AlertTriangle v-if="detailItem.has_unfinished_dependencies" :size="14" class="warn-icon" />
            </div>
            <h3>{{ detailItem.title }}</h3>
            <p style="opacity:0.6;font-size:0.85rem;margin-top:4px">
              <Clock :size="12" /> {{ detailItem.start_time }} – {{ detailItem.end_time }}
              &nbsp;|&nbsp; <Calendar :size="12" /> {{ formatDateShort(detailItem.event_date) }}
              <span v-if="detailItem.location_note">&nbsp;|&nbsp; <MapPin :size="12" /> {{ detailItem.location_note }}</span>
            </p>
          </div>
          <button class="btn-icon" @click="closeDetail"><X :size="18" /></button>
        </div>

        <div class="modal-body detail-layout">
          <!-- Left col -->
          <div class="detail-left">
            <!-- Description -->
            <div v-if="detailItem.description" class="detail-section">
              <h5>Deskripsi</h5>
              <p>{{ detailItem.description }}</p>
            </div>

            <!-- Meta info -->
            <div class="detail-section">
              <h5>Informasi</h5>
              <div class="meta-grid">
                <span class="meta-label">Event</span>
                <span>{{ detailItem.event?.name }}</span>
                <span class="meta-label">PIC</span>
                <span>{{ detailItem.pic ? detailItem.pic.name : '-' }}</span>
                <span class="meta-label">Dibuat oleh</span>
                <span>{{ detailItem.creator?.name }}</span>
                <span class="meta-label">Delay</span>
                <span :class="detailItem.delay_minutes > 0 ? 'text-warn' : ''">
                  {{ detailItem.delay_minutes > 0 ? `+${detailItem.delay_minutes} menit` : 'Tidak ada' }}
                </span>
                <span v-if="detailItem.started_at" class="meta-label">Mulai Aktual</span>
                <span v-if="detailItem.started_at">{{ formatDatetime(detailItem.started_at) }}</span>
                <span v-if="detailItem.ended_at" class="meta-label">Selesai Aktual</span>
                <span v-if="detailItem.ended_at">{{ formatDatetime(detailItem.ended_at) }}</span>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="detailItem.notes" class="detail-section">
              <h5>Catatan Teknis</h5>
              <p>{{ detailItem.notes }}</p>
            </div>

            <!-- Dependencies -->
            <div class="detail-section">
              <h5>
                Task Dependency
                <AlertTriangle v-if="detailItem.has_unfinished_dependencies" :size="13" class="warn-icon" />
              </h5>
              <div v-if="!detailItem.dependency_tasks || detailItem.dependency_tasks.length === 0" class="text-dim">Tidak ada dependency.</div>
              <div v-else class="dep-list">
                <div v-for="t in detailItem.dependency_tasks" :key="t.id" class="dep-chip" :class="{ 'dep-done': t.status === 'completed', 'dep-warn': t.status !== 'completed' && t.status !== 'cancelled' }">
                  <CheckSquare v-if="t.status === 'completed'" :size="12" />
                  <AlertTriangle v-else-if="t.status !== 'cancelled'" :size="12" style="color:#fbbf24" />
                  <span>{{ t.title }}</span>
                  <span class="dep-status">[{{ t.status }}]</span>
                </div>
              </div>
              <p v-if="detailItem.has_unfinished_dependencies" class="dep-warning-text">
                ⚠ Beberapa task dependency belum selesai. Pastikan semua task selesai sebelum sesi ini dimulai.
              </p>
            </div>
          </div>

          <!-- Right col: Status + Logs -->
          <div class="detail-right">
            <!-- Status update -->
            <div v-if="canUpdateStatus(detailItem)" class="detail-section">
              <h5>Update Status</h5>
              <div class="status-pills">
                <button
                  v-for="s in rundownStatuses" :key="s.value"
                  class="status-pill"
                  :class="[`pill-${s.value}`, { active: detailItem.status === s.value }]"
                  @click="changeStatus(detailItem, s.value)"
                >
                  {{ s.label }}
                </button>
              </div>
              <div v-if="pendingStatus === 'delayed'" class="form-group" style="margin-top:8px">
                <label>Alasan Delay</label>
                <textarea v-model="delayReason" class="glass-input" rows="2" placeholder="Jelaskan penyebab delay..."></textarea>
                <button class="btn btn-primary" style="margin-top:6px" @click="confirmStatus">Konfirmasi Delayed</button>
              </div>
            </div>

            <!-- Edit / Delete buttons -->
            <div v-if="canManage" class="detail-section" style="display:flex;gap:8px">
              <button class="btn btn-secondary" @click="openEditFromDetail">
                <Pencil :size="14" /> Edit
              </button>
              <button v-if="canDelete" class="btn btn-danger-sm" @click="confirmDeleteFromDetail">
                <Trash2 :size="14" /> Hapus
              </button>
            </div>

            <!-- Audit Log -->
            <div class="detail-section">
              <h5><History :size="14" /> Riwayat Aktivitas</h5>
              <div v-if="loadingLogs" class="text-dim">Memuat log...</div>
              <div v-else-if="!detailLogs.length" class="text-dim">Belum ada aktivitas.</div>
              <div v-else class="log-list">
                <div v-for="log in detailLogs" :key="log.id" class="log-item">
                  <div class="log-dot" :class="`dot-${log.new_status || 'created'}`"></div>
                  <div class="log-content">
                    <span class="log-user">{{ log.user?.name }}</span>
                    <span class="log-action">{{ formatLogAction(log) }}</span>
                    <span v-if="log.delay_reason" class="log-reason">"{{ log.delay_reason }}"</span>
                    <span class="log-time">{{ formatDatetime(log.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ───────────────────────── PRINT-ONLY ALL DAY VIEW ───────────────────────── -->
    <div class="print-only-container">
      <div v-for="(dayItems, dayDate) in groupedByDateForPrint" :key="dayDate" class="day-block-print">
        <div class="day-header-print">
          {{ formatDayHeader(dayDate) }}
        </div>
        <div class="print-list">
          <div v-for="item in dayItems" :key="item.id" class="print-item">
            <div class="print-time-col">
              {{ item.start_time }} – {{ item.end_time }}
            </div>
            <div class="print-content-col">
              <div class="print-item-title">{{ item.title }}</div>
              <div class="print-item-meta">
                <span v-if="item.category">Kategori: {{ item.category }}</span>
                <span v-if="item.pic"> | PIC: {{ item.pic.name }}</span>
                <span v-if="item.location_note"> | Lokasi: {{ item.location_note }}</span>
                <span v-if="item.status"> | Status: {{ statusLabel(item.status) }}</span>
                <span v-if="item.delay_minutes > 0"> | Delay: {{ item.delay_minutes }}m</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '../api/axios'
import { useAuthStore } from '../stores/auth'
import {
  Plus, X, Pencil, Trash2, Eye, List, Clock, Calendar,
  CalendarClock, Loader2, User, MapPin, AlertTriangle,
  CheckSquare, History, Printer
} from 'lucide-vue-next'

const auth = useAuthStore()

function exportPDF() {
  window.print()
}

// ───────────────────────────────────────────────────────────────
// State
// ───────────────────────────────────────────────────────────────
const rundowns    = ref([])
const allRundownsForPrint = ref([])
const eventsList  = ref([])
const personnelList = ref([])
const eventTasks  = ref([])   // tasks for dep picker
const eventDates  = ref([])
const loading     = ref(false)
const saving      = ref(false)
const loadingLogs = ref(false)
const viewMode    = ref('timeline')

const filters = ref({
  event_id:   '',
  event_date: '',
  category:   '',
  status:     '',
  search:     '',
})

// form state
const showFormModal = ref(false)
const editId        = ref(null)
const form          = ref(defaultForm())
const selectedDepTask = ref('')

// detail / status
const showDetailModal = ref(false)
const detailItem      = ref(null)
const detailLogs      = ref([])
const pendingStatus   = ref('')
const delayReason     = ref('')

// ───────────────────────────────────────────────────────────────
// Constants
// ───────────────────────────────────────────────────────────────
const categories = [
  { value: 'ceremony',  label: 'Ceremony' },
  { value: 'talent',    label: 'Talent' },
  { value: 'technical', label: 'Technical' },
  { value: 'logistics', label: 'Logistics' },
  { value: 'meal',      label: 'Meal / Break' },
  { value: 'other',     label: 'Other' },
]

const rundownStatuses = [
  { value: 'pending',   label: 'Pending' },
  { value: 'ready',     label: 'Ready' },
  { value: 'live',      label: 'Live' },
  { value: 'delayed',   label: 'Delayed' },
  { value: 'completed', label: 'Completed' },
]

// ───────────────────────────────────────────────────────────────
// Permission helpers
// ───────────────────────────────────────────────────────────────
const currentUserPersonnel = computed(() => {
  return personnelList.value.find(p => p.id === auth.user?.id)
})

const currentUserEventRole = computed(() => {
  const r = currentUserPersonnel.value?.role_in_event
  if (!r) return ''
  return r.toLowerCase().replace(/[\s_]+/g, '')
})

const canManage = computed(() => {
  const globalRole = auth.user?.role
  if (['superadmin', 'project_manager'].includes(globalRole)) return true
  
  const eventRole = currentUserEventRole.value
  return [
    'rundowncoordinator', 'rundownpic',
    'eventplanner', 'eventcoordinator',
    'projectmanager'
  ].includes(eventRole)
})

const canDelete = computed(() => {
  const globalRole = auth.user?.role
  if (['superadmin', 'project_manager'].includes(globalRole)) return true
  
  const eventRole = currentUserEventRole.value
  return ['rundowncoordinator', 'rundownpic'].includes(eventRole)
})

function canUpdateStatus(item) {
  const userId = auth.user?.id
  if (!userId) return false
  if (item.pic_id === userId) return true
  
  const globalRole = auth.user?.role
  if (['superadmin', 'project_manager'].includes(globalRole)) return true
  
  const eventRole = currentUserEventRole.value
  
  // Roles that can update status of any category
  if ([
    'rundowncoordinator', 'rundownpic',
    'eventplanner', 'eventcoordinator',
    'operationsteam', 'operationshead',
    'projectmanager'
  ].includes(eventRole)) {
    return true
  }
  
  // Category-specific roles
  if (['technicalteam', 'technicallead', 'technicalpic'].includes(eventRole)) {
    return item.category === 'technical'
  }
  if (['talentcoordinator', 'talenthandler', 'talentpic'].includes(eventRole)) {
    return item.category === 'talent'
  }
  
  return false
}

// ───────────────────────────────────────────────────────────────
// Data fetching
// ───────────────────────────────────────────────────────────────
async function fetchEvents() {
  const res = await axios.get('/events')
  eventsList.value = res.data.data ?? res.data
}

async function fetchRundowns() {
  if (!filters.value.event_id) return
  loading.value = true
  try {
    const params = { ...filters.value }
    const res = await axios.get(`/events/${filters.value.event_id}/rundowns`, { params })
    rundowns.value  = res.data.data
    eventDates.value = res.data.dates

    // Fetch all for print (ignoring event_date, status, category, search)
    const printRes = await axios.get(`/events/${filters.value.event_id}/rundowns`)
    allRundownsForPrint.value = printRes.data.data ?? []
  } finally {
    loading.value = false
  }
}

async function fetchPersonnel() {
  if (!filters.value.event_id) return
  const res = await axios.get(`/events/${filters.value.event_id}/personnel`)
  personnelList.value = res.data.data ?? res.data
}

async function fetchEventTasks() {
  if (!filters.value.event_id) return
  const res = await axios.get('/tasks', { params: { event_id: filters.value.event_id, per_page: 100 } })
  eventTasks.value = res.data.data ?? res.data
}

async function fetchLogs(rundownId) {
  loadingLogs.value = true
  try {
    const res = await axios.get(`/rundowns/${rundownId}/logs`)
    detailLogs.value = res.data.data
  } finally {
    loadingLogs.value = false
  }
}

function onEventChange() {
  filters.value.event_date = ''
  eventDates.value = []
  rundowns.value = []
  fetchRundowns()
  fetchPersonnel()
  fetchEventTasks()
}

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchRundowns, 350)
}

// ───────────────────────────────────────────────────────────────
// Computed
// ───────────────────────────────────────────────────────────────
const groupedByDate = computed(() => {
  const groups = {}
  for (const item of rundowns.value) {
    const d = item.event_date
    if (!groups[d]) groups[d] = []
    groups[d].push(item)
  }
  return groups
})

const groupedByDateForPrint = computed(() => {
  const groups = {}
  for (const item of allRundownsForPrint.value) {
    const d = item.event_date
    if (!groups[d]) groups[d] = []
    groups[d].push(item)
  }
  // Sort keys (dates) chronologically
  const sorted = {}
  Object.keys(groups).sort().forEach(k => {
    sorted[k] = groups[k].sort((a, b) => a.start_time.localeCompare(b.start_time))
  })
  return sorted
})

const availableTasksForDep = computed(() => {
  const selected = form.value.dependency_task_ids
  return eventTasks.value.filter(t => !selected.includes(t.id))
})

// ───────────────────────────────────────────────────────────────
// Create / Edit
// ───────────────────────────────────────────────────────────────
function defaultForm() {
  return {
    event_id:            '',
    event_date:          '',
    title:               '',
    description:         '',
    category:            '',
    start_time:          '',
    end_time:            '',
    pic_id:              '',
    location_note:       '',
    notes:               '',
    order_number:        0,
    dependency_task_ids: [],
  }
}

function openCreate() {
  editId.value = null
  form.value   = defaultForm()
  form.value.event_id    = filters.value.event_id || ''
  form.value.event_date  = filters.value.event_date || ''
  selectedDepTask.value  = ''
  showFormModal.value    = true
}

function openEdit(item) {
  editId.value = item.id
  form.value   = {
    event_id:            item.event_id,
    event_date:          (item.event_date ?? '').slice(0, 10),
    title:               item.title,
    description:         item.description || '',
    category:            item.category || '',
    start_time:          (item.start_time ?? '').slice(0, 5),
    end_time:            (item.end_time ?? '').slice(0, 5),
    pic_id:              item.pic_id || '',
    location_note:       item.location_note || '',
    notes:               item.notes || '',
    order_number:        item.order_number,
    dependency_task_ids: (item.dependency_tasks ?? []).map(t => t.id),
  }
  selectedDepTask.value = ''
  showFormModal.value   = true
}

function openEditFromDetail() {
  const item = detailItem.value
  closeDetail()
  openEdit(item)
}

function addDepTask() {
  const id = Number(selectedDepTask.value)
  if (id && !form.value.dependency_task_ids.includes(id)) {
    form.value.dependency_task_ids.push(id)
  }
  selectedDepTask.value = ''
}

function removeDepTask(id) {
  form.value.dependency_task_ids = form.value.dependency_task_ids.filter(t => t !== id)
}

function getTaskTitle(id) {
  const t = eventTasks.value.find(t => t.id === id)
  return t ? t.title : `Task #${id}`
}

async function saveForm() {
  if (!form.value.event_id || !form.value.title || !form.value.event_date || !form.value.start_time || !form.value.end_time) {
    alert('Mohon lengkapi field wajib: Event, Tanggal, Nama Sesi, Jam Mulai, Jam Selesai.')
    return
  }
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editId.value) {
      await axios.put(`/rundowns/${editId.value}`, payload)
    } else {
      await axios.post(`/events/${form.value.event_id}/rundowns`, payload)
    }
    closeForm()
    fetchRundowns()
  } catch (err) {
    alert(err.response?.data?.message || 'Terjadi kesalahan.')
  } finally {
    saving.value = false
  }
}

function closeForm() {
  showFormModal.value = false
}

// ───────────────────────────────────────────────────────────────
// Detail
// ───────────────────────────────────────────────────────────────
async function openDetail(item) {
  const res = await axios.get(`/rundowns/${item.id}`)
  detailItem.value    = res.data.data
  detailLogs.value    = []
  pendingStatus.value = ''
  delayReason.value   = ''
  showDetailModal.value = true
  fetchLogs(item.id)
}

function closeDetail() {
  showDetailModal.value = false
  detailItem.value      = null
}

// ───────────────────────────────────────────────────────────────
// Status updates
// ───────────────────────────────────────────────────────────────
async function quickStatus(item, newStatus) {
  if (newStatus === 'delayed') {
    // open detail for delay reason input
    await openDetail(item)
    pendingStatus.value = 'delayed'
    return
  }
  await doStatusUpdate(item.id, newStatus, '')
  fetchRundowns()
}

function changeStatus(item, newStatus) {
  if (newStatus === 'delayed') {
    pendingStatus.value = 'delayed'
    return
  }
  doStatusUpdate(item.id, newStatus, '')
    .then(() => {
      detailItem.value.status = newStatus
      fetchRundowns()
    })
}

async function confirmStatus() {
  await doStatusUpdate(detailItem.value.id, 'delayed', delayReason.value)
  detailItem.value.status = 'delayed'
  pendingStatus.value = ''
  fetchRundowns()
  fetchLogs(detailItem.value.id)
}

async function doStatusUpdate(id, status, reason) {
  await axios.patch(`/rundowns/${id}/status`, { status, delay_reason: reason || undefined })
}

// ───────────────────────────────────────────────────────────────
// Delete
// ───────────────────────────────────────────────────────────────
async function confirmDelete(item) {
  if (!confirm(`Hapus sesi "${item.title}"?`)) return
  await axios.delete(`/rundowns/${item.id}`)
  fetchRundowns()
}

async function confirmDeleteFromDetail() {
  const item = detailItem.value
  if (!confirm(`Hapus sesi "${item.title}"?`)) return
  await axios.delete(`/rundowns/${item.id}`)
  closeDetail()
  fetchRundowns()
}

// ───────────────────────────────────────────────────────────────
// Formatting helpers
// ───────────────────────────────────────────────────────────────
function statusLabel(s) {
  return rundownStatuses.find(x => x.value === s)?.label ?? s
}

function statusColor(s) {
  const m = { pending: '#0ea5e9', ready: '#3b82f6', live: '#10b981', delayed: '#f59e0b', completed: '#6b7280' }
  return m[s] || '#0ea5e9'
}

function categoryColor(c) {
  const m = { ceremony: 'rgba(139,92,246,0.4)', talent: 'rgba(236,72,153,0.4)', technical: 'rgba(59,130,246,0.4)', logistics: 'rgba(245,158,11,0.4)', meal: 'rgba(16,185,129,0.4)', other: 'rgba(107,114,128,0.4)' }
  return m[c] || 'rgba(107,114,128,0.3)'
}

function formatDateTab(d) {
  const dt = new Date(d)
  return dt.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatDayHeader(d) {
  const dt = new Date(d)
  return dt.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

function formatDateShort(d) {
  if (!d) return '-'
  const dt = new Date(d)
  return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDatetime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
}

function formatLogAction(log) {
  if (log.action === 'created') return 'membuat rundown item ini'
  if (log.action === 'status_changed') {
    return `mengubah status: ${log.old_status} → ${log.new_status}`
  }
  return log.action
}

// ───────────────────────────────────────────────────────────────
// Lifecycle
// ───────────────────────────────────────────────────────────────
onMounted(async () => {
  await fetchEvents()
  // If a query param is passed from EventsView, pre-select
  const url = new URL(window.location.href)
  const eid = url.searchParams.get('event_id')
  if (eid) {
    filters.value.event_id = eid
    onEventChange()
  }
})
</script>

<style scoped>
/* ── Page ── */
.rundown-page { display: flex; flex-direction: column; gap: 16px; }

/* ── Header ── */
.page-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; }
.page-header h2 { margin: 0; font-size: 1.4rem; color: #fff; }
.page-header p  { margin: 4px 0 0; opacity: 0.6; font-size: 0.85rem; }

/* ── View toggle ── */
.view-toggle { display: flex; background: rgba(255,255,255,0.08); border-radius: 8px; padding: 3px; gap: 3px; }
.toggle-btn  { background: transparent; border: none; color: rgba(255,255,255,0.5); padding: 6px 10px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; transition: all 0.2s; }
.toggle-btn.active { background: rgba(14, 165, 233, 0.4); color: #fff; }

/* ── Filters ── */
.filters { display: flex; flex-wrap: wrap; gap: 10px; padding: 14px 20px; align-items: center; }

/* ── Date tabs ── */
.date-tabs { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 20px; }
.date-tab  { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 0.82rem; transition: all 0.2s; }
.date-tab:hover, .date-tab.active { background: rgba(14, 165, 233, 0.35); border-color: rgba(14, 165, 233, 0.6); color: #fff; }

/* ── Empty / loading ── */
.empty-state  { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 60px 20px; color: rgba(255,255,255,0.5); text-align: center; }
.loading-state{ display: flex; align-items: center; gap: 12px; padding: 40px 20px; color: rgba(255,255,255,0.6); justify-content: center; }
@keyframes spin { to { transform: rotate(360deg); } }
.spinner-icon { animation: spin 0.8s linear infinite; }

/* ── Day block (timeline) ── */
.day-block { margin-bottom: 8px; }
.day-header { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.5); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; padding-left: 4px; }
.day-count  { background: rgba(14, 165, 233, 0.25); color: #38bdf8; border-radius: 10px; padding: 1px 8px; font-size: 0.75rem; }

/* ── Timeline track ── */
.timeline-track  { display: flex; flex-direction: column; gap: 8px; }
.timeline-item   { display: flex; gap: 0; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; cursor: pointer; transition: all 0.2s; overflow: hidden; }
.timeline-item:hover { background: rgba(255,255,255,0.09); border-color: rgba(14, 165, 233, 0.4); transform: translateX(3px); }
.timeline-item.has-warning { border-left: 3px solid #fbbf24; }
.timeline-item.status-live { border-left: 3px solid #10b981; background: rgba(16,185,129,0.07); }
.timeline-item.status-delayed { border-left: 3px solid #f59e0b; }

/* ── Time column ── */
.time-col   { display: flex; flex-direction: column; align-items: center; padding: 14px 14px 14px 16px; gap: 4px; min-width: 68px; }
.time-start, .time-end { font-size: 0.75rem; color: rgba(255,255,255,0.7); font-variant-numeric: tabular-nums; }
.time-bar   { width: 2px; flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; min-height: 20px; }
.time-bar-fill { width: 100%; height: 100%; border-radius: 2px; opacity: 0.7; }

/* ── Item content ── */
.item-content { flex: 1; padding: 12px 14px; }
.item-top     { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; flex-wrap: wrap; }
.item-title   { margin: 0 0 6px; font-size: 0.95rem; color: #fff; }
.item-meta    { display: flex; gap: 12px; flex-wrap: wrap; font-size: 0.75rem; color: rgba(255,255,255,0.5); align-items: center; }
.item-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Quick actions ── */
.quick-actions    { display: flex; align-items: center; padding: 0 14px; }
.status-select-mini { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; padding: 5px 8px; font-size: 0.78rem; cursor: pointer; }

/* ── Badges ── */
.category-badge    { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; color: #fff; text-transform: capitalize; }
.category-badge-sm { font-size: 0.68rem; padding: 1px 7px; border-radius: 8px; color: #fff; text-transform: capitalize; }
.status-badge      { font-size: 0.72rem; padding: 2px 9px; border-radius: 10px; font-weight: 600; text-transform: capitalize; }
.badge-pending   { background: rgba(14, 165, 233, 0.3);  color: #38bdf8; }
.badge-ready     { background: rgba(59,130,246,0.3);  color: #93c5fd; }
.badge-live      { background: rgba(16,185,129,0.3);  color: #6ee7b7; }
.badge-delayed   { background: rgba(245,158,11,0.3);  color: #fcd34d; }
.badge-completed { background: rgba(107,114,128,0.3); color: #d1d5db; }

.warn-icon { color: #fbbf24; }
.delay-tag { background: rgba(245,158,11,0.2); color: #fcd34d; border-radius: 8px; padding: 1px 6px; }
.delay-tag-sm { font-size: 0.7rem; background: rgba(245,158,11,0.2); color: #fcd34d; border-radius: 6px; padding: 0 5px; margin-left: 4px; }

/* ── Table view ── */
.table-wrapper { padding: 0; overflow-x: auto; }
.data-table    { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th { padding: 12px 16px; text-align: left; color: rgba(255,255,255,0.5); font-weight: 500; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.06); }
.data-table td { padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; color: rgba(255,255,255,0.85); }
.data-table tr:hover td { background: rgba(255,255,255,0.03); }
.time-cell     { font-variant-numeric: tabular-nums; color: rgba(255,255,255,0.6); font-size: 0.8rem; }
.item-title-cell { color: #fff; font-weight: 500; }
.action-cell   { display: flex; gap: 4px; }
.text-dim      { color: rgba(255,255,255,0.3); }
.text-ok       { color: #10b981; }
.text-warn     { color: #fbbf24; }

/* ── Buttons ── */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; }
.btn-primary   { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; }
.btn-primary:hover   { opacity: 0.9; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.15); }
.btn-secondary:hover { background: rgba(255,255,255,0.15); }
.btn-danger-sm { background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; }
.btn-icon      { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); padding: 6px 8px; border-radius: 7px; cursor: pointer; display: inline-flex; align-items: center; transition: all 0.2s; }
.btn-icon:hover      { background: rgba(255,255,255,0.14); color: #fff; }
.btn-icon.btn-danger { color: #f87171; }
.btn-icon.btn-danger:hover { background: rgba(239,68,68,0.2); }

/* ── Form modal ── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal        { width: 100%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; border-radius: 24px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); border: 1px solid var(--glass-border); box-shadow: 0 25px 50px rgba(0,0,0,0.5); -ms-overflow-style: none; scrollbar-width: none; }
.modal::-webkit-scrollbar { display: none; }
.modal-lg     { max-width: 760px; }
.modal-xl     { max-width: 1000px; }
.modal-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 24px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.modal-header h3 { margin: 0; font-size: 1.1rem; color: #fff; display: flex; align-items: center; gap: 8px; }
.modal-body   { overflow-y: auto; padding: 20px 24px; flex: 1; -ms-overflow-style: none; scrollbar-width: none; }
.modal-body::-webkit-scrollbar { display: none; }
.modal-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: flex-end; gap: 10px; }


/* ── Form grid ── */
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group  { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 0.8rem; color: rgba(255,255,255,0.6); }
.full-width  { grid-column: 1 / -1; }
.glass-input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); color: #fff; border-radius: 8px; padding: 8px 12px; font-size: 0.88rem; width: 100%; box-sizing: border-box; }
.glass-input:focus { outline: none; border-color: var(--primary); background: rgba(255,255,255,0.1); }
.glass-input option { background: #13243d; color: #fff; }

/* ── Dep selector ── */
.dep-selector { display: flex; gap: 8px; align-items: center; }
.dep-list     { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.dep-chip     { display: inline-flex; align-items: center; gap: 5px; background: rgba(14, 165, 233, 0.2); border: 1px solid rgba(14, 165, 233, 0.3); color: #38bdf8; border-radius: 10px; padding: 3px 10px; font-size: 0.78rem; }
.dep-chip.dep-done { background: rgba(16,185,129,0.15); border-color: rgba(16,185,129,0.3); color: #6ee7b7; }
.dep-chip.dep-warn { background: rgba(245,158,11,0.15); border-color: rgba(245,158,11,0.3); color: #fcd34d; }
.dep-remove   { background: none; border: none; cursor: pointer; color: inherit; padding: 0; display: flex; align-items: center; opacity: 0.6; }
.dep-remove:hover { opacity: 1; }
.dep-status   { opacity: 0.6; font-size: 0.7rem; }
.dep-warning-text { font-size: 0.8rem; color: #fbbf24; margin-top: 8px; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); border-radius: 8px; padding: 8px 12px; }

/* ── Detail layout ── */
.detail-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
.detail-left, .detail-right { display: flex; flex-direction: column; gap: 16px; }
.detail-section { background: rgba(255,255,255,0.04); border-radius: 10px; padding: 14px 16px; }
.detail-section h5 { margin: 0 0 10px; font-size: 0.82rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.06em; display: flex; align-items: center; gap: 6px; }
.detail-section p  { margin: 0; font-size: 0.88rem; color: rgba(255,255,255,0.8); line-height: 1.5; }
.meta-grid { display: grid; grid-template-columns: 110px 1fr; gap: 7px 12px; font-size: 0.84rem; }
.meta-label { color: rgba(255,255,255,0.45); }

/* ── Status pills ── */
.status-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.status-pill  { border: none; border-radius: 10px; padding: 5px 14px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; opacity: 0.5; }
.status-pill.active, .status-pill:hover { opacity: 1; }
.pill-pending   { background: rgba(14, 165, 233, 0.3); color: #38bdf8; }
.pill-ready     { background: rgba(59,130,246,0.3); color: #93c5fd; }
.pill-live      { background: rgba(16,185,129,0.3); color: #6ee7b7; }
.pill-delayed   { background: rgba(245,158,11,0.3); color: #fcd34d; }
.pill-completed { background: rgba(107,114,128,0.3); color: #d1d5db; }
.pill-pending.active   { background: rgba(14, 165, 233, 0.6); }
.pill-ready.active     { background: rgba(59,130,246,0.6); }
.pill-live.active      { background: rgba(16,185,129,0.6); }
.pill-delayed.active   { background: rgba(245,158,11,0.6); }
.pill-completed.active { background: rgba(107,114,128,0.6); }

/* ── Log list ── */
.log-list   { display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto; }
.log-item   { display: flex; gap: 10px; align-items: flex-start; }
.log-dot    { width: 8px; height: 8px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
.dot-created   { background: #0ea5e9; }
.dot-pending   { background: #0ea5e9; }
.dot-ready     { background: #3b82f6; }
.dot-live      { background: #10b981; }
.dot-delayed   { background: #f59e0b; }
.dot-completed { background: #6b7280; }
.log-content{ display: flex; flex-direction: column; gap: 2px; }
.log-user   { font-size: 0.82rem; color: #fff; font-weight: 500; }
.log-action { font-size: 0.8rem; color: rgba(255,255,255,0.6); }
.log-reason { font-size: 0.78rem; color: #fcd34d; font-style: italic; }
.log-time   { font-size: 0.72rem; color: rgba(255,255,255,0.35); }

/* ── Glass card base ── */
.glass-card { background: rgba(255,255,255,0.07); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; }

@media (max-width: 768px) {
  .form-grid     { grid-template-columns: 1fr; }
  .detail-layout { grid-template-columns: 1fr; }
  .filters { flex-direction: column; align-items: stretch; }
}

.print-only-container {
  display: none;
}

@media print {
  /* Show only the print-only rundown container and its children */
  body * {
    visibility: hidden;
  }
  
  .print-only-container, .print-only-container * {
    visibility: visible;
  }
  
  .print-only-container {
    position: static !important;
    width: 100% !important;
    background: #fff !important;
    color: #000 !important;
    display: block !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
  }

  /* Make sure the main screen container is completely hidden */
  .rundown-page > *:not(.print-only-container) {
    display: none !important;
  }

  .day-block-print {
    margin-bottom: 28px;
    page-break-inside: avoid;
  }

  .day-header-print {
    font-size: 1.2rem;
    font-weight: bold;
    color: #000;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 12px;
    text-transform: uppercase;
  }

  .print-list {
    display: flex;
    flex-direction: column;
  }

  .print-item {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #ccc;
    page-break-inside: avoid;
  }

  .print-time-col {
    width: 160px;
    font-weight: bold;
    font-size: 0.95rem;
    color: #000;
    flex-shrink: 0;
  }

  .print-content-col {
    flex: 1;
  }

  .print-item-title {
    font-size: 1.05rem;
    font-weight: bold;
    color: #000;
    margin-bottom: 4px;
  }

  .print-item-meta {
    font-size: 0.85rem;
    color: #333;
  }
}
</style>

<style>
@media print {
  .sidebar, .topbar, .sidebar-overlay, .collapse-btn, .mobile-toggle-btn, .floating-chat-container, .floating-chat-trigger, #floating-chat, .no-print {
    display: none !important;
  }

  html, body, #app, .layout, .main-wrap, .main-content, .rundown-page {
    background: #ffffff !important;
    background-image: none !important;
    box-shadow: none !important;
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
  }
}
</style>
