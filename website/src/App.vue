<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import {
  Layers, CheckSquare, Boxes, MessageCircle, ShieldCheck,
  Check, Mail, Phone, MapPin, Loader2, ArrowRight, X, User, Lock, Award, Menu,
  Upload, CheckCircle, Info
} from 'lucide-vue-next'

const API_BASE = 'http://localhost:8000/api'
const LOGIN_APP_URL = 'http://localhost:5173'

const loading = ref(true)
const config = ref({
  hero: { title: 'Platform Manajemen Event Terintegrasi', content: 'Kelola rundown, logistik gudang global, alokasi anggaran, dan koordinasi tim secara instan dalam satu ekosistem multi-tenant premium.' },
  features: { title: 'Fitur Unggulan EventConnect', content: [] },
  pricing: { title: 'Pilihan Paket Layanan', content: [] },
  contact_info: { title: 'Informasi Kontak Kami', content: { email: 'support@eventconnect.com', phone: '+62 821-4567-8901', address: 'Gedung Cyber Plaza Lantai 12, Jl. Kuningan Mulia, Jakarta Selatan, Indonesia' } }
})

// Contact Form
const contactForm = ref({ name: '', email: '', subject: '', message: '' })
const submittingContact = ref(false)
const contactSuccess = ref('')
const contactError = ref('')

// Register EO Modal
const showRegisterModal = ref(false)
const currentStep = ref(1) // 1: Form details, 2: Mock Payment Simulation (for paid plans)
const mockReceiptFile = ref(null)
const mockReceiptUploaded = ref(false)
const registerForm = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  admin_name: '',
  admin_email: '',
  admin_password: '',
  plan: 'free'
})
const registering = ref(false)
const registerSuccess = ref(false)
const registerError = ref('')
const createdOrg = ref(null)

// Load Page Copy from API
async function loadPageData() {
  loading.value = true
  try {
    const res = await axios.get(`${API_BASE}/landing/contents`)
    if (res.data.hero) config.value.hero = res.data.hero
    if (res.data.features) config.value.features = res.data.features
    if (res.data.pricing) config.value.pricing = res.data.pricing
    if (res.data.contact_info) config.value.contact_info = res.data.contact_info
  } catch (err) {
    console.error('Gagal mengambil konten dinamis, menggunakan fallback default.', err)
  } finally {
    loading.value = false
  }
}

// Submit Contact Form
async function submitContact() {
  submittingContact.value = true
  contactSuccess.value = ''
  contactError.value = ''
  try {
    const res = await axios.post(`${API_BASE}/landing/contact`, contactForm.value)
    contactSuccess.value = res.data.message
    contactForm.value = { name: '', email: '', subject: '', message: '' }
    setTimeout(() => { contactSuccess.value = '' }, 5000)
  } catch (err) {
    contactError.value = err.response?.data?.message || 'Gagal mengirim pesan. Silakan coba kembali.'
  } finally {
    submittingContact.value = false
  }
}

// Register Organization (SaaS self-signup)
async function submitRegister() {
  registering.value = true
  registerError.value = ''
  try {
    const res = await axios.post(`${API_BASE}/landing/register`, registerForm.value)
    createdOrg.value = res.data.organization
    registerSuccess.value = true
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      registerError.value = Object.values(errors).flat().join(' ')
    } else {
      registerError.value = err.response?.data?.message || 'Registrasi gagal. Silakan periksa kembali data Anda.'
    }
  } finally {
    registering.value = false
  }
}

function openRegister(plan = 'free') {
  registerSuccess.value = false
  registerError.value = ''
  currentStep.value = 1
  mockReceiptFile.value = null
  mockReceiptUploaded.value = false
  registerForm.value = {
    name: '',
    email: '',
    phone: '',
    address: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    plan: plan
  }
  showRegisterModal.value = true
}

function closeRegister() {
  showRegisterModal.value = false
  createdOrg.value = null
  registerSuccess.value = false
  currentStep.value = 1
  mockReceiptFile.value = null
  mockReceiptUploaded.value = false
}

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    mockReceiptFile.value = file
    mockReceiptUploaded.value = true
  }
}

function handleFictitiousPay() {
  mockReceiptUploaded.value = true
}

