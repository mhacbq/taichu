<template>
  <div class="tarot-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">塔罗占卜</h1>
      </div>

      <!-- 积分提示 -->
      <div class="points-hint card card-hover">
        <el-icon class="hint-icon"><Coin /></el-icon>
        <span>本次占卜将消耗 <strong>5 积分</strong></span>
        <span class="current-points">当前积分: {{ pointsDisplayText }}</span>
        <div v-if="pointsError" class="points-warning" role="status" aria-live="polite">
          <span>积分同步失败，请先重新获取后再继续占卜</span>
          <el-button link type="warning" class="points-retry" @click="loadPoints" :loading="pointsLoading">重新获取积分</el-button>
        </div>
      </div>

      <div v-if="currentPoints !== null && currentPoints < 5" class="insufficient-points card card-hover">
        <p><el-icon><MagicStick /></el-icon> 积分不足，请先 <router-link to="/profile">签到领取积分</router-link></p>
      </div>

      <div class="tarot-intro card card-hover">
        <h2>选择您的牌阵</h2>
        <div class="spread-options">
          <button
            v-for="spread in spreads"
            :key="spread.id"
            type="button"
            class="spread-card card-hover"
            :class="{ active: displayedSpread === spread.id }"
            :aria-pressed="displayedSpread === spread.id"
            :disabled="isTarotContextLocked"
            :aria-disabled="isTarotContextLocked"
            @click="selectSpread(spread.id)"
          >

            <div class="spread-icon">
              <el-icon v-if="spread.icon === 'card'"><Document /></el-icon>
              <el-icon v-else-if="spread.icon === 'cards'"><ChatDotRound /></el-icon>
              <el-icon v-else-if="spread.icon === 'magic'"><MagicStick /></el-icon>
            </div>
            <span v-if="selectedSpread === spread.id" class="spread-status-badge">
              <el-icon><Select /></el-icon>
            </span>
            <h3>{{ spread.name }}</h3>
            <p>{{ spread.description }}</p>
          </button>
        </div>
      </div>

      <!-- 问题引导区域 -->
      <div v-if="showQuestionGuide" class="question-guide card card-hover">
        <div class="question-guide__header">
          <h3>
            <el-icon class="guide-icon"><ChatDotRound /></el-icon>
            不知道问什么？选择一个你关心的话题
          </h3>
          <el-button
            v-if="question && cards.length === 0"
            link
            type="primary"
            class="guide-toggle"
            @click="toggleQuestionGuide"
          >
            {{ questionGuideExpanded ? '收起模板' : '继续查看模板' }}
          </el-button>
        </div>
        <div v-show="questionGuideExpanded || (!question && cards.length === 0)">
          <div class="topic-tabs">
            <button
              v-for="topic in questionTopics"
              :key="topic.id"
              type="button"
              class="topic-tab card-hover"
              :class="{ active: selectedTopic === topic.id }"
              :aria-pressed="selectedTopic === topic.id"
              @click="selectTopic(topic.id)"
            >
              <span class="topic-icon">
                <el-icon v-if="topic.icon === 'briefcase'"><Briefcase /></el-icon>
                <el-icon v-else-if="topic.icon === 'heart'"><StarFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'star'"><StarFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'question'"><QuestionFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'users'"><UserFilled /></el-icon>
              </span>
              <span class="topic-name">{{ topic.name }}</span>
            </button>
          </div>
          <div class="question-templates" v-if="selectedTopic">
            <p class="template-hint">点击选择一个问题，或以此为灵感输入你自己的问题：</p>
            <div class="template-list">
              <button
                v-for="(template, index) in currentTemplates"
                :key="index"
                type="button"
                class="template-item card-hover"
                @click="selectQuestion(template)"
              >
                <span class="template-bullet">•</span>
                <span class="template-text">{{ template }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>


      <div class="question-section card card-hover" :class="{ 'question-section--locked': isResultLocked }">
        <h3>您想咨询的问题</h3>
        <el-input
          v-model="question"
          type="textarea"
          :rows="3"
          :readonly="isTarotContextLocked"
          placeholder="描述你的困惑，越具体越好。比如：'我应该接受这份新工作吗？'"
        />
        <div class="question-hint" v-if="question && !isResultLocked">
          <el-icon class="hint-icon"><MagicStick /></el-icon>
          <span>好的问题通常是开放性的，以"我应该..."或"我该如何..."开头</span>
        </div>
        <div v-else-if="isResultLocked" class="question-lock-note" role="status" aria-live="polite">
          <el-icon class="hint-icon"><Select /></el-icon>
          <span>本次抽牌已锁定问题与牌阵，保存记录、分享与详情弹窗都会沿用当前上下文。想修改的话，直接点“重新占卜”。</span>
        </div>
        <el-button
          type="primary"
          size="large"
          @click="showConfirm"
          :loading="loading"
          :disabled="!canDrawTarot"
          class="draw-btn"
        >
          <el-icon class="btn-icon"><Document /></el-icon>
          开始抽牌
        </el-button>
      </div>


      <div v-if="flowError && cards.length === 0" class="flow-error card card-hover">
        <EmptyState
          type="error"
          size="small"
          :title="flowErrorTitle"
          :description="flowErrorDescription"
        >
          <template #extra>
            <div class="flow-error-actions">
              <el-button type="primary" @click="retryLastAction" :loading="loading">{{ flowErrorActionText }}</el-button>
              <el-button @click="resetTarot">重新整理问题</el-button>
            </div>
          </template>
        </EmptyState>
      </div>

      <div v-if="cards.length > 0" class="cards-result card card-hover">
        <h3>您的牌阵</h3>
        <p class="cards-hint"><el-icon><MagicStick /></el-icon> 点击或按回车查看详细解读</p>
        <div v-if="submittedQuestionDisplay" class="result-context-summary" aria-label="本次塔罗结果上下文">
          <span class="result-context-chip">
            <strong>牌阵</strong>
            <span>{{ submittedSpreadName }}</span>
          </span>
          <span class="result-context-chip result-context-chip--question">
            <strong>问题</strong>
            <span>{{ submittedQuestionDisplay }}</span>
          </span>
        </div>
        <div class="cards-display">
          <div
            v-for="(card, index) in cards"
            :key="`${card.name}-${index}`"
            class="card-stack"
          >
            <button
              type="button"
              class="card-detail-trigger"
              :aria-label="getCardDetailAriaLabel(card, index)"
              aria-haspopup="dialog"
              @click="showCardDetail(card, index)"
            >
              <TarotCard
                :name="card.name"
                :emoji="card.emoji"
                :reversed="card.reversed"
                :element="card.element"
                :color="card.color"
                :index="index"
              />

              <span v-if="cards.length > 1" class="card-position">{{ getPositionLabel(displayedSpread, index) }}</span>
            </button>
          </div>
        </div>


        <div v-if="flowError && cards.length > 0" class="flow-error flow-error--inline card card-hover">
          <EmptyState
            type="error"
            size="small"
            inline
            :title="flowErrorTitle"
            :description="flowErrorDescription"
          >
            <template #extra>
              <div class="flow-error-actions">
                <el-button type="primary" @click="retryLastAction" :loading="loading">{{ flowErrorActionText }}</el-button>
                <el-button @click="resetTarot">重新占卜</el-button>
              </div>
            </template>
          </EmptyState>
        </div>

        <div class="interpretation" :class="{ 'interpretation--pending': interpretationState !== 'ready' }">
          <h3>牌面解读</h3>
          <div v-if="interpretationState === 'ready'" class="interpretation-content">{{ interpretation }}</div>
          <p v-else-if="interpretationState === 'loading'" class="interpretation-placeholder interpretation-placeholder--loading">牌面已经抽出，正在结合你的问题生成解读，请稍候...</p>
          <p v-else-if="interpretationState === 'error'" class="interpretation-placeholder interpretation-placeholder--error">本次牌面已抽出，但解读暂时中断。你可以直接点击“重试解读”，无需重新抽牌。</p>
          <p v-else class="interpretation-placeholder">抽牌完成后，这里会展示与你问题对应的牌面解读。</p>
        </div>

        
        <!-- 操作按钮 -->
        <div class="result-actions">
          <el-button type="primary" @click="saveTarotResult" :disabled="!interpretation">
            <el-icon class="btn-icon"><Download /></el-icon> 保存记录
          </el-button>
          <el-button @click="shareTarotResult" :disabled="!interpretation">
            <el-icon class="btn-icon"><Document /></el-icon> 分享
          </el-button>
          <el-button @click="resetTarot">
            <el-icon class="btn-icon"><RefreshRight /></el-icon> 重新占卜
          </el-button>
        </div>
      </div>

      <!-- 单张牌详情弹窗 -->
      <el-dialog
        v-if="selectedCard"
        v-model="cardDetailVisible"
        :title="selectedCard.name + ' - ' + (selectedCard.reversed ? '逆位' : '正位')"
        width="min(92vw, 500px)"
        class="card-detail-dialog"
      >

        <div v-if="selectedCard" class="card-detail">
          <div class="detail-header-new">
            <TarotCard 
              :name="selectedCard.name"
              :emoji="selectedCard.emoji"
              :reversed="selectedCard.reversed"
              :element="selectedCard.element"
              :color="selectedCard.color"
              class="detail-card"
            />
          </div>
          <div v-if="selectedCard.positionLabel" class="detail-context">
            <span class="card-position card-position--detail">{{ selectedCard.positionLabel }}</span>
            <span v-if="cards.length > 1" class="card-position card-position--detail">第{{ selectedCard.positionIndex + 1 }}张</span>
            <span class="card-position card-position--detail">{{ selectedCard.spreadName }}</span>
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
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { drawTarot, interpretTarot, getPointsBalance, saveTarotRecord, setTarotPublic } from '../api'

import BackButton from '../components/BackButton.vue'
import TarotCard from '../components/TarotCard.vue'
import EmptyState from '../components/EmptyState.vue'
import { Coin, MagicStick, ChatDotRound, Briefcase, StarFilled, UserFilled, QuestionFilled, Document, Download, RefreshRight, Select } from '@element-plus/icons-vue'

const spreads = [
  { id: 'single', name: '单张牌', icon: 'card', description: '简单直接，适合快速解答' },
  { id: 'three', name: '三张牌', icon: 'cards', description: '过去、现在、未来' },
  { id: 'celtic', name: '凯尔特十字', icon: 'magic', description: '深度分析，全面解读' },
]

// 问题引导话题
const questionTopics = [
  { id: 'career', name: '工作事业', icon: 'briefcase' },
  { id: 'love', name: '感情关系', icon: 'heart' },
  { id: 'growth', name: '个人成长', icon: 'star' },
  { id: 'decision', name: '选择困难', icon: 'question' },
  { id: 'relation', name: '人际关系', icon: 'users' },
]

// 问题模板
const questionTemplates = {
  career: [
    '我是否应该接受这份新工作offer？',
    '现在是我换工作的合适时机吗？',
    '我应该如何提升自己在工作中的表现？',
    '我的职业发展方向应该是什么？',
    '如何应对目前工作中的困难和挑战？',
    '我应该创业还是继续打工？',
  ],
  love: [
    '这段感情是否值得我继续投入？',
    '我应该主动表白还是再等等看？',
    '如何改善我和伴侣之间的关系？',
    '我什么时候能遇到合适的人？',
    '前任还会回到我身边吗？',
    '我应该放下这段感情吗？',
  ],
  growth: [
    '我该如何克服目前的迷茫状态？',
    '我最大的优势和需要改进的地方是什么？',
    '如何实现我今年的目标？',
    '我应该学习什么新技能？',
    '如何建立更好的自信心？',
    '我该如何找到人生的方向？',
  ],
  decision: [
    '我应该选择A还是B？',
    '现在采取行动是好时机吗？',
    '我应该继续坚持还是及时止损？',
    '如何做出不会后悔的决定？',
    '我应该听从内心还是理性分析？',
  ],
  relation: [
    '如何改善我和家人之间的关系？',
    '我应该如何与朋友沟通这个问题？',
    '这个人是否值得我信任？',
    '如何处理与同事的冲突？',
    '我该如何扩大自己的社交圈？',
  ],
}

const selectedSpread = ref('three')
const question = ref('')
const loading = ref(false)
const pointsLoading = ref(false)
const pointsError = ref(false)
const cards = ref([])
const interpretation = ref('')
const currentPoints = ref(null)
const cardDetailVisible = ref(false)
const selectedCard = ref(null)
const selectedTopic = ref('')
const questionGuideExpanded = ref(true)
const flowError = ref(null)
const submittedQuestion = ref('')
const submittedSpread = ref('')


const getSpreadName = (spreadType) => {
  return spreads.find((spread) => spread.id === spreadType)?.name || '当前牌阵'
}

const isResultLocked = computed(() => cards.value.length > 0)
const isTarotContextLocked = computed(() => loading.value || isResultLocked.value)
const submittedQuestionDisplay = computed(() => submittedQuestion.value || question.value.trim())
const submittedSpreadName = computed(() => getSpreadName(displayedSpread.value))


const reportUiError = (action, error, userMessage = '') => {

  console.error(`[Tarot] ${action}`, error)
  if (userMessage) {
    ElMessage.error(userMessage)
  }
}

const pointsDisplayText = computed(() => {
  if (pointsLoading.value) {
    return '加载中...'
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    return '暂未获取'
  }

  return currentPoints.value
})

const canDrawTarot = computed(() => {
  if (isResultLocked.value || !question.value.trim() || pointsLoading.value || loading.value) {
    return false
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    return true
  }

  return currentPoints.value >= 5
})



const setFlowError = (stage, message) => {
  flowError.value = {
    stage,
    message,
  }
}

const clearFlowError = () => {
  flowError.value = null
}

const refreshPoints = async ({ silent = false } = {}) => {
  pointsLoading.value = true
  pointsError.value = false

  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      currentPoints.value = response.data.balance
      pointsError.value = false
      return true
    }

    currentPoints.value = null
    pointsError.value = true
    if (!silent) {
      ElMessage.warning(response.message || '获取积分失败，请稍后重试')
    }
  } catch (error) {
    currentPoints.value = null
    pointsError.value = true
    reportUiError('获取积分失败', error, silent ? '' : '获取积分失败，请稍后重试')
  } finally {
    pointsLoading.value = false
  }

  return false
}


