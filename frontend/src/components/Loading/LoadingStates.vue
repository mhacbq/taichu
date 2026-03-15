<template>
  <div class="loading-states">
    <!-- 类型1: 旋转动画 -->
    <div v-if="type === 'spinner'" class="loading-spinner">
      <div class="spinner-ring">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
      <p v-if="text" class="loading-text">{{ text }}</p>
    </div>
    
    <!-- 类型2: 进度条 -->
    <div v-else-if="type === 'progress'" class="loading-progress">
      <div class="progress-container">
        <div class="progress-bar" :style="{ width: `${progress}%` }">
          <div class="progress-glow"></div>
        </div>
      </div>
      <div class="progress-info">
        <span class="progress-text">{{ text || '加载中...' }}</span>
        <span class="progress-percent">{{ Math.round(progress) }}%</span>
      </div>
    </div>
    
    <!-- 类型3: 骨架屏 -->
    <div v-else-if="type === 'skeleton'" class="loading-skeleton">
      <slot name="skeleton">
        <div class="default-skeleton">
          <div class="skeleton-header">
            <div class="skeleton-avatar"></div>
            <div class="skeleton-lines">
              <div class="skeleton-line" style="width: 60%"></div>
              <div class="skeleton-line" style="width: 40%"></div>
            </div>
          </div>
          <div class="skeleton-content">
            <div class="skeleton-line"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line" style="width: 80%"></div>
          </div>
        </div>
      </slot>
    </div>
    
    <!-- 类型4: 全屏加载 -->
    <div v-else-if="type === 'fullscreen'" class="loading-fullscreen">
      <div class="fullscreen-content">
        <div class="logo-animation">
          <div class="logo-circle"></div>
          <div class="logo-text">太初</div>
        </div>
        <div class="loading-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
        <p class="fullscreen-text">{{ text || '正在加载...' }}</p>
      </div>
    </div>
    
    <!-- 类型5: 按钮加载 -->
    <div v-else-if="type === 'button'" class="loading-button">
      <span class="btn-spinner"></span>
      <span>{{ text || '加载中' }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'spinner',
    validator: (value) => ['spinner', 'progress', 'skeleton', 'fullscreen', 'button'].includes(value)
  },
  text: {
    type: String,
    default: ''
  },
  progress: {
    type: Number,
    default: 0
  }
})
</script>

<style scoped>
/* 旋转动画 */
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner-ring {
  display: inline-block;
  position: relative;
  width: 64px;
  height: 64px;
}

.spinner-ring div {
  box-sizing: border-box;
  display: block;
  position: absolute;
  width: 51px;
  height: 51px;
  margin: 6px;
  border: 4px solid #667eea;
  border-radius: 50%;
  animation: spinner-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
  border-color: #667eea transparent transparent transparent;
}

.spinner-ring div:nth-child(1) { animation-delay: -0.45s; }
.spinner-ring div:nth-child(2) { animation-delay: -0.3s; }
.spinner-ring div:nth-child(3) { animation-delay: -0.15s; }

@keyframes spinner-ring {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #666;
  font-size: 14px;
}

/* 进度条 */
.loading-progress {
  width: 100%;
  max-width: 400px;
}

.progress-container {
  height: 8px;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px;
  transition: width 0.3s ease;
  position: relative;
  overflow: hidden;
}

.progress-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  animation: shimmer 1.5s infinite;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-text {
  font-size: 14px;
  color: #666;
}

.progress-percent {
  font-size: 14px;
  font-weight: 600;
  color: #667eea;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* 骨架屏 */
.loading-skeleton {
  background: white;
  border-radius: 16px;
  padding: 20px;
}

.default-skeleton {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton-header {
  display: flex;
  gap: 12px;
  align-items: center;
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

.skeleton-lines {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-content {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-line {
  height: 14px;
  border-radius: 7px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 全屏加载 */
.loading-fullscreen {
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.fullscreen-content {
  text-align: center;
}

.logo-animation {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 30px;
}

.logo-circle {
  position: absolute;
  inset: 0;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.logo-text {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: bold;
  color: white;
}

.loading-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 16px;
}

.loading-dots span {
  width: 10px;
  height: 10px;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: bounce 1.4s ease-in-out infinite both;
}

.loading-dots span:nth-child(1) { animation-delay: -0.32s; }
.loading-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

.fullscreen-text {
  color: rgba(255, 255, 255, 0.9);
  font-size: 16px;
}

/* 按钮加载 */
.loading-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #667eea;
  color: white;
  border-radius: 8px;
  font-size: 14px;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
</style>
