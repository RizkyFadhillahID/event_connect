<template>
  <div class="dashboard-page">
    
    <!-- 1. Welcome Hero Banner -->
    <!-- <div class="welcome-hero glass-card">
      <div class="hero-left">
        <h1>Selamat datang kembali, {{ auth.user?.name }}! 👋</h1>
        <p>
          Hari ini Anda memiliki <strong style="color: #67e8f9">{{ myTasks.length }}</strong> tugas aktif dan
          <strong style="color: #67e8f9">{{ activeEvents }}</strong> event yang sedang berlangsung. Mari buat event hari ini luar biasa!
        </p>
      </div>
      <div class="hero-right hide-on-mobile">
        <div class="hero-chip">
          <Activity :size="16" style="color: #67e8f9" />
          <span>{{ formatRole(auth.user?.role) }}</span>
        </div>
      </div>
    </div> -->

    <!-- Stats row -->
    <div class="stats-grid">
      <div class="stat-card glass-card" v-for="s in stats" :key="s.label">
        <div class="stat-icon" :class="`tone-${s.tone}`">
          <component :is="s.icon" :size="18" :stroke-width="2.25" />
        </div>
        <div class="stat-info">
          <div class="stat-value">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- Content grid -->
    <div class="content-grid">
      <!-- Recent events -->
      <div class="glass-card content-card">
        <div class="card-header">
          <h3>Event Terbaru</h3>
          <RouterLink to="/events" class="btn btn-glass btn-sm">
            <ArrowRight :size="16" />
            Lihat Semua
          </RouterLink>
        </div>
        <div v-if="loadingEvents" class="loading-state">
          <Loader2 class="spinner-icon" :size="24" />
        </div>
        <div v-else class="event-list">
          <div class="event-item" v-for="event in recentEvents" :key="event.id">
            <div class="event-dot" :class="`dot-${event.status}`"></div>
            <div class="event-info">
              <div class="event-name">{{ event.name }}</div>
              <div class="event-meta">{{ event.location }} · {{ formatDate(event.start_date) }}</div>
            </div>
            <span class="badge" :class="`badge-${event.status}`">{{ eventStatusLabel(event.status) }}</span>
          </div>
          <div v-if="recentEvents.length === 0" class="empty-state">Belum ada event</div>
        </div>
      </div>

      <!-- My Tasks -->
      <div class="glass-card content-card">
        <div class="card-header">
          <h3>Task Saya</h3>
          <RouterLink to="/tasks" class="btn btn-glass btn-sm">
            <ArrowRight :size="16" />
            Semua Task
          </RouterLink>
        </div>
        <div v-if="loadingTasks" class="loading-state">
          <Loader2 class="spinner-icon" :size="24" />
        </div>
        <div v-else-if="!myTasks.length" class="empty-state" style="padding:24px;text-align:center;color:var(--text-muted)">
          <CheckSquare :size="32" style="opacity:0.3;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto" />
          Tidak ada task aktif
        </div>
        <div v-else class="task-mini-list">
          <div v-for="task in myTasks.slice(0, 4)" :key="task.id" class="task-mini-item">
            <span class="priority-dot" :class="`pdot-${task.priority}`"></span>
            <div class="task-mini-info">
              <div class="task-mini-title">{{ task.title }}</div>
              <div class="task-mini-meta">
                <span v-if="task.event">{{ task.event.name }}</span>
                <span v-if="task.due_date" :class="{ overdue: task.is_overdue }">
                  · {{ formatDate(task.due_date) }}
                  <span v-if="task.is_overdue" style="color:#fca5a5"> (Overdue)</span>
                </span>
              </div>
            </div>
            <span class="task-mini-status" :class="`ts-${task.status}`">{{ statusLabel(task.status) }}</span>
          </div>
        </div>
      </div>

      <!-- Logistics Gudang Summary (NEW Premium) -->
      <div class="glass-card content-card">
        <div class="card-header">
          <h3>Logistik &amp; Status Gudang</h3>
          <RouterLink to="/logistics" class="btn btn-glass btn-sm">
            <ArrowRight :size="16" />
            Ke Gudang
          </RouterLink>
        </div>
        <div v-if="loadingLogistics" class="loading-state">
          <Loader2 class="spinner-icon" :size="24" />
        </div>
        <div v-else class="logistics-dashboard-body">
          <div class="logistics-dashboard-stats">
            <div class="ld-stat">
              <span class="ld-val">{{ totalInventoryUnits }}</span>
              <span class="ld-lbl">Total Aset</span>
            </div>
            <div class="ld-stat">
              <span class="ld-val" style="color:#6ee7b7">{{ readyInventoryUnits }}</span>
              <span class="ld-lbl">Ready Gudang</span>
            </div>
            <div class="ld-stat">
              <span class="ld-val" style="color:#38bdf8">{{ deployedInventoryUnits }}</span>
              <span class="ld-lbl">Di Lapangan</span>
            </div>
            <div class="ld-stat">
              <span class="ld-val" style="color:#fcd34d">{{ maintenanceInventoryUnits }}</span>
              <span class="ld-lbl">Perawatan</span>
            </div>
          </div>

          <div class="logistics-ratio-section" style="margin-top: 16px;">
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px; color:var(--text-secondary)">
              <span>Rasio Utilisasi Aset di Lapangan</span>
              <strong style="color: #67e8f9;">{{ utilizationRate }}% Terpakai</strong>
            </div>
            <div class="bp-track" style="height:8px; background:rgba(255,255,255,0.08); border-radius:4px; overflow:hidden;">
              <div class="bp-fill" :style="`width: ${utilizationRate}%; height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); transition:width 0.4s;`"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Prioritas & Analisis Kerja (NEW Premium Chart) -->
      <div class="glass-card content-card">
        <div class="card-header">
          <h3>Prioritas &amp; Analisis Kerja Saya</h3>
          <span style="font-size: 11px; font-weight:600; padding:2px 8px; background:rgba(14,165,233,0.15); border-radius:12px; color:#38bdf8">
            {{ completedMyTasks }} / {{ myTasks.length }} Selesai
          </span>
        </div>
        <div v-if="loadingTasks" class="loading-state">
          <Loader2 class="spinner-icon" :size="24" />
        </div>
        <div v-else class="analytics-dashboard-body">
          <div class="task-progress-overview" style="display:flex; align-items:center; gap:20px; margin-bottom:18px;">
            <div class="circle-progress" :style="`width:70px; height:70px; background: radial-gradient(closest-side, #0f172a 79%, transparent 80% 100%), conic-gradient(var(--primary) ${myTasks.length ? Math.round((completedMyTasks / myTasks.length) * 100) : 0}%, rgba(255,255,255,0.1) 0)`">
              <span class="circle-val" style="font-size:14px">{{ myTasks.length ? Math.round((completedMyTasks / myTasks.length) * 100) : 0 }}%</span>
            </div>
            <div style="font-size:12px; line-height:1.5; color:var(--text-secondary)">
              <div style="font-weight:600; color:#fff; font-size:13px; margin-bottom:4px">Rasio Progres Kerja Anda</div>
              <div>Selesaikan tugas bernomor prioritas tinggi untuk menjaga ritme kerja tim.</div>
            </div>
          </div>

          <div class="priority-progress-bars" style="display:flex; flex-direction:column; gap:10px;">
            <div class="p-bar-item">
              <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px">
                <span style="color:#ef4444; font-weight:600">Urgent &amp; High Priority</span>
                <span style="color:var(--text-secondary)">{{ urgentHighMyTasks }} tugas</span>
              </div>
              <div class="bp-track" style="height:6px; background:rgba(255,255,255,0.06); border-radius:3px; overflow:hidden">
                <div class="bp-fill" :style="`width: ${myTasks.length ? (urgentHighMyTasks / myTasks.length) * 100 : 0}%; height:100%; background:#ef4444;`"></div>
              </div>
            </div>
            <div class="p-bar-item">
              <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px">
                <span style="color:var(--primary); font-weight:600">Medium Priority</span>
                <span style="color:var(--text-secondary)">{{ mediumMyTasks }} tugas</span>
              </div>
              <div class="bp-track" style="height:6px; background:rgba(255,255,255,0.06); border-radius:3px; overflow:hidden">
                <div class="bp-fill" :style="`width: ${myTasks.length ? (mediumMyTasks / myTasks.length) * 100 : 0}%; height:100%; background:var(--primary);`"></div>
              </div>
            </div>
            <div class="p-bar-item">
              <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px">
                <span style="color:#6b7280; font-weight:600">Low Priority</span>
                <span style="color:var(--text-secondary)">{{ lowMyTasks }} tugas</span>
              </div>
              <div class="bp-track" style="height:6px; background:rgba(255,255,255,0.06); border-radius:3px; overflow:hidden">
                <div class="bp-fill" :style="`width: ${myTasks.length ? (lowMyTasks / myTasks.length) * 100 : 0}%; height:100%; background:#6b7280;`"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analisis Keuangan & Anggaran Event (NEW Premium Chart) -->
      <div class="glass-card content-card financial-card" style="grid-column: span 2;">
        <div class="card-header">
          <h3>Analisis Anggaran &amp; Keuangan Event</h3>
          <span style="font-size: 11px; font-weight:600; padding:2px 8px; background:rgba(16,185,129,0.15); border-radius:12px; color:#10b981; display:flex; align-items:center; gap:4px">
            <DollarSign :size="12" /> Top 5 Event Terbaru
          </span>
        </div>
        <div v-if="loadingFinancials" class="loading-state">
          <Loader2 class="spinner-icon" :size="24" />
        </div>
        <div v-else-if="!financialEvents.length" class="empty-state" style="padding:24px;text-align:center;color:var(--text-muted)">
          <DollarSign :size="32" style="opacity:0.3;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto" />
          Belum ada data anggaran event
        </div>
        <div v-else class="financial-list">
          <div v-for="event in financialEvents" :key="event.id" class="financial-event-row">
            <div class="fe-header">
              <span class="fe-title">{{ event.name }}</span>
              <div class="fe-amounts">
                <span class="fe-spent">Terpakai: <strong>{{ formatCurrency(event.total_spent || 0) }}</strong></span>
                <span class="fe-divider">/</span>
                <span class="fe-budget">Anggaran: {{ formatCurrency(event.budget || 0) }}</span>
                <span class="fe-percent" :class="{ 'over-budget': (event.total_spent || 0) > (event.budget || 0) }">
                  ({{ calculatePercent(event.total_spent || 0, event.budget || 0) }}%)
                </span>
              </div>
            </div>
            <div class="fe-bar-container">
              <svg class="fe-svg-bar" width="100%" height="10" viewBox="0 0 100 10" preserveAspectRatio="none">
                <defs>
                  <linearGradient :id="'grad-safe-' + event.id" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#06b6d4" />
                    <stop offset="100%" stop-color="#10b981" />
                  </linearGradient>
                  <linearGradient :id="'grad-warn-' + event.id" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#f59e0b" />
                    <stop offset="100%" stop-color="#ef4444" />
                  </linearGradient>
                </defs>
                <!-- Track -->
                <rect x="0" y="0" width="100" height="10" rx="5" fill="rgba(255, 255, 255, 0.08)" />
                <!-- Fill -->
                <rect x="0" y="0" 
                  :width="Math.min(calculatePercent(event.total_spent || 0, event.budget || 0), 100)" 
                  height="10" rx="5" 
                  :fill="(event.total_spent || 0) > (event.budget || 0) ? `url(#grad-warn-${event.id})` : `url(#grad-safe-${event.id})`" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowRight,
  Loader2,
  CheckSquare,
  CalendarDays,
  Activity,
  Users,
  CheckCircle2,
  Boxes,
  DollarSign
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const recentEvents = ref([])
const loadingEvents = ref(true)
const myTasks = ref([])
const loadingTasks = ref(true)
const loadingLogistics = ref(true)
const financialEvents = ref([])
const loadingFinancials = ref(true)

