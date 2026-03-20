<template>
  <!-- 骨架屏组件 -->
  <div class="skeleton-loader" :class="{ animate: animate }">
    <!-- 卡片骨架 -->
    <template v-if="type === 'card'">
      <div class="skeleton-card">
        <div class="skeleton-header">
          <div class="skeleton-avatar" v-if="avatar"></div>
          <div class="skeleton-lines">
            <div class="skeleton-line" :style="{ width: titleWidth }"></div>
            <div class="skeleton-line short"></div>
          </div>
        </div>
        <div class="skeleton-content">
          <div class="skeleton-line" v-for="i in lines" :key="i"></div>
        </div>
      </div>
    </template>
    
    <!-- 列表骨架 -->
    <template v-else-if="type === 'list'">
      <div class="skeleton-list">
        <div class="skeleton-item" v-for="i in rows" :key="i">
          <div class="skeleton-avatar" v-if="avatar"></div>
          <div class="skeleton-lines">
            <div class="skeleton-line"></div>
            <div class="skeleton-line short"></div>
          </div>
        </div>
      </div>
    </template>
    
    <!-- 文本骨架 -->
    <template v-else-if="type === 'text'">
      <div class="skeleton-text">
        <div class="skeleton-line" v-for="i in lines" :key="i" :style="{ width: i === lines ? `${lastLineWidth}%` : '100%' }"></div>
      </div>
    </template>
    
    <!-- 图片骨架 -->
    <template v-else-if="type === 'image'">
      <div class="skeleton-image" :style="{ width, height, borderRadius }"></div>
    </template>
    
    <!-- 自定义骨架 -->
    <template v-else>
      <slot></slot>
    </template>
  </div>
</template>

<script setup>
defineProps({
  // 骨架类型: card, list, text, image, custom
  type: {
    type: String,
    default: 'card'
  },
  // 是否显示动画
  animate: {
    type: Boolean,
    default: true
  },
  // 是否显示头像
  avatar: {
    type: Boolean,
    default: false
  },
  // 文本行数
  lines: {
    type: Number,
    default: 3
  },
  // 列表行数
  rows: {
    type: Number,
    default: 5
  },
  // 标题宽度
  titleWidth: {
    type: String,
    default: '60%'
  },
  // 最后一行宽度
  lastLineWidth: {
    type: Number,
    default: 80
  },
  // 图片宽度
  width: {
    type: String,
    default: '100%'
  },
  // 图片高度
  height: {
    type: String,
    default: '200px'
  },
  // 圆角
  borderRadius: {
    type: String,
    default: '8px'
  }
})
</script>

<style scoped>
.skeleton-loader {
  width: 100%;
}

/* 通用骨架元素样式 */
.skeleton-line {
  height: 16px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  margin-bottom: 12px;
}

.skeleton-line.short {
  width: 40%;
}

.skeleton-line:last-child {
  margin-bottom: 0;
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  flex-shrink: 0;
}

/* 卡片骨架 */
.skeleton-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
}

.skeleton-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.skeleton-header .skeleton-lines {
  flex: 1;
}

.skeleton-header .skeleton-line {
  margin-bottom: 8px;
}

.skeleton-header .skeleton-line:last-child {
  width: 40%;
}

/* 列表骨架 */
.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.skeleton-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.skeleton-item .skeleton-lines {
  flex: 1;
}

/* 图片骨架 */
.skeleton-image {
  background: rgba(255, 255, 255, 0.1);
}

/* 闪烁动画 */
.skeleton-loader.animate .skeleton-line,
.skeleton-loader.animate .skeleton-avatar,
.skeleton-loader.animate .skeleton-image {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.05) 25%,
    rgba(255, 255, 255, 0.1) 50%,
    rgba(255, 255, 255, 0.05) 75%
  );
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

/* 脉冲动画 */
.skeleton-loader.animate.pulse .skeleton-line,
.skeleton-loader.animate.pulse .skeleton-avatar,
.skeleton-loader.animate.pulse .skeleton-image {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.4;
  }
  50% {
    opacity: 0.8;
  }
}
</style>
