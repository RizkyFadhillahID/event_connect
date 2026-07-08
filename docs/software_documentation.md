# Dokumentasi Pengembangan Perangkat Lunak - EventConnect

Selamat datang di dokumentasi teknis lengkap untuk proyek **EventConnect**. Dokumen ini mencakup analisis arsitektur, struktur kode, alur bisnis, basis data, API, keamanan, serta dokumentasi antarmuka pengguna (Frontend) untuk seluruh subsistem proyek: `backend`, `backoffice`, `tenant`, dan `website`.

---

## 1. Executive Summary

### 1.1. Tujuan Project
**EventConnect** adalah platform *Software as a Service* (SaaS) berbasis *multi-tenant* yang dirancang khusus untuk membantu Event Organizer (EO) mengelola seluruh siklus hidup perencanaan acara. Platform ini mengintegrasikan manajemen tim, penjadwalan waktu nyata (rundown), kontrol inventaris gudang logistik, pelacakan alokasi anggaran dan pengeluaran aktual, hingga manajemen tamu undangan dalam satu ekosistem yang terisolasi secara aman antar organisasi (tenant).

### 1.2. Fungsi Utama
1. **Pendaftaran Mandiri Tenant (SaaS Self-Signup):** Event Organizer dapat mendaftar langsung secara online, memilih paket layanan (Free, Business, Enterprise), dan mengaktifkan organisasi mereka secara otomatis atau melalui verifikasi pembayaran.
2. **Manajemen Event & Kolaborasi Tim:** Menugaskan personel dengan peran spesifik ke dalam event tertentu, serta melakukan manajemen pengguna internal organisasi.
3. **Manajemen Tugas & Alur Kerja (Kanban):** Sistem penugasan tugas bertingkat (subtasks) dilengkapi dengan tenggat waktu, prioritas, dan kolom komentar untuk kolaborasi tim.
4. **Rundown Acara Real-Time:** Penjadwalan rundown per kategori (technical/talent) dengan penandaan status langsung (*Live*, *Delayed*, *Completed*), pelacakan keterlambatan otomatis, log audit aktivitas, serta dependensi terhadap tugas (task).
5. **Manajemen Anggaran & Biaya:** Pengalokasian anggaran per kategori dan pencatatan pengeluaran riil untuk memantau sisa anggaran secara real-time.
6. **Logistik Gudang Global & Deplesi Event:** Pencatatan inventaris gudang pusat organisasi, peminjaman barang ke event (*checkout*), pengembalian (*check-in*), serta integrasi otomatis pencatatan biaya sewa jika barang berstatus sewa (*rented*).
7. **Obrolan Grup Event Terintegrasi:** Ruang diskusi tim per event menggunakan protokol WebSocket untuk komunikasi instan, mendukung unggah berkas/gambar, dan notifikasi peramban.
8. **Manajemen Tamu Undangan:** RSVP tamu, kategori undangan (VVIP/VIP/Regular), dan fitur *Check-in* kehadiran saat acara berlangsung.
9. **Platform Backoffice Admin:** Portal pusat bagi administrator platform untuk memverifikasi pendaftaran organisasi, mengelola konten pemasaran landing page, memantau metrik bisnis, dan merespons pesan masuk publik.

### 1.3. Teknologi yang Digunakan

#### Backend
*   **Bahasa Pemrograman:** PHP 8.3+
*   **Framework:** Laravel 13.x
*   **Database:** MySQL / MariaDB (Relasional)
*   **Penyedia WebSocket:** Laravel Reverb (Protokol WebSocket bawaan Laravel)
*   **Autentikasi API:** Laravel Sanctum (Token-based & Stateful SPA)

#### Frontend (Backoffice, Tenant, Website)
*   **Bahasa Pemrograman & Framework:** JavaScript, Vue.js 3.x (Composition API)
*   **Build Tool:** Vite
*   **Manajemen State:** Pinia Store
*   **Klien HTTP:** Axios
*   **Pustaka Ikon:** Lucide Vue Next
*   **Protokol Real-time Client:** Laravel Echo & Pusher JS

### 1.4. Arsitektur Aplikasi & Pola Desain
Aplikasi ini menerapkan pola arsitektur **Model-View-Controller (MVC)** pada sisi backend yang berfungsi sebagai API Server, sedangkan sisi frontend dirancang dengan konsep **Single Page Application (SPA)**.

Beberapa pola desain (*design patterns*) penting yang diterapkan meliputi:
1.  **Single-Table Multi-Tenancy (Tenant Scoping):** Menggunakan fitur *Eloquent Global Scope* melalui trait `BelongsToOrganization`. Setiap kueri database pada entitas `User`, `Event`, dan `Inventory` secara otomatis disaring berdasarkan `organization_id` dari pengguna yang sedang masuk.
2.  **Database Transactions:** Menjamin integritas data saat melakukan operasi kompleks yang melibatkan banyak tabel (misalnya pembuatan organisasi baru beserta akun superadmin-nya, atau proses checkout logistik yang memotong stok gudang sekaligus mencatat pengeluaran keuangan otomatis).
3.  **Repository-like Query Scoping:** Memanfaatkan *Eloquent Query Scopes* di tingkat Model (seperti `scopeForEvent`, `scopeRootTasks`) untuk memisahkan logika penyaringan database dari Controller.
4.  **Middleware-Based RBAC & Tenant Protection:** Melindungi endpoint API menggunakan middleware khusus untuk otorisasi peran (`RoleMiddleware`) dan status keaktifan tenant (`OrganizationStatusMiddleware`).