const currentTemplates = computed(() => {
  return selectedTopic.value ? questionTemplates[selectedTopic.value] : []
})

const showQuestionGuide = computed(() => cards.value.length === 0 && (!question.value || questionGuideExpanded.value))

const toggleQuestionGuide = () => {
  questionGuideExpanded.value = !questionGuideExpanded.value
}

const selectSpread = (spreadId) => {
  if (isTarotContextLocked.value) {
    return
  }

  selectedSpread.value = spreadId
}

const displayedSpread = computed(() => submittedSpread.value || selectedSpread.value)


const flowErrorTitle = computed(() => {

  if (!flowError.value) return ''
  return flowError.value.stage === 'interpret' ? '牌面已抽出，但解读未完成' : '抽牌流程暂时中断'
})

const flowErrorDescription = computed(() => {
  if (!flowError.value) return ''

  return flowError.value.stage === 'interpret'
    ? `最近失败原因：${flowError.value.message}。您可以直接重试解读，无需重新抽牌。`
    : `最近失败原因：${flowError.value.message}。请稍后重试，系统会重新发起抽牌流程。`
})

const flowErrorActionText = computed(() => {
  if (!flowError.value) return '重试'
  return flowError.value.stage === 'interpret' ? '重试解读' : '重试抽牌'
})

