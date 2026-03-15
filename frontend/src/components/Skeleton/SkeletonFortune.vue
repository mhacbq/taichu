<template>
  <div class="skeleton-fortune">
    <!-- 运势分数骨架 -->
    <div class="skeleton-score-section">
      <div class="skeleton-circle"></div>
      <div class="skeleton-score-info">
        <div class="skeleton-line" style="width: 60%"></div>
        <div class="skeleton-line" style="width: 40%"></div>
      </div>
    </div>
    
    <!-- 运势详情骨架 -->
    <div class="skeleton-details">
      <div v-for="i in 5" :key="i" class="skeleton-detail-item">
        <div class="skeleton-icon"></div>
        <div class="skeleton-detail-content">
          <div class="skeleton-line" style="width: 30%"></div>
          <div class="skeleton-line" style="width: 80%"></div>
        </div>
        <div class="skeleton-progress"></div>
      </div>
    </div>
    
    <!-- 建议骨架 -->
    <div class="skeleton-advice">
      <div class="skeleton-line" style="width: 40%"></div>
      <div class="skeleton-line" style="width: 100%"></div>
      <div class="skeleton-line" style="width: 90%"></div>
      <div class="skeleton-line" style="width: 60%"></div>
    </div>
  </div>
</template>

<style scoped>
.skeleton-fortune {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.skeleton-score-section {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.skeleton-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-score-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-details {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.skeleton-detail-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.skeleton-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-detail-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.skeleton-progress {
  width: 60px;
  height: 8px;
  border-radius: 4px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-advice {
  padding: 16px;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-line {
  height: 14px;
  border-radius: 7px;
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

.dark .skeleton-fortune {
  background: rgba(30, 30, 40, 0.8);
}

.dark .skeleton-circle,
.dark .skeleton-icon,
.dark .skeleton-progress,
.dark .skeleton-line {
  background: linear-gradient(90deg, #2a2a3a 25%, #3a3a4a 50%, #2a2a3a 75%);
  background-size: 200% 100%;
}
</style>
