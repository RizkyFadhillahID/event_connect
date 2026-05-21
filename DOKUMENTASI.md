# EventConnect — Dokumentasi Teknis Lengkap

**Proyek Capstone — Universitas UNDIRA, Semester Genap 2026**  
**Tanggal Dokumentasi:** 21 Mei 2026

---

## Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Arsitektur Sistem](#2-arsitektur-sistem)
3. [Teknologi yang Digunakan](#3-teknologi-yang-digunakan)
4. [Struktur Direktori](#4-struktur-direktori)
5. [Skema Database](#5-skema-database)
6. [Backend — API Documentation](#6-backend--api-documentation)
7. [Frontend — Arsitektur & Komponen](#7-frontend--arsitektur--komponen)
8. [Role-Based Access Control (RBAC)](#8-role-based-access-control-rbac)
9. [Sistem Realtime (WebSocket)](#9-sistem-realtime-websocket)
10. [Panduan Setup & Menjalankan Aplikasi](#10-panduan-setup--menjalankan-aplikasi)
11. [Data Seed & Akun Testing](#11-data-seed--akun-testing)
12. [Hasil Testing Fitur](#12-hasil-testing-fitur)
13. [Bug yang Ditemukan & Diperbaiki](#13-bug-yang-ditemukan--diperbaiki)
14. [Rekomendasi Pengembangan Lanjutan](#14-rekomendasi-pengembangan-lanjutan)

---

## 1. Gambaran Umum Sistem

**EventConnect** adalah platform manajemen event terpadu berbasis web yang dirancang untuk membantu tim penyelenggara event mengelola seluruh proses operasional dari perencanaan hingga eksekusi secara realtime.

### Fitur Utama

| Modul | Deskripsi |
|-------|-----------|
| **Autentikasi** | Login token-based dengan Laravel Sanctum, role-aware redirect |
| **Manajemen User** | CRUD user, role assignment (superadmin only) |
| **Manajemen Event** | CRUD event, assignment personel ke event |
| **Task & Workflow** | CRUD task dengan prioritas, status tracking, komentar, parent-child task |
| **Timeline & Rundown** | Manajemen sesi event multi-hari, update status realtime, activity log |
| **Group Chat** | Chat per event berbasis WebSocket dengan presence indicator |
| **Dashboard** | Ringkasan statistik per role |

### Target Pengguna

- **Super Admin** — Mengelola semua aspek sistem termasuk user management
- **Project Manager** — Membuat & mengelola event, task, dan rundown
- **Staff/Personnel** — Melihat event yang ditugaskan, mengerjakan task, berkomunikasi via chat

---

## 2. Arsitektur Sistem

```
┌────────────────────────────────────────────────────────────┐
│                    CLIENT BROWSER                          │
│                                                            │
│  Vue 3 SPA (Vite)  ←→  Pinia Store  ←→  Vue Router       │
│       │                     │                              │
│  Axios (REST)          Laravel Echo (WS)                   │
└────────┬────────────────────┬───────────────────────────────┘
         │ HTTP :8000          │ WebSocket :8080
         ▼                    ▼
┌────────────────────┐  ┌──────────────────────┐
│  Laravel 13        │  │  Laravel Reverb       │
│  REST API          │  │  WebSocket Server     │
│  (Sanctum Auth)    │  │  Presence Channels    │
│                    │  │  event.{id}           │
│  Controllers:      │  └──────────────────────┘
│  - Auth            │
│  - Event           │
│  - Task            │
│  - Rundown         │
│  - Chat            │
│  - User            │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│  MySQL Database    │
│                    │
│  Tables:           │
│  - users           │
│  - events          │
│  - event_personnel │
│  - tasks           │
│  - task_comments   │
│  - event_rundowns  │
│  - rundown_logs    │
│  - rundown_deps    │
│  - chat_messages   │
└────────────────────┘
```

### Pola Komunikasi

- **REST API**: Semua operasi CRUD menggunakan HTTP/JSON ke `http://localhost:8000/api`
- **WebSocket**: Pengiriman pesan chat dan update status realtime via Laravel Reverb di port `8080`
- **Autentikasi Token**: Bearer token dari Sanctum disertakan di setiap request HTTP dan WebSocket auth

---

## 3. Teknologi yang Digunakan

### Backend

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| PHP | ^8.3 | Runtime bahasa |
| Laravel | ^13.0 | Framework utama |
| Laravel Sanctum | ^4.3 | Token-based API authentication |
| Laravel Reverb | ^1.0 | WebSocket server terintegrasi |
| MySQL | 8.x | Relational database |

### Frontend

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| Vue.js | ^3.5.32 | Framework UI reaktif |
| Vite | ^8.0.10 | Build tool & dev server |
| Vue Router | ^4.6.4 | Client-side routing |
| Pinia | ^3.0.4 | State management |
| Axios | ^1.15.2 | HTTP client |
| Laravel Echo | ^2.3.4 | WebSocket client wrapper |
| Pusher JS | ^8.5.0 | WebSocket transport layer |
| Lucide Vue Next | ^1.0.0 | Icon library |

---

## 4. Struktur Direktori

```
Capstone/
├── backend/                      # Laravel application
│   ├── app/
│   │   ├── Events/
│   │   │   └── MessageSent.php   # WebSocket broadcast event
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ChatController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── RundownController.php
│   │   │   │   ├── TaskController.php
│   │   │   │   └── UserController.php
│   │   │   └── Middleware/
│   │   │       └── RoleMiddleware.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Event.php
│   │   │   ├── EventRundown.php
│   │   │   ├── RundownLog.php
│   │   │   ├── Task.php
│   │   │   └── TaskComment.php
│   │   └── Providers/
│   │       └── AppServiceProvider.php
│   ├── database/
│   │   ├── migrations/           # 13 file migrasi
│   │   └── seeders/
│   │       └── DatabaseSeeder.php
│   └── routes/
│       ├── api.php               # Semua API routes
│       └── channels.php          # WebSocket channel auth
│
└── frontend/                     # Vue 3 SPA
    └── src/
        ├── api/
        │   └── axios.js          # Axios instance terkonfigurasi
        ├── components/
        │   └── FloatingChat.vue  # Floating chat popup
        ├── layouts/
        │   └── DashboardLayout.vue
        ├── router/
        │   └── index.js          # Route definitions + guards
        ├── stores/
        │   ├── auth.js           # Auth state (Pinia)
        │   └── chat.js           # Chat state + Echo (Pinia)
        └── views/
            ├── LoginView.vue
            ├── DashboardView.vue
            ├── UsersView.vue
            ├── EventsView.vue
            ├── TasksView.vue
            └── RundownView.vue
```

---

## 5. Skema Database

### Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | Auto increment |
| name | varchar(255) | Nama lengkap |
| email | varchar(255) unique | Email login |
| phone | varchar(255) nullable | Nomor telepon |
| role | enum | `superadmin`, `project_manager`, `staff` |
| status | enum | `active`, `inactive` |
| password | varchar(255) | Hashed (bcrypt) |
| created_at, updated_at | timestamp | |

### Tabel `events`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| name | varchar(255) | Nama event |
| description | text nullable | Deskripsi |
| location | varchar(255) | Lokasi |
| start_date | date | Tanggal mulai |
| end_date | date | Tanggal selesai |
| start_time | time nullable | Jam mulai |
| end_time | time nullable | Jam selesai |
| status | enum | `draft`, `active`, `ongoing`, `completed`, `cancelled` |
| budget | decimal(15,2) nullable | Anggaran |
| category | varchar(100) nullable | Kategori event |
| expected_participants | integer nullable | Target peserta |
| created_by | FK → users | Pembuat event |

### Tabel `event_personnel` (Pivot)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| event_id | FK → events | |
| user_id | FK → users | |
| role_in_event | varchar nullable | Peran di event |
| notes | text nullable | Catatan |

### Tabel `tasks`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| title | varchar(255) | Judul task |
| description | text nullable | |
| event_id | FK → events | Task milik event ini |
| assigned_to | FK → users nullable | Penanggungjawab |
| created_by | FK → users | Pembuat |
| parent_task_id | FK → tasks nullable | Parent untuk sub-task |
| priority | enum | `low`, `medium`, `high`, `urgent` |
| status | enum | `pending`, `in_progress`, `review`, `completed`, `cancelled` |
| due_date | date nullable | Batas waktu |
| due_time | time nullable | |
| category | varchar nullable | |
| completed_at | timestamp nullable | |

### Tabel `task_comments`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| task_id | FK → tasks | |
| user_id | FK → users | |
| comment | text | Isi komentar |

### Tabel `event_rundowns`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| event_id | FK → events | |
| event_date | date | Tanggal sesi (multi-day support) |
| title | varchar(255) | Nama sesi |
| description | text nullable | |
| category | varchar nullable | `ceremony`, `talent`, `technical`, `logistics`, `meal`, dll |
| start_time | time | Jadwal mulai |
| end_time | time | Jadwal selesai |
| duration_minutes | integer nullable | Durasi (kalkulasi otomatis) |
| status | enum | `pending`, `ready`, `live`, `delayed`, `completed` |
| pic_id | FK → users nullable | Person In Charge |
| location_note | varchar nullable | Lokasi spesifik dalam venue |
| notes | text nullable | Catatan umum |
| order_number | integer | Urutan dalam hari yang sama |
| started_at | timestamp nullable | Waktu mulai aktual |
| ended_at | timestamp nullable | Waktu selesai aktual |
| delay_minutes | integer | Keterlambatan (menit) |
| created_by | FK → users | |

### Tabel `rundown_logs`

Menyimpan riwayat setiap perubahan status rundown (audit trail).

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| rundown_id | FK → event_rundowns | |
| user_id | FK → users | User yang melakukan aksi |
| action | varchar | Deskripsi aksi |
| old_status | varchar nullable | Status sebelumnya |
| new_status | varchar nullable | Status sesudah |

### Tabel `rundown_dependencies`

Task yang harus selesai sebelum sesi rundown bisa berjalan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| rundown_id | FK → event_rundowns | |
| task_id | FK → tasks | |

### Tabel `chat_messages`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint PK | |
| event_id | FK → events | Chat termasuk event ini |
| user_id | FK → users | Pengirim |
| message | text | Isi pesan (max 1000 karakter) |
| created_at, updated_at | timestamp | Index: `(event_id, created_at)` |

### Tabel `personal_access_tokens`

Dikelola oleh Laravel Sanctum untuk API token authentication.

---

## 6. Backend — API Documentation

### Base URL
```
http://localhost:8000/api
```

### Autentikasi
Semua endpoint (kecuali `/login`) memerlukan header:
```
Authorization: Bearer {token}
```

---

### 6.1 Auth Endpoints

#### POST `/login`
Login dan mendapatkan token.

**Request Body:**
```json
{
  "email": "superadmin@eventconnect.com",
  "password": "password123"
}
```

**Response 200:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "superadmin@eventconnect.com",
    "role": "superadmin",
    "status": "active"
  }
}
```

#### POST `/logout`
Hapus token saat ini. Requires auth.

#### GET `/me`
Dapatkan data user yang sedang login. Requires auth.

---

### 6.2 User Management Endpoints
**Akses:** Superadmin only

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/users` | List semua user (dengan paginasi & search) |
| POST | `/users` | Buat user baru |
| GET | `/users/{id}` | Detail satu user |
| PUT | `/users/{id}` | Update user |
| DELETE | `/users/{id}` | Hapus user |

**Query Parameters GET `/users`:**
- `search` — pencarian nama/email
- `role` — filter berdasarkan role
- `per_page` — jumlah per halaman (default 10, max 100)

**Request Body POST/PUT `/users`:**
```json
{
  "name": "Nama User",
  "email": "email@example.com",
  "phone": "081234567890",
  "role": "staff",
  "status": "active",
  "password": "password123"
}
```

---

### 6.3 Event Management Endpoints

| Method | Endpoint | Akses | Deskripsi |
|--------|----------|-------|-----------|
| GET | `/events` | Semua | List event (scope by role) |
| POST | `/events` | SA + PM | Buat event baru |
| GET | `/events/{id}` | Semua | Detail event + personnel |
| PUT | `/events/{id}` | SA + PM | Update event |
| DELETE | `/events/{id}` | SA + PM | Hapus event |
| GET | `/users-list` | Semua | List user untuk assignment |
| GET | `/events/{id}/tasks` | Semua | Task milik event |
| GET | `/events/{id}/personnel` | Semua | Personel event |

**Query Parameters GET `/events`:**
- `search` — pencarian nama/lokasi
- `status` — filter status
- `per_page` — paginasi

**Response GET `/events`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Festival Budaya Nusantara 2026",
      "location": "Lapangan Banteng, Jakarta Pusat",
      "start_date": "2026-06-15",
      "status": "active",
      "creator": { "id": 2, "name": "Budi Santoso" },
      "personnel": [...]
    }
  ],
  "total": 5
}
```

**Role Scoping:** Staff hanya melihat event di mana mereka terdaftar di `event_personnel`. Superadmin & PM melihat semua.

---

### 6.4 Task & Workflow Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/tasks` | List task (scope by event membership) |
| POST | `/tasks` | Buat task baru |
| GET | `/tasks/{id}` | Detail task |
| PUT | `/tasks/{id}` | Update task |
| PATCH | `/tasks/{id}/status` | Update status saja |
| DELETE | `/tasks/{id}` | Hapus task |
| GET | `/tasks/{id}/comments` | Komentar task |
| POST | `/tasks/{id}/comments` | Tambah komentar |
| GET | `/my-tasks` | Task yang di-assign ke user saat ini |

**Request Body POST/PUT `/tasks`:**
```json
{
  "title": "Persiapan Venue",
  "description": "Memastikan venue siap sebelum H-1",
  "event_id": 1,
  "assigned_to": 3,
  "priority": "high",
  "status": "pending",
  "due_date": "2026-06-14",
  "category": "Logistik"
}
```

**PATCH `/tasks/{id}/status`:**
```json
{ "status": "in_progress" }
```

---

### 6.5 Timeline & Rundown Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/events/{id}/rundowns` | List rundown event (filter by date/category/status) |
| POST | `/events/{id}/rundowns` | Buat sesi rundown baru |
| GET | `/events/{id}/rundown-dates` | Daftar tanggal yang ada rundown-nya |
| GET | `/events/{id}/rundown-stats` | Statistik status rundown per event |
| GET | `/rundowns/{id}` | Detail satu sesi rundown |
| PUT | `/rundowns/{id}` | Update sesi rundown |
| PATCH | `/rundowns/{id}/status` | Update status sesi |
| DELETE | `/rundowns/{id}` | Hapus sesi rundown |
| GET | `/rundowns/{id}/logs` | Riwayat aktivitas sesi |
| POST | `/rundowns/{id}/dependencies` | Tambah task dependency |
| DELETE | `/rundowns/{id}/dependencies/{taskId}` | Hapus task dependency |

**Request Body POST `/events/{id}/rundowns`:**
```json
{
  "event_id": 1,
  "event_date": "2026-06-15",
  "title": "Opening Ceremony",
  "category": "ceremony",
  "start_time": "09:00",
  "end_time": "10:00",
  "pic_id": 3,
  "location_note": "Stage Utama",
  "order_number": 1
}
```

**PATCH `/rundowns/{id}/status`:**
```json
{
  "status": "live",
  "delay_reason": "Persiapan panggung mundur 15 menit"
}
```

**Status Lifecycle:**
```
pending → ready → live → completed
              ↘ delayed ↗
```

**Query Parameters GET `/events/{id}/rundowns`:**
- `event_date` — filter tanggal
- `category` — filter kategori
- `status` — filter status
- `search` — cari judul

---

### 6.6 Group Chat Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/events/{id}/chat` | 50 pesan terakhir (chronological) |
| POST | `/events/{id}/chat` | Kirim pesan baru + broadcast WebSocket |

**Request Body POST:**
```json
{ "message": "Isi pesan maksimal 1000 karakter" }
```

**Response GET:**
```json
[
  {
    "id": 1,
    "event_id": 1,
    "message": "Test pesan",
    "created_at": "2026-05-21T05:31:10.000Z",
    "user": { "id": 1, "name": "Super Admin", "role": "superadmin" }
  }
]
```

---

### 6.7 Middleware & Access Control

#### `RoleMiddleware`
Diregistrasi sebagai `role:{roles}` di route definitions.

```php
// Hanya superadmin
Route::middleware('role:superadmin')->group(...)

// Superadmin ATAU project_manager
Route::middleware('role:superadmin,project_manager')->group(...)
```

**Response jika akses ditolak (403):**
```json
{ "message": "Akses ditolak." }
```

---

## 7. Frontend — Arsitektur & Komponen

### 7.1 Axios Configuration (`src/api/axios.js`)

Instance Axios terpusat dengan:
- `baseURL: 'http://localhost:8000/api'` — semua path dipanggil relatif terhadap ini
- Request interceptor: inject `Authorization: Bearer {token}` dari localStorage
- Response interceptor: redirect ke `/login` jika response `401`

```javascript
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})
// Semua komponen harus import dari: '../api/axios'
// BUKAN import axios from 'axios' (akan break tanpa baseURL)
```

### 7.2 Auth Store (`src/stores/auth.js`)

State management autentikasi dengan Pinia:

| State/Getter | Tipe | Keterangan |
|-------------|------|------------|
| `user` | ref | Data user dari localStorage |
| `token` | ref | Bearer token dari localStorage |
| `isLoggedIn` | computed | Boolean: ada token atau tidak |
| `isSuperAdmin` | computed | role === 'superadmin' |
| `isProjectManager` | computed | role === 'project_manager' |
| `canManageUsers` | computed | Hanya superadmin |
| `canManageEvents` | computed | SA + PM |
| `canManageTasks` | computed | SA + PM |

Actions: `login(email, password)`, `logout()`

### 7.3 Chat Store (`src/stores/chat.js`)

State management chat dengan Pinia + Laravel Echo:

| State | Keterangan |
|-------|------------|
| `messagesByEvent` | reactive `{[eventId]: Message[]}` |
| `onlineByEvent` | reactive `{[eventId]: Member[]}` |
| `unreadCounts` | reactive `{[eventId]: number}` |
| `eventGroups` | `ref([])` — list event untuk sidebar chat |
| `loadingGroups` | loading state |

Key Actions:
- `loadEventGroups()` — fetch list event dari API
- `subscribeToEvent(eventId, userId)` — join presence channel via Echo
- `loadHistory(eventId)` — fetch 50 pesan terakhir
- `sendMessage(eventId, message)` — POST ke API

### 7.4 Vue Router (`src/router/index.js`)

Route guards:
- `requiresAuth` — redirect ke `/login` jika belum login
- `guest` — redirect ke `/` jika sudah login
- `meta.role: 'superadmin'` — redirect ke `/` jika bukan superadmin

```javascript
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) return next('/login')
  if (to.meta.guest && auth.isLoggedIn) return next('/')
  if (to.meta.role === 'superadmin' && !auth.isSuperAdmin) return next('/')
  next()
})
```

### 7.5 Halaman (Views)

#### `LoginView.vue`
- Form email + password
- Memanggil `authStore.login()`
- Redirect ke `/` setelah sukses

#### `DashboardView.vue`
- Statistik ringkasan: total event, event aktif, total user (SA only), event selesai
- Event terbaru (3 item)
- Task saya (my-tasks)
- Data di-scope sesuai role (staff hanya melihat event yang ditugaskan)

#### `UsersView.vue` *(Superadmin Only)*
- Tabel user dengan search & filter role
- Modal tambah/edit user
- Konfirmasi hapus

#### `EventsView.vue`
- List event (card view) dengan search, filter status
- Modal detail event: info, personnel, task statistics
- Modal tambah/edit event (SA + PM)
- Assignment personel ke event
- Staff hanya melihat event yang ditugaskan

#### `TasksView.vue`
- List task dengan filter event, status, prioritas
- Modal tambah/edit task
- Update status dengan drag-concept button
- Tab komentar di modal detail
- Parent-child task support

#### `RundownView.vue`
- Filter: pilih event, tanggal, kategori, status
- View mode: Timeline (grouped by date) dan List
- Card sesi: tampil waktu, kategori badge, status badge, lokasi
- Quick status update via dropdown langsung di card
- Modal detail sesi: informasi lengkap, status buttons, activity log
- Modal tambah/edit sesi: form lengkap dengan task dependency
- Konfirmasi hapus

#### `DashboardLayout.vue`
- Sidebar navigasi dengan icon + label
- User info di bawah sidebar
- Sidebar collapse/expand
- **FloatingChat** terintegrasi di kanan bawah

### 7.6 Komponen

#### `FloatingChat.vue`
Floating chat popup berbentuk WhatsApp-style:
- **Trigger:** Tombol bubble di kanan bawah dengan badge unread count
- **Sidebar kiri:** List event group, search, preview pesan terakhir, unread badge
- **Panel kanan:** Chat history, form kirim pesan, online member count
- **Realtime:** Subscribe ke presence channel `event.{id}` via Laravel Echo
- Event groups diload saat pertama kali popup dibuka

---

## 8. Role-Based Access Control (RBAC)

### Matriks Izin

| Fitur | Superadmin | Project Manager | Staff |
|-------|-----------|----------------|-------|
| Login | ✅ | ✅ | ✅ |
| Dashboard | ✅ (semua) | ✅ (semua) | ✅ (event ditugaskan) |
| Lihat User | ✅ | ❌ | ❌ |
| CRUD User | ✅ | ❌ | ❌ |
| Lihat Event | ✅ (semua) | ✅ (semua) | ✅ (assigned only) |
| CRUD Event | ✅ | ✅ | ❌ |
| Lihat Task | ✅ | ✅ | ✅ (event ditugaskan) |
| CRUD Task | ✅ | ✅ | ❌ |
| Update Status Task | ✅ | ✅ | ✅ (task sendiri) |
| Lihat Rundown | ✅ | ✅ | ✅ (event ditugaskan) |
| CRUD Rundown | ✅ | ✅ | Tergantung `role_in_event` |
| Update Status Rundown | ✅ | ✅ | PIC/role terkait |
| Chat | ✅ | ✅ | ✅ (event ditugaskan) |

### Implementasi RBAC

**Backend — Layer 1: `RoleMiddleware`**
```php
// routes/api.php
Route::middleware('role:superadmin')->group(...);           // Hanya SA
Route::middleware('role:superadmin,project_manager')->...;  // SA + PM
```

**Backend — Layer 2: Query Scoping (EventController)**
```php
->when(
    !in_array($user->role, ['superadmin', 'project_manager']),
    fn($q) => $q->whereHas('personnel', fn($inner) => $inner->where('users.id', $user->id))
)
```

**Frontend — Route Guard**
```javascript
if (to.meta.role === 'superadmin' && !auth.isSuperAdmin) return next('/')
```

**Frontend — UI Conditional Rendering**
```html
<link v-if="auth.canManageUsers" to="/users">Manajemen User</link>
<button v-if="canManage" @click="createEvent">Buat Event</button>
```

---

## 9. Sistem Realtime (WebSocket)

### Stack
- **Server:** Laravel Reverb (WebSocket server bawaan Laravel, port 8080)
- **Client:** Laravel Echo + Pusher JS (transport: `ws`/`wss`)
- **Broadcast Event:** `MessageSent` (implements `ShouldBroadcastNow`)
- **Channel Type:** Presence Channel `event.{eventId}`

### Alur Pengiriman Pesan

```
User mengetik pesan → POST /api/events/{id}/chat
       ↓
ChatController::store()
       ↓ ChatMessage::create()
       ↓ broadcast(new MessageSent($message))
       ↓
Laravel Reverb
       ↓ Broadcast ke channel event.{eventId}
       ↓ Event: message.sent
       ↓
Laravel Echo (client)
       ↓ .listen('.message.sent', callback)
       ↓
chat store → messagesByEvent[eventId].push(msg)
       ↓
Vue reactivity → UI update
```

### Channel Auth (`routes/channels.php`)

Presence channel hanya bisa di-join oleh:
1. User dengan role `superadmin` atau `project_manager` (akses semua event)
2. User yang terdaftar di `event_personnel` untuk event tersebut

```php
Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    if (in_array($user->role, ['superadmin', 'project_manager'])) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
    }
    $isMember = DB::table('event_personnel')
        ->where('event_id', $eventId)->where('user_id', $user->id)->exists();
    return $isMember ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role] : false;
});
```

### Broadcast Auth Route

```php
// routes/api.php
Broadcast::routes(['middleware' => ['auth:sanctum']]);
// Auth endpoint: POST /broadcasting/auth
// Header: Authorization: Bearer {token}
```

### Konfigurasi Echo (Frontend)

```javascript
echoInstance = new Echo({
  broadcaster:        'reverb',
  key:                import.meta.env.VITE_REVERB_APP_KEY,
  wsHost:             import.meta.env.VITE_REVERB_HOST,       // localhost
  wsPort:             Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS:           false,
  enabledTransports:  ['ws', 'wss'],
  authEndpoint:       'http://localhost:8000/broadcasting/auth',
  auth: {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') },
  },
})
```

---

## 10. Panduan Setup & Menjalankan Aplikasi

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8.x
- npm atau yarn

### Setup Backend

```bash
cd backend

# 1. Install PHP dependencies
composer install

# 2. Copy konfigurasi environment
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Konfigurasi database di .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=event_connect
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Konfigurasi Reverb di .env:
# REVERB_APP_ID=my-app-id
# REVERB_APP_KEY=my-app-key
# REVERB_APP_SECRET=my-app-secret
# REVERB_HOST=localhost
# REVERB_PORT=8080
# REVERB_SCHEME=http

# 6. Jalankan migrasi + seeder
php artisan migrate --seed

# 7. Jalankan server Laravel
php artisan serve
# → Berjalan di http://localhost:8000

# 8. (Terminal terpisah) Jalankan Reverb WebSocket server
php artisan reverb:start
# → Berjalan di ws://localhost:8080
```

### Setup Frontend

```bash
cd frontend

# 1. Install dependencies
npm install

# 2. Konfigurasi .env (buat file .env):
# VITE_REVERB_APP_KEY=my-app-key
# VITE_REVERB_HOST=localhost
# VITE_REVERB_PORT=8080
# VITE_REVERB_SCHEME=http

# 3. Jalankan dev server
npm run dev
# → Berjalan di http://localhost:5175
```

### Menjalankan Ketiga Server Sekaligus

```bash
# Terminal 1 — Laravel API
cd backend && php artisan serve

# Terminal 2 — Laravel Reverb WebSocket
cd backend && php artisan reverb:start

# Terminal 3 — Vite Frontend
cd frontend && npm run dev
```

### Catatan Penting
> Semua komponen frontend **harus** menggunakan `import api from '../api/axios'` (bukan `import axios from 'axios'`) agar request diarahkan ke `http://localhost:8000/api`.

---

## 11. Data Seed & Akun Testing

Setelah `php artisan migrate --seed`, database diisi dengan:

### Akun Pengguna (password semua: `password123`)

| Role | Email | Nama |
|------|-------|------|
| Superadmin | superadmin@eventconnect.com | Super Admin |
| Project Manager | pm@eventconnect.com | Budi Santoso |
| Staff | promo@eventconnect.com | Ahmad Fauzi |
| Staff | planner@eventconnect.com | Siti Rahayu |
| Staff | partner@eventconnect.com | Diana Putri |
| Staff | budget@eventconnect.com | Rizky Maulana |
| Staff | ops@eventconnect.com | Eka Fitriani |
| Staff | creative@eventconnect.com | Farhan Hidayat |
| Staff | rundown@eventconnect.com | Gina Marlina |
| Staff | talent@eventconnect.com | Hendra Wijaya |
| Staff | registration@eventconnect.com | Indah Permata |
| Staff | tech@eventconnect.com | Joko Susilo |
| Staff | doc@eventconnect.com | Kartika Dewi |
| Staff | lo@eventconnect.com | Luhut Pangaribuan |

**Total: 14 user** (1 superadmin + 1 PM + 12 staff)

### Event Sample

| Nama Event | Status | Tanggal |
|-----------|--------|---------|
| Festival Budaya Nusantara 2026 | active | 15-17 Jun 2026 |
| Tech Summit Indonesia 2026 | draft | 20 Jul 2026 |
| Gala Dinner Alumni UNDIRA | active | 10 Jun 2026 |
| Konser Amal Peduli Anak | completed | ... |
| Seminar Kewirausahaan Muda | completed | 5 Mei 2026 |

**Total: 5 event** seeded

---

## 12. Hasil Testing Fitur

Testing dilakukan dengan **browser automation (Playwright)** tanggal 21 Mei 2026.

### Ringkasan

| TC | Fitur | Status |
|----|-------|--------|
| TC-01 | Login & Autentikasi | ✅ PASS |
| TC-02 | CRUD Manajemen User | ✅ PASS |
| TC-03 | CRUD Manajemen Event | ✅ PASS |
| TC-04 | CRUD Task & Workflow | ✅ PASS |
| TC-05 | CRUD Timeline & Rundown | ✅ PASS (setelah 4 perbaikan) |
| TC-06 | Group Chat Realtime | ✅ PASS |
| TC-07 | Role-Based Access (Staff) | ✅ PASS |

### Detail Test Case

**TC-01 — Login**
- Superadmin login → dashboard tampil 5 events, 14 users ✅
- Token tersimpan di localStorage ✅
- Redirect otomatis jika sudah login ✅

**TC-02 — CRUD User**
- Create user baru (total 14 → 15) ✅
- Edit nama user ✅
- Delete user (kembali ke 14) ✅

**TC-03 — CRUD Event**
- Create event dengan personnel assignment ✅
- View detail event + stats task ✅
- Edit status event ✅
- Delete event ✅

**TC-04 — CRUD Task**
- Create task dengan priority high, due date, assignee ✅
- Update status: pending → in_progress → review → completed ✅
- Tambah komentar ✅
- Delete task ✅

**TC-05 — CRUD Rundown**
- Pilih event → dropdown terisi 5 events ✅ (setelah fix axios)
- Create sesi "Opening Ceremony TC-05", 15 Jun 2026, 09:00–10:00 ✅
- Detail sesi: informasi lengkap tampil ✅
- Update status: Pending → Live via detail modal ✅
- Activity log terekam otomatis ✅
- Edit sesi: ubah judul → "Opening Ceremony TC-05 (EDITED)" ✅
- Delete sesi dari detail modal ✅

**TC-06 — Group Chat**
- Popup terbuka, 5 event groups muncul di sidebar ✅
- Pilih event → chat panel terbuka ✅
- Kirim pesan → tampil di chat + preview di sidebar ✅
- Pesan tersimpan di database ✅

**TC-07 — Role Restriction (Staff)**
- Login Ahmad Fauzi (staff): hanya 3 event terlihat (bukan 5) ✅
- Menu "Manajemen User" tidak muncul ✅
- Total User di dashboard tersembunyi ("—") ✅
- Akses langsung `/users` → redirect ke Dashboard ✅

---

## 13. Bug yang Ditemukan & Diperbaiki

### BUG-01 — Axios Import Salah di RundownView *(KRITIS, DIPERBAIKI)*

**File:** `frontend/src/views/RundownView.vue`

**Gejala:** Dropdown "Pilih Event" menampilkan ratusan opsi kosong

**Root Cause:**
```javascript
// BUGGY — bare axios tanpa baseURL
import axios from 'axios'
const res = await axios.get('/api/events')
// Mengirim request ke http://localhost:5175/api/events (Vite dev server)
// Vite mengembalikan HTML (index.html)
// eventsList.value = HTML string
// v-for iterates tiap karakter → ratusan <option> kosong
```

**Fix:**
```javascript
// FIXED — gunakan configured instance
import axios from '../api/axios'
const res = await axios.get('/events')  // baseURL sudah http://localhost:8000/api
```

**Semua 11 API calls diperbaiki** (remove `/api/` prefix dari semua path).

---

### BUG-02 — Null Reference di `openEditFromDetail` *(SEDANG, DIPERBAIKI)*

**File:** `frontend/src/views/RundownView.vue`

**Gejala:** Klik "Edit" di detail modal → `TypeError: Cannot read properties of null (reading 'id')`

**Root Cause:**
```javascript
// BUGGY
function openEditFromDetail() {
  closeDetail()              // detailItem.value = null
  openEdit(detailItem.value) // openEdit(null) → null.id crash!
}
```

**Fix:**
```javascript
// FIXED
function openEditFromDetail() {
  const item = detailItem.value  // simpan referensi SEBELUM closeDetail()
  closeDetail()
  openEdit(item)
}
```

---

### BUG-03 — Format Tanggal ISO di Form Edit *(SEDANG, DIPERBAIKI)*

**File:** `frontend/src/views/RundownView.vue`

**Gejala:** Field "Tanggal Sesi" kosong saat buka form Edit

**Root Cause:** API mengembalikan `"2026-06-15T00:00:00.000000Z"` (ISO datetime) tetapi `<input type="date">` membutuhkan format `"yyyy-MM-dd"`

**Fix:**
```javascript
// openEdit() function
// BUGGY
event_date: item.event_date,

// FIXED
event_date: (item.event_date ?? '').slice(0, 10),
// "2026-06-15T00:00:00.000000Z" → "2026-06-15"
```

---

### BUG-04 — Format Waktu dengan Detik Gagal Validasi Backend *(SEDANG, DIPERBAIKI)*

**File:** `frontend/src/views/RundownView.vue`

**Gejala:** Submit form Edit → HTTP 422 error "The start time field must match the format H:i"

**Root Cause:** API menyimpan waktu sebagai `"09:00:00"` (dengan detik). Saat di-load ke form edit dan di-submit kembali, backend menolak karena validasinya `H:i` (tanpa detik).

**Fix:**
```javascript
// openEdit() function
// BUGGY
start_time: item.start_time,   // "09:00:00"
end_time:   item.end_time,     // "10:00:00"

// FIXED
start_time: (item.start_time ?? '').slice(0, 5),  // "09:00"
end_time:   (item.end_time ?? '').slice(0, 5),    // "10:00"
```

---

### ISSUE-01 — Reverb Presence "0 Online" *(MINOR, BELUM DIPERBAIKI)*

**Komponen:** `FloatingChat.vue` + `stores/chat.js`

**Gejala:** Chat kirim/terima pesan berfungsi normal, tetapi indicator "0 online" selalu tampil

**Kemungkinan Penyebab:** Echo instance diinisialisasi dengan token saat pertama kali (lazy init). Saat logout dan login akun berbeda, token di Echo tidak diperbarui karena Echo sudah terinisialisasi.

**Rekomendasi Fix:**
```javascript
// stores/auth.js — logout()
async function logout() {
  try {
    await api.post('/logout')
  } finally {
    // Tambahkan ini:
    resetEcho()  // import dari stores/chat.js
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }
}
```

---

## 14. Rekomendasi Pengembangan Lanjutan

### Prioritas Tinggi

1. **Fix Echo Reset saat Logout**  
   Panggil `resetEcho()` dari `chat.js` di action logout `auth.js` untuk memastikan token WebSocket selalu fresh.

2. **Tambahkan Vite Proxy**  
   Mencegah bug seperti BUG-01 terulang jika ada developer yang lupa menggunakan configured instance:
   ```javascript
   // vite.config.js
   server: {
     proxy: {
       '/api': { target: 'http://localhost:8000', changeOrigin: true }
     }
   }
   ```

3. **Global Error Handler Vue**  
   Ganti `alert()` dengan toast notification dan tambahkan error boundary:
   ```javascript
   // main.js
   app.config.errorHandler = (err, instance, info) => {
     toastStore.show({ type: 'error', message: err.message })
   }
   ```

### Prioritas Sedang

4. **Validasi Waktu Backend**  
   Backend RundownController sudah menerima `H:i` — pertimbangkan juga menerima `H:i:s` untuk toleransi lebih:
   ```php
   'start_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
   ```

5. **Rundown Display Format**  
   Waktu tampilan di card masih `09:00:00` (dari DB). Tambahkan format helper:
   ```javascript
   function formatTime(t) { return t?.slice(0, 5) ?? '-' }
   ```

6. **Chat History Pagination**  
   Saat ini load 50 pesan sekaligus. Untuk performa dengan pesan banyak, tambahkan infinite scroll dengan cursor-based pagination.

7. **Notifikasi Realtime untuk Task/Rundown**  
   Broadcast event saat status task atau rundown berubah agar semua anggota team mendapat update tanpa refresh halaman.

### Prioritas Rendah

8. **Export Laporan**  
   Fitur export rundown ke PDF atau Excel untuk kebutuhan dokumen resmi event.

9. **Attachment/File Upload**  
   Dukungan upload file di komentar task (foto progres, dokumen teknis).

10. **Dark Mode**  
    Toggle tema terang/gelap untuk kenyamanan penggunaan di malam hari selama event berlangsung.

11. **Push Notification (PWA)**  
    Notifikasi browser untuk pesan chat dan update status rundown saat halaman tidak aktif.

---

## Lampiran: Ringkasan File yang Dimodifikasi

Selama sesi development & bug fixing, file-file berikut mengalami perubahan signifikan:

| File | Perubahan |
|------|-----------|
| `frontend/src/views/RundownView.vue` | Fix 4 bug: axios import, null ref, date format, time format |
| `backend/routes/api.php` | Tambah `Broadcast::routes()` dengan middleware Sanctum |
| `backend/routes/channels.php` | Presence channel auth dengan event member scoping |
| `backend/app/Events/MessageSent.php` | ShouldBroadcastNow, PresenceChannel, broadcastWith() |
| `backend/app/Http/Controllers/Api/ChatController.php` | Optimasi query, hapus toOthers(), authorizeEventAccess() |
| `backend/app/Http/Controllers/Api/EventController.php` | Role-based query scoping untuk staff |
| `frontend/src/stores/chat.js` | reactive maps, splice deduplication, Echo lazy init |
| `frontend/src/components/FloatingChat.vue` | Desain ulang WhatsApp-style popup |
| `frontend/src/views/EventsView.vue` | Hapus tab chat lama, integrasi FloatingChat |
| `frontend/src/layouts/DashboardLayout.vue` | Integrasi FloatingChat global |

---

*Dokumentasi ini dibuat berdasarkan hasil pengembangan dan testing otomatis pada sesi Capstone UNDIRA Genap 2026.*