const interpretationState = computed(() => {
  if (interpretation.value) {
    return 'ready'
  }

  if (loading.value && cards.value.length > 0 && !flowError.value) {
    return 'loading'
  }

  if (flowError.value?.stage === 'interpret') {
    return 'error'
  }

  return 'idle'
})

const spreadPositionLabels = {

  single: ['今日指引'],
  three: ['过去', '现在', '未来'],
  celtic: [
    '当前状态',
    '障碍/挑战',
    '潜意识/基础',
    '过去影响',
    '目标可能',
    '近期发展',
    '你的态度',
    '外部环境',
    '希望/恐惧',
    '最终走向',
  ],
}

const getPositionLabel = (spreadType, index) => {
  const labels = spreadPositionLabels[spreadType] || []
  return labels[index] || `第${index + 1}张`
}

const selectTopic = (topicId) => {
  selectedTopic.value = topicId
  questionGuideExpanded.value = true
}

const selectQuestion = (template) => {
  if (isTarotContextLocked.value) {
    return
  }

  question.value = template
  questionGuideExpanded.value = false
}

const getCurrentTarotQuestion = () => submittedQuestion.value || question.value.trim()
const getCurrentTarotSpread = () => submittedSpread.value || selectedSpread.value
const getCurrentCardNamesText = () => cards.value.map((card) => `${card.name}${card.reversed ? '(逆位)' : '(正位)'}`).join('、')

