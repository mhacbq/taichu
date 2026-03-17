<template>
  <button class="back-button" type="button" @click="goBack">
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
  gap: 8px;
  min-height: 44px;
  min-width: 44px;
  padding: 10px 16px;
  background: var(--surface-raised);
  border: 1px solid var(--border-light);
  border-radius: 999px;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: background-color var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast), color var(--transition-fast);
}

.back-button:hover {
  background: var(--surface-hover);
  border-color: var(--primary-light-30);
  color: var(--primary-color);
  transform: translateX(-2px);
  box-shadow: var(--shadow-md);
}

.back-button:active {
  transform: translateX(-1px);
}

.back-button:focus-visible {
  outline: none;
  border-color: var(--border-focus);
  box-shadow: var(--focus-ring);
}

.back-icon {
  font-size: 16px;
}

.back-text {
  font-size: 14px;
  line-height: 1;
}

@media (max-width: 576px) {
  .back-button {
    padding: 10px 12px;
    border-radius: 14px;
  }

  .back-text {
    display: none;
  }
}
</style>
