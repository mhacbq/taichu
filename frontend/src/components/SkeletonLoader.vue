<template>
  <div class="skeleton-loader" :class="{ 'skeleton-pulse': animate }">
    <!-- 卡片骨架 -->
    <template v-if="type === 'card'">
      <div class="skeleton-card">
        <div class="skeleton-header">
          <div class="skeleton-avatar" :style="{ width: avatarSize, height: avatarSize }"></div>
          <div class="skeleton-lines">
            <div class="skeleton-line" :style="{ width: titleWidth }"></div>
            <div class="skeleton-line" style="width: 60%"></div>
          </div>
        </div>
        <div class="skeleton-body">
          <div class="skeleton-line" v-for="i in lines" :key="i" :style="{ width: `${Math.random() * 40 + 60}%` }"></div>
        </div>
      </div>
    </template>

    <!-- 列表骨架 -->
    <template v-else-if="type === 'list'">
      <div class="skeleton-list">
        <div class="skeleton-list-item" v-for="i in rows" :key="i">
          <div class="skeleton-circle" v-if="showIcon"></div>
          <div class="skeleton-content">
            <div class="skeleton-line" :style="{ width: `${Math.random() * 30 + 50}%` }"></div>
            <div class="skeleton-line short" style="width: 30%"></div>
          </div>
        </div>
      </div>
    </template>

    <!-- 统计骨架 -->
    <template v-else-if="type === 'stats'">
      <div class="skeleton-stats">
        <div class="skeleton-stat-item" v-for="i in 3" :key="i">
          <div class="skeleton-circle large"></div>
          <div class="skeleton-line" style="width: 60%"></div>
        </div>
      </div>
    </template>

    <!-- 文本骨架 -->
    <template v-else-if="type === 'text'">
      <div class="skeleton-text">
        <div class="skeleton-line" v-for="i in lines" :key="i" :style="{ width: i === lines ? `${Math.random() * 40 + 40}%` : '100%' }"></div>
      </div>
    </template>

    <!-- 图片骨架 -->
    <template v-else-if="type === 'image'">
      <div class="skeleton-image" :style="{ width, height }">
        <div class="skeleton-image-icon">🖼️</div>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'card',
    validator: (value) => ['card', 'list', 'stats', 'text', 'image'].includes(value)
  },
  lines: {
    type: Number,
    default: 3
  },
  rows: {
    type: Number,
    default: 5
  },
  avatarSize: {
    type: String,
    default: '50px'
  },
  titleWidth: {
    type: String,
    default: '70%'
  },
  width: {
    type: String,
    default: '100%'
  },
  height: {
    type: String,
    default: '200px'
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  animate: {
    type: Boolean,
    default: true
  }
})
</script>

<style scoped>
.skeleton-loader {
  --skeleton-bg: rgba(255, 255, 255, 0.1);
  --skeleton-shine: rgba(255, 255, 255, 0.2);
}

.skeleton-pulse .skeleton-line,
.skeleton-pulse .skeleton-circle,
.skeleton-pulse .skeleton-avatar,
.skeleton-pulse .skeleton-image {
  animation: skeleton-pulse 1.5s ease-in-out infinite;
}

@keyframes skeleton-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* 通用元素 */
.skeleton-line {
  height: 12px;
  background: var(--skeleton-bg);
  border-radius: 6px;
  margin-bottom: 10px;
}

.skeleton-line:last-child {
  margin-bottom: 0;
}

.skeleton-line.short {
  width: 40%;
}

.skeleton-circle {
  width: 40px;
  height: 40px;
  background: var(--skeleton-bg);
  border-radius: 50%;
  flex-shrink: 0;
}

.skeleton-circle.large {
  width: 60px;
  height: 60px;
}

/* 卡片骨架 */
.skeleton-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 15px;
  padding: 20px;
}

.skeleton-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.skeleton-avatar {
  background: var(--skeleton-bg);
  border-radius: 50%;
  flex-shrink: 0;
}

.skeleton-lines {
  flex: 1;
}

.skeleton-body .skeleton-line {
  margin-bottom: 12px;
}

/* 列表骨架 */
.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.skeleton-list-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
}

.skeleton-content {
  flex: 1;
}

.skeleton-content .skeleton-line {
  margin-bottom: 8px;
}

/* 统计骨架 */
.skeleton-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.skeleton-stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
}

/* 文本骨架 */
.skeleton-text {
  padding: 10px 0;
}

.skeleton-text .skeleton-line {
  margin-bottom: 15px;
  height: 14px;
}

/* 图片骨架 */
.skeleton-image {
  background: var(--skeleton-bg);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 150px;
}

.skeleton-image-icon {
  font-size: 40px;
  opacity: 0.3;
}

/* 加载动画效果 */
@keyframes skeleton-shine {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.skeleton-line,
.skeleton-circle,
.skeleton-avatar,
.skeleton-image {
  background: linear-gradient(
    90deg,
    var(--skeleton-bg) 25%,
    var(--skeleton-shine) 50%,
    var(--skeleton-bg) 75%
  );
  background-size: 200% 100%;
  animation: skeleton-shine 1.5s ease-in-out infinite;
}

@media (max-width: 768px) {
  .skeleton-stats {
    grid-template-columns: 1fr;
  }
  
  .skeleton-header {
    flex-direction: column;
    text-align: center;
  }
}
</style>
