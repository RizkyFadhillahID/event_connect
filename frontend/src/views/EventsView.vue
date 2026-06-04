<template>
  <div class="events-page">
    <div v-if="!detailEvent" style="display:contents">

    <!-- Filters -->
    <div class="filters glass-card">
      <input v-model="search" class="glass-input" placeholder="Cari berdasarkan nama atau lokasi event..." @input="fetchEvents(1)" style="max-width:320px"/>
      <select v-model="filterStatus" class="glass-input" @change="fetchEvents(1)" style="max-width:180px">
        <option value="">Semua Status</option>
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
      <button v-if="auth.canManageEvents" class="btn btn-primary" @click="openCreate" style="margin-left: auto;">
        <Plus :size="18" />
        Tambah Event
      </button>
    </div>

    <!-- Table -->
    <div class="glass-card table-container">
      <div v-if="loading" class="loading-state">
        <Loader2 :size="24" class="spinner-icon" />
        <span>Memuat data...</span>
      </div>
      <div v-else class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama Event</th>
              <th>Lokasi</th>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Personel</th>
              <th>Task</th>
              <th>Budget</th>
              <th v-if="auth.canManageEvents">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="events.length === 0">
              <td :colspan="auth.canManageEvents ? 10 : 9" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada data event</td>
            </tr>
            <tr v-for="(ev, idx) in events" :key="ev.id" class="clickable-row" @click="openDetail(ev)">
              <td style="color:var(--text-muted)">{{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}</td>
              <td>
                <div class="event-cell">
                  <div class="event-icon" :class="`icon-${ev.status}`">
                    <Calendar :size="18" />
                  </div>
                  <div>
                    <div style="font-weight:500">{{ ev.name }}</div>
                    <div style="font-size:12px;color:var(--text-muted)">{{ ev.creator?.name || '—' }}</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--text-secondary);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ ev.location }}</td>
              <td style="white-space:nowrap">
                <div style="font-size:13px">{{ formatDate(ev.start_date) }}</div>
                <div style="font-size:11px;color:var(--text-muted)" v-if="ev.end_date !== ev.start_date">s/d {{ formatDate(ev.end_date) }}</div>
              </td>
              <td>
                <span v-if="ev.category" style="font-size:12px;color:var(--text-secondary)">{{ ev.category }}</span>
                <span v-else style="color:var(--text-muted)">—</span>
              </td>
              <td><span class="badge" :class="`badge-${ev.status}`">{{ statusLabel(ev.status) }}</span></td>
              <td>
                <div v-if="ev.personnel?.length" class="personnel-avatars">
                  <div v-for="p in ev.personnel.slice(0,4)" :key="p.id" class="p-avatar" :title="p.name">{{ p.name.charAt(0) }}</div>
                  <div v-if="ev.personnel.length > 4" class="p-avatar more">+{{ ev.personnel.length - 4 }}</div>
                </div>
                <span v-else style="color:var(--text-muted);font-size:12px">—</span>
              </td>
              <td>
                <RouterLink
                  :to="{ path: '/tasks', query: { event_id: ev.id } }"
                  class="task-count-badge"
                  @click.stop
                  :title="'Lihat tasks event ini'"
                >
                  <CheckSquare :size="13" />
                  {{ eventTaskCounts[ev.id] ?? '…' }}
                </RouterLink>
              </td>
              <td style="white-space:nowrap">
                <span v-if="ev.budget" style="font-size:13px">{{ formatCurrency(ev.budget) }}</span>
                <span v-else style="color:var(--text-muted)">—</span>
              </td>
              <td v-if="auth.canManageEvents" @click.stop>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-glass btn-sm" @click="openEdit(ev)" title="Edit">
                    <Edit :size="16" />
                  </button>
                  <button class="btn btn-danger btn-sm" @click="confirmDelete(ev)" title="Hapus">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="pagination">
        <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchEvents(pagination.current_page - 1)">
          <ChevronLeft :size="18" />
        </button>
        <button v-for="p in pagination.last_page" :key="p" class="page-btn" :class="{ active: p === pagination.current_page }" @click="fetchEvents(p)">{{ p }}</button>
        <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchEvents(pagination.current_page + 1)">
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>
    </div>

    <!-- Detail Modal (view) -->
    <!-- Detail View (Full Page) -->
    <div v-else class="detail-page-container">

      <!-- Detail Card Content (styled in full-width card) -->
      <div class="glass-card detail-card-content" style="padding:24px">
          
          <!-- Modal Tabs -->
          <div class="modal-tabs">
            <button class="btn btn-glass btn-sm" @click="detailEvent = null" title="Kembali" style="display:flex; align-items:center; gap:6px; padding: 6px 12px">
            <ArrowLeft :size="16" />
          </button>
            <button type="button" class="tab-btn" :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">
              Info Umum & Tim
            </button>
            <button type="button" class="tab-btn" :class="{ active: activeTab === 'budget' }" @click="openBudgetTab">
              Anggaran & Pengeluaran
            </button>
            <button type="button" class="tab-btn" :class="{ active: activeTab === 'logistic' }" @click="openLogisticTab">
              Logistik Acara
            </button>
            <button type="button" class="tab-btn" :class="{ active: activeTab === 'report' }" @click="openReportTab">
              Laporan Evaluasi
            </button>
            <button type="button" class="tab-btn" :class="{ active: activeTab === 'guests' }" @click="openGuestsTab">
              Daftar Tamu (RSVP)
            </button>
          </div>

          <!-- Tab 1: General Info & Personnel & Tasks -->
          <div v-if="activeTab === 'info'" class="detail-grid">
            <div class="detail-section">
              <h3 class="detail-title">{{ detailEvent.name }}</h3>
              <p class="detail-desc">{{ detailEvent.description || 'Tidak ada deskripsi.' }}</p>
              <div class="detail-meta-list">
                <div class="dm-item">
                  <MapPin :size="16" />
                  <span>{{ detailEvent.location }}</span>
                </div>
                <div class="dm-item">
                  <Calendar :size="16" />
                  <span>{{ formatDate(detailEvent.start_date) }} — {{ formatDate(detailEvent.end_date) }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.start_time">
                  <Clock :size="16" />
                  <span>{{ detailEvent.start_time }} — {{ detailEvent.end_time }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.budget">
                  <DollarSign :size="16" />
                  <span>{{ formatCurrency(detailEvent.budget) }}</span>
                </div>
                <div class="dm-item" v-if="detailEvent.expected_participants">
                  <Users :size="16" />
                  <span>{{ detailEvent.expected_participants.toLocaleString('id-ID') }} peserta</span>
                </div>
                <div class="dm-item">
                  <User :size="16" />
                  <span>Dibuat oleh: {{ detailEvent.creator?.name }}</span>
                </div>
              </div>
            </div>
            <div class="detail-section">
              <h4 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--text-secondary)">TIM PERSONEL ({{ detailEvent.personnel?.length || 0 }})</h4>
              <div class="personnel-list">
                <div v-if="!detailEvent.personnel?.length" style="color:var(--text-muted);font-size:13px">Belum ada personel</div>
                <div v-for="p in detailEvent.personnel" :key="p.id" class="personnel-item">
                  <div class="p-avatar-lg">{{ p.name.charAt(0) }}</div>
                  <div>
                    <div style="font-size:13px;font-weight:500">{{ p.name }}</div>
                    <div style="font-size:11px;color:var(--text-muted)">{{ p.pivot?.role_in_event || roleLabel(p.role) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tasks section -->
            <div class="detail-section">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <h4 style="font-size:14px;font-weight:600;color:var(--text-secondary)">TASK & WORKFLOW</h4>
                <RouterLink :to="{ path: '/tasks', query: { event_id: detailEvent.id } }" class="btn btn-glass btn-sm" style="font-size:11px">
                  <CheckSquare :size="14" />
                  Semua Task
                </RouterLink>
              </div>
              <div v-if="loadingEventTasks" style="padding:16px;text-align:center">
                <Loader2 :size="20" class="spinner-icon" />
              </div>
              <div v-else>
                <div v-if="eventTaskStats" class="task-stats-bar">
                  <div class="tstat" style="color:#67e8f9"><span class="tstat-val">{{ eventTaskStats.total }}</span><span>Total</span></div>
                  <div class="tstat" style="color:#fcd34d"><span class="tstat-val">{{ eventTaskStats.in_progress }}</span><span>Berjalan</span></div>
                  <div class="tstat" style="color:#38bdf8"><span class="tstat-val">{{ eventTaskStats.review }}</span><span>Review</span></div>
                  <div class="tstat" style="color:#6ee7b7"><span class="tstat-val">{{ eventTaskStats.completed }}</span><span>Selesai</span></div>
                  <div class="tstat" style="color:#fca5a5"><span class="tstat-val">{{ eventTaskStats.overdue }}</span><span>Overdue</span></div>
                </div>
                <div v-if="eventTaskStats?.total" class="task-progress-wrap">
                  <div class="task-progress-bar">
                    <div class="task-progress-fill" :style="`width:${eventTaskStats.progress}%`"></div>
                  </div>
                  <span style="font-size:11px;color:var(--text-muted)">{{ eventTaskStats.progress }}% selesai</span>
                </div>
                <div v-if="!eventTasks.length" style="color:var(--text-muted);font-size:13px;padding:8px 0">
                  Belum ada task
                </div>
                <div v-for="t in eventTasks.slice(0, 5)" :key="t.id" class="event-task-item">
                  <span class="priority-dot-sm" :class="`pdot-${t.priority}`"></span>
                  <span class="event-task-title">{{ t.title }}</span>
                  <span class="badge event-task-status" :class="`task-badge-${t.status}`">{{ statusLabel2(t.status) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Budget & Expenses Tracker -->
          <div v-else-if="activeTab === 'budget'" class="budget-tab-wrapper">
            <div v-if="loadingBudget" class="loading-state" style="padding:48px 0">
              <Loader2 :size="24" class="spinner-icon" />
              <span>Memuat data anggaran...</span>
            </div>
            
            <div v-else class="budget-tab-content">
              <!-- Summary cards -->
              <div class="financial-summary-cards">
                <div class="fin-card">
                  <span class="fin-label">Total Anggaran</span>
                  <span class="fin-val">{{ formatCurrency(budgetData.event_budget) }}</span>
                </div>
                <div class="fin-card">
                  <span class="fin-label">Terdistribusi</span>
                  <span class="fin-val" style="color:#38bdf8">{{ formatCurrency(budgetData.total_allocated) }}</span>
                </div>
                <div class="fin-card">
                  <span class="fin-label">Aktual Terpakai</span>
                  <span class="fin-val" :style="{ color: budgetData.total_spent > budgetData.event_budget ? '#ef4444' : '#6ee7b7' }">
                    {{ formatCurrency(budgetData.total_spent) }}
                  </span>
                </div>
                <div class="fin-card">
                  <span class="fin-label">Sisa Saldo</span>
                  <span class="fin-val" :style="{ color: budgetData.remaining_budget < 0 ? '#ef4444' : '#67e8f9' }">
                    {{ formatCurrency(budgetData.remaining_budget) }}
                  </span>
                </div>
              </div>

              <!-- Overbudget warning Progress Bar -->
              <div class="budget-progress-section">
                <div class="bp-header">
                  <span>Efisiensi Pemakaian Anggaran</span>
                  <span :style="{ color: budgetPercent > 100 ? '#ef4444' : '#e0e7ff' }">
                    {{ budgetPercent }}% Terpakai
                  </span>
                </div>
                <div class="bp-track">
                  <div class="bp-fill" :class="{ 'bp-over': budgetPercent > 100 }" :style="`width: ${Math.min(budgetPercent, 100)}%`"></div>
                </div>
                <div v-if="budgetPercent > 100" class="bp-warning-text">
                  <AlertCircle :size="14" />
                  <span>Pengeluaran melebihi total anggaran sebesar {{ formatCurrency(Math.abs(budgetData.remaining_budget)) }}!</span>
                </div>
              </div>

              <!-- Split Pane Layout -->
              <div class="budget-split-pane">
                <!-- Allocations Column -->
                <div class="budget-col">
                  <div class="col-title-row">
                    <h4>Rencana Alokasi Anggaran</h4>
                    <button v-if="auth.canManageEvents" type="button" class="btn btn-glass btn-sm" @click="showAllocationForm = !showAllocationForm">
                      <Plus :size="14" />
                      Alokasi
                    </button>
                  </div>

                  <!-- Allocation Form -->
                  <Transition name="fade">
                    <div v-if="showAllocationForm" class="mini-form glass-card">
                      <div style="font-weight:600;font-size:12px;margin-bottom:8px">Tambah/Update Alokasi</div>
                      <form @submit.prevent="submitAllocation">
                        <div class="form-group-sm">
                          <label>Kategori *</label>
                          <select v-model="allocationForm.category" class="glass-input-sm" required>
                            <option value="">Pilih Kategori</option>
                            <option v-for="cat in budgetCategories" :key="cat" :value="cat">{{ cat }}</option>
                          </select>
                        </div>
                        <div class="form-group-sm">
                          <label>Jumlah Alokasi (Rp) *</label>
                          <input v-model.number="allocationForm.allocated_amount" type="number" min="0" class="glass-input-sm" required placeholder="5000000" />
                        </div>
                        <div class="form-group-sm">
                          <label>Catatan</label>
                          <input v-model="allocationForm.notes" type="text" class="glass-input-sm" placeholder="Tulis catatan opsional di sini..." />
                        </div>
                        <div class="form-actions-sm">
                          <button type="button" class="btn btn-glass btn-xs" @click="cancelAllocationForm">Batal</button>
                          <button type="submit" class="btn btn-primary btn-xs" :disabled="submittingAllocation">Simpan</button>
                        </div>
                      </form>
                    </div>
                  </Transition>

                  <div class="allocations-list">
                    <div v-if="budgetData.allocations.length === 0" class="empty-list-text">Belum ada alokasi anggaran</div>
                    <div v-for="alloc in budgetData.allocations" :key="alloc.id" class="alloc-item">
                      <div class="alloc-info-row">
                        <span class="alloc-cat">{{ alloc.category }}</span>
                        <span class="alloc-amt">{{ formatCurrency(alloc.allocated_amount) }}</span>
                      </div>
                      <div class="alloc-progress-row">
                        <div class="alloc-bar-track">
                          <div class="alloc-bar-fill" :class="{ 'over': getAllocPercent(alloc) > 100 }" :style="`width: ${Math.min(getAllocPercent(alloc), 100)}%`"></div>
                        </div>
                        <span class="alloc-percent">{{ getAllocPercent(alloc) }}% terpakai ({{ formatCurrency(getAllocSpent(alloc)) }})</span>
                      </div>
                      <div v-if="alloc.notes" class="alloc-notes">{{ alloc.notes }}</div>
                      <button v-if="auth.canManageEvents" type="button" class="delete-alloc-btn" @click="deleteAllocation(alloc)" title="Hapus Alokasi">
                        <Trash2 :size="12" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Expenses Column -->
                <div class="budget-col">
                  <div class="col-title-row">
                    <h4>Riwayat Pengeluaran Aktual</h4>
                    <button type="button" class="btn btn-primary btn-sm" @click="showExpenseForm = !showExpenseForm">
                      <Plus :size="14" />
                      Catat
                    </button>
                  </div>

                  <!-- Expense Form -->
                  <Transition name="fade">
                    <div v-if="showExpenseForm" class="mini-form glass-card">
                      <div style="font-weight:600;font-size:12px;margin-bottom:8px">Catat Transaksi Pengeluaran</div>
                      <form @submit.prevent="submitExpense">
                        <div class="form-group-sm">
                          <label>Nama Transaksi *</label>
                          <input v-model="expenseForm.title" type="text" class="glass-input-sm" required placeholder="Masukkan keterangan pengeluaran" />
                        </div>
                        <div class="form-group-sm">
                          <label>Kategori *</label>
                          <select v-model="expenseForm.category" class="glass-input-sm" required>
                            <option value="">Pilih Kategori</option>
                            <option v-for="cat in budgetCategories" :key="cat" :value="cat">{{ cat }}</option>
                          </select>
                        </div>
                        <div class="form-row-sm">
                          <div class="form-group-sm">
                            <label>Nominal (Rp) *</label>
                            <input v-model.number="expenseForm.amount" type="number" min="0" class="glass-input-sm" required placeholder="Masukkan nominal" />
                          </div>
                          <div class="form-group-sm">
                            <label>Tanggal *</label>
                            <input v-model="expenseForm.spent_at" type="date" class="glass-input-sm" required />
                          </div>
                        </div>
                        <div class="form-row-sm">
                          <div class="form-group-sm">
                            <label>Nama Vendor</label>
                            <input v-model="expenseForm.vendor_name" type="text" class="glass-input-sm" placeholder="Masukkan nama vendor atau toko" />
                          </div>
                          <div class="form-group-sm">
                            <label>Metode</label>
                            <select v-model="expenseForm.payment_method" class="glass-input-sm">
                              <option value="cash">Cash</option>
                              <option value="transfer">Transfer</option>
                              <option value="credit_card">Kartu Kredit</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-row-sm">
                          <div class="form-group-sm" style="grid-column: span 2">
                            <label>Status</label>
                            <select v-model="expenseForm.payment_status" class="glass-input-sm">
                              <option value="paid">Lunas (Paid)</option>
                              <option value="pending">Pending</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group-sm">
                          <label>Catatan</label>
                          <input v-model="expenseForm.notes" type="text" class="glass-input-sm" placeholder="Tulis catatan opsional di sini..." />
                        </div>
                        <div class="form-actions-sm">
                          <button type="button" class="btn btn-glass btn-xs" @click="cancelExpenseForm">Batal</button>
                          <button type="submit" class="btn btn-primary btn-xs" :disabled="submittingExpense">Simpan</button>
                        </div>
                      </form>
                    </div>
                  </Transition>

                  <div class="expenses-list">
                    <div v-if="budgetData.expenses.length === 0" class="empty-list-text">Belum ada catatan transaksi pengeluaran</div>
                    <div v-for="exp in budgetData.expenses" :key="exp.id" class="expense-item">
                      <div class="exp-title-row">
                        <span class="exp-title">{{ exp.title }}</span>
                        <span class="exp-amt">{{ formatCurrency(exp.amount) }}</span>
                      </div>
                      <div class="exp-meta-row" style="margin-top:2px">
                        <span class="badge" style="font-size:9px;padding:1px 5px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)">{{ exp.category }}</span>
                        <span class="exp-date">{{ formatDateShort(exp.spent_at) }}</span>
                      </div>
                      <div class="exp-bottom-row" style="margin-top:2px">
                        <span class="exp-user" :title="`Dicatat oleh ${exp.user?.name}`">Kru: {{ exp.user?.name ? exp.user.name.split(' ')[0] : '—' }}</span>
                        <span class="badge" :class="exp.payment_status === 'paid' ? 'badge-completed' : 'badge-review'" style="font-size:9px;padding:1px 5px">{{ exp.payment_status === 'paid' ? 'Paid' : 'Pending' }}</span>
                      </div>
                      <div v-if="exp.notes" class="exp-notes">Catatan: {{ exp.notes }}</div>
                      <button v-if="auth.canManageEvents || exp.user_id === auth.user?.id" type="button" class="delete-exp-btn" @click="deleteExpense(exp)" title="Hapus Transaksi">
                        <Trash2 :size="12" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 3: Logistik Acara -->
          <div v-else-if="activeTab === 'logistic'" class="budget-tab-wrapper">
            <div v-if="loadingLogistic" class="loading-state" style="padding:48px 0">
              <Loader2 :size="24" class="spinner-icon" />
              <span>Memuat data logistik...</span>
            </div>
            
            <div v-else class="budget-tab-content">
              <!-- Split Pane Layout -->
              <div class="budget-split-pane">
                <!-- Left Column: Allocated Items -->
                <div class="budget-col">
                  <div class="col-title-row">
                    <h4>Logistik Terdeploy di Lapangan</h4>
                  </div>

                  <div class="expenses-list">
                    <div v-if="logisticData.length === 0" class="empty-list-text">Belum ada barang logistik dialokasikan untuk event ini</div>
                    <div v-for="logi in logisticData" :key="logi.id" class="expense-item">
                      <div class="exp-title-row">
                        <span class="exp-title">{{ logi.inventory?.item_name }}</span>
                        <span class="exp-amt">{{ logi.quantity }} unit</span>
                      </div>
                      
                      <div class="exp-meta-row" style="margin-top:2px">
                        <span class="badge" :class="logi.inventory?.ownership === 'owned' ? 'badge-owned' : 'badge-rented'" style="font-size:9px;padding:1px 5px">
                          {{ logi.inventory?.ownership === 'owned' ? 'Milik EO' : 'Sewa' }}
                        </span>
                        <span class="exp-date">PIC: <strong>{{ logi.user?.name }}</strong></span>
                      </div>

                      <div class="exp-bottom-row" style="margin-top:2px">
                        <span class="exp-user">Keluar: {{ formatDateShort(logi.borrowed_at) }}</span>
                        <span v-if="logi.returned_at" class="badge badge-completed" style="font-size:9px;padding:1px 5px">
                          Kembali: {{ formatDateShort(logi.returned_at) }} ({{ logi.return_status === 'complete' ? 'Lengkap' : logi.return_status === 'incomplete' ? 'Kurang' : 'Rusak' }})
                        </span>
                        <span v-else class="badge badge-review" style="font-size:9px;padding:1px 5px">Di Lapangan</span>
                      </div>

                      <div v-if="logi.rent_cost > 0" style="font-size:10px; color:#fca5a5; margin-top:2px; font-weight:600">
                        Biaya Sewa Alat: {{ formatCurrency(logi.rent_cost) }}
                      </div>
                      <div v-if="logi.notes" class="exp-notes">Catatan: {{ logi.notes }}</div>
                      
                      <!-- Quick check-in / return button -->
                      <div style="display:flex; justify-content:flex-end; gap:6px; margin-top:8px">
                        <button v-if="!logi.returned_at" type="button" class="btn btn-glass btn-xs" @click="openEventReturn(logi)">
                          <ArrowDownLeft :size="12" style="margin-right:2px" /> Kembalikan
                        </button>
                        <button v-if="auth.canManageEvents" type="button" class="btn btn-danger btn-xs" @click="deleteLogistic(logi)" title="Batalkan Peminjaman">
                          Hapus
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Checkout Form -->
                <div class="budget-col">
                  <div class="col-title-row">
                    <h4>Alokasikan Logistik Baru</h4>
                  </div>

                  <div class="mini-form glass-card" style="margin-bottom:0">
                    <form @submit.prevent="submitLogistic">
                      <div class="form-group-sm">
                        <label>Pilih Barang Gudang *</label>
                        <select v-model="logisticForm.inventory_id" class="glass-input-sm" required>
                          <option value="">Pilih Barang</option>
                          <option v-for="item in availableWarehouseItems" :key="item.id" :value="item.id">
                            {{ item.item_name }} (Ready: {{ item.available_quantity }} unit)
                          </option>
                        </select>
                      </div>

                      <div class="form-group-sm">
                        <label>Pilih Kru PIC Lapangan *</label>
                        <select v-model="logisticForm.user_id" class="glass-input-sm" required>
                          <option value="">Pilih Personel PIC</option>
                          <!-- Only show event personnel! -->
                          <option v-for="p in detailEvent.personnel" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.pivot?.role_in_event || roleLabel(p.role) }})
                          </option>
                        </select>
                      </div>

                      <div class="form-row-sm">
                        <div class="form-group-sm">
                          <label>Jumlah Unit *</label>
                          <input v-model.number="logisticForm.quantity" type="number" min="1" :max="selectedInventory ? selectedInventory.available_quantity : 99" class="glass-input-sm" required />
                        </div>
                        <div class="form-group-sm">
                          <label>Tgl Penyerahan *</label>
                          <input v-model="logisticForm.borrowed_at" type="date" class="glass-input-sm" required />
                        </div>
                      </div>

                      <!-- Rent cost calculation preview -->
                      <Transition name="fade">
                        <div v-if="selectedInventory && selectedInventory.ownership === 'rented'" style="margin: 8px 0; padding:8px 10px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:6px; font-size:11px; color:#fca5a5;">
                          Barang sewaan. Durasi s/d akhir event: <strong>{{ rentDays }} hari</strong>.<br>
                          Estimasi Pengeluaran Sewa: <strong>{{ formatCurrency(estimatedRentCost) }}</strong>.
                        </div>
                      </Transition>

                      <div class="form-group-sm">
                        <label>Catatan Alokasi</label>
                        <input v-model="logisticForm.notes" type="text" class="glass-input-sm" placeholder="Tulis catatan opsional di sini..." />
                      </div>

                      <div class="form-actions-sm" style="margin-top:12px">
                        <button type="button" class="btn btn-glass btn-xs" @click="cancelLogisticForm">Reset Form</button>
                        <button type="submit" class="btn btn-primary btn-xs" :disabled="submittingLogistic || (selectedInventory && selectedInventory.available_quantity < logisticForm.quantity)">Alokasikan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tab 4: Laporan Evaluasi -->
          <div v-else-if="activeTab === 'report'" class="budget-tab-wrapper">
            <div v-if="loadingReport" class="loading-state" style="padding:48px 0">
              <Loader2 :size="24" class="spinner-icon" />
              <span>Memuat laporan evaluasi...</span>
            </div>

            <div v-else class="budget-tab-content">
              <!-- CASE 1: REPORT IS NOT YET FINALIZED -->
              <div v-if="!reportData.is_finalized" class="report-not-finalized-container" style="width: 100%">
                <!-- If the user is PM/Admin, show draft creator form -->
                <div v-if="auth.canManageEvents" class="report-creator-form-wrap" style="width: 100%">
                  <div class="report-alert-info glass-card" style="margin-bottom: 20px; display: flex; gap: 12px; padding: 14px; border: 1px solid rgba(14, 165, 233, 0.25); background: rgba(14, 165, 233, 0.06); border-radius:12px;">
                    <FileBarChart2 :size="32" style="color: #38bdf8; flex-shrink: 0;" />
                    <div>
                      <h4 style="font-size:14px;font-weight:600;margin-bottom:4px;color:#e0f2fe">Laporan Evaluasi Belum Diterbitkan</h4>
                      <p style="font-size:12px;color:var(--text-secondary);line-height:1.5;margin:0">
                        Sebagai Project Manager, Anda dapat menyusun dan mempublikasikan laporan evaluasi pasca-event di bawah ini. Halaman ini menyajikan preview metrics aktual saat ini. Setelah diterbitkan, data ini akan dikunci sebagai snapshot sejarah.
                      </p>
                    </div>
                  </div>

                  <div class="budget-split-pane" style="gap:24px">
                    <!-- Left column: Metrics preview -->
                    <div class="budget-col">
                      <h4 style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text-secondary);border-bottom:1px solid rgba(255,255,255,0.05);padding-bottom:6px">Metrik Evaluasi Aktual (Preview)</h4>
                      
                      <!-- Tasks efficiency preview card -->
                      <div class="report-metric-card glass-card" style="padding: 12px; margin-bottom: 12px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:12px;">
                        <div class="rm-header" style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--text-secondary); margin-bottom:10px;">
                          <CheckSquare :size="16" />
                          <span>Penyelesaian Tugas</span>
                        </div>
                        <div class="rm-body" style="display:flex; gap:16px; align-items:center;">
                          <div class="rm-progress-circle-wrap">
                            <div class="circle-progress" :style="`background: radial-gradient(closest-side, #13243d 79%, transparent 80% 100%), conic-gradient(#10b981 ${reportData.preview?.task?.progress || 0}%, rgba(255,255,255,0.1) 0)`">
                              <span class="circle-val">{{ reportData.preview?.task?.progress || 0 }}%</span>
                            </div>
                          </div>
                          <div class="rm-details-list" style="font-size:12px; line-height:1.6;">
                            <div class="rm-det">Total Task: <strong>{{ reportData.preview?.task?.total }}</strong></div>
                            <div class="rm-det">Selesai: <span style="color:#6ee7b7;font-weight:600;">{{ reportData.preview?.task?.completed }}</span></div>
                            <div class="rm-det">Overdue: <span :style="{ color: (reportData.preview?.task?.overdue > 0 ? '#ef4444' : 'var(--text-muted)'), fontWeight: reportData.preview?.task?.overdue > 0 ? '600' : 'normal' }">{{ reportData.preview?.task?.overdue }} task</span></div>
                          </div>
                        </div>
                      </div>

                      <!-- Budget summary preview card -->
                      <div class="report-metric-card glass-card" style="padding: 12px; margin-bottom: 12px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:12px;">
                        <div class="rm-header" style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--text-secondary); margin-bottom:10px;">
                          <DollarSign :size="16" />
                          <span>Kesehatan Anggaran</span>
                        </div>
                        <div class="rm-body-column" style="width: 100%;">
                          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                            <span>Anggaran Terpakai</span>
                            <span :style="{ color: reportData.preview?.budget?.total_spent > reportData.preview?.budget?.event_budget ? '#ef4444' : '#6ee7b7', fontWeight: '600' }">
                              {{ formatCurrency(reportData.preview?.budget?.total_spent || 0) }} / {{ formatCurrency(reportData.preview?.budget?.event_budget || 0) }}
                            </span>
                          </div>
                          <div class="bp-track" style="margin-bottom:8px; height:6px">
                            <div class="bp-fill" :class="{ 'bp-over': reportData.preview?.budget?.total_spent > reportData.preview?.budget?.event_budget }" :style="`width: ${Math.min(reportData.preview?.budget?.event_budget ? (reportData.preview.budget.total_spent / reportData.preview.budget.event_budget) * 100 : 0, 100)}%`"></div>
                          </div>
                          <div style="font-size:11px;color:var(--text-muted);font-style:italic">
                            Sisa Saldo: <strong>{{ formatCurrency(reportData.preview?.budget?.remaining_budget || 0) }}</strong>
                          </div>
                        </div>
                      </div>

                      <!-- Logistics preview card -->
                      <div class="report-metric-card glass-card" style="padding: 12px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:12px;">
                        <div class="rm-header" style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--text-secondary); margin-bottom:10px;">
                          <Boxes :size="16" />
                          <span>Logistik &amp; Aset</span>
                        </div>
                        <div class="rm-body">
                          <div class="rm-details-list" style="width:100%; font-size:12px; line-height:1.6;">
                            <div class="rm-det">Total Alokasi Deployed: <strong>{{ reportData.preview?.logistic?.total_allocations_count }} item</strong></div>
                            <div class="rm-det">Total Unit Logistik: <strong>{{ reportData.preview?.logistic?.total_items_borrowed }} unit</strong></div>
                            <div class="rm-det">Masih di Lapangan: <span :style="{ color: reportData.preview?.logistic?.items_in_field > 0 ? '#f59e0b' : '#6ee7b7', fontWeight: '600' }">{{ reportData.preview?.logistic?.items_in_field }} unit</span></div>
                            <div class="rm-det" style="border-top:1px solid rgba(255,255,255,0.05);padding-top:6px;margin-top:6px;font-size:11px;">
                              Status Kembali: Lengkap (<span style="color:#6ee7b7">{{ reportData.preview?.logistic?.returned_complete }}</span>), Rusak (<span style="color:#fca5a5">{{ reportData.preview?.logistic?.returned_damaged }}</span>), Kurang (<span style="color:#fcd34d">{{ reportData.preview?.logistic?.returned_incomplete }}</span>)
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Right column: PM Form inputs -->
                    <div class="budget-col">
                      <h4 style="font-size:13px;font-weight:600;margin-bottom:12px;color:var(--text-secondary);border-bottom:1px solid rgba(255,255,255,0.05);padding-bottom:6px">Form Penerbitan Laporan Evaluasi</h4>
                      <form @submit.prevent="submitReport">
                        <div class="form-group-sm">
                          <label>Catatan Evaluasi / Kelebihan &amp; Kekurangan Event *</label>
                          <textarea v-model="reportForm.evaluation_notes" class="glass-input-sm" rows="6" required placeholder="Tuliskan evaluasi jalannya event di sini..."></textarea>
                        </div>
                        <div class="form-group-sm" style="margin-top:12px">
                          <label>Rekomendasi Utama Masa Depan *</label>
                          <textarea v-model="reportForm.recommendations" class="glass-input-sm" rows="4" required placeholder="Tuliskan rekomendasi atau saran perbaikan untuk event selanjutnya..."></textarea>
                        </div>
                        
                        <div class="form-actions-sm" style="margin-top:18px">
                          <button type="submit" class="btn btn-primary btn-sm" :disabled="submittingReport" style="width: 100%; justify-content:center">
                            <Loader2 v-if="submittingReport" :size="16" class="spinner-icon" />
                            <FileBarChart2 v-else :size="16" />
                            Menerbitkan Laporan Evaluasi
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- If user is Staff, show waiting state -->
                <div v-else class="report-empty-state-wrap glass-card" style="padding: 48px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; width: 100%">
                  <FileBarChart2 :size="48" style="color: var(--text-muted); margin-bottom:12px" />
                  <h3 style="font-size:16px; font-weight:600;">Belum Ada Laporan Evaluasi</h3>
                  <p style="font-size:13px; color:var(--text-secondary); max-width:400px; line-height:1.5">Laporan evaluasi pasca-event belum diterbitkan oleh Project Manager untuk event ini.</p>
                </div>
              </div>

              <!-- CASE 2: REPORT IS FINALLY PUBLISHED (READ/PRINT VIEW) -->
              <div v-else class="final-report-wrapper" style="width: 100%">
                <div class="report-top-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.06)">
                  <span class="report-status-badge" style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:600; padding:4px 10px; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); border-radius:20px; color:#6ee7b7;">
                    <CheckSquare :size="12" /> Laporan Diterbitkan
                  </span>
                  <div style="display:flex; gap:8px">
                    <button type="button" class="btn btn-glass btn-sm" @click="printReport">
                      <Printer :size="14" style="margin-right:2px" /> Cetak / Simpan PDF
                    </button>
                    <button v-if="auth.canManageEvents" type="button" class="btn btn-glass btn-sm" @click="editFinalReport">
                      <Edit :size="14" style="margin-right:2px" /> Edit Catatan
                    </button>
                    <button v-if="auth.canManageEvents" type="button" class="btn btn-danger btn-sm" @click="deleteReport">
                      Hapus Laporan
                    </button>
                  </div>
                </div>

                <!-- Printable Report Sheet container -->
                <div id="printable-report-sheet" class="report-sheet">
                  <!-- Header -->
                  <div class="rs-header" style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid rgba(255,255,255,0.1)">
                    <div class="rs-brand-section" style="display:flex; align-items:center; gap:8px; margin-bottom:12px">
                      <div class="rs-brand-icon" style="width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
                        <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
                          <rect width="40" height="40" rx="10" fill="url(#g1)"/>
                          <path d="M12 20h16M20 12l8 8-8 8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                      <span style="font-weight: 700; font-size:12px; letter-spacing:0.05em; color:var(--text-muted)">EVENTCONNECT PLATFORM</span>
                    </div>
                    <div class="rs-title-section">
                      <h2 style="font-size: 18px; font-weight: 700; color: #fff; letter-spacing:0.02em;">LAPORAN EVALUASI &amp; PERFORMA ACARA</h2>
                      <div class="rs-event-title" style="font-size: 22px; font-weight: 700; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-top:4px;">{{ detailEvent.name }}</div>
                      <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                        Tanggal Pelaksanaan: <strong>{{ formatDate(detailEvent.start_date) }}</strong> s/d <strong>{{ formatDate(detailEvent.end_date) }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- Executive snapshot summary cards -->
                  <div class="financial-summary-cards" style="margin-bottom: 24px; display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;">
                    <div class="fin-card">
                      <span class="fin-label">Tingkat Tugas Selesai</span>
                      <span class="fin-val" style="color:#6ee7b7">{{ reportData.report?.task_snapshot?.progress }}%</span>
                    </div>
                    <div class="fin-card">
                      <span class="fin-label">Total Dana Terpakai</span>
                      <span class="fin-val" :style="{ color: reportData.report?.budget_snapshot?.total_spent > reportData.report?.budget_snapshot?.event_budget ? '#ef4444' : '#6ee7b7' }">
                        {{ formatCurrency(reportData.report?.budget_snapshot?.total_spent || 0) }}
                      </span>
                    </div>
                    <div class="fin-card">
                      <span class="fin-label">Efisiensi Finansial</span>
                      <span class="fin-val" :style="{ color: reportData.report?.budget_snapshot?.remaining_budget < 0 ? '#ef4444' : '#67e8f9' }">
                        {{ reportData.report?.budget_snapshot?.remaining_budget >= 0 ? 'Surplus' : 'Defisit' }}
                      </span>
                    </div>
                    <div class="fin-card">
                      <span class="fin-label">Kehilangan/Kerusakan Aset</span>
                      <span class="fin-val" :style="{ color: (reportData.report?.logistic_snapshot?.returned_damaged > 0 || reportData.report?.logistic_snapshot?.returned_incomplete > 0 ? '#ef4444' : '#6ee7b7') }">
                        {{ (reportData.report?.logistic_snapshot?.returned_damaged || 0) + (reportData.report?.logistic_snapshot?.returned_incomplete || 0) }} unit
                      </span>
                    </div>
                  </div>

                  <!-- Section: Metrics detailed analysis -->
                  <div class="budget-split-pane" style="gap:24px">
                    <!-- Left: Tasks and Logistics snapshot -->
                    <div class="budget-col">
                      <div class="rs-sec-title" style="font-size:12px; font-weight:700; color:#38bdf8; text-transform:uppercase; margin-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:4px; display:flex; align-items:center; gap:6px;">
                        <CheckSquare :size="14" /> Evaluasi Operasional &amp; Task
                      </div>
                      <div class="rs-metric-detail-box" style="padding:10px 14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:10px; font-size:12px; display:flex; flex-direction:column; gap:8px;">
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Total Rencana Tugas:</span>
                          <strong>{{ reportData.report?.task_snapshot?.total }} task</strong>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Tugas Sukses Diselesaikan:</span>
                          <span style="color:#6ee7b7; font-weight:600">{{ reportData.report?.task_snapshot?.completed }} task</span>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Tugas Tertunda / Batal:</span>
                          <span>{{ (reportData.report?.task_snapshot?.pending || 0) + (reportData.report?.task_snapshot?.in_progress || 0) + (reportData.report?.task_snapshot?.review || 0) + (reportData.report?.task_snapshot?.cancelled || 0) }} task</span>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Tugas Lewat Batas (Overdue):</span>
                          <span :style="{ color: reportData.report?.task_snapshot?.overdue > 0 ? '#ef4444' : 'inherit', fontWeight: reportData.report?.task_snapshot?.overdue > 0 ? '600' : 'normal' }">
                            {{ reportData.report?.task_snapshot?.overdue }} task
                          </span>
                        </div>
                      </div>

                      <div class="rs-sec-title" style="font-size:12px; font-weight:700; color:#38bdf8; text-transform:uppercase; margin-top: 18px; margin-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:4px; display:flex; align-items:center; gap:6px;">
                        <Boxes :size="14" /> Evaluasi Inventaris &amp; Aset Logistik
                      </div>
                      <div class="rs-metric-detail-box" style="padding:10px 14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:10px; font-size:12px; display:flex; flex-direction:column; gap:8px;">
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Total Distribusi Alokasi:</span>
                          <strong>{{ reportData.report?.logistic_snapshot?.total_allocations_count }} item</strong>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Total Unit Logistik Keluar:</span>
                          <strong>{{ reportData.report?.logistic_snapshot?.total_items_borrowed }} unit</strong>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Kelengkapan Pengembalian:</span>
                          <span style="color:#6ee7b7; font-weight:600">
                            {{ reportData.report?.logistic_snapshot?.returned_complete }} / {{ reportData.report?.logistic_snapshot?.total_items_borrowed }} unit
                          </span>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Aset Rusak saat Check-in:</span>
                          <span :style="{ color: reportData.report?.logistic_snapshot?.returned_damaged > 0 ? '#ef4444' : 'inherit', fontWeight: reportData.report?.logistic_snapshot?.returned_damaged > 0 ? '600' : 'normal' }">
                            {{ reportData.report?.logistic_snapshot?.returned_damaged }} unit
                          </span>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Kekurangan / Aset Hilang:</span>
                          <span :style="{ color: reportData.report?.logistic_snapshot?.returned_incomplete > 0 ? '#ef4444' : 'inherit', fontWeight: reportData.report?.logistic_snapshot?.returned_incomplete > 0 ? '600' : 'normal' }">
                            {{ reportData.report?.logistic_snapshot?.returned_incomplete }} unit
                          </span>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between" v-if="reportData.report?.logistic_snapshot?.total_rent_costs > 0">
                          <span>Total Pengeluaran Sewa Aset:</span>
                          <span style="color:#fca5a5; font-weight:600">{{ formatCurrency(reportData.report?.logistic_snapshot?.total_rent_costs) }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Right: Financial / Budget snapshot -->
                    <div class="budget-col">
                      <div class="rs-sec-title" style="font-size:12px; font-weight:700; color:#38bdf8; text-transform:uppercase; margin-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:4px; display:flex; align-items:center; gap:6px;">
                        <DollarSign :size="14" /> Evaluasi Finansial &amp; Anggaran
                      </div>
                      
                      <div class="rs-metric-detail-box" style="padding:10px 14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:10px; font-size:12px; display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Limit Anggaran Terpancang:</span>
                          <strong>{{ formatCurrency(reportData.report?.budget_snapshot?.event_budget || 0) }}</strong>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Aktual Dana Pengeluaran:</span>
                          <strong :style="{ color: reportData.report?.budget_snapshot?.total_spent > reportData.report?.budget_snapshot?.event_budget ? '#ef4444' : '#6ee7b7' }">
                            {{ formatCurrency(reportData.report?.budget_snapshot?.total_spent || 0) }}
                          </strong>
                        </div>
                        <div class="rs-met-row" style="display:flex; justify-content:space-between">
                          <span>Sisa Kelebihan Saldo (Surplus):</span>
                          <span :style="{ color: reportData.report?.budget_snapshot?.remaining_budget < 0 ? '#ef4444' : '#67e8f9', fontWeight: '600' }">
                            {{ formatCurrency(reportData.report?.budget_snapshot?.remaining_budget || 0) }}
                          </span>
                        </div>
                      </div>

                      <div>
                        <div style="font-size:10px; color:var(--text-muted); font-weight:600; margin-bottom: 6px; text-transform:uppercase; letter-spacing:0.02em;">Alokasi Pengeluaran per Kategori</div>
                        <div class="rs-cat-breakdown-list" style="display:flex; flex-direction:column; gap:8px;">
                          <div v-for="cat in reportData.report?.budget_snapshot?.categories" :key="cat.category" class="rs-cat-item">
                            <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px">
                              <span style="color:var(--text-secondary)">{{ cat.category }}</span>
                              <span style="font-weight:600">{{ formatCurrency(cat.spent) }}</span>
                            </div>
                            <div class="bp-track" style="height:4px; background:rgba(255,255,255,0.06)">
                              <div class="bp-fill" :class="{ 'bp-over': cat.spent > cat.allocated }" :style="`width: ${Math.min(cat.allocated > 0 ? (cat.spent / cat.allocated) * 100 : 0, 100)}%`"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- PM customized review and feedback -->
                  <div class="rs-sec-title" style="font-size:12px; font-weight:700; color:#38bdf8; text-transform:uppercase; margin-top: 24px; margin-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:4px; display:flex; align-items:center; gap:6px;">
                    <FileBarChart2 :size="14" /> Catatan Evaluasi &amp; Penilaian Project Manager
                  </div>
                  <div class="rs-pm-notes-box glass-card" style="padding: 14px 18px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px;">
                    <div style="font-size: 10px; font-weight: 600; color: #38bdf8; text-transform:uppercase; margin-bottom: 6px; letter-spacing:0.05em;">Ulasan Evaluasi Acara:</div>
                    <p class="rs-notes-text" style="font-size:12.5px; line-height:1.6; color:#e2e8f0; margin:0; font-style:italic;">"{{ reportData.report?.evaluation_notes }}"</p>
                    
                    <div style="font-size: 10px; font-weight: 600; color: #38bdf8; text-transform:uppercase; margin-top: 16px; margin-bottom: 6px; letter-spacing:0.05em;">Rekomendasi Utama untuk Event Mendatang:</div>
                    <p class="rs-notes-text" style="font-size:12.5px; line-height:1.6; color:#e2e8f0; margin:0; font-style:italic;">"{{ reportData.report?.recommendations }}"</p>
                  </div>

                  <!-- Footer details -->
                  <div class="rs-footer" style="display:flex; justify-content:space-between; margin-top:32px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-size:11px; color:var(--text-secondary)">
                    <div style="text-align: left;">
                      <div>Diterbitkan oleh Project Manager:</div>
                      <strong style="color: #fff; font-size:12px; display:inline-block; margin-top:2px;">{{ reportData.report?.user?.name || 'Project Manager' }}</strong>
                      <div style="font-size:10px; color:var(--text-muted);">{{ formatRole(reportData.report?.user?.role || '') }}</div>
                    </div>
                    <div style="text-align: right;">
                      <div>Tanggal Penerbitan Laporan:</div>
                      <strong style="color: #fff; font-size:12px; display:inline-block; margin-top:2px;">{{ formatDate(reportData.report?.finalized_at) }}</strong>
                      <div style="font-size:10px; color:var(--text-muted);">EventConnect Report Snapshot</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 5: Daftar Tamu (RSVP & Check-in) -->
          <div v-else-if="activeTab === 'guests'" class="budget-tab-wrapper">
            <div v-if="loadingGuests" class="loading-state" style="padding:48px 0">
              <Loader2 :size="24" class="spinner-icon" />
              <span>Memuat daftar tamu...</span>
            </div>
            
            <div v-else class="budget-tab-content">
              <!-- Split Pane Layout -->
              <div class="budget-split-pane" style="gap: 24px;">
                
                <!-- Left Column: Add / Edit Guest Form -->
                <div class="budget-col" style="flex: 1 1 350px;">
                  <div class="col-title-row">
                    <h4>{{ isEditingGuest ? 'Edit Informasi Tamu' : 'Tambah Tamu Undangan' }}</h4>
                  </div>
                  
                  <form @submit.prevent="saveGuest" class="budget-form glass-card" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px;">
                    <div class="form-group">
                      <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Nama Tamu <strong style="color:#ef4444">*</strong></label>
                      <input 
                        type="text" 
                        v-model="guestForm.name" 
                        class="glass-input" 
                        placeholder="Contoh: Budi Santoso" 
                        required 
                        style="width: 100%; border-radius: 8px; font-size:13px; padding: 10px 12px;"
                      />
                    </div>
                    
                    <div class="form-group">
                      <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Email Tamu</label>
                      <input 
                        type="email" 
                        v-model="guestForm.email" 
                        class="glass-input" 
                        placeholder="Contoh: budi.santoso@domain.com" 
                        style="width: 100%; border-radius: 8px; font-size:13px; padding: 10px 12px;"
                      />
                    </div>
                    
                    <div class="form-group">
                      <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">No HP / Telpon</label>
                      <input 
                        type="text" 
                        v-model="guestForm.phone" 
                        class="glass-input" 
                        placeholder="Contoh: 081234567890" 
                        style="width: 100%; border-radius: 8px; font-size:13px; padding: 10px 12px;"
                      />
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 12px;">
                      <div class="form-group" style="flex: 1;">
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Kategori <strong style="color:#ef4444">*</strong></label>
                        <select 
                          v-model="guestForm.category" 
                          class="glass-input" 
                          required 
                          style="width: 100%; border-radius: 8px; font-size:13px; padding: 10px 12px;"
                        >
                          <option value="vvip">VVIP</option>
                          <option value="vip">VIP</option>
                          <option value="regular">Regular</option>
                        </select>
                      </div>
                      
                      <div class="form-group" style="flex: 1;">
                        <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px">Status RSVP <strong style="color:#ef4444">*</strong></label>
                        <select 
                          v-model="guestForm.rsvp_status" 
                          class="glass-input" 
                          required 
                          style="width: 100%; border-radius: 8px; font-size:13px; padding: 10px 12px;"
                        >
                          <option value="pending">Pending</option>
                          <option value="attending">Hadir (Attending)</option>
                          <option value="declined">Tidak Hadir (Declined)</option>
                        </select>
                      </div>
                    </div>
                    
                    <div class="form-actions" style="display: flex; gap: 10px; margin-top: 6px;">
                      <button 
                        type="submit" 
                        class="btn btn-primary" 
                        style="flex: 1; font-size: 13px; height: 38px; border-radius: 8px; display:flex; justify-content:center; align-items:center; font-weight: 600;"
                      >
                        {{ isEditingGuest ? 'Simpan Perubahan' : 'Daftarkan Tamu' }}
                      </button>
                      <button 
                        v-if="isEditingGuest" 
                        type="button" 
                        class="btn btn-glass" 
                        @click="resetGuestForm" 
                        style="font-size: 13px; height: 38px; border-radius: 8px;"
                      >
                        Batal
                      </button>
                    </div>
                  </form>
                </div>
                
                <!-- Right Column: Guests List and Stats -->
                <div class="budget-col" style="flex: 2 1 500px;">
                  <div class="col-title-row" style="display:flex; justify-content:space-between; align-items:center;">
                    <h4>Daftar Tamu ({{ filteredGuests.length }} / {{ guestsData.length }})</h4>
                    
                    <!-- Stats summary pills -->
                    <div style="display: flex; gap: 8px; font-size: 11px;">
                      <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 2px 8px; border-radius: 20px; font-weight:600">
                        {{ guestsStats.attending }} RSVP Hadir
                      </span>
                      <span style="background: rgba(6, 182, 212, 0.15); color: #67e8f9; padding: 2px 8px; border-radius: 20px; font-weight:600">
                        {{ guestsStats.checkedIn }} Check-in
                      </span>
                    </div>
                  </div>
                  
                  <!-- Filter & Search Toolbar -->
                  <div class="guest-toolbar" style="display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
                    <input 
                      type="text" 
                      v-model="guestSearch" 
                      placeholder="Cari nama tamu..." 
                      class="glass-input" 
                      style="flex: 1 1 200px; font-size:12px; padding: 8px 12px; border-radius: 8px;"
                    />
                    
                    <select 
                      v-model="guestCategoryFilter" 
                      class="glass-input" 
                      style="font-size:12px; padding: 8px 12px; border-radius: 8px;"
                    >
                      <option value="all">Semua Kategori</option>
                      <option value="vvip">VVIP Only</option>
                      <option value="vip">VIP Only</option>
                      <option value="regular">Regular Only</option>
                    </select>
                    
                    <select 
                      v-model="guestRsvpFilter" 
                      class="glass-input" 
                      style="font-size:12px; padding: 8px 12px; border-radius: 8px;"
                    >
                      <option value="all">Semua RSVP</option>
                      <option value="pending">Pending</option>
                      <option value="attending">Hadir</option>
                      <option value="declined">Absen</option>
                    </select>
                  </div>
                  
                  <!-- Guest Items container -->
                  <div class="expenses-list" style="max-height: 480px; overflow-y: auto;">
                    <div v-if="filteredGuests.length === 0" class="empty-list-text" style="padding: 32px 0;">
                      Tidak ada data tamu yang cocok dengan filter / pencarian.
                    </div>
                    
                    <div v-for="guest in filteredGuests" :key="guest.id" class="expense-item" style="border-radius: 12px; transition: background 0.3s; margin-bottom: 8px; padding: 12px;">
                      <div class="exp-title-row" style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                          <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="exp-title" style="font-size: 14px; font-weight: 600; color: #fff;">{{ guest.name }}</span>
                            <span class="badge" :class="'guest-cat-' + guest.category" style="font-size: 9px; padding: 1px 6px; text-transform: uppercase;">
                              {{ guest.category }}
                            </span>
                            <span class="badge" :class="'guest-rsvp-' + guest.rsvp_status" style="font-size: 9px; padding: 1px 6px;">
                              {{ rsvpLabel(guest.rsvp_status) }}
                            </span>
                          </div>
                          
                          <!-- Contact Info details -->
                          <div style="display: flex; gap: 12px; font-size: 11px; color: var(--text-secondary); margin-top: 6px; flex-wrap: wrap;">
                            <span v-if="guest.email" style="display:flex; align-items:center; gap:4px">
                              <span style="opacity: 0.7">✉</span> {{ guest.email }}
                            </span>
                            <span v-if="guest.phone" style="display:flex; align-items:center; gap:4px">
                              <span style="opacity: 0.7">☎</span> {{ guest.phone }}
                            </span>
                          </div>
                        </div>
                        
                        <!-- Actions & Check-in info -->
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                          <!-- Checkin Status Badge -->
                          <div style="display: flex; align-items: center; gap: 6px;">
                            <span 
                              v-if="guest.checkin_status" 
                              class="badge badge-completed" 
                              style="font-size: 10px; display:flex; align-items:center; gap:4px; padding: 2px 8px; font-weight: 600;"
                              :title="guest.checked_in_at ? 'Waktu Check-in: ' + new Date(guest.checked_in_at).toLocaleString() : ''"
                            >
                              ✓ Hadir
                            </span>
                            <span 
                              v-else 
                              class="badge" 
                              style="background: rgba(255,255,255,0.06); color: var(--text-secondary); font-size: 10px; padding: 2px 8px;"
                            >
                              Belum Hadir
                            </span>
                          </div>
                          
                          <!-- Buttons -->
                          <div style="display: flex; gap: 6px; align-items: center;">
                            <!-- QR Scanner Simulation Trigger -->
                            <button 
                              v-if="guest.rsvp_status === 'attending'"
                              type="button" 
                              class="btn btn-glass btn-sm" 
                              @click="triggerQRScanner(guest)"
                              style="font-size: 10px; padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 4px; border-color: rgba(6,182,212,0.3); color: #67e8f9"
                              title="Pindai Tiket (Check-in)"
                            >
                              Simulasikan Scan
                            </button>
                            
                            <button 
                              type="button" 
                              class="btn btn-glass btn-sm" 
                              @click="editGuest(guest)"
                              style="font-size: 10px; padding: 3px 8px; border-radius: 6px;"
                              title="Edit Tamu"
                            >
                              Edit
                            </button>
                            
                            <button 
                              type="button" 
                              class="btn btn-glass btn-sm" 
                              @click="deleteGuest(guest.id)"
                              style="font-size: 10px; padding: 3px 8px; border-radius: 6px; border-color: rgba(239,68,68,0.2); color: #fca5a5"
                              title="Hapus Tamu"
                            >
                              Hapus
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>
            </div>
          </div>
      </div>
    </div>

    <!-- Event Logistic Return Modal -->
    <Transition name="fade">
      <div v-if="showEventReturnModal" class="modal-overlay" @click.self="closeEventReturnModal">
        <div class="modal-box" style="max-width:440px">
          <div class="modal-header">
            <h2>Proses Pengembalian Logistik</h2>
            <button class="btn btn-glass btn-sm" @click="closeEventReturnModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <form @submit.prevent="submitEventReturn">
            <div style="margin-bottom: 16px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:12px;">
              <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase;">Alat yang dikembalikan</div>
              <div style="font-weight:600; font-size:14px; margin-top:2px;">{{ eventReturnForm.item_name }}</div>
              <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Jumlah: <strong>{{ eventReturnForm.quantity }} unit</strong></div>
            </div>
            <div class="form-group">
              <label>Tanggal Pengembalian *</label>
              <input v-model="eventReturnForm.returned_at" type="date" class="glass-input" required/>
            </div>
            <div class="form-group">
              <label>Kondisi Pengembalian *</label>
              <select v-model="eventReturnForm.return_status" class="glass-input" required>
                <option value="complete">Lengkap (Baik)</option>
                <option value="incomplete">Kurang / Unit Hilang</option>
                <option value="damaged">Rusak (Broken)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Catatan Kerusakan / Kehilangan</label>
              <input v-model="eventReturnForm.notes" class="glass-input" placeholder="Keterangan kondisi barang saat dikembalikan"/>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeEventReturnModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submittingEventReturn">
                <Loader2 v-if="submittingEventReturn" :size="16" class="spinner-icon" />
                <span v-else>Konfirmasi Kembali</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Fictitious QR-Code Scan Simulator Modal -->
    <Transition name="fade">
      <div v-if="showScanModal" class="modal-overlay" @click.self="closeScanModal">
        <div class="modal-box" style="max-width: 420px; text-align: center; overflow: hidden; background: linear-gradient(180deg, #111827 0%, rgba(15, 23, 42, 0.95) 100%);">
          <div class="modal-header" style="justify-content: center; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom:14px">
            <h2 style="font-size:18px; font-weight:700; color:#fff; display:flex; align-items:center; gap:8px">
              Simulasi Check-in Tiket Tamu
            </h2>
          </div>
          
          <div class="modal-body" style="padding: 24px 16px;">
            <!-- Guest info -->
            <div style="margin-bottom: 18px;">
              <h3 style="font-size:16px; font-weight:700; color:#fff; margin-bottom:4px">{{ scanningGuest?.name }}</h3>
              <p style="font-size:12px; color:var(--text-secondary); margin:0">{{ scanningGuest?.email || scanningGuest?.phone || 'No Contact Info' }}</p>
              
              <div style="display: flex; justify-content: center; gap: 8px; margin-top: 8px;">
                <span class="badge" :class="'guest-cat-' + (scanningGuest?.category || '')" style="text-transform: uppercase; font-size:10px; padding: 2px 8px;">
                  {{ scanningGuest?.category }}
                </span>
                <span v-if="scanningGuest?.checkin_status" class="badge badge-completed" style="font-size:10px; padding: 2px 8px;">
                  Sudah Ter-checkin
                </span>
              </div>
            </div>
            
            <!-- Graphic Fictitious QR Code & Scanner visualizer -->
            <div class="qr-visualizer-container" style="position: relative; width: 180px; height: 180px; margin: 0 auto 20px auto; background: rgba(255,255,255,0.02); border: 2px solid rgba(6, 182, 212, 0.3); border-radius: 16px; display: flex; align-items: center; justify-content: center; padding: 12px; box-shadow: 0 0 25px rgba(6, 182, 212, 0.15)">
              
              <!-- Scanning moving line animation -->
              <div v-if="!scanSuccess" class="qr-scan-line" style="position: absolute; left: 0; width: 100%; height: 2px; background: #67e8f9; box-shadow: 0 0 8px #67e8f9; z-index: 10; animation: scanLineMove 2s infinite ease-in-out;"></div>
              
              <!-- Fictitious QR Code graphic using SVG -->
              <svg width="100%" height="100%" viewBox="0 0 100 100" fill="none" style="z-index: 1;">
                <rect x="0" y="0" width="22" height="22" rx="3" stroke="#67e8f9" stroke-width="3" />
                <rect x="5" y="5" width="12" height="12" rx="1.5" fill="#67e8f9" />
                
                <rect x="78" y="0" width="22" height="22" rx="3" stroke="#67e8f9" stroke-width="3" />
                <rect x="83" y="5" width="12" height="12" rx="1.5" fill="#67e8f9" />
                
                <rect x="0" y="78" width="22" height="22" rx="3" stroke="#67e8f9" stroke-width="3" />
                <rect x="5" y="83" width="12" height="12" rx="1.5" fill="#67e8f9" />
                
                <!-- QR details -->
                <path d="M 30,5 H 45 V 15 H 30 Z" fill="rgba(103, 232, 249, 0.6)" />
                <path d="M 50,5 H 70 V 10 H 50 Z" fill="rgba(103, 232, 249, 0.8)" />
                <path d="M 30,20 H 40 V 35 H 30 Z" fill="rgba(103, 232, 249, 0.5)" />
                <path d="M 45,20 H 60 V 30 H 45 Z" fill="rgba(103, 232, 249, 0.7)" />
                <path d="M 65,20 H 75 V 45 H 65 Z" fill="rgba(103, 232, 249, 0.9)" />
                
                <path d="M 0,30 H 15 V 40 H 0 Z" fill="rgba(103, 232, 249, 0.4)" />
                <path d="M 0,45 H 25 V 50 H 0 Z" fill="rgba(103, 232, 249, 0.6)" />
                <path d="M 20,35 H 35 V 45 H 20 Z" fill="rgba(103, 232, 249, 0.8)" />
                
                <path d="M 30,55 H 55 V 65 H 30 Z" fill="rgba(103, 232, 249, 0.7)" />
                <path d="M 60,55 H 70 V 70 H 60 Z" fill="rgba(103, 232, 249, 0.5)" />
                <path d="M 75,55 H 100 V 60 H 75 Z" fill="rgba(103, 232, 249, 0.8)" />
                
                <path d="M 0,55 H 10 V 70 H 0 Z" fill="rgba(103, 232, 249, 0.6)" />
                <path d="M 15,55 H 25 V 65 H 15 Z" fill="rgba(103, 232, 249, 0.4)" />
                
                <path d="M 30,75 H 40 V 100 H 30 Z" fill="rgba(103, 232, 249, 0.9)" />
                <path d="M 45,75 H 65 V 85 H 45 Z" fill="rgba(103, 232, 249, 0.7)" />
                <path d="M 70,75 H 100 V 80 H 70 Z" fill="rgba(103, 232, 249, 0.5)" />
                
                <path d="M 45,90 H 80 V 100 H 45 Z" fill="rgba(103, 232, 249, 0.8)" />
                <path d="M 85,85 H 95 V 95 H 85 Z" fill="rgba(103, 232, 249, 0.6)" />
              </svg>
            </div>
            
            <!-- Scan Actions -->
            <div v-if="!scanSuccess" style="display:flex; flex-direction:column; gap:10px; align-items:center;">
              <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                Tekan tombol di bawah untuk menyimulasikan pemindaian tiket QR Code tamu ini di lapangan.
              </p>
              
              <button 
                type="button" 
                class="btn btn-primary" 
                @click="confirmScanCheckIn"
                style="width:100%; border-radius:10px; font-weight:600; display:flex; justify-content:center; align-items:center; gap:8px;"
              >
                Pindai &amp; Check-in Tamu
              </button>
              
              <button 
                type="button" 
                class="btn btn-glass" 
                @click="closeScanModal"
                style="width:100%; border-radius:10px;"
              >
                Batal
              </button>
            </div>
            
            <!-- Scan Success Message -->
            <div v-else style="display:flex; flex-direction:column; gap:12px; align-items:center; animation: popIn 0.3s ease-out;">
              <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(16, 185, 129, 0.2); border: 2px solid #10b981; display:flex; align-items:center; justify-content:center;">
                <span style="color: #10b981; font-size: 24px; font-weight: bold;">✓</span>
              </div>
              
              <div>
                <h4 style="font-size:16px; font-weight:700; color:#10b981; margin-bottom:4px">Check-in Berhasil!</h4>
                <p style="font-size:12px; color:var(--text-secondary); margin:0;">
                  Kehadiran {{ scanningGuest?.name }} telah dicatat pada {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}.
                </p>
              </div>
              
              <button 
                type="button" 
                class="btn btn-glass" 
                @click="closeScanModal"
                style="width:100%; border-radius:10px; margin-top:8px; border-color: rgba(16,185,129,0.3); color:#10b981"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Create/Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-box" style="max-width:700px">
          <div class="modal-header">
            <h2>{{ editId ? 'Edit Event' : 'Buat Event Baru' }}</h2>
            <button class="btn btn-glass btn-sm" @click="closeModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <transition name="fade">
            <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          </transition>

          <form @submit.prevent="submitForm">
            <!-- Basic info -->
            <div class="form-group">
              <label>Nama Event *</label>
              <input v-model="form.name" class="glass-input" required placeholder="Masukkan nama event"/>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="glass-input" rows="3" placeholder="Tulis deskripsi singkat event di sini..."></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Lokasi *</label>
                <input v-model="form.location" class="glass-input" required placeholder="Masukkan lokasi event"/>
              </div>
              <div class="form-group">
                <label>Kategori</label>
                <input v-model="form.category" class="glass-input" placeholder="Masukkan kategori event (misal: Seminar)"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Mulai *</label>
                <input v-model="form.start_date" type="date" class="glass-input" required/>
              </div>
              <div class="form-group">
                <label>Tanggal Selesai *</label>
                <input v-model="form.end_date" type="date" class="glass-input" required/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jam Mulai</label>
                <input v-model="form.start_time" type="time" class="glass-input"/>
              </div>
              <div class="form-group">
                <label>Jam Selesai</label>
                <input v-model="form.end_time" type="time" class="glass-input"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Status *</label>
                <select v-model="form.status" class="glass-input" required>
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Estimasi Peserta</label>
                <input v-model.number="form.expected_participants" type="number" class="glass-input" min="0" placeholder="500"/>
              </div>
            </div>
            <div class="form-group">
              <label>Budget (Rp)</label>
              <input v-model.number="form.budget" type="number" class="glass-input" min="0" placeholder="500000000"/>
            </div>

            <!-- Personnel -->
            <div class="personnel-section">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <label style="margin-bottom:0">Tim Personel</label>
                <button type="button" class="btn btn-glass btn-sm" @click="addPersonnel">
                  <Plus :size="16" />
                  Tambah
                </button>
              </div>
              <div v-if="form.personnel.length === 0" style="color:var(--text-muted);font-size:13px;padding:12px 0">Belum ada personel ditambahkan</div>
              <div v-for="(p, idx) in form.personnel" :key="idx" class="personnel-row">
                <select v-model="p.user_id" class="glass-input" style="flex:1">
                  <option value="">Pilih User</option>
                  <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ roleLabel(u.role) }})</option>
                </select>
                <select v-model="p.role_in_event" class="glass-input" style="flex:1">
                  <option value="">Pilih Peran di Event</option>
                  <option value="Rundown Coordinator">Rundown Coordinator (Koordinator)</option>
                  <option value="Rundown PIC">Rundown PIC (Panggung/Acara)</option>
                  <option value="Event Planner">Event Planner</option>
                  <option value="Event Coordinator">Event Coordinator</option>
                  <option value="Technical Team">Technical Team</option>
                  <option value="Talent Team">Talent Team</option>
                  <option value="Logistics Team">Logistics Team</option>
                  <option value="Staff">Staff (Staf Umum)</option>
                </select>
                <button type="button" class="btn btn-danger btn-sm" @click="removePersonnel(idx)" title="Hapus">
                  <X :size="16" />
                </button>
              </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>{{ editId ? 'Simpan Perubahan' : 'Buat Event' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Delete confirm -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width:400px;text-align:center">
          <div style="margin-bottom:16px">
            <AlertCircle :size="48" style="margin:0 auto; color: #f59e0b;" />
          </div>
          <h2 style="margin-bottom:12px">Hapus Event?</h2>
          <p style="color:var(--text-secondary);margin-bottom:24px">Event <strong>{{ deleteTarget.name }}</strong> akan dihapus permanen beserta data personelnya.</p>
          <div style="display:flex;gap:10px;justify-content:center">
            <button class="btn btn-glass" @click="deleteTarget = null">Batal</button>
            <button class="btn btn-danger" @click="doDelete" :disabled="submitting">
              <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
              <span v-else>Ya, Hapus</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Plus, Calendar, Edit, Trash2, ChevronLeft, ChevronRight, X, MapPin, Clock, DollarSign, Users, User, AlertCircle, Loader2, CheckSquare, ArrowDownLeft, Package, FileBarChart2, Printer, ArrowLeft } from 'lucide-vue-next'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const router = useRouter()
const events = ref([])
const loading = ref(true)
const search = ref('')
const filterStatus = ref('')
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10 })
const availableUsers = ref([])

