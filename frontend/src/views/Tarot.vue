<template>
  <div class="tarot-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">塔罗占卜</h1>
      </div>

      <!-- 积分提示 -->
      <div class="points-hint card">
        <span class="hint-icon">💎</span>
        <span>本次占卜将消耗 <strong>5 积分</strong></span>
        <span class="current-points">当前积分: {{ currentPoints }}</span>
      </div>

      <div v-if="currentPoints < 5" class="insufficient-points card">
        <p>💡 积分不足，请先 <router-link to="/profile">签到领取积分</router-link></p>
      </div>

      <div class="tarot-intro card">
        <h2>选择您的牌阵</h2>
        <div class="spread-options">
          <div 
            v-for="spread in spreads" 
            :key="spread.id"
            class="spread-card"
            :class="{ active: selectedSpread === spread.id }"
            @click="selectedSpread = spread.id"
          >
            <div class="spread-icon">{{ spread.icon }}</div>
            <h3>{{ spread.name }}</h3>
            <p>{{ spread.description }}</p>
          </div>
        </div>
      </div>

      <div class="question-section card">
        <h3>您想咨询的问题</h3>
        <el-input
          v-model="question"
          type="textarea"
          :rows="3"
          placeholder="请描述您想咨询的问题，越具体越好..."
        />
        <el-button
          type="primary"
          size="large"
          @click="showConfirm"
          :loading="loading"
          :disabled="!question || currentPoints < 5"
          class="draw-btn"
        >
          开始抽牌
        </el-button>
      </div>

      <div v-if="cards.length > 0" class="cards-result card">
        <h3>您的牌阵</h3>
        <div class="cards-display">
          <div 
            v-for="(card, index) in cards" 
            :key="index"
            class="tarot-card"
            :class="{ reversed: card.reversed }"
          >
            <div class="card-inner" :style="getCardStyle(card)">
              <div class="card-number">{{ index + 1 }}</div>
              <div class="card-emoji">{{ card.emoji }}</div>
              <div class="card-name">{{ card.name }}</div>
              <div class="card-position">{{ getPositionName(index) }}</div>
              <div class="card-element" :class="card.element">{{ card.element }}</div>
              <div v-if="card.reversed" class="reversed-badge">逆位</div>
            </div>
          </div>
        </div>
        
        <div class="interpretation">
          <h3>牌面解读</h3>
          <div class="interpretation-content" v-html="interpretation"></div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="result-actions">
          <el-button type="primary" @click="saveTarotResult">
            <span class="btn-icon">💾</span> 保存记录
          </el-button>
          <el-button @click="shareTarotResult">
            <span class="btn-icon">📤</span> 分享
          </el-button>
          <el-button @click="resetTarot">
            <span class="btn-icon">🔄</span> 重新占卜
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { drawTarot, interpretTarot, getPointsBalance } from '../api'
import BackButton from '../components/BackButton.vue'

const spreads = [
  { id: 'single', name: '单张牌', icon: '🎴', description: '简单直接，适合快速解答' },
  { id: 'three', name: '三张牌', icon: '🎴🎴🎴', description: '过去、现在、未来' },
  { id: 'celtic', name: '凯尔特十字', icon: '🔮', description: '深度分析，全面解读' },
]

const selectedSpread = ref('three')
const question = ref('')
const loading = ref(false)
const cards = ref([])
const interpretation = ref('')
const currentPoints = ref(0)

// 获取当前积分
const loadPoints = async () => {
  try {
    const response = await getPointsBalance()
    if (response.code === 0) {
      currentPoints.value = response.data.balance
    }
  } catch (error) {
    console.error('获取积分失败:', error)
  }
}

// 显示确认对话框
const showConfirm = async () => {
  if (!question.value.trim()) {
    ElMessage.warning('请输入您想咨询的问题')
    return
  }
  if (currentPoints.value < 5) {
    ElMessage.warning('积分不足，请先签到领取积分')
    return
  }

  try {
    await ElMessageBox.confirm(
      `本次占卜将消耗 5 积分，确认继续吗？`,
      '确认占卜',
      {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: 'info',
      }
    )
    await drawCards()
  } catch {
    // 用户取消
  }
}

const getPositionName = (index) => {
  const positions = {
    'single': ['指引'],
    'three': ['过去', '现在', '未来'],
    'celtic': ['现状', '阻碍', '基础', '过去', '目标', '未来', '自我', '环境', '希望', '结果'],
  }
  return positions[selectedSpread.value]?.[index] || `位置 ${index + 1}`
}

