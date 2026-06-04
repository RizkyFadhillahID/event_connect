<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" />
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
</p>

# 🎯 EventConnect — Multi-Tenant SaaS Event Management Platform

**EventConnect** adalah platform manajemen event berbasis web yang dibangun dengan arsitektur **Multi-Tenant SaaS** (*Software as a Service*). Platform ini dirancang khusus untuk memfasilitasi operasional Event Organizer (EO) secara profesional — mulai dari perencanaan, pengelolaan tugas, timeline/rundown, anggaran, logistik, hingga evaluasi pasca-event.

---

## 📐 Arsitektur Sistem

Sistem terdiri dari **4 komponen** utama yang berjalan secara independen:

```
┌─────────────────────────────────────────────────────────────────┐
│                      EventConnect Platform                      │
├─────────────┬──────────────┬───────────────┬────────────────────┤
│   Backend   │   Frontend   │   Backoffice  │     Website        │
│  (Laravel)  │   (Vue 3)    │    (Vue 3)    │     (Vue 3)        │
│  Port 8000  │  Port 5173   │  Port 5174    │   Port 5175        │
│  REST API   │  Tenant App  │  Admin Portal │  Landing Page      │
└─────────────┴──────────────┴───────────────┴────────────────────┘
```

| Komponen | Deskripsi | Teknologi |
|----------|-----------|-----------|
| **`backend/`** | REST API server, autentikasi, database, real-time broadcasting | Laravel 13, Sanctum, Reverb |
| **`frontend/`** | Aplikasi operasional EO (tenant) — kelola event, task, rundown, chat, dll. | Vue 3, Vite 8, Pinia, Laravel Echo |
| **`backoffice/`** | Portal admin platform — kelola tenant EO, CMS landing page, inbox | Vue 3, Vite 8, Pinia |
| **`website/`** | Landing page publik — showcase produk, pricing, kontak, pendaftaran EO | Vue 3, Vite 8 |

---

## 📋 Prasyarat (Prerequisites)

Pastikan perangkat lunak berikut sudah terinstal di sistem Anda:

| Software | Versi Minimum | Cek Versi |
|----------|--------------|-----------|
| **PHP** | 8.3+ | `php -v` |
| **Composer** | 2.x | `composer -V` |
| **Node.js** | 18+ | `node -v` |
| **npm** | 9+ | `npm -v` |
| **MySQL** | 8.0+ | `mysql --version` |
| **Git** | 2.x | `git --version` |

> **Catatan:** Untuk environment lokal tanpa MySQL, backend juga mendukung **SQLite** secara default.

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### 1. Clone Repository

```bash
git clone https://github.com/RizkyFadhillahID/event_connect.git
cd event_connect
```

---

### 2. Setup Backend (Laravel API)

```bash
cd backend
```

#### a. Install Dependencies

```bash
composer install
```

#### b. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

#### c. Konfigurasi Database

Buka file `.env` dan sesuaikan konfigurasi database:

