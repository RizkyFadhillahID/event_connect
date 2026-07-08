<template>
  <div class="dashboard-view">
    <!-- Loading skeleton -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p style="margin-top: 12px; color: var(--text-secondary);">Memuat statistik platform...</p>
    </div>

    <div v-else>
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card glass-card">
          <div class="sc-header">
            <Building2 :size="24" class="c-blue" />
            <span class="sc-title">Total Customer (EO)</span>
          </div>
          <div class="sc-value">{{ stats.total_organizations }}</div>
          <div class="sc-sub">Jumlah keseluruhan Event Organizer</div>
        </div>

        <div class="stat-card glass-card">
          <div class="sc-header">
            <CheckCircle2 :size="24" class="c-teal" />
            <span class="sc-title">EO Aktif</span>
          </div>
          <div class="sc-value">{{ stats.active_organizations }}</div>
          <div class="sc-sub">EO dengan status langganan aktif</div>
        </div>

        <div class="stat-card glass-card">
          <div class="sc-header">
            <AlertOctagon :size="24" class="c-amber" />
            <span class="sc-title">EO Suspended</span>
          </div>
          <div class="sc-value">{{ stats.suspended_organizations }}</div>
          <div class="sc-sub">EO ditangguhkan sementara</div>
        </div>

        <div class="stat-card glass-card">
          <div class="sc-header">
            <Users2 :size="24" class="c-purple" />
            <span class="sc-title">Total User (SaaS)</span>
          </div>
          <div class="sc-value">{{ stats.total_users }}</div>
          <div class="sc-sub">Gabungan semua user dari seluruh EO</div>
        </div>

        <div class="stat-card glass-card">
          <div class="sc-header">
            <CalendarRange :size="24" class="c-cyan" />
            <span class="sc-title">Total Event Managed</span>
          </div>
          <div class="sc-value">{{ stats.total_events }}</div>
          <div class="sc-sub">Total kegiatan yang dikelola di sistem</div>
        </div>
      </div>

      <!-- Detail section -->
      <div class="detail-section">
        <!-- Recent EOs -->
        <div class="detail-card glass-card">
          <h3 class="dc-title">Customer Baru Terdaftar</h3>
          <div class="list-container">
            <div v-for="org in stats.recent_organizations" :key="org.id" class="list-item">
              <div class="item-left">
                <div class="item-avatar avatar-blue">
                  {{ org.name ? org.name.charAt(0).toUpperCase() : 'E' }}
                </div>
                <div class="item-info">
                  <div class="item-name" :title="org.name">{{ org.name }}</div>
                  <div class="item-sub">{{ org.email }}</div>
                </div>
              </div>
              <div class="item-right">
                <span :class="['badge', `badge-${org.status}`]">{{ formatStatus(org.status) }}</span>
                <span class="item-date">{{ formatDate(org.created_at) }}</span>
              </div>
            </div>
            <div v-if="!stats.recent_organizations?.length" class="empty-state">
              Belum ada EO yang terdaftar.
            </div>
          </div>
        </div>

        <!-- Top EOs -->
        <div class="detail-card glass-card">
          <h3 class="dc-title">EO Paling Aktif (Event Terbanyak)</h3>
          <div class="list-container">
            <div v-for="org in stats.top_organizations" :key="org.id" class="list-item">
              <div class="item-left">
                <div class="item-avatar avatar-teal">
                  {{ org.name ? org.name.charAt(0).toUpperCase() : 'E' }}
                </div>
                <div class="item-info">
                  <div class="item-name" :title="org.name">{{ org.name }}</div>
                  <div class="item-sub">{{ org.email }}</div>
                </div>
              </div>
              <div class="item-right progress-col">
                <div class="progress-info">
                  <span class="progress-count"><strong>{{ org.events_count }}</strong> / {{ org.max_events }} event</span>
                  <span class="progress-percentage">{{ org.max_events > 0 ? Math.round(org.events_count / org.max_events * 100) : 0 }}%</span>
                </div>
                <div class="progress-bar-bg">
                  <div class="progress-bar-fill" :style="{ width: Math.min(org.max_events > 0 ? Math.round(org.events_count / org.max_events * 100) : 0, 100) + '%' }"></div>
                </div>
              </div>
            </div>
            <div v-if="!stats.top_organizations?.length" class="empty-state">
              Belum ada EO yang terdaftar.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Building2, CheckCircle2, AlertOctagon, Users2, CalendarRange } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const loading = ref(true)
const stats = ref({
  total_organizations: 0,
  active_organizations: 0,
  suspended_organizations: 0,
  inactive_organizations: 0,
  total_users: 0,
  total_events: 0,
  recent_organizations: [],
  top_organizations: [],
})

const roleLabels = {
  owner: 'Platform Owner',
  admin: 'Platform Admin',
  support: 'Platform Support',
}

function formatRole(role) {
  return roleLabels[role] || role
}

function formatStatus(status) {
  const labels = {
    active: 'Aktif',
    suspended: 'Ditangguhkan',
    inactive: 'Nonaktif'
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

async function fetchStats() {
  loading.value = true
  try {
    const res = await api.get('/dashboard')
    stats.value = res.data
  } catch (err) {
    console.error('Gagal mengambil data dashboard:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.welcome-card {
  padding: 32px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(13, 148, 136, 0.15));
  border-color: rgba(14, 165, 233, 0.25);
  margin-bottom: 28px;
}
.welcome-card h1 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #f8fafc, #38bdf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.welcome-card p {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 28px;
}
.stat-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
}
.sc-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.sc-title {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
}
.sc-value {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 6px;
}
.sc-sub {
  font-size: 11px;
  color: var(--text-muted);
}

.c-blue { color: #0ea5e9; }
.c-teal { color: #10b981; }
.c-amber { color: #f59e0b; }
.c-purple { color: #a855f7; }
.c-cyan { color: #06b6d4; }

/* Details */
.detail-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 1024px) {
  .detail-section {
    grid-template-columns: 1fr;
  }
}
.detail-card {
  padding: 24px;
}
.dc-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 20px;
  border-left: 3px solid var(--primary);
  padding-left: 10px;
}

/* Details list styling */
.list-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  transition: all 0.3s ease;
  gap: 16px;
}
.list-item:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.08);
  transform: translateY(-1px);
}
.item-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0; /* allows text truncation */
  flex: 1;
}
.item-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.avatar-blue {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
}
.avatar-teal {
  background: linear-gradient(135deg, #0d9488, #0f766e);
}
.item-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.item-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.item-sub {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.item-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  flex-shrink: 0;
}
.item-date {
  font-size: 11px;
  color: var(--text-muted);
}
.progress-col {
  width: 160px;
}
.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  margin-bottom: 4px;
  font-size: 11px;
}
.progress-count {
  color: var(--text-secondary);
}
.progress-percentage {
  font-weight: 600;
  color: var(--text-primary);
}
.progress-bar-bg {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #34d399);
  border-radius: 4px;
  transition: width 0.5s ease-out;
}
.empty-state {
  text-align: center;
  padding: 32px;
  color: var(--text-muted);
  font-size: 14px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px dashed rgba(255, 255, 255, 0.08);
  border-radius: 12px;
}
</style>