// Smooth scrolling
function scrollTo(id) {
  const el = document.querySelector(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

const mobileNavOpen = ref(false)

function handleMobileNavClick(id) {
  mobileNavOpen.value = false
  scrollTo(id)
}

function openRegisterMobile() {
  mobileNavOpen.value = false
  openRegister()
}

// Interactive Feature Showcase State
const activeShowcaseTab = ref('rundown')
const mockRundowns = ref([
  { id: 1, time: '09:00 - 09:30', title: 'Registrasi Ulang & Kopi Pagi', status: 'completed' },
  { id: 2, time: '09:30 - 10:00', title: 'Sambutan Owner & Pembukaan Acara', status: 'live' },
  { id: 3, time: '10:00 - 11:30', title: 'Sesi Panel Utama & Q&A', status: 'pending' },
])
const isLiveSimulating = ref(false)

function simulateLive() {
  if (isLiveSimulating.value) return
  isLiveSimulating.value = true
  setTimeout(() => {
    mockRundowns.value[1].status = 'completed'
    mockRundowns.value[2].status = 'live'
    setTimeout(() => {
      mockRundowns.value[2].status = 'completed'
      isLiveSimulating.value = false
    }, 2000)
  }, 2000)
}

function resetShowcase() {
  mockRundowns.value = [
    { id: 1, time: '09:00 - 09:30', title: 'Registrasi Ulang & Kopi Pagi', status: 'completed' },
    { id: 2, time: '09:30 - 10:00', title: 'Sambutan Owner & Pembukaan Acara', status: 'live' },
    { id: 3, time: '10:00 - 11:30', title: 'Sesi Panel Utama & Q&A', status: 'pending' },
  ]
  isLiveSimulating.value = false
}

const mockTasks = ref([
  { id: 101, title: 'Hubungi Vendor Panggung', col: 'todo' },
  { id: 102, title: 'Finalisasi Desain Booklet', col: 'in_progress' },
  { id: 103, title: 'Kirim Undangan VVIP', col: 'done' },
])

function moveMockTask(task) {
  if (task.col === 'todo') {
    task.col = 'in_progress'
  } else if (task.col === 'in_progress') {
    task.col = 'done'
  } else {
    task.col = 'todo'
  }
}

// Pricing & ROI Calculator State
const sliderEvents = ref(3)
const sliderUsers = ref(5)

const calculatedPlan = computed(() => {
  if (sliderEvents.value > 5 || sliderUsers.value > 10) {
    return {
      name: 'Business Plan',
      price: 'Rp 299.000 / bln',
      desc: 'Cocok untuk EO berskala sedang hingga besar.',
      timeSaved: sliderEvents.value * 12
    }
  } else {
    return {
      name: 'Starter Plan',
      price: 'Rp 0 (Free)',
      desc: 'Ideal untuk EO pemula dengan kebutuhan dasar.',
      timeSaved: sliderEvents.value * 8
    }
  }
})

// FAQ Accordion State
const faqs = ref([])
const activeFaqId = ref(null)
const fallbackFaqs = [
  { id: 1, question: 'Apakah EventConnect gratis untuk dicoba?', answer: 'Ya! EventConnect menyediakan pendaftaran mandiri (self-signup) gratis untuk Event Organizer baru. Anda akan langsung mendapatkan organisasi dengan kuota gratis uji coba (seperti 1 event aktif dan 5 anggota tim) tanpa perlu kartu kredit.' },
  { id: 2, question: 'Bagaimana cara meningkatkan kuota jumlah event atau anggota tim?', answer: 'Anda dapat menghubungi administrator platform EventConnect melalui formulir "Hubungi Kami" di halaman ini untuk melakukan peningkatan kapasitas kuota (upgrade plan) sesuai dengan kebutuhan operasional Event Organizer Anda.' },
  { id: 3, question: 'Apakah data rundown dan anggaran event kami aman?', answer: 'Tentu saja. EventConnect dibangun menggunakan arsitektur keamanan multi-tenant. Ini berarti data organisasi Anda terisolasi secara logis dari organisasi lain, menjamin kerahasiaan rundown, data anggaran, dan inventaris logistik Anda.' },
]

async function loadFaqs() {
  try {
    const res = await axios.get(`${API_BASE}/landing/faqs`)
    faqs.value = res.data
  } catch (err) {
    console.error('Gagal mengambil FAQ, menggunakan fallback.', err)
  }
}

function toggleFaq(id) {
  activeFaqId.value = activeFaqId.value === id ? null : id
}

onMounted(() => {
  loadPageData()
  loadFaqs()
})
</script>

<template>
  <div class="landing-layout">
    <!-- Navbar -->
    <header class="navbar glass-card" :class="{ 'mobile-open': mobileNavOpen }">
      <div class="nav-brand">
        <img src="/logo.png" alt="EventConnect Logo" class="brand-logo" />
      </div>
      <nav class="nav-links">
        <a href="#features" @click.prevent="handleMobileNavClick('#features')">Fitur</a>
        <a href="#sandbox" @click.prevent="handleMobileNavClick('#sandbox')">Interactive Sandbox</a>
        <a href="#pricing" @click.prevent="handleMobileNavClick('#pricing')">Paket Harga</a>
        <a href="#faq" @click.prevent="handleMobileNavClick('#faq')">FAQ</a>
        <a href="#contact" @click.prevent="handleMobileNavClick('#contact')">Hubungi Kami</a>
        <a :href="LOGIN_APP_URL" class="mobile-login-link">Login App</a>
        <button class="btn btn-primary mobile-signup-btn" @click="openRegisterMobile">Daftarkan EO</button>
      </nav>
      <div class="nav-actions">
        <a :href="LOGIN_APP_URL" class="btn btn-glass btn-login">Login App</a>
        <button class="btn btn-primary btn-signup" @click="openRegister">Daftarkan EO</button>
      </div>
      <button class="mobile-nav-toggle" @click="mobileNavOpen = !mobileNavOpen" aria-label="Toggle Navigation">
        <X v-if="mobileNavOpen" :size="20" />
        <Menu v-else :size="20" />
      </button>
    </header>

    <!-- Hero Section -->
    <section class="section-hero">
      <div class="hero-content">
        <div class="pill-tag">EventConnect Platform SaaS</div>
        <h1>{{ config.hero.title }}</h1>
        <p>{{ config.hero.content }}</p>
        <div class="hero-ctas">
          <button class="btn btn-primary btn-lg" @click="openRegister">
            <span>Mulai Sekarang</span>
            <ArrowRight :size="18" />
          </button>
          <a href="#features" @click.prevent="scrollTo('#features')" class="btn btn-glass btn-lg">Pelajari Fitur</a>
        </div>
      </div>
      <div class="hero-visual">
        <div class="visual-card glass-card">
          <div class="card-bar">
            <span class="dot red"></span>
            <span class="dot yellow"></span>
            <span class="dot green"></span>
            <span class="bar-title">dashboard_preview.exe</span>
          </div>
          <div class="visual-body">
            <!-- Grid representation of real app features -->
            <div class="v-grid">
              <div class="v-card glass-card">
                <span class="v-label">EVENT TERINTEGRASI</span>
                <span class="v-val">12 Aktif</span>
              </div>
              <div class="v-card glass-card">
                <span class="v-label">PERSONNEL ONLINE</span>
                <span class="v-val">45 Anggota</span>
              </div>
              <div class="v-card glass-card" style="grid-column: span 2;">
                <span class="v-label">EFISIENSI BUDGET</span>
                <div class="v-bar"><div class="v-fill" style="width: 82%;"></div></div>
                <span class="v-sub">82% Digunakan</span>
              </div>
            </div>
            <div class="v-tasks glass-card">
              <span class="v-label">TIMELINE & WORKFLOW</span>
              <div class="t-row"><Check :size="12" style="color: var(--success)" /> <span>Sewa Sound System</span></div>
              <div class="t-row"><Loader2 :size="12" class="spinner-icon" style="color: var(--warning)" /> <span>Evaluasi Rundown Panggung</span></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Interactive Showcase Section -->
    <section id="sandbox" class="section-showcase">
      <div class="section-header">
        <h2>Interactive Sandbox</h2>
        <p>Rasakan kemudahan mengelola operasional event secara langsung. Klik tab di bawah untuk mencoba simulasi alur kerja platform kami.</p>
      </div>

      <div class="showcase-container glass-card">
        <div class="showcase-sidebar">
          <button class="showcase-tab-btn" :class="{ active: activeShowcaseTab === 'rundown' }" @click="activeShowcaseTab = 'rundown'">
            <Layers :size="16" />
            <span>Rundown Panggung</span>
          </button>
          <button class="showcase-tab-btn" :class="{ active: activeShowcaseTab === 'tasks' }" @click="activeShowcaseTab = 'tasks'">
            <CheckSquare :size="16" />
            <span>Kanban Board Tim</span>
          </button>
        </div>

        <div class="showcase-main">
          <!-- Rundown Showcase -->
          <div v-if="activeShowcaseTab === 'rundown'" class="sim-wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px">
              <span class="sim-title">Live Preview Rundown Acara</span>
              <div style="display:flex; gap:8px">
                <button class="btn btn-primary btn-sm" @click="simulateLive" :disabled="isLiveSimulating">
                  {{ isLiveSimulating ? 'Menyimulasikan...' : 'Mulai Simulasi Live' }}
                </button>
                <button class="btn btn-glass btn-sm" @click="resetShowcase">Reset</button>
              </div>
            </div>

            <div class="sim-rundown-list">
              <div v-for="item in mockRundowns" :key="item.id" class="sim-rundown-item glass-card" :class="item.status">
                <span class="sim-time">{{ item.time }}</span>
                <span class="sim-text">{{ item.title }}</span>
                <span class="sim-status-badge" :class="item.status">{{ item.status.toUpperCase() }}</span>
              </div>
            </div>
          </div>

          <!-- Tasks Showcase -->
          <div v-if="activeShowcaseTab === 'tasks'" class="sim-wrapper">
            <div style="margin-bottom:12px">
              <span class="sim-title">Kanban Workflow Tim</span>
              <p style="font-size:12px; color:var(--text-secondary); margin:4px 0 0">Klik kartu tugas di bawah untuk memindahkannya ke kolom alur kerja berikutnya!</p>
            </div>

            <div class="sim-kanban-board">
              <div class="sim-kanban-col">
                <div class="col-name">TO DO</div>
                <div v-for="t in mockTasks.filter(tk => tk.col === 'todo')" :key="t.id" class="sim-task-card glass-card" @click="moveMockTask(t)">
                  <span>{{ t.title }}</span>
                  <span class="arrow-indicator">&rarr;</span>
                </div>
              </div>
              <div class="sim-kanban-col">
                <div class="col-name">IN PROGRESS</div>
                <div v-for="t in mockTasks.filter(tk => tk.col === 'in_progress')" :key="t.id" class="sim-task-card glass-card in-progress" @click="moveMockTask(t)">
                  <span>{{ t.title }}</span>
                  <span class="arrow-indicator">&rarr;</span>
                </div>
              </div>
              <div class="sim-kanban-col">
                <div class="col-name">DONE</div>
                <div v-for="t in mockTasks.filter(tk => tk.col === 'done')" :key="t.id" class="sim-task-card glass-card done" @click="moveMockTask(t)">
                  <span>{{ t.title }}</span>
                  <span class="check-indicator">&check;</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section-features">
      <div class="section-header">
        <h2>{{ config.features.title }}</h2>
        <p>Kelebihan sistem manajemen event terpusat yang dirancang khusus untuk Event Organizer profesional.</p>
      </div>

      <div v-if="config.features.content.length === 0" class="features-grid">
        <!-- Fallback cards if API fails -->
        <div class="feat-card glass-card">
          <ShieldCheck class="feat-icon" :size="32" />
          <h3>Isolasi Multi-Tenant</h3>
          <p>Data organisasi dan tim Anda tersimpan aman serta terisolasi penuh dari pihak luar.</p>
        </div>
        <div class="feat-card glass-card">
          <CheckSquare class="feat-icon" :size="32" />
          <h3>Kanban Task &amp; Rundown</h3>
          <p>Pantau jadwal acara secara real-time lengkap dengan sistem dependency task &amp; log audit.</p>
        </div>
        <div class="feat-card glass-card">
          <Boxes class="feat-icon" :size="32" />
          <h3>Logistik Global</h3>
          <p>Kelola aset gudang EO secara terpusat, lengkap dengan pelacakan distribusi alat ke event aktif.</p>
        </div>
        <div class="feat-card glass-card">
          <MessageCircle class="feat-icon" :size="32" />
          <h3>Chat Kolaborasi &amp; Laporan</h3>
          <p>Komunikasi instan per-kegiatan dan hasilkan laporan evaluasi pasca-event secara otomatis.</p>
        </div>
      </div>

      <div v-else class="features-grid">
        <div v-for="(feat, idx) in config.features.content" :key="idx" class="feat-card glass-card">
          <!-- Assign icon based on index -->
          <component :is="[ShieldCheck, CheckSquare, Boxes, MessageCircle][idx % 4]" class="feat-icon" :size="32" />
          <h3>{{ feat.title }}</h3>
          <p>{{ feat.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="section-pricing">
      <div class="section-header">
        <h2>{{ config.pricing.title }}</h2>
        <p>Investasi transparan untuk operasional event organizer Anda yang lebih produktif dan efisien.</p>
      </div>

      <!-- Pricing & ROI Calculator -->
      <div class="calculator-wrap glass-card" style="margin-bottom: 48px; padding: 28px;">
        <h3 style="margin-top:0; font-size:18px; text-align:center; margin-bottom:20px">Kalkulator Rekomendasi Paket &amp; ROI</h3>
        <div class="calc-grid">
          <div class="calc-inputs" style="display:flex; flex-direction:column; gap:16px">
            <div class="slider-group">
              <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:6px">
                <span style="color:var(--text-secondary)">Jumlah Event Aktif / Bulan</span>
                <strong style="color:var(--primary-light)">{{ sliderEvents }} Event</strong>
              </div>
              <input v-model.number="sliderEvents" type="range" min="1" max="20" class="glass-slider" style="width:100%" />
            </div>
            <div class="slider-group">
              <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:6px">
                <span style="color:var(--text-secondary)">Jumlah Anggota Tim (User)</span>
                <strong style="color:var(--primary-light)">{{ sliderUsers }} Anggota</strong>
              </div>
              <input v-model.number="sliderUsers" type="range" min="1" max="50" class="glass-slider" style="width:100%" />
            </div>
          </div>

          <div class="calc-results glass-card" style="padding:16px; background:rgba(255,255,255,0.02); display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;">
            <span style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em">Rekomendasi Paket</span>
            <strong style="font-size:20px; color:#fff; margin-top:4px">{{ calculatedPlan.name }}</strong>
            <span style="font-size:16px; color:var(--primary-light); font-weight:700; margin-top:2px">{{ calculatedPlan.price }}</span>
            <div style="margin-top:12px; border-top:1px solid rgba(255,255,255,0.08); padding-top:8px; width:100%">
              <span style="font-size:11px; color:var(--text-muted); text-transform:uppercase; display:block">Estimasi Waktu Dihemat</span>
              <strong style="font-size:16px; color:var(--success)">~{{ calculatedPlan.timeSaved }} Jam / Bulan</strong>
              <p style="font-size:11px; color:var(--text-secondary); margin:4px 0 0">Berdasarkan penyederhanaan rundown &amp; otomatisasi logistik.</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="config.pricing.content.length === 0" class="pricing-grid">
        <!-- Fallback Pricing cards -->
        <div class="price-card glass-card">
          <h3>Starter Plan</h3>
          <div class="price-val">Rp 0 <span class="price-sub">/ Free</span></div>
          <p>Cocok untuk Event Organizer pemula.</p>
          <ul class="price-specs">
            <li><Check :size="14" /> Maksimal 5 User</li>
            <li><Check :size="14" /> Maksimal 3 Event Aktif</li>
            <li><Check :size="14" /> Akses Fitur Dasar</li>
          </ul>
          <button class="btn btn-glass" @click="openRegister('free')">Mulai Gratis</button>
        </div>
        <div class="price-card glass-card featured">
          <div class="featured-badge">TERPOPULER</div>
          <h3>Business Plan</h3>
          <div class="price-val">Rp 299.000 <span class="price-sub">/ bln</span></div>
          <p>Solusi terbaik untuk EO berkembang.</p>
          <ul class="price-specs">
            <li><Check :size="14" /> Maksimal 25 User</li>
            <li><Check :size="14" /> Maksimal 20 Event Aktif</li>
            <li><Check :size="14" /> Fitur Logistik &amp; Rundown</li>
            <li><Check :size="14" /> Live Chat &amp; Laporan Evaluasi</li>
          </ul>
          <button class="btn btn-primary" @click="openRegister('business')">Pilih Paket</button>
        </div>
      </div>

      <div v-else class="pricing-grid">
        <div 
          v-for="(plan, idx) in config.pricing.content" 
          :key="idx" 
          class="price-card glass-card"
          :class="{ featured: idx === 1 }"
        >
          <div v-if="idx === 1" class="featured-badge">TERPOPULER</div>
          <h3>{{ plan.name }}</h3>
          <div class="price-val">{{ plan.price }}</div>
          <p>{{ plan.desc }}</p>
          <ul class="price-specs">
            <li v-for="spec in plan.specs" :key="spec">
              <Check :size="14" /> <span>{{ spec }}</span>
            </li>
          </ul>
          <button 
            class="btn" 
            :class="idx === 1 ? 'btn-primary' : 'btn-glass'" 
            @click="openRegister(idx === 0 ? 'free' : (idx === 1 ? 'business' : 'enterprise'))"
          >
            Mulai Sekarang
          </button>
        </div>
      </div>
    </section>

    <!-- FAQ Accordion Section -->
    <section id="faq" class="section-faq">
      <div class="section-header">
        <h2>Tanya Jawab (FAQ)</h2>
        <p>Temukan jawaban untuk pertanyaan umum mengenai pendaftaran, kuota, dan keamanan platform kami.</p>
      </div>

      <div class="faq-accordion-wrap">
        <div 
          v-for="faq in (faqs.length ? faqs : fallbackFaqs)" 
          :key="faq.id" 
          class="faq-accordion-item glass-card"
          :class="{ active: activeFaqId === faq.id }"
        >
          <div class="faq-question" @click="toggleFaq(faq.id)">
            <span>{{ faq.question }}</span>
            <span class="faq-arrow" style="font-size: 16px; transition: transform 0.2s;">{{ activeFaqId === faq.id ? '▲' : '▼' }}</span>
          </div>
          <div class="faq-answer" v-show="activeFaqId === faq.id">
            <p>{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact" class="section-contact">
      <div class="contact-grid">
        <div class="contact-info">
          <h2>{{ config.contact_info.title }}</h2>
          <p class="contact-intro">Apakah Anda memiliki pertanyaan mengenai platform kami? Jangan ragu untuk mengirimkan pesan kepada admin kami.</p>
          
          <div class="info-list">
            <div class="info-item">
              <Mail class="info-icon" :size="20" />
              <div>
                <span class="info-lbl">Email Kami</span>
                <span class="info-val">{{ config.contact_info.content.email }}</span>
              </div>
            </div>
            <div class="info-item">
              <Phone class="info-icon" :size="20" />
              <div>
                <span class="info-lbl">Hubungi Telepon</span>
                <span class="info-val">{{ config.contact_info.content.phone }}</span>
              </div>
            </div>
            <div class="info-item">
              <MapPin class="info-icon" :size="20" />
              <div>
                <span class="info-lbl">Lokasi Kantor</span>
                <span class="info-val">{{ config.contact_info.content.address }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="contact-form-wrap glass-card">
          <form @submit.prevent="submitContact">
            <div v-if="contactSuccess" class="alert alert-success">{{ contactSuccess }}</div>
            <div v-if="contactError" class="alert alert-error">{{ contactError }}</div>
            
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input v-model="contactForm.name" class="glass-input" required placeholder="Masukkan nama lengkap" />
            </div>
            <div class="form-group">
              <label>Alamat Email</label>
              <input v-model="contactForm.email" type="email" class="glass-input" required placeholder="nama@domain.com" />
            </div>
            <div class="form-group">
              <label>Subjek</label>
              <input v-model="contactForm.subject" class="glass-input" required placeholder="Masukkan subjek pesan" />
            </div>
            <div class="form-group">
              <label>Isi Pesan Anda</label>
              <textarea v-model="contactForm.message" class="glass-input" rows="4" required placeholder="Tulis rincian pesan di sini..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="submittingContact" style="width: 100%;">
              <Loader2 v-if="submittingContact" :size="16" class="spinner-icon" />
              <span v-else>Kirim Pesan ke Admin</span>
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <p>&copy; 2026 EventConnect Platform. All rights reserved.</p>
    </footer>

    <!-- Register EO Modal -->
    <Transition name="fade">
      <div v-if="showRegisterModal" class="modal-overlay" @click.self="closeRegister">
        <div class="modal-box glass-card">
          <div class="modal-header">
            <h2>Daftarkan Organisasi EO Baru</h2>
            <button class="btn-close" @click="closeRegister"><X :size="18" /></button>
          </div>

          <!-- Successful Signup -->
          <div v-if="registerSuccess" class="success-signup-content">
            <div class="success-icon-wrap">
              <Award :size="48" style="color: var(--success);" />
            </div>
            <h3>Pendaftaran Berhasil!</h3>
            <p v-if="registerForm.plan === 'free'">
              Organisasi <strong>{{ createdOrg?.name }}</strong> telah terdaftar aktif di platform.
            </p>
            <p v-else>
              Pendaftaran organisasi <strong>{{ createdOrg?.name }}</strong> berhasil diajukan dengan status <strong>PENDING</strong>. Akses login akan aktif segera setelah pembayaran Anda dikonfirmasi oleh Admin Backoffice.
            </p>
            
            <div class="credentials-box">
              <span class="cred-title">Akses Portal Tenant Anda</span>
              <div class="cred-item">
                <span class="c-label">Subdomain Slug:</span>
                <span class="c-val">{{ createdOrg?.slug }}</span>
              </div>
              <div class="cred-item">
                <span class="c-label">Tenant App URL:</span>
                <a :href="LOGIN_APP_URL" target="_blank" class="c-link">{{ LOGIN_APP_URL }}</a>
              </div>
            </div>
            
            <button class="btn btn-primary" style="width: 100%;" @click="closeRegister">
              {{ registerForm.plan === 'free' ? 'Tutup & Login' : 'Selesai & Tutup' }}
            </button>
          </div>

          <!-- Registration Form Steps -->
          <div v-else>
            <!-- Step 1: Form details -->
            <form v-if="currentStep === 1" @submit.prevent="registerForm.plan === 'free' ? submitRegister() : (currentStep = 2)" class="register-form">
              <div v-if="registerError" class="alert alert-error">{{ registerError }}</div>

              <!-- Paket Selector -->
              <div class="form-group">
                <label>Paket Layanan *</label>
                <select v-model="registerForm.plan" class="glass-input" required>
                  <option value="free">Starter Plan (Gratis)</option>
                  <option value="business">Business Plan (Rp 299.000 / bln)</option>
                  <option value="enterprise">Enterprise Plan (Rp 999.000 / bln)</option>
                </select>
              </div>

              <!-- EO Info -->
              <div class="form-section-title">Informasi Organisasi EO</div>
              <div class="form-group">
                <label>Nama Event Organizer (EO) *</label>
                <input v-model="registerForm.name" class="glass-input" required placeholder="Masukkan nama Event Organizer" />
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Email EO *</label>
                  <input v-model="registerForm.email" type="email" class="glass-input" required placeholder="info@perusahaan.com" />
                </div>
                <div class="form-group">
                  <label>Telepon EO</label>
                  <input v-model="registerForm.phone" class="glass-input" placeholder="081234567890" />
                </div>
              </div>
              <div class="form-group">
                <label>Alamat EO</label>
                <input v-model="registerForm.address" class="glass-input" placeholder="Masukkan alamat kantor" />
              </div>

              <!-- Admin Info -->
              <div class="form-section-title" style="margin-top: 20px;">Akun Super Admin Anda</div>
              <div class="form-group">
                <label>Nama Lengkap Admin *</label>
                <input v-model="registerForm.admin_name" class="glass-input" required placeholder="Masukkan nama lengkap" />
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Email Login Admin *</label>
                  <input v-model="registerForm.admin_email" type="email" class="glass-input" required placeholder="nama@domain.com" />
                </div>
                <div class="form-group">
                  <label>Password Akun * (min. 6 kar)</label>
                  <input v-model="registerForm.admin_password" type="password" class="glass-input" required placeholder="Masukkan kata sandi" />
                </div>
              </div>

              <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn btn-glass" @click="closeRegister">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="registering">
                  <Loader2 v-if="registering" :size="16" class="spinner-icon" />
                  <span v-else>{{ registerForm.plan === 'free' ? 'Konfirmasi Pendaftaran' : 'Lanjut ke Pembayaran' }}</span>
                </button>
              </div>
            </form>

            <!-- Step 2: Fictitious Payment Screen -->
            <div v-else-if="currentStep === 2" class="payment-step-content">
              <div v-if="registerError" class="alert alert-error">{{ registerError }}</div>
              
              <div class="form-section-title">Simulasi Pembayaran Fiktif</div>
              
              <div class="plan-summary-card glass-card" style="padding: 16px; margin-bottom: 20px; background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 12px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Paket Terpilih</span>
                    <strong style="color: #fff; font-size: 16px;">
                      {{ registerForm.plan === 'business' ? 'Business Plan' : 'Enterprise Plan' }}
                    </strong>
                  </div>
                  <div style="text-align: right;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Total Tagihan</span>
                    <strong style="color: var(--primary-light); font-weight: 700; font-size: 16px;">
                      {{ registerForm.plan === 'business' ? 'Rp 299.000' : 'Rp 999.000' }}
                    </strong>
                  </div>
                </div>
              </div>

              <div class="payment-instructions" style="font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 20px;">
                <p style="margin-bottom: 12px;">Selesaikan pembayaran fiktif Anda dengan mentransfer ke salah satu rekening berikut:</p>
                
                <div class="payment-methods" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                  <div class="pm-card glass-card" style="padding: 12px; text-align: center; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05); border-radius: 8px;">
                    <strong style="color: #fff; display: block; margin-bottom: 4px; font-size: 12px;">Transfer Bank (Fiktif)</strong>
                    <span style="font-size: 11px; color: var(--text-muted); display: block;">Bank Mandiri</span>
                    <span style="font-size: 13px; color: var(--primary-light); font-weight: bold; letter-spacing: 0.05em;">123-00-998877-66</span>
                    <span style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 4px;">a.n. EventConnect Platform</span>
                  </div>
                  <div class="pm-card glass-card" style="padding: 12px; text-align: center; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <strong style="color: #fff; display: block; margin-bottom: 4px; font-size: 12px;">QRIS (Fiktif)</strong>
                    <div style="width: 50px; height: 50px; background: #fff; padding: 4px; border-radius: 4px; margin: 2px 0;">
                      <svg viewBox="0 0 100 100" style="width: 100%; height: 100%;">
                        <rect width="100" height="100" fill="white"/>
                        <rect x="10" y="10" width="20" height="20" fill="black"/>
                        <rect x="13" y="13" width="14" height="14" fill="white"/>
                        <rect x="16" y="16" width="8" height="8" fill="black"/>
                        <rect x="70" y="10" width="20" height="20" fill="black"/>
                        <rect x="73" y="13" width="14" height="14" fill="white"/>
                        <rect x="76" y="16" width="8" height="8" fill="black"/>
                        <rect x="10" y="70" width="20" height="20" fill="black"/>
                        <rect x="13" y="73" width="14" height="14" fill="white"/>
                        <rect x="16" y="76" width="8" height="8" fill="black"/>
                        <rect x="40" y="20" width="10" height="10" fill="black"/>
                        <rect x="50" y="30" width="10" height="10" fill="black"/>
                        <rect x="40" y="50" width="20" height="10" fill="black"/>
                        <rect x="50" y="70" width="10" height="20" fill="black"/>
                        <rect x="80" y="80" width="10" height="10" fill="black"/>
                        <rect x="70" y="50" width="10" height="10" fill="black"/>
                      </svg>
                    </div>
                    <span style="font-size: 9px; color: var(--text-muted);">Pindai QR fiktif</span>
                  </div>
                </div>
              </div>

              <!-- File Upload mockup -->
              <div class="form-group">
                <label>Unggah Bukti Transfer (Fiktif) *</label>
                <div class="receipt-upload-box" style="border: 2px dashed rgba(255,255,255,0.15); border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; position: relative; transition: all 0.2s;" :style="mockReceiptUploaded ? 'border-color: var(--success); background: rgba(16,185,129,0.02);' : ''">
                  <input type="file" @change="handleFileChange" accept="image/*" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;" />
                  <div v-if="!mockReceiptUploaded">
                    <Upload :size="24" style="color: var(--text-muted); margin-bottom: 8px; margin: 0 auto 8px;" />
                    <p style="font-size: 13px; margin: 0 0 4px; color: #fff;">Pilih file bukti transfer fiktif</p>
                    <span style="font-size: 11px; color: var(--text-muted);">Mendukung PNG, JPG (Maks. 2MB)</span>
                  </div>
                  <div v-else>
                    <CheckCircle :size="24" style="color: var(--success); margin-bottom: 8px; margin: 0 auto 8px;" />
                    <p style="font-size: 13px; margin: 0 0 4px; color: #fff; font-weight: 600;">Bukti transfer terunggah (Simulasi)</p>
                    <span style="font-size: 11px; color: var(--text-muted);">{{ mockReceiptFile ? mockReceiptFile.name : 'bukti_transfer_fiktif.png' }}</span>
                  </div>
                </div>
              </div>

              <div style="margin-top: 16px; font-size: 12px; color: var(--text-secondary); display: flex; gap: 8px; align-items: flex-start; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <Info :size="16" style="flex-shrink: 0; color: var(--primary-light); margin-top: 1px;" />
                <span>Ini adalah sistem pendaftaran Capstone fiktif. Silakan upload gambar apa saja atau langsung unggah bukti untuk melanjutkan.</span>
              </div>

              <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn btn-glass" @click="currentStep = 1">Kembali</button>
                <button type="button" class="btn btn-primary" :disabled="registering || !mockReceiptUploaded" @click="submitRegister">
                  <Loader2 v-if="registering" :size="16" class="spinner-icon" />
                  <span v-else>Konfirmasi Pembayaran &amp; Daftar</span>
                </button>
              </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  </div>
</template>

<style scoped>
.landing-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Navbar */
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 32px;
  position: fixed;
  top: 16px;
  left: 50%;
  transform: translateX(-50%);
  width: 90%;
  max-width: 1200px;
  z-index: 1000;
  border-radius: 20px;
}
.nav-brand {
  display: flex;
  align-items: center;
  gap: 10px;
}
.brand-logo {
  height: 52px;
  object-fit: contain;
  filter: drop-shadow(0 0 2px rgba(255, 255, 255, 0.7));
}
.nav-links {
  display: flex;
  gap: 24px;
}
.nav-links a {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.2s;
}
.nav-links a:hover {
  color: #fff;
}
.nav-actions {
  display: flex;
  gap: 12px;
}
.mobile-nav-toggle {
  display: none;
  background: none;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 4px;
}
.mobile-login-link {
  display: none;
}
.mobile-signup-btn {
  display: none;
}

/* Hero Section */
.section-hero {
  max-width: 1200px;
  margin: 120px auto 0;
  padding: 60px 24px;
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 40px;
  align-items: center;
}
@media (max-width: 900px) {
  .section-hero {
    grid-template-columns: 1fr;
    text-align: center;
    margin-top: 80px;
  }
}
.hero-content h1 {
  font-size: 48px;
  line-height: 1.2;
  margin-bottom: 20px;
  background: linear-gradient(135deg, #fff 60%, var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-content p {
  color: var(--text-secondary);
  font-size: 16px;
  margin-bottom: 32px;
  max-width: 540px;
}
@media (max-width: 900px) {
  .hero-content p {
    margin-left: auto;
    margin-right: auto;
  }
}
.hero-ctas {
  display: flex;
  gap: 16px;
}
@media (max-width: 900px) {
  .hero-ctas {
    justify-content: center;
  }
}
.pill-tag {
  display: inline-block;
  padding: 4px 12px;
  background: rgba(14, 165, 233, 0.15);
  border: 1px solid rgba(14, 165, 233, 0.25);
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  color: var(--primary-light);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 20px;
}

/* Hero Visual Mockup */
.hero-visual {
  display: flex;
  justify-content: center;
}
.visual-card {
  width: 100%;
  max-width: 480px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08);
}
.card-bar {
  background: rgba(0,0,0,0.3);
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 6px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.card-bar .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.card-bar .dot.red { background: #ef4444; }
.card-bar .dot.yellow { background: #f59e0b; }
.card-bar .dot.green { background: #10b981; }
.bar-title {
  font-size: 11px;
  font-family: monospace;
  color: var(--text-muted);
  margin-left: 8px;
}
.visual-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.v-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.v-card {
  padding: 12px;
  background: rgba(255,255,255,0.02);
  display: flex;
  flex-direction: column;
  border-radius: 12px;
}
.v-label {
  font-size: 9px;
  font-weight: 600;
  color: var(--text-muted);
  letter-spacing: 0.05em;
}
.v-val {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  margin-top: 4px;
}
.v-bar {
  width: 100%;
  height: 4px;
  background: rgba(255,255,255,0.1);
  border-radius: 2px;
  overflow: hidden;
  margin-top: 6px;
}
.v-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), var(--success));
  border-radius: 2px;
}
.v-sub {
  font-size: 10px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.v-tasks {
  padding: 12px;
  background: rgba(255,255,255,0.02);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.t-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--text-secondary);
}

/* Sections Shared */
.section-features, .section-pricing, .section-contact {
  max-width: 1200px;
  margin: 0 auto;
  padding: 80px 24px;
}
.section-header {
  text-align: center;
  margin-bottom: 50px;
}
.section-header h2 {
  font-size: 32px;
  margin-bottom: 12px;
}
.section-header p {
  color: var(--text-secondary);
  max-width: 600px;
  margin: 0 auto;
  font-size: 15px;
}

/* Features Grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}
.feat-card {
  padding: 32px 24px;
  text-align: center;
  transition: transform 0.25s;
}
.feat-card:hover {
  transform: translateY(-5px);
}
.feat-icon {
  color: var(--primary-light);
  margin-bottom: 20px;
}
.feat-card h3 {
  font-size: 18px;
  margin-bottom: 10px;
  font-weight: 600;
}
.feat-card p {
  color: var(--text-secondary);
  font-size: 13.5px;
  line-height: 1.5;
}

/* Pricing Grid */
.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  max-width: 1000px;
  margin: 0 auto;
}
.price-card {
  padding: 40px 28px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: relative;
}
.price-card.featured {
  border-color: var(--primary);
  background: rgba(14, 165, 233, 0.08);
  box-shadow: 0 8px 32px rgba(14, 165, 233, 0.15);
}
.featured-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  background: var(--primary);
  color: #fff;
  font-size: 9px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 20px;
}
.price-card h3 {
  font-size: 20px;
  font-weight: 600;
}
.price-val {
  font-size: 32px;
  font-weight: 800;
  color: #fff;
  font-family: 'Outfit', sans-serif;
}
.price-sub {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-secondary);
}
.price-specs {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 12px;
  flex-grow: 1;
}
.price-specs li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  color: var(--text-secondary);
}
.price-specs li svg {
  color: var(--success);
  flex-shrink: 0;
}

/* Contact Grid */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 50px;
  align-items: center;
}
@media (max-width: 992px) {
  .contact-grid {
    grid-template-columns: 1fr;
  }
}
.contact-intro {
  color: var(--text-secondary);
  font-size: 15px;
  margin-top: 12px;
  margin-bottom: 32px;
}
.info-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.info-item {
  display: flex;
  align-items: center;
  gap: 16px;
}
.info-icon {
  color: var(--primary-light);
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(14, 165, 233, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.info-lbl {
  display: block;
  font-size: 11px;
  color: var(--text-muted);
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.05em;
}
.info-val {
  font-size: 14px;
  font-weight: 500;
  color: #fff;
}
.contact-form-wrap {
  padding: 32px;
}
.form-group {
  margin-bottom: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
}
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 13.5px;
  margin-bottom: 20px;
}
.alert-success {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #6ee7b7;
}
.alert-error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
}

/* Footer */
.footer {
  margin-top: auto;
  border-top: 1px solid var(--glass-border);
  padding: 30px 24px;
  text-align: center;
  color: var(--text-muted);
  font-size: 13px;
}

/* Modals & Overlays */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 20px;
}
.modal-box {
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 32px;
  background: rgba(10, 18, 32, 0.95) !important;
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
.modal-box::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--glass-border);
  padding-bottom: 16px;
}
.modal-header h2 {
  font-size: 20px;
  font-weight: 600;
}
.btn-close {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 4px;
  display: flex;
}
.btn-close:hover {
  color: #fff;
}
.form-section-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--primary-light);
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Success Signup Box */
.success-signup-content {
  text-align: center;
  padding: 20px 0;
}
.success-icon-wrap {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}
.success-signup-content h3 {
  font-size: 20px;
  margin-bottom: 10px;
}
.success-signup-content p {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 24px;
}
.credentials-box {
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  padding: 20px;
  text-align: left;
  margin-bottom: 28px;
}
.cred-title {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: var(--primary-light);
  text-transform: uppercase;
  margin-bottom: 12px;
  letter-spacing: 0.05em;
}
.cred-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 12px;
}
.cred-item:last-child {
  margin-bottom: 0;
}
.c-label {
  font-size: 11px;
  color: var(--text-muted);
}
.c-val {
  font-size: 14px;
  font-weight: 600;
  color: #fff;
}
.c-link {
  font-size: 14px;
  color: var(--primary-light);
  font-weight: 600;
  text-decoration: underline;
}