---

## 2. Struktur Project

Aplikasi ini dibagi menjadi 4 folder utama:

```
Capstone/
├── backend/          # Laravel 13 API Server
├── backoffice/       # Vue 3 SPA untuk Administrator Platform
├── tenant/           # Vue 3 SPA untuk Event Organizer (Tenants & Staff)
└── website/          # Vue 3 Single Page untuk Landing Page Publik
```

### 2.1. Backend (`backend/`)
*   `app/`
    *   `Events/`: Berisi event kelas PHP untuk broadcasting real-time (contoh: `MessageSent`).
    *   `Http/`
        *   `Controllers/`: Logic penanganan request API. Dibagi menjadi sub-folder `Api/` (untuk portal EO/Tenant), `Backoffice/` (untuk admin platform), dan `PublicLandingController.php` (untuk publik).
        *   `Middleware/`: Filter HTTP Request (contoh: `RoleMiddleware`, `OrganizationStatusMiddleware`).
    *   `Models/`: Representasi tabel database sebagai objek Eloquent.
    *   `Providers/`: Pengaturan bootstrap aplikasi (seperti `AppServiceProvider`, `BroadcastServiceProvider`).
    *   `Traits/`: Berisi trait reusable (seperti `BelongsToOrganization` untuk multi-tenancy).
*   `bootstrap/`
    *   `app.php`: Konfigurasi routing, middleware alias, dan exception handler (Laravel 13).
    *   `providers.php`: Daftar service provider yang dimuat.
*   `config/`: File konfigurasi aplikasi (database, auth, session, broadcasting, dll).
*   `database/`
    *   `migrations/`: Skema DDL tabel database.
    *   `seeders/`: Data awal pengisian database.
*   `routes/`
    *   `api.php`: Daftar rute endpoint API yang diakses oleh frontend.
    *   `channels.php`: Definisi otorisasi channel real-time (Laravel Echo/Reverb).
    *   `web.php`: Rute web dasar untuk monitoring status server.

### 2.2. Backoffice (`backoffice/`)
*   `src/`
    *   `api/`: Konfigurasi Axios dengan interseptor token `platform_token`.
    *   `layouts/`: Layout pembungkus tampilan halaman (seperti sidebar dan header).
    *   `router/`: Konfigurasi rute halaman Vue Router beserta *navigation guard* autentikasi.
    *   `stores/`: Manajemen state global menggunakan Pinia (`auth.js` untuk admin platform).
    *   `views/`: Halaman-halaman tampilan utama (Dashboard, Organisasi, Admin, FAQ, Landing Editor, Contact Inbox).
    *   `main.js`: Titik masuk aplikasi (*entry point*).
    *   `App.vue`: Komponen akar aplikasi.

### 2.3. Tenant (`tenant/`)
*   `src/`
    *   `api/`: Konfigurasi Axios dengan interseptor token `token` (Sanctum).
    *   `components/`: Komponen UI reusable (seperti modal, bagan, kartu informasi).
    *   `layouts/`: Tata letak dashboard tenant.
    *   `router/`: Vue Router untuk navigasi EO (mencegah non-superadmin masuk ke manajemen user).
    *   `stores/`: State global Pinia (`auth.js` untuk user session, `chat.js` untuk pesan instan & koneksi Echo).
    *   `views/`: Tampilan modul utama (Dashboard, Events, Tasks, Rundown, Logistics, Profile, Chat, Users).

### 2.4. Website (`website/`)
*   `src/`
    *   `components/`: Komponen interaktif kecil.
    *   `App.vue`: Halaman tunggal super besar yang menampung seluruh tampilan landing page, kalkulator ROI, FAQ akordeon, formulir kontak, dan modal pendaftaran organisasi baru.

---

## 3. Analisis Source Code

### 3.1. Trait & Middleware Backend

#### 3.1.1. Trait: [BelongsToOrganization](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Traits/BelongsToOrganization.php)
*   **Tujuan:** Menerapkan pembatasan data berbasis penyewa (*Tenant Scoping*) secara otomatis dan aman di tingkat ORM.
*   **Fungsi Utama:** 
    *   Menambahkan *Global Scope* `organization` pada model yang menggunakannya. Setiap operasi pembacaan (`select`) akan otomatis disaring dengan klausa `WHERE organization_id = [ID Organisasi User]`.
    *   Mengaitkan *event hook* `creating` untuk otomatis mengisi kolom `organization_id` pada baris baru berdasarkan organisasi dari user yang sedang login.
*   **Dependency:** `Illuminate\Database\Eloquent\Builder`, `Illuminate\Support\Facades\Auth`, `App\Models\Organization`.
*   **Cara Kerja:** Ketika model di-boot, scope mendeteksi apakah ada sesi user aktif. Jika ya, kueri database disuntikkan filter ID organisasi. Variabel `$isResolvingAuth` digunakan untuk mencegah perulangan tak terbatas (*infinite loop*) saat Laravel sedang menyelesaikan data user.

#### 3.1.2. Middleware: [OrganizationStatusMiddleware](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Middleware/OrganizationStatusMiddleware.php)
*   **Tujuan:** Mencegah akses API bagi pengguna dari organisasi yang ditangguhkan (*suspended*) atau belum aktif.
*   **Fungsi Utama:** Memeriksa status organisasi dari user yang sedang terautentikasi. Jika status tidak sama dengan `'active'`, request dihentikan dan dibalas dengan respons 403 Forbidden.
*   **Cara Kerja:** Mengambil data user dari request, memeriksa relasi `organization`. Jika status organisasi `'suspended'` atau `'inactive'`, middleware langsung mengembalikan respons JSON penolakan tanpa meneruskan request ke controller.

