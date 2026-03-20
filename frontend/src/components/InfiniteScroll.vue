<template>
  <div class="infinite-scroll" ref="scrollContainer">
    <div class="infinite-scroll__content">
      <slot></slot>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="infinite-scroll__loading">
      <el-icon class="is-loading">
        <Loading />
      </el-icon>
      <span>{{ loadingText }}</span>
    </div>

    <!-- 无更多数据 -->
    <div v-else-if="!hasMore" class="infinite-scroll__nomore">
      <span>没有更多了</span>
    </div>

    <!-- 加载失败 -->
    <div v-else-if="error" class="infinite-scroll__error">
      <span>{{ error }}</span>
      <el-button text @click="retry">重试</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Loading } from '@element-plus/icons-vue'

interface Props {
  loading: boolean
  hasMore: boolean
  error?: string
  loadingText?: string
  threshold?: number
}

const props = withDefaults(defineProps<Props>(), {
  error: '',
  loadingText: '加载中...',
  threshold: 200,
})

const emit = defineEmits<{
  load: []
  retry: []
}>()

const scrollContainer = ref<HTMLElement>()
let observer: IntersectionObserver | null = null
const loadingElement = ref<HTMLElement>()

const retry = () => {
  emit('retry')
}

const setupIntersectionObserver = () => {
  if (!scrollContainer.value) return

  // 创建加载指示器元素
  const indicator = document.createElement('div')
  indicator.className = 'infinite-scroll__indicator'
  indicator.style.height = '1px'
  indicator.style.opacity = '0'
  scrollContainer.value.appendChild(indicator)
  loadingElement.value = indicator

  // 设置Intersection Observer
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && props.hasMore && !props.loading && !props.error) {
          emit('load')
        }
      })
    },
    {
      root: null,
      rootMargin: `${props.threshold}px`,
      threshold: 0.1,
    }
  )

  observer.observe(indicator)
}

const setupScrollListener = () => {
  if (!scrollContainer.value) return

  const handleScroll = () => {
    const container = scrollContainer.value!
    const { scrollTop, scrollHeight, clientHeight } = container

    // 距离底部threshold像素时触发加载
    if (
      scrollHeight - (scrollTop + clientHeight) < props.threshold &&
      props.hasMore &&
      !props.loading &&
      !props.error
    ) {
      emit('load')
    }
  }

  scrollContainer.value.addEventListener('scroll', handleScroll)
}

onMounted(() => {
  // 优先使用Intersection Observer API
  if ('IntersectionObserver' in window) {
    nextTick(() => {
      setupIntersectionObserver()
    })
  } else {
    // 降级方案：使用scroll事件
    setupScrollListener()
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
    observer = null
  }

  if (loadingElement.value && scrollContainer.value) {
    scrollContainer.value.removeChild(loadingElement.value)
  }
})

// 暴露重置方法
const reset = () => {
  if (observer) {
    observer.disconnect()
    nextTick(() => {
      setupIntersectionObserver()
    })
  }
}

defineExpose({
  reset,
})
</script>

<style scoped>
.infinite-scroll {
  height: 100%;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}

.infinite-scroll__content {
  min-height: 100%;
}

.infinite-scroll__loading,
.infinite-scroll__nomore,
.infinite-scroll__error {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  gap: 8px;
  color: #999;
  font-size: 14px;
}

.infinite-scroll__loading .el-icon {
  font-size: 20px;
}

.infinite-scroll__error {
  color: #f56c6c;
}
</style>
