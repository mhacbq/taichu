<template>
  <div class="skeleton-card" :style="{ width: width, height: height }">
    <div class="skeleton-header" v-if="showHeader">
      <div class="skeleton-avatar" v-if="showAvatar"></div>
      <div class="skeleton-title">
        <div class="skeleton-line" :style="{ width: titleWidth }"></div>
        <div class="skeleton-line short" v-if="showSubtitle"></div>
      </div>
    </div>
    <div class="skeleton-content">
      <div 
        v-for="i in rows" 
        :key="i" 
        class="skeleton-line"
        :style="{ width: i === rows ? lastRowWidth : '100%' }"
      ></div>
    </div>
    <div class="skeleton-action" v-if="showAction">
      <div class="skeleton-button"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  width: { type: String, default: '100%' },
  height: { type: String, default: 'auto' },
  rows: { type: Number, default: 3 },
  showHeader: { type: Boolean, default: true },
  showAvatar: { type: Boolean, default: false },
  showSubtitle: { type: Boolean, default: false },
  showAction: { type: Boolean, default: false },
  titleWidth: { type: String, default: '60%' },
  lastRowWidth: { type: String, default: '80%' }
})
</script>

<style scoped>
.skeleton-card {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.skeleton-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-title {
  flex: 1;
}

.skeleton-content {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-line {
  height: 16px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-line.short {
  width: 40%;
  height: 12px;
  margin-top: 8px;
}

.skeleton-action {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.skeleton-button {
  width: 80px;
  height: 36px;
  border-radius: 18px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* 暗黑模式 */
.dark .skeleton-card {
  background: rgba(30, 30, 40, 0.8);
}

.dark .skeleton-avatar,
.dark .skeleton-line,
.dark .skeleton-button {
  background: linear-gradient(90deg, #2a2a3a 25%, #3a3a4a 50%, #2a2a3a 75%);
  background-size: 200% 100%;
}
</style>
