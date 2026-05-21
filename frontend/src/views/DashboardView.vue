<template>
  <div class="dashboard-page">
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
            <span class="badge" :class="`badge-${event.status}`">{{ event.status }}</span>
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
          <div v-for="task in myTasks" :key="task.id" class="task-mini-item">
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
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowRight,
  Loader2,
  CheckSquare,
  CalendarDays,
  Activity,
  Users,
  CheckCircle2,
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const recentEvents = ref([])
const loadingEvents = ref(true)
const myTasks = ref([])
const loadingTasks = ref(true)
const totalUsers = ref(0)
const totalEvents = ref(0)
const activeEvents = ref(0)
const completedEvents = ref(0)

const statusLabels = { pending: 'Pending', in_progress: 'In Progress', review: 'Review', completed: 'Completed', cancelled: 'Cancelled' }
function statusLabel(v) { return statusLabels[v] || v }

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const stats = ref([
  { label: 'Total Event', value: 0, icon: CalendarDays, tone: 'event' },
  { label: 'Event Aktif', value: 0, icon: Activity, tone: 'active' },
  { label: 'Total User', value: 0, icon: Users, tone: 'user' },
  { label: 'Selesai', value: 0, icon: CheckCircle2, tone: 'done' },
])

onMounted(async () => {
  try {
    const [evRes, usrRes, taskRes] = await Promise.all([
      api.get('/events?per_page=5'),
      auth.canManageUsers ? api.get('/users?per_page=1') : Promise.resolve(null),
      api.get('/my-tasks'),
    ])
    recentEvents.value = evRes.data.data
    totalEvents.value = evRes.data.total
    activeEvents.value = evRes.data.data.filter(e => e.status === 'active' || e.status === 'ongoing').length
    completedEvents.value = evRes.data.data.filter(e => e.status === 'completed').length

    if (usrRes) totalUsers.value = usrRes.data.total

    myTasks.value = taskRes.data || []
    stats.value[0].value = totalEvents.value
    stats.value[1].value = activeEvents.value
    stats.value[2].value = totalUsers.value || '—'
    stats.value[3].value = completedEvents.value
  } finally {
    loadingEvents.value = false
    loadingTasks.value = false
  }
})
</script>

<style scoped>
.dashboard-page { display: flex; flex-direction: column; gap: 20px; }

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
.tone-event { color: #a5b4fc; }
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
.card-header h3 { font-size: 16px; font-weight: 600; }

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
.dot-completed { background: #6366f1; }
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
.pdot-medium { background: #6366f1; }
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
</style>
