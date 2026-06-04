<template>
  <div class="fc-wrapper" v-if="!isChatRoute">
    <!-- Floating Button -->
    <button class="fc-btn" :class="{ open: isOpen }" @click="toggleOpen" title="Event Group Chat">
      <MessageCircleIcon v-if="!isOpen" :size="24" />
      <XIcon v-else :size="24" />
      <span v-if="totalUnread > 0" class="fc-btn-badge">{{ totalUnread > 99 ? '99+' : totalUnread }}</span>
    </button>

    <!-- Popup Window -->
    <Transition name="fc-pop">
      <div v-if="isOpen" class="fc-popup">
        <!-- Popup Header -->
        <div class="fc-popup-header">
          <div class="fc-popup-title">
            <MessageCircleIcon :size="16" />
            Event Group Chat
          </div>
          <div style="display:flex; align-items:center; gap:8px">
            <button class="fc-expand-btn" @click="goToFullChat" title="Buka Halaman Penuh" style="background: rgba(255,255,255,0.1); border: none; border-radius: 8px; color: #38bdf8; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s;">
              <Maximize2Icon :size="16" />
            </button>
            <button class="fc-close-btn" @click="isOpen = false">
              <XIcon :size="16" />
            </button>
          </div>
        </div>

        <!-- Popup Body: left sidebar + right chat -->
        <div class="fc-popup-body">
          <!-- Left: Group list -->
          <div class="fc-sidebar">
            <div class="fc-sidebar-search">
              <SearchIcon :size="14" class="fc-search-icon" />
              <input v-model="search" placeholder="Cari event..." autocomplete="off" />
            </div>

            <div v-if="loadingGroups" class="fc-sidebar-loading">
              <Loader2Icon :size="18" class="spinner-icon" />
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

          <!-- Right: Chat panel -->
          <div class="fc-chat-panel">
            <!-- No event selected -->
            <div v-if="!activeEventId" class="fc-no-chat">
              <MessageCircleIcon :size="40" style="opacity:0.3;margin-bottom:12px" />
              <p>Pilih grup event<br>untuk mulai chat</p>
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
                  <Loader2Icon :size="20" class="spinner-icon" />
                </div>
                <div v-else-if="messages.length === 0" class="fc-msg-empty">
                  Belum ada pesan. Mulai percakapan!
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
                            <FileTextIcon :size="18" class="file-card-icon" />
                            <div class="file-card-info">
                              <div class="file-card-name">{{ msg.file_name }}</div>
                              <div class="file-card-download">Unduh Lampiran</div>
                            </div>
                            <DownloadIcon :size="14" class="file-card-arrow" />
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
                  <ImageIcon v-if="selectedFileType === 'image'" :size="14" class="preview-icon" />
                  <FileIcon v-else :size="14" class="preview-icon" />
                  <span class="preview-name" :title="selectedFile.name">{{ selectedFile.name }}</span>
                  <span class="preview-size">({{ formatSize(selectedFile.size) }})</span>
                </div>
                <button type="button" class="preview-cancel-btn" @click="cancelFile" title="Batalkan unggahan">
                  <XIcon :size="12" />
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
                  <PaperclipIcon :size="16" />
                </button>
                <button 
                  type="button" 
                  class="attachment-btn" 
                  :class="{ disabled: authStore.organization?.plan === 'free' }"
                  @click="triggerImageInput" 
                  title="Kirim Foto / Kamera"
                >
                  <CameraIcon :size="16" />
                </button>

                <!-- Text input -->
                <input
                  ref="inputEl"
                  v-model="inputText"
                  placeholder="Tulis pesan..."
                  maxlength="1000"
                  :disabled="sending"
                  autocomplete="off"
                  @keydown.enter.exact.prevent="send"
                />
                
                <button type="submit" class="fc-send-btn" :disabled="(!inputText.trim() && !selectedFile) || sending">
                  <SendIcon :size="16" v-if="!sending" />
                  <Loader2Icon :size="16" class="spinner-icon" v-else />
                </button>
              </form>
            </template>
          </div>
        </div>
      </div>
    </Transition>

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
  MessageCircleIcon, XIcon, SearchIcon, Loader2Icon, SendIcon,
  PaperclipIcon, CameraIcon, ImageIcon, FileIcon, FileTextIcon, DownloadIcon,
  Maximize2Icon
} from 'lucide-vue-next'
import { useRoute, useRouter } from 'vue-router'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'

const chatStore = useChatStore()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user)
const route = useRoute()
const router = useRouter()

const isOpen = ref(false)
const search = ref('')
const activeEventId = ref(null)
const activeEvent = ref(null)
const inputText = ref('')
const sending = ref(false)
const loadingHistory = ref(false)
const msgEl = ref(null)
const inputEl = ref(null)
const userScrolledUp = ref(false)
const isChatRoute = computed(() => route.path === '/chat')