const showModal = ref(false)
const editId = ref(null)
const form = ref(defaultForm())
const formError = ref('')
const submitting = ref(false)
const deleteTarget = ref(null)
const detailEvent = ref(null)
const eventTaskCounts = ref({})
const eventTasks = ref([])
const eventTaskStats = ref(null)
const loadingEventTasks = ref(false)

// Guest RSVP and Scan Simulation ref states
const guestsData = ref([])
const loadingGuests = ref(false)
const guestForm = ref(defaultGuestForm())
const isEditingGuest = ref(false)
const showScanModal = ref(false)
const scanningGuest = ref(null)
const scanSuccess = ref(false)
const guestSearch = ref('')
const guestCategoryFilter = ref('all')
const guestRsvpFilter = ref('all')

function defaultGuestForm() {
  return {
    id: null,
    name: '',
    email: '',
    phone: '',
    category: 'regular',
    rsvp_status: 'pending'
  }
}

const statuses = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Aktif' },
  { value: 'ongoing', label: 'Berlangsung' },
  { value: 'completed', label: 'Selesai' },
  { value: 'cancelled', label: 'Dibatalkan' },
]

const roleLabels = {
  superadmin: 'Super Admin', project_manager: 'Project Manager', staff: 'Staff / Personnel',
  event_planner: 'Event Planner', promotion_team: 'Promotion Team', partnership_manager: 'Partnership Manager',
  budgeting: 'Budgeting', operations_team: 'Operations Team', creative_team: 'Creative Team',
  rundown_coordinator: 'Rundown Coordinator', talent_coordinator: 'Talent Coordinator',
  registration_guest_management: 'Registration & Guest Mgmt', technical_team: 'Technical Team',
  documentation_team: 'Documentation Team', liaison_officer: 'Liaison Officer',
}
function roleLabel(r) { return roleLabels[r] || r }
function statusLabel2(s) {
  const map = { pending: 'Pending', in_progress: 'Progress', review: 'Review', completed: 'Selesai', cancelled: 'Batal' }
  return map[s] || s
}

