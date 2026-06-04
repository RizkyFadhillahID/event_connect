<template>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar glass-card" :class="{ collapsed: sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="brand" v-if="!sidebarCollapsed">
          <img src="/logo.png" alt="EventConnect Logo" class="brand-logo" />
          <div class="brand-details" style="display: flex; flex-direction: column; margin-left: 2px;">
            <span class="org-name" style="font-size: 10px; color: var(--primary-light); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; line-height: 1;">Backoffice Portal</span>
          </div>
        </div>
        <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
          <ChevronLeft v-if="!sidebarCollapsed" :size="18" />
          <ChevronRight v-else :size="18" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/" class="nav-item" exact-active-class="active">
          <LayoutDashboard :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Dashboard</span>
        </RouterLink>

        <RouterLink to="/organizations" class="nav-item" active-class="active">
          <Building2 :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Manajemen EO</span>
        </RouterLink>

        <RouterLink v-if="auth.isOwner" to="/admins" class="nav-item" active-class="active">
          <ShieldAlert :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Admin Platform</span>
        </RouterLink>

        <RouterLink to="/landing-editor" class="nav-item" active-class="active">
          <Edit3 :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Landing Editor</span>
        </RouterLink>

        <RouterLink to="/contact-messages" class="nav-item" active-class="active">
          <Mail :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Inbox Pesan</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer" v-if="!sidebarCollapsed">
        <div class="user-info">
          <div class="user-avatar">{{ auth.admin?.name?.charAt(0)?.toUpperCase() }}</div>
          <div class="user-details">
            <div class="user-name">{{ auth.admin?.name }}</div>
            <div class="user-role">{{ formatRole(auth.admin?.role) }}</div>
          </div>
        </div>
        <button class="btn btn-danger btn-sm logout-btn" @click="triggerLogout">
          <LogOut :size="16" style="margin-right: 6px;" />
          Keluar
        </button>
      </div>
      <div class="sidebar-footer-mini" v-else>
        <button class="nav-item" @click="triggerLogout" title="Logout">
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
            <div class="user-avatar-sm">{{ auth.admin?.name?.charAt(0)?.toUpperCase() }}</div>
            <div>
              <div class="ub-name">{{ auth.admin?.name }}</div>
              <div class="ub-role">{{ formatRole(auth.admin?.role) }}</div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="main-content">
        <RouterView />
      </main>
    </div>

    <!-- Logout Confirm Modal -->
    <Transition name="fade">
      <div v-if="showLogoutConfirm" class="modal-overlay" @click.self="showLogoutConfirm = false">
        <div class="modal-box" style="max-width: 400px; text-align: center;">
          <div style="margin-bottom: 16px;">
            <LogOut :size="48" style="margin: 0 auto; color: var(--danger);" />
          </div>
          <h2 style="margin-bottom: 12px; font-size: 20px; font-weight: 600;">Keluar Aplikasi?</h2>
          <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 14px;">Apakah Anda yakin ingin keluar dari portal admin?</p>
          <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-glass" @click="showLogoutConfirm = false">Batal</button>
            <button class="btn btn-danger" @click="confirmLogout" :disabled="loggingOut">
              <Loader2 v-if="loggingOut" :size="16" class="spinner-icon" />
              <span v-else>Ya, Keluar</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { LayoutDashboard, Building2, ShieldAlert, LogOut, ChevronLeft, ChevronRight, Edit3, Mail, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarCollapsed = ref(false)

const pageTitles = {
  Dashboard: 'Platform Overview',
  Organizations: 'Manajemen Event Organizer',
  Admins: 'Manajemen Admin Platform',
  LandingEditor: 'Landing Page Editor',
  ContactMessages: 'Contact Inbox',
  ContactMessageDetail: 'Detail Pesan',
}
const pageTitle = computed(() => pageTitles[route.name] || 'Overview')

const roleLabels = {
  owner: 'Platform Owner',
  admin: 'Platform Admin',
  support: 'Platform Support',
}

function formatRole(role) {
  return roleLabels[role] || role
}

const showLogoutConfirm = ref(false)
const loggingOut = ref(false)

function triggerLogout() {
  showLogoutConfirm.value = true
}

async function confirmLogout() {
  loggingOut.value = true
  try {
    await auth.logout()
    showLogoutConfirm.value = false
    router.push('/login')
  } finally {
    loggingOut.value = false
  }
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
.sidebar.collapsed {
  width: 70px;
  padding: 20px 8px;
}

.sidebar.collapsed .sidebar-header {
  justify-content: center;
}

.sidebar.collapsed .nav-item {
  width: 44px;
  height: 44px;
  padding: 0;
  justify-content: center;
  margin: 0 auto;
}

.sidebar.collapsed .sidebar-footer-mini {
  display: flex;
  justify-content: center;
}

.sidebar.collapsed .sidebar-footer-mini .nav-item {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  min-height: 40px;
}
.brand { display: flex; align-items: center; gap: 6px; }
.brand-logo { height: 44px; object-fit: contain; }
.collapse-btn {
  background: rgba(255,255,255,0.05);
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
  background: rgba(255,255,255,0.1);
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
.nav-item:hover { background: rgba(255,255,255,0.06); color: var(--text-primary); }
.nav-item.active { background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(13,148,136,0.2)); color: var(--text-primary); border: 1px solid rgba(14,165,233,0.3); }
.nav-icon-lucide { flex-shrink: 0; stroke-width: 2.5; }
.nav-label { white-space: nowrap; overflow: hidden; }

.sidebar-footer { padding-top: 20px; border-top: 1px solid var(--glass-border); }
.user-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px;
  flex-shrink: 0;
}
.user-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
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
  border: 1px solid var(--glass-border);
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
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
  flex-shrink: 0;
}
.ub-name { font-size: 13px; font-weight: 600; }
.ub-role { font-size: 11px; color: var(--text-muted); }

.main-content { flex: 1; padding: 20px 16px 24px; }
</style>