const drawCards = async () => {
  loading.value = true
  try {
    const drawResponse = await drawTarot({
      spread: selectedSpread.value,
      question: question.value,
    })

    if (drawResponse.code === 0) {
      cards.value = drawResponse.data.cards
      currentPoints.value = drawResponse.data.remaining_points

      const interpretResponse = await interpretTarot({
        cards: cards.value,
        question: question.value,
      })

      if (interpretResponse.code === 0) {
        interpretation.value = interpretResponse.data.interpretation
        ElMessage.success('抽牌成功')
      }
    } else {
      ElMessage.error(drawResponse.message || '抽牌失败')
      if (drawResponse.code === 403) {
        loadPoints()
      }
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPoints()
})

// 获取卡片样式
const getCardStyle = (card) => {
  return {
    background: `linear-gradient(135deg, ${card.color}40 0%, ${card.color}20 100%)`,
    borderColor: card.color
  }
}

// 保存塔罗结果
const saveTarotResult = () => {
  const savedResults = JSON.parse(localStorage.getItem('tarot_saved') || '[]')
  savedResults.unshift({
    date: new Date().toISOString(),
    question: question.value,
    cards: cards.value,
    interpretation: interpretation.value,
    spread: selectedSpread.value
  })
  if (savedResults.length > 50) {
    savedResults.pop()
  }
  localStorage.setItem('tarot_saved', JSON.stringify(savedResults))
  ElMessage.success('保存成功，可在个人中心查看')
}

// 分享塔罗结果
const shareTarotResult = () => {
  const cardNames = cards.value.map(c => c.name + (c.reversed ? '(逆位)' : '(正位)')).join('、')
  const shareText = `我在太初命理进行了塔罗占卜\n` +
    `问题：${question.value}\n` +
    `抽到的牌：${cardNames}\n` +
    `快来体验吧！`
  
  if (navigator.share) {
    navigator.share({
      title: '我的塔罗占卜结果',
      text: shareText
    })
  } else {
    navigator.clipboard.writeText(shareText).then(() => {
      ElMessage.success('分享内容已复制到剪贴板')
    })
  }
}

// 重置占卜
const resetTarot = () => {
  cards.value = []
  interpretation.value = ''
  question.value = ''
}
</script>

<style scoped>
.tarot-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

.points-hint {
  max-width: 900px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px 20px;
  color: rgba(255, 255, 255, 0.9);
}

.hint-icon {
  font-size: 20px;
}

.current-points {
  margin-left: auto;
  color: #ffd700;
  font-weight: 500;
}

.insufficient-points {
  max-width: 900px;
  margin: 0 auto 20px;
  padding: 15px 20px;
  text-align: center;
}

.insufficient-points a {
  color: #e94560;
  text-decoration: underline;
}

.tarot-intro {
  max-width: 900px;
  margin: 0 auto 30px;
}

.tarot-intro h2 {
  text-align: center;
  margin-bottom: 30px;
  color: #fff;
}

.spread-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.spread-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  padding: 25px;
  text-align: center;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.spread-card:hover,
.spread-card.active {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

.spread-icon {
  font-size: 36px;
  margin-bottom: 15px;
}

.spread-card h3 {
  color: #fff;
  margin-bottom: 10px;
}

.spread-card p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.question-section {
  max-width: 600px;
  margin: 0 auto 30px;
}

.question-section h3 {
  margin-bottom: 20px;
  color: #fff;
}

.draw-btn {
  margin-top: 20px;
  width: 100%;
}

.cards-result {
  max-width: 900px;
  margin: 0 auto;
}

.cards-result h3 {
  text-align: center;
  margin-bottom: 30px;
  color: #fff;
}

.cards-display {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.tarot-card {
  width: 150px;
  height: 250px;
  perspective: 1000px;
}

.card-inner {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 15px;
  border: 2px solid rgba(233, 69, 96, 0.3);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 20px;
  text-align: center;
  transition: all 0.3s ease;
}

.tarot-card.reversed .card-inner {
  transform: rotate(180deg);
}

.card-number {
  font-size: 24px;
  color: #e94560;
  font-weight: bold;
  margin-bottom: 20px;
}

.card-name {
  font-size: 18px;
  color: #fff;
  font-weight: 500;
}

.card-position {
  margin-top: 15px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

.interpretation {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 30px;
}

.interpretation-content {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  white-space: pre-line;
}

/* 塔罗牌样式 */
.card-inner {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 15px;
  border: 2px solid rgba(233, 69, 96, 0.3);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 15px;
  text-align: center;
  transition: all 0.3s ease;
  position: relative;
}

.tarot-card:hover .card-inner {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.tarot-card.reversed .card-inner {
  transform: rotate(180deg);
}

.tarot-card.reversed:hover .card-inner {
  transform: rotate(180deg) translateY(-5px);
}

.card-emoji {
  font-size: 48px;
  margin: 10px 0;
}

.card-element {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  margin-top: 8px;
}

.card-element.火 { background: rgba(255, 69, 0, 0.3); color: #ff6347; }
.card-element.水 { background: rgba(30, 144, 255, 0.3); color: #87ceeb; }
.card-element.木 { background: rgba(34, 139, 34, 0.3); color: #90ee90; }
.card-element.金 { background: rgba(255, 215, 0, 0.3); color: #ffd700; }
.card-element.土 { background: rgba(139, 69, 19, 0.3); color: #deb887; }
.card-element.风 { background: rgba(147, 112, 219, 0.3); color: #dda0dd; }

.reversed-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(233, 69, 96, 0.8);
  color: #fff;
  font-size: 10px;
  padding: 3px 8px;
  border-radius: 10px;
}

/* 操作按钮 */
.result-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
  flex-wrap: wrap;
}

.result-actions .btn-icon {
  margin-right: 5px;
}

@media (max-width: 768px) {
  .spread-options {
    grid-template-columns: 1fr;
  }
  
  .cards-display {
    gap: 15px;
  }
  
  .tarot-card {
    width: 120px;
    height: 220px;
  }
  
  .card-emoji {
    font-size: 36px;
  }
  
  .card-name {
    font-size: 14px;
  }
}
</style>
