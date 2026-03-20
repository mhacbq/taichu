<template>
  <div class="async-state" :class="[`async-state--${status}`, customClass]">
    <!-- 加载中状态 -->
    <div v-if="status === 'loading'" class="state-content state-loading">
      <slot name="loading">
        <div class="loading-animation">
          <div class="loading-spinner"></div>
        </div>
        <h3 v-if="title">{{ title }}</h3>
        <p class="state-text">{{ loadingText }}</p>
        <WisdomText />
      </slot>
    </div>

    <!-- 错误状态 -->
    <div v-else-if="status === 'error'" class="state-content state-error">
      <slot name="error" :error="error">
        <el-icon class="state-icon error-icon"><Warning /></el-icon>
        <h3 v-if="title">{{ title }}</h3>
        <p class="state-text">{{ errorText || error?.message || '请求失败，请稍后重试' }}</p>
        <div class="state-actions" v-if="showRetry">
          <el-button type="primary" @click="$emit('retry')" :loading="retrying">
            {{ retryText }}
          </el-button>
        </div>
      </slot>
    </div>

    <!-- 空数据状态 -->
    <div v-else-if="status === 'empty'" class="state-content state-empty">
      <slot name="empty">
        <el-empty :description="emptyText" :image-size="120" />
        <WisdomText />
        <div class="state-actions" v-if="$slots.emptyAction">
          <slot name="emptyAction"></slot>
        </div>
      </slot>
    </div>

    <!-- 成功/就绪状态 -->
    <div v-else-if="status === 'success' || status === 'ready'" class="state-content state-success">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { Warning } from '@element-plus/icons-vue'
import WisdomText from './WisdomText.vue'

defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['idle', 'loading', 'error', 'empty', 'success', 'ready'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  loadingText: {
    type: String,
    default: '正在加载中...'
  },
  error: {
    type: [Error, Object, String],
    default: null
  },
  errorText: {
    type: String,
    default: ''
  },
  emptyText: {
    type: String,
    default: '暂无数据'
  },
  showRetry: {
    type: Boolean,
    default: true
  },
  retryText: {
    type: String,
    default: '重新加载'
  },
  retrying: {
    type: Boolean,
    default: false
  },
  customClass: {
    type: String,
    default: ''
  }
})

defineEmits(['retry'])
</script>

<style scoped>
.async-state {
  width: 100%;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.async-state--success,
.async-state--ready {
  min-height: auto;
  display: block;
}

.state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px 20px;
  width: 100%;
}

.state-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-icon {
  color: var(--danger-color);
}

.state-content h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
}

.state-text {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 0 0 24px 0;
  max-width: 400px;
  line-height: 1.5;
}

.state-actions {
  margin-top: 16px;
}

/* 加载动画 */
.loading-animation {
  margin-bottom: 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(212, 175, 55, 0.2);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