**Opsi A — Menggunakan MySQL (Rekomendasi untuk produksi):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_connect
DB_USERNAME=root
DB_PASSWORD=your_password
```

> Pastikan database `event_connect` sudah dibuat terlebih dahulu di MySQL:
> ```sql
> CREATE DATABASE event_connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> ```

**Opsi B — Menggunakan SQLite (untuk pengembangan cepat):**
```env
DB_CONNECTION=sqlite
```
```bash
# Buat file database SQLite
touch database/database.sqlite
```

#### d. Konfigurasi Broadcasting (Real-Time Chat)

Tambahkan konfigurasi berikut di file `.env` untuk mengaktifkan fitur chat real-time:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=eventconnect
REVERB_APP_KEY=eventconnect-key
REVERB_APP_SECRET=eventconnect-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### e. Jalankan Migrasi & Seeding Database

```bash
php artisan migrate --seed
```

Perintah ini akan:
- Membuat semua tabel database
- Membuat organisasi default ("Default Organization")
- Membuat akun Platform Admin (Backoffice)
- Membuat akun Superadmin Tenant beserta data dummy (users, events, dll.)
- Membuat konten awal landing page dan FAQ

#### f. Jalankan Backend Server

```bash
php artisan serve
```

Backend akan berjalan di: **http://localhost:8000**

#### g. (Opsional) Jalankan WebSocket Server untuk Real-Time Chat

Buka terminal baru:
```bash
php artisan reverb:start
```

---

### 3. Setup Frontend — Aplikasi Tenant EO

Buka terminal baru:

```bash
cd frontend
npm install
npm run dev
```

Frontend akan berjalan di: **http://localhost:5173**

#### Konfigurasi API URL (Opsional)

Jika backend berjalan di URL berbeda, buat file `.env` di folder `frontend/`:

```env
VITE_API_URL=http://localhost:8000/api
```

---

### 4. Setup Backoffice — Portal Admin Platform

Buka terminal baru:

```bash
cd backoffice
npm install
npm run dev
```

Backoffice akan berjalan di: **http://localhost:5174**

#### Konfigurasi API URL (Opsional)

Edit file `.env` di folder `backoffice/`:

```env
VITE_API_URL=http://localhost:8000/api/backoffice
```

---

### 5. Setup Website — Landing Page Publik

Buka terminal baru:

```bash
cd website
npm install
npm run dev
```

Website akan berjalan di: **http://localhost:5175**

---

## 🔑 Akun Default (Setelah Seeding)

### Portal Backoffice (Admin Platform)
| Field | Value |
|-------|-------|
| URL | http://localhost:5174 |
| Email | `admin@eventconnect.com` |
| Password | `password123` |
| Role | Platform Owner |

### Aplikasi Tenant (Frontend EO)
| Field | Value |
|-------|-------|
| URL | http://localhost:5173 |
| Email (Superadmin) | `superadmin@eventconnect.com` |
| Email (Project Manager) | `pm@eventconnect.com` |
| Email (Staff) | `planner@eventconnect.com` |
| Password (semua akun) | `password123` |

---

## 🏗️ Ringkasan Port & URL

| Komponen | URL | Keterangan |
|----------|-----|------------|
| Backend API | http://localhost:8000/api | REST API Server |
| WebSocket (Reverb) | http://localhost:8080 | Real-Time Broadcasting |
| Frontend (Tenant) | http://localhost:5173 | Aplikasi Event Organizer |
| Backoffice (Admin) | http://localhost:5174 | Portal Admin Platform |
| Website (Landing) | http://localhost:5175 | Landing Page Publik |

---

## 📁 Struktur Proyek

```
event_connect/
│
├── backend/                    # Laravel 13 REST API
│   ├── app/
│   │   ├── Events/             # Broadcasting events (real-time chat)
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/        # Tenant API controllers
│   │   │   │   ├── Backoffice/ # Platform admin API controllers
│   │   │   │   └── PublicLandingController.php
│   │   │   └── Middleware/     # OrganizationStatusMiddleware, dll.
│   │   ├── Models/             # Eloquent models
│   │   └── Traits/             # BelongsToOrganization (multi-tenant)
│   ├── database/
│   │   ├── migrations/         # Skema database
│   │   └── seeders/            # Data awal (dummy)
│   ├── routes/
│   │   ├── api.php             # Definisi semua REST API routes
│   │   └── channels.php        # WebSocket channel authorization
│   ├── tests/Feature/          # Automated feature tests
│   ├── .env.example            # Template konfigurasi environment
│   └── composer.json
│
├── frontend/                   # Vue 3 — Aplikasi Tenant EO
│   ├── src/
│   │   ├── api/                # Axios HTTP client config
│   │   ├── components/         # Komponen reusable (ChatBox, dll.)
│   │   ├── layouts/            # DashboardLayout
│   │   ├── router/             # Vue Router config
│   │   ├── stores/             # Pinia state management
│   │   ├── views/              # Halaman-halaman (Events, Tasks, dll.)
│   │   └── style.css           # Global stylesheet
│   └── package.json
│
├── backoffice/                 # Vue 3 — Portal Admin Platform
│   ├── src/
│   │   ├── api/                # Axios HTTP client config
│   │   ├── layouts/            # BackofficeLayout
│   │   ├── router/             # Vue Router config
│   │   ├── stores/             # Pinia state management
│   │   ├── views/              # Dashboard, Organizations, dll.
│   │   └── style.css           # Global stylesheet
│   └── package.json
│
├── website/                    # Vue 3 — Landing Page Publik
│   ├── src/
│   │   ├── App.vue             # Single-page landing application
│   │   ├── main.js             # Entry point
│   │   └── style.css           # Global stylesheet
│   └── package.json
│
├── system_design_document.md   # Dokumentasi desain sistem (SDD)
├── .gitignore
└── README.md                   # File ini
```

---

## 🧩 Fitur Utama

### 🏢 Multi-Tenant SaaS
- Isolasi data antar organisasi EO menggunakan `organization_id` + Laravel Global Scope
- Kuota dinamis user & event per plan (Free, Starter, Professional, Enterprise)
- Suspend/activate organisasi secara real-time

### 📅 Manajemen Event
- CRUD event dengan kategori, anggaran, dan personel
- Penugasan tim dengan peran event terstandarisasi
- Detail event full-page dengan tab terstruktur

### ✅ Kanban Task & Workflow
- Papan tugas drag-and-drop (To Do → In Progress → Done)
- Komentar tugas dan filter multi-dimensi
- Otorisasi berbasis peran event

### ⏱️ Timeline & Rundown
- Susunan acara real-time dengan status tracking
- Dependensi antar item rundown
- Audit log perubahan

### 💰 Keuangan & Anggaran
- Alokasi anggaran per kategori
- Tracking pengeluaran riil vs. anggaran
- Dashboard grafik keuangan

### 📦 Logistik & Inventaris
- Gudang inventaris global EO
- Checkout/peminjaman barang ke event aktif
- Tracking status pengembalian

### 💬 Chat Real-Time
- Group chat per event via WebSocket (Laravel Reverb)
- File sharing (dibatasi berdasarkan plan EO)
- Isolasi keamanan antar tenant

### 👥 Manajemen Tamu
- Pendataan undangan (VVIP, VIP, Regular)
- RSVP tracking (Confirmed, Pending, Declined)
- Check-in kehadiran di lapangan

### 📊 Laporan Evaluasi
- Ringkasan pasca-event (highlights, challenges, recommendations)
- Printable report sheets
- Rating & feedback

### 🌐 Landing Page & CMS
- Landing page dinamis dengan konten dari database
- Interactive sandbox & showcase
- ROI calculator & pricing
- FAQ accordion dinamis
- Self-signup tenant baru

---

## 🧪 Menjalankan Tests

```bash
cd backend
php artisan test
```

Output yang diharapkan:
```
Tests:    16 passed (158 assertions)
Duration: ...
```

---

## 🔨 Build untuk Produksi

```bash
# Frontend
cd frontend && npm run build

# Backoffice
cd backoffice && npm run build

# Website
cd website && npm run build
```

Hasil build akan tersedia di folder `dist/` masing-masing komponen.

---

## 📚 Dokumentasi Lengkap

Untuk dokumentasi teknis yang lebih detail, silakan baca:
- **[System Design Document (SDD)](system_design_document.md)** — Berisi arsitektur sistem, use case diagram, activity diagram, sequence diagram, ERD, database schema, API specification, dan class diagram.

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend Framework** | Laravel 13 (PHP 8.3+) |
| **Frontend Framework** | Vue 3 (Composition API) |
| **Build Tool** | Vite 8 |
| **State Management** | Pinia 3 |
| **HTTP Client** | Axios |
| **Authentication** | Laravel Sanctum (Token-based) |
| **Real-Time** | Laravel Reverb (WebSocket) |
| **Database** | MySQL 8 / SQLite |
| **Icons** | Lucide Vue Next |
| **Routing** | Vue Router 4 |

---

## 👨‍💻 Kontributor

- **Rizky Fadhillah** — Developer

---

## 📄 Lisensi

Proyek ini dikembangkan sebagai bagian dari **Capstone Project** di Universitas Dian Nusantara (UNDIRA), Semester Genap 2025/2026.
