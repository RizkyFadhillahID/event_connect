<template>
  <div class="chat-page glass-card">
    <div class="chat-container">
      
      <!-- Left Side: Group list (Visible on desktop OR on mobile when no group is active) -->
      <div v-show="!isMobile || !activeEventId" class="fc-sidebar">
        <div class="fc-sidebar-search">
          <SearchIcon :size="16" class="fc-search-icon" />
          <input v-model="search" placeholder="Cari event..." autocomplete="off" class="search-input" />
        </div>

        <div v-if="loadingGroups" class="fc-sidebar-loading">
          <Loader2Icon :size="20" class="spinner-icon" />
          <span>Memuat grup...</span>
        </div>
        <div v-else-if="filteredGroups.length === 0" class="fc-sidebar-empty">
          Tidak ada event aktif
        </div>
        <div v-else class="fc-group-list">
          <button
            v-for="ev in filteredGroups"
            :key="ev.id"
            class="fc-group-item"
            :class="{ active: activeEventId === ev.id }"
            @click="selectEvent(ev)"
          >
            <div class="fc-group-avatar" :style="avatarColor(ev.name)">
              {{ ev.name.charAt(0).toUpperCase() }}
            </div>
            <div class="fc-group-info">
              <div class="fc-group-name">{{ ev.name }}</div>
              <div class="fc-group-last">
                {{ lastMessagePreview(ev.id) }}
              </div>
            </div>
            <div class="fc-group-meta">
              <span v-if="(chatStore.unreadCounts[ev.id] ?? 0) > 0" class="fc-unread-badge">
                {{ chatStore.unreadCounts[ev.id] > 99 ? '99+' : chatStore.unreadCounts[ev.id] }}
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- Right Side: Chat panel (Visible on desktop OR on mobile when a group is active) -->
      <div v-show="!isMobile || activeEventId" class="fc-chat-panel">
        <!-- No event selected (Desktop only) -->
        <div v-if="!activeEventId" class="fc-no-chat">
          <MessageCircleIcon :size="56" style="opacity: 0.25; margin-bottom: 16px;" />
          <h3>Event Group Chat</h3>
          <p>Pilih salah satu grup event dari daftar di samping untuk mulai bertukar pesan dengan tim.</p>
        </div>

        <!-- Chat area -->
        <template v-else>
          <!-- Chat header -->
          <div class="fc-chat-header">
            
            <div class="fc-chat-event-avatar" :style="avatarColor(activeEvent?.name ?? '')">
              {{ activeEvent?.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div class="fc-chat-event-info">
              <div class="fc-chat-event-name">{{ activeEvent?.name }}</div>
              <div class="fc-chat-online">
                <span class="fc-online-dot" />
                {{ onlineMembers.length }} online
                <div class="fc-avatars-row">
                  <div
                    v-for="m in onlineMembers.slice(0, 4)"
                    :key="m.id"
                    class="fc-mini-avatar"
                    :title="m.name"
                    :style="avatarColor(m.name)"
                  >{{ m.name.charAt(0).toUpperCase() }}</div>
                  <div v-if="onlineMembers.length > 4" class="fc-mini-avatar fc-mini-more">
                    +{{ onlineMembers.length - 4 }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages List -->
          <div ref="msgEl" class="fc-messages" @scroll="onScroll">
            <div v-if="loadingHistory" class="fc-msg-loading">
              <Loader2Icon :size="24" class="spinner-icon" />
              <span>Memuat riwayat pesan...</span>
            </div>
            <div v-else-if="messages.length === 0" class="fc-msg-empty">
              Belum ada pesan. Mulai percakapan pertama!
            </div>
            <template v-else>
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="fc-msg"
                :class="{ 'fc-msg--own': msg.user.id === currentUser?.id }"
              >
                <div v-if="msg.user.id !== currentUser?.id" class="fc-msg-avatar" :style="avatarColor(msg.user.name)">
                  {{ msg.user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="fc-msg-body">
                  <div v-if="msg.user.id !== currentUser?.id" class="fc-msg-sender">
                    {{ msg.user.name }}
                    <span class="fc-msg-role">{{ roleTag(msg.user.role) }}</span>
                  </div>
                  
                  <div class="fc-msg-bubble">
                    <!-- Text message -->
                    <div v-if="msg.message" class="msg-text">{{ msg.message }}</div>
                    
                    <!-- File attachment rendering -->
                    <div v-if="msg.file_url" class="msg-attachment">
                      <!-- Photo attachment -->
                      <div v-if="msg.file_type === 'image'" class="msg-image-wrap">
                        <img :src="msg.file_url" class="msg-image-preview" @click="openImage(msg.file_url)" alt="Attached Photo" />
                      </div>
                      
                      <!-- Document file attachment -->
                      <a v-else :href="msg.file_url" target="_blank" class="msg-file-card" :download="msg.file_name" title="Klik untuk mengunduh berkas">
                        <FileTextIcon :size="22" class="file-card-icon" />
                        <div class="file-card-info">
                          <div class="file-card-name">{{ msg.file_name }}</div>
                          <div class="file-card-download">Unduh Lampiran</div>
                        </div>
                        <DownloadIcon :size="16" class="file-card-arrow" />
                      </a>
                    </div>
                  </div>
                  
                  <div class="fc-msg-time">{{ formatTime(msg.created_at) }}</div>
                </div>
              </div>
            </template>
          </div>

          <!-- Selected File Preview Bar -->
          <div v-if="selectedFile" class="chat-preview-bar">
            <div class="preview-info">
              <ImageIcon v-if="selectedFileType === 'image'" :size="16" class="preview-icon" />
              <FileIcon v-else :size="16" class="preview-icon" />
              <span class="preview-name" :title="selectedFile.name">{{ selectedFile.name }}</span>
              <span class="preview-size">({{ formatSize(selectedFile.size) }})</span>
            </div>
            <button type="button" class="preview-cancel-btn" @click="cancelFile" title="Batalkan unggahan">
              <XIcon :size="14" />
            </button>
          </div>

          <!-- Input Row -->
          <form class="fc-input-row" @submit.prevent="send">
            <!-- Hidden inputs -->
            <input ref="fileInput" type="file" style="display: none;" @change="onFileSelected" />
            <input ref="imageInput" type="file" accept="image/*" style="display: none;" @change="onFileSelected" />

            <!-- Input actions -->
            <button 
              type="button" 
              class="attachment-btn" 
              :class="{ disabled: authStore.organization?.plan === 'free' }"
              @click="triggerFileInput" 
              title="Kirim Berkas"
            >
              <PaperclipIcon :size="18" />
            </button>
            <button 
              type="button" 
              class="attachment-btn" 
              :class="{ disabled: authStore.organization?.plan === 'free' }"
              @click="triggerImageInput" 
              title="Kirim Foto / Kamera"
            >
              <CameraIcon :size="18" />
            </button>

            <!-- Text input -->
            <input
              ref="inputEl"
              v-model="inputText"
              placeholder="Tulis pesan untuk tim..."
              maxlength="1000"
              :disabled="sending"
              autocomplete="off"
              class="glass-input chat-input"
              @keydown.enter.exact.prevent="send"
            />
            
            <button type="submit" class="btn btn-primary send-btn" :disabled="(!inputText.trim() && !selectedFile) || sending">
              <SendIcon :size="16" v-if="!sending" />
              <Loader2Icon :size="16" class="spinner-icon" v-else />
            </button>
          </form>
        </template>
      </div>

    </div>

    <!-- Lightbox Modal -->
    <Transition name="fade">
      <div v-if="lightboxUrl" class="lightbox-overlay" @click="lightboxUrl = null">
        <img :src="lightboxUrl" class="lightbox-img" alt="Zoomed Photo" />
        <button class="lightbox-close" @click="lightboxUrl = null">
          <XIcon :size="24" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { 
  MessageCircleIcon, SearchIcon, Loader2Icon, SendIcon, 
  ArrowLeftIcon, PaperclipIcon, CameraIcon, XIcon, 
  ImageIcon, FileIcon, FileTextIcon, DownloadIcon 
} from 'lucide-vue-next'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'

const chatStore = useChatStore()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user)

const search = ref('')
const activeEventId = ref(null)
const activeEvent = ref(null)
const inputText = ref('')
const sending = ref(false)
const loadingHistory = ref(false)
const msgEl = ref(null)
const inputEl = ref(null)
const userScrolledUp = ref(false)

// File attachment references & states
const fileInput = ref(null)
const imageInput = ref(null)
const selectedFile = ref(null)
const selectedFileType = ref(null)
const lightboxUrl = ref(null)

// Mobile responsiveness tracking
const windowWidth = ref(window.innerWidth)
const isMobile = computed(() => windowWidth.value <= 768)

function updateWidth() {
  windowWidth.value = window.innerWidth
}

const filteredGroups = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return chatStore.eventGroups
  return chatStore.eventGroups.filter(ev => ev.name.toLowerCase().includes(q))
})

const messages = computed(() => chatStore.messagesByEvent[activeEventId.value] ?? [])
const onlineMembers = computed(() => chatStore.onlineByEvent[activeEventId.value] ?? [])
const loadingGroups = computed(() => chatStore.loadingGroups)

function lastMessagePreview(eventId) {
  const msgs = chatStore.messagesByEvent[eventId]
  if (!msgs?.length) return 'Belum ada pesan'
  const last = msgs[msgs.length - 1]
  const prefix = last.user.id === currentUser.value?.id ? 'Kamu: ' : `${last.user.name.split(' ')[0]}: `
  
  if (last.file_url) {
    const typeLabel = last.file_type === 'image' ? '📷 Foto' : '📎 Berkas'
    const suffix = last.message ? `: ${last.message}` : ''
    const content = `${typeLabel}${suffix}`
    return prefix + (content.length > 25 ? content.slice(0, 25) + '…' : content)
  }
  
  return prefix + (last.message.length > 25 ? last.message.slice(0, 25) + '…' : last.message)
}

function triggerFileInput() {
  if (authStore.organization?.plan === 'free') {
    alert('Unggah berkas tidak didukung pada paket Free. Silakan hubungi admin untuk upgrade ke paket Business atau Enterprise.')
    return
  }
  fileInput.value?.click()
}

function triggerImageInput() {
  if (authStore.organization?.plan === 'free') {
    alert('Unggah foto tidak didukung pada paket Free. Silakan hubungi admin untuk upgrade ke paket Business atau Enterprise.')
    return
  }
  imageInput.value?.click()
}

function onFileSelected(e) {
  const file = e.target.files[0]
  if (!file) return

  // Limit file size to 10MB client-side
  if (file.size > 10 * 1024 * 1024) {
    alert('Ukuran berkas maksimal adalah 10 MB.')
    return
  }

  selectedFile.value = file
  selectedFileType.value = file.type.startsWith('image/') ? 'image' : 'file'

  // Reset inputs
  if (fileInput.value) fileInput.value.value = ''
  if (imageInput.value) imageInput.value.value = ''
}

function cancelFile() {
  selectedFile.value = null
  selectedFileType.value = null
}

function formatSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function openImage(url) {
  lightboxUrl.value = url
}

function goBack() {
  if (activeEventId.value) {
    chatStore.unsubscribeFromEvent(activeEventId.value)
    activeEventId.value = null
    chatStore.activeEventId = null
    activeEvent.value = null
    cancelFile()
  }
}

async function selectEvent(ev) {
  if (activeEventId.value === ev.id) return
  
  if (activeEventId.value) {
    chatStore.unsubscribeFromEvent(activeEventId.value)
  }

  activeEventId.value = ev.id
  chatStore.activeEventId = ev.id
  activeEvent.value = ev
  cancelFile()

  chatStore.subscribeToEvent(ev.id, currentUser.value?.id)
  chatStore.clearUnread(ev.id)

  const hasCached = (chatStore.messagesByEvent[ev.id]?.length ?? 0) > 0

  if (hasCached) {
    await nextTick()
    scrollToBottom()
    inputEl.value?.focus()
    chatStore.loadHistory(ev.id)
  } else {
    loadingHistory.value = true
    await chatStore.loadHistory(ev.id)
    loadingHistory.value = false
    await nextTick()
    scrollToBottom()
    inputEl.value?.focus()
  }
}

async function send() {
  const text = inputText.value.trim()
  const file = selectedFile.value
  if (!text && !file) return
  
  inputText.value = ''
  cancelFile()
  sending.value = true
  try {
    await chatStore.sendMessage(activeEventId.value, text, file)
    await nextTick()
    scrollToBottom()
  } catch (e) {
    console.error('Failed to send message:', e)
    alert(e.response?.data?.message || 'Gagal mengirim pesan.')
  } finally {
    sending.value = false
    inputEl.value?.focus()
  }
}

function scrollToBottom() {
  if (msgEl.value) {
    msgEl.value.scrollTop = msgEl.value.scrollHeight
  }
}

function onScroll() {
  if (!msgEl.value) return
  const el = msgEl.value
  userScrolledUp.value = el.scrollHeight - el.scrollTop - el.clientHeight > 60
}

watch(messages, async () => {
  if (!userScrolledUp.value) {
    await nextTick()
    scrollToBottom()
  }
}, { deep: true })

watch([activeEventId], ([evId]) => {
  if (evId) chatStore.clearUnread(evId)
})

onMounted(async () => {
  window.addEventListener('resize', updateWidth)
  
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }

  if (chatStore.eventGroups.length === 0) {
    await chatStore.loadEventGroups()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', updateWidth)
  if (activeEventId.value) {
    chatStore.unsubscribeFromEvent(activeEventId.value)
  }
})

const avatarColors = ['#0ea5e9','#0d9488','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6']
function avatarColor(name = '') {
  const idx = name.charCodeAt(0) % avatarColors.length
  return { background: avatarColors[idx] }
}

function roleTag(role) {
  const map = { superadmin: 'SA', project_manager: 'PM', staff: 'Staff' }
  return map[role] ?? role
}

function formatTime(iso) {
  const d = new Date(iso)
  const today = new Date()
  if (d.toDateString() === today.toDateString()) {
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  }
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.chat-page {
  width: 100%;
  height: calc(100vh - 140px);
  min-height: 480px;
  overflow: hidden;
}

.chat-container {
  display: flex;
  height: 100%;
  width: 100%;
}

/* ── Sidebar (Left Column) ── */
.fc-sidebar {
  width: 280px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--glass-border);
  background: rgba(0, 0, 0, 0.15);
  height: 100%;
}

.fc-sidebar-search {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-bottom: 1px solid var(--glass-border);
  flex-shrink: 0;
}
.fc-search-icon {
  color: var(--text-muted);
}
.search-input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  font-size: 13px;
  color: var(--text-primary);
  padding: 4px 0;
}
.search-input::placeholder {
  color: var(--text-muted);
}

.fc-sidebar-loading, .fc-sidebar-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  color: var(--text-muted);
  font-size: 13px;
  gap: 10px;
}

