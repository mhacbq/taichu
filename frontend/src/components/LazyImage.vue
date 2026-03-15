<template>
  <div class="lazy-image-wrapper" :style="{ width: width, height: height }">
    <!-- 占位骨架 -->
    <div v-if="!loaded" class="image-skeleton" :style="{ borderRadius: radius }">
      <div class="shimmer"></div>
    </div>
    
    <!-- 实际图片 -->
    <img
      ref="imageRef"
      :src="currentSrc"
      :alt="alt"
      :style="{ 
        borderRadius: radius,
        opacity: loaded ? 1 : 0,
        transform: loaded ? 'scale(1)' : 'scale(1.05)'
      }"
      @load="onLoad"
      @error="onError"
    />
    
    <!-- 错误状态 -->
    <div v-if="error" class="image-error" :style="{ borderRadius: radius }">
      <el-icon :size="40"><Picture /></el-icon>
      <span>加载失败</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Picture } from '@element-plus/icons-vue'

const props = defineProps({
  src: { type: String, required: true },
  alt: { type: String, default: '' },
  width: { type: String, default: '100%' },
  height: { type: String, default: '200px' },
  radius: { type: String, default: '12px' },
  placeholder: { type: String, default: '' },
  threshold: { type: Number, default: 0.1 },
  useWebP: { type: Boolean, default: true }
})

const emit = defineEmits(['load', 'error'])

const imageRef = ref(null)
const loaded = ref(false)
const error = ref(false)
const observer = ref(null)

// 检测浏览器是否支持WebP
const supportsWebP = () => {
  const canvas = document.createElement('canvas')
  if (canvas.getContext && canvas.getContext('2d')) {
    return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0
  }
  return false
}

// 处理图片URL（转换为WebP）
const currentSrc = computed(() => {
  if (!loaded.value && props.placeholder) {
    return props.placeholder
  }
  
  // 如果启用WebP且浏览器支持，尝试转换URL
  if (props.useWebP && supportsWebP() && props.src) {
    // 这里可以根据实际CDN配置调整
    // 例如：阿里云OSS添加?x-oss-process=image/format,webp
    return props.src
  }
  
  return props.src
})

const onLoad = () => {
  loaded.value = true
  emit('load')
}

const onError = () => {
  error.value = true
  loaded.value = true
  emit('error')
}

// 使用IntersectionObserver实现懒加载
onMounted(() => {
  if (!imageRef.value) return
  
  // 优先使用原生loading="lazy"
  if ('loading' in HTMLImageElement.prototype) {
    imageRef.value.loading = 'lazy'
  } else {
    // 降级使用IntersectionObserver
    observer.value = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            loaded.value = false
            observer.value?.unobserve(entry.target)
          }
        })
      },
      { threshold: props.threshold }
    )
    observer.value.observe(imageRef.value)
  }
})

onUnmounted(() => {
  observer.value?.disconnect()
})
</script>

<style scoped>
.lazy-image-wrapper {
  position: relative;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.03);
}

.lazy-image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.image-skeleton {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
}

.shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.4) 50%,
    transparent 100%
  );
  animation: shimmer 1.5s infinite;
}

.image-error {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.05);
  color: #999;
  font-size: 14px;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* 悬浮效果 */
.lazy-image-wrapper:hover img {
  transform: scale(1.05);
}
</style>