#### 3.1.3. Middleware: [RoleMiddleware](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Middleware/RoleMiddleware.php)
*   **Tujuan:** Otorisasi akses endpoint berdasarkan peran (*RBAC*).
*   **Fungsi Utama:** Memeriksa apakah kolom `role` pada tabel `users` milik pengguna saat ini terdaftar dalam daftar peran yang diizinkan untuk mengakses rute tertentu.
*   **Cara Kerja:** Menerima parameter variadik `...$roles`. Jika peran user tidak cocok, mengembalikan respons JSON `'Akses ditolak.'` dengan kode status 403.

---

### 3.2. Model-Model Utama Backend

1.  **[User](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/User.php):** Memiliki trait `BelongsToOrganization`. Menyimpan data kredensial staf EO. Berelasi *Many-to-Many* dengan `Event` via tabel pivot `event_personnel` untuk penugasan tim.
2.  **[Event](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/Event.php):** Memiliki trait `BelongsToOrganization`. Menampung data utama acara (nama, tanggal, budget). Berelasi *One-to-Many* dengan `Task`, `EventRundown`, `EventBudgetAllocation`, `EventExpense`, `EventLogistic`, `Guest`, dan berelasi *One-to-One* dengan `EventReport`.
3.  **[Task](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/Task.php):** Representasi tugas kerja tim. Mendukung struktur hierarkis (*Self-Relation* via `parent_task_id` dan `subtasks`). Berelasi dengan `User` sebagai pembuat (`creator`) dan penerima tugas (`assignee`). Memiliki properti dinamis `is_overdue` untuk mendeteksi keterlambatan tugas secara real-time.
4.  **[EventRundown](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/EventRundown.php):** Menyimpan detail jadwal acara. Berelasi *Many-to-Many* dengan `Task` sebagai prasyarat keberlangsungan rundown (`dependencyTasks`). Properti `has_unfinished_dependencies` akan bernilai `true` jika ada tugas prasyarat yang belum diselesaikan.
5.  **[Inventory](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/Inventory.php):** Memiliki trait `BelongsToOrganization`. Menyimpan stok barang gudang pusat organisasi, status barang (`ready`, `maintenance`, `damaged`), serta harga sewa harian bawaan jika berstatus sewa (`rented`).
6.  **[EventLogistic](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/EventLogistic.php):** Pencatatan barang gudang yang dipinjam oleh event tertentu. Menyimpan jumlah barang, tanggal peminjaman, tanggal pengembalian, status saat kembali (`complete`, `incomplete`, `damaged`), dan total biaya sewa yang dibebankan.
7.  **[EventExpense](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/EventExpense.php):** Pencatatan pengeluaran keuangan riil dalam event.
8.  **[ChatMessage](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Models/ChatMessage.php):** Menyimpan riwayat obrolan grup instan per event, mendukung kolom path lampiran file/gambar.

---

### 3.3. Controller Utama Backend

#### 3.3.1. [AuthController](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Controllers/Api/AuthController.php)
*   **Tujuan:** Mengatur alur autentikasi dan profil pengguna tenant.
*   **Fungsi Utama:**
    *   `login`: Validasi kredensial. Jika sukses, periksa keaktifan user dan organisasi. Hasilkan token akses via Sanctum.
    *   `logout`: Menghapus token akses saat ini dari database.
    *   `me`: Mengembalikan data user beserta relasi organisasinya.
    *   `updateProfile`: Mengubah nama, email, telepon, dan password. Memerlukan verifikasi password saat ini (`current_password`) jika email atau password diubah.

#### 3.3.2. [EventController](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Controllers/Api/EventController.php)
*   **Tujuan:** Mengelola data event organisasi.
*   **Fungsi Utama:**
    *   `index`: Menampilkan daftar event terpaginasi dengan filter pencarian dan status. Peran di luar `superadmin` dan `project_manager` hanya dapat melihat event di mana mereka terdaftar sebagai personel.
    *   `store`: Membuat event baru. Memeriksa batasan maksimal pembuatan event berdasarkan paket organisasi (`max_events`). Menyinkronkan data staf pelaksana ke tabel pivot `event_personnel` dalam skema *Database Transaction*.
    *   `update`: Memperbarui detail event dan memperbarui daftar staf pelaksana.
    *   `dashboardFinancials`: Mengambil data 5 event terbaru beserta perbandingan budget vs total pengeluaran aktual (menggunakan metode kueri `withSum`).

#### 3.3.3. [TaskController](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Controllers/Api/TaskController.php)
*   **Tujuan:** Mengelola penugasan tugas tim.
*   **Fungsi Utama:**
    *   `index`: Menampilkan daftar tugas utama (*root tasks*) beserta subtasks-nya menggunakan teknik *eager loading*. Dilengkapi filter pencarian, prioritas, status, kategori, tugas saya (`my_tasks`), dan keterlambatan (`overdue`).
    *   `store`: Membuat tugas baru setelah memvalidasi bahwa pembuat memiliki hak koordinasi dalam event tersebut, dan penerima tugas terdaftar sebagai staf dalam event tersebut.
    *   `updateStatus`: Mengizinkan penerima tugas (*assignee*) untuk memperbarui status pengerjaan secara mandiri. Mengisi timestamp `completed_at` otomatis jika status diset menjadi `'completed'`.
    *   `addComment`: Menyimpan komentar baru dari staf pada lembar detail tugas.

