<template>
  <div class="tarot-card-component" :class="{ 'is-reversed': reversed, 'is-revealed': revealed }">
    <div class="card-inner">
      <!-- Front of the card (Revealed state) -->
      <div class="card-front" :style="frontStyle">
        <div class="card-header">
          <span class="card-number">{{ romanNumber }}</span>
          <span class="card-element-tag" :class="element">{{ element }}</span>
        </div>
        
        <div class="card-visual-container">
          <div class="visual-glow" :style="{ backgroundColor: color + '22' }"></div>
          <div class="visual-frame">
            <svg class="mystic-pattern" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="48" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="2 2" opacity="0.3" />
              <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="0.2" opacity="0.1" />
              <path d="M50 5 L55 45 L95 50 L55 55 L50 95 L45 55 L5 50 L45 45 Z" fill="none" stroke="currentColor" stroke-width="0.3" opacity="0.15" />
            </svg>
            <span class="card-emoji">{{ emoji }}</span>
          </div>
        </div>
        
        <div class="card-footer-new">
          <div class="card-name-cn">{{ name }}</div>
          <div v-if="reversed" class="reversed-badge-mini">REVERSED</div>
        </div>

        <!-- Decorative Corners -->
        <div class="corner-decor top-l"></div>
        <div class="corner-decor top-r"></div>
        <div class="corner-decor bot-l"></div>
        <div class="corner-decor bot-r"></div>
      </div>
      
      <!-- Back of the card -->
      <div class="card-back">
        <div class="back-pattern">
          <div class="back-border-outer"></div>
          <div class="back-border-inner"></div>
          <div class="center-emblem">
            <el-icon class="center-icon"><MagicStick /></el-icon>
          </div>
          <!-- Mandala style corners -->
          <div class="mandala-corner tl"></div>
          <div class="mandala-corner tr"></div>
          <div class="mandala-corner bl"></div>
          <div class="mandala-corner br"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { MagicStick } from '@element-plus/icons-vue'

const props = defineProps({
  name: String,
  emoji: String,
  reversed: Boolean,
  revealed: {
    type: Boolean,
    default: true
  },
  element: String,
  color: {
    type: String,
    default: '#B8860B'
  },
  index: Number
})

const romanNumber = computed(() => {
  const romanMap = [
    '0', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X',
    'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX', 'XXI'
  ]
  const majorArcana = [
    '愚者', '魔术师', '女祭司', '皇后', '皇帝', '教皇', '恋人', '战车', 
    '力量', '隐者', '命运之轮', '正义', '倒吊人', '死神', '节制', 
    '恶魔', '塔', '星星', '月亮', '太阳', '审判', '世界'
  ]
  const idx = majorArcana.indexOf(props.name)
  return idx === -1 ? '' : romanMap[idx]
})

const frontStyle = computed(() => ({
  border: `1px solid ${props.color}44`,
  boxShadow: `0 10px 30px ${props.color}15`
}))
</script>

<style scoped>
.tarot-card-component {
  width: 180px;
  height: 300px;
  perspective: 1000px;
  cursor: pointer;
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.tarot-card-component:hover {
  transform: translateY(-15px) scale(1.02);
}

.card-inner {
  position: relative;
  width: 100%;
  height: 100%;
  text-align: center;
  transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1);
  transform-style: preserve-3d;
}

.tarot-card-component.is-revealed .card-inner {
  transform: rotateY(180deg);
}

.tarot-card-component.is-reversed.is-revealed .card-inner {
  transform: rotateY(180deg) rotate(180deg);
}

.card-front, .card-back {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  border-radius: 24px;
  overflow: hidden;
}

/* Card Front Styling - 优化为八字排版风格 */
.card-front {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 241, 0.94));
  color: var(--text-primary);
  transform: rotateY(180deg);
  display: flex;
  flex-direction: column;
  padding: 20px;
  position: relative;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  z-index: 2;
}

.card-number {
  font-family: 'Cinzel', 'Times New Roman', serif;
  font-weight: 800;
  font-size: 20px;
  color: var(--primary-color);
  text-shadow: 0 0 10px rgba(var(--primary-rgb), 0.3);
}

.card-element-tag {
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--text-secondary);
  font-weight: 600;
}

.card-visual-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.visual-glow {
  position: absolute;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  filter: blur(25px);
  z-index: 0;
  animation: pulse-glow 4s ease-in-out infinite;
}

