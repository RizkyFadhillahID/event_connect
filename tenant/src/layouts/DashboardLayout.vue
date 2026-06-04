<template>
  <div class="layout">
    <!-- Backdrop Overlay on Mobile -->
    <div v-if="mobileMenuOpen" class="sidebar-overlay" @click="mobileMenuOpen = false"></div>
    <!-- Sidebar -->
    <aside class="sidebar glass-card" :class="{ collapsed: sidebarCollapsed, 'mobile-open': mobileMenuOpen }">
      <div class="sidebar-header">
        <div class="brand" v-if="!sidebarCollapsed">
          <img src="/logo.png" alt="EventConnect Logo" class="brand-logo" />
          <div class="brand-details" style="display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
              <span class="org-name" style="font-size: 11px; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;" :title="auth.organizationName">{{ auth.organizationName }}</span>
              <span 
                v-if="auth.organization?.plan" 
                class="plan-badge" 
                :class="auth.organization?.plan"
                @click="triggerUpgradeModal"
                title="Klik untuk detail & upgrade paket"
              >
                {{ auth.organization?.plan }}
              </span>
            </div>
          </div>
        </div>
        <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed">
          <ChevronLeft v-if="!sidebarCollapsed" :size="18" />
          <ChevronRight v-else :size="18" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/" class="nav-item" exact-active-class="active" @click="mobileMenuOpen = false">
          <Home :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Dashboard</span>
        </RouterLink>

        <RouterLink v-if="auth.canManageUsers" to="/users" class="nav-item" active-class="active" @click="mobileMenuOpen = false">
          <Users :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Manajemen User</span>
        </RouterLink>

        <RouterLink to="/events" class="nav-item" active-class="active" @click="mobileMenuOpen = false">
          <Calendar :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Manajemen Event</span>
        </RouterLink>

        <RouterLink to="/tasks" class="nav-item" active-class="active" @click="mobileMenuOpen = false">
          <CheckSquare :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Task &amp; Workflow</span>
        </RouterLink>
        <RouterLink to="/rundown" class="nav-item" active-class="active" @click="mobileMenuOpen = false">
          <CalendarClock :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Timeline &amp; Rundown</span>
        </RouterLink>
        <RouterLink to="/logistics" class="nav-item" active-class="active" @click="mobileMenuOpen = false">
          <Boxes :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Logistik &amp; Inventaris</span>
        </RouterLink>
        <RouterLink to="/chat" class="nav-item mobile-only-nav" active-class="active" @click="mobileMenuOpen = false">
          <MessageSquare :size="20" class="nav-icon-lucide" />
          <span class="nav-label" v-if="!sidebarCollapsed">Chat Grup</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer" v-if="!sidebarCollapsed">
        <RouterLink to="/profile" class="user-info" title="Edit Profil">
          <div class="user-avatar">{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</div>
          <div class="user-details">
            <div class="user-name">{{ auth.user?.name }}</div>
            <div class="user-role">{{ formatRole(auth.user?.role) }}</div>
          </div>
        </RouterLink>
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
        <div class="topbar-left" style="display: flex; align-items: center; gap: 10px;">
          <button class="mobile-toggle-btn" @click="mobileMenuOpen = !mobileMenuOpen" title="Toggle Menu">
            <Menu :size="20" />
          </button>
          <h2 class="page-title">{{ pageTitle }}</h2>
        </div>
        <div class="topbar-right">
          <RouterLink to="/profile" class="user-badge glass-card" title="Edit Profil">
            <div class="user-avatar-sm">{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</div>
            <div class="user-badge-info">
              <div class="ub-name">{{ auth.user?.name }}</div>
              <div class="ub-role">{{ formatRole(auth.user?.role) }}</div>
            </div>
          </RouterLink>
        </div>
      </header>

      <!-- Page content -->
      <main class="main-content">
        <RouterView />
      </main>
    </div>

    <!-- Floating Chat -->
    <FloatingChat />

    <!-- Upgrade Plan Modal -->
    <Transition name="fade">
      <div v-if="showUpgradeModal" class="modal-overlay" @click.self="showUpgradeModal = false">
        <div class="modal-box glass-card" style="max-width: 620px; padding: 32px; text-align: left;">
          <div class="modal-header" style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="display: flex; align-items: center; gap: 8px; margin: 0; font-size: 20px; color: #fff;">
              <Sparkles :size="22" style="color: #fbbf24;" />
              <span>Paket Layanan EventConnect</span>
            </h3>
            <button class="btn-close" @click="showUpgradeModal = false" style="background:none; border:none; color:var(--text-muted); cursor:pointer;"><X :size="20" /></button>
          </div>

          <div style="margin-bottom: 24px;">
            <p style="color: var(--text-secondary); font-size: 13.5px; line-height: 1.6; margin-bottom: 20px;">
              Tingkatkan batas kuota dan dapatkan fitur premium tambahan dengan mengupgrade paket layanan Event Organizer Anda.
            </p>

            <div class="plan-comparison-grid">
              <!-- Free Card -->
              <div class="plan-card-mini" :class="{ active: auth.organization?.plan === 'free' }">
                <div class="plan-header">
                  <h4>Free</h4>
                  <span class="price">Rp 0</span>
                </div>
                <ul class="plan-features">
                  <li><strong>5</strong> Akun User</li>
                  <li><strong>3</strong> Event Aktif</li>
                  <li>Sistem Manajemen Dasar</li>
                  <li class="disabled">❌ Kirim File Chat</li>
                </ul>
                <div v-if="auth.organization?.plan === 'free'" class="current-plan-badge">Paket Aktif</div>
              </div>

              <!-- Business Card -->
              <div class="plan-card-mini" :class="{ active: auth.organization?.plan === 'business' }">
                <div class="plan-header">
                  <h4>Business</h4>
                  <span class="price">Rp 299k<span class="period">/bln</span></span>
                </div>
                <ul class="plan-features">
                  <li><strong>25</strong> Akun User</li>
                  <li><strong>50</strong> Event Managed</li>
                  <li>Sistem Manajemen Lengkap</li>
                  <li>✓ Kirim File Chat</li>
                </ul>
                <div v-if="auth.organization?.plan === 'business'" class="current-plan-badge">Paket Aktif</div>
              </div>

              <!-- Enterprise Card -->
              <div class="plan-card-mini" :class="{ active: auth.organization?.plan === 'enterprise' }">
                <div class="plan-header">
                  <h4>Enterprise</h4>
                  <span class="price">Custom</span>
                </div>
                <ul class="plan-features">
                  <li><strong>Unlimited</strong> User</li>
                  <li><strong>Unlimited</strong> Event</li>
                  <li>Prioritas Support 24/7</li>
                  <li>✓ Kirim File Chat</li>
                </ul>
                <div v-if="auth.organization?.plan === 'enterprise'" class="current-plan-badge">Paket Aktif</div>
              </div>
            </div>
          </div>

          <div style="background: rgba(14, 165, 233, 0.05); border: 1px solid rgba(14, 165, 233, 0.2); padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start;">
            <HelpCircle :size="20" style="color: var(--primary-light); flex-shrink: 0; margin-top: 2px;" />
            <div>
              <h5 style="margin: 0 0 4px; color: #fff; font-size: 13.5px; font-weight: 600;">Ingin melakukan upgrade paket?</h5>
              <p style="margin: 0; color: var(--text-secondary); font-size: 12.5px; line-height: 1.5;">
                Silakan hubungi tim sales kami melalui email di <strong>sales@eventconnect.com</strong> atau kontak WhatsApp di <strong>0812-3456-7890</strong>.
              </p>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end;">
            <button class="btn btn-glass" @click="showUpgradeModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Logout Confirm Modal -->
    <Transition name="fade">
      <div v-if="showLogoutConfirm" class="modal-overlay" @click.self="showLogoutConfirm = false">
        <div class="modal-box" style="max-width: 400px; text-align: center;">
          <div style="margin-bottom: 16px;">
            <LogOut :size="48" style="margin: 0 auto; color: var(--danger);" />
          </div>
          <h2 style="margin-bottom: 12px; font-size: 20px; font-weight: 600;">Keluar Aplikasi?</h2>
          <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 14px;">Apakah Anda yakin ingin keluar dari akun Anda?</p>
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
import { Home, Users, Calendar, LogOut, ChevronLeft, ChevronRight, CheckSquare, CalendarClock, Boxes, Loader2, Menu, MessageSquare, Sparkles, X, HelpCircle } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import FloatingChat from '../components/FloatingChat.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarCollapsed = ref(false)
const mobileMenuOpen = ref(false)
const showUpgradeModal = ref(false)

function triggerUpgradeModal() {
  showUpgradeModal.value = true
}

const pageTitles = {
  Dashboard: 'Dashboard',
  Users: 'Manajemen Akun',
  Events: 'Manajemen Event',
  Tasks: 'Task & Workflow',
  Rundown: 'Timeline & Rundown',
  Logistics: 'Logistik & Inventaris',
  Profile: 'Pengaturan Profil',
  Chat: 'Chat Grup',
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
.brand-logo { height: 44px; object-fit: contain; filter: drop-shadow(0 0 2px rgba(255, 255, 255, 0.7)); }
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
.nav-item.active { background: linear-gradient(135deg, rgba(14,165,233,0.3), rgba(13,148,136,0.3)); color: var(--text-primary); border: 1px solid rgba(14,165,233,0.4); }
.nav-icon { font-size: 18px; flex-shrink: 0; }
.nav-icon-lucide { flex-shrink: 0; stroke-width: 2.5; }
.nav-label { white-space: nowrap; overflow: hidden; }

.sidebar-footer { padding-top: 20px; border-top: 1px solid var(--glass-border); }
.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  padding: 6px;
  border-radius: 12px;
  cursor: pointer;
}
.user-info:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-1px);
}
.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #0ea5e9, #0d9488);
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
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  cursor: pointer;
}
.user-badge:hover {
  background: rgba(255, 255, 255, 0.12);
  transform: translateY(-1px);
}
.user-avatar-sm {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, #0ea5e9, #0d9488);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
  flex-shrink: 0;
}
.ub-name { font-size: 13px; font-weight: 600; }
.ub-role { font-size: 11px; color: var(--text-muted); }

