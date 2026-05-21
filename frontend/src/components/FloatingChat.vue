<template>
  <div class="fc-wrapper">
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
          <button class="fc-close-btn" @click="isOpen = false">
            <XIcon :size="16" />
          </button>
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

              <!-- Messages -->
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
                      <div class="fc-msg-bubble">{{ msg.message }}</div>
                      <div class="fc-msg-time">{{ formatTime(msg.created_at) }}</div>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Input -->
              <form class="fc-input-row" @submit.prevent="send">
                <input
                  ref="inputEl"
                  v-model="inputText"
                  placeholder="Tulis pesan..."
                  maxlength="1000"
                  :disabled="sending"
                  autocomplete="off"
                  @keydown.enter.exact.prevent="send"
                />
                <button type="submit" class="fc-send-btn" :disabled="!inputText.trim() || sending">
                  <SendIcon :size="16" />
                </button>
              </form>
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { MessageCircleIcon, XIcon, SearchIcon, Loader2Icon, SendIcon } from 'lucide-vue-next'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'

const chatStore = useChatStore()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user)

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

// Total unread across all events
const totalUnread = computed(() =>
  Object.values(chatStore.unreadCounts).reduce((a, b) => a + b, 0)
)

const filteredGroups = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return chatStore.eventGroups
  return chatStore.eventGroups.filter(ev => ev.name.toLowerCase().includes(q))
})

// Access reactive maps directly — Vue tracks in-place mutations (push/splice)
const messages     = computed(() => chatStore.messagesByEvent[activeEventId.value] ?? [])
const onlineMembers = computed(() => chatStore.onlineByEvent[activeEventId.value]  ?? [])
const loadingGroups = computed(() => chatStore.loadingGroups)

function lastMessagePreview(eventId) {
  const msgs = chatStore.messagesByEvent[eventId]
  if (!msgs?.length) return 'Belum ada pesan'
  const last = msgs[msgs.length - 1]
  const prefix = last.user.id === currentUser.value?.id ? 'Kamu: ' : `${last.user.name.split(' ')[0]}: `
  return prefix + (last.message.length > 28 ? last.message.slice(0, 28) + '…' : last.message)
}

async function toggleOpen() {
  isOpen.value = !isOpen.value
  if (!isOpen.value) return
  // Load groups first time
  if (chatStore.eventGroups.length === 0) {
    await chatStore.loadEventGroups()
  }
  // Pre-subscribe to all visible events so messages arrive even before selecting
  chatStore.eventGroups.forEach(ev => {
    chatStore.subscribeToEvent(ev.id, currentUser.value?.id)
  })
}

async function selectEvent(ev) {
  if (activeEventId.value === ev.id) return
  activeEventId.value = ev.id
  activeEvent.value = ev
  chatStore.subscribeToEvent(ev.id, currentUser.value?.id)
  chatStore.clearUnread(ev.id)

  const hasCached = (chatStore.messagesByEvent[ev.id]?.length ?? 0) > 0

  if (hasCached) {
    // Show cached messages immediately, then refresh silently in the background
    await nextTick()
    scrollToBottom()
    inputEl.value?.focus()
    chatStore.loadHistory(ev.id)  // fire-and-forget refresh
  } else {
    // No cache yet — show spinner until loaded
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
  if (!text || !activeEventId.value) return
  inputText.value = ''
  sending.value = true
  try {
    await chatStore.sendMessage(activeEventId.value, text)
    await nextTick()
    scrollToBottom()
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

// Auto-scroll on new messages unless user scrolled up
watch(messages, async () => {
  if (!userScrolledUp.value) {
    await nextTick()
    scrollToBottom()
  }
}, { deep: true })

// Clear unread when the event is active and popup is open
watch([isOpen, activeEventId], ([open, evId]) => {
  if (open && evId) chatStore.clearUnread(evId)
})

// Re-subscribe after groups load (first open)
watch(() => chatStore.eventGroups, (groups) => {
  if (isOpen.value && groups.length) {
    groups.forEach(ev => chatStore.subscribeToEvent(ev.id, currentUser.value?.id))
  }
})

// Helpers
const avatarColors = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6']
function avatarColor(name = '') {
  const idx = name.charCodeAt(0) % avatarColors.length
  return { background: avatarColors[idx] }
}

function roleTag(role) {
  const map = { superadmin: 'SA', project_manager: 'PM', personnel: 'Staff' }
  return map[role] ?? ''
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

.fc-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(99,102,241,0.5);
  transition: transform 0.2s, box-shadow 0.2s;
  position: relative;
  flex-shrink: 0;
}
.fc-btn:hover { transform: scale(1.08); box-shadow: 0 12px 32px rgba(99,102,241,0.6); }
.fc-btn.open { background: linear-gradient(135deg, #4f46e5, #7c3aed); }

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
  border: 2px solid #1e1b4b;
  line-height: 1.2;
}

/* Popup window */
.fc-popup {
  position: absolute;
  bottom: 68px;
  right: 0;
  width: 720px;
  height: 520px;
  background: rgba(20, 18, 58, 0.97);
  backdrop-filter: blur(30px);
  border: 1px solid rgba(99,102,241,0.3);
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
  background: rgba(99,102,241,0.15);
  border-bottom: 1px solid rgba(99,102,241,0.2);
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
  color: #a5b4fc;
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
  border-right: 1px solid rgba(99,102,241,0.2);
  background: rgba(0,0,0,0.15);
}

.fc-sidebar-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(99,102,241,0.15);
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
.fc-group-item:hover { background: rgba(99,102,241,0.15); }
.fc-group-item.active { background: rgba(99,102,241,0.25); }

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
  background: #6366f1;
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
  border-bottom: 1px solid rgba(99,102,241,0.2);
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
  background: rgba(99,102,241,0.3);
  color: #a5b4fc;
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
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  border-radius: 12px 12px 4px 12px;
  border-color: transparent;
}
.fc-msg-time {
  font-size: 10px;
  color: #4b5563;
  padding: 0 4px;
}
.fc-msg--own .fc-msg-time { text-align: right; }

/* Input row */
.fc-input-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-top: 1px solid rgba(99,102,241,0.2);
  background: rgba(0,0,0,0.1);
  flex-shrink: 0;
}
.fc-input-row input {
  flex: 1;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(99,102,241,0.3);
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 12px;
  color: #e0e7ff;
  outline: none;
  transition: border-color 0.15s;
}
.fc-input-row input:focus { border-color: #6366f1; }
.fc-input-row input::placeholder { color: #4b5563; }
.fc-send-btn {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
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

/* Scrollbar styling */
.fc-group-list::-webkit-scrollbar,
.fc-messages::-webkit-scrollbar { width: 4px; }
.fc-group-list::-webkit-scrollbar-track,
.fc-messages::-webkit-scrollbar-track { background: transparent; }
.fc-group-list::-webkit-scrollbar-thumb,
.fc-messages::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 2px; }

/* Transition */
.fc-pop-enter-active { transition: opacity 0.2s, transform 0.2s; }
.fc-pop-leave-active { transition: opacity 0.15s, transform 0.15s; }
.fc-pop-enter-from { opacity: 0; transform: translateY(12px) scale(0.97); }
.fc-pop-leave-to { opacity: 0; transform: translateY(8px) scale(0.98); }

/* Spinner */
.spinner-icon { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
