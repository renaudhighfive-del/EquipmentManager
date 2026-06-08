import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './assets/index.css'

import App from './App.vue'
import router from './router'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'

const app = createApp(App)

// Directive v-click-outside : ferme les dropdowns au clic extérieur
app.directive('click-outside', {
  beforeMount(el, binding) {
    el._clickOutsideHandler = (event) => {
      if (!el.contains(event.target)) {
        binding.value(event);
      }
    };
    document.addEventListener('mousedown', el._clickOutsideHandler);
  },
  unmounted(el) {
    document.removeEventListener('mousedown', el._clickOutsideHandler);
  }
});

app.use(createPinia())
app.use(router)
app.use(ToastService)
app.use(ConfirmationService)
app.use(PrimeVue, {
    theme: {
        preset: Aura
    }
})

app.mount('#app')