.main-content { flex: 1; padding: 20px 16px 24px; }

.plan-badge {
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,0.15);
  cursor: pointer;
  letter-spacing: 0.02em;
  transition: all 0.2s;
  display: inline-block;
  line-height: 1;
}
.plan-badge:hover {
  transform: scale(1.08);
  box-shadow: 0 0 8px rgba(14, 165, 233, 0.2);
}
.plan-badge.free {
  background: rgba(156, 163, 175, 0.2);
  color: #d1d5db;
}
.plan-badge.business {
  background: rgba(14, 165, 233, 0.2);
  color: var(--primary-light);
  border-color: rgba(14, 165, 233, 0.4);
}
.plan-badge.enterprise {
  background: rgba(217, 70, 239, 0.2);
  color: #f5d0fe;
  border-color: rgba(217, 70, 239, 0.4);
}

/* Upgrade Modal Grid */
.plan-comparison-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 12px;
}
.plan-card-mini {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.3s ease;
}
.plan-card-mini:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}
.plan-card-mini.active {
  background: rgba(14, 165, 233, 0.05);
  border-color: rgba(14, 165, 233, 0.4);
  box-shadow: 0 4px 20px rgba(14, 165, 233, 0.15);
}
.plan-header {
  text-align: center;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding-bottom: 12px;
  margin-bottom: 12px;
}
.plan-header h4 {
  font-size: 15px;
  font-weight: 700;
  margin: 0 0 6px;
  color: #fff;
}
.plan-header .price {
  font-size: 18px;
  font-weight: 800;
  color: var(--primary-light);
}
.plan-header .price .period {
  font-size: 11px;
  font-weight: 500;
  color: var(--text-muted);
}
.plan-features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  flex: 1;
}
.plan-features li.disabled {
  color: var(--text-muted);
  opacity: 0.6;
}
.current-plan-badge {
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
}