const totalUsers = ref(0)
const totalEvents = ref(0)
const activeEvents = ref(0)
const completedEvents = ref(0)

// Logistics ref states
const totalInventoryUnits = ref(0)
const readyInventoryUnits = ref(0)
const deployedInventoryUnits = ref(0)
const maintenanceInventoryUnits = ref(0)

const statusLabels = { pending: 'Pending', in_progress: 'In Progress', review: 'Review', completed: 'Completed', cancelled: 'Cancelled' }
function statusLabel(v) { return statusLabels[v] || v }

const eventStatuses = { draft: 'Draft', active: 'Aktif', ongoing: 'Berlangsung', completed: 'Selesai', cancelled: 'Dibatalkan' }
function eventStatusLabel(v) { return eventStatuses[v] || v }

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v) }

const stats = ref([
  { label: 'Total Event', value: 0, icon: CalendarDays, tone: 'event' },
  { label: 'Event Aktif', value: 0, icon: Activity, tone: 'active' },
  { label: 'Total User', value: 0, icon: Users, tone: 'user' },
  { label: 'Selesai', value: 0, icon: CheckCircle2, tone: 'done' },
])

const roleLabels = {
  superadmin: 'Super Admin',
  project_manager: 'Project Manager',
  staff: 'Staff / Personnel',
  event_planner: 'Event Planner',
  promotion_team: 'Promotion Team',
  partnership_manager: 'Partnership Manager',
  budgeting: 'Budgeting',
  operations_team: 'Operations Team',
  creative_team: 'Creative Team',
  rundown_coordinator: 'Rundown Coordinator',
  talent_coordinator: 'Talent Coordinator',
  registration_guest_management: 'Registration & Guest Mgmt',
  technical_team: 'Technical Team',
  documentation_team: 'Documentation Team',
  liaison_officer: 'Liaison Officer (LO)',
}
function formatRole(role) {
  return roleLabels[role] || role
}

