<template>
  <div class="profile-page">
    

    <!-- Alert Notifications -->
    <transition name="fade">
      <div v-if="successMsg" class="alert alert-success d-flex align-items-center">
        <CheckCircle :size="18" style="margin-right: 8px;" />
        <span>{{ successMsg }}</span>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="errorMsg" class="alert alert-error d-flex align-items-center">
        <AlertTriangle :size="18" style="margin-right: 8px;" />
        <span>{{ errorMsg }}</span>
      </div>
    </transition>

    <div class="profile-content">
      <!-- Form Edit Profile -->
      <div class="profile-main glass-card">
        <div class="card-header">
          <h3>Informasi Pribadi</h3>
          <p>Perbarui informasi akun dasar Anda</p>
        </div>

        <form @submit.prevent="handleUpdate">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Nama Lengkap *</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="glass-input"
                required
                placeholder="Masukkan nama lengkap"
              />
            </div>
            <div class="form-group">
              <label for="phone">Nomor Telepon</label>
              <input
                id="phone"
                v-model="form.phone"
                type="text"
                class="glass-input"
                placeholder="081234567890"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="email">Alamat Email *</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="glass-input"
              required
              placeholder="nama@domain.com"
            />
          </div>

          <div class="section-divider"></div>

          <div class="card-header" style="padding: 0 0 16px 0;">
            <h3>Ubah Password</h3>
            <p>Kosongkan jika Anda tidak ingin mengubah password akun</p>
          </div>

          <div class="form-group">
            <label for="current_password">
              Password Saat Ini 
              <span v-if="requiresCurrentPassword" class="required-indicator">(Wajib diisi untuk menyimpan email/password baru)</span>
            </label>
            <input
              id="current_password"
              v-model="form.current_password"
              type="password"
              class="glass-input"
              :required="requiresCurrentPassword"
              placeholder="Masukkan kata sandi saat ini"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="password">Password Baru</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="glass-input"
                placeholder="Masukkan kata sandi baru"
              />
            </div>
            <div class="form-group">
              <label for="password_confirmation">Konfirmasi Password Baru</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="glass-input"
                placeholder="Konfirmasi kata sandi baru"
              />
            </div>
          </div>

          <div class="form-actions">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
              <Save v-else :size="16" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Info Details Card -->
      <div class="profile-sidebar">
        <div class="glass-card info-card">
          <div class="avatar-container">
            <div class="avatar-large">{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</div>
            <h3 class="user-display-name">{{ auth.user?.name }}</h3>
            <div class="user-display-email">{{ auth.user?.email }}</div>
          </div>

          <div class="divider"></div>

          <div class="meta-list">
            <div class="meta-item">
              <span class="meta-label">Organisasi</span>
              <span class="meta-value">{{ auth.organizationName }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Peran Sistem</span>
              <span class="meta-value role-badge">{{ formatRole(auth.user?.role) }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Status Akun</span>
              <span class="meta-value">
                <span class="badge" :class="`badge-${auth.user?.status}`">
                  {{ auth.user?.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
              </span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Tanggal Bergabung</span>
              <span class="meta-value date-value">{{ formatDate(auth.user?.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { CheckCircle, AlertTriangle, Loader2, Save } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()

const form = ref({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  phone: auth.user?.phone || '',
  current_password: '',
  password: '',
  password_confirmation: ''
})

const submitting = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

// Check if verification with current password is required
const requiresCurrentPassword = computed(() => {
  const emailChanged = form.value.email !== auth.user?.email
  const passwordFilled = form.value.password.length > 0
  return emailChanged || passwordFilled
})

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
  liaison_officer: 'Liaison Officer (LO)'
}

function formatRole(role) {
  return roleLabels[role] || role
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

async function handleUpdate() {
  successMsg.value = ''
  errorMsg.value = ''
  submitting.value = true

  // Simple client check for matching password
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    errorMsg.value = 'Konfirmasi password baru tidak cocok.'
    submitting.value = false
    return
  }

  // Format request object
  const payload = {
    name: form.value.name,
    email: form.value.email,
    phone: form.value.phone || null,
    current_password: form.value.current_password || null,
    password: form.value.password || null,
    password_confirmation: form.value.password_confirmation || null
  }

  try {
    const res = await api.put('/profile', payload)
    
    // Update store state and localStorage
    auth.updateCurrentUser(res.data)
    
    successMsg.value = 'Profil Anda berhasil diperbarui.'
    
    // Clear passwords
    form.value.current_password = ''
    form.value.password = ''
    form.value.password_confirmation = ''
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorMsg.value = Object.values(errs).flat().join(' ')
    } else {
      errorMsg.value = e.response?.data?.message || 'Gagal memperbarui profil. Periksa kembali input Anda.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.profile-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  padding: 20px 24px;
}
.header-left h2 {
  font-size: 20px;
  font-weight: 600;
}
.header-left p {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 4px;
}

.profile-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

@media (max-width: 992px) {
  .profile-content {
    grid-template-columns: 1fr;
  }
}

.profile-main {
  padding: 28px;
}

.card-header {
  margin-bottom: 20px;
}
.card-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}
.card-header p {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 2px;
}

.section-divider {
  height: 1px;
  background: var(--glass-border);
  margin: 24px 0;
}

.required-indicator {
  font-size: 11px;
  color: var(--warning);
  font-weight: 400;
  margin-left: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 24px;
}

.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.info-card {
  padding: 28px 24px;
  text-align: center;
}

.avatar-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.avatar-large {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  font-weight: 700;
  color: white;
  margin-bottom: 16px;
  box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
}

.user-display-name {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.user-display-email {
  font-size: 13px;
  color: var(--text-muted);
  word-break: break-all;
}

.divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 20px 0;
}

.meta-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  text-align: left;
}

.meta-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
}

.meta-label {
  color: var(--text-secondary);
  font-weight: 500;
}

.meta-value {
  color: var(--text-primary);
  font-weight: 600;
}

.role-badge {
  background: rgba(14, 165, 233, 0.15);
  border: 1px solid rgba(14, 165, 233, 0.3);
  color: var(--primary-light);
  padding: 3px 8px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 500;
}

.date-value {
  color: var(--text-secondary);
  font-weight: 400;
}

.d-flex {
  display: flex;
}
.align-items-center {
  align-items: center;
}
</style>