// File attachment references & states
const fileInput = ref(null)
const imageInput = ref(null)
const selectedFile = ref(null)
const selectedFileType = ref(null)
const lightboxUrl = ref(null)

// Total unread across all events
const totalUnread = computed(() =>
  Object.values(chatStore.unreadCounts).reduce((a, b) => a + b, 0)
)

const filteredGroups = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return chatStore.eventGroups
  return chatStore.eventGroups.filter(ev => ev.name.toLowerCase().includes(q))
})

const messages     = computed(() => chatStore.messagesByEvent[activeEventId.value] ?? [])
const onlineMembers = computed(() => chatStore.onlineByEvent[activeEventId.value]  ?? [])
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

watch(() => currentUser.value?.id, (newId, oldId) => {
  if (oldId) {
    chatStore.unsubscribeFromUserNotifications(oldId)
  }
  if (newId) {
    chatStore.subscribeToUserNotifications(newId)
  }
}, { immediate: true })

async function toggleOpen() {
  isOpen.value = !isOpen.value
  if (!isOpen.value) {
    cancelFile()
    return
  }
  
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }

  if (chatStore.eventGroups.length === 0) {
    await chatStore.loadEventGroups()
  }
}

function goToFullChat() {
  isOpen.value = false
  router.push('/chat')
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

  if (file.size > 10 * 1024 * 1024) {
    alert('Ukuran berkas maksimal adalah 10 MB.')
    return
  }

  selectedFile.value = file
  selectedFileType.value = file.type.startsWith('image/') ? 'image' : 'file'

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

watch([isOpen, activeEventId], ([open, evId]) => {
  if (open && evId) chatStore.clearUnread(evId)
})

watch(isOpen, (open) => {
  if (!open && activeEventId.value) {
    chatStore.unsubscribeFromEvent(activeEventId.value)
    activeEventId.value = null
    chatStore.activeEventId = null
    activeEvent.value = null
    cancelFile()
  }
})

// Helpers
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
/* Floating button */
.fc-wrapper {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
}

@media (max-width: 768px) {
  .fc-wrapper {
    display: none !important;
  }
}

.fc-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(14,165,233,0.4);
  transition: transform 0.2s, box-shadow 0.2s;
  position: relative;
  flex-shrink: 0;
}
.fc-btn:hover { transform: scale(1.08); box-shadow: 0 12px 32px rgba(14,165,233,0.5); }
.fc-btn.open { background: linear-gradient(135deg, var(--primary-light), var(--secondary)); }

.fc-btn-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  border-radius: 9999px;
  padding: 2px 5px;
  min-width: 18px;
  text-align: center;
  border: 2px solid #0f172a;
  line-height: 1.2;
}

/* Popup window */
.fc-popup {
  position: absolute;
  bottom: 68px;
  right: 0;
  width: 720px;
  height: 520px;
  background: rgba(15, 23, 42, 0.97);
  backdrop-filter: blur(30px);
  border: 1px solid rgba(14,165,233,0.3);
  border-radius: 20px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.6);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Popup header */
.fc-popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  background: rgba(14,165,233,0.15);
  border-bottom: 1px solid rgba(14,165,233,0.2);
  flex-shrink: 0;
}
.fc-popup-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 14px;
  color: #e0e7ff;
}
.fc-close-btn {
  background: rgba(255,255,255,0.1);
  border: none;
  border-radius: 8px;
  color: #38bdf8;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
}
.fc-close-btn:hover { background: rgba(255,255,255,0.2); }

/* Popup body */
.fc-popup-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* ── Left Sidebar ── */
.fc-sidebar {
  width: 230px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(14,165,233,0.2);
  background: rgba(0,0,0,0.15);
}

.fc-sidebar-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(14,165,233,0.15);
  flex-shrink: 0;
}
.fc-search-icon { color: #6b7280; flex-shrink: 0; }
.fc-sidebar-search input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  font-size: 12px;
  color: #e0e7ff;
}
.fc-sidebar-search input::placeholder { color: #4b5563; }

.fc-sidebar-loading, .fc-sidebar-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 12px;
  color: #6b7280;
  font-size: 12px;
  text-align: center;
}

.fc-group-list {
  flex: 1;
  overflow-y: auto;
}

.fc-group-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.fc-group-item:hover { background: rgba(14,165,233,0.15); }
.fc-group-item.active { background: rgba(14,165,233,0.25); }