// Computeds for logistics
const utilizationRate = computed(() => {
  if (!totalInventoryUnits.value) return 0
  return Math.round((deployedInventoryUnits.value / totalInventoryUnits.value) * 100)
})

// Computeds for my tasks
const completedMyTasks = computed(() => {
  return myTasks.value.filter(t => t.status === 'completed').length
})
const urgentHighMyTasks = computed(() => {
  return myTasks.value.filter(t => t.priority === 'urgent' || t.priority === 'high').length
})
const mediumMyTasks = computed(() => {
  return myTasks.value.filter(t => t.priority === 'medium').length
})
const lowMyTasks = computed(() => {
  return myTasks.value.filter(t => t.priority === 'low').length
})

function calculatePercent(spent, budget) {
  if (!budget) return 0
  return Math.round((spent / budget) * 100)
}

onMounted(async () => {
  try {
    const [evRes, usrRes, taskRes, invRes, finRes] = await Promise.all([
      api.get('/events?per_page=5'),
      auth.canManageUsers ? api.get('/users?per_page=1') : api.get('/users-list').catch(() => null),
      api.get('/my-tasks'),
      api.get('/inventories?paginate=false').catch(() => ({ data: [] })),
      api.get('/dashboard/financials').catch(() => ({ data: [] }))
    ])
    
    recentEvents.value = evRes.data.data
    totalEvents.value = evRes.data.total
    activeEvents.value = evRes.data.data.filter(e => e.status === 'active' || e.status === 'ongoing').length
    completedEvents.value = evRes.data.data.filter(e => e.status === 'completed').length

    if (usrRes && usrRes.data) {
      if (typeof usrRes.data.total !== 'undefined') {
        totalUsers.value = usrRes.data.total
      } else if (Array.isArray(usrRes.data)) {
        totalUsers.value = usrRes.data.length
      }
    }

    myTasks.value = taskRes.data || []
    
    // Set Stats Row
    stats.value[0].value = totalEvents.value
    stats.value[1].value = activeEvents.value
    stats.value[2].value = totalUsers.value || '—'
    stats.value[3].value = completedEvents.value

    // Calculate inventories units
    if (invRes && invRes.data) {
      const items = invRes.data
      totalInventoryUnits.value = items.reduce((sum, item) => sum + Number(item.total_quantity || 0), 0)
      readyInventoryUnits.value = items.reduce((sum, item) => sum + Number(item.available_quantity || 0), 0)
      deployedInventoryUnits.value = totalInventoryUnits.value - readyInventoryUnits.value
      maintenanceInventoryUnits.value = items.filter(item => item.status === 'maintenance' || item.status === 'damaged').reduce((sum, item) => sum + Number(item.total_quantity || 0), 0)
    }

    if (finRes && finRes.data) {
      financialEvents.value = finRes.data
    }
  } catch (e) {
    console.error('Failed to load dashboard statistics:', e)
  } finally {
    loadingEvents.value = false
    loadingTasks.value = false
    loadingLogistics.value = false
    loadingFinancials.value = false
  }
})
</script>