.fc-group-list {
  flex: 1;
  overflow-y: auto;
}

.fc-group-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.fc-group-item:hover {
  background: rgba(255, 255, 255, 0.05);
}
.fc-group-item.active {
  background: rgba(14, 165, 233, 0.15);
  border-left: 3px solid var(--primary);
}

.fc-group-avatar {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: white;
  font-size: 16px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.fc-group-info {
  flex: 1;
  min-width: 0;
}
.fc-group-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fc-group-last {
  font-size: 11px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 4px;
}

.fc-group-meta {
  flex-shrink: 0;
}
.fc-unread-badge {
  background: var(--danger);
  color: white;
  font-size: 10px;
  font-weight: 700;
  border-radius: 9999px;
  padding: 2px 6px;
  min-width: 18px;
  text-align: center;
  display: inline-block;
}

/* ── Chat Panel (Right Column) ── */
.fc-chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: rgba(0, 0, 0, 0.05);
  height: 100%;
}

.fc-no-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 14px;
  text-align: center;
  padding: 40px;
}
.fc-no-chat h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.fc-chat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--glass-border);
  background: rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 12px;
  margin-right: 8px;
  border-radius: 8px;
}

.fc-chat-event-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  color: white;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.fc-chat-event-info {
  flex: 1;
  min-width: 0;
}
.fc-chat-event-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fc-chat-online {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 3px;
}
.fc-online-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 6px var(--success);
}
.fc-avatars-row {
  display: flex;
  gap: -4px;
  margin-left: 8px;
}
.fc-mini-avatar {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  color: white;
  font-size: 8px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.15);
  margin-left: -4px;
}
.fc-mini-more {
  background: #475569 !important;
}