function statusLabel(s) { return statuses.find(x => x.value === s)?.label || s }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }
function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v) }
function normalizeTimeInput(v) {
  if (!v) return ''
  return String(v).substring(0, 5)
}

function defaultForm() {
  return { name: '', description: '', location: '', start_date: '', end_date: '', start_time: '', end_time: '', status: 'draft', budget: '', category: '', expected_participants: '', personnel: [] }
}

async function fetchEvents(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/events', { params: { page, search: search.value, status: filterStatus.value } })
    events.value = res.data.data
    pagination.value = { current_page: res.data.current_page, last_page: res.data.last_page, per_page: res.data.per_page }
    // prefetch task counts for visible events
    res.data.data.forEach(ev => {
      if (!(ev.id in eventTaskCounts.value)) {
        api.get(`/events/${ev.id}/tasks`).then(r => {
          eventTaskCounts.value[ev.id] = r.data.stats?.total ?? 0
        }).catch(() => { eventTaskCounts.value[ev.id] = 0 })
      }
    })
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal memuat data event.'
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  const res = await api.get('/users-list')
  availableUsers.value = res.data
}

async function openDetail(ev) {
  activeTab.value = 'info'
  detailEvent.value = ev
  eventTasks.value = []
  eventTaskStats.value = null
  loadingEventTasks.value = true
  try {
    const res = await api.get(`/events/${ev.id}/tasks`)
    eventTasks.value = res.data.tasks || []
    eventTaskStats.value = res.data.stats || null
    eventTaskCounts.value[ev.id] = res.data.stats?.total ?? 0
  } finally {
    loadingEventTasks.value = false
  }
}

