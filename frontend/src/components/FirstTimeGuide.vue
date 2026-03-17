<template>
  <Teleport to="body">
    <Transition name="guide">
      <div v-if="show" class="first-time-guide">
        <!-- 遮罩 -->
        <div class="guide-overlay" @click="handleSkip"></div>
        
        <!-- 高亮区域 -->
        <div
          v-if="currentStep && highlightRect"
          class="guide-highlight"
          :style="highlightStyle"
        >
          <div class="guide-spotlight"></div>
        </div>
        
        <!-- 提示卡片 -->
        <Transition name="card" mode="out-in">
          <div
            v-if="currentStep"
            :key="currentStepIndex"
            class="guide-card"
            :style="cardPosition"
          >
            <div class="guide-progress">
              <div
                v-for="(step, index) in steps"
                :key="index"
                class="guide-progress__dot"
                :class="{ active: index === currentStepIndex, completed: index < currentStepIndex }"
              ></div>
            </div>
            
            <div class="guide-step-number">步骤 {{ currentStepIndex + 1 }}/{{ steps.length }}</div>
            
            <h3 class="guide-title">{{ currentStep.title }}</h3>
            <p class="guide-description">{{ currentStep.description }}</p>
            
            <div class="guide-image" v-if="currentStep.image">
              <img :src="currentStep.image" :alt="currentStep.title">
            </div>
            
            <div class="guide-actions">
              <button
                v-if="currentStepIndex > 0"
                class="guide-btn guide-btn--secondary"
                @click="prevStep"
              >
                上一步
              </button>
              <button
                class="guide-btn guide-btn--primary"
                @click="nextStep"
              >
                {{ isLastStep ? '开始使用' : '下一步' }}
              </button>
            </div>
            
            <button class="guide-skip" @click="handleSkip">
              {{ isLastStep ? '跳过' : '跳过引导' }}
            </button>
          </div>
        </Transition>
        
        <!-- 欢迎卡片（第一步） -->
        <div v-if="currentStepIndex === -1" class="guide-welcome">
          <div class="guide-welcome__icon">✨</div>
          <h2 class="guide-welcome__title">欢迎来到太初</h2>
          <p class="guide-welcome__desc">
            只需 30 秒，带你快速了解如何使用太初探索命理奥秘
          </p>
          <button class="guide-btn guide-btn--primary guide-btn--large" @click="startGuide">
            开始引导
          </button>
          <button class="guide-skip" @click="handleSkip">跳过</button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { ref, computed, onMounted, nextTick } from 'vue'

export default {
  name: 'FirstTimeGuide',
  props: {
    steps: {
      type: Array,
      required: true
    },
    storageKey: {
      type: String,
      default: 'firstTimeGuideCompleted'
    }
  },
  emits: ['complete', 'skip'],
  setup(props, { emit }) {
    const show = ref(false)
    const currentStepIndex = ref(-1)
    const highlightRect = ref(null)
    
    const currentStep = computed(() => {
      if (currentStepIndex.value < 0 || currentStepIndex.value >= props.steps.length) {
        return null
      }
      return props.steps[currentStepIndex.value]
    })
    
    const isLastStep = computed(() => {
      return currentStepIndex.value === props.steps.length - 1
    })
    
    const highlightStyle = computed(() => {
      if (!highlightRect.value) return {}
      return {
        top: `${highlightRect.value.top - 8}px`,
        left: `${highlightRect.value.left - 8}px`,
        width: `${highlightRect.value.width + 16}px`,
        height: `${highlightRect.value.height + 16}px`,
      }
    })
    
    const cardPosition = computed(() => {
      if (!highlightRect.value || !currentStep.value) return {}
      
      const rect = highlightRect.value
      const placement = currentStep.value.placement || 'bottom'
      const spacing = 16
      
      let style = {}
      
      switch (placement) {
        case 'top':
          style = {
            bottom: `${window.innerHeight - rect.top + spacing}px`,
            left: `${Math.max(16, Math.min(rect.left, window.innerWidth - 350))}px`,
          }
          break
        case 'bottom':
          style = {
            top: `${rect.bottom + spacing}px`,
            left: `${Math.max(16, Math.min(rect.left, window.innerWidth - 350))}px`,
          }
          break
        case 'left':
          style = {
            top: `${rect.top}px`,
            right: `${window.innerWidth - rect.left + spacing}px`,
          }
          break
        case 'right':
          style = {
            top: `${rect.top}px`,
            left: `${rect.right + spacing}px`,
          }
          break
        case 'center':
          style = {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
          }
          break
      }
      
      return style
    })
    
    const checkAndShow = () => {
      const completed = localStorage.getItem(props.storageKey)
      if (!completed) {
        show.value = true
      }
    }
    
    const startGuide = () => {
      currentStepIndex.value = 0
      updateHighlight()
    }
    
    const updateHighlight = async () => {
      await nextTick()
      
      if (!currentStep.value || !currentStep.value.target) {
        highlightRect.value = null
        return
      }
      
      const element = document.querySelector(currentStep.value.target)
      if (element) {
        highlightRect.value = element.getBoundingClientRect()
        
        // 滚动到元素
        element.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    }
    
    const nextStep = () => {
      if (isLastStep.value) {
        complete()
      } else {
        currentStepIndex.value++
        updateHighlight()
      }
    }
    
    const prevStep = () => {
      if (currentStepIndex.value > 0) {
        currentStepIndex.value--
        updateHighlight()
      }
    }
    
    const handleSkip = () => {
      localStorage.setItem(props.storageKey, 'skipped')
      show.value = false
      emit('skip')
    }
    
    const complete = () => {
      localStorage.setItem(props.storageKey, 'completed')
      show.value = false
      emit('complete')
    }
    
    // 监听窗口大小变化
    const handleResize = () => {
      if (show.value && currentStep.value) {
        updateHighlight()
      }
    }
    
    onMounted(() => {
      setTimeout(checkAndShow, 1000)
      window.addEventListener('resize', handleResize)
    })
    
    return {
      show,
      currentStepIndex,
      currentStep,
      isLastStep,
      highlightRect,
      highlightStyle,
      cardPosition,
      startGuide,
      nextStep,
      prevStep,
      handleSkip
    }
  }
}
</script>

<style scoped>
.first-time-guide {
  position: fixed;
  inset: 0;
  z-index: 9999;
}

.guide-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
}