/* Interactive Showcase Section */
.section-showcase {
  max-width: 1200px;
  margin: 0 auto;
  padding: 80px 24px;
}
.showcase-container {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 380px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
}
@media (max-width: 992px) {
  .showcase-container {
    grid-template-columns: 1fr;
  }
}
.showcase-sidebar {
  background: rgba(0, 0, 0, 0.2);
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}
@media (max-width: 992px) {
  .showcase-sidebar {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    flex-direction: row;
    justify-content: center;
    padding: 16px;
  }
}
.showcase-tab-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-family: inherit;
  font-size: 14px;
  font-weight: 500;
  padding: 12px 16px;
  cursor: pointer;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s;
  text-align: left;
}
.showcase-tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}
.showcase-tab-btn.active {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(13, 148, 136, 0.15));
  border: 1px solid rgba(14, 165, 233, 0.25);
  color: #fff;
  font-weight: 600;
}
.showcase-main {
  padding: 32px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.sim-wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.sim-title {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
}
.sim-rundown-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 10px;
}
.sim-rundown-item {
  padding: 14px 20px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(255, 255, 255, 0.01);
  transition: all 0.5s ease;
  flex-wrap: wrap;
  gap: 10px;
}
.sim-rundown-item.completed {
  border-color: rgba(16, 185, 129, 0.2);
  background: rgba(16, 185, 129, 0.04);
}
.sim-rundown-item.live {
  border-color: rgba(14, 165, 233, 0.3);
  background: rgba(14, 165, 233, 0.08);
  box-shadow: 0 0 16px rgba(14, 165, 233, 0.15);
  animation: pulse-border 2s infinite;
}
@keyframes pulse-border {
  0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(14, 165, 233, 0); }
  100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
}
.sim-time {
  font-family: monospace;
  font-size: 13px;
  color: var(--primary-light);
  font-weight: 600;
}
.sim-text {
  font-size: 14px;
  color: #fff;
  flex: 1;
  margin-left: 20px;
}
@media (max-width: 480px) {
  .sim-text { margin-left: 0; }
}
.sim-status-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 20px;
  letter-spacing: 0.05em;
}
.sim-status-badge.completed { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; }
.sim-status-badge.live { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
.sim-status-badge.pending { background: rgba(255, 255, 255, 0.08); color: var(--text-muted); }

/* Kanban Sandbox */
.sim-kanban-board {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 16px;
}
@media (max-width: 640px) {
  .sim-kanban-board {
    grid-template-columns: 1fr;
  }
}
.sim-kanban-col {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 14px;
  padding: 14px;
  min-height: 180px;
  border: 1px solid rgba(255, 255, 255, 0.03);
}
.col-name {
  font-size: 10px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  margin-bottom: 12px;
  text-align: center;
}
.sim-task-card {
  padding: 12px 14px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.03);
  margin-bottom: 10px;
  font-size: 13px;
  color: #fff;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.2s;
}
.sim-task-card:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.05);
}
.sim-task-card.in-progress {
  border-left: 3px solid var(--primary);
}
.sim-task-card.done {
  border-left: 3px solid var(--success);
  opacity: 0.8;
}
.arrow-indicator {
  color: var(--primary-light);
  font-weight: 700;
}
.check-indicator {
  color: var(--success);
  font-weight: 700;
}