function openCreate() {
  editId.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}
function openEdit(ev) {
  editId.value = ev.id
  form.value = {
    name: ev.name, description: ev.description || '', location: ev.location,
    start_date: ev.start_date?.substring(0, 10) || '', end_date: ev.end_date?.substring(0, 10) || '',
    start_time: normalizeTimeInput(ev.start_time), end_time: normalizeTimeInput(ev.end_time),
    status: ev.status, budget: ev.budget || '', category: ev.category || '',
    expected_participants: ev.expected_participants || '',
    personnel: ev.personnel?.map(p => ({ user_id: p.id, role_in_event: p.pivot?.role_in_event || '' })) || [],
  }
  formError.value = ''
  showModal.value = true
}
function closeModal() { showModal.value = false }

function addPersonnel() { form.value.personnel.push({ user_id: '', role_in_event: '' }) }
function removePersonnel(idx) { form.value.personnel.splice(idx, 1) }

async function submitForm() {
  formError.value = ''
  submitting.value = true
  const payload = { ...form.value }
  payload.start_time = normalizeTimeInput(payload.start_time)
  payload.end_time = normalizeTimeInput(payload.end_time)
  if (!payload.budget) delete payload.budget
  if (!payload.expected_participants) delete payload.expected_participants
  if (!payload.start_time) delete payload.start_time
  if (!payload.end_time) delete payload.end_time

  const selectedIds = payload.personnel.filter(p => p.user_id).map(p => String(p.user_id))
  if (new Set(selectedIds).size !== selectedIds.length) {
    formError.value = 'User personel tidak boleh duplikat dalam satu event.'
    submitting.value = false
    return
  }

  payload.personnel = payload.personnel.filter(p => p.user_id)
  try {
    if (editId.value) {
      await api.put(`/events/${editId.value}`, payload)
    } else {
      await api.post('/events', payload)
    }
    closeModal()
    fetchEvents(pagination.value.current_page)
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) formError.value = Object.values(errs).flat().join(' ')
    else formError.value = e.response?.data?.message || 'Terjadi kesalahan.'
  } finally {
    submitting.value = false
  }
}

