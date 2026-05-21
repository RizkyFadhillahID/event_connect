<template>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar glass-card" :class="{ collapsed: sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="brand" v-if="!sidebarCollapsed">
          <div class="brand-icon">
            <svg width="28" height="28" viewBox="0 0 40 40" fill="none">
              <rect width="40" height="40" rx="10" fill="url(#g1)"/>
              <path d="M12 20h16M20 12l8 8-8 8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
              <defs><linearGradient id="g1" x1="0" y1="0" x2="40" y2="40"><stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#8b5cf6"/></linearGradient></defs>
            </svg>
          </div>
          <span class="brand-name">EventConnect</span>
        </div>
        <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
          <ChevronLeft v-if="!sidebarCollapsed" :size="18" />
          <ChevronRight v-else :size="18" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/" class="nav-item" exact-active-class="active">
          <Home :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Dashboard</span>
        </RouterLink>

        <RouterLink v-if="auth.canManageUsers" to="/users" class="nav-item" active-class="active">
          <Users :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Manajemen User</span>
        </RouterLink>

        <RouterLink to="/events" class="nav-item" active-class="active">
          <Calendar :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Manajemen Event</span>
        </RouterLink>

        <RouterLink to="/tasks" class="nav-item" active-class="active">
          <CheckSquare :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Task & Workflow</span>
        </RouterLink>
        <RouterLink to="/rundown" class="nav-item" active-class="active">
          <CalendarClock :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Timeline &amp; Rundown</span>
        </RouterLink>      </nav>

      <div class="sidebar-footer" v-if="!sidebarCollapsed">
        <div class="user-info">
          <div class="user-avatar">{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</div>
          <div class="user-details">
            <div class="user-name">{{ auth.user?.name }}</div>
            <div class="user-role">{{ formatRole(auth.user?.role) }}</div>
          </div>
        </div>
        <button class="btn btn-danger btn-sm logout-btn" @click="handleLogout">
          <LogOut :size="16" style="margin-right: 6px;" />
          Keluar
        </button>
      </div>
      <div class="sidebar-footer-mini" v-else>
        <button class="nav-item" @click="handleLogout" title="Logout">
          <LogOut :size="20" class="nav-icon-lucide" />
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="main-wrap">
      <!-- Top bar -->
      <header class="topbar glass-card">
        <div class="topbar-left">
          <h2 class="page-title">{{ pageTitle }}</h2>
        </div>
        <div class="topbar-right">
          <div class="user-badge glass-card">
            <div class="user-avatar-sm">{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</div>
            <div>
              <div class="ub-name">{{ auth.user?.name }}</div>
              <div class="ub-role">{{ formatRole(auth.user?.role) }}</div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="main-content">
        <RouterView />
      </main>
    </div>

    <!-- Floating Chat -->
    <FloatingChat />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { Home, Users, Calendar, LogOut, ChevronLeft, ChevronRight, CheckSquare, CalendarClock } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import FloatingChat from '../components/FloatingChat.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarCollapsed = ref(false)

const pageTitles = {
  Dashboard: 'Dashboard',
  Users: 'Manajemen Akun',
  Events: 'Manajemen Event',
  Tasks: 'Task & Workflow',
  Rundown: 'Timeline & Rundown',
}
const pageTitle = computed(() => pageTitles[route.name] || 'Dashboard')

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

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

/* Sidebar */
.sidebar {
  width: 260px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 20px 16px;
  border-radius: 0;
  border-right: 1px solid var(--glass-border);
  border-top: none;
  border-bottom: none;
  border-left: none;
  transition: width 0.3s ease;
  position: sticky;
  top: 0;
  height: 100vh;
  flex-shrink: 0;
  z-index: 1001;
}
.sidebar.collapsed { width: 70px; }

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  min-height: 40px;
}
.brand { display: flex; align-items: center; gap: 10px; }
.brand-name { font-size: 16px; font-weight: 700; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.collapse-btn {
  background: rgba(255,255,255,0.1);
  border: 1px solid var(--glass-border);
  border-radius: 8px;
  color: var(--text-primary);
  cursor: pointer;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}
.collapse-btn:hover {
  background: rgba(255,255,255,0.15);
}

.sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  background: none;
  border: none;
  width: 100%;
  font-family: inherit;
  transition: all 0.2s;
}
.nav-item:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }
.nav-item.active { background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3)); color: var(--text-primary); border: 1px solid rgba(99,102,241,0.4); }
.nav-icon { font-size: 18px; flex-shrink: 0; }
.nav-icon-lucide { flex-shrink: 0; stroke-width: 2.5; }
.nav-label { white-space: nowrap; overflow: hidden; }

.sidebar-footer { padding-top: 20px; border-top: 1px solid var(--glass-border); }
.user-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px;
  flex-shrink: 0;
}
.user-name { font-size: 13px; font-weight: 600; }
.user-role { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.logout-btn { width: 100%; justify-content: center; }

.sidebar-footer-mini { padding-top: 20px; border-top: 1px solid var(--glass-border); }

/* Main */
.main-wrap { flex: 1; display: flex; flex-direction: column; min-width: 0; }

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 28px;
  margin: 16px 16px 0;
  border-radius: 16px;
}
.page-title { font-size: 18px; font-weight: 600; }

.user-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  border-radius: 12px;
}
.user-avatar-sm {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
  flex-shrink: 0;
}
.ub-name { font-size: 13px; font-weight: 600; }
.ub-role { font-size: 11px; color: var(--text-muted); }

.main-content { flex: 1; padding: 20px 16px 24px; }
</style>