@media (max-width: 640px) {
  .plan-comparison-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

.mobile-toggle-btn {
  display: none;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--glass-border);
  border-radius: 8px;
  color: var(--text-primary);
  cursor: pointer;
  padding: 8px;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.mobile-only-nav {
  display: none;
}

@media (max-width: 768px) {
  .collapse-btn {
    display: none;
  }
  .mobile-toggle-btn {
    display: inline-flex;
  }
  .mobile-only-nav {
    display: flex;
  }
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 260px;
    height: 100vh;
    transform: translateX(-100%);
    border-radius: 0;
    z-index: 1020;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
  }
  .sidebar.mobile-open {
    transform: translateX(0) !important;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
  }
  /* Disable mini sidebar on mobile, just hide it */
  .sidebar.collapsed {
    transform: translateX(-100%);
    width: 260px;
  }
  
  .sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1015;
    animation: fadeIn 0.2s ease;
  }
  
  .main-wrap {
    width: 100%;
    min-width: 0;
  }
  
  .topbar {
    margin: 10px 10px 0;
    padding: 12px 16px;
  }
  .page-title {
    font-size: 16px;
  }
  .user-badge {
    padding: 6px;
    gap: 0;
  }
  .user-badge-info {
    display: none;
  }
  .main-content {
    padding: 12px 10px;
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
