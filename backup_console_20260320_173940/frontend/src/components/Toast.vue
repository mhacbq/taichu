<template>
  <Teleport to="body">
    <TransitionGroup name="toast" tag="div" class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="[`toast--${toast.type}`, toast.position]"
        @click="remove(toast.id)"
      >
        <div class="toast__icon">
          <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M8 12l3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else-if="toast.type === 'error'" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else-if="toast.type === 'warning'" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L2 20h20L12 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <path d="M12 9v6M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 7v6M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="toast__content">
          <div v-if="toast.title" class="toast__title">{{ toast.title }}</div>
          <div class="toast__message">{{ toast.message }}</div>
        </div>
        <button class="toast__close" @click.stop="remove(toast.id)">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
          </svg>
        </button>
        <div class="toast__progress" v-if="toast.duration > 0">
          <div
            class="toast__progress-bar"
            :style="{ animationDuration: toast.duration + 'ms' }"
          ></div>
        </div>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script>
import { ref, onMounted } from 'vue'

const toasts = ref([])
let id = 0

export function useToast() {
  const add = (options) => {
    const toast = {
      id: ++id,
      type: options.type || 'info',
      title: options.title || '',
      message: options.message,
      duration: options.duration !== undefined ? options.duration : 3000,
      position: options.position || 'top-right',
      onClose: options.onClose,
    }
    
    toasts.value.push(toast)
    
    if (toast.duration > 0) {
      setTimeout(() => {
        remove(toast.id)
      }, toast.duration)
    }
    
    return toast.id
  }
  
  const remove = (toastId) => {
    const index = toasts.value.findIndex(t => t.id === toastId)
    if (index > -1) {
      const toast = toasts.value[index]
      if (toast.onClose) toast.onClose()
      toasts.value.splice(index, 1)
    }
  }
  
  const success = (message, options = {}) => add({ type: 'success', message, ...options })
  const error = (message, options = {}) => add({ type: 'error', message, ...options })
  const warning = (message, options = {}) => add({ type: 'warning', message, ...options })
  const info = (message, options = {}) => add({ type: 'info', message, ...options })
  
  return { add, remove, success, error, warning, info }
}

export default {
  name: 'Toast',
  setup() {
    return { toasts, remove }
  }
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 9999;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  margin: 8px;
  min-width: 300px;
  max-width: 400px;
  pointer-events: auto;
  position: relative;
  overflow: hidden;
}

.toast--success {
  border-left: 4px solid var(--success-color);
}

.toast--error {
  border-left: 4px solid var(--danger-color);
}

.toast--warning {
  border-left: 4px solid var(--warning-color);
}

.toast--info {
  border-left: 4px solid var(--info-color);
}

.toast__icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.toast--success .toast__icon { color: var(--success-color); }
.toast--error .toast__icon { color: var(--danger-color); }
.toast--warning .toast__icon { color: var(--warning-color); }
.toast--info .toast__icon { color: var(--info-color); }

.toast__icon svg {
  width: 100%;
  height: 100%;
}

.toast__content {
  flex: 1;
  min-width: 0;
}

.toast__title {
  font-weight: 600;
  font-size: 14px;
  color: #1a1a1a;
  margin-bottom: 4px;
}

.toast__message {
  font-size: 14px;
  color: #666;
  line-height: 1.5;
}

.toast__close {
  width: 20px;
  height: 20px;
  padding: 0;
  border: none;
  background: none;
  color: #999;
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.2s;
}

.toast__close:hover {
  color: #666;
}

.toast__close svg {
  width: 100%;
  height: 100%;
}

.toast__progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0, 0, 0, 0.05);
}

.toast__progress-bar {
  height: 100%;
  background: currentColor;
  opacity: 0.3;
  animation: progress linear forwards;
}

@keyframes progress {
  from { width: 100%; }
  to { width: 0%; }
}

/* 位置 */
.toast-container .top-right { position: fixed; top: 20px; right: 20px; }
.toast-container .top-left { position: fixed; top: 20px; left: 20px; }
.toast-container .top-center { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); }
.toast-container .bottom-right { position: fixed; bottom: 20px; right: 20px; }
.toast-container .bottom-left { position: fixed; bottom: 20px; left: 20px; }
.toast-container .bottom-center { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); }

/* 动画 */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

/* 移动端适配 */
@media (max-width: 768px) {
  .toast {
    min-width: auto;
    max-width: calc(100vw - 32px);
    margin: 8px 16px;
  }
  
  .toast-container .top-right,
  .toast-container .top-left,
  .toast-container .top-center {
    left: 0;
    right: 0;
    transform: none;
  }
  
  .toast-container .bottom-right,
  .toast-container .bottom-left,
  .toast-container .bottom-center {
    left: 0;
    right: 0;
    transform: none;
  }
}
</style>