/* Calculator Styles */
.calculator-wrap {
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.calc-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 32px;
}
@media (max-width: 640px) {
  .calc-grid {
    grid-template-columns: 1fr;
  }
}
.glass-slider {
  -webkit-appearance: none;
  height: 6px;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.1);
  outline: none;
  margin: 10px 0;
}
.glass-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--primary-light);
  cursor: pointer;
  transition: all 0.15s;
  box-shadow: 0 0 10px var(--primary);
}
.glass-slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}

/* FAQ Accordion Styles */
.section-faq {
  max-width: 800px;
  margin: 0 auto;
  padding: 80px 24px;
}
.faq-accordion-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.faq-accordion-item {
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  overflow: hidden;
  transition: all 0.3s ease;
}
.faq-accordion-item.active {
  border-color: rgba(14, 165, 233, 0.25);
  background: rgba(255, 255, 255, 0.02);
}
.faq-question {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 15px;
  font-weight: 600;
  color: #fff;
}
.faq-question:hover {
  background: rgba(255, 255, 255, 0.02);
}
.faq-answer {
  padding: 0 24px 20px;
  font-size: 13.5px;
  line-height: 1.6;
  color: var(--text-secondary);
  border-top: 1px solid rgba(255, 255, 255, 0.03);
}
.faq-answer p {
  margin: 0;
  padding-top: 16px;
}
.faq-arrow {
  color: var(--text-muted);
}
.faq-accordion-item.active .faq-arrow {
  color: var(--primary-light);
  transform: rotate(180deg);
}