.fc-group-avatar {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fc-group-info {
  flex: 1;
  min-width: 0;
}
.fc-group-name {
  font-size: 12px;
  font-weight: 600;
  color: #e0e7ff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fc-group-last {
  font-size: 11px;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

.fc-group-meta { flex-shrink: 0; }
.fc-unread-badge {
  background: var(--primary);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  border-radius: 9999px;
  padding: 2px 6px;
  min-width: 18px;
  text-align: center;
  display: inline-block;
}

/* ── Right Chat Panel ── */
.fc-chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.fc-no-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #4b5563;
  font-size: 13px;
  text-align: center;
  line-height: 1.6;
}

.fc-chat-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(14,165,233,0.2);
  background: rgba(0,0,0,0.1);
  flex-shrink: 0;
}
.fc-chat-event-avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.fc-chat-event-info { flex: 1; min-width: 0; }
.fc-chat-event-name {
  font-size: 13px;
  font-weight: 600;
  color: #e0e7ff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fc-chat-online {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: #6b7280;
  margin-top: 2px;
}
.fc-online-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
}
.fc-avatars-row {
  display: flex;
  gap: 2px;
  margin-left: 4px;
}
.fc-mini-avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,0.2);
}
.fc-mini-more { background: #4b5563 !important; }

.fc-messages {
  flex: 1;
  overflow-y: auto;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.fc-msg-loading, .fc-msg-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #6b7280;
  font-size: 12px;
  text-align: center;
}

.fc-msg {
  display: flex;
  align-items: flex-end;
  gap: 7px;
  max-width: 75%;
}
.fc-msg--own {
  flex-direction: row-reverse;
  align-self: flex-end;
}

.fc-msg-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.fc-msg-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.fc-msg-sender {
  font-size: 10px;
  color: #6b7280;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 0 4px;
}
.fc-msg-role {
  background: rgba(14,165,233,0.3);
  color: #38bdf8;
  font-size: 9px;
  border-radius: 3px;
  padding: 0 4px;
}
.fc-msg-bubble {
  background: rgba(255,255,255,0.08);
  border-radius: 12px 12px 12px 4px;
  padding: 7px 11px;
  font-size: 12px;
  color: #e0e7ff;
  word-break: break-word;
  white-space: pre-wrap;
  line-height: 1.5;
  border: 1px solid rgba(255,255,255,0.07);
}
.fc-msg--own .fc-msg-bubble {
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border-radius: 12px 12px 4px 12px;
  border-color: transparent;
}
.fc-msg-time {
  font-size: 10px;
  color: #4b5563;
  padding: 0 4px;
}
.fc-msg--own .fc-msg-time { text-align: right; }

/* Document & Image Attachments Styling */
.msg-attachment {
  margin-top: 6px;
}
.msg-image-wrap {
  border-radius: 6px;
  overflow: hidden;
  max-width: 220px;
  max-height: 150px;
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
  gap: 10px;
  padding: 8px 12px;
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  text-decoration: none;
  color: var(--text-primary);
  transition: background 0.2s;
  max-width: 240px;
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
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.file-card-download {
  font-size: 9px;
  color: var(--text-muted);
  margin-top: 1px;
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
  padding: 8px 12px;
  background: rgba(14, 165, 233, 0.1);
  border-top: 1px solid var(--glass-border);
  flex-shrink: 0;
}
.preview-info {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
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
  max-width: 180px;
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
  padding: 3px;
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
  gap: 6px;
  padding: 10px 14px;
  border-top: 1px solid rgba(14,165,233,0.2);
  background: rgba(0,0,0,0.1);
  flex-shrink: 0;
}
.attachment-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--glass-border);
  border-radius: 8px;
  color: var(--text-secondary);
  width: 32px;
  height: 32px;
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

.fc-input-row input {
  flex: 1;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(14,165,233,0.3);
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 12px;
  color: #e0e7ff;
  outline: none;
  transition: border-color 0.15s;
}
.fc-input-row input:focus { border-color: var(--primary); }
.fc-input-row input::placeholder { color: #4b5563; }

.fc-send-btn {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: opacity 0.15s;
}
.fc-send-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.fc-send-btn:not(:disabled):hover { opacity: 0.85; }

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

/* Scrollbar styling */
.fc-group-list::-webkit-scrollbar,
.fc-messages::-webkit-scrollbar { width: 4px; }
.fc-group-list::-webkit-scrollbar-track,
.fc-messages::-webkit-scrollbar-track { background: transparent; }
.fc-group-list::-webkit-scrollbar-thumb,
.fc-messages::-webkit-scrollbar-thumb { background: rgba(14,165,233,0.3); border-radius: 2px; }

/* Transition */
.fc-pop-enter-active { transition: opacity 0.2s, transform 0.2s; }
.fc-pop-leave-active { transition: opacity 0.15s, transform 0.15s; }
.fc-pop-enter-from { opacity: 0; transform: translateY(12px) scale(0.97); }
.fc-pop-leave-to { opacity: 0; transform: translateY(8px) scale(0.98); }

/* Spinner */
.spinner-icon { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
