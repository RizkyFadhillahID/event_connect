<template>
  <div class="chat-box">
    <!-- Header -->
    <div class="chat-header">
      <div class="chat-title">
        <MessageCircleIcon :size="18" />
        <span>Group Chat</span>
        <span v-if="unread > 0" class="badge">{{ unread }}</span>
      </div>
      <div class="online-list">
        <span class="online-dot" />
        <span class="online-count">{{ onlineMembers.length }} online</span>
        <div class="online-avatars">
          <div
            v-for="member in onlineMembers.slice(0, 5)"
            :key="member.id"
            class="avatar"
            :title="member.name"
          >
            {{ initials(member.name) }}
          </div>
          <div v-if="onlineMembers.length > 5" class="avatar avatar-more">
            +{{ onlineMembers.length - 5 }}
          </div>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div ref="scrollEl" class="chat-messages" @scroll="onScroll">
      <div v-if="messages.length === 0" class="chat-empty">
        Belum ada pesan. Mulai percakapan!
      </div>
      <div
        v-for="msg in messages"
        :key="msg.id"
        class="chat-message"
        :class="{ 'chat-message--own': msg.user.id === currentUser?.id }"
      >
        <div v-if="msg.user.id !== currentUser?.id" class="msg-avatar">
          {{ initials(msg.user.name) }}
        </div>
        <div class="msg-body">
          <div v-if="msg.user.id !== currentUser?.id" class="msg-sender">
            {{ msg.user.name }}
            <span class="msg-role">{{ roleLabel(msg.user.role) }}</span>
          </div>
          <div class="msg-bubble">{{ msg.message }}</div>
          <div class="msg-time">{{ formatTime(msg.created_at) }}</div>
        </div>
      </div>
    </div>

    <!-- Input -->
    <form class="chat-input" @submit.prevent="send">
      <input
        v-model="inputText"
        type="text"
        placeholder="Tulis pesan..."
        maxlength="1000"
        autocomplete="off"
        :disabled="sending"
      />
      <button type="submit" :disabled="!inputText.trim() || sending">
        <SendIcon :size="18" />
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  eventId: { type: [Number, String], required: true },
})

const chatStore = useChatStore()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user)

const inputText = ref('')
const sending = ref(false)
const scrollEl = ref(null)
const userScrolledUp = ref(false)

const messages = computed(() => chatStore.messagesByEvent[props.eventId] ?? [])
const onlineMembers = computed(() => chatStore.onlineByEvent[props.eventId] ?? [])
const unread = computed(() => chatStore.unreadCounts[props.eventId] ?? 0)

async function send() {
  const text = inputText.value.trim()
  if (!text) return
  inputText.value = ''
  sending.value = true
  try {
    await chatStore.sendMessage(props.eventId, text)
    await nextTick()
    scrollToBottom()
  } finally {
    sending.value = false
  }
}

function scrollToBottom() {
  if (scrollEl.value) {
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  }
}

function onScroll() {
  if (!scrollEl.value) return
  const el = scrollEl.value
  userScrolledUp.value = el.scrollHeight - el.scrollTop - el.clientHeight > 60
}

// Auto-scroll when new messages arrive (unless user scrolled up)
watch(messages, async () => {
  if (!userScrolledUp.value) {
    await nextTick()
    scrollToBottom()
  }
}, { deep: true })

// Clear unread when the component is visible
watch(unread, (val) => {
  if (val > 0) chatStore.clearUnread(props.eventId)
})

onMounted(async () => {
  chatStore.activeEventId = props.eventId
  chatStore.subscribeToEvent(props.eventId, currentUser.value?.id)
  await chatStore.loadHistory(props.eventId)
  await nextTick()
  scrollToBottom()
  chatStore.clearUnread(props.eventId)
})

onUnmounted(() => {
  chatStore.unsubscribeFromEvent(props.eventId)
  if (chatStore.activeEventId === props.eventId) {
    chatStore.activeEventId = null
  }
})

// Helpers
function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function roleLabel(role) {
  const map = { superadmin: 'SA', project_manager: 'PM', personnel: 'Staff' }
  return map[role] ?? role
}

function formatTime(iso) {
  const d = new Date(iso)
  const today = new Date()
  const isToday = d.toDateString() === today.toDateString()
  return isToday
    ? d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.chat-box {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 400px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  gap: 12px;
}

.chat-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: 14px;
  color: #111827;
}

.badge {
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  border-radius: 9999px;
  padding: 1px 6px;
  min-width: 18px;
  text-align: center;
}

.online-list {
  display: flex;
  align-items: center;
  gap: 6px;
}

.online-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
}

.online-count {
  font-size: 12px;
  color: #6b7280;
}

.online-avatars {
  display: flex;
  gap: 2px;
}

.avatar {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--primary);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: default;
  border: 2px solid #fff;
}

.avatar-more {
  background: #d1d5db;
  color: #374151;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.chat-empty {
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
  margin: auto;
}

.chat-message {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  max-width: 75%;
}

.chat-message--own {
  flex-direction: row-reverse;
  align-self: flex-end;
}

.msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.msg-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.msg-sender {
  font-size: 11px;
  color: #6b7280;
  display: flex;
  align-items: center;
  gap: 4px;
}

.msg-role {
  background: #e5e7eb;
  border-radius: 4px;
  padding: 0 4px;
  font-size: 10px;
  color: #374151;
}

.msg-bubble {
  background: #f3f4f6;
  border-radius: 12px 12px 12px 4px;
  padding: 8px 12px;
  font-size: 13px;
  color: #111827;
  word-break: break-word;
  white-space: pre-wrap;
  line-height: 1.5;
}

.chat-message--own .msg-bubble {
  background: var(--primary);
  color: #fff;
  border-radius: 12px 12px 4px 12px;
}

.msg-time {
  font-size: 10px;
  color: #9ca3af;
  padding: 0 4px;
}

.chat-message--own .msg-time {
  text-align: right;
}

.chat-input {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.chat-input input {
  flex: 1;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  outline: none;
  background: #fff;
  color: #111827;
  transition: border-color 0.15s;
}

.chat-input input:focus {
  border-color: var(--primary);
}

.chat-input button {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--primary);
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s;
}

.chat-input button:hover:not(:disabled) {
  background: #0284c7;
}

.chat-input button:disabled {
  background: rgba(14, 165, 233, 0.3);
  cursor: not-allowed;
}
</style>
