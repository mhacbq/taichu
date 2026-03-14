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
        <p class="cards-hint">💡 点击任意牌查看详细解读</p>
        <div class="cards-display">
          <div 
            v-for="(card, index) in cards" 
            :key="index"
            class="tarot-card"
            :class="{ reversed: card.reversed }"
            @click="showCardDetail(card, index)"
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

      <!-- 单张牌详情弹窗 -->
      <el-dialog
        v-model="cardDetailVisible"
        :title="selectedCard?.name + ' - ' + (selectedCard?.reversed ? '逆位' : '正位')"
        width="500px"
        class="card-detail-dialog"
      >
        <div v-if="selectedCard" class="card-detail">
          <div class="detail-header" :style="getCardStyle(selectedCard)">
            <span class="detail-emoji">{{ selectedCard.emoji }}</span>
            <span class="detail-element" :class="selectedCard.element">{{ selectedCard.element }}</span>
          </div>
          <div class="detail-content">
            <h4>牌面含义</h4>
            <p class="detail-meaning">{{ selectedCard.meaning }}</p>
            
            <h4>详细解读</h4>
            <div class="detail-interpretation">
              <div class="interp-section">
                <h5>{{ selectedCard.reversed ? '逆位' : '正位' }}含义</h5>
                <p>{{ getCardDetailedMeaning(selectedCard) }}</p>
              </div>
              <div class="interp-section">
                <h5>关键启示</h5>
                <p>{{ getCardAdvice(selectedCard) }}</p>
              </div>
            </div>
          </div>
        </div>
      </el-dialog>
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
const cardDetailVisible = ref(false)
const selectedCard = ref(null)
const selectedCardIndex = ref(0)

// 塔罗牌详细解读数据
const cardDetailedMeanings = {
  '愚者': {
    upright: '新的开始，充满潜力和可能性，勇敢地迈出第一步',
    reversed: '缺乏计划，过于冲动，需要更多思考和准备',
    advice: '相信自己的直觉，但也要脚踏实地'
  },
  '魔术师': {
    upright: '创造力爆发，拥有实现目标的资源和能力',
    reversed: '技能未充分发挥，可能存在欺骗或操纵',
    advice: '运用你的才能，相信自己有实现梦想的能力'
  },
  '女祭司': {
    upright: '倾听内心的声音，直觉会给你正确的指引',
    reversed: '忽视直觉，过于依赖理性分析',
    advice: '静心冥想，答案就在你心中'
  },
  '皇后': {
    upright: '丰饶与滋养，享受生活中的美好事物',
    reversed: '过度放纵，缺乏安全感',
    advice: '关爱自己，同时也关爱身边的人'
  },
  '皇帝': {
    upright: '建立秩序和结构，展现领导力',
    reversed: '专横跋扈，过度控制',
    advice: '建立稳定的框架，但不要过于僵化'
  },
  '教皇': {
    upright: '遵循传统智慧，寻求精神指导',
    reversed: '打破传统，走自己的路',
    advice: '尊重传统，但也要有自己的判断'
  },
  '恋人': {
    upright: '和谐的关系，重要的选择，爱情的到来',
    reversed: '关系失衡，选择困难',
    advice: '跟随内心，做出真诚的选择'
  },
  '战车': {
    upright: '意志坚定，克服困难，取得胜利',
    reversed: '方向不明，力量分散',
    advice: '保持专注，控制好自己的情绪'
  },
  '力量': {
    upright: '内在的力量，以柔克刚，耐心与勇气',
    reversed: '失去信心，被恐惧支配',
    advice: '相信自己的力量，温柔而坚定地面对挑战'
  },
  '隐者': {
    upright: ' introspection, seeking inner wisdom, solitude',
    reversed: 'isolation, loneliness, rejecting guidance',
    advice: 'Take time to reflect and find your inner light'
  },
  '命运之轮': {
    upright: '命运的转折，周期的变化，把握机会',
    reversed: '抗拒变化，错失良机',
    advice: '顺应变化，把握命运的转机'
  },
  '正义': {
    upright: '公正公平，因果报应，理性决策',
    reversed: '不公正，逃避责任',
    advice: '做出公正的决定，承担相应的责任'
  },
  '倒吊人': {
    upright: '牺牲与等待，新的视角，耐心',
    reversed: '抗拒改变，无谓的牺牲',
    advice: '换个角度看问题，有时等待是最好的选择'
  },
  '死神': {
    upright: '结束与转变，新的开始，放下过去',
    reversed: '抗拒结束，停滞不前',
    advice: '接受变化，结束是为了更好的开始'
  },
  '节制': {
    upright: '平衡与调和，耐心，中庸之道',
    reversed: '极端，失衡，过度',
    advice: '保持平衡，避免走极端'
  },
  '恶魔': {
    upright: '物质诱惑，束缚，上瘾',
    reversed: '挣脱束缚，重获自由',
    advice: '认识到束缚你的东西，勇敢挣脱'
  },
  '塔': {
    upright: '突然的变化，觉醒，打破旧有模式',
    reversed: '避免改变，内在的崩溃',
    advice: '接受必要的改变，从中学习成长'
  },
  '星星': {
    upright: '希望与灵感，宁静，信心的恢复',
    reversed: '失去信心，绝望',
    advice: '保持希望，相信美好的未来'
  },
  '月亮': {
    upright: '幻觉与恐惧，潜意识，直觉',
    reversed: '真相大白，恐惧消散',
    advice: '面对内心的恐惧，寻找真相'
  },
  '太阳': {
    upright: '快乐与成功，活力，积极能量',
    reversed: '暂时的阴霾，失去信心',
    advice: '保持乐观，阳光总在风雨后'
  },
  '审判': {
    upright: '重生与觉醒，宽恕，新的开始',
    reversed: '自我怀疑，逃避审判',
    advice: '接受自己的过去，勇敢地开始新篇章'
  },
  '世界': {
    upright: '完成与成就，圆满，旅行的结束',
    reversed: '未完成，延迟，缺乏closure',
    advice: '庆祝你的成就，准备开始新的旅程'
  }
}

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

