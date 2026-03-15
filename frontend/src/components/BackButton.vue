<template>
  <button class="back-button" @click="goBack">
    <span class="back-icon">←</span>
    <span class="back-text">{{ text }}</span>
  </button>
</template>

<script setup>
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
  gap: 8px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-button:hover {
  background: rgba(233, 69, 96, 0.2);
  border-color: rgba(233, 69, 96, 0.5);
  transform: translateX(-3px);
}

.back-icon {
  font-size: 16px;
}

.back-text {
  font-size: 14px;
}

@media (max-width: 768px) {
  .back-button {
    padding: 6px 12px;
  }
  
  .back-text {
    display: none;
  }
}
</style>
