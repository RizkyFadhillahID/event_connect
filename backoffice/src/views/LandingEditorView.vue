<template>
  <div class="landing-editor-page">
    <!-- Header/Tabs -->
    <div class="glass-card tabs-card">
      <div class="editor-tabs">
        <button 
          v-for="tab in tabItems" 
          :key="tab.value" 
          class="tab-btn" 
          :class="{ active: activeTab === tab.value }"
          @click="selectTab(tab.value)"
        >
          <component :is="tab.icon" :size="16" class="tab-icon" />
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="glass-card loading-state">
      <Loader2 class="spinner-icon" :size="32" />
      <p>Memuat konfigurasi landing page...</p>
    </div>

    <!-- Error/Success Alerts -->
    <div v-else>
      <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>
      <div v-if="errorMsg" class="alert alert-error">{{ errorMsg }}</div>

      <!-- Hero Tab -->
      <div v-if="activeTab === 'hero'" class="glass-card form-card">
        <h3>Section Hero Utama</h3>
        <p class="tab-desc">Atur judul dan deskripsi perkenalan utama di bagian atas website.</p>
        
        <form @submit.prevent="saveSection('hero')">
          <div class="form-group">
            <label>Judul Hero (Title)</label>
            <input v-model="form.hero.title" class="glass-input" required placeholder="Platform Manajemen Event Terintegrasi" />
          </div>
          <div class="form-group">
            <label>Sub-judul / Deskripsi (Content)</label>
            <textarea v-model="form.hero.content" class="glass-input" rows="4" required placeholder="Masukkan penjelasan singkat mengenai platform Anda..."></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <Loader2 v-if="saving" :size="16" class="spinner-icon" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Features Tab -->
      <div v-if="activeTab === 'features'" class="glass-card form-card">
        <h3>Section Fitur Unggulan</h3>
        <p class="tab-desc">Atur judul seksi fitur dan daftar fitur unggulan (maksimal 4 fitur direkomendasikan).</p>

        <form @submit.prevent="saveSection('features')">
          <div class="form-group">
            <label>Judul Seksi Fitur</label>
            <input v-model="form.features.title" class="glass-input" required placeholder="Fitur Unggulan" />
          </div>
          
          <div class="features-list">
            <div v-for="(feat, index) in form.features.items" :key="index" class="feature-item-form glass-card">
              <div class="item-header">
                <h4>Fitur #{{ index + 1 }}</h4>
                <button type="button" class="btn btn-danger btn-sm" @click="removeFeature(index)" v-if="form.features.items.length > 1">Hapus</button>
              </div>
              <div class="form-group">
                <label>Judul Fitur</label>
                <input v-model="feat.title" class="glass-input" required placeholder="Manajemen Real-time" />
              </div>
              <div class="form-group">
                <label>Deskripsi Singkat</label>
                <textarea v-model="feat.desc" class="glass-input" rows="2" required placeholder="Masukkan penjelasan cara kerja fitur ini..."></textarea>
              </div>
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <button type="button" class="btn btn-glass btn-sm" @click="addFeature" :disabled="form.features.items.length >= 6">
              + Tambah Fitur Baru
            </button>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <Loader2 v-if="saving" :size="16" class="spinner-icon" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Pricing Tab -->
      <div v-if="activeTab === 'pricing'" class="glass-card form-card">
        <h3>Section Paket Layanan</h3>
        <p class="tab-desc">Atur daftar paket yang akan ditawarkan ke Event Organizer.</p>

        <form @submit.prevent="saveSection('pricing')">
          <div class="form-group">
            <label>Judul Seksi Paket</label>
            <input v-model="form.pricing.title" class="glass-input" required placeholder="Paket Layanan" />
          </div>

          <div class="pricing-list">
            <div v-for="(plan, index) in form.pricing.items" :key="index" class="price-item-form glass-card">
              <div class="item-header">
                <h4>Paket #{{ index + 1 }}</h4>
                <button type="button" class="btn btn-danger btn-sm" @click="removePlan(index)" v-if="form.pricing.items.length > 1">Hapus</button>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Nama Paket</label>
                  <input v-model="plan.name" class="glass-input" required placeholder="Business Plan" />
                </div>
                <div class="form-group">
                  <label>Harga / Tarif</label>
                  <input v-model="plan.price" class="glass-input" required placeholder="Rp 299.000 / bln" />
                </div>
              </div>
              <div class="form-group">
                <label>Deskripsi Singkat</label>
                <input v-model="plan.desc" class="glass-input" required placeholder="Solusi terbaik untuk EO berkembang" />
              </div>
              <div class="form-group">
                <label>Spesifikasi / Fitur Paket (Satu per baris)</label>
                <textarea v-model="plan.specsText" class="glass-input" rows="4" required placeholder="Masukkan fitur-fitur paket (misal: Laporan Otomatis)"></textarea>
              </div>
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <button type="button" class="btn btn-glass btn-sm" @click="addPlan" :disabled="form.pricing.items.length >= 4">
              + Tambah Paket Baru
            </button>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <Loader2 v-if="saving" :size="16" class="spinner-icon" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Contact Info Tab -->
      <div v-if="activeTab === 'contact_info'" class="glass-card form-card">
        <h3>Section Informasi Kontak</h3>
        <p class="tab-desc">Atur alamat email admin, nomor telepon, dan lokasi kantor untuk website.</p>

        <form @submit.prevent="saveSection('contact_info')">
          <div class="form-group">
            <label>Judul Seksi Kontak</label>
            <input v-model="form.contact_info.title" class="glass-input" required placeholder="Hubungi Kami" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Alamat Email Support</label>
              <input v-model="form.contact_info.items.email" type="email" class="glass-input" required placeholder="support@eventconnect.com" />
            </div>
            <div class="form-group">
              <label>Nomor Telepon/WA Kantor</label>
              <input v-model="form.contact_info.items.phone" class="glass-input" required placeholder="082145678901" />
            </div>
          </div>
          <div class="form-group">
            <label>Alamat Fisik Kantor</label>
            <textarea v-model="form.contact_info.items.address" class="glass-input" rows="3" required placeholder="Masukkan alamat lengkap kantor..."></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <Loader2 v-if="saving" :size="16" class="spinner-icon" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>

      <!-- FAQ Tab -->
      <div v-if="activeTab === 'faqs'" class="glass-card form-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
          <div>
            <h3>Daftar Tanya Jawab (FAQ)</h3>
            <p class="tab-desc" style="margin-bottom:0">Kelola daftar pertanyaan dan jawaban yang sering diajukan di landing website.</p>
          </div>
          <button type="button" class="btn btn-primary btn-sm" @click="startCreateFaq">
            + Tambah FAQ Baru
          </button>
        </div>

        <div v-if="loadingFaqs" style="text-align:center; padding: 20px;">
          <Loader2 class="spinner-icon" :size="24" />
          <p style="margin-top:8px; font-size:13px; color:var(--text-muted)">Memuat FAQ...</p>
        </div>
        <div v-else-if="faqsList.length === 0" style="text-align:center; padding:32px; color:var(--text-muted); font-style:italic">
          Belum ada FAQ ditambahkan. Silakan klik tombol di kanan atas untuk menambahkan.
        </div>
        <div v-else class="faq-manager-list">
          <div v-for="faq in faqsList" :key="faq.id" class="faq-manager-item glass-card" style="padding:16px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:flex-start; gap:16px">
            <div style="flex:1">
              <strong style="font-size:14px; color:#fff">{{ faq.order_number }}. {{ faq.question }}</strong>
              <p style="font-size:13px; color:var(--text-secondary); margin: 6px 0 0">{{ faq.answer }}</p>
            </div>
            <div style="display:flex; gap:8px">
              <button class="btn btn-glass btn-sm" @click="startEditFaq(faq)">Edit</button>
              <button class="btn btn-danger btn-sm" @click="deleteFaq(faq.id)">Hapus</button>
            </div>
          </div>
        </div>

        <!-- Inline FAQ Modal -->
        <div v-if="showFaqModal" class="faq-modal-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; z-index:999" @click.self="showFaqModal = false">
          <div class="glass-card" style="width: 500px; padding: 24px; position: relative;">
            <h4 style="margin-top:0; margin-bottom:16px">{{ faqForm.id ? 'Edit FAQ' : 'Tambah FAQ Baru' }}</h4>
            <form @submit.prevent="saveFaq">
              <div class="form-group" style="margin-bottom:12px">
                <label>Nomor Urut (Order Number)</label>
                <input v-model.number="faqForm.order_number" type="number" class="glass-input" required style="width:100%"/>
              </div>
              <div class="form-group" style="margin-bottom:12px">
                <label>Pertanyaan (Question)</label>
                <input v-model="faqForm.question" class="glass-input" required placeholder="Masukkan pertanyaan FAQ" style="width:100%"/>
              </div>
              <div class="form-group" style="margin-bottom:16px">
                <label>Jawaban (Answer)</label>
                <textarea v-model="faqForm.answer" class="glass-input" rows="4" required placeholder="Masukkan penjelasan jawaban FAQ..." style="width:100%"></textarea>
              </div>
              <div style="display:flex; gap:8px; justify-content:flex-end">
                <button type="button" class="btn btn-glass" @click="showFaqModal = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="savingFaq">
                  <Loader2 v-if="savingFaq" :size="16" class="spinner-icon" />
                  <span>Simpan</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Edit3, CheckSquare, DollarSign, Mail, Loader2, HelpCircle } from 'lucide-vue-next'
