<template>
  <div class="tarot-draw-animation">
    <div class="animation-container">
      <!-- 背景装饰 -->
      <div class="bg-decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
      
      <!-- 牌堆 -->
      <div class="deck-container" v-if="stage === 'shuffle'">
        <div class="deck" :class="{ shuffling: isShuffling }">
          <div 
            v-for="i in 5" 
            :key="i"
            class="deck-card"
            :style="getDeckCardStyle(i)"
          >
            <div class="card-back-styled">
              <div class="back-pattern">
                <span class="pattern-small">☯</span>
              </div>
            </div>
          </div>
        </div>
        <p class="stage-text">正在洗牌...</p>
        <p class="stage-hint">集中精神，想着你的问题</p>
      </div>
      
      <!-- 切牌 -->
      <div class="cut-container" v-else-if="stage === 'cut'">
        <div class="cut-animation">
          <div class="card-half left"></div>
          <div class="card-half right"></div>
        </div>
        <p class="stage-text">切牌中...</p>
        <p class="stage-hint">将牌分为两半，准备抽牌</p>
      </div>
      
      <!-- 抽牌 -->
      <div class="draw-container" v-else-if="stage === 'draw'">
        <div class="draw-animation">
          <TarotCard 
            v-for="(card, index) in displayCards" 
            :key="index"
            :name="card.name"
            :emoji="card.emoji"
            :reversed="card.reversed"
            :revealed="card.revealed"
            :element="card.element"
            :color="card.color"
            class="drawn-card-new"
            :style="getDrawnCardStyle(index)"
          />
        </div>
        <p class="stage-text">正在揭示...</p>
        <p class="stage-hint">{{ revealedCount }}/{{ totalCards }} 张牌已揭示</p>
      </div>
      
      <!-- 完成 -->
      <div class="complete-container" v-else-if="stage === 'complete'">
        <div class="complete-animation">
          <span class="complete-icon">✨</span>
          <p class="complete-text">抽牌完成</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TarotCard from './TarotCard.vue'

const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  onComplete: {
    type: Function,
    default: () => {}
  }
})

const stage = ref('shuffle')
const isShuffling = ref(false)
const displayCards = ref([])
const revealedCount = ref(0)
const totalCards = ref(props.cards.length)

const getDeckCardStyle = (index) => {
  const offset = (index - 3) * 2
  const rotation = (index - 3) * 3
  return {
    transform: `translateX(${offset}px) rotate(${rotation}deg)`,
    zIndex: 6 - index
  }
}

const getDrawnCardStyle = (index) => {
  const spreadWidth = Math.min(props.cards.length * 120, 600)
  const startX = -spreadWidth / 2 + 60
  const x = startX + index * 120
  return {
    transform: `translateX(${x}px) ${displayCards.value[index]?.revealed ? 'rotateY(180deg)' : ''}`
  }
}

const startAnimation = async () => {
  // 洗牌阶段
  stage.value = 'shuffle'
  isShuffling.value = true
  await sleep(2000)
  isShuffling.value = false
  
  // 切牌阶段
  stage.value = 'cut'
  await sleep(1500)
  
  // 抽牌阶段
  stage.value = 'draw'
  displayCards.value = props.cards.map(card => ({
    ...card,
    revealed: false
  }))
  
  // 逐张揭示
  for (let i = 0; i < displayCards.value.length; i++) {
    await sleep(800)
    displayCards.value[i].revealed = true
    revealedCount.value++
  }
  
  await sleep(1000)
  
  // 完成
  stage.value = 'complete'
  await sleep(800)
  
  props.onComplete()
}

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms))

onMounted(() => {
  startAnimation()
})
</script>