@media (max-width: 992px) {
  .navbar {
    padding: 12px 20px;
    width: 92%;
    top: 12px;
  }
  .nav-links {
    display: none;
  }
  .navbar.mobile-open .nav-links {
    display: flex;
    flex-direction: column;
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    background: rgba(10, 18, 32, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    padding: 24px 20px;
    border-radius: 20px;
    gap: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    align-items: center;
  }
  .navbar.mobile-open .nav-links a {
    font-size: 15px;
    width: 100%;
    text-align: center;
    padding: 6px 0;
  }
  .nav-actions {
    display: none;
  }
  .mobile-nav-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .mobile-login-link {
    display: block;
    color: var(--primary-light) !important;
    font-weight: 600;
    margin-top: 8px;
  }
  .mobile-signup-btn {
    display: block;
    width: 100%;
    margin-top: 4px;
  }

  /* Section & Hero Responsiveness */
  .section-features, .section-pricing, .section-contact, .section-showcase, .section-faq {
    padding: 48px 16px;
  }
  .hero-content h1 {
    font-size: 32px;
  }
  .hero-content p {
    font-size: 14px;
    margin-bottom: 24px;
  }
  .section-header {
    margin-bottom: 32px;
  }
  .section-header h2 {
    font-size: 24px;
  }
  .section-header p {
    font-size: 13.5px;
  }

  /* Sandbox Responsiveness */
  .showcase-sidebar {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    padding: 12px;
    gap: 8px;
  }
  .showcase-tab-btn {
    flex: 1;
    justify-content: center;
    padding: 10px 12px;
    font-size: 13px;
  }
  .showcase-main {
    padding: 20px 16px;
  }

  /* Calculator Responsiveness */
  .calc-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  /* Pricing Responsiveness */
  .pricing-grid {
    grid-template-columns: 1fr;
    max-width: 450px;
    margin: 0 auto;
    gap: 20px;
  }
  .price-card {
    padding: 24px 16px;
  }
  .calculator-wrap {
    padding: 16px !important;
  }

  /* Modal Responsiveness */
  .modal-box {
    padding: 24px 16px;
  }
}
</style>