.visual-frame {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.95);
  border: 2px solid rgba(var(--primary-rgb), 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1), 0 8px 24px rgba(var(--primary-rgb), 0.15);
  position: relative;
}

.mystic-pattern {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  color: var(--primary-color);
  pointer-events: none;
  animation: rotate-pattern 30s linear infinite;
}

@keyframes rotate-pattern {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.card-emoji {
  font-size: 60px;
  filter: drop-shadow(0 0 15px rgba(var(--primary-rgb), 0.3));
}

.card-footer-new {
  padding-top: 16px;
  border-top: 1px dashed rgba(var(--primary-rgb), 0.2);
  z-index: 2;
}

.card-name-cn {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 1px;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.reversed-badge-mini {
  font-size: 10px;
  color: #ff4d4f;
  letter-spacing: 1px;
  font-weight: bold;
  padding: 2px 8px;
  border-radius: 12px;
  background: rgba(255, 77, 79, 0.1);
  border: 1px solid rgba(255, 77, 79, 0.3);
}

/* Decorative Corners - 优化为更现代的风格 */
.corner-decor {
  position: absolute;
  width: 28px;
  height: 28px;
  border: 2px solid rgba(var(--primary-rgb), 0.3);
  opacity: 0.6;
  border-radius: 8px;
}

.top-l { top: 10px; left: 10px; border-right: none; border-bottom: none; }
.top-r { top: 10px; right: 10px; border-left: none; border-bottom: none; }
.bot-l { bottom: 10px; left: 10px; border-right: none; border-top: none; }
.bot-r { bottom: 10px; right: 10px; border-left: none; border-top: none; }

/* Card Back Styling - 优化为八字排版风格 */
.card-back {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.98));
  padding: 16px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
}

.back-pattern {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 249, 250, 0.95));
  border-radius: 20px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(var(--primary-rgb), 0.2);
}

.back-border-outer {
  position: absolute;
  top: 10px; left: 10px; right: 10px; bottom: 10px;
  border: 1px solid rgba(var(--primary-rgb), 0.3);
  border-radius: 12px;
}

.back-border-inner {
  position: absolute;
  top: 16px; left: 16px; right: 16px; bottom: 16px;
  border: 1px solid rgba(var(--primary-rgb), 0.1);
  border-radius: 8px;
}

.center-emblem {
  background: rgba(255, 255, 255, 0.95);
  padding: 18px;
  border-radius: 50%;
  border: 2px solid var(--primary-color);
  box-shadow: 0 0 30px rgba(var(--primary-rgb), 0.2), inset 0 0 20px rgba(var(--primary-rgb), 0.1);
}

.center-icon {
  font-size: 60px;
  color: var(--primary-color);
  animation: rotate-back 20s linear infinite;
}

.mandala-corner {
  position: absolute;
  width: 40px;
  height: 40px;
  opacity: 0.2;
  background-image: radial-gradient(circle, var(--primary-color) 1px, transparent 1px);
  background-size: 8px 8px;
  border-radius: 8px;
}

.tl { top: 0; left: 0; border-radius: 0 0 12px 0; }
.tr { top: 0; right: 0; border-radius: 0 0 0 12px; }
.bl { bottom: 0; left: 0; border-radius: 0 12px 0 0; }
.br { bottom: 0; right: 0; border-radius: 12px 0 0 0; }

@keyframes rotate-back {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse-glow {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.2); }
}

@media (prefers-reduced-motion: reduce) {
  .tarot-card-component,
  .card-inner,
  .visual-glow,
  .mystic-pattern,
  .center-icon {
    animation: none !important;
    transition: none !important;
  }

  .tarot-card-component:hover,
  .tarot-card-component.is-revealed .card-inner,
  .tarot-card-component.is-reversed.is-revealed .card-inner {
    transform: none !important;
  }

  .tarot-card-component.is-revealed .card-front,
  .tarot-card-component.is-reversed.is-revealed .card-front {
    transform: none;
  }

  .card-front,
  .card-back {
    position: relative;
    backface-visibility: visible;
  }

  .card-back {
    display: none;
  }
}

@media (max-width: 768px) {
  .tarot-card-component {
    width: 140px;
    height: 240px;
  }
  
  .card-emoji {
    font-size: 44px;
  }
  
  .card-name-cn {
    font-size: 15px;
  }

  .visual-frame {
    width: 70px;
    height: 70px;
  }
  
  .card-front,
  .card-back {
    padding: 14px;
    border-radius: 20px;
  }
}
</style>