<style scoped>
.dashboard-page { display: flex; flex-direction: column; gap: 20px; }

/* Welcome Hero Styling */
.welcome-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(13, 148, 136, 0.2)) !important;
  border: 1px solid rgba(14, 165, 233, 0.3) !important;
  border-radius: 20px;
}
.hero-left h1 {
  font-size: 20px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 6px;
}
.hero-left p {
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}
.hero-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
}
@media (max-width: 600px) {
  .welcome-hero {
    padding: 20px;
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .hide-on-mobile {
    display: none !important;
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
}
.stat-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.12);
  flex-shrink: 0;
}
.tone-event { color: #38bdf8; }
.tone-active { color: #67e8f9; }
.tone-user { color: #6ee7b7; }
.tone-done { color: #fcd34d; }
.stat-value { font-size: 22px; font-weight: 700; line-height: 1.1; }
.stat-label { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 768px) { .content-grid { grid-template-columns: 1fr; } }

.content-card { padding: 24px; }
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.card-header h3 { font-size: 16px; font-weight: 600; margin: 0; }

.loading-state { display: flex; justify-content: center; padding: 24px; }

.event-list { display: flex; flex-direction: column; gap: 14px; }
.event-item { display: flex; align-items: center; gap: 12px; }
.event-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.dot-active { background: #10b981; box-shadow: 0 0 8px #10b981; }
.dot-draft { background: #9ca3af; }
.dot-ongoing { background: #06b6d4; box-shadow: 0 0 8px #06b6d4; }
.dot-completed { background: var(--primary); }
.dot-cancelled { background: #ef4444; }
.event-info { flex: 1; min-width: 0; }
.event-name { font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.event-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.empty-state { color: var(--text-muted); font-size: 14px; text-align: center; padding: 20px; }

/* My Tasks mini list */
.task-mini-list { display: flex; flex-direction: column; gap: 10px; }
.task-mini-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.06); }
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pdot-urgent { background: #ef4444; box-shadow: 0 0 6px #ef4444; }
.pdot-high   { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }
.pdot-medium { background: var(--primary); }
.pdot-low    { background: #6b7280; }
.task-mini-info { flex: 1; min-width: 0; }
.task-mini-title { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.task-mini-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.task-mini-meta .overdue { color: #fca5a5; }
.task-mini-status { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.ts-pending     { background: rgba(107,114,128,0.2); color: #d1d5db; }
.ts-in_progress { background: rgba(6,182,212,0.2);  color: #67e8f9; }
.ts-review      { background: rgba(245,158,11,0.2); color: #fcd34d; }
.ts-completed   { background: rgba(16,185,129,0.2); color: #6ee7b7; }
.ts-cancelled   { background: rgba(239,68,68,0.2);  color: #fca5a5; }

/* Logistics Summary styling */
.logistics-dashboard-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}
.ld-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 4px;
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 10px;
  text-align: center;
}
.ld-val {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
}
.ld-lbl {
  font-size: 9px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-top: 4px;
  letter-spacing: 0.02em;
}

/* Analytics Circle Progress styling */
.circle-progress {
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  flex-shrink: 0;
}
.circle-val {
  font-weight: 700;
  color: #fff;
}
.bp-track {
  background: rgba(255,255,255,0.06);
  border-radius: 3px;
  overflow: hidden;
}
.bp-fill {
  height: 100%;
  transition: width 0.4s ease;
}

.financial-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.financial-event-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  transition: all 0.3s ease;
}
.financial-event-row:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.08);
  transform: translateY(-1px);
}
.fe-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.fe-title {
  font-size: 14px;
  font-weight: 600;
  color: #fff;
}
.fe-amounts {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--text-secondary);
}
.fe-spent strong {
  color: #67e8f9;
}
.fe-divider {
  color: rgba(255, 255, 255, 0.2);
}
.fe-percent {
  font-weight: 600;
  color: #10b981;
}
.fe-percent.over-budget {
  color: #ef4444;
  text-shadow: 0 0 6px rgba(239, 68, 68, 0.3);
}
.fe-bar-container {
  width: 100%;
  border-radius: 5px;
  overflow: hidden;
}
.fe-svg-bar {
  display: block;
}
</style>
