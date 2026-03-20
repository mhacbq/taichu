<template>
  <div class="bazi-loading">
    <div class="loading-content">
      <!-- 八卦动画 -->
      <div class="bagua-container">
        <div class="bagua">
          <div class="yin-yang"></div>
          <div class="trigrams">
            <div v-for="i in 8" :key="i" class="trigram" :style="getTrigramStyle(i)"></div>
          </div>
        </div>
      </div>
      
      <!-- 加载文字 -->
      <div class="loading-text">
        <p class="main-text">{{ currentText }}</p>
        <p class="sub-text">{{ subText }}</p>
      </div>
      
      <!-- 进度条 -->
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progress + '%' }"></div>
        </div>
        <span class="progress-text">{{ progress }}%</span>
      </div>
      
      <!-- 有趣的提示 -->
      <div class="tips-container">
        <transition name="fade" mode="out-in">
          <p :key="currentTip" class="tip-text">💡 {{ tips[currentTip] }}</p>
        </transition>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  duration: {
    type: Number,
    default: 3000
  }
})

const progress = ref(0)
const currentText = ref('正在排盘')
const subText = ref('计算天干地支...')
const currentTip = ref(0)

const steps = [
  { main: '测算', sub: '计算文化数据...' },
  { main: '分析五行', sub: '统计五行分布...' },
  { main: '推算十神', sub: '分析十神关系...' },
  { main: '解读周期', sub: '推算十年趋势...' },
  { main: '生成报告', sub: '整合分析结果...' }
]

const tips = [
  '文化测算基于传统天干地支组合，共八个字',
  '年柱代表祖上根基，月柱代表父母兄弟',
  '日柱的天干就是"日主"，代表你自己',
  '时柱代表子女和晚年生活',
  '五行平衡是文化分析的重要参考',
  '十神关系反映了你与周围人的互动模式',
  '周期每十年一换，影响人生不同阶段的趋势',
  '年度变化是每年的趋势变化，需要结合周期来看'
]

let progressTimer = null
let textTimer = null
let tipTimer = null

const getTrigramStyle = (index) => {
  const angle = (index - 1) * 45
  return {
    transform: `rotate(${angle}deg) translateY(-60px)`
  }
}

onMounted(() => {
  // 进度条动画
  const step = 100 / (props.duration / 50)
  progressTimer = setInterval(() => {
    progress.value = Math.min(progress.value + step, 99)
    
    // 根据进度更新文字
    const textIndex = Math.floor((progress.value / 100) * loadingTexts.length)
    if (textIndex < loadingTexts.length) {
      currentText.value = loadingTexts[textIndex].main
      subText.value = loadingTexts[textIndex].sub
    }
  }, 50)
  
  // 提示文字轮播
  tipTimer = setInterval(() => {
    currentTip.value = (currentTip.value + 1) % tips.length
  }, 4000)
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (textTimer) clearInterval(textTimer)
  if (tipTimer) clearInterval(tipTimer)
})
</script>

<style scoped>
.bazi-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 500px;
  padding: 40px;
}

.loading-content {
  text-align: center;
  max-width: 500px;
}

/* 八卦动画 */
.bagua-container {
  margin-bottom: 40px;
}

.bagua {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto;
  animation: rotate 8s linear infinite;
}

.yin-yang {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(to bottom, #b8860b 50%, #16213e 50%);
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 30px rgba(184, 134, 11, 0.5);
}

.yin-yang::before,
.yin-yang::after {
  content: '';
  position: absolute;
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.yin-yang::before {
  background: #b8860b;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.yin-yang::after {
  background: #16213e;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
}

.trigrams {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.trigram {
  position: absolute;
  width: 4px;
  height: 20px;
  background: linear-gradient(to bottom, #b8860b, #daa520);
  border-radius: 2px;
  top: 50%;
  left: 50%;
  margin-left: -2px;
  margin-top: -10px;
  transform-origin: center center;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* 加载文字 */
.loading-text {
  margin-bottom: 30px;
}

.main-text {
  font-size: 28px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 10px;
  animation: pulse 2s ease-in-out infinite;
}

.sub-text {
  font-size: 16px;
  color: var(--white-60);
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

/* 进度条 */
.progress-container {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 30px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: var(--white-10);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--primary-gradient);
  border-radius: 4px;
  transition: width 0.1s ease;
  box-shadow: 0 0 10px rgba(184, 134, 11, 0.5);
}

.progress-text {
  font-size: 14px;
  color: var(--white-60);
  min-width: 40px;
}

/* 提示文字 */
.tips-container {
  background: var(--white-05);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--white-10);
}

.tip-text {
  font-size: 14px;
  color: var(--white-70);
  line-height: 1.6;
  margin: 0;
}

/* 过渡动画 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .bazi-loading {
    min-height: 400px;
    padding: 20px;
  }
  
  .main-text {
    font-size: 22px;
  }
  
  .bagua {
    width: 100px;
    height: 100px;
  }
  
  .yin-yang {
    width: 60px;
    height: 60px;
  }
}
</style>
