<template>
  <Transition name="fade">
    <div v-if="show" class="onboarding-overlay" @click="skip">
      <!-- 高亮遮罩 -->
      <div class="highlight-mask" :style="highlightStyle">
        <div class="highlight-box"></div>
      </div>
      
      <!-- 引导提示卡片 -->
      <div 
        class="guide-card"
        :style="cardStyle"
        @click.stop
      >
        <div class="guide-header">
          <div class="step-dots">
            <span 
              v-for="(_, index) in normalizedSteps"
              :key="index"
              :class="['dot', { active: index === currentStepIndex }]"
            ></span>
          </div>
          <button class="skip-btn" @click="skip">跳过</button>
        </div>
        
        <div class="guide-content">
          <div class="step-icon" :style="{ background: currentStepData.iconBg }">
            <el-icon :size="32" :color="currentStepData.iconColor">
              <component :is="resolvedStepIcon" />
            </el-icon>
          </div>
          <h3 class="step-title">{{ currentStepData.title }}</h3>
          <p class="step-desc">{{ currentStepData.description }}</p>
        </div>
        
        <div class="guide-footer">
          <button 
            v-if="currentStepIndex > 0"
            class="btn-prev"
            @click="prev"
          >
            上一步
          </button>
          <button 
            class="btn-next"
            :class="{ 'btn-finish': isLastStep }"
            @click="next"
          >
            {{ isLastStep ? '开始探索' : '下一步' }}
            <el-icon v-if="!isLastStep"><ArrowRight /></el-icon>
          </button>
        </div>
        
        <!-- 箭头指示 -->
        <div v-if="showArrow" class="guide-arrow" :class="arrowPosition"></div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ArrowRight, Calendar, MagicStick, Star, Present } from '@element-plus/icons-vue'

const props = defineProps({
  steps: {
    type: Array,
    default: () => [
      {
        target: '.hero-section',
        title: '欢迎来到太初命理',
        description: '开启你的命理探索之旅，发现命运的奥秘',
        icon: 'Star',
        iconBg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        iconColor: '#fff',
        position: 'bottom'
      },
      {
        target: '.quick-actions',
        title: '快速开始',
        description: '点击这里快速进行八字排盘、塔罗占卜或查看今日运势',
        icon: 'MagicStick',
        iconBg: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        iconColor: '#fff',
        position: 'bottom'
      },
      {
        target: '.fortune-calendar',
        title: '运势日历',
        description: '查看每日运势变化，掌握最佳时机',
        icon: 'Calendar',
        iconBg: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        iconColor: '#fff',
        position: 'right'
      },
      {
        target: '.achievement-section',
        title: '成就系统',
        description: '完成任务解锁成就徽章，获得额外积分奖励',
        icon: 'Present',
        iconBg: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        iconColor: '#fff',
        position: 'left'
      }
    ]
  }
})

const emit = defineEmits(['complete', 'skip'])

const show = ref(false)
const currentStep = ref(0)
const targetRect = ref(null)
const isFallbackTarget = ref(false)
let showTimer = null

const iconMap = {
  Star,
  MagicStick,
  Calendar,
  Present,
}

const defaultStep = {
  target: '',
  title: '欢迎使用',
  description: '让我们快速熟悉关键功能。',
  icon: 'Star',
  iconBg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  iconColor: '#fff',
  position: 'bottom',
}

const normalizedSteps = computed(() => {
  if (!Array.isArray(props.steps) || props.steps.length === 0) {
    return [defaultStep]
  }

  return props.steps.map((step) => ({ ...defaultStep, ...(step || {}) }))
})

const currentStepIndex = computed(() => {
  const lastIndex = normalizedSteps.value.length - 1
  if (lastIndex <= 0) {
    return 0
  }

  return Math.min(Math.max(currentStep.value, 0), lastIndex)
})

// 当前步骤数据
const currentStepData = computed(() => normalizedSteps.value[currentStepIndex.value] || defaultStep)
const isLastStep = computed(() => currentStepIndex.value === normalizedSteps.value.length - 1)
const resolvedStepIcon = computed(() => {
  const icon = currentStepData.value.icon
  if (typeof icon === 'string') {
    return iconMap[icon] || Star
  }

  return icon || Star
})

// 高亮区域样式
const highlightStyle = computed(() => {
  if (!targetRect.value) return {}
  const padding = 8
  return {
    top: `${targetRect.value.top - padding}px`,
    left: `${targetRect.value.left - padding}px`,
    width: `${targetRect.value.width + padding * 2}px`,
    height: `${targetRect.value.height + padding * 2}px`
  }
})

// 引导卡片位置
const cardStyle = computed(() => {
  if (!targetRect.value) return {}

  if (typeof window === 'undefined') {
    return {}
  }

  const position = currentStepData.value.position || 'bottom'
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight
  const cardWidth = Math.min(320, Math.max(260, viewportWidth - 24))
  const cardHeight = 280
  const spacing = viewportWidth < 768 ? 12 : 20

  if (isFallbackTarget.value) {
    const centerTop = Math.max(12, (viewportHeight - cardHeight) / 2)
    const centerLeft = Math.max(12, (viewportWidth - cardWidth) / 2)
    return {
      top: `${centerTop}px`,
      left: `${centerLeft}px`,
    }
  }

  let top, left
  
  switch (position) {
    case 'top':
      top = targetRect.value.top - cardHeight - spacing
      left = targetRect.value.left + (targetRect.value.width - cardWidth) / 2
      break
    case 'bottom':
      top = targetRect.value.bottom + spacing
      left = targetRect.value.left + (targetRect.value.width - cardWidth) / 2
      break
    case 'left':
      top = targetRect.value.top + (targetRect.value.height - cardHeight) / 2
      left = targetRect.value.left - cardWidth - spacing
      break
    case 'right':
      top = targetRect.value.top + (targetRect.value.height - cardHeight) / 2
      left = targetRect.value.right + spacing
      break
    default:
      top = targetRect.value.bottom + spacing
      left = targetRect.value.left
  }
  
  // 边界检查
  const maxLeft = viewportWidth - cardWidth - 12
  const maxTop = viewportHeight - cardHeight - 12
  left = Math.max(12, Math.min(left, maxLeft))
  top = Math.max(12, Math.min(top, maxTop))

  return { top: `${top}px`, left: `${left}px` }
})