const getCardDetailAriaLabel = (card, index) => {
  const positionLabel = getPositionLabel(displayedSpread.value, index)
  const directionLabel = card.reversed ? '逆位' : '正位'
  return `查看${positionLabel}${card.name}${directionLabel}的详细解读`
}



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
    upright: '内省沉思，寻求内在智慧，独处静修',
    reversed: '孤立自闭，孤独寂寞，拒绝指引',
    advice: '花时间反思，找到内心的光芒'
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
  await refreshPoints()
}

const interpretCurrentCards = async () => {
  const interpretQuestion = getCurrentTarotQuestion()
  const interpretResponse = await interpretTarot({
    cards: cards.value,
    question: interpretQuestion,
  })

  if (interpretResponse.code === 200) {
    interpretation.value = interpretResponse.data.interpretation
    clearFlowError()
    ElMessage.success('抽牌成功')
    return true
  }

  setFlowError('interpret', interpretResponse.message || '解读失败')
  ElMessage.error(interpretResponse.message || '解读失败')
  if (interpretResponse.code === 403) {
    await loadPoints()
  }

  return false
}


// 显示确认对话框
const showConfirm = async () => {
  if (!question.value.trim()) {
    ElMessage.warning('请输入您想咨询的问题')
    return
  }

  if (pointsError.value || currentPoints.value === null || currentPoints.value === undefined) {
    const recovered = await refreshPoints()
    if (!recovered) {
      ElMessage.warning('积分同步失败，请先重新获取积分')
      return
    }
  }

  if (currentPoints.value === null || currentPoints.value === undefined) {
    ElMessage.warning('积分状态同步中，请稍后再试')
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


const drawCards = async () => {
  const lockedQuestion = question.value.trim()
  const lockedSpread = selectedSpread.value

  loading.value = true
  try {
    const drawResponse = await drawTarot({
      spread: lockedSpread,
      question: lockedQuestion,
    })

    if (drawResponse.code === 200) {
      cards.value = drawResponse.data.cards
      interpretation.value = ''
      submittedQuestion.value = lockedQuestion
      submittedSpread.value = lockedSpread
      question.value = lockedQuestion
      selectedSpread.value = lockedSpread
      savedRecordId.value = null
      savedShareCode.value = null
      sharePublicConfirmed.value = false

      const remainingPoints = Number(drawResponse.data.remaining_points)

      if (Number.isFinite(remainingPoints)) {
        currentPoints.value = remainingPoints
        window.dispatchEvent(new CustomEvent('points-updated', {
          detail: {
            balance: remainingPoints,
          },
        }))
      }

      clearFlowError()

      await interpretCurrentCards()
      return
    }


    setFlowError('draw', drawResponse.message || '抽牌失败')
    ElMessage.error(drawResponse.message || '抽牌失败')
    if (drawResponse.code === 403) {
      await loadPoints()
    }
  } catch (error) {
    setFlowError('draw', '网络错误，请稍后重试')
    reportUiError('抽牌失败', error, '网络错误，请稍后重试')
  } finally {
    loading.value = false
  }
}

const retryLastAction = async () => {
  if (!flowError.value) {
    return
  }

  if (flowError.value.stage === 'interpret' && cards.value.length > 0) {
    loading.value = true
    try {
      await interpretCurrentCards()
    } catch (error) {
      setFlowError('interpret', '网络错误，请稍后重试')
      reportUiError('重试塔罗解读失败', error, '网络错误，请稍后重试')
    } finally {
      loading.value = false
    }
    return
  }

  await drawCards()
}

const handlePointsUpdated = () => {
  refreshPoints({ silent: true })
}

onMounted(() => {
  loadPoints()
  window.addEventListener('points-updated', handlePointsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('points-updated', handlePointsUpdated)
})


// 当前保存的记录信息
const savedRecordId = ref(null)
const savedShareCode = ref(null)
const sharePublicConfirmed = ref(false)

// 保存塔罗结果到后端
const saveTarotResult = async () => {
  if (savedRecordId.value) {
    ElMessage.info('已经保存过了')
    return true
  }
  
  try {
    const response = await saveTarotRecord({
      spread_type: getCurrentTarotSpread(),
      question: getCurrentTarotQuestion(),
      cards: cards.value,
      interpretation: interpretation.value,
      ai_analysis: ''
    })


    if (response.code === 200) {
      savedRecordId.value = response.data.record_id
      savedShareCode.value = response.data.share_code
      sharePublicConfirmed.value = false
      window.dispatchEvent(new CustomEvent('tarot-history-updated', {
        detail: {
          recordId: savedRecordId.value,
        },
      }))
      ElMessage.success('保存成功，已同步到云端塔罗历史，可在个人中心查看')
      return true
    }

    ElMessage.error(response.message || '保存失败')
  } catch (error) {
    reportUiError('保存塔罗记录失败', error, '保存失败，请稍后重试')
  }

  return false
}


const updateTarotShareVisibility = async (isPublic, { silent = false } = {}) => {
  if (!savedRecordId.value) {
    return false
  }

  const errorMessage = isPublic ? '分享链接准备失败，请稍后重试' : '恢复私密状态失败，请稍后重试'

  try {
    const response = await setTarotPublic({ id: savedRecordId.value, is_public: isPublic })
    if (response.code === 200) {
      savedShareCode.value = response.data.share_code || savedShareCode.value
      sharePublicConfirmed.value = isPublic
      return true
    }

    if (!silent) {
      ElMessage.error(response.message || errorMessage)
    }
  } catch (error) {
    reportUiError(
      isPublic ? '准备塔罗分享链接失败' : '恢复塔罗私密状态失败',
      error,
      silent ? '' : errorMessage
    )
  }

  return false
}

const ensureTarotShareReady = async () => {
  if (!savedRecordId.value) {
    return { ready: false, exposedNow: false }
  }

  if (sharePublicConfirmed.value && savedShareCode.value) {
    return { ready: true, exposedNow: false }
  }

  try {
    await ElMessageBox.confirm(
      '分享前需要先将本次塔罗记录设为公开，仅持有链接的人可以查看。若你稍后取消系统分享，我们会自动恢复为私密状态。',
      '确认公开分享',
      {
        confirmButtonText: '确认公开并分享',
        cancelButtonText: '先不分享',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    )
  } catch (actionOrError) {
    if (actionOrError === 'cancel' || actionOrError === 'close' || actionOrError?.name === 'AbortError') {
      return { ready: false, exposedNow: false }
    }

    ElMessage.error('分享确认失败，请稍后重试')
    return { ready: false, exposedNow: false }
  }

  const ready = await updateTarotShareVisibility(true)
  return { ready, exposedNow: ready }
}

// 分享塔罗结果
const shareTarotResult = async () => {
  // 如果没有保存过，先保存
  if (!savedRecordId.value) {
    const saved = await saveTarotResult()
    if (!saved) {
      return
    }
  }

  
  if (!savedRecordId.value) {
    return
  }

  const { ready: shareReady, exposedNow } = await ensureTarotShareReady()
  if (!shareReady || !savedShareCode.value) {
    return
  }

  // 生成分享链接
  const shareUrl = `${window.location.origin}/tarot/share/${savedShareCode.value}`
  const cardNames = getCurrentCardNamesText()
  const shareText = `我在太初命理进行了塔罗占卜\n` +
    `问题：${getCurrentTarotQuestion()}\n` +
    `抽到的牌：${cardNames}\n` +
    `查看详情：${shareUrl}`

  
  if (navigator.share) {
    try {
      await navigator.share({
        title: '我的塔罗占卜结果',
        text: shareText,
        url: shareUrl
      })
    } catch (error) {
      if (exposedNow) {
        const reverted = await updateTarotShareVisibility(false)
        if (reverted && error?.name === 'AbortError') {
          ElMessage.info('已取消分享，本次记录仍保持私密')
        }
      }

      if (error?.name !== 'AbortError') {
        reportUiError('系统分享失败', error, '分享失败，请稍后重试')
      }
    }

    return
  }

  try {
    await navigator.clipboard.writeText(shareText)
    ElMessage.success('分享链接已复制到剪贴板')
  } catch (error) {
    if (exposedNow) {
      await updateTarotShareVisibility(false)
    }
    reportUiError('复制分享内容失败', error, '复制失败，请手动复制')
  }
}




// 重置占卜
const resetTarot = () => {
  cards.value = []
  interpretation.value = ''
  question.value = ''
  submittedQuestion.value = ''
  submittedSpread.value = ''
  selectedTopic.value = ''
  questionGuideExpanded.value = true
  savedRecordId.value = null
  savedShareCode.value = null
  sharePublicConfirmed.value = false
  clearFlowError()
}



// 显示卡片详情
const showCardDetail = (card, index = 0) => {
  const currentSpread = getCurrentTarotSpread()
  selectedCard.value = {
    ...card,
    positionIndex: index,
    positionLabel: getPositionLabel(currentSpread, index),
    spreadType: currentSpread,
    spreadName: getSpreadName(currentSpread),
  }
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
  color: var(--text-primary);
}

.hint-icon {
  font-size: 20px;
}

.current-points {
  margin-left: auto;
  color: var(--wuxing-jin);
  font-weight: 500;
}

.points-warning {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  color: var(--warning-color, #d97706);
  font-size: 13px;
}

.points-retry {
  padding: 0;
  min-height: auto;
  font-weight: 600;
}


.insufficient-points {
  max-width: 900px;
  margin: 0 auto 20px;
  padding: 15px 20px;
  text-align: center;
}

.insufficient-points a {
  color: var(--primary-color);
  text-decoration: underline;
}

.tarot-intro {
  max-width: 900px;
  margin: 0 auto 30px;
}

.tarot-intro h2 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--text-primary);
}

.spread-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.spread-card {
  appearance: none;
  width: 100%;
  background: var(--surface-raised);
  border-radius: var(--radius-lg);
  padding: 30px 20px;
  text-align: center;
  cursor: pointer;
  border: 1px solid var(--border-light);
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  color: inherit;
  font: inherit;
}

.spread-card:hover {
  border-color: var(--primary-light-40);
  background: var(--surface-hover);
  transform: translateY(-6px);
  box-shadow: var(--shadow-hover);
}

.spread-card.active {
  border-color: var(--primary-color);
  background: linear-gradient(135deg, var(--bg-card) 0%, var(--primary-light-10) 100%);
  box-shadow: 0 16px 32px rgba(var(--primary-rgb), 0.18);
  transform: translateY(-8px) scale(1.02);
  z-index: 2;
}

.spread-card.active::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  padding: 2px;
  background: var(--primary-gradient);
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

.spread-card:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 3px;
  border-color: var(--primary-color);
}

.spread-card:disabled {
  cursor: not-allowed;
  opacity: 0.72;
  transform: none;
}

.spread-card:disabled:hover {
  transform: none;
  box-shadow: none;
  background: var(--surface-raised);
  border-color: var(--border-light);
}


.spread-status-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 30px;
  height: 30px;
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  animation: scale-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 0 15px rgba(var(--primary-rgb), 0.24);
}

@keyframes scale-in {
  from { transform: scale(0) rotate(-45deg); opacity: 0; }
  to { transform: scale(1) rotate(0); opacity: 1; }
}

.spread-icon {
  font-size: 42px;
  margin-bottom: 5px;
  color: var(--primary-color);
  transition: all 0.4s ease;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

.spread-card:hover .spread-icon {
  transform: scale(1.15) translateY(-5px);
  color: var(--primary-light);
}

.spread-card.active .spread-icon {
  transform: scale(1.2);
  filter: drop-shadow(0 0 15px var(--primary-color));
}

.spread-card h3 {
  color: var(--text-primary);
  margin: 0;
  font-size: var(--font-h4);
  font-weight: var(--weight-bold);
  letter-spacing: var(--tracking-normal);
}

.spread-card p {
  color: var(--text-secondary);
  font-size: var(--font-caption);
  line-height: var(--line-height-base);
  margin: 0;
}

/* 问题引导 */
.question-guide {
  max-width: 800px;
  margin: 0 auto 40px;
  background: var(--white-02);
  border: 1px solid var(--white-05);
}

.question-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.question-guide__header h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.guide-toggle {
  padding: 0;
  min-height: auto;
}

.topic-tabs {

  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  margin-bottom: 25px;
}

.topic-tab {
  appearance: none;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 44px;
  padding: 15px 10px;
  background: var(--white-05);
  border: 1px solid var(--white-10);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: inherit;
  font: inherit;
}

.topic-tab:hover {
  background: var(--white-10);
  border-color: var(--primary-light-30);
  transform: translateY(-3px);
}

.topic-tab.active {
  background: var(--primary-gradient);
  border-color: var(--primary-color);
  transform: translateY(-5px);
  box-shadow: 0 8px 20px var(--primary-light-30);
}

.topic-tab:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 3px;
  border-color: var(--primary-color);
}

.topic-tab .topic-icon {
  font-size: 24px;
  color: var(--primary-color);
  transition: all 0.3s ease;
}

.topic-tab.active .topic-icon,
.topic-tab.active .topic-name {
  color: var(--text-accent-contrast);
  font-weight: var(--weight-bold);
}

.template-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.template-item {
  appearance: none;
  width: 100%;
  background: var(--surface-raised);
  border: 1px solid var(--border-light);
  min-height: 44px;
  padding: 14px 18px;
  border-radius: 12px;
  transition: all 0.3s ease;
  text-align: left;
  color: inherit;
  font: inherit;
}

.template-item:hover {
  background: var(--surface-hover);
  border-color: var(--primary-color);
  transform: scale(1.01) translateX(4px);
}

.template-item:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 3px;
  border-color: var(--primary-color);
}

