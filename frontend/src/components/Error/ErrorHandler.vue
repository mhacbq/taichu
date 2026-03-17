<template>
  <div v-if="show" class="error-container" :class="[type, { 'fullscreen': fullscreen }]">
    <!-- 网络错误 -->
    <template v-if="errorType === 'network'">
      <div class="error-icon">
        <el-icon :size="iconSize"><Connection /></el-icon>
      </div>
      <h3 class="error-title">网络连接失败</h3>
      <p class="error-desc">请检查您的网络连接，或稍后再试</p>
    </template>
    
    <!-- 404错误 -->
    <template v-else-if="errorType === '404'">
      <div class="error-icon">
        <el-icon :size="iconSize"><DocumentDelete /></el-icon>
      </div>
      <h3 class="error-title">页面不存在</h3>
      <p class="error-desc">您访问的页面可能已经删除或移动</p>
    </template>
    
    <!-- 服务器错误 -->
    <template v-else-if="errorType === 'server'">
      <div class="error-icon">
        <el-icon :size="iconSize"><Warning /></el-icon>
      </div>
      <h3 class="error-title">服务器开小差了</h3>
      <p class="error-desc">我们的工程师正在紧急修复中，请稍后再试</p>
    </template>
    
    <!-- 通用错误 -->
    <template v-else>
      <div class="error-icon">
        <el-icon :size="iconSize"><CircleClose /></el-icon>
      </div>
      <h3 class="error-title">{{ title || '出错了' }}</h3>
      <p class="error-desc">{{ message || '抱歉，发生了一些错误' }}</p>
    </template>
    
    <!-- 错误代码 -->
    <div v-if="code" class="error-code">错误代码: {{ code }}</div>
    
    <!-- 操作按钮 -->
    <div class="error-actions">
      <button 
        v-if="showRetry" 
        class="btn-primary"
        @click="handleRetry"
        :disabled="retrying"
      >
        <el-icon v-if="retrying" class="rotating"><Loading /></el-icon>
        <span>{{ retrying ? '重试中...' : '重新加载' }}</span>
      </button>
      <button 
        v-if="showHome" 
        class="btn-secondary"
        @click="goHome"
      >
        返回首页
      </button>
      <button 
        v-if="showBack" 
        class="btn-secondary"
        @click="goBack"
      >
        返回上页
      </button>
    </div>
    
    <!-- 技术支持 -->
    <div v-if="showSupport" class="error-support">
      <p>如果问题持续存在，请联系客服</p>
      <div class="support-actions">
        <a href="mailto:support@taichu.com" class="support-link">
          <el-icon><Message /></el-icon>
          发送邮件
        </a>
        <span class="divider">|</span>
        <span class="support-link" @click="copyError">
          <el-icon><CopyDocument /></el-icon>
          复制错误信息
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Connection,
  DocumentDelete,
  Warning,
  CircleClose,
  Loading,
  Message,
  CopyDocument
} from '@element-plus/icons-vue'

const props = defineProps({
  show: { type: Boolean, default: true },
  type: { type: String, default: 'default' },
  errorType: { type: String, default: 'default' },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  code: { type: [String, Number], default: '' },
  fullscreen: { type: Boolean, default: false },
  showRetry: { type: Boolean, default: true },
  showHome: { type: Boolean, default: true },
  showBack: { type: Boolean, default: false },
  showSupport: { type: Boolean, default: true },
  iconSize: { type: Number, default: 64 }
})

const emit = defineEmits(['retry', 'close'])
const router = useRouter()

const retrying = ref(false)

// 重试
const handleRetry = async () => {
  retrying.value = true
  try {
    await emit('retry')
  } finally {
    retrying.value = false
  }
}

// 返回首页
const goHome = () => {
  router.push('/')
}

// 返回上一页
const goBack = () => {
  router.back()
}

// 复制错误信息
const copyError = () => {
  const errorInfo = `
错误类型: ${props.errorType}
错误代码: ${props.code}
错误信息: ${props.message}
时间: ${new Date().toLocaleString()}
用户代理: ${navigator.userAgent}
  `.trim()
  
  navigator.clipboard.writeText(errorInfo).then(() => {
    ElMessage.success('错误信息已复制')
  }).catch(() => {
    ElMessage.error('复制失败')
  })
}
</script>

<style scoped>
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  text-align: center;
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.error-container.fullscreen {
  position: fixed;
  inset: 0;
  border-radius: 0;
  z-index: 9999;
  background: var(--bg-primary);
}

.error-icon {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  margin-bottom: 24px;
  animation: shake 0.5s ease-in-out;
}

.error-container.network .error-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.error-container.server .error-icon {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.error-title {
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.error-desc {
  font-size: 15px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 16px;
  max-width: 400px;
}

.error-code {
  font-size: 13px;
  color: var(--text-tertiary);
  background: rgba(255, 255, 255, 0.05);
  padding: 6px 16px;
  border-radius: 20px;
  margin-bottom: 24px;
  font-family: monospace;
}

.error-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.btn-primary,
.btn-secondary {
  padding: 12px 28px;
  border-radius: var(--radius-xl);
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
}

.btn-primary {
  background: var(--primary-gradient);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(184, 134, 11, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background: var(--bg-card);
  color: var(--text-primary);
}

.rotating {
  animation: rotate 1s linear infinite;
}

.error-support {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border-color);
}

.error-support p {
  font-size: 13px;
  color: var(--text-tertiary);
  margin-bottom: 12px;
}

.support-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.support-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: var(--primary-light);
  cursor: pointer;
  transition: opacity 0.3s;
}

.support-link:hover {
  opacity: 0.8;
}

.divider {
  color: var(--border-color);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 移除重复定义 */
</style>