// 箭头位置
const arrowPosition = computed(() => {
  const validPositions = ['top', 'bottom', 'left', 'right']
  const position = currentStepData.value.position || 'bottom'
  return validPositions.includes(position) ? position : 'bottom'
})

const showArrow = computed(() => !isFallbackTarget.value)

// 更新目标元素位置
const updateTargetRect = () => {
  if (typeof window === 'undefined') {
    return
  }

  const selector = currentStepData.value.target
  const target = typeof selector === 'string' && selector ? document.querySelector(selector) : null

  if (target) {
    isFallbackTarget.value = false
    targetRect.value = target.getBoundingClientRect()
    // 滚动到目标元素
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' })
    return
  }

  // 目标不存在时回退为居中定位，避免渲染报错
  isFallbackTarget.value = true
  targetRect.value = {
    top: window.innerHeight / 2 - 80,
    left: window.innerWidth / 2 - 120,
    width: 240,
    height: 160,
    right: window.innerWidth / 2 + 120,
    bottom: window.innerHeight / 2 + 80,
  }
}

// 下一步
const next = () => {
  if (isLastStep.value) {
    complete()
  } else {
    currentStep.value = currentStepIndex.value + 1
    updateTargetRect()
  }
}

// 上一步
const prev = () => {
  if (currentStepIndex.value > 0) {
    currentStep.value = currentStepIndex.value - 1
    updateTargetRect()
  }
}

// 完成
const complete = () => {
  show.value = false
  localStorage.setItem('onboarding_completed', 'true')
  emit('complete')
}

// 跳过
const skip = () => {
  show.value = false
  localStorage.setItem('onboarding_completed', 'true')
  emit('skip')
}

// 窗口大小改变时更新位置
const handleResize = () => {
  if (show.value) {
    updateTargetRect()
  }
}

const handleScroll = () => {
  if (show.value) {
    updateTargetRect()
  }
}

const handleKeydown = (event) => {
  if (!show.value) return

  if (event.key === 'Escape') {
    skip()
    return
  }

  if (event.key === 'ArrowRight') {
    next()
    return
  }

  if (event.key === 'ArrowLeft') {
    prev()
  }
}

// 检查是否应该显示引导
onMounted(() => {
  const completed = localStorage.getItem('onboarding_completed')
  if (!completed) {
    showTimer = setTimeout(() => {
      show.value = true
      updateTargetRect()
    }, 1000)
  }
  
  window.addEventListener('resize', handleResize)
  window.addEventListener('scroll', handleScroll, true)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('keydown', handleKeydown)
  if (showTimer) {
    clearTimeout(showTimer)
    showTimer = null
  }
})

// 暴露方法供外部调用
const start = () => {
  currentStep.value = 0
  show.value = true
  updateTargetRect()
}

defineExpose({ start, skip })
</script>

<style scoped>
.onboarding-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.7);
}

.highlight-mask {
  position: absolute;
  border-radius: 16px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
  animation: pulse 2s infinite;
}

.highlight-box {
  width: 100%;
  height: 100%;
  border-radius: 16px;
  border: 2px solid #667eea;
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
}

.guide-card {
  position: absolute;
  width: min(320px, calc(100vw - 24px));
  background: white;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

.guide-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.step-dots {
  display: flex;
  gap: 8px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #e0e0e0;
  transition: all 0.3s;
}

.dot.active {
  width: 24px;
  border-radius: 4px;
  background: #667eea;
}

.skip-btn {
  font-size: 14px;
  color: #999;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.3s;
}

.skip-btn:hover {
  color: #667eea;
}

.guide-content {
  text-align: center;
  margin-bottom: 24px;
}

.step-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  animation: bounce 1s infinite;
}

.step-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.step-desc {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.guide-footer {
  display: flex;
  gap: 12px;
}

.btn-prev,
.btn-next {
  flex: 1;
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.btn-prev {
  background: #f5f5f5;
  color: #666;
  border: none;
}

.btn-prev:hover {
  background: #e8e8e8;
}

.btn-next {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.btn-next:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-finish {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.guide-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border-style: solid;
}

.guide-arrow.top {
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 10px 10px 0;
  border-color: white transparent transparent;
}

.guide-arrow.bottom {
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  border-width: 0 10px 10px;
  border-color: transparent transparent white;
}

.guide-arrow.left {
  right: -10px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 10px 0 10px 10px;
  border-color: transparent transparent transparent white;
}

.guide-arrow.right {
  left: -10px;
  top: 50%;
  transform: translateY(-50%);
  border-width: 10px 10px 10px 0;
  border-color: transparent white transparent transparent;
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7), 0 0 0 0 rgba(102, 126, 234, 0.4);
  }
  50% {
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7), 0 0 0 10px rgba(102, 126, 234, 0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .guide-card {
    border-radius: 16px;
    padding: 18px;
  }

  .step-title {
    font-size: 18px;
  }

  .step-desc {
    font-size: 13px;
  }

  .btn-prev,
  .btn-next {
    padding: 10px 14px;
    font-size: 14px;
  }
}
</style>