.template-bullet {
  color: var(--primary-color);
  font-weight: var(--weight-bold);
}

.template-text {
  color: var(--text-secondary);
  font-size: var(--font-small);
  line-height: var(--line-height-base);
}

.question-section--locked {
  border-color: rgba(var(--primary-rgb), 0.22);
  box-shadow: 0 0 0 1px rgba(var(--primary-rgb), 0.1), var(--shadow-md);
}

.question-lock-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid rgba(var(--primary-rgb), 0.16);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.04));
  color: var(--text-secondary);
  line-height: 1.7;
}

.cards-result {

  max-width: 900px;
  margin: 0 auto;
}

.cards-result h3 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--text-primary);
}

.cards-display {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.result-context-summary {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin: 0 0 22px;
}

.result-context-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--text-secondary);
  line-height: 1.5;
}

.result-context-chip strong {
  color: var(--text-primary);
  font-size: 13px;
}

.result-context-chip--question {
  max-width: min(100%, 520px);
}

.card-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.card-detail-trigger {
  appearance: none;
  border: none;
  background: none;
  padding: 0;
  margin: 0;
  color: inherit;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  border-radius: 20px;
}

.card-detail-trigger:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 6px;
}


.tarot-card {
  width: 150px;
  height: 250px;
  perspective: 1000px;
}


