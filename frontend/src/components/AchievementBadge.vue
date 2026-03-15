<template>
  <Transition name="achievement">
    <div v-if="visible" class="achievement-popup" :class="{ 'achievement-popup--compact': compact }">
      <div class="achievement-popup__icon">
        <div class="achievement-icon" :class="`achievement-icon--${type}`">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" 
                  fill="currentColor"/>
          </svg>
        </div>
        <div class="achievement-shine"></div>
      </div>
      <div class="achievement-popup__content">
        <div class="achievement-label">解锁成就</div>
        <div class="achievement-name">{{ title }}</div>
        <div class="achievement-desc">{{ description }}</div>
      </div>
      <button class="achievement-close" @click="close">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
        </svg>
      </button>
      
      <!-- 粒子效果 -->
      <div class="particles">
        <span v-for="n in 8" :key="n" class="particle" :style="getParticleStyle(n)"></span>
      </div>
    </div>
  </Transition>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'AchievementBadge',
  props: {
    title: {
      type: String,
      required: true
    },
    description: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'gold',
      validator: (val) => ['gold', 'silver', 'bronze', 'special'].includes(val)
    },
    duration: {
      type: Number,
      default: 4000
    },
    compact: {
      type: Boolean,
      default: false
    },
    showOnMount: {
      type: Boolean,
      default: true
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const visible = ref(false)
    
    const show = () => {
      visible.value = true
      if (props.duration > 0) {
        setTimeout(() => {
          close()
        }, props.duration)
      }
    }
    
    const close = () => {
      visible.value = false
      emit('close')
    }
    
    const getParticleStyle = (n) => {
      const angle = (n - 1) * 45
      const delay = n * 0.1
      return {
        transform: `rotate(${angle}deg)`,
        animationDelay: `${delay}s`
      }
    }
    
    onMounted(() => {
      if (props.showOnMount) {
        setTimeout(show, 100)
      }
    })
    
    return {
      visible,
      show,
      close,
      getParticleStyle
    }
  }
}
</script>

<style scoped>
.achievement-popup {
  position: fixed;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 215, 0, 0.3);
  z-index: 9999;
  min-width: 320px;
  max-width: 90vw;
  overflow: hidden;
}

.achievement-popup--compact {
  padding: 12px 16px;
  min-width: 280px;
}

.achievement-popup__icon {
  position: relative;
  flex-shrink: 0;
}

.achievement-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
}

.achievement-icon svg {
  width: 32px;
  height: 32px;
}

.achievement-icon--gold {
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  color: #fff;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
}

.achievement-icon--silver {
  background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
  color: #fff;
}

.achievement-icon--bronze {
  background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
  color: #fff;
}

.achievement-icon--special {
  background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%);
  color: #fff;
  animation: specialGlow 2s ease-in-out infinite;
}

@keyframes specialGlow {
  0%, 100% { box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4); }
  50% { box-shadow: 0 4px 30px rgba(236, 72, 153, 0.6); }
}

.achievement-shine {
  position: absolute;
  top: -4px;
  left: -4px;
  right: -4px;
  bottom: -4px;
  border-radius: 50%;
  background: conic-gradient(from 0deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  animation: shine 3s linear infinite;
}

@keyframes shine {
  to { transform: rotate(360deg); }
}

.achievement-popup__content {
  flex: 1;
  min-width: 0;
}

.achievement-label {
  font-size: 11px;
  color: #FFD700;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 4px;
}

.achievement-name {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
  margin-bottom: 4px;
}

.achievement-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.achievement-close {
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.achievement-close:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.achievement-close svg {
  width: 14px;
  height: 14px;
}

/* 粒子效果 */
.particles {
  position: absolute;
  top: 50%;
  left: 28px;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #FFD700;
  border-radius: 50%;
  opacity: 0;
  animation: particle 1s ease-out forwards;
}

@keyframes particle {
  0% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateY(-40px) scale(0);
  }
}

/* 动画 */
.achievement-enter-active {
  animation: achievementIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.achievement-leave-active {
  animation: achievementOut 0.3s ease-in forwards;
}

@keyframes achievementIn {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(20px) scale(0.9);
  }
  60% {
    transform: translateX(-50%) translateY(-5px) scale(1.02);
  }
  100% {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

@keyframes achievementOut {
  to {
    opacity: 0;
    transform: translateX(-50%) translateY(20px) scale(0.9);
  }
}

/* 移动端适配 */
@media (max-width: 480px) {
  .achievement-popup {
    left: 16px;
    right: 16px;
    transform: none;
    min-width: auto;
  }
  
  .achievement-enter-active,
  .achievement-leave-active {
    animation-name: achievementInMobile;
  }
  
  @keyframes achievementInMobile {
    0% {
      opacity: 0;
      transform: translateY(20px) scale(0.9);
    }
    60% {
      transform: translateY(-5px) scale(1.02);
    }
    100% {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }
}
</style>
