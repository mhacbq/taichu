<template>
  <slot v-if="!hasError"></slot>
  <div v-else class="error-boundary">
    <EmptyState
      type="error"
      :title="errorTitle"
      :description="errorDescription"
      :action-text="showRetry ? '重新加载' : ''"
      @action="handleRetry"
    >
      <template #extra>
        <div class="error-boundary__actions">
          <button v-if="showRetry" class="btn btn-primary" @click="handleRetry">
            <svg class="icon" viewBox="0 0 24 24" fill="none">
              <path d="M23 4v6h-6M1 20v-6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            重新加载
          </button>
          <button class="btn btn-secondary" @click="handleReport">
            反馈问题
          </button>
        </div>
      </template>
    </EmptyState>
    
    <!-- 开发环境显示错误详情 -->
    <details v-if="isDev" class="error-details">
      <summary>错误详情（仅开发环境可见）</summary>
      <pre>{{ errorInfo }}</pre>
    </details>
  </div>
</template>

<script>
import { ref, onErrorCaptured, onMounted } from 'vue'
import EmptyState from './EmptyState.vue'

export default {
  name: 'ErrorBoundary',
  components: { EmptyState },
  props: {
    title: {
      type: String,
      default: '出错了'
    },
    description: {
      type: String,
      default: '页面加载出现问题，请稍后重试'
    },
    showRetry: {
      type: Boolean,
      default: true
    },
    onRetry: {
      type: Function,
      default: null
    }
  },
  emits: ['error', 'retry'],
  setup(props, { emit }) {
    const hasError = ref(false)
    const error = ref(null)
    const errorInfo = ref('')
    const isDev = ref(import.meta.env.DEV)
    
    const errorTitle = ref(props.title)
    const errorDescription = ref(props.description)
    
    onErrorCaptured((err, instance, info) => {
      hasError.value = true
      error.value = err
      errorInfo.value = `${err.message}\n\n${err.stack || ''}`
      
      // 上报错误
      reportError(err, info)
      
      emit('error', { error: err, info })
      
      return false // 阻止错误继续传播
    })
    
    const reportError = (err, info) => {
      // 发送到错误监控服务
      if (window.$errorReporter) {
        window.$errorReporter.captureException(err, {
          tags: { component: info },
          extra: { 
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
          }
        })
      }
      
      // 控制台输出
      console.error('[ErrorBoundary]', err, info)
    }
    
    const handleRetry = () => {
      hasError.value = false
      error.value = null
      
      if (props.onRetry) {
        props.onRetry()
      }
      
      emit('retry')
    }
    
    const handleReport = () => {
      // 打开反馈页面或弹窗
      const feedbackUrl = '/feedback?type=error'
      window.open(feedbackUrl, '_blank')
    }
    
    // 全局错误监听
    onMounted(() => {
      window.addEventListener('error', (event) => {
        if (!hasError.value) {
          reportError(event.error, 'global')
        }
      })
      
      window.addEventListener('unhandledrejection', (event) => {
        if (!hasError.value) {
          reportError(event.reason, 'promise')
        }
      })
    })
    
    return {
      hasError,
      errorTitle,
      errorDescription,
      errorInfo,
      isDev,
      handleRetry,
      handleReport
    }
  }
}
</script>

<style scoped>
.error-boundary {
  padding: 40px 20px;
}

.error-boundary__actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 16px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.btn-secondary {
  background: #f5f5f5;
  color: #666;
}

.btn-secondary:hover {
  background: #e8e8e8;
}

.icon {
  width: 16px;
  height: 16px;
}

.error-details {
  margin-top: 40px;
  padding: 20px;
  background: #f8f8f8;
  border-radius: 8px;
}

.error-details summary {
  cursor: pointer;
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.error-details pre {
  font-size: 12px;
  color: #333;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
  background: #fff;
  padding: 16px;
  border-radius: 4px;
  border: 1px solid #e8e8e8;
}
</style>
