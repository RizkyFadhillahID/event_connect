import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'
import * as LucideVue from 'lucide-vue-next'

const app = createApp(App)
app.use(createPinia())
app.use(router)

// Register Lucide icons globally
Object.entries(LucideVue).forEach(([name, component]) => {
  if (component.render) {
    app.component(name, component)
  }
})

app.mount('#app')
