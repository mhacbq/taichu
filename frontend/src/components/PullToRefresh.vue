<template>
  <div
    class="pull-to-refresh"
    @touchstart="handleTouchStart"
    @touchmove="handleTouchMove"
    @touchend="handleTouchEnd"
  >
    <div
      class="ptr-indicator"
      :style="indicatorStyle"
      :class="{ 'ptr-indicator--refreshing': refreshing }"
    >
      <div class="ptr-spinner" :class="{ 'ptr-spinner--spinning': refreshing }">
        <svg viewBox="0 0 24 24" fill="none">
          <path
            d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>
      </div>
      <span class="ptr-text">{{ indicatorText }}</span>
    </div>
    
    <div class="ptr-content" :style="contentStyle">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'PullToRefresh',
  props: {
    threshold: {
      type: Number,
      default: 80
    },
    maxPull: {
      type: Number,
      default: 120
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['refresh'],
  setup(props, { emit }) {
    const refreshing = ref(false)
    const pullDistance = ref(0)
    const startY = ref(0)
    const isPulling = ref(false)
    
    const indicatorText = computed(() => {
      if (refreshing.value) return '刷新中...'
      if (pullDistance.value >= props.threshold) return '松开刷新'
      return '下拉刷新'
    })
    
    const indicatorStyle = computed(() => {
      const opacity = Math.min(pullDistance.value / props.threshold, 1)
      const scale = Math.min(pullDistance.value / props.threshold, 1)
      return {
        transform: `translateY(${Math.max(0, pullDistance.value - 50)}px)`,
        opacity,
      }
    })
    
    const contentStyle = computed(() => {
      return {
        transform: `translateY(${pullDistance.value}px)`,
        transition: isPulling.value ? 'none' : 'transform 0.3s ease'
      }
    })
    
    const handleTouchStart = (e) => {
      if (props.disabled || refreshing.value) return
      
      // 只有在顶部才能触发下拉刷新
      const scrollTop = document.documentElement.scrollTop || document.body.scrollTop
      if (scrollTop > 0) return
      
      startY.value = e.touches[0].clientY
      isPulling.value = true
    }
    
    const handleTouchMove = (e) => {
      if (!isPulling.value || props.disabled || refreshing.value) return
      
      const currentY = e.touches[0].clientY
      const diff = currentY - startY.value
      
      if (diff > 0) {
        // 阻尼效果
        const damped = Math.min(diff * 0.6, props.maxPull)
        pullDistance.value = damped
        e.preventDefault()
      }
    }
    
    const handleTouchEnd = () => {
      if (!isPulling.value) return
      
      isPulling.value = false
      
      if (pullDistance.value >= props.threshold) {
        // 触发刷新
        refreshing.value = true
        pullDistance.value = props.threshold
        
        emit('refresh', () => {
          // 刷新完成回调
          refreshing.value = false
          pullDistance.value = 0
        })
      } else {
        // 未达到阈值，回弹
        pullDistance.value = 0
      }
    }
    
    return {
      refreshing,
      pullDistance,
      indicatorText,
      indicatorStyle,
      contentStyle,
      handleTouchStart,
      handleTouchMove,
      handleTouchEnd
    }
  }
}
</script>

<style scoped>
.pull-to-refresh {
  position: relative;
  overflow: hidden;
}

.ptr-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #999;
  font-size: 14px;
  z-index: 1;
}

.ptr-indicator--refreshing {
  color: #8B5CF6;
}

.ptr-spinner {
  width: 20px;
  height: 20px;
  transition: transform 0.3s;
}

.ptr-spinner svg {
  width: 100%;
  height: 100%;
}

.ptr-spinner--spinning {
  animation: spin 1s linear infinite;
}

.ptr-spinner--spinning svg {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.ptr-content {
  position: relative;
  background: white;
  min-height: 100vh;
}
</style>