import api from '../api/axios'

const tabItems = [
  { label: 'Hero Utama', value: 'hero', icon: Edit3 },
  { label: 'Fitur', value: 'features', icon: CheckSquare },
  { label: 'Paket & Layanan', value: 'pricing', icon: DollarSign },
  { label: 'Kontak Admin', value: 'contact_info', icon: Mail },
  { label: 'Tanya Jawab (FAQ)', value: 'faqs', icon: HelpCircle },
]

const activeTab = ref('hero')
const loading = ref(true)
const saving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const form = ref({
  hero: { title: '', content: '' },
  features: { title: '', items: [] },
  pricing: { title: '', items: [] },
  contact_info: { title: '', items: { email: '', phone: '', address: '' } }
})

// FAQ Specific State
const faqsList = ref([])
const loadingFaqs = ref(false)
const showFaqModal = ref(false)
const savingFaq = ref(false)
const faqForm = ref({ id: null, question: '', answer: '', order_number: 1 })

function selectTab(value) {
  activeTab.value = value
  if (value === 'faqs') {
    loadFaqs()
  }
}

async function loadFaqs() {
  loadingFaqs.value = true
  try {
    const res = await api.get('/faqs')
    faqsList.value = res.data
  } catch (err) {
    errorMsg.value = 'Gagal memuat daftar FAQ.'
  } finally {
    loadingFaqs.value = false
  }
}

