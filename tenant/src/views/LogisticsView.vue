<template>
  <div class="logistics-page">
    <!-- Modal Tabs -->
    <div class="modal-tabs glass-card" style="padding: 12px 20px; margin-bottom: 0;">
      <button class="tab-btn" :class="{ active: activeTab === 'warehouse' }" @click="switchTab('warehouse')">
        <Boxes :size="16" style="margin-right: 6px;" />
        Katalog Gudang Global
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'distribution' }" @click="switchTab('distribution')">
        <Truck :size="16" style="margin-right: 6px;" />
        Distribusi Alat Aktif
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'status_actions' }" @click="switchTab('status_actions')">
        <AlertCircle :size="16" style="margin-right: 6px;" />
        Status Alat Bermasalah
      </button>
      <button v-if="auth.canManageEvents" class="btn btn-primary" @click="openCreate" style="margin-left: auto;">
        <Plus :size="18" />
        Tambah Aset Baru
      </button>
    </div>

    <!-- Tab 1: Catalogue Warehouse -->
    <template v-if="activeTab === 'warehouse'">
      <!-- Filters -->
      <div class="filters glass-card">
        <input v-model="search" class="glass-input" placeholder="Cari berdasarkan nama atau kode barang..." @input="fetchInventory" style="max-width:320px"/>
        <select v-model="filterStatus" class="glass-input" @change="fetchInventory" style="max-width:180px">
          <option value="">Semua Kondisi</option>
          <option value="ready">Ready (Baik)</option>
          <option value="maintenance">Maintenance</option>
          <option value="damaged">Rusak (Broken)</option>
        </select>
        <select v-model="filterOwnership" class="glass-input" @change="fetchInventory" style="max-width:180px">
          <option value="">Semua Kepemilikan</option>
          <option value="owned">Milik Sendiri (Owned)</option>
          <option value="rented">Sewa (Rented)</option>
        </select>
      </div>

      <!-- Warehouse Table -->
      <div class="glass-card table-container">
        <div v-if="loading" class="loading-state">
          <Loader2 :size="24" class="spinner-icon" />
          <span>Memuat data inventaris...</span>
        </div>
        <div v-else class="table-wrap">
          <table class="glass-table">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th>Kode / Serial No</th>
                <th>Status Stok</th>
                <th>Kepemilikan</th>
                <th>Biaya Sewa / unit</th>
                <th>Kondisi</th>
                <th>Catatan</th>
                <th v-if="auth.canManageEvents">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="inventories.length === 0">
                <td :colspan="auth.canManageEvents ? 8 : 7" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada data inventaris gudang</td>
              </tr>
              <tr v-for="item in inventories" :key="item.id">
                <td>
                  <div class="item-cell">
                    <div class="item-icon" :class="`icon-${item.status}`">
                      <Package :size="16" />
                    </div>
                    <span style="font-weight:600">{{ item.item_name }}</span>
                  </div>
                </td>
                <td style="font-family:monospace;color:var(--text-secondary)">{{ item.serial_number || '—' }}</td>
                <td>
                  <div class="stock-display">
                    <span class="stock-avail" :class="{ 'warning': item.available_quantity === 0 }">{{ item.available_quantity }}</span>
                    <span class="stock-total">/ {{ item.total_quantity }} unit</span>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="item.ownership === 'owned' ? 'badge-owned' : 'badge-rented'">
                    {{ item.ownership === 'owned' ? 'Milik EO' : 'Sewa' }}
                  </span>
                </td>
                <td>
                  <span v-if="item.ownership === 'rented' && item.default_rent_price > 0" style="font-size:13px">
                    {{ formatCurrency(item.default_rent_price) }}/hari
                  </span>
                  <span v-else style="color:var(--text-muted)">—</span>
                </td>
                <td>
                  <span class="badge" :class="`badge-status-${item.status}`">
                    {{ item.status === 'ready' ? 'Ready' : item.status === 'maintenance' ? 'Maintenance' : 'Broken' }}
                  </span>
                </td>
                <td style="color:var(--text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" :title="item.notes">
                  {{ item.notes || '—' }}
                </td>
                <td v-if="auth.canManageEvents">
                  <div style="display:flex;gap:6px">
                    <button v-if="item.available_quantity > 0" class="btn btn-glass btn-sm" @click="openStatusModal(item)" title="Pindahkan Status (Maintenance/Rusak)" style="color:#f59e0b">
                      <Wrench :size="16" />
                    </button>
                    <button class="btn btn-glass btn-sm" @click="openEdit(item)" title="Edit">
                      <Edit :size="16" />
                    </button>
                    <button class="btn btn-danger btn-sm" @click="confirmDelete(item)" title="Hapus">
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination" style="margin-top: 16px; padding: 0 20px 20px 20px;">
          <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchInventory(pagination.current_page - 1)" title="Sebelumnya">
            <ChevronLeft :size="18" />
          </button>
          <button
            v-for="p in pagination.last_page"
            :key="p"
            class="page-btn"
            :class="{ active: p === pagination.current_page }"
            @click="fetchInventory(p)"
          >
            {{ p }}
          </button>
          <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchInventory(pagination.current_page + 1)" title="Selanjutnya">
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </template>

    <!-- Tab 2: Active Distribution -->
    <template v-else-if="activeTab === 'distribution'">
      <div class="glass-card table-container" style="margin-top: 16px;">
        <div v-if="loading" class="loading-state">
          <Loader2 :size="24" class="spinner-icon" />
          <span>Memuat data distribusi...</span>
        </div>
        <div v-else class="table-wrap">
          <table class="glass-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Penanggung Jawab (PIC)</th>
                <th>Tgl Keluar</th>
                <th>Biaya Sewa Alat</th>
                <th>Catatan Alokasi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="activeDistributions.length === 0">
                <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada barang logistik yang sedang terdistribusi ke event aktif</td>
              </tr>
              <tr v-for="dist in activeDistributions" :key="dist.id">
                <td>
                  <div style="font-weight:600">{{ dist.event?.name }}</div>
                </td>
                <td>
                  <div class="item-cell">
                    <div class="item-icon icon-ready">
                      <Package :size="16" />
                    </div>
                    <span>{{ dist.inventory?.item_name }}</span>
                  </div>
                </td>
                <td style="font-weight:700">{{ dist.quantity }} unit</td>
                <td>
                  <div class="pic-cell" v-if="dist.user">
                    <div class="pic-avatar">{{ dist.user?.name.charAt(0) }}</div>
                    <div>
                      <div style="font-weight:500">{{ dist.user?.name }}</div>
                      <div style="font-size:10px;color:var(--text-muted)">{{ roleLabel(dist.user?.role) }}</div>
                    </div>
                  </div>
                  <span v-else style="color:var(--text-muted)">—</span>
                </td>
                <td>{{ formatDate(dist.borrowed_at) }}</td>
                <td>
                  <span v-if="dist.rent_cost > 0" style="color:#ef4444;font-weight:600">
                    {{ formatCurrency(dist.rent_cost) }}
                  </span>
                  <span v-else style="color:var(--text-muted)">Milik Sendiri</span>
                </td>
                <td style="color:var(--text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" :title="dist.notes">
                  {{ dist.notes || '—' }}
                </td>
                <td>
                  <button class="btn btn-glass btn-sm" @click="openReturnModal(dist)">
                    <ArrowDownLeft :size="16" style="margin-right:4px;" />
                    Kembalikan
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="distPagination.last_page > 1" class="pagination" style="margin-top: 16px; padding: 0 20px 20px 20px;">
          <button class="page-btn" :disabled="distPagination.current_page === 1" @click="fetchDistributions(distPagination.current_page - 1)" title="Sebelumnya">
            <ChevronLeft :size="18" />
          </button>
          <button
            v-for="p in distPagination.last_page"
            :key="p"
            class="page-btn"
            :class="{ active: p === distPagination.current_page }"
            @click="fetchDistributions(p)"
          >
            {{ p }}
          </button>
          <button class="page-btn" :disabled="distPagination.current_page === distPagination.last_page" @click="fetchDistributions(distPagination.current_page + 1)" title="Selanjutnya">
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </template>

    <!-- Tab 3: Status Actions (Maintenance / Damaged) -->
    <template v-else-if="activeTab === 'status_actions'">
      <div class="glass-card table-container" style="margin-top: 16px;">
        <div v-if="loading" class="loading-state">
          <Loader2 :size="24" class="spinner-icon" />
          <span>Memuat data status alat bermasalah...</span>
        </div>
        <div v-else class="table-wrap">
          <table class="glass-table">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th>Kategori Kondisi</th>
                <th>Jumlah</th>
                <th>Penanggung Jawab (PIC)</th>
                <th>Tgl Dilaporkan</th>
                <th>Catatan</th>
                <th v-if="auth.canManageEvents">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="statusActions.length === 0">
                <td :colspan="auth.canManageEvents ? 7 : 6" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada alat yang sedang dalam pemeliharaan atau rusak</td>
              </tr>
              <tr v-for="act in statusActions" :key="act.id">
                <td>
                  <div class="item-cell">
                    <div class="item-icon" :class="`icon-${act.type}`">
                      <Package :size="16" />
                    </div>
                    <span style="font-weight:600">{{ act.inventory?.item_name }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="`badge-status-${act.type}`">
                    {{ act.type === 'maintenance' ? 'Maintenance' : 'Broken' }}
                  </span>
                </td>
                <td style="font-weight:700">{{ act.quantity }} unit</td>
                <td>
                  <div class="pic-cell" v-if="act.user">
                    <div class="pic-avatar">{{ act.user.name.charAt(0) }}</div>
                    <div>
                      <div style="font-weight:500">{{ act.user.name }}</div>
                      <div style="font-size:10px;color:var(--text-muted)">{{ roleLabel(act.user.role) }}</div>
                    </div>
                  </div>
                  <span v-else style="color:var(--text-muted)">—</span>
                </td>
                <td>{{ formatDate(act.created_at) }}</td>
                <td style="color:var(--text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" :title="act.notes">
                  {{ act.notes || '—' }}
                </td>
                <td v-if="auth.canManageEvents">
                  <button class="btn btn-primary btn-sm" @click="openResolveModal(act)">
                    <Check :size="14" style="margin-right: 4px;" />
                    Selesaikan
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="statusPagination.last_page > 1" class="pagination" style="margin-top: 16px; padding: 0 20px 20px 20px;">
          <button class="page-btn" :disabled="statusPagination.current_page === 1" @click="fetchStatusActions(statusPagination.current_page - 1)" title="Sebelumnya">
            <ChevronLeft :size="18" />
          </button>
          <button
            v-for="p in statusPagination.last_page"
            :key="p"
            class="page-btn"
            :class="{ active: p === statusPagination.current_page }"
            @click="fetchStatusActions(p)"
          >
            {{ p }}
          </button>
          <button class="page-btn" :disabled="statusPagination.current_page === statusPagination.last_page" @click="fetchStatusActions(statusPagination.current_page + 1)" title="Selanjutnya">
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </template>

    <!-- Global Inventory Add/Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-box" style="max-width:560px">
          <div class="modal-header">
            <h2>{{ editId ? 'Edit Barang Inventaris' : 'Daftarkan Barang Baru' }}</h2>
            <button class="btn btn-glass btn-sm" @click="closeModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <transition name="fade">
            <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          </transition>

          <form @submit.prevent="submitForm">
            <div class="form-group">
              <label>Nama Barang *</label>
              <input v-model="form.item_name" class="glass-input" required placeholder="Masukkan nama barang"/>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Nomor Seri / Kode</label>
                <input v-model="form.serial_number" class="glass-input" placeholder="Masukkan nomor seri barang"/>
              </div>
              <div class="form-group">
                <label>Kondisi Awal *</label>
                <select v-model="form.status" class="glass-input" required>
                  <option value="ready">Ready (Baik)</option>
                  <option value="maintenance">Maintenance</option>
                  <option value="damaged">Rusak (Broken)</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Total Stok Gudang *</label>
                <input v-model.number="form.total_quantity" type="number" min="1" class="glass-input" required placeholder="5"/>
              </div>
              <div class="form-group">
                <label>Kepemilikan *</label>
                <select v-model="form.ownership" class="glass-input" required>
                  <option value="owned">Milik Sendiri (Owned)</option>
                  <option value="rented">Sewa dari Vendor (Rented)</option>
                </select>
              </div>
            </div>
            <transition name="fade">
              <div v-if="form.ownership === 'rented'" class="form-group">
                <label>Tarif Sewa per Unit (Rp / Hari) *</label>
                <input v-model.number="form.default_rent_price" type="number" min="0" class="glass-input" required placeholder="150000"/>
              </div>
            </transition>
            <div class="form-group">
              <label>Catatan Tambahan</label>
              <textarea v-model="form.notes" class="glass-input" rows="3" placeholder="Masukkan informasi detail atau vendor logistik..."></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>{{ editId ? 'Simpan Perubahan' : 'Daftarkan Barang' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Return/Check-in Modal -->
    <Transition name="fade">
      <div v-if="showReturnModal" class="modal-overlay" @click.self="closeReturnModal">
        <div class="modal-box" style="max-width:480px">
          <div class="modal-header">
            <h2>Proses Pengembalian Barang</h2>
            <button class="btn btn-glass btn-sm" @click="closeReturnModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <form @submit.prevent="submitReturn">
            <div style="margin-bottom: 16px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:12px;">
              <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Barang yang dikembalikan</div>
              <div style="font-weight:600; font-size:14px; margin-top:2px;">{{ returnForm.item_name }}</div>
              <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Jumlah: <strong>{{ returnForm.quantity }} unit</strong> dari Event <strong>{{ returnForm.event_name }}</strong></div>
            </div>
            <div class="form-group">
              <label>Tanggal Pengembalian *</label>
              <input v-model="returnForm.returned_at" type="date" class="glass-input" required/>
            </div>
            <div class="form-group">
              <label>Kondisi Pengembalian *</label>
              <select v-model="returnForm.return_status" class="glass-input" required>
                <option value="complete">Lengkap (Baik)</option>
                <option value="incomplete">Kurang / Unit Hilang</option>
                <option value="damaged">Rusak (Broken)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Catatan Kerusakan / Kehilangan</label>
              <input v-model="returnForm.notes" class="glass-input" placeholder="Tulis keterangan kondisi barang (opsional)..."/>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeReturnModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>Konfirmasi Pengembalian</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Create Status Action Modal -->
    <Transition name="fade">
      <div v-if="showStatusModal" class="modal-overlay" @click.self="closeStatusModal">
        <div class="modal-box" style="max-width:480px">
          <div class="modal-header">
            <h2>Pindahkan Status Barang</h2>
            <button class="btn btn-glass btn-sm" @click="closeStatusModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <transition name="fade">
            <div v-if="statusFormError" class="alert alert-error">{{ statusFormError }}</div>
          </transition>

          <form @submit.prevent="submitStatusAction">
            <div style="margin-bottom: 16px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:12px;">
              <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Barang Terpilih</div>
              <div style="font-weight:600; font-size:14px; margin-top:2px;">{{ selectedInventoryItem?.item_name }}</div>
              <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">Tersedia di Gudang: <strong>{{ selectedInventoryItem?.available_quantity }} unit</strong></div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kategori Masalah *</label>
                <select v-model="statusForm.type" class="glass-input" required>
                  <option value="maintenance">Maintenance (Pemeliharaan)</option>
                  <option value="damaged">Rusak (Broken)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jumlah Unit *</label>
                <input v-model.number="statusForm.quantity" type="number" min="1" :max="selectedInventoryItem?.available_quantity" class="glass-input" required />
              </div>
            </div>

            <div class="form-group">
              <label>Penanggung Jawab (PIC) *</label>
              <select v-model="statusForm.user_id" class="glass-input" required>
                <option value="">Pilih PIC Staff</option>
                <option v-for="u in usersList" :key="u.id" :value="u.id">{{ u.name }} ({{ roleLabel(u.role) }})</option>
              </select>
            </div>

            <div class="form-group">
              <label>Catatan Kerusakan / Keterangan perbaikan *</label>
              <textarea v-model="statusForm.notes" class="glass-input" required rows="3" placeholder="Masukkan detail alasan pemindahan..."></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeStatusModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>Pindahkan</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Resolve Status Action Modal -->
    <Transition name="fade">
      <div v-if="showResolveModal" class="modal-overlay" @click.self="closeResolveModal">
        <div class="modal-box" style="max-width:480px">
          <div class="modal-header">
            <h2>Resolusi Status Barang</h2>
            <button class="btn btn-glass btn-sm" @click="closeResolveModal" title="Tutup">
              <X :size="18" />
            </button>
          </div>

          <form @submit.prevent="submitResolveStatusAction">
            <div style="margin-bottom: 16px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:12px;">
              <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Barang Terkait</div>
              <div style="font-weight:600; font-size:14px; margin-top:2px;">{{ selectedStatusAction?.inventory?.item_name }}</div>
              <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">
                Jumlah: <strong>{{ selectedStatusAction?.quantity }} unit</strong> | Status: <strong>{{ selectedStatusAction?.type === 'maintenance' ? 'Maintenance' : 'Rusak' }}</strong>
              </div>
            </div>

            <div class="form-group">
              <label>Opsi Penyelesaian *</label>
              <select v-model="resolveForm.resolution" class="glass-input" required>
                <option value="repaired">Selesai Diperbaiki (Kembali Siap Pakai)</option>
                <option value="discarded">Dibuang / Rusak Total (Mengurangi Total Aset)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Catatan Resolusi</label>
              <textarea v-model="resolveForm.notes" class="glass-input" rows="3" placeholder="Masukkan keterangan tambahan jika ada..."></textarea>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
              <button type="button" class="btn btn-glass" @click="closeResolveModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>Konfirmasi Resolusi</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Global Inventory Delete Confirm Modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width:400px;text-align:center">
          <div style="margin-bottom:16px">
            <AlertCircle :size="48" style="margin:0 auto; color: #f59e0b;" />
          </div>
          <h2 style="margin-bottom:12px">Hapus Barang?</h2>
          <p style="color:var(--text-secondary);margin-bottom:24px">Barang <strong>{{ deleteTarget.item_name }}</strong> akan dihapus permanen dari sistem pergudangan global.</p>
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
import { ref, onMounted, watch } from 'vue'
import { Plus, Boxes, Truck, Loader2, Package, Trash2, Edit, X, AlertCircle, ArrowDownLeft, Wrench, Check, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()
const activeTab = ref('warehouse')
const loading = ref(true)
const search = ref('')
const filterStatus = ref('')
const filterOwnership = ref('')

const pagination = ref({ current_page: 1, last_page: 1, per_page: 10 })
const distPagination = ref({ current_page: 1, last_page: 1, per_page: 10 })
const statusPagination = ref({ current_page: 1, last_page: 1, per_page: 10 })
const inventories = ref([])
const activeDistributions = ref([])
const statusActions = ref([])
const usersList = ref([])

// Form states
const showModal = ref(false)
const editId = ref(null)
const submitting = ref(false)
const formError = ref('')
const deleteTarget = ref(null)

const form = ref(defaultForm())

// Return Form state
const showReturnModal = ref(false)
const returnForm = ref({
  id: null,
  item_name: '',
  quantity: 0,
  event_name: '',
  returned_at: new Date().toISOString().substring(0, 10),
  return_status: 'complete',
  notes: ''
})

// Status Action state
const showStatusModal = ref(false)
const showResolveModal = ref(false)
const statusFormError = ref('')
const selectedInventoryItem = ref(null)
const selectedStatusAction = ref(null)
const statusForm = ref({
  inventory_id: null,
  user_id: '',
  type: 'maintenance',
  quantity: 1,
  notes: ''
})
const resolveForm = ref({
  resolution: 'repaired',
  notes: ''
})

const roleLabels = {
  superadmin: 'Super Admin', project_manager: 'Project Manager', staff: 'Staff / Personnel',
  event_planner: 'Event Planner', promotion_team: 'Promotion Team', partnership_manager: 'Partnership Manager',
  budgeting: 'Budgeting', operations_team: 'Operations Team', creative_team: 'Creative Team',
  rundown_coordinator: 'Rundown Coordinator', talent_coordinator: 'Talent Coordinator',
  registration_guest_management: 'Registration & Guest Mgmt', technical_team: 'Technical Team',
  documentation_team: 'Documentation Team', liaison_officer: 'Liaison Officer',
}
function roleLabel(r) { return roleLabels[r] || r }

function defaultForm() {
  return { item_name: '', serial_number: '', total_quantity: 1, ownership: 'owned', default_rent_price: '', status: 'ready', notes: '' }
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v) }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }

async function switchTab(tab) {
  activeTab.value = tab
  search.value = ''
  filterStatus.value = ''
  filterOwnership.value = ''
  if (tab === 'warehouse') {
    await fetchInventory()
  } else if (tab === 'distribution') {
    await fetchDistributions()
  } else if (tab === 'status_actions') {
    await fetchStatusActions()
  }
}

async function fetchInventory(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/inventories', {
      params: {
        page,
        search: search.value,
        status: filterStatus.value,
        ownership: filterOwnership.value
      }
    })
    inventories.value = res.data.data ?? res.data ?? []
    pagination.value = {
      current_page: res.data.current_page ?? 1,
      last_page: res.data.last_page ?? 1,
      per_page: res.data.per_page ?? 10
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function fetchDistributions(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/logistics/active', { params: { page } })
    activeDistributions.value = res.data.data ?? res.data ?? []
    distPagination.value = {
      current_page: res.data.current_page ?? 1,
      last_page: res.data.last_page ?? 1,
      per_page: res.data.per_page ?? 10
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function fetchStatusActions(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/logistics/status-actions', { params: { page } })
    statusActions.value = res.data.data ?? res.data ?? []
    statusPagination.value = {
      current_page: res.data.current_page ?? 1,
      last_page: res.data.last_page ?? 1,
      per_page: res.data.per_page ?? 10
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function fetchUsersList() {
  try {
    const res = await api.get('/users-list')
    usersList.value = res.data
  } catch (e) {
    console.error(e)
  }
}

function openCreate() {
  editId.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(item) {
  editId.value = item.id
  form.value = {
    id: item.id,
    item_name: item.item_name,
    serial_number: item.serial_number || '',
    total_quantity: item.total_quantity,
    ownership: item.ownership,
    default_rent_price: item.default_rent_price || '',
    status: item.status,
    notes: item.notes || ''
  }
  formError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitForm() {
  formError.value = ''
  submitting.value = true
  
  const payload = { ...form.value }
  if (payload.ownership === 'owned') {
    payload.default_rent_price = null
  }

  try {
    await api.post('/inventories', payload)
    closeModal()
    await fetchInventory(editId.value ? pagination.value.current_page : 1)
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan data barang.'
  } finally {
    submitting.value = false
  }
}

function confirmDelete(item) {
  deleteTarget.value = item
}

async function doDelete() {
  submitting.value = true
  try {
    await api.delete(`/inventories/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchInventory(pagination.value.current_page)
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menghapus barang.')
  } finally {
    submitting.value = false
  }
}

function openReturnModal(dist) {
  returnForm.value = {
    id: dist.id,
    item_name: dist.inventory?.item_name || '',
    quantity: dist.quantity,
    event_id: dist.event_id,
    event_name: dist.event?.name || '',
    returned_at: new Date().toISOString().substring(0, 10),
    return_status: 'complete',
    notes: ''
  }
  showReturnModal.value = true
}

function closeReturnModal() {
  showReturnModal.value = false
}

async function submitReturn() {
  submitting.value = true
  try {
    await api.post(`/events/${returnForm.value.event_id}/logistics/${returnForm.value.id}/return`, {
      returned_at: returnForm.value.returned_at,
      return_status: returnForm.value.return_status,
      notes: returnForm.value.notes
    })
    closeReturnModal()
    await fetchDistributions(distPagination.value.current_page)
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal mengembalikan barang.')
  } finally {
    submitting.value = false
  }
}

function openStatusModal(item) {
  selectedInventoryItem.value = item
  statusForm.value = {
    inventory_id: item.id,
    user_id: '',
    type: 'maintenance',
    quantity: 1,
    notes: ''
  }
  statusFormError.value = ''
  showStatusModal.value = true
  fetchUsersList()
}

function closeStatusModal() {
  showStatusModal.value = false
  selectedInventoryItem.value = null
}

async function submitStatusAction() {
  statusFormError.value = ''
  submitting.value = true
  try {
    await api.post('/logistics/status-actions', statusForm.value)
    closeStatusModal()
    await fetchInventory(pagination.value.current_page)
  } catch (e) {
    statusFormError.value = e.response?.data?.message || 'Gagal memindahkan status barang.'
  } finally {
    submitting.value = false
  }
}

function openResolveModal(act) {
  selectedStatusAction.value = act
  resolveForm.value = {
    resolution: 'repaired',
    notes: ''
  }
  showResolveModal.value = true
}

function closeResolveModal() {
  showResolveModal.value = false
  selectedStatusAction.value = null
}

async function submitResolveStatusAction() {
  submitting.value = true
  try {
    await api.post(`/logistics/status-actions/${selectedStatusAction.value.id}/resolve`, resolveForm.value)
    closeResolveModal()
    await fetchStatusActions(statusPagination.value.current_page)
  } catch (e) {
    window.alert(e.response?.data?.message || 'Gagal menyelesaikan status barang.')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchInventory()
})
</script>

<style scoped>
.logistics-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
}
.header-left h2 {
  font-size: 18px;
  font-weight: 600;
}
.header-left p {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.filters {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 16px;
}
.table-container {
  padding: 0 0 20px;
}
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 48px;
  color: var(--text-secondary);
}

.item-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}
.item-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.icon-ready {
  background: rgba(16,185,129,0.15);
  color: #10b981;
}
.icon-maintenance {
  background: rgba(245,158,11,0.15);
  color: #f59e0b;
}
.icon-damaged {
  background: rgba(239,68,68,0.15);
  color: #ef4444;
}

.stock-display {
  display: flex;
  align-items: baseline;
  gap: 3px;
}
.stock-avail {
  font-size: 15px;
  font-weight: 700;
  color: #fff;
}
.stock-avail.warning {
  color: #ef4444;
}
.stock-total {
  font-size: 11px;
  color: var(--text-muted);
}

.badge-owned {
  background: rgba(14, 165, 233, 0.15);
  border: 1px solid rgba(14, 165, 233, 0.3);
  color: #38bdf8;
}
.badge-rented {
  background: rgba(236,72,153,0.15);
  border: 1px solid rgba(236,72,153,0.3);
  color: #f9a8d4;
}

.badge-status-ready {
  background: rgba(16,185,129,0.15);
  color: #34d399;
}
.badge-status-maintenance {
  background: rgba(245,158,11,0.15);
  color: #fcd34d;
}
.badge-status-damaged {
  background: rgba(239,68,68,0.15);
  color: #fca5a5;
}

/* Tab bar */
.modal-tabs {
  display: flex;
  gap: 12px;
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
  display: flex;
  align-items: center;
}
.tab-btn:hover {
  color: var(--text-primary);
  background: rgba(255,255,255,0.04);
}
.tab-btn.active {
  color: #fff;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.25), rgba(13, 148, 136, 0.25));
  border: 1px solid rgba(14, 165, 233, 0.3);
  font-weight: 600;
}

/* PIC styling */
.pic-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pic-avatar {
  width: 26px;
  height: 26px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  border: 1px solid rgba(255,255,255,0.1);
}
</style>