#### 3.3.4. [RundownController](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Controllers/Api/RundownController.php)
*   **Tujuan:** Mengontrol lini masa dan jadwal acara secara ketat.
*   **Fungsi Utama:**
    *   `store` & `update`: Mengelola data jadwal rundown. Mendukung relasi tugas prasyarat (`dependency_task_ids`).
    *   `updateStatus`: Mengubah status rundown (`pending`, `ready`, `live`, `delayed`, `completed`). Mengisi timestamp `started_at` saat live dan `ended_at` saat selesai. Jika selesai, selisih waktu aktual dibandingkan dengan durasi rencana disimpan sebagai `delay_minutes`.
    *   `logs`: Menampilkan riwayat perubahan status rundown, nama pengubah, dan alasan penundaan jika status diubah menjadi `delayed` (sebagai jejak audit).

#### 3.3.5. [LogisticController](file:///d:/UNDIRA/Genap%202026/Capstone/backend/app/Http/Controllers/Api/LogisticController.php)
*   **Tujuan:** Mengatur aset gudang dan distribusi logistik ke event.
*   **Fungsi Utama:**
    *   `storeInventory`: Membuat/mengubah data barang gudang global. Menyesuaikan jumlah stok tersedia (`available_quantity`) jika total stok gudang diubah.
    *   `checkoutLogistic`: Meminjamkan barang ke event. Memeriksa ketersediaan stok. Jika barang berstatus sewa (`rented`), sistem menghitung biaya sewa berdasarkan durasi hari peminjaman dikalikan harga sewa bawaan, kemudian **secara otomatis membuat catatan pengeluaran baru** pada modul keuangan (`EventExpense`) dengan kategori `'Logistik & Ops'`.
    *   `returnLogistic`: Mencatat pengembalian barang ke gudang dan mengembalikan jumlah ketersediaan stok. Jika barang kembali dalam kondisi rusak (`damaged`), status barang di gudang global otomatis berubah menjadi `'maintenance'`.

---

## 4. Business Flow

Berikut adalah visualisasi alur bisnis utama pada platform EventConnect dari interaksi pengguna hingga penyimpanan di database dan respons kembali.

```
Pengguna (Browser) ──> Frontend (Vue.js) ──> API Gateway (Laravel) ──> Controller ──> Trait / Scope ──> Database (MySQL)
       ^                                                                                                  │
       └─────────────────────────────── Kirim Response JSON ──────────────────────────────────────────────┘
```

### 4.1. Pendaftaran Organisasi (SaaS Self-Signup)
1.  **User (Website):** Mengisi data organisasi (nama, email, rencana paket: *Free/Business/Enterprise*) dan data administrator pertama pada halaman registrasi.
2.  **API (`POST /api/landing/register`):** Diterima oleh `PublicLandingController@register`.
3.  **Validation:** Memvalidasi keunikan email organisasi dan email admin.
4.  **Database Process:** Menjalankan transaksi database:
    *   Membuat baris baru di tabel `organizations`. Kolom `status` bernilai `'active'` jika memilih paket *Free*, dan `'pending'` (menunggu pembayaran) jika memilih paket *Business/Enterprise*.
    *   Membuat baris baru di tabel `users` dengan `organization_id` yang baru dibuat dan peran (`role`) sebagai `'superadmin'`.
5.  **Response:** Mengirim pesan sukses beserta data organisasi baru.

### 4.2. Peminjaman Logistik Gudang (Checkout)
1.  **User (Tenant):** Memilih barang di gudang pusat, memasukkan jumlah barang, nama penanggung jawab (PIC), dan tanggal pinjam pada modul logistik event.
2.  **API (`POST /api/events/{event}/logistics`):** Diterima oleh `LogisticController@checkoutLogistic`.
3.  **Business Logic:**
    *   Mengecek ketersediaan stok barang di tabel `inventories` (di mana `available_quantity >= quantity`).
    *   Mengurangi kolom `available_quantity` pada tabel `inventories`.
    *   Menghitung durasi hari peminjaman (hari pinjam s/d hari selesai event). Jika kepemilikan barang adalah `'rented'`, hitung `rent_cost = default_rent_price * quantity * days`.
    *   Membuat baris baru di tabel `event_logistics`.
    *   **Keuangan Otomatis:** Jika `rent_cost > 0`, buat baris baru di tabel `event_expenses` dengan catatan kaki berisi ID logistik terkait.
4.  **Response:** Mengirim data logistik terbuat beserta relasi barang dan PIC.

### 4.3. Perubahan Status Rundown & Audit Keterlambatan
1.  **User (Tenant):** Menekan tombol "Selesai" (*Completed*) pada item rundown yang sedang berlangsung di timeline.
2.  **API (`PATCH /api/rundowns/{rundown}/status`):** Diterima oleh `RundownController@updateStatus` dengan input `status: 'completed'`.
3.  **Business Logic:**
    *   Mengisi kolom `ended_at` dengan waktu server saat ini.
    *   Menghitung durasi aktual (`ended_at` dikurangi `started_at`). Jika durasi aktual melebihi nilai `duration_minutes` rencana, hitung menit selisihnya dan simpan di kolom `delay_minutes`.
    *   Membuat log perubahan di tabel `rundown_logs` dengan aksi `'status_changed'`, status lama, status baru, dan alasan keterlambatan jika ada.
4.  **Response:** Mengirim objek rundown terbaru untuk memperbarui status visual di layar timeline tim.

---

## 5. Database Documentation