.fc-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.fc-msg-loading, .fc-msg-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--text-muted);
  font-size: 13px;
  gap: 12px;
}

.fc-msg {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  max-width: 70%;
}
.fc-msg--own {
  flex-direction: row-reverse;
  align-self: flex-end;
}

.fc-msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: white;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fc-msg-body {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.fc-msg-sender {
  font-size: 10px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 4px;
}
.fc-msg-role {
  background: rgba(14, 165, 233, 0.2);
  color: var(--primary-light);
  font-size: 8px;
  font-weight: 600;
  border-radius: 4px;
  padding: 1px 4px;
  text-transform: uppercase;
}
.fc-msg-bubble {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--glass-border);
  border-radius: 14px 14px 14px 4px;
  padding: 9px 13px;
  font-size: 13px;
  color: var(--text-primary);
  word-break: break-word;
  white-space: pre-wrap;
  line-height: 1.5;
}
.fc-msg--own .fc-msg-bubble {
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-color: transparent;
  border-radius: 14px 14px 4px 14px;
  color: white;
}
.fc-msg-time {
  font-size: 9px;
  color: var(--text-muted);
  padding: 0 4px;
  margin-top: 1px;
}
.fc-msg--own .fc-msg-time {
  text-align: right;
}

/* Document & Image Attachments Styling */
.msg-attachment {
  margin-top: 8px;
}
.msg-image-wrap {
  border-radius: 8px;
  overflow: hidden;
  max-width: 280px;
  max-height: 200px;
  cursor: zoom-in;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: opacity 0.2s;
}
.msg-image-wrap:hover {
  opacity: 0.9;
}
.msg-image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.msg-file-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  text-decoration: none;
  color: var(--text-primary);
  transition: background 0.2s;
  max-width: 320px;
}
.msg-file-card:hover {
  background: rgba(255, 255, 255, 0.05);
}
.file-card-icon {
  color: var(--primary-light);
  flex-shrink: 0;
}
.file-card-info {
  flex: 1;
  min-width: 0;
}
.file-card-name {
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.file-card-download {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 2px;
}
.file-card-arrow {
  color: var(--text-secondary);
  flex-shrink: 0;
}

/* File Preview Bar */
.chat-preview-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: rgba(14, 165, 233, 0.1);
  border-top: 1px solid var(--glass-border);
  flex-shrink: 0;
}
.preview-info {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  min-width: 0;
}
.preview-icon {
  color: var(--primary-light);
  flex-shrink: 0;
}
.preview-name {
  color: var(--text-primary);
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 250px;
}
.preview-size {
  color: var(--text-muted);
  flex-shrink: 0;
}
.preview-cancel-btn {
  background: none;
  border: none;
  color: var(--danger);
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: background 0.2s;
}
.preview-cancel-btn:hover {
  background: rgba(239, 68, 68, 0.15);
}

