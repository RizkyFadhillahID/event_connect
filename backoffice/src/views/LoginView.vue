<template>
  <div class="login-page">
    <!-- Animated background blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="login-container glass-card">
      <div class="login-header">
        <div class="logo-icon">
          <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="12" fill="url(#gradAdmin)"/>
            <path d="M12 20h16M20 12l8 8-8 8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <defs>
              <linearGradient id="gradAdmin" x1="0" y1="0" x2="40" y2="40">
                <stop offset="0%" stop-color="#e5e5e5"/>
                <stop offset="100%" stop-color="#737373"/>
              </linearGradient>
            </defs>
          </svg>
        </div>
        <h1>EventConnect</h1>
        <p style="color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.08em; margin-top: 4px;">Platform Admin Portal</p>
      </div>

      <form @submit.prevent="handleLogin">
        <transition name="fade">
          <div v-if="error" class="alert alert-error">{{ error }}</div>
        </transition>

        <div class="form-group">
          <label>Platform Email</label>
          <input
            v-model="form.email"
            type="email"
            class="glass-input"
            placeholder="nama@domain.com"
            autocomplete="email"
            required
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrapper">
            <input
              v-model="form.password"
              :type="showPass ? 'text' : 'password'"
              class="glass-input"
              placeholder="Masukkan kata sandi"
              autocomplete="current-password"
              required
            />
            <button type="button" class="toggle-pass" @click="showPass = !showPass">
              <Eye v-if="showPass" :size="20" />
              <EyeOff v-else :size="20" />
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary login-btn" :disabled="loading">
          <Loader2 v-if="loading" :size="18" class="spinner-icon" />
          <span v-if="!loading">Masuk ke Backoffice</span>
          <span v-else>Loading...</span>
        </button>
      </form>

      <div class="login-footer">
        <p>© 2026 EventConnect Platform</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)
const showPass = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/')
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login gagal. Periksa email dan password Anda.'
    error.value = msg
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.35;
  animation: float 8s ease-in-out infinite;
  pointer-events: none;
}
.blob-1 { width: 400px; height: 400px; background: #262626; top: -100px; left: -100px; animation-delay: 0s; }
.blob-2 { width: 300px; height: 300px; background: #1a1a1a; bottom: -50px; right: -50px; animation-delay: 2s; }
.blob-3 { width: 250px; height: 250px; background: #333333; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 4s; }

@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-30px) scale(1.05); }
}
.blob-3 { animation: float3 8s ease-in-out infinite; }
@keyframes float3 {
  0%, 100% { transform: translate(-50%,-50%) scale(1); }
  50% { transform: translate(-50%,-60%) scale(1.05); }
}

.login-container {
  width: 100%;
  max-width: 440px;
  padding: 48px 40px;
  position: relative;
  z-index: 10;
  border: 1px solid var(--glass-border);
}

.login-header {
  text-align: center;
  margin-bottom: 36px;
}
.logo-icon {
  display: inline-flex;
  margin-bottom: 16px;
}
.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  background: linear-gradient(135deg, #ffffff, #a3a3a3);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 2px;
}

.input-wrapper { position: relative; }
.toggle-pass {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  transition: color 0.2s;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.toggle-pass:hover { color: var(--text-secondary); }

.spinner-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.login-btn {
  width: 100%;
  justify-content: center;
  padding: 14px;
  font-size: 15px;
  margin-top: 8px;
}

.login-footer {
  text-align: center;
  margin-top: 24px;
  color: var(--text-muted);
  font-size: 12px;
}
</style>