.guide-highlight {
  position: absolute;
  border-radius: 16px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
  z-index: 1;
  transition: all 0.3s ease;
}

.guide-spotlight {
  position: absolute;
  inset: 0;
  border-radius: 16px;
  border: 2px solid var(--info-color);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4); }
  50% { box-shadow: 0 0 0 10px rgba(139, 92, 246, 0); }
}

.guide-card {
  position: absolute;
  width: 320px;
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  z-index: 2;
  transition: all 0.3s ease;
}

.guide-progress {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.guide-progress__dot {
  flex: 1;
  height: 4px;
  background: #f0f0f0;
  border-radius: 2px;
  transition: all 0.3s;
}

.guide-progress__dot.active {
  background: #8B5CF6;
}

.guide-progress__dot.completed {
  background: #10b981;
}

.guide-step-number {
  font-size: 12px;
  color: #8B5CF6;
  font-weight: 500;
  margin-bottom: 8px;
}

.guide-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px;
}

.guide-description {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0 0 16px;
}

.guide-image {
  margin: 16px 0;
  border-radius: 16px;
  overflow: hidden;
}

.guide-image img {
  width: 100%;
  display: block;
}

.guide-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.guide-btn {
  flex: 1;
  padding: 12px 20px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.guide-btn--primary {
  background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
  color: white;
}

.guide-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.guide-btn--secondary {
  background: #f5f5f5;
  color: #666;
}

.guide-btn--secondary:hover {
  background: #e8e8e8;
}

.guide-btn--large {
  padding: 14px 32px;
  font-size: 16px;
}

.guide-skip {
  display: block;
  margin: 16px auto 0;
  padding: 8px 16px;
  background: none;
  border: none;
  color: #999;
  font-size: 13px;
  cursor: pointer;
  transition: color 0.2s;
}

.guide-skip:hover {
  color: #666;
}

.guide-welcome {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  background: white;
  padding: 48px 40px;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  z-index: 2;
  max-width: 360px;
  width: 90%;
}

.guide-welcome__icon {
  font-size: 64px;
  margin-bottom: 16px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.guide-welcome__title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 12px;
}

.guide-welcome__desc {
  font-size: 15px;
  color: #666;
  line-height: 1.6;
  margin: 0 0 32px;
}

/* 动画 */
.guide-enter-active,
.guide-leave-active {
  transition: opacity 0.3s;
}

.guide-enter-from,
.guide-leave-to {
  opacity: 0;
}

.card-enter-active,
.card-leave-active {
  transition: all 0.3s ease;
}

.card-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.card-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* 移动端适配 */
@media (max-width: 480px) {
  .guide-card {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    width: auto;
    top: auto !important;
  }
  
  .guide-highlight {
    display: none;
  }
  
  .guide-welcome {
    padding: 32px 24px;
  }
}
</style>