/* Input row */
.fc-input-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 20px;
  border-top: 1px solid var(--glass-border);
  background: rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}
.attachment-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--glass-border);
  border-radius: 10px;
  color: var(--text-secondary);
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}
.attachment-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--text-primary);
  transform: translateY(-1px);
}
.attachment-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.attachment-btn.disabled:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-secondary);
  transform: none;
}

.chat-input {
  flex: 1;
  background: rgba(255, 255, 255, 0.05) !important;
}

.send-btn {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  flex-shrink: 0;
}

/* Lightbox Modal */
.lightbox-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
  padding: 20px;
}
.lightbox-img {
  max-width: 100%;
  max-height: 90vh;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
}
.lightbox-close {
  position: absolute;
  top: 24px;
  right: 24px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.lightbox-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* ── Mobile Layout Overrides ── */
@media (max-width: 768px) {
  .chat-page {
    height: calc(100vh - 120px);
  }
  
  .fc-sidebar {
    width: 100%;
    border-right: none;
  }
  
  .fc-chat-panel {
    width: 100%;
  }

  .fc-msg {
    max-width: 85%;
  }
}

/* Scrollbar */
.fc-group-list::-webkit-scrollbar,
.fc-messages::-webkit-scrollbar {
  width: 4px;
}
.fc-group-list::-webkit-scrollbar-track,
.fc-messages::-webkit-scrollbar-track {
  background: transparent;
}
.fc-group-list::-webkit-scrollbar-thumb,
.fc-messages::-webkit-scrollbar-thumb {
  background: rgba(14, 165, 233, 0.25);
  border-radius: 2px;
}

/* Animations */
.spinner-icon {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