### 5.1. Hubungan Antar Tabel (Entity Relationship Diagram - ERD)

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ USERS : "has many"
    ORGANIZATIONS ||--o{ EVENTS : "has many"
    ORGANIZATIONS ||--o{ INVENTORIES : "has many"
    
    USERS ||--o{ EVENTS : "creates"
    USERS ||--o{ EVENT_PERSONNEL : "assigned as"
    EVENTS ||--o{ EVENT_PERSONNEL : "has staff"
    
    EVENTS ||--o{ TASKS : "has tasks"
    USERS ||--o{ TASKS : "assigned to / created by"
    TASKS ||--o{ TASK_COMMENTS : "has comments"
    USERS ||--o{ TASK_COMMENTS : "comments"
    
    EVENTS ||--o{ EVENT_RUNDOWNS : "scheduled in"
    USERS ||--o{ EVENT_RUNDOWNS : "PIC of"
    EVENT_RUNDOWNS ||--o{ RUNDOWN_DEPENDENCIES : "depends on"
    TASKS ||--o{ RUNDOWN_DEPENDENCIES : "is prerequisite for"
    EVENT_RUNDOWNS ||--o{ RUNDOWN_LOGS : "logs status"
    
    EVENTS ||--o{ EVENT_BUDGET_ALLOCATIONS : "allocates"
    EVENTS ||--o{ EVENT_EXPENSES : "spends"
    USERS ||--o{ EVENT_EXPENSES : "records"
    
    INVENTORIES ||--o{ EVENT_LOGISTICS : "stored in"
    EVENTS ||--o{ EVENT_LOGISTICS : "deployed in"
    USERS ||--o{ EVENT_LOGISTICS : "PIC of loan"
    
    EVENTS ||--|| EVENT_REPORTS : "summarized in"
    EVENTS ||--o{ EVENT_GUESTS : "invites"
```

---

### 5.2. Kamus Data Tabel Utama

#### 5.2.1. Tabel: `organizations`
Menampung data penyewa (tenant) utama yang mendaftar di platform.
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `name` (VARCHAR(255), Not Null): Nama Event Organizer.
*   `slug` (VARCHAR(255), Unique, Not Null): Identifikasi URL tenant.
*   `email` (VARCHAR(255), Unique, Not Null): Kontak email utama organisasi.
*   `phone` (VARCHAR(20), Nullable)
*   `address` (TEXT, Nullable)
*   `logo` (VARCHAR(255), Nullable): Path berkas logo.
*   `status` (ENUM('pending', 'active', 'suspended', 'inactive'), Default 'pending')
*   `plan` (ENUM('free', 'business', 'enterprise'), Default 'free')
*   `max_users` (INT, Not Null): Batas kuota pengguna organisasi.
*   `max_events` (INT, Not Null): Batas kuota pembuatan event.

#### 5.2.2. Tabel: `users`
Menyimpan kredensial dan peran staf di setiap organisasi.
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `organization_id` (BIGINT, Foreign Key, Nullable): Berelasi ke `organizations.id` (ON DELETE CASCADE).
*   `name` (VARCHAR(255), Not Null)
*   `email` (VARCHAR(255), Unique, Not Null)
*   `phone` (VARCHAR(255), Nullable)
*   `role` (ENUM('superadmin', 'project_manager', 'staff'), Default 'staff')
*   `status` (ENUM('active', 'inactive'), Default 'active')
*   `password` (VARCHAR(255), Not Null)

#### 5.2.3. Tabel: `events`
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `organization_id` (BIGINT, Foreign Key, Nullable): Berelasi ke `organizations.id` (ON DELETE CASCADE).
*   `name` (VARCHAR(255), Not Null)
*   `description` (TEXT, Nullable)
*   `location` (VARCHAR(255), Not Null)
*   `start_date` (DATE, Not Null)
*   `end_date` (DATE, Not Null)
*   `start_time` (TIME, Nullable)
*   `end_time` (TIME, Nullable)
*   `status` (ENUM('draft', 'active', 'ongoing', 'completed', 'cancelled'), Default 'draft')
*   `budget` (DECIMAL(15,2), Nullable)
*   `category` (VARCHAR(255), Nullable): Jenis event (contoh: 'Concert', 'Conference').
*   `expected_participants` (INT, Nullable)
*   `created_by` (BIGINT, Foreign Key): Pembuat event, berelasi ke `users.id` (ON DELETE CASCADE).

#### 5.2.4. Tabel: `event_personnel` (Pivot)
Menghubungkan staf dengan event yang ditugaskan kepada mereka.
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `event_id` (BIGINT, Foreign Key): Berelasi ke `events.id` (ON DELETE CASCADE).
*   `user_id` (BIGINT, Foreign Key): Berelasi ke `users.id` (ON DELETE CASCADE).
*   `role_in_event` (VARCHAR(255), Nullable): Peran spesifik dalam event (contoh: 'Creative Director').
*   `notes` (TEXT, Nullable)
*   *Constraint:* Unique Key pada (`event_id`, `user_id`).

#### 5.2.5. Tabel: `tasks`
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `title` (VARCHAR(255), Not Null)
*   `description` (TEXT, Nullable)
*   `event_id` (BIGINT, Foreign Key): Berelasi ke `events.id` (ON DELETE CASCADE).
*   `assigned_to` (BIGINT, Foreign Key, Nullable): Penerima tugas, berelasi ke `users.id` (ON DELETE SET NULL).
*   `created_by` (BIGINT, Foreign Key): Pembuat tugas, berelasi ke `users.id` (ON DELETE CASCADE).
*   `parent_task_id` (BIGINT, Foreign Key, Nullable): Hubungan hierarkis tugas, berelasi ke `tasks.id` (ON DELETE SET NULL).
*   `priority` (ENUM('low', 'medium', 'high', 'urgent'), Default 'medium')
*   `status` (ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled'), Default 'pending')
*   `due_date` (DATE, Nullable)
*   `due_time` (TIME, Nullable)
*   `completed_at` (TIMESTAMP, Nullable)

#### 5.2.6. Tabel: `event_rundowns`
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `event_id` (BIGINT, Foreign Key): Berelasi ke `events.id` (ON DELETE CASCADE).
*   `event_date` (DATE, Not Null): Hari pelaksanaan jadwal.
*   `title` (VARCHAR(255), Not Null)
*   `description` (TEXT, Nullable)
*   `category` (VARCHAR(100), Nullable): Kategori (contoh: 'technical', 'talent').
*   `start_time` (TIME, Not Null)
*   `end_time` (TIME, Not Null)
*   `duration_minutes` (INT, Nullable)
*   `status` (ENUM('pending', 'ready', 'live', 'delayed', 'completed'), Default 'pending')
*   `pic_id` (BIGINT, Foreign Key, Nullable): Berelasi ke `users.id` (ON DELETE SET NULL).
*   `started_at` (TIMESTAMP, Nullable)
*   `ended_at` (TIMESTAMP, Nullable)
*   `delay_minutes` (INT, Default 0): Total menit keterlambatan.

#### 5.2.7. Tabel: `inventories`
*   `id` (BIGINT, Primary Key, Auto Increment, Unsigned)
*   `organization_id` (BIGINT, Foreign Key, Nullable): Berelasi ke `organizations.id` (ON DELETE CASCADE).
*   `item_name` (VARCHAR(255), Not Null)
*   `serial_number` (VARCHAR(100), Nullable)
*   `total_quantity` (INT, Not Null)
*   `available_quantity` (INT, Not Null)
*   `ownership` (ENUM('owned', 'rented'), Not Null)
*   `default_rent_price` (DECIMAL(15,2), Nullable)
*   `status` (ENUM('ready', 'maintenance', 'damaged'), Default 'ready')

---

## 6. API Documentation

Seluruh rute dilindungi oleh middleware `auth:sanctum` (kecuali rute publik).

### 6.1. Portal Tenant (Event Organizer)

#### 6.1.1. Autentikasi Pengguna
*   **Rute:** `POST /api/login`
*   **Deskripsi:** Masuk ke aplikasi portal tenant.
*   **Request Body:**
    *   `email` (String, Wajib, Format Email)
    *   `password` (String, Wajib)
*   **Respons Sukses (200 OK):**
    ```json
    {
      "user": {
        "id": 2,
        "name": "Rizky",
        "email": "pm@eventconnect.com",
        "role": "project_manager",
        "organization": {
          "id": 1,
          "name": "Rizky Organizers",
          "plan": "business",
          "status": "active"
        }
      },
      "token": "1|sanctum_generated_token_string..."
    }
    ```
*   **Respons Gagal Validasi (422 Unprocessable Entity):**
    ```json
    {
      "message": "Email atau password salah.",
      "errors": {
        "email": ["Email atau password salah."]
      }
    }
    ```

#### 6.1.2. Logistik & Sewa Barang (Checkout)
*   **Rute:** `POST /api/events/{event}/logistics`
*   **Deskripsi:** Meminjamkan barang dari gudang pusat ke event tertentu.
*   **Request Body:**
    *   `inventory_id` (Integer, Wajib, Harus ada di tabel `inventories`)
    *   `user_id` (Integer, Wajib, PIC berstatus aktif)
    *   `quantity` (Integer, Wajib, Minimal 1)
    *   `borrowed_at` (Date, Wajib, Format YYYY-MM-DD)
    *   `notes` (String, Opsional)
*   **Proses Database:**
    1.  Mengecek ketersediaan stok barang.
    2.  Mengurangi stok gudang (`inventories.available_quantity`).
    3.  Membuat data peminjaman (`event_logistics`).
    4.  Jika barang disewa, sistem otomatis menyisipkan data pengeluaran (`event_expenses`) dengan rincian biaya sewa.
*   **Respons Sukses (201 Created):**
    ```json
    {
      "id": 8,
      "event_id": 5,
      "inventory_id": 1,
      "quantity": 2,
      "rent_cost": 500000.00,
      "borrowed_at": "2026-07-15",
      "user": {
        "id": 3,
        "name": "Siti Rahayu"
      }
    }
    ```

#### 6.1.3. Perubahan Status Rundown
*   **Rute:** `PATCH /api/rundowns/{rundown}/status`
*   **Deskripsi:** Memperbarui status pelaksanaan item rundown.
*   **Request Body:**
    *   `status` (ENUM: `'pending'`, `'ready'`, `'live'`, `'delayed'`, `'completed'`, Wajib)
    *   `delay_reason` (String, Wajib jika status diubah menjadi `'delayed'`)
    *   `delay_minutes` (Integer, Opsional)
*   **Respons Sukses (200 OK):**
    ```json
    {
      "message": "Status berhasil diperbarui.",
      "data": {
        "id": 12,
        "status": "completed",
        "started_at": "2026-06-30 09:30:00",
        "ended_at": "2026-06-30 10:15:00",
        "delay_minutes": 15
      }
    }
    ```

---

### 6.2. Portal Platform Backoffice (Platform Admins)

#### 6.2.1. Memperbarui Status Organisasi
*   **Rute:** `PATCH /api/backoffice/organizations/{organization}/status`
*   **Deskripsi:** Menangguhkan (*suspend*) atau mengaktifkan kembali akun organisasi.
*   **Request Body:**
    *   `status` (ENUM: `'active'`, `'suspended'`, `'inactive'`, Wajib)
*   **Proses Bisnis:** Jika status diubah menjadi `'suspended'` atau `'inactive'`, **sistem otomatis menghapus seluruh token akses aktif** (`personal_access_tokens`) dari seluruh staf yang bernaung di bawah organisasi tersebut, sehingga mereka otomatis ter-logout paksa dari aplikasi.
*   **Respons Sukses (200 OK):**
    ```json
    {
      "message": "Status organisasi berhasil diubah.",
      "organization": {
        "id": 4,
        "name": "Nusantara Jaya",
        "status": "suspended"
      }
    }
    ```

---

## 7. Authentication

### 7.1. Mekanisme Token (Sanctum)
Platform ini menggunakan **Laravel Sanctum** untuk menangani autentikasi berbasis token (*Token-based API Authentication*). 
*   **Sesi Stateless:** Setiap request API yang membutuhkan autentikasi wajib menyertakan header HTTP `Authorization: Bearer [token]`.
*   **Penyimpanan Klien:** Token disimpan secara aman pada browser client menggunakan `localStorage` (`token` untuk pengguna portal EO, dan `platform_token` untuk admin platform).
*   **Sesi Stateful (SPA):** Untuk meningkatkan keamanan pengembangan lokal, Sanctum dikonfigurasi untuk mendukung request stateful menggunakan kuki sesi jika diakses dari domain frontend yang sama.

### 7.2. Alur Pendaftaran & Aktivasi Organisasi
```
[User mengisi Form] ──> [Database mencatat Org & Admin] ──> [Plan Free: Otomatis Aktif]
                                                      └──> [Plan Paid: Status Pending] ──> [Admin Verifikasi Pembayaran] ──> [Status Aktif]
```

---

## 8. Authorization

Otorisasi pada platform ini dibagi menjadi dua lapis keamanan: **Otorisasi Platform (Multi-Tenant & Global Role)** dan **Otorisasi Event (Contextual Permissions)**.

### 8.1. Hak Akses Peran Global (Tingkat Organisasi)
Setiap pengguna yang terdaftar di dalam organisasi memiliki salah satu dari tiga peran berikut:

| Modul / Fitur | Superadmin (EO) | Project Manager | Staff |
| :--- | :---: | :---: | :---: |
| Manajemen User Organisasi | **Ya** | Tidak | Tidak |
| Membuat & Menghapus Event | **Ya** | **Ya** | Tidak |
| Mengatur Alokasi Anggaran | **Ya** | **Ya** | Tidak |
| Mengelola Katalog Gudang | **Ya** | **Ya** | Tidak |
| Mencatat Pengeluaran Event | **Ya** | **Ya** | **Ya** |
| Melakukan Checkout Logistik | **Ya** | **Ya** | **Ya** |
| Melihat Laporan Keuangan | **Ya** | **Ya** | Hanya Event Sendiri |

### 8.2. Hak Akses Kontekstual (Tingkat Event)
Untuk melindungi data sensitif antar acara, pengguna dengan peran `'staff'` **hanya dapat mengakses** data event (tugas, rundown, logistik, anggaran, obrolan grup) jika mereka telah terdaftar sebagai personel pada event tersebut di tabel `event_personnel`.

Di tingkat rincian modul:
*   **Manajemen Rundown:** Hanya pengguna yang ditugaskan dengan peran pivot (`role_in_event`) sebagai `'Rundown Coordinator'`, `'Event Planner'`, `'Event Coordinator'`, atau `'Project Manager'` yang dapat menambah, mengedit, atau menghapus item rundown.
*   **Perubahan Status Rundown:**
    *   *Staf Teknis* (`role_in_event` mengandung kata `'technical'`) hanya diizinkan mengubah status rundown yang berkategori `'technical'`.
    *   *Staf Talent* (`role_in_event` mengandung kata `'talent'`) hanya diizinkan mengubah status rundown berkategori `'talent'`.
    *   *PIC Rundown* terkait dapat mengubah status item rundown yang menjadi tanggung jawabnya tanpa memandang perannya.

---

## 9. Dependency Documentation

### 9.1. Backend (`backend/composer.json`)
*   **`php` (^8.3):** Bahasa pemrograman utama backend.
*   **`laravel/framework` (^13.0):** Framework backend utama.
*   **`laravel/reverb` (^1.0):** Server protokol WebSocket berkinerja tinggi untuk pembaruan pesan obrolan dan status rundown secara instan tanpa membutuhkan server Node.js eksternal.
*   **`laravel/sanctum` (^4.3):** Pustaka penyedia token API.
*   **`laravel/tinker` (^3.0):** Konsol interaktif untuk debugging backend.
*   **`phpunit/phpunit` (Dev, ^12.5):** Kerangka kerja pengujian unit (*unit testing*).

### 9.2. Frontend Tenant (`tenant/package.json`)
*   **`vue` (^3.5.32):** Pustaka utama UI.
*   **`vue-router` (^4.6.4):** Manajemen navigasi halaman SPA.
*   **`pinia` (^3.0.4):** Pengelola state global.
*   **`axios` (^1.15.2):** Klien HTTP untuk berkomunikasi dengan API backend.
*   **`laravel-echo` (^2.3.4) & `pusher-js` (^8.5.0):** Klien WebSocket untuk mendengarkan siaran langsung event dari server Laravel Reverb.
*   **`lucide-vue-next` (^1.0.0):** Pustaka ikon UI.

---

## 10. Frontend Documentation

Sisi frontend dirancang sebagai aplikasi satu halaman (*Single Page Application* - SPA) yang sangat responsif, memanfaatkan sistem reaktivitas Vue 3 dan manajemen state terpusat via Pinia.

### 10.1. Arsitektur Antarmuka
1.  **Axios Interceptor:** Setiap aplikasi memiliki instance Axios yang secara otomatis menyuntikkan token dari `localStorage` pada header rute request:
    *   Tenant: Menyuntikkan `Authorization: Bearer localStorage.getItem('token')`.
    *   Backoffice: Menyuntikkan `Authorization: Bearer localStorage.getItem('platform_token')`.
2.  **Navigation Guards:** Vue Router dikonfigurasi untuk mencegah pengguna yang belum masuk mengakses rute dalam, serta melarang staf mengakses halaman khusus Superadmin (seperti `/users` pada portal tenant).

---

### 10.2. Analisis Halaman Utama

#### 10.2.1. Portal Tenant: Halaman Rundown (`RundownView.vue`)
*   **Tujuan:** Menyediakan timeline visual interaktif bagi tim pelaksana lapangan untuk memantau dan memperbarui jadwal acara secara langsung.
*   **Route:** `/rundown`
*   **API yang Dipanggil:**
    *   `GET /api/events/{active_event}/rundowns` (Memuat daftar jadwal dan daftar tanggal).
    *   `POST /api/events/{active_event}/rundowns` (Membuat item rundown baru).
    *   `PATCH /api/rundowns/{id}/status` (Mengubah status item rundown).
    *   `GET /api/rundowns/{id}/logs` (Memuat log audit item).
*   **Interaksi Pengguna:**
    *   Mengalihkan tab tanggal acara untuk melihat jadwal per hari.
    *   Menekan tombol status (seperti memicu status *Live* atau *Completed*).
    *   Membuka modal detail rundown untuk melihat riwayat log perubahan waktu dan PIC yang bertanggung jawab.
    *   Melihat indikator peringatan kuning jika tugas prasyarat (*task dependency*) belum ditandai selesai.

#### 10.2.2. Portal Tenant: Halaman Obrolan Tim (`ChatView.vue`)
*   **Tujuan:** Memungkinkan komunikasi real-time antar staf yang ditugaskan dalam event yang sama.
*   **Route:** `/chat`
*   **State Management & Real-time Flow:**
    *   Saat halaman dibuka, Pinia Store (`chat.js`) memicu `loadHistory(eventId)` untuk menarik 50 pesan terakhir via Axios.
    *   Store memanggil `subscribeToEvent(eventId, currentUserId)` yang menginisiasi koneksi Laravel Echo ke channel kehadiran (*Presence Channel*) `event.[eventId]`.
    *   **Presence Indicator:** Anggota tim yang sedang membuka halaman chat secara otomatis ditambahkan ke daftar pengguna online via event `.here()` dan `.joining()`, dan dihapus saat menutup halaman via `.leaving()`.
    *   **Penerimaan Pesan:** Ketika ada pesan baru dikirim oleh pengguna lain, server membroadcast event `MessageSent` yang ditangkap oleh listener `.listen('.message.sent')` di sisi client, lalu dimasukkan langsung ke dalam array reaktif sehingga pesan muncul instan tanpa me-refresh halaman.
    *   **Notifikasi Latar Belakang:** Jika pengguna sedang membuka obrolan di event A tetapi ada pesan masuk dari event B, sistem akan menaikkan angka belum dibaca (*unread count*) dan memicu notifikasi desktop browser.
*   **User Interaction:**
    *   Memilih grup obrolan berdasarkan event yang diikuti.
    *   Mengetik pesan teks atau mengunggah berkas lampiran (gambar/dokumen) melalui tombol klip kertas (didukung validasi batas ukuran file 10MB).

#### 10.2.3. Landing Page Publik (`App.vue` di `website/`)
*   **Tujuan:** Pemasaran produk, penyediaan informasi, wadah kontak admin, serta registrasi mandiri organisasi baru.
*   **Route:** `/`
*   **API yang Dipanggil:**
    *   `GET /api/landing/contents` (Memuat judul dan salinan teks dinamis).
    *   `GET /api/landing/faqs` (Memuat daftar pertanyaan umum).
    *   `POST /api/landing/contact` (Mengirim pesan hubungi kami).
    *   `POST /api/landing/register` (Mendaftarkan organisasi EO baru).
*   **Interaksi Pengguna:**
    *   **Kalkulator ROI & Paket:** Menggeser slider jumlah event dan jumlah user untuk melihat simulasi rekomendasi paket harga yang cocok dan estimasi waktu yang dapat dihemat.
    *   **Interactive Sandbox:** Menyediakan simulator mini untuk mencoba mencentang status rundown dan menggeser kartu Kanban tugas secara interaktif sebelum mendaftar.
    *   **Pendaftaran EO Modal:** Pengguna memilih paket. Jika memilih paket gratis (*Free*), pendaftaran langsung aktif. Jika memilih paket berbayar (*Business/Enterprise*), langkah kedua memandu simulasi pembayaran fiktif dan unggah tanda bukti transfer sebelum diserahkan ke admin backoffice untuk diverifikasi.

---

*Dokumen ini diperbarui secara berkala seiring dengan perkembangan fitur platform EventConnect. Hubungi Tim Pengembang Utama untuk pertanyaan teknis lebih lanjut.*
