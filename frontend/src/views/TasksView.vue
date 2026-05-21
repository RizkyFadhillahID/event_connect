<template>
  <div class="tasks-page">

    <!-- ══════════════════════════════════════════════════════
         PHASE 1: EVENT SELECTION SCREEN
         Shown when no event is selected yet
    ══════════════════════════════════════════════════════ -->
    <template v-if="!selectedEvent">
      <div class="page-header glass-card">
        <div class="header-left">
          <h2>Task &amp; Workflow Management</h2>
          <p>Pilih event terlebih dahulu untuk mulai mengelola task</p>
        </div>
      </div>

      <div class="event-search-bar glass-card">
        <Search :size="16" style="color:rgba(255,255,255,0.4);flex-shrink:0" />
        <input
          v-model="eventSearch"
          class="event-search-input"
          placeholder="Cari nama event..."
          @input="filterEvents"
        />
        <span v-if="loadingEvents" style="margin-left:auto">
          <Loader2 :size="16" class="spinner-icon" style="color:rgba(255,255,255,0.4)" />
        </span>
      </div>

      <div v-if="loadingEvents" class="loading-state glass-card">
        <Loader2 :size="28" class="spinner-icon" />
        <span>Memuat daftar event...</span>
      </div>

      <div v-else-if="!filteredEvents.length" class="empty-state glass-card">
        <FolderOpen :size="48" style="opacity:0.3" />
        <p>Tidak ada event yang tersedia.</p>
      </div>

      <div v-else class="event-grid">
        <div
          v-for="ev in filteredEvents"
          :key="ev.id"
          class="event-select-card glass-card"
          @click="selectEvent(ev)"
        >
          <div class="esc-header">
            <div class="esc-status-dot" :class="`dot-${ev.status}`"></div>
            <span class="esc-status-label" :class="`badge-ev-${ev.status}`">{{ eventStatusLabel(ev.status) }}</span>
          </div>
          <h3 class="esc-title">{{ ev.name }}</h3>
          <div class="esc-meta">
            <span><MapPin :size="12" /> {{ ev.location }}</span>
            <span><Calendar :size="12" /> {{ formatDate(ev.start_date) }}</span>
          </div>
          <div class="esc-stats" v-if="eventTaskCounts[ev.id]">
            <div class="esc-stat">
              <span class="esc-stat-num">{{ eventTaskCounts[ev.id].total }}</span>
              <span class="esc-stat-lbl">Total</span>
            </div>
            <div class="esc-stat">
              <span class="esc-stat-num" style="color:#67e8f9">{{ eventTaskCounts[ev.id].in_progress }}</span>
              <span class="esc-stat-lbl">Aktif</span>
            </div>
            <div class="esc-stat">
              <span class="esc-stat-num" style="color:#6ee7b7">{{ eventTaskCounts[ev.id].completed }}</span>
              <span class="esc-stat-lbl">Selesai</span>
            </div>
            <div class="esc-stat" v-if="eventTaskCounts[ev.id].overdue > 0">
              <span class="esc-stat-num" style="color:#fca5a5">{{ eventTaskCounts[ev.id].overdue }}</span>
              <span class="esc-stat-lbl">Overdue</span>
            </div>
          </div>
          <div class="esc-progress-wrap" v-if="eventTaskCounts[ev.id]?.total > 0">
            <div class="esc-progress-bar">
              <div class="esc-progress-fill" :style="`width:${eventTaskCounts[ev.id].progress}%`"></div>
            </div>
            <span class="esc-progress-pct">{{ eventTaskCounts[ev.id].progress }}%</span>
          </div>
          <div class="esc-cta">
            <span>Kelola Task</span>
            <ArrowRight :size="14" />
          </div>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════
         PHASE 2: TASK MANAGEMENT SCREEN
         Shown when event is selected
    ══════════════════════════════════════════════════════ -->
    <template v-else>

      <!-- Context header: shows current event + change button -->
      <div class="event-context-bar glass-card">
        <button class="back-btn" @click="deselectEvent" title="Ganti Event">
          <ArrowLeft :size="16" />
        </button>
        <div class="context-info">
          <span class="context-label">Working on</span>
          <h3 class="context-event-name">{{ selectedEvent.name }}</h3>
        </div>
        <div class="context-meta">
          <span class="context-chip"><Calendar :size="12" /> {{ formatDate(selectedEvent.start_date) }}</span>
          <span class="context-chip"><MapPin :size="12" /> {{ selectedEvent.location }}</span>
          <span class="context-chip" :class="`chip-ev-${selectedEvent.status}`">{{ eventStatusLabel(selectedEvent.status) }}</span>
        </div>
        <div class="context-task-stats" v-if="eventStats">
          <div class="ctx-stat"><span class="ctx-num">{{ eventStats.total }}</span><span class="ctx-lbl">Total</span></div>
          <div class="ctx-stat"><span class="ctx-num in-prog">{{ eventStats.in_progress }}</span><span class="ctx-lbl">Aktif</span></div>
          <div class="ctx-stat"><span class="ctx-num done">{{ eventStats.completed }}</span><span class="ctx-lbl">Selesai</span></div>
          <div class="ctx-stat" v-if="eventStats.overdue > 0"><span class="ctx-num overdue-num">{{ eventStats.overdue }}</span><span class="ctx-lbl">Overdue</span></div>
          <div class="ctx-progress">
            <div class="ctx-bar"><div class="ctx-bar-fill" :style="`width:${eventStats.progress}%`"></div></div>
            <span class="ctx-pct">{{ eventStats.progress }}%</span>
          </div>
        </div>
        <div class="context-actions">
          <div class="view-toggle">
            <button class="toggle-btn" :class="{ active: viewMode === 'kanban' }" @click="viewMode = 'kanban'" title="Kanban">
              <LayoutGrid :size="18" />
            </button>
            <button class="toggle-btn" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'" title="List">
              <List :size="18" />
            </button>
          </div>
          <button class="btn btn-primary" @click="openCreate">
            <Plus :size="18" /> Tambah Task
          </button>
        </div>
      </div>

      <!-- Filters (no event selector here) -->
      <div class="filters glass-card">
        <input v-model="filters.search" class="glass-input" placeholder="Cari task..." @input="debounceFetch" style="max-width:240px"/>
        <select v-model="filters.priority" class="glass-input" @change="fetchTasks()" style="max-width:150px">
          <option value="">Semua Prioritas</option>
          <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
        </select>
        <select v-model="filters.status" class="glass-input" @change="fetchTasks()" style="max-width:160px">
          <option value="">Semua Status</option>
          <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <label class="my-tasks-toggle">
          <input type="checkbox" v-model="filters.my_tasks" @change="fetchTasks()" />
          <span>Task Saya</span>
        </label>
        <label class="my-tasks-toggle" style="color:#fcd34d">
          <input type="checkbox" v-model="filters.overdue" @change="fetchTasks()" />
          <span>Overdue</span>
        </label>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state glass-card">
        <Loader2 :size="28" class="spinner-icon" />
        <span>Memuat task...</span>
      </div>

      <!-- Kanban View -->
      <div v-else-if="viewMode === 'kanban'" class="kanban-board">
      <div v-for="col in kanbanColumns" :key="col.status" class="kanban-col glass-card">
        <div class="col-header">
          <div class="col-title">
            <span class="col-dot" :style="`background:${col.color}`"></span>
            {{ col.label }}
          </div>
          <span class="col-count">{{ tasksByStatus[col.status]?.length || 0 }}</span>
        </div>
        <div class="col-cards">
          <div v-if="!tasksByStatus[col.status]?.length" class="empty-col">
            <ClipboardList :size="32" style="opacity:0.3;margin-bottom:8px" />
            <span>Tidak ada task</span>
          </div>
          <div
            v-for="task in tasksByStatus[col.status]"
            :key="task.id"
            class="task-card"
            :class="[`priority-${task.priority}`, { overdue: task.is_overdue }]"
            @click="openDetail(task)"
          >
            <div class="card-top">
              <span class="priority-badge" :class="`prio-${task.priority}`">{{ priorityLabel(task.priority) }}</span>
              <div class="card-actions" @click.stop>
                <button class="icon-btn" @click="openEdit(task)" title="Edit"><Edit :size="14" /></button>
                <button v-if="auth.canManageTasks" class="icon-btn danger" @click="confirmDelete(task)" title="Hapus"><Trash2 :size="14" /></button>
              </div>
            </div>
            <div class="card-title">{{ task.title }}</div>
            <div class="card-event" v-if="task.event">
              <Calendar :size="12" />
              {{ task.event.name }}
            </div>
            <div v-if="task.subtasks?.length" class="card-subtasks">
              <CheckSquare :size="12" />
              {{ task.subtasks.filter(s => s.status === 'completed').length }}/{{ task.subtasks.length }} subtask
            </div>
            <div class="card-footer">
              <div class="card-assignee" v-if="task.assignee">
                <div class="avatar-xs">{{ task.assignee.name.charAt(0) }}</div>
                <span>{{ task.assignee.name }}</span>
              </div>
              <div v-else class="card-assignee muted"><UserX :size="13" /> Unassigned</div>
              <div class="card-due" :class="{ overdue: task.is_overdue }" v-if="task.due_date">
                <Clock :size="12" />
                {{ formatDate(task.due_date) }}
              </div>
            </div>
            <!-- Status quick-change -->
            <select
              v-if="task.assigned_to === auth.user?.id || auth.canManageTasks"
              class="status-select"
              :value="task.status"
              @change="quickStatus(task, $event.target.value)"
              @click.stop
            >
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="glass-card table-container">
      <div class="table-wrap">
        <table class="glass-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Task</th>
              <th>Event</th>
              <th>Prioritas</th>
              <th>Status</th>
              <th>Assignee</th>
              <th>Deadline</th>
              <th>Progress</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!tasks.length">
              <td colspan="9" style="text-align:center;color:var(--text-muted);padding:32px">Tidak ada task ditemukan</td>
            </tr>
            <tr v-for="(task, idx) in tasks" :key="task.id" class="clickable-row" @click="openDetail(task)">
              <td style="color:var(--text-muted)">{{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}</td>
              <td>
                <div style="display:flex;flex-direction:column;gap:2px">
                  <div style="font-weight:500;display:flex;align-items:center;gap:6px">
                    <span v-if="task.is_overdue" style="color:#fca5a5" title="Overdue"><AlertCircle :size="14" /></span>
                    {{ task.title }}
                  </div>
                  <div v-if="task.subtasks?.length" style="font-size:11px;color:var(--text-muted)">
                    {{ task.subtasks.filter(s => s.status === 'completed').length }}/{{ task.subtasks.length }} subtask
                  </div>
                </div>
              </td>
              <td style="font-size:12px;color:var(--text-secondary)">{{ task.event?.name || '—' }}</td>
              <td><span class="priority-badge" :class="`prio-${task.priority}`">{{ priorityLabel(task.priority) }}</span></td>
              <td>
                <select
                  v-if="task.assigned_to === auth.user?.id || auth.canManageTasks"
                  class="status-select-inline"
                  :class="`status-${task.status}`"
                  :value="task.status"
                  @change="quickStatus(task, $event.target.value)"
                  @click.stop
                >
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <span v-else class="badge" :class="`task-badge-${task.status}`">{{ statusLabel(task.status) }}</span>
              </td>
              <td>
                <div v-if="task.assignee" style="display:flex;align-items:center;gap:6px">
                  <div class="avatar-xs">{{ task.assignee.name.charAt(0) }}</div>
                  <span style="font-size:13px">{{ task.assignee.name }}</span>
                </div>
                <span v-else style="color:var(--text-muted);font-size:12px">—</span>
              </td>
              <td style="white-space:nowrap;font-size:13px" :class="{ 'text-overdue': task.is_overdue }">
                {{ task.due_date ? formatDate(task.due_date) : '—' }}
              </td>
              <td>
                <div v-if="task.subtasks?.length" class="mini-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="`width:${subtaskProgress(task)}%`"></div>
                  </div>
                  <span>{{ subtaskProgress(task) }}%</span>
                </div>
                <span v-else style="color:var(--text-muted);font-size:12px">—</span>
              </td>
              <td @click.stop>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-glass btn-sm" @click="openEdit(task)" title="Edit"><Edit :size="16" /></button>
                  <button v-if="auth.canManageTasks" class="btn btn-danger btn-sm" @click="confirmDelete(task)" title="Hapus"><Trash2 :size="16" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="pagination">
        <button class="page-btn" :disabled="pagination.current_page === 1" @click="fetchTasks(pagination.current_page - 1)"><ChevronLeft :size="18" /></button>
        <button v-for="p in pagination.last_page" :key="p" class="page-btn" :class="{ active: p === pagination.current_page }" @click="fetchTasks(p)">{{ p }}</button>
        <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="fetchTasks(pagination.current_page + 1)"><ChevronRight :size="18" /></button>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-box" style="max-width:680px">
          <div class="modal-header">
            <h2>{{ editId ? 'Edit Task' : 'Buat Task Baru' }}</h2>
            <button class="btn btn-glass btn-sm" @click="closeModal"><X :size="18" /></button>
          </div>
          <transition name="fade">
            <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          </transition>
          <form @submit.prevent="submitForm">
            <div class="form-group">
              <label>Judul Task *</label>
              <input v-model="form.title" class="glass-input" required placeholder="e.g. Siapkan dekorasi panggung"/>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="glass-input" rows="2" placeholder="Detail task..."></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Event</label>
                <div class="glass-input event-readonly-field">{{ selectedEvent?.name }}</div>
              </div>
              <div class="form-group">
                <label>Kategori</label>
                <input v-model="form.category" class="glass-input" placeholder="Logistics, Creative, Technical…"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Prioritas *</label>
                <select v-model="form.priority" class="glass-input" required>
                  <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status *</label>
                <select v-model="form.status" class="glass-input" required>
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tenggat Waktu</label>
                <input v-model="form.due_date" type="date" class="glass-input"/>
              </div>
              <div class="form-group">
                <label>Jam Tenggat</label>
                <input v-model="form.due_time" type="time" class="glass-input"/>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Ditugaskan ke</label>
                <select v-model="form.assigned_to" class="glass-input">
                  <option value="">— Belum ditugaskan —</option>
                  <option v-for="p in personnelList" :key="p.id" :value="p.id">
                    {{ p.name }} ({{ p.role_in_event || roleLabel(p.role) }})
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Parent Task (sub-task dari)</label>
                <select v-model="form.parent_task_id" class="glass-input">
                  <option value="">— Tidak ada —</option>
                  <option v-for="t in parentableTasksList" :key="t.id" :value="t.id">{{ t.title }}</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Catatan Internal</label>
              <textarea v-model="form.notes" class="glass-input" rows="2" placeholder="Catatan tambahan untuk tim..."></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
              <button type="button" class="btn btn-glass" @click="closeModal">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <Loader2 v-if="submitting" :size="16" class="spinner-icon" />
                <span v-else>{{ editId ? 'Simpan' : 'Buat Task' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Detail + Comments Modal -->
    <Transition name="fade">
      <div v-if="detailTask" class="modal-overlay" @click.self="detailTask = null">
        <div class="modal-box" style="max-width:720px">
          <div class="modal-header">
            <div style="display:flex;flex-direction:column;gap:4px">
              <div style="display:flex;align-items:center;gap:8px">
                <span class="priority-badge" :class="`prio-${detailTask.priority}`">{{ priorityLabel(detailTask.priority) }}</span>
                <span v-if="detailTask.is_overdue" class="overdue-chip"><AlertCircle :size="13" /> Overdue</span>
              </div>
              <h2 style="margin-bottom:0">{{ detailTask.title }}</h2>
            </div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-glass btn-sm" @click="openEdit(detailTask)"><Edit :size="16" /></button>
              <button class="btn btn-glass btn-sm" @click="detailTask = null"><X :size="18" /></button>
            </div>
          </div>

          <div class="detail-layout">
            <!-- Left: info -->
            <div class="detail-info">
              <p class="detail-desc" v-if="detailTask.description">{{ detailTask.description }}</p>
              <p class="detail-desc" v-else style="color:var(--text-muted);font-style:italic">Tidak ada deskripsi.</p>

              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label"><Calendar :size="14"/> Event</span>
                  <span>{{ detailTask.event?.name || '—' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label"><User :size="14"/> Assignee</span>
                  <div v-if="detailTask.assignee" style="display:flex;align-items:center;gap:6px">
                    <div class="avatar-xs">{{ detailTask.assignee.name.charAt(0) }}</div>
                    <span>{{ detailTask.assignee.name }}</span>
                    <span style="font-size:11px;color:var(--text-muted)">({{ roleLabel(detailTask.assignee.role) }})</span>
                  </div>
                  <span v-else style="color:var(--text-muted)">Belum ditugaskan</span>
                </div>
                <div class="info-item">
                  <span class="info-label"><UserCheck :size="14"/> Dibuat oleh</span>
                  <span>{{ detailTask.creator?.name || '—' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label"><Clock :size="14"/> Deadline</span>
                  <span :class="{ 'text-overdue': detailTask.is_overdue }">
                    {{ detailTask.due_date ? formatDate(detailTask.due_date) : '—' }}
                    <span v-if="detailTask.due_time"> · {{ detailTask.due_time }}</span>
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label"><Tag :size="14"/> Kategori</span>
                  <span>{{ detailTask.category || '—' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label"><Link :size="14"/> Parent Task</span>
                  <span>{{ detailTask.parent?.title || '—' }}</span>
                </div>
              </div>

              <!-- Status change (assignee or manager) -->
              <div v-if="detailTask.assigned_to === auth.user?.id || auth.canManageTasks" class="status-changer">
                <label style="font-size:12px;color:var(--text-muted);margin-bottom:6px;display:block">Ubah Status</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <button
                    v-for="s in statuses"
                    :key="s.value"
                    class="status-pill"
                    :class="{ active: detailTask.status === s.value }"
                    :style="detailTask.status === s.value ? `background:${s.color};border-color:${s.color}` : ''"
                    @click="quickStatus(detailTask, s.value)"
                  >{{ s.label }}</button>
                </div>
              </div>

              <!-- Notes -->
              <div v-if="detailTask.notes" class="notes-box">
                <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px">CATATAN</div>
                <p style="font-size:13px;line-height:1.6">{{ detailTask.notes }}</p>
              </div>

              <!-- Sub-tasks -->
              <div v-if="detailTask.subtasks?.length" class="subtasks-section">
                <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:8px">
                  SUB-TASK ({{ detailTask.subtasks.filter(s => s.status === 'completed').length }}/{{ detailTask.subtasks.length }})
                </div>
                <div class="subtask-progress-bar">
                  <div class="progress-fill" :style="`width:${subtaskProgress(detailTask)}%`"></div>
                </div>
                <div v-for="sub in detailTask.subtasks" :key="sub.id" class="subtask-item">
                  <span class="sub-check" :class="{ done: sub.status === 'completed' }">
                    <CheckCircle2 v-if="sub.status === 'completed'" :size="16" style="color:#10b981"/>
                    <Circle v-else :size="16" style="color:var(--text-muted)"/>
                  </span>
                  <span :class="{ 'line-through': sub.status === 'completed' }">{{ sub.title }}</span>
                  <span v-if="sub.assignee" style="font-size:11px;color:var(--text-muted)">– {{ sub.assignee.name }}</span>
                </div>
              </div>
            </div>

            <!-- Right: comments -->
            <div class="detail-comments">
              <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:12px">
                KOMENTAR ({{ comments.length }})
              </div>
              <div class="comments-list" ref="commentsList">
                <div v-if="!comments.length" class="empty-comments">
                  <MessageSquare :size="28" style="opacity:0.3;margin-bottom:6px"/>
                  <span>Belum ada komentar</span>
                </div>
                <div v-for="c in comments" :key="c.id" class="comment-item">
                  <div class="comment-avatar">{{ c.user?.name?.charAt(0) }}</div>
                  <div class="comment-body">
                    <div class="comment-meta">
                      <span class="comment-author">{{ c.user?.name }}</span>
                      <span class="comment-time">{{ timeAgo(c.created_at) }}</span>
                    </div>
                    <div class="comment-text">{{ c.comment }}</div>
                  </div>
                </div>
              </div>
              <div class="comment-input-area">
                <textarea
                  v-model="newComment"
                  class="glass-input"
                  rows="2"
                  placeholder="Tulis komentar..."
                  @keydown.ctrl.enter="submitComment"
                ></textarea>
                <button class="btn btn-primary btn-sm" @click="submitComment" :disabled="!newComment.trim() || sendingComment">
                  <Loader2 v-if="sendingComment" :size="14" class="spinner-icon" />
                  <Send v-else :size="14" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirm -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal-box" style="max-width:400px;text-align:center">
          <AlertCircle :size="48" style="margin:0 auto 16px;color:#f59e0b"/>
          <h2 style="margin-bottom:12px">Hapus Task?</h2>
          <p style="color:var(--text-secondary);margin-bottom:24px">Task <strong>{{ deleteTarget.title }}</strong> dan semua sub-task-nya akan dihapus permanen.</p>
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
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import {
  Plus, Edit, Trash2, X, Loader2, List, LayoutGrid,
  Calendar, Clock, User, UserX, UserCheck, Tag, Link,
  ChevronLeft, ChevronRight, AlertCircle, CheckSquare,
  CheckCircle2, Circle, MessageSquare, Send, ClipboardList,
  Search, FolderOpen, MapPin, ArrowRight, ArrowLeft,
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'

const auth = useAuthStore()

// ─── Event Selection State ─────────────────────────────────
const selectedEvent   = ref(null)
const eventsList      = ref([])
const eventSearch     = ref('')
const filteredEvents  = ref([])
const loadingEvents   = ref(false)
const eventTaskCounts = ref({})
const eventStats      = ref(null)

// ─── Task Management State ─────────────────────────────────
const viewMode       = ref('kanban')
const tasks          = ref([])
const loading        = ref(false)
const submitting     = ref(false)
const formError      = ref('')
const showModal      = ref(false)
const editId         = ref(null)
const deleteTarget   = ref(null)
const detailTask     = ref(null)
const comments       = ref([])
const newComment     = ref('')
const sendingComment = ref(false)
const commentsList   = ref(null)
const pagination     = ref({ current_page: 1, last_page: 1, per_page: 20 })
const personnelList  = ref([])
const parentableTasksList = ref([])

const filters = ref({
  search:   '',
  priority: '',
  status:   '',
  my_tasks: false,
  overdue:  false,
})

// ─── Constants ────────────────────────────────────────────
const priorities = [
  { value: 'urgent', label: 'Urgent',  color: '#ef4444' },
  { value: 'high',   label: 'High',    color: '#f59e0b' },
  { value: 'medium', label: 'Medium',  color: '#6366f1' },
  { value: 'low',    label: 'Low',     color: '#6b7280' },
]

const statuses = [
  { value: 'pending',     label: 'Pending',     color: '#6b7280' },
  { value: 'in_progress', label: 'In Progress', color: '#06b6d4' },
  { value: 'review',      label: 'Review',      color: '#f59e0b' },
  { value: 'completed',   label: 'Completed',   color: '#10b981' },
  { value: 'cancelled',   label: 'Cancelled',   color: '#ef4444' },
]

const kanbanColumns = [
  { status: 'pending',     label: 'Pending',     color: '#6b7280' },
  { status: 'in_progress', label: 'In Progress', color: '#06b6d4' },
  { status: 'review',      label: 'Review',      color: '#f59e0b' },
  { status: 'completed',   label: 'Completed',   color: '#10b981' },
]

const roleLabels = {
  superadmin: 'Super Admin', project_manager: 'Project Manager', staff: 'Staff / Personnel',
  event_planner: 'Event Planner', promotion_team: 'Promotion Team', partnership_manager: 'Partnership Manager',
  budgeting: 'Budgeting', operations_team: 'Operations Team', creative_team: 'Creative Team',
  rundown_coordinator: 'Rundown Coordinator', talent_coordinator: 'Talent Coordinator',
  registration_guest_management: 'Registration & Guest Mgmt', technical_team: 'Technical Team',
  documentation_team: 'Documentation Team', liaison_officer: 'Liaison Officer',
}

// ─── Computed ─────────────────────────────────────────────
const tasksByStatus = computed(() => {
  const map = {}
  for (const col of kanbanColumns) map[col.status] = []
  for (const t of tasks.value) {
    if (map[t.status]) map[t.status].push(t)
  }
  return map
})

// ─── Helpers ──────────────────────────────────────────────
function priorityLabel(v) { return priorities.find(p => p.value === v)?.label || v }
function statusLabel(v)   { return statuses.find(s => s.value === v)?.label || v }
function roleLabel(v)     { return roleLabels[v] || v }
function subtaskProgress(task) {
  if (!task.subtasks?.length) return 0
  return Math.round(task.subtasks.filter(s => s.status === 'completed').length / task.subtasks.length * 100)
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
function timeAgo(dt) {
  const diff = Date.now() - new Date(dt)
  const mins = Math.floor(diff / 60000)
  if (mins < 1)  return 'baru saja'
  if (mins < 60) return `${mins} menit lalu`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24)  return `${hrs} jam lalu`
  return formatDate(dt)
}
function eventStatusLabel(s) {
  const m = { draft: 'Draft', active: 'Active', ongoing: 'Ongoing', completed: 'Selesai', cancelled: 'Dibatalkan' }
  return m[s] || s
}

let debounceTimer
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchTasks(), 400)
}

// ─── Event Selection ───────────────────────────────────────
function filterEvents() {
  const q = eventSearch.value.toLowerCase()
  filteredEvents.value = q
    ? eventsList.value.filter(ev =>
        ev.name.toLowerCase().includes(q) || (ev.location || '').toLowerCase().includes(q))
    : [...eventsList.value]
}

async function selectEvent(ev) {
  selectedEvent.value = ev
  localStorage.setItem('tasks_selected_event_id', String(ev.id))
  filters.value = { search: '', priority: '', status: '', my_tasks: false, overdue: false }
  await Promise.all([fetchTasks(), loadEventPersonnel(), loadEventStats()])
}

function deselectEvent() {
  selectedEvent.value = null
  tasks.value = []
  eventStats.value = null
  localStorage.removeItem('tasks_selected_event_id')
}

async function loadEventStats() {
  if (!selectedEvent.value) return
  try {
    const res = await api.get(`/events/${selectedEvent.value.id}/tasks`)
    eventStats.value = res.data.stats ?? null
  } catch { /* silent */ }
}

async function loadEventTaskCounts() {
  for (const ev of eventsList.value) {
    try {
      const res = await api.get(`/events/${ev.id}/tasks`)
      eventTaskCounts.value = { ...eventTaskCounts.value, [ev.id]: res.data.stats }
    } catch { /* skip */ }
  }
}

// ─── Data Fetching ─────────────────────────────────────────
async function fetchTasks(page = 1) {
  if (!selectedEvent.value) return
  loading.value = true
  try {
    const params = { page, per_page: 20, event_id: selectedEvent.value.id, ...filters.value }
    if (!params.priority) delete params.priority
    if (!params.status)   delete params.status
    if (!params.search)   delete params.search
    if (!params.my_tasks) delete params.my_tasks
    if (!params.overdue)  delete params.overdue
    const res = await api.get('/tasks', { params })
    tasks.value = res.data.data
    pagination.value = { current_page: res.data.current_page, last_page: res.data.last_page, per_page: res.data.per_page }
  } finally {
    loading.value = false
  }
}

async function loadEventsList() {
  loadingEvents.value = true
  try {
    const res = await api.get('/events?per_page=100')
    eventsList.value = res.data.data
    filteredEvents.value = [...eventsList.value]
    loadEventTaskCounts()
  } finally {
    loadingEvents.value = false
  }
}

async function loadEventPersonnel() {
  if (!selectedEvent.value) return
  personnelList.value = []
  parentableTasksList.value = []
  const [pRes, tRes] = await Promise.all([
    api.get(`/events/${selectedEvent.value.id}/personnel`),
    api.get('/tasks', { params: { event_id: selectedEvent.value.id, per_page: 100 } }),
  ])
  personnelList.value = pRes.data
  parentableTasksList.value = tRes.data.data?.filter(t => t.id !== editId.value) || []
}

async function loadComments(task) {
  const res = await api.get(`/tasks/${task.id}/comments`)
  comments.value = res.data
  await nextTick()
  if (commentsList.value) commentsList.value.scrollTop = commentsList.value.scrollHeight
}

// ─── CRUD ──────────────────────────────────────────────────
function defaultForm() {
  return {
    title: '', description: '',
    event_id: selectedEvent.value?.id || '',
    assigned_to: '', parent_task_id: '',
    priority: 'medium', status: 'pending',
    due_date: '', due_time: '', category: '', notes: '',
  }
}
const form = ref(defaultForm())

function openCreate() {
  editId.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(task) {
  editId.value = task.id
  form.value = {
    title: task.title,
    description: task.description || '',
    event_id: task.event_id,
    assigned_to: task.assigned_to || '',
    parent_task_id: task.parent_task_id || '',
    priority: task.priority,
    status: task.status,
    due_date: task.due_date ? String(task.due_date).substring(0, 10) : '',
    due_time: task.due_time || '',
    category: task.category || '',
    notes: task.notes || '',
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
  if (!payload.assigned_to)    delete payload.assigned_to
  if (!payload.parent_task_id) delete payload.parent_task_id
  if (!payload.due_date)       delete payload.due_date
  if (!payload.due_time)       delete payload.due_time
  if (!payload.category)       delete payload.category
  if (!payload.notes)          delete payload.notes
  try {
    if (editId.value) {
      await api.put(`/tasks/${editId.value}`, payload)
    } else {
      await api.post('/tasks', payload)
    }
    closeModal()
    fetchTasks(pagination.value.current_page)
    loadEventStats()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) formError.value = Object.values(errs).flat().join(' ')
    else formError.value = e.response?.data?.message || 'Terjadi kesalahan.'
  } finally {
    submitting.value = false
  }
}

async function quickStatus(task, newStatus) {
  if (task.status === newStatus) return
  task.status = newStatus
  try {
    await api.patch(`/tasks/${task.id}/status`, { status: newStatus })
    if (detailTask.value?.id === task.id) detailTask.value.status = newStatus
    loadEventStats()
  } catch {
    fetchTasks(pagination.value.current_page)
  }
}

function confirmDelete(task) {
  deleteTarget.value = task
}

async function doDelete() {
  const target = deleteTarget.value
  submitting.value = true
  try {
    await api.delete(`/tasks/${target.id}`)
    deleteTarget.value = null
    if (detailTask.value?.id === target.id) detailTask.value = null
    fetchTasks(pagination.value.current_page)
    loadEventStats()
  } finally {
    submitting.value = false
  }
}

async function openDetail(task) {
  const res = await api.get(`/tasks/${task.id}`)
  detailTask.value = res.data
  comments.value = []
  await loadComments(task)
}

async function submitComment() {
  if (!newComment.value.trim()) return
  sendingComment.value = true
  try {
    const res = await api.post(`/tasks/${detailTask.value.id}/comments`, { comment: newComment.value })
    comments.value.unshift(res.data)
    newComment.value = ''
  } finally {
    sendingComment.value = false
  }
}

// ─── Init ──────────────────────────────────────────────────
onMounted(async () => {
  await loadEventsList()
  const url = new URL(window.location.href)
  const eid = url.searchParams.get('event_id') || localStorage.getItem('tasks_selected_event_id')
  if (eid) {
    const ev = eventsList.value.find(e => String(e.id) === String(eid))
    if (ev) await selectEvent(ev)
  }
})
</script>

<style scoped>
.tasks-page { display: flex; flex-direction: column; gap: 16px; }

/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; }
.header-left h2 { font-size: 18px; font-weight: 600; }
.header-left p { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

/* View toggle */
.view-toggle { display: flex; background: rgba(255,255,255,0.08); border: 1px solid var(--glass-border); border-radius: 10px; overflow: hidden; }
.toggle-btn { padding: 7px 12px; background: none; border: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; transition: all 0.2s; }
.toggle-btn.active { background: rgba(99,102,241,0.3); color: var(--text-primary); }

/* Filters */
.filters { display: flex; gap: 10px; padding: 14px 20px; flex-wrap: wrap; align-items: center; }
.my-tasks-toggle { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-secondary); cursor: pointer; padding: 0 8px; }
.my-tasks-toggle input { accent-color: var(--primary); cursor: pointer; }

/* Loading */
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 60px; color: var(--text-secondary); }

/* ─── KANBAN ──── */
.kanban-board { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
@media (max-width: 1100px) { .kanban-board { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .kanban-board { grid-template-columns: 1fr; } }

.kanban-col { padding: 14px; display: flex; flex-direction: column; gap: 10px; min-height: 400px; }
.col-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.col-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; }
.col-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.col-count { font-size: 12px; color: var(--text-muted); background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 20px; }
.col-cards { display: flex; flex-direction: column; gap: 8px; }
.empty-col { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px 16px; color: var(--text-muted); font-size: 12px; }

/* Task card */
.task-card {
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.task-card:hover { background: rgba(255,255,255,0.1); transform: translateY(-2px); }
.task-card.overdue { border-color: rgba(239,68,68,0.4); }
.task-card.priority-urgent { border-left: 3px solid #ef4444; }
.task-card.priority-high   { border-left: 3px solid #f59e0b; }
.task-card.priority-medium { border-left: 3px solid #6366f1; }
.task-card.priority-low    { border-left: 3px solid #6b7280; }

.card-top { display: flex; align-items: center; justify-content: space-between; }
.card-actions { display: flex; gap: 4px; opacity: 0; transition: opacity 0.2s; }
.task-card:hover .card-actions { opacity: 1; }
.icon-btn { background: rgba(255,255,255,0.1); border: none; border-radius: 6px; color: var(--text-secondary); cursor: pointer; padding: 4px; display: flex; transition: all 0.2s; }
.icon-btn:hover { background: rgba(255,255,255,0.2); color: var(--text-primary); }
.icon-btn.danger:hover { background: rgba(239,68,68,0.2); color: #fca5a5; }

.card-title { font-size: 13px; font-weight: 500; line-height: 1.4; }
.card-event { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
.card-subtasks { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
.card-footer { display: flex; align-items: center; justify-content: space-between; }
.card-assignee { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-secondary); }
.card-assignee.muted { color: var(--text-muted); }
.card-due { display: flex; align-items: center; gap: 3px; font-size: 11px; color: var(--text-muted); }
.card-due.overdue { color: #fca5a5; }

.status-select {
  width: 100%;
  padding: 5px 8px;
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--glass-border);
  border-radius: 8px;
  color: var(--text-secondary);
  font-size: 11px;
  font-family: inherit;
  cursor: pointer;
}
.status-select option { background: #302b63; }

/* Priority badges */
.priority-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.prio-urgent { background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
.prio-high   { background: rgba(245,158,11,0.2); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3); }
.prio-medium { background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
.prio-low    { background: rgba(107,114,128,0.2); color: #9ca3af; border: 1px solid rgba(107,114,128,0.3); }

/* Task status badge */
.task-badge-pending    { background:rgba(107,114,128,0.2); color:#d1d5db; border:1px solid rgba(107,114,128,0.3); }
.task-badge-in_progress{ background:rgba(6,182,212,0.2);  color:#67e8f9; border:1px solid rgba(6,182,212,0.3); }
.task-badge-review     { background:rgba(245,158,11,0.2); color:#fcd34d; border:1px solid rgba(245,158,11,0.3); }
.task-badge-completed  { background:rgba(16,185,129,0.2); color:#6ee7b7; border:1px solid rgba(16,185,129,0.3); }
.task-badge-cancelled  { background:rgba(239,68,68,0.2);  color:#fca5a5; border:1px solid rgba(239,68,68,0.3); }

/* List */
.table-container { padding: 0 0 20px; }
.text-overdue { color: #fca5a5 !important; }

.status-select-inline {
  padding: 4px 28px 4px 8px;
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  font-family: inherit;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='rgba(255,255,255,0.4)'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 6px center;
  background-size: 16px;
}
.status-pending     { color: #d1d5db; }
.status-in_progress { color: #67e8f9; }
.status-review      { color: #fcd34d; }
.status-completed   { color: #6ee7b7; }
.status-cancelled   { color: #fca5a5; }

.mini-progress { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-muted); }
.progress-bar { width: 60px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); border-radius: 2px; transition: width 0.3s; }

/* Avatars */
.avatar-xs { width: 22px; height: 22px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }

/* ─── DETAIL MODAL ─── */
.detail-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; max-height: 75vh; overflow: hidden; }
@media (max-width: 700px) { .detail-layout { grid-template-columns: 1fr; } }

.detail-info { overflow-y: auto; padding-right: 8px; }
.detail-desc { font-size: 14px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 16px; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.info-item { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px; }
.info-label { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

.status-changer { margin-bottom: 16px; }
.status-pill { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.06); color: var(--text-secondary); font-size: 12px; cursor: pointer; transition: all 0.2s; font-family: inherit; }
.status-pill:hover { background: rgba(255,255,255,0.12); }
.status-pill.active { color: white; }

.notes-box { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; }

.subtasks-section { margin-bottom: 8px; }
.subtask-progress-bar { width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-bottom: 10px; }
.subtask-item { display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.subtask-item:last-child { border-bottom: none; }
.line-through { text-decoration: line-through; color: var(--text-muted); }

/* Comments */
.detail-comments { display: flex; flex-direction: column; border-left: 1px solid var(--glass-border); padding-left: 20px; overflow: hidden; }
.comments-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column-reverse; gap: 12px; min-height: 0; max-height: 300px; padding-right: 4px; }
.empty-comments { display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); font-size: 12px; padding: 32px; }
.comment-item { display: flex; gap: 10px; }
.comment-avatar { width: 30px; height: 30px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.comment-body { flex: 1; min-width: 0; }
.comment-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.comment-author { font-size: 12px; font-weight: 600; }
.comment-time { font-size: 11px; color: var(--text-muted); }
.comment-text { font-size: 13px; color: var(--text-secondary); line-height: 1.5; word-break: break-word; }

.comment-input-area { display: flex; gap: 8px; align-items: flex-end; padding-top: 12px; border-top: 1px solid var(--glass-border); margin-top: 12px; }
.comment-input-area .glass-input { flex: 1; resize: none; font-size: 13px; }

.overdue-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 20px; font-size: 11px; color: #fca5a5; }

/* ─── EVENT SELECTION SCREEN ─── */
.event-search-bar { display: flex; align-items: center; gap: 10px; padding: 12px 20px; }
.event-search-input { flex: 1; background: none; border: none; outline: none; color: #fff; font-size: 0.9rem; }
.event-search-input::placeholder { color: rgba(255,255,255,0.3); }
.event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.event-select-card { padding: 20px; cursor: pointer; transition: all 0.2s; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); }
.event-select-card:hover { transform: translateY(-3px); border-color: rgba(99,102,241,0.5); background: rgba(99,102,241,0.08) !important; }
.esc-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.esc-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-draft { background: #6b7280; } .dot-active { background: #6366f1; } .dot-ongoing { background: #10b981; } .dot-completed { background: #3b82f6; } .dot-cancelled { background: #ef4444; }
.esc-status-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 2px 8px; border-radius: 20px; }
.badge-ev-draft { background: rgba(107,114,128,0.2); color: #9ca3af; }
.badge-ev-active { background: rgba(99,102,241,0.2); color: #a5b4fc; }
.badge-ev-ongoing { background: rgba(16,185,129,0.2); color: #6ee7b7; }
.badge-ev-completed { background: rgba(59,130,246,0.2); color: #93c5fd; }
.badge-ev-cancelled { background: rgba(239,68,68,0.2); color: #fca5a5; }
.esc-title { font-size: 1rem; font-weight: 600; color: #fff; margin: 0 0 8px; line-height: 1.3; }
.esc-meta { display: flex; gap: 12px; font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-bottom: 12px; flex-wrap: wrap; }
.esc-meta span { display: flex; align-items: center; gap: 4px; }
.esc-stats { display: flex; gap: 16px; margin-bottom: 10px; }
.esc-stat { display: flex; flex-direction: column; align-items: center; }
.esc-stat-num { font-size: 1.2rem; font-weight: 700; color: #fff; line-height: 1; }
.esc-stat-lbl { font-size: 0.68rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-top: 2px; }
.esc-progress-wrap { display: flex; align-items: center; gap: 8px; }
.esc-progress-bar { flex: 1; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
.esc-progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); border-radius: 2px; }
.esc-progress-pct { font-size: 0.75rem; color: rgba(255,255,255,0.5); white-space: nowrap; }
.esc-cta { display: flex; align-items: center; justify-content: flex-end; gap: 6px; font-size: 0.8rem; color: rgba(99,102,241,0.8); margin-top: 12px; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 60px 20px; color: rgba(255,255,255,0.5); text-align: center; }

/* ─── EVENT CONTEXT BAR ─── */
.event-context-bar { display: flex; align-items: center; gap: 16px; padding: 14px 20px; flex-wrap: wrap; }
.back-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.7); border-radius: 8px; padding: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; }
.back-btn:hover { background: rgba(255,255,255,0.14); color: #fff; }
.context-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.context-label { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.06em; }
.context-event-name { font-size: 1rem; font-weight: 600; color: #fff; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.context-meta { display: flex; gap: 8px; flex-wrap: wrap; }
.context-chip { font-size: 0.75rem; padding: 3px 10px; background: rgba(255,255,255,0.08); border-radius: 12px; color: rgba(255,255,255,0.6); display: flex; align-items: center; gap: 4px; border: 1px solid rgba(255,255,255,0.08); }
.chip-ev-active { border-color: rgba(99,102,241,0.3); color: #a5b4fc; }
.chip-ev-ongoing { border-color: rgba(16,185,129,0.3); color: #6ee7b7; }
.chip-ev-completed { border-color: rgba(59,130,246,0.3); color: #93c5fd; }
.chip-ev-cancelled { border-color: rgba(239,68,68,0.3); color: #fca5a5; }
.context-task-stats { display: flex; align-items: center; gap: 14px; margin-left: auto; }
.ctx-stat { display: flex; flex-direction: column; align-items: center; }
.ctx-num { font-size: 1rem; font-weight: 700; color: #fff; line-height: 1; }
.ctx-num.in-prog { color: #67e8f9; } .ctx-num.done { color: #6ee7b7; } .ctx-num.overdue-num { color: #fca5a5; }
.ctx-lbl { font-size: 0.65rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-top: 2px; }
.ctx-progress { display: flex; align-items: center; gap: 6px; }
.ctx-bar { width: 60px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
.ctx-bar-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); border-radius: 2px; }
.ctx-pct { font-size: 0.75rem; color: rgba(255,255,255,0.5); }
.context-actions { display: flex; gap: 8px; align-items: center; }

/* Event field readonly in modal */
.event-readonly-field { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.08) !important; color: rgba(255,255,255,0.6) !important; font-size: 0.88rem; font-style: italic; padding: 10px 14px; border-radius: 10px; }
</style>