<style scoped>
.tarot-draw-animation {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.animation-container {
  position: relative;
  width: 100%;
  max-width: 800px;
  height: 500px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 背景装饰 */
.bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
}

.circle {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(255, 215, 0, 0.3);
  animation: pulse 3s ease-in-out infinite;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.circle-2 {
  width: 400px;
  height: 400px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: 0.5s;
}

.circle-3 {
  width: 500px;
  height: 500px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: 1s;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.3;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.6;
    transform: translate(-50%, -50%) scale(1.1);
  }
}

/* 牌堆 */
.deck-container {
  text-align: center;
}

.deck {
  position: relative;
  width: 120px;
  height: 200px;
  margin: 0 auto 30px;
}

.deck-card {
  position: absolute;
  width: 100%;
  height: 100%;
  transition: all 0.3s ease;
}

.card-back-styled {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid #B8860B;
  border-radius: 12px;
  padding: 5px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
}

.back-pattern {
  width: 100%;
  height: 100%;
  border: 1px solid rgba(184, 134, 11, 0.4);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pattern-small {
  font-size: 32px;
  color: rgba(184, 134, 11, 0.5);
}

.drawn-card-new {
  position: absolute;
  transition: all 0.6s ease;
}

.shuffling .deck-card {
  animation: shuffle 0.5s ease-in-out infinite;
}

.shuffling .deck-card:nth-child(1) { animation-delay: 0s; }
.shuffling .deck-card:nth-child(2) { animation-delay: 0.1s; }
.shuffling .deck-card:nth-child(3) { animation-delay: 0.2s; }
.shuffling .deck-card:nth-child(4) { animation-delay: 0.3s; }
.shuffling .deck-card:nth-child(5) { animation-delay: 0.4s; }

@keyframes shuffle {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  25% {
    transform: translateY(-30px) rotate(-5deg);
  }
  75% {
    transform: translateY(-30px) rotate(5deg);
  }
}

/* 切牌 */
.cut-container {
  text-align: center;
}

.cut-animation {
  position: relative;
  width: 120px;
  height: 200px;
  margin: 0 auto 30px;
}

.card-half {
  position: absolute;
  width: 60px;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid rgba(255, 215, 0, 0.3);
  animation: cut 1s ease-in-out infinite;
}

.card-half.left {
  left: 0;
  border-radius: 12px 0 0 12px;
  border-right: none;
  animation-name: cutLeft;
}

.card-half.right {
  right: 0;
  border-radius: 0 12px 12px 0;
  border-left: none;
  animation-name: cutRight;
}

@keyframes cutLeft {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(-20px);
  }
}

@keyframes cutRight {
  0%, 100% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(20px);
  }
}

/* 抽牌 */
.draw-container {
  text-align: center;
  width: 100%;
}

.draw-animation {
  position: relative;
  height: 250px;
  margin-bottom: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.drawn-card {
  position: absolute;
  width: 100px;
  height: 180px;
  perspective: 1000px;
  transition: all 0.6s ease;
}

.card-inner {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.6s;
}

.drawn-card.revealed .card-inner {
  transform: rotateY(180deg);
}

.drawn-card.reversed.revealed .card-inner {
  transform: rotateY(180deg) rotate(180deg);
}

.card-front,
.card-back {
  position: absolute;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.card-front {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 193, 7, 0.1));
  border: 2px solid rgba(255, 215, 0, 0.5);
  transform: rotateY(180deg);
}

.card-emoji {
  font-size: 48px;
  margin-bottom: 10px;
}

.card-name {
  font-size: 14px;
  color: #fff;
  font-weight: 500;
}

/* 完成 */
.complete-container {
  text-align: center;
}

.complete-animation {
  animation: completePop 0.5s ease;
}

.complete-icon {
  font-size: 80px;
  display: block;
  margin-bottom: 20px;
}

.complete-text {
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
}

@keyframes completePop {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* 文字 */
.stage-text {
  font-size: 24px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 10px;
}

.stage-hint {
  font-size: 14px;
  color: var(--text-tertiary);
}

@media (max-width: 768px) {
  .draw-animation {
    transform: scale(0.7);
  }
  
  .stage-text {
    font-size: 20px;
  }
}
</style>