.card-inner {
  width: 100%;
  height: 100%;
  background: var(--bg-tertiary);
  border-radius: 15px;
  border: 2px solid rgba(184, 134, 11, 0.3);
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
  color: var(--primary-color);
  font-weight: bold;
  margin-bottom: 20px;
}

.card-name {
  font-size: 18px;
  color: var(--text-primary);
  font-weight: 500;
}

.card-position {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.12);
  border: 1px solid rgba(var(--primary-rgb), 0.22);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
  text-align: center;
}


.interpretation {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 30px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-md);
}

.interpretation-content {
  color: var(--text-secondary);
  line-height: 1.8;
  white-space: pre-line;
}

.interpretation-placeholder {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.8;
}

.interpretation-placeholder--loading {
  color: var(--primary-color);
}

.interpretation-placeholder--error {
  color: var(--danger-color);
}

/* 塔罗牌样式 */

.card-inner-alt {
  width: 100%;
  height: 100%;
  background: var(--bg-tertiary);
  border-radius: var(--radius-md);
  border: 2px solid rgba(184, 134, 11, 0.25);
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
  box-shadow: var(--shadow-lg);
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
  font-size: 13px;
  padding: 8px 16px;
  border-radius: var(--radius-xl);
  background: var(--bg-tertiary);
  margin-top: 8px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}