function startCreateFaq() {
  faqForm.value = {
    id: null,
    question: '',
    answer: '',
    order_number: faqsList.value.length ? Math.max(...faqsList.value.map(f => f.order_number)) + 1 : 1
  }
  showFaqModal.value = true
}

function startEditFaq(faq) {
  faqForm.value = {
    id: faq.id,
    question: faq.question,
    answer: faq.answer,
    order_number: faq.order_number
  }
  showFaqModal.value = true
}

async function saveFaq() {
  savingFaq.value = true
  errorMsg.value = ''
  successMsg.value = ''
  
  const payload = {
    question: faqForm.value.question,
    answer: faqForm.value.answer,
    order_number: faqForm.value.order_number
  }

  try {
    if (faqForm.value.id) {
      await api.put(`/faqs/${faqForm.value.id}`, payload)
      successMsg.value = 'FAQ berhasil diperbarui.'
    } else {
      await api.post('/faqs', payload)
      successMsg.value = 'FAQ baru berhasil dibuat.'
    }
    showFaqModal.value = false
    loadFaqs()
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal menyimpan FAQ.'
  } finally {
    savingFaq.value = false
  }
}

async function deleteFaq(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus FAQ ini?')) return
  errorMsg.value = ''
  successMsg.value = ''
  try {
    await api.delete(`/faqs/${id}`)
    successMsg.value = 'FAQ berhasil dihapus.'
    loadFaqs()
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err) {
    errorMsg.value = 'Gagal menghapus FAQ.'
  }
}

