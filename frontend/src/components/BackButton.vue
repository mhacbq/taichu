<template>
  <button class="back-button" type="button" @click="goBack" aria-label="返回上一页">
    <el-icon class="back-icon"><ArrowLeft /></el-icon>
    <span class="back-text">{{ text }}</span>
  </button>
</template>

<script setup>
import { ArrowLeft } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  text: {
    type: String,
    default: '返回'
  },
  fallback: {
    type: String,
    default: '/'
  }
})

const router = useRouter()

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push(props.fallback)
  }
}
</script>

<style scoped>
.back-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 30px;
  min-width: 30px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.86);
  border: 1px solid rgba(var(--primary-rgb), 0.14);
  border-radius: 999px;
  color: #7a5520;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06), 0 2px 6px rgba(var(--primary-rgb), 0.05);
  backdrop-filter: blur(18px);
  transition: background-color var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast), color var(--transition-fast);
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.96);
  border-color: rgba(var(--primary-rgb), 0.24);
  color: var(--primary-color);
  transform: translateX(-2px);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1), 0 8px 18px rgba(var(--primary-rgb), 0.08);
}

.back-button:active {
  transform: translateX(-1px) scale(0.99);
}

.back-button:focus-visible {
  outline: none;
  border-color: var(--border-focus);
  box-shadow: var(--focus-ring);
}

.back-icon {
  font-size: 14px;
}

.back-text {
  font-size: 13px;
  line-height: 1;
}

@media (max-width: 576px) {
  .back-button {
    padding: 0 13px;
    border-radius: 16px;
  }

  .back-text {
    display: none;
  }
}
</style>
