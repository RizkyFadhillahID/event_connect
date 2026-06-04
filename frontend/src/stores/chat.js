import { defineStore } from 'pinia'
import { reactive, ref } from 'vue'
import api from '../api/axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance = null

function getEcho() {
  if (echoInstance) return echoInstance
  window.Pusher = Pusher
  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: (import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api') + '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: 'Bearer ' + (localStorage.getItem('token') ?? ''),
      },
    },
  })
  return echoInstance
}

export function resetEcho() {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}

function triggerBrowserNotification(data) {
  if (typeof window !== 'undefined' && 'Notification' in window) {
    if (Notification.permission === 'granted') {
      try {
        const notification = new Notification(`Pesan Baru: ${data.user.name}`, {
          body: data.message,
          icon: '/favicon.ico',
          tag: `event-chat-${data.event_id}`,
          requireInteraction: false
        })
        notification.onclick = () => {
          window.focus()
        }
      } catch (e) {
        console.error('Error triggering notification:', e)
      }
    }
  }
}

export const useChatStore = defineStore('chat', () => {
  // Use reactive() for nested maps — more reliable reactivity for in-place mutations
  const messagesByEvent = reactive({})   // { [eventId]: ChatMessage[] }
  const onlineByEvent  = reactive({})    // { [eventId]: Member[] }
  const unreadCounts   = reactive({})    // { [eventId]: number }
  const activeEventId  = ref(null)

  // Plain Set — not rendered, no reactivity needed
  const subscribedChannels = new Set()

  const eventGroups   = ref([])
  const loadingGroups = ref(false)

  function ensureEvent(eventId) {
    if (!messagesByEvent[eventId]) messagesByEvent[eventId] = []
    if (!onlineByEvent[eventId])   onlineByEvent[eventId]   = []
    if (unreadCounts[eventId] === undefined) unreadCounts[eventId] = 0
  }

  async function loadHistory(eventId) {
    ensureEvent(eventId)
    const res = await api.get(`/events/${eventId}/chat`)
    const incoming = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
    // Mutate in-place with splice so Vue keeps tracking the same array reference
    messagesByEvent[eventId].splice(0, messagesByEvent[eventId].length, ...incoming)
  }

  async function sendMessage(eventId, message, file = null) {
    let payload = null
    let headers = {}

    if (file) {
      const formData = new FormData()
      formData.append('message', message ?? '')
      formData.append('file', file)
      payload = formData
      headers = { 'Content-Type': 'multipart/form-data' }
    } else {
      payload = { message }
    }

    const res = await api.post(`/events/${eventId}/chat`, payload, { headers })
    ensureEvent(eventId)
    // Deduplicate: the WebSocket listener may also fire for our own message
    if (!messagesByEvent[eventId].find(m => m.id === res.data.id)) {
      messagesByEvent[eventId].push(res.data)
    }
  }

  let userNotificationChannel = null

  function subscribeToUserNotifications(currentUserId) {
    if (!currentUserId || userNotificationChannel) return

    userNotificationChannel = getEcho().private(`App.Models.User.${currentUserId}`)
      .listen('.message.sent', (data) => {
        const eventId = data.event_id
        ensureEvent(eventId)

        // Deduplicate
        if (!messagesByEvent[eventId].find(m => m.id === data.id)) {
          messagesByEvent[eventId].push(data)
          
          if (data.user?.id !== currentUserId) {
            if (activeEventId.value !== eventId) {
              unreadCounts[eventId] = (unreadCounts[eventId] || 0) + 1
              triggerBrowserNotification(data)
            }
          }
        }
      })
  }

  function unsubscribeFromUserNotifications(currentUserId) {
    if (userNotificationChannel) {
      getEcho().leave(`App.Models.User.${currentUserId}`)
      userNotificationChannel = null
    }
  }

  function subscribeToEvent(eventId, currentUserId) {
    if (subscribedChannels.has(eventId)) return
    subscribedChannels.add(eventId)
    ensureEvent(eventId)

    getEcho().join(`event.${eventId}`)
      .here((members) => {
        // Replace list — assign on reactive parent triggers dependency update
        onlineByEvent[eventId] = members
      })
      .joining((member) => {
        if (!onlineByEvent[eventId]) onlineByEvent[eventId] = []
        if (!onlineByEvent[eventId].find(m => m.id === member.id)) {
          onlineByEvent[eventId].push(member)
        }
      })
      .leaving((member) => {
        if (onlineByEvent[eventId]) {
          onlineByEvent[eventId] = onlineByEvent[eventId].filter(m => m.id !== member.id)
        }
      })
      .listen('.message.sent', (data) => {
        if (!messagesByEvent[eventId]) messagesByEvent[eventId] = []
        // Deduplicate: skip if already added by sendMessage()
        if (!messagesByEvent[eventId].find(m => m.id === data.id)) {
          messagesByEvent[eventId].push(data)
          if (data.user?.id !== currentUserId) {
            if (activeEventId.value !== eventId) {
              unreadCounts[eventId] = (unreadCounts[eventId] || 0) + 1
            }
          }
        }
      })
  }

  function unsubscribeFromEvent(eventId) {
    getEcho().leave(`event.${eventId}`)
    subscribedChannels.delete(eventId)
  }

  function clearUnread(eventId) {
    unreadCounts[eventId] = 0
  }

  async function loadEventGroups() {
    loadingGroups.value = true
    try {
      const res = await api.get('/events', { params: { per_page: 100 } })
      eventGroups.value = res.data.data ?? []
    } finally {
      loadingGroups.value = false
    }
  }

  return {
    messagesByEvent,
    onlineByEvent,
    unreadCounts,
    activeEventId,
    eventGroups,
    loadingGroups,
    loadHistory,
    sendMessage,
    subscribeToUserNotifications,
    unsubscribeFromUserNotifications,
    subscribeToEvent,
    unsubscribeFromEvent,
    clearUnread,
    loadEventGroups,
  }
})