// Load Config from Backend
async function loadConfig() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get('/landing-contents')
    
    // Bind Hero
    if (res.data.hero) {
      form.value.hero.title = res.data.hero.title
      form.value.hero.content = res.data.hero.content
    }

    // Bind Features
    if (res.data.features) {
      form.value.features.title = res.data.features.title
      form.value.features.items = Array.isArray(res.data.features.content) ? res.data.features.content : []
    }

    // Bind Pricing
    if (res.data.pricing) {
      form.value.pricing.title = res.data.pricing.title
      const plans = Array.isArray(res.data.pricing.content) ? res.data.pricing.content : []
      form.value.pricing.items = plans.map(p => ({
        ...p,
        specsText: Array.isArray(p.specs) ? p.specs.join('\n') : ''
      }))
    }

    // Bind Contact Info
    if (res.data.contact_info) {
      form.value.contact_info.title = res.data.contact_info.title
      form.value.contact_info.items = res.data.contact_info.content || { email: '', phone: '', address: '' }
    }

  } catch (err) {
    console.error('Error loading config:', err)
    errorMsg.value = 'Gagal memuat konfigurasi dari backend: ' + (err.response?.data?.message || err.message)
  } finally {
    loading.value = false
  }
}

// Add/Remove item helpers
function addFeature() {
  form.value.features.items.push({ title: '', desc: '' })
}
function removeFeature(idx) {
  form.value.features.items.splice(idx, 1)
}

function addPlan() {
  form.value.pricing.items.push({ name: '', price: '', desc: '', specsText: '' })
}
function removePlan(idx) {
  form.value.pricing.items.splice(idx, 1)
}

// Save Section API Call
async function saveSection(sectionKey) {
  saving.value = true
  successMsg.value = ''
  errorMsg.value = ''

  let payload = {
    title: '',
    content: null
  }

  if (sectionKey === 'hero') {
    payload.title = form.value.hero.title
    payload.content = form.value.hero.content
  } else if (sectionKey === 'features') {
    payload.title = form.value.features.title
    payload.content = form.value.features.items
  } else if (sectionKey === 'pricing') {
    payload.title = form.value.pricing.title
    payload.content = form.value.pricing.items.map(p => ({
      name: p.name,
      price: p.price,
      desc: p.desc,
      specs: p.specsText.split('\n').map(s => s.trim()).filter(s => s !== '')
    }))
  } else if (sectionKey === 'contact_info') {
    payload.title = form.value.contact_info.title
    payload.content = form.value.contact_info.items
  }

  try {
    await api.put(`/landing-contents/${sectionKey}`, payload)
    successMsg.value = `Berhasil menyimpan perubahan pada section '${sectionKey}'.`
    // Auto clear alert
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal menyimpan perubahan ke backend.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.landing-editor-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.tabs-card {
  padding: 10px;
}
.editor-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.tab-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-family: inherit;
  font-size: 14px;
  font-weight: 500;
  padding: 10px 20px;
  cursor: pointer;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}
.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}
.tab-btn.active {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(13, 148, 136, 0.15));
  border: 1px solid rgba(14, 165, 233, 0.25);
  color: var(--text-primary);
  font-weight: 600;
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 60px;
  color: var(--text-secondary);
}
.form-card {
  padding: 28px;
}
.form-card h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 6px;
}
.tab-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 24px;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  border-top: 1px solid var(--glass-border);
  padding-top: 18px;
}

/* Feature & Pricing Editor specific */
.features-list, .pricing-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .features-list, .pricing-list {
    grid-template-columns: 1fr;
  }
}
.feature-item-form, .price-item-form {
  padding: 20px;
  background: rgba(255, 255, 255, 0.02);
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 10px;
}
.item-header h4 {
  font-size: 14px;
  font-weight: 600;
  color: var(--primary-light);
}
</style>