.card-element.火 { background: rgba(var(--wuxing-huo-rgb), 0.12); color: var(--wuxing-huo); }
.card-element.水 { background: rgba(var(--wuxing-shui-rgb), 0.12); color: var(--wuxing-shui); }
.card-element.木 { background: rgba(var(--wuxing-mu-rgb), 0.12); color: var(--wuxing-mu); }
.card-element.金 { background: rgba(var(--wuxing-jin-rgb), 0.12); color: var(--wuxing-jin); }
.card-element.土 { background: rgba(var(--wuxing-tu-rgb), 0.12); color: var(--wuxing-tu); }
.card-element.风 { background: rgba(var(--primary-light-rgb), 0.16); color: var(--primary-color); }

.reversed-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(184, 134, 11, 0.8);
  color: var(--text-primary);
  font-size: 10px;
  padding: 3px 8px;
  border-radius: var(--radius-sm);
}

/* 操作按钮 */
.result-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
  flex-wrap: wrap;
}

.result-actions .el-button {
  min-height: 44px;
  padding: 12px 24px;
}

.result-actions .btn-icon {
  margin-right: 5px;
}

.cards-hint {
  text-align: center;
  color: var(--text-tertiary);
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
  color: var(--text-primary);
  font-size: 20px;
}

.card-detail {
  text-align: center;
}

