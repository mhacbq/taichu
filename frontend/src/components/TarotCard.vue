<template>
  <div class="tarot-card-component" :class="{ 'is-reversed': reversed, 'is-revealed': revealed }">
    <div class="card-inner">
      <!-- Front of the card (Revealed state) -->
      <div class="card-front" :style="frontStyle">
        <div class="card-header">
          <span class="card-number">{{ cardNumber }}</span>
          <span class="card-element" :class="element">{{ element }}</span>
        </div>
        <div class="card-visual">
          <div class="visual-bg"></div>
          <span class="card-emoji">{{ emoji }}</span>
        </div>
        <div class="card-footer">
          <div class="card-name">{{ name }}</div>
          <div v-if="reversed" class="reversed-label">逆位</div>
        </div>
      </div>
      
      <!-- Back of the card -->
      <div class="card-back">
        <div class="back-pattern">
          <div class="inner-border"></div>
          <el-icon class="center-icon"><YinYang /></el-icon>
          <div class="corner-decoration top-left"></div>
          <div class="corner-decoration top-right"></div>
          <div class="corner-decoration bottom-left"></div>
          <div class="corner-decoration bottom-right"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { YinYang } from '@element-plus/icons-vue'

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

const cardNumber = computed(() => {
  const majorArcana = [
    '愚者', '魔术师', '女祭司', '皇后', '皇帝', '教皇', '恋人', '战车', 
    '力量', '隐者', '命运之轮', '正义', '倒吊人', '死神', '节制', 
    '恶魔', '塔', '星星', '月亮', '太阳', '审判', '世界'
  ]
  const idx = majorArcana.indexOf(props.name)
  return idx === -1 ? '' : idx
})

const frontStyle = computed(() => ({
  borderTop: `4px solid ${props.color}`,
  boxShadow: `0 8px 20px ${props.color}33`
}))
</script>

<style scoped>
.tarot-card-component {
  width: 160px;
  height: 260px;
  perspective: 1000px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.tarot-card-component:hover {
  transform: translateY(-10px);
}

.card-inner {
  position: relative;
  width: 100%;
  height: 100%;
  text-align: center;
  transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
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
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Card Front Styling */
.card-front {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  transform: rotateY(180deg);
  display: flex;
  flex-direction: column;
  padding: 12px;
  border: 1px solid rgba(184, 134, 11, 0.3);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.card-number {
  font-family: 'Times New Roman', serif;
  font-weight: bold;
  font-size: 18px;
  color: var(--primary-light);
}

.card-element {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(184, 134, 11, 0.15);
  color: var(--primary-light);
}

.card-visual {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  margin-bottom: 15px;
}

.visual-bg {
  position: absolute;
  width: 80px;
  height: 80px;
  background: radial-gradient(circle, rgba(184, 134, 11, 0.15) 0%, transparent 70%);
  border-radius: 50%;
}

.card-emoji {
  font-size: 64px;
  z-index: 1;
  filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.4));
}

.card-footer {
  padding-top: 10px;
  border-top: 1px solid var(--border-light);
}

.card-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.reversed-label {
  font-size: 11px;
  color: var(--danger-color);
  margin-top: 4px;
}

/* Card Back Styling */
.card-back {
  background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-tertiary) 100%);
  padding: 10px;
  border: 2px solid var(--primary-color);
}

.back-pattern {
  width: 100%;
  height: 100%;
  border: 1px solid var(--border-light);
  border-radius: 12px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.inner-border {
  position: absolute;
  top: 5px;
  left: 5px;
  right: 5px;
  bottom: 5px;
  border: 1px solid var(--border-light-20);
  border-radius: 8px;
}

.center-icon {
  font-size: 50px;
  color: var(--primary-light-60);
  animation: rotate 10s linear infinite;
}

.corner-decoration {
  position: absolute;
  width: 15px;
  height: 15px;
  border: 2px solid var(--border-light);
}

.top-left { top: 10px; left: 10px; border-right: none; border-bottom: none; }
.top-right { top: 10px; right: 10px; border-left: none; border-bottom: none; }
.bottom-left { bottom: 10px; left: 10px; border-right: none; border-top: none; }
.bottom-right { bottom: 10px; right: 10px; border-left: none; border-top: none; }

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .tarot-card-component {
    width: 120px;
    height: 200px;
  }
  
  .card-emoji {
    font-size: 48px;
  }
  
  .card-name {
    font-size: 14px;
  }
}
</style>