function confirmDelete(ev) { deleteTarget.value = ev }
async function doDelete() {
  submitting.value = true
  try {
    await api.delete(`/events/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchEvents(pagination.value.current_page)
  } catch (e) {
    const message = e.response?.data?.message || 'Gagal menghapus event.'
    formError.value = message
    window.alert(message)
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchEvents()
  if (auth.canManageEvents) loadUsers()
})

// Close all modals when navigating away
watch(() => router.currentRoute.value.path, () => {
  detailEvent.value = null
  showModal.value = false
  deleteTarget.value = null
  cancelAllocationForm()
  cancelExpenseForm()
  cancelLogisticForm()
  closeEventReturnModal()
  cancelReportForm()
})

const activeTab = ref('info')
const budgetData = ref({
  event_budget: 0,
  total_allocated: 0,
  total_spent: 0,
  remaining_budget: 0,
  allocations: [],
  expenses: []
})
const loadingBudget = ref(false)

const budgetCategories = [
  'Konsumsi',
  'Sound & Lighting',
  'Venue / Akomodasi',
  'Dekorasi & Stage',
  'Talent & MC',
  'Publikasi & Dok',
  'Logistik & Ops',
  'Lain-lain'
]

// Allocation Form State
const showAllocationForm = ref(false)
const submittingAllocation = ref(false)
const allocationForm = ref({
  category: '',
  allocated_amount: '',
  notes: ''
})

// Expense Form State
const showExpenseForm = ref(false)
const submittingExpense = ref(false)
const expenseForm = ref({
  title: '',
  category: '',
  amount: '',
  vendor_name: '',
  payment_method: 'cash',
  payment_status: 'paid',
  spent_at: new Date().toISOString().substring(0, 10),
  notes: ''
})

const budgetPercent = computed(() => {
  if (!budgetData.value.event_budget) return 0
  return Math.round((budgetData.value.total_spent / budgetData.value.event_budget) * 100)
})

function getAllocSpent(alloc) {
  const matching = budgetData.value.expenses.filter(e => e.category === alloc.category)
  return matching.reduce((sum, e) => sum + Number(e.amount), 0)
}

function getAllocPercent(alloc) {
  if (!alloc.allocated_amount) return 0
  const spent = getAllocSpent(alloc)
  return Math.round((spent / alloc.allocated_amount) * 100)
}

function formatDateShort(d) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '—'
}

async function openBudgetTab() {
  activeTab.value = 'budget'
  cancelAllocationForm()
  cancelExpenseForm()
  await fetchBudget()
}

async function fetchBudget() {
  if (!detailEvent.value) return
  loadingBudget.value = true
  try {
    const res = await api.get(`/events/${detailEvent.value.id}/budget`)
    budgetData.value = res.data
  } catch (e) {
    console.error('Failed to fetch budget:', e)
  } finally {
    loadingBudget.value = false
  }
}

function cancelAllocationForm() {
  showAllocationForm.value = false
  allocationForm.value = { category: '', allocated_amount: '', notes: '' }
}

function cancelExpenseForm() {
  showExpenseForm.value = false
  expenseForm.value = {
    title: '',
    category: '',
    amount: '',
    vendor_name: '',
    payment_method: 'cash',
    payment_status: 'paid',
    spent_at: new Date().toISOString().substring(0, 10),
    notes: ''
  }
}

async function submitAllocation() {
  if (!allocationForm.value.category || !allocationForm.value.allocated_amount) return
  submittingAllocation.value = true
  try {
    await api.post(`/events/${detailEvent.value.id}/budget/allocations`, allocationForm.value)
    cancelAllocationForm()
    await fetchBudget()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menyimpan alokasi.')
  } finally {
    submittingAllocation.value = false
  }
}

async function deleteAllocation(alloc) {
  if (!window.confirm(`Hapus alokasi anggaran kategori ${alloc.category}?`)) return
  try {
    await api.delete(`/events/${detailEvent.value.id}/budget/allocations/${alloc.id}`)
    await fetchBudget()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menghapus alokasi.')
  }
}

async function submitExpense() {
  if (!expenseForm.value.title || !expenseForm.value.category || !expenseForm.value.amount) return
  submittingExpense.value = true
  try {
    await api.post(`/events/${detailEvent.value.id}/budget/expenses`, expenseForm.value)
    cancelExpenseForm()
    await fetchBudget()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal mencatat transaksi.')
  } finally {
    submittingExpense.value = false
  }
}

async function deleteExpense(exp) {
  if (!window.confirm(`Hapus transaksi ${exp.title} sebesar ${formatCurrency(exp.amount)}?`)) return
  try {
    await api.delete(`/events/${detailEvent.value.id}/budget/expenses/${exp.id}`)
    await fetchBudget()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menghapus transaksi.')
  }
}

// =========================================================================
// EVENT LOGISTICS & WAREHOUSE INTEGRATION
// =========================================================================
const logisticData = ref([])
const loadingLogistic = ref(false)
const availableWarehouseItems = ref([])
const submittingLogistic = ref(false)

const logisticForm = ref({
  inventory_id: '',
  user_id: '',
  quantity: 1,
  borrowed_at: new Date().toISOString().substring(0, 10),
  notes: ''
})

const showEventReturnModal = ref(false)
const submittingEventReturn = ref(false)
const eventReturnForm = ref({
  id: null,
  item_name: '',
  quantity: 0,
  returned_at: new Date().toISOString().substring(0, 10),
  return_status: 'complete',
  notes: ''
})

const selectedInventory = computed(() => {
  if (!logisticForm.value.inventory_id) return null
  return availableWarehouseItems.value.find(item => item.id === logisticForm.value.inventory_id) || null
})

const rentDays = computed(() => {
  if (!logisticForm.value.borrowed_at || !detailEvent.value?.end_date) return 0
  const start = new Date(logisticForm.value.borrowed_at)
  start.setHours(0,0,0,0)
  const end = new Date(detailEvent.value.end_date.substring(0, 10))
  end.setHours(0,0,0,0)
  const diffTime = end.getTime() - start.getTime()
  if (isNaN(diffTime)) return 0
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1
  return Math.max(1, diffDays)
})

const estimatedRentCost = computed(() => {
  const inv = selectedInventory.value
  if (!inv || inv.ownership !== 'rented' || !inv.default_rent_price) return 0
  const qty = Number(logisticForm.value.quantity) || 0
  const days = rentDays.value
  return inv.default_rent_price * qty * days
})

async function openLogisticTab() {
  activeTab.value = 'logistic'
  cancelLogisticForm()
  closeEventReturnModal()
  await Promise.all([
    fetchLogistic(),
    fetchAvailableWarehouse()
  ])
}

async function fetchLogistic() {
  if (!detailEvent.value) return
  loadingLogistic.value = true
  try {
    const res = await api.get(`/events/${detailEvent.value.id}/logistics`)
    logisticData.value = res.data
  } catch (e) {
    console.error('Failed to fetch event logistics:', e)
  } finally {
    loadingLogistic.value = false
  }
}

async function fetchAvailableWarehouse() {
  try {
    const res = await api.get('/inventories', { params: { status: 'ready' } })
    availableWarehouseItems.value = res.data.filter(item => item.available_quantity > 0)
  } catch (e) {
    console.error('Failed to fetch warehouse items:', e)
  }
}

function cancelLogisticForm() {
  logisticForm.value = {
    inventory_id: '',
    user_id: '',
    quantity: 1,
    borrowed_at: new Date().toISOString().substring(0, 10),
    notes: ''
  }
}

async function submitLogistic() {
  if (!logisticForm.value.inventory_id || !logisticForm.value.user_id || !logisticForm.value.quantity || !logisticForm.value.borrowed_at) return
  submittingLogistic.value = true
  try {
    await api.post(`/events/${detailEvent.value.id}/logistics`, logisticForm.value)
    cancelLogisticForm()
    await Promise.all([
      fetchLogistic(),
      fetchAvailableWarehouse()
    ])
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal mengalokasikan logistik.')
  } finally {
    submittingLogistic.value = false
  }
}

function openEventReturn(logi) {
  eventReturnForm.value = {
    id: logi.id,
    item_name: logi.inventory?.item_name || 'Alat',
    quantity: logi.quantity,
    returned_at: new Date().toISOString().substring(0, 10),
    return_status: 'complete',
    notes: ''
  }
  showEventReturnModal.value = true
}

function closeEventReturnModal() {
  showEventReturnModal.value = false
  eventReturnForm.value = {
    id: null,
    item_name: '',
    quantity: 0,
    returned_at: new Date().toISOString().substring(0, 10),
    return_status: 'complete',
    notes: ''
  }
}

async function submitEventReturn() {
  if (!eventReturnForm.value.id || !eventReturnForm.value.returned_at || !eventReturnForm.value.return_status) return
  submittingEventReturn.value = true
  try {
    const logiId = eventReturnForm.value.id
    const payload = {
      returned_at: eventReturnForm.value.returned_at,
      return_status: eventReturnForm.value.return_status,
      notes: eventReturnForm.value.notes
    }
    await api.post(`/events/${detailEvent.value.id}/logistics/${logiId}/return`, payload)
    closeEventReturnModal()
    await Promise.all([
      fetchLogistic(),
      fetchAvailableWarehouse()
    ])
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal memproses pengembalian.')
  } finally {
    submittingEventReturn.value = false
  }
}

async function deleteLogistic(logi) {
  const isRented = logi.inventory?.ownership === 'rented'
  const confirmMsg = isRented 
    ? `Batalkan alokasi ${logi.inventory?.item_name}? Ini juga akan menghapus biaya sewa otomatis dari anggaran.`
    : `Batalkan alokasi ${logi.inventory?.item_name}?`
  if (!window.confirm(confirmMsg)) return
  try {
    await api.delete(`/events/${detailEvent.value.id}/logistics/${logi.id}`)
    await Promise.all([
      fetchLogistic(),
      fetchAvailableWarehouse()
    ])
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal membatalkan alokasi.')
  }
}

// =========================================================================
// EVENT EVALUATION REPORT INTEGRATION
// =========================================================================
const loadingReport = ref(false)
const reportData = ref({
  is_finalized: false,
  report: null,
  preview: null
})
const submittingReport = ref(false)
const reportForm = ref({
  evaluation_notes: '',
  recommendations: ''
})

async function openReportTab() {
  activeTab.value = 'report'
  cancelReportForm()
  await fetchReport()
}

async function fetchReport() {
  if (!detailEvent.value) return
  loadingReport.value = true
  try {
    const res = await api.get(`/events/${detailEvent.value.id}/report`)
    reportData.value = res.data
    if (res.data.is_finalized) {
      reportForm.value.evaluation_notes = res.data.report.evaluation_notes
      reportForm.value.recommendations = res.data.report.recommendations
    } else {
      reportForm.value.evaluation_notes = ''
      reportForm.value.recommendations = ''
    }
  } catch (e) {
    console.error('Failed to fetch event report:', e)
  } finally {
    loadingReport.value = false
  }
}

async function openGuestsTab() {
  activeTab.value = 'guests'
  resetGuestForm()
  await fetchGuests()
}

async function fetchGuests() {
  if (!detailEvent.value) return
  loadingGuests.value = true
  try {
    const res = await api.get(`/events/${detailEvent.value.id}/guests`)
    guestsData.value = res.data
  } catch (e) {
    console.error('Failed to fetch guests:', e)
  } finally {
    loadingGuests.value = false
  }
}

function resetGuestForm() {
  guestForm.value = defaultGuestForm()
  isEditingGuest.value = false
}

async function saveGuest() {
  if (!detailEvent.value) return
  try {
    if (isEditingGuest.value) {
      const res = await api.put(`/events/${detailEvent.value.id}/guests/${guestForm.value.id}`, {
        name: guestForm.value.name,
        email: guestForm.value.email || null,
        phone: guestForm.value.phone || null,
        category: guestForm.value.category,
        rsvp_status: guestForm.value.rsvp_status,
      })
      const idx = guestsData.value.findIndex(g => g.id === guestForm.value.id)
      if (idx !== -1) guestsData.value[idx] = res.data
      alert('Tamu berhasil diperbarui!')
    } else {
      const res = await api.post(`/events/${detailEvent.value.id}/guests`, {
        name: guestForm.value.name,
        email: guestForm.value.email || null,
        phone: guestForm.value.phone || null,
        category: guestForm.value.category,
        rsvp_status: guestForm.value.rsvp_status,
      })
      guestsData.value.push(res.data)
      alert('Tamu berhasil didaftarkan!')
    }
    resetGuestForm()
  } catch (e) {
    console.error('Failed to save guest:', e)
    alert('Gagal menyimpan data tamu. Periksa kembali inputan Anda.')
  }
}

function editGuest(guest) {
  isEditingGuest.value = true
  guestForm.value = {
    id: guest.id,
    name: guest.name,
    email: guest.email || '',
    phone: guest.phone || '',
    category: guest.category,
    rsvp_status: guest.rsvp_status
  }
}

async function deleteGuest(guestId) {
  if (!confirm('Apakah Anda yakin ingin menghapus tamu ini dari daftar undangan?')) return
  try {
    await api.delete(`/events/${detailEvent.value.id}/guests/${guestId}`)
    guestsData.value = guestsData.value.filter(g => g.id !== guestId)
    alert('Tamu berhasil dihapus.')
  } catch (e) {
    console.error('Failed to delete guest:', e)
    alert('Gagal menghapus tamu.')
  }
}

function triggerQRScanner(guest) {
  scanningGuest.value = guest
  scanSuccess.value = false
  showScanModal.value = true
}

function closeScanModal() {
  showScanModal.value = false
  scanningGuest.value = null
  scanSuccess.value = false
}

async function confirmScanCheckIn() {
  if (!scanningGuest.value || !detailEvent.value) return
  try {
    const res = await api.patch(`/events/${detailEvent.value.id}/guests/${scanningGuest.value.id}/checkin`, {
      checkin_status: true
    })
    
    const idx = guestsData.value.findIndex(g => g.id === scanningGuest.value.id)
    if (idx !== -1) {
      guestsData.value[idx] = res.data
    }
    scanSuccess.value = true
  } catch (e) {
    console.error('Failed to checkin guest:', e)
    alert('Gagal menyimulasikan check-in.')
  }
}

function rsvpLabel(status) {
  const map = {
    pending: 'Pending',
    attending: 'Hadir',
    declined: 'Absen'
  }
  return map[status] || status
}

const filteredGuests = computed(() => {
  return guestsData.value.filter(guest => {
    const matchesSearch = guest.name.toLowerCase().includes(guestSearch.value.toLowerCase())
    const matchesCategory = guestCategoryFilter.value === 'all' || guest.category === guestCategoryFilter.value
    const matchesRsvp = guestRsvpFilter.value === 'all' || guest.rsvp_status === guestRsvpFilter.value
    return matchesSearch && matchesCategory && matchesRsvp
  })
})

const guestsStats = computed(() => {
  const attending = guestsData.value.filter(g => g.rsvp_status === 'attending').length
  const checkedIn = guestsData.value.filter(g => g.checkin_status).length
  return { attending, checkedIn }
})

function cancelReportForm() {
  reportForm.value = {
    evaluation_notes: '',
    recommendations: ''
  }
}

async function submitReport() {
  if (!reportForm.value.evaluation_notes || !reportForm.value.recommendations) return
  submittingReport.value = true
  try {
    await api.post(`/events/${detailEvent.value.id}/report`, reportForm.value)
    cancelReportForm()
    await fetchReport()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menerbitkan laporan evaluasi.')
  } finally {
    submittingReport.value = false
  }
}

function editFinalReport() {
  // Turn finalize view back into form view filled with current notes
  reportData.value.is_finalized = false
}

async function deleteReport() {
  if (!window.confirm('Hapus laporan evaluasi ini secara permanen? Data metrik historis akan ikut terhapus.')) return
  try {
    await api.delete(`/events/${detailEvent.value.id}/report`)
    cancelReportForm()
    await fetchReport()
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menghapus laporan evaluasi.')
  }
}

function printReport() {
  window.print()
}
</script>

<style scoped>
.events-page { display: flex; flex-direction: column; gap: 16px; }
.page-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; }
.header-left h2 { font-size: 18px; font-weight: 600; }
.header-left p { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }
.filters { display: flex; gap: 12px; padding: 16px 20px; flex-wrap: wrap; align-items: center; }
.table-container { padding: 0 0 20px; }
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 48px; color: var(--text-secondary); }

.clickable-row { cursor: pointer; }

.event-cell { display: flex; align-items: center; gap: 10px; }
.event-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.icon-active { background: rgba(16,185,129,0.2); }
.icon-draft { background: rgba(107,114,128,0.2); }
.icon-ongoing { background: rgba(6,182,212,0.2); }
.icon-completed { background: rgba(14, 165, 233, 0.2); }
.icon-cancelled { background: rgba(239,68,68,0.2); }

.personnel-avatars { display: flex; gap: -4px; }
.p-avatar {
  width: 26px; height: 26px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700;
  border: 2px solid rgba(15,23,42,0.8);
  margin-left: -4px;
}
.p-avatar:first-child { margin-left: 0; }
.p-avatar.more { background: rgba(255,255,255,0.15); font-size: 9px; }

/* Detail */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
.detail-badge-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.category-tag { display: inline-block; padding: 3px 10px; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); border-radius: 20px; font-size: 11px; color: #67e8f9; }
.detail-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
.detail-desc { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.6; }
.detail-meta-list { display: flex; flex-direction: column; gap: 8px; }
.dm-item { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: var(--text-secondary); }
.personnel-list { display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto; }
.personnel-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.05); border-radius: 10px; }
.p-avatar-lg { width: 34px; height: 34px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }

/* Personnel form */
.personnel-section { background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 14px; padding: 16px; margin-bottom: 4px; }
.personnel-row { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }

/* Task integration */
.task-count-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); border-radius: 20px; color: #38bdf8; font-size: 12px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
.task-count-badge:hover { background: rgba(14,165,233,0.3); }
.task-stats-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.tstat { display: flex; flex-direction: column; align-items: center; gap: 2px; background: rgba(255,255,255,0.05); border-radius: 10px; padding: 8px 14px; min-width: 60px; }
.tstat-val { font-size: 20px; font-weight: 700; }
.tstat span:last-child { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
.task-progress-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.task-progress-bar { flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
.task-progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #10b981); border-radius: 3px; transition: width 0.5s; }
.event-task-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 8px; background: rgba(255,255,255,0.04); margin-bottom: 6px; }
.priority-dot-sm { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.pdot-urgent { background: #ef4444; }
.pdot-high   { background: #f59e0b; }
.pdot-medium { background: var(--primary); }
.pdot-low    { background: #6b7280; }
.event-task-title { flex: 1; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.event-task-status { font-size: 10px; font-weight: 700; padding: 2px 7px; }
.task-badge-pending    { background:rgba(107,114,128,0.2); color:#d1d5db; }
.task-badge-in_progress{ background:rgba(6,182,212,0.2);  color:#67e8f9; }
.task-badge-review     { background:rgba(245,158,11,0.2); color:#fcd34d; }
.task-badge-completed  { background:rgba(16,185,129,0.2); color:#6ee7b7; }
.task-badge-cancelled  { background:rgba(239,68,68,0.2);  color:#fca5a5; }

/* Detail Page Container (Full Page View) */
.detail-page-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
}
.detail-card-content {
  width: 100%;
  box-sizing: border-box;
}

/* Multi-Tab Styling */
.modal-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  padding-bottom: 8px;
  margin-bottom: 16px;
}
.tab-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 500;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}
.tab-btn:hover {
  color: var(--text-primary);
  background: rgba(255,255,255,0.04);
}
.tab-btn.active {
  color: #fff;
  background: linear-gradient(135deg, rgba(14,165,233,0.25), rgba(13,148,136,0.25));
  border: 1px solid rgba(14,165,233,0.3);
  font-weight: 600;
}

/* Financial summary cards */
.financial-summary-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}
@media (max-width: 600px) {
  .financial-summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}
.fin-card {
  display: flex;
  flex-direction: column;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px;
  padding: 10px 14px;
}
.fin-label {
  font-size: 10px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.fin-val {
  font-size: 15px;
  font-weight: 700;
  margin-top: 4px;
  color: #fff;
}

/* Progress bar */
.budget-progress-section {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 20px;
}
.bp-header {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
  margin-bottom: 6px;
}
.bp-track {
  height: 8px;
  background: rgba(255,255,255,0.08);
  border-radius: 4px;
  overflow: hidden;
}
.bp-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), #10b981);
  border-radius: 4px;
  transition: width 0.4s;
}
.bp-fill.bp-over {
  background: linear-gradient(90deg, #ef4444, #f43f5e);
}
.bp-warning-text {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #f43f5e;
  margin-top: 6px;
  font-weight: 500;
}

/* Split Pane Layout */
.budget-split-pane {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 600px) {
  .budget-split-pane {
    grid-template-columns: 1fr;
  }
}
.budget-col {
  display: flex;
  flex-direction: column;
}
.col-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding-bottom: 6px;
}
.col-title-row h4 {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
}

/* Lists */
.allocations-list, .expenses-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 280px;
  overflow-y: auto;
  padding-right: 4px;
}
.empty-list-text {
  color: var(--text-muted);
  font-size: 12px;
  padding: 24px 0;
  text-align: center;
}
.alloc-item, .expense-item {
  position: relative;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 10px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  transition: background 0.15s;
}
.alloc-item:hover, .expense-item:hover {
  background: rgba(255,255,255,0.05);
}
.alloc-info-row, .exp-title-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}
.alloc-cat, .exp-title {
  font-size: 12px;
  font-weight: 600;
  color: #e0e7ff;
}
.alloc-amt, .exp-amt {
  font-size: 12px;
  font-weight: 700;
  color: #fff;
}
.alloc-progress-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 4px;
}
.alloc-bar-track {
  height: 4px;
  background: rgba(255,255,255,0.08);
  border-radius: 2px;
  overflow: hidden;
}
.alloc-bar-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 2px;
  transition: width 0.3s;
}
.alloc-bar-fill.over {
  background: #ef4444;
}
.alloc-percent {
  font-size: 10px;
  color: var(--text-muted);
}
.alloc-notes, .exp-notes {
  font-size: 10px;
  color: var(--text-muted);
  font-style: italic;
  margin-top: 2px;
}
.delete-alloc-btn, .delete-exp-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  opacity: 0;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.alloc-item:hover .delete-alloc-btn, .expense-item:hover .delete-exp-btn {
  opacity: 1;
}
.delete-alloc-btn:hover, .delete-exp-btn:hover {
  color: #ef4444;
  background: rgba(239,68,68,0.1);
}

.exp-meta-row, .exp-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 10px;
  color: var(--text-muted);
}
.exp-date, .exp-user {
  font-size: 10px;
}

/* Mini Forms */
.mini-form {
  background: rgba(255,255,255,0.05) !important;
  border: 1px solid rgba(14,165,233,0.2) !important;
  padding: 12px !important;
  margin-bottom: 14px;
}
.form-group-sm {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 8px;
}
.form-group-sm label {
  font-size: 10px;
  font-weight: 600;
  color: var(--text-secondary);
}
.glass-input-sm {
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--glass-border);
  border-radius: 6px;
  padding: 6px 10px;
  color: #fff;
  font-size: 11px;
  outline: none;
  width: 100%;
}
.glass-input-sm:focus {
  border-color: var(--primary);
}
.form-row-sm {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 8px;
}
.form-actions-sm {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
  margin-top: 10px;
}
.btn-xs {
  font-size: 10px;
  padding: 4px 8px;
  border-radius: 4px;
}
.btn-xs.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border: none;
  color: #fff;
}

.badge-owned {
  background: rgba(14,165,233,0.15);
  border: 1px solid rgba(14,165,233,0.3);
  color: #38bdf8;
}
.badge-rented {
  background: rgba(236,72,153,0.15);
  border: 1px solid rgba(236,72,153,0.3);
  color: #f9a8d4;
}

/* =========================================================================
   CIRCULAR PROGRESS BAR & REPORT VIEW STYLING
   ========================================================================= */
.rm-progress-circle-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
}
.circle-progress {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.circle-val {
  font-size: 14px;
  font-weight: 700;
  color: #fff;
}

.rm-body-column {
  display: flex;
  flex-direction: column;
}

.report-alert-info {
  display: flex;
  gap: 12px;
  padding: 14px;
  border: 1px solid rgba(14, 165, 233, 0.25);
  background: rgba(14, 165, 233, 0.06);
  border-radius: 12px;
}

/* Printable Report Sheet Layout */
.report-sheet {
  background: rgba(255,255,255,0.02) !important;
  border: 1px solid rgba(255,255,255,0.08) !important;
  padding: 24px !important;
  border-radius: 16px;
  color: #f8fafc;
}

.rs-notes-text {
  font-size: 12.5px;
  line-height: 1.6;
  color: #e2e8f0;
  white-space: pre-wrap;
}

/* Guest Badge Styles & Custom Modals */
.guest-cat-vvip {
  background: linear-gradient(135deg, #ef4444, #f59e0b) !important;
  color: #fff !important;
  font-weight: 700;
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.3);
}
.guest-cat-vip {
  background: linear-gradient(135deg, #a855f7, #ec4899) !important;
  color: #fff !important;
  font-weight: 700;
  box-shadow: 0 0 6px rgba(168, 85, 247, 0.3);
}
.guest-cat-regular {
  background: rgba(6, 182, 212, 0.12) !important;
  border: 1px solid rgba(6, 182, 212, 0.25) !important;
  color: #67e8f9 !important;
}
.guest-rsvp-pending {
  background: rgba(255, 255, 255, 0.06) !important;
  color: var(--text-secondary) !important;
}
.guest-rsvp-attending {
  background: rgba(16, 185, 129, 0.12) !important;
  border: 1px solid rgba(16, 185, 129, 0.25) !important;
  color: #34d399 !important;
}
.guest-rsvp-declined {
  background: rgba(239, 68, 68, 0.12) !important;
  border: 1px solid rgba(239, 68, 68, 0.25) !important;
  color: #fca5a5 !important;
}

/* Animations for QR and checks */
@keyframes scanLineMove {
  0% { top: 2%; }
  50% { top: 96%; }
  100% { top: 2%; }
}
@keyframes popIn {
  0% { transform: scale(0.92); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

/* =========================================================================
   PROFESSIONAL PRINT MEDIA RULES
   ========================================================================= */
@media print {
  /* Hide all dashboard/modal wraps and show only report-sheet in full width */
  body * {
    visibility: hidden;
  }
  
  #printable-report-sheet, #printable-report-sheet * {
    visibility: visible;
  }
  
  #printable-report-sheet {
    position: fixed;
    left: 0;
    top: 0;
    width: 100vw !important;
    height: 100vh !important;
    background: #ffffff !important;
    color: #000000 !important;
    padding: 30px !important;
    border: none !important;
    box-shadow: none !important;
    margin: 0 !important;
    border-radius: 0 !important;
  }
  
  #printable-report-sheet strong,
  #printable-report-sheet span,
  #printable-report-sheet p,
  #printable-report-sheet div,
  #printable-report-sheet h2,
  #printable-report-sheet h3,
  #printable-report-sheet th,
  #printable-report-sheet td {
    color: #000000 !important;
    background: transparent !important;
    text-shadow: none !important;
    box-shadow: none !important;
  }
  
  .financial-summary-cards {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 15px !important;
    margin-bottom: 30px !important;
    width: 100% !important;
  }
  
  .fin-card {
    border: 1px solid #cccccc !important;
    background: #fcfcfc !important;
    padding: 10px !important;
    border-radius: 8px !important;
    display: flex !important;
    flex-direction: column !important;
  }
  
  .fin-label {
    color: #555555 !important;
    font-size: 10px !important;
  }
  
  .fin-val {
    color: #000000 !important;
    font-size: 16px !important;
    font-weight: 700 !important;
  }
  
  .budget-split-pane {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 30px !important;
    width: 100% !important;
  }
  
  .rs-metric-detail-box {
    border: 1px solid #dddddd !important;
    background: #fafafa !important;
    padding: 12px !important;
    border-radius: 8px !important;
  }
  
  .bp-track {
    background: #eeeeee !important;
    border: 1px solid #cccccc !important;
  }
  
  .bp-fill {
    background: #000000 !important;
  }
  
  .rs-pm-notes-box {
    border: 1px solid #cccccc !important;
    background: #fafafa !important;
    padding: 15px !important;
    border-radius: 8px !important;
    margin-top: 10px !important;
  }
  
  .rs-brand-section, .rs-footer {
    border-top-color: #dddddd !important;
    border-bottom-color: #dddddd !important;
  }
  
  .rs-event-title {
    -webkit-text-fill-color: #000000 !important;
    color: #000000 !important;
    background: none !important;
  }

  .rs-brand-icon {
    display: none !important;
  }
  
  /* Avoid table layout breaks */
  tr, td, th {
    page-break-inside: avoid !important;
  }
}
</style>