.detail-header-new {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.detail-context {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-bottom: 18px;
}

.card-position--detail {
  min-height: 30px;
  font-size: 12px;
  padding: 6px 12px;
}

.detail-card {
  transform: scale(1.1);
}


.detail-card:hover {
  transform: scale(1.1) translateY(-5px);
}

.detail-content {
  text-align: left;
}

.detail-content h4 {
  color: var(--primary-color);
  font-size: 16px;
  margin-bottom: 10px;
}

.detail-meaning {
  color: var(--text-primary);
  line-height: 1.8;
  margin-bottom: 20px;
}

.detail-interpretation {
  background: var(--bg-card);
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
  color: var(--primary-light);
  font-size: 14px;
  margin-bottom: 8px;
}

.interp-section p {
  color: var(--text-secondary);
  line-height: 1.7;
}

@media (max-width: 768px) {
  .points-hint {
    flex-direction: column;
    align-items: flex-start;
  }

  .current-points {
    margin-left: 0;
  }

  .points-warning {
    width: 100%;
    justify-content: space-between;
  }

  .points-retry {
    min-height: 44px;
  }


  .spread-options {

    grid-template-columns: 1fr;
  }

  .question-guide {
    padding: 20px 16px;
  }

  .topic-tabs {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .topic-tab {
    gap: 8px;
    padding: 12px 10px;
  }

  .template-list {
    grid-template-columns: 1fr;
  }

  .template-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 14px;
  }

  .template-text {
    flex: 1;
  }

  .flow-error {
    padding: 8px;
  }

  .flow-error-actions {
    justify-content: stretch;
  }

  .flow-error-actions :deep(.el-button) {
    flex: 1;
  }
  
  .cards-display {
    gap: 15px;
  }

  .detail-context {
    justify-content: flex-start;
  }

  .card-position--detail {
    min-height: 28px;
    font-size: 11px;
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


@media (max-width: 480px) {
  .topic-tabs {
    grid-template-columns: 1fr 1fr;
  }

  .card-detail-dialog :deep(.el-dialog) {
    width: calc(100vw - 24px) !important;
    margin: 4vh auto;
  }
}

</style>