// 显示卡片详情
const showCardDetail = (card, index) => {
  selectedCard.value = card
  selectedCardIndex.value = index
  cardDetailVisible.value = true
}

// 获取卡片详细含义
const getCardDetailedMeaning = (card) => {
  const meaning = cardDetailedMeanings[card.name]
  if (!meaning) return card.meaning
  return card.reversed ? meaning.reversed : meaning.upright
}

// 获取卡片建议
const getCardAdvice = (card) => {
  const meaning = cardDetailedMeanings[card.name]
  if (!meaning) return '相信直觉，找到属于你的答案'
  return meaning.advice
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

.cards-hint {
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  margin-bottom: 20px;
}

.tarot-card {
  cursor: pointer;
}

/* 卡片详情弹窗 */
.card-detail-dialog :deep(.el-dialog__header) {
  text-align: center;
}

.card-detail-dialog :deep(.el-dialog__title) {
  color: #fff;
  font-size: 20px;
}

.card-detail {
  text-align: center;
}

.detail-header {
  padding: 30px;
  border-radius: 15px;
  margin-bottom: 25px;
  position: relative;
}

.detail-emoji {
  font-size: 80px;
}

.detail-element {
  position: absolute;
  top: 15px;
  right: 15px;
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 12px;
  background: rgba(255, 255, 255, 0.2);
}

.detail-content {
  text-align: left;
}

.detail-content h4 {
  color: #e94560;
  font-size: 16px;
  margin-bottom: 10px;
}

.detail-meaning {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.8;
  margin-bottom: 20px;
}

.detail-interpretation {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
}

.interp-section {
  margin-bottom: 20px;
}

.interp-section:last-child {
  margin-bottom: 0;
}

.interp-section h5 {
  color: #ffd700;
  font-size: 14px;
  margin-bottom: 8px;
}

.interp-section p {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.7;
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
