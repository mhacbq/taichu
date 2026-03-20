<template>
  <div class="tarot-share-page">
    <div class="container">
      <div class="page-header">
        <BackButton text="返回塔罗" fallback="/tarot" />
        <div>
          <h1 class="section-title">塔罗分享结果</h1>
          <p class="page-subtitle">公开查看这一次塔罗占卜的结果与解读</p>
        </div>
      </div>

      <div v-if="loading" class="loading-state card card-hover">
        <el-skeleton :rows="8" animated />
      </div>

      <div v-else-if="errorMessage" class="error-state card card-hover">
        <el-icon class="state-icon"><WarningFilled /></el-icon>
        <h3>分享内容暂时不可用</h3>
        <p>{{ errorMessage }}</p>
        <div class="state-actions">
          <el-button type="primary" @click="loadShareRecord">重新加载</el-button>
          <router-link to="/tarot">
            <el-button>去塔罗占卜</el-button>
          </router-link>
        </div>
      </div>

      <div v-else-if="shareRecord" class="share-result card card-hover">
        <div class="share-meta">
          <el-tag type="warning" effect="dark">{{ shareRecord.spread_name }}</el-tag>
          <span>浏览 {{ shareRecord.view_count || 0 }} 次</span>
          <span>{{ formatShareTime(shareRecord.created_at) }}</span>
        </div>

        <div class="question-box">
          <span class="question-label">占问</span>
          <p class="question-text">{{ shareRecord.question || '未填写问题' }}</p>
        </div>

        <div class="cards-section">
          <h3>牌阵展示</h3>
          <div class="cards-display">
            <div
              v-for="(card, index) in shareRecord.cards"
              :key="`${card.name}-${index}`"
              class="card-stack"
            >
              <TarotCard
                :name="card.name"
                :emoji="card.emoji"
                :reversed="card.reversed"
                :element="card.element"
                :color="card.color"
                :index="index"
              />
              <span v-if="shareRecord.cards.length > 1" class="card-position">
                {{ getPositionLabel(shareRecord.spread_type, index) }}
              </span>
            </div>
          </div>
        </div>

        <div class="interpretation-section">
          <h3>塔罗解读</h3>
          <div class="interpretation-content">{{ shareRecord.interpretation }}</div>
        </div>

        <div class="hot-questions-section">
          <h3>大家都在问</h3>
          <div class="hot-questions-list">
            <router-link to="/tarot" class="hot-question-item card-hover" v-for="(q, index) in hotQuestions" :key="index">
              <span class="hot-question-rank" :class="`rank-${index + 1}`">{{ index + 1 }}</span>
              <span class="hot-question-text">{{ q }}</span>
              <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>

        <div class="share-actions-fixed">
          <div class="share-actions-content">
            <p class="share-actions-hint">想知道你的专属答案吗？</p>
            <router-link to="/tarot" class="share-actions-btn">
              我也来占一卦 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { WarningFilled, ArrowRight } from '@element-plus/icons-vue'
import { getTarotShare } from '../api'
import BackButton from '../components/BackButton.vue'
import TarotCard from '../components/TarotCard.vue'

const route = useRoute()
const loading = ref(true)
const errorMessage = ref('')
const shareRecord = ref(null)

const hotQuestions = [
  '我最近的财运走向如何？',
  '这段感情值得我继续投入吗？',
  '我应该接受这份新工作offer吗？',
  '如何突破目前的职业瓶颈？',
  '我什么时候能遇到正缘？'
]

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

const formatShareTime = (value) => {
  if (!value) return '时间未知'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return date.toLocaleString('zh-CN', { hour12: false })
}

const loadShareRecord = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const code = route.params.code
    const response = await getTarotShare({ code })

    if (response.code === 200) {
      shareRecord.value = response.data
      updateOGMeta(response.data)
      return
    }

    errorMessage.value = response.message || '分享内容加载失败'
  } catch (error) {
    console.error('[TarotShare] 加载分享记录失败', error)
    errorMessage.value = error?.response?.data?.message || '分享内容加载失败，请稍后重试'
    ElMessage.error(errorMessage.value)
  } finally {
    loading.value = false
  }
}

const updateOGMeta = (data) => {
  if (!data) return
  
  const title = `塔罗占卜分享 - ${data.spread_name}`
  const description = data.question ? `占问：${data.question}` : '查看我的塔罗占卜结果'
  
  document.title = `${title} | 太初命理`
  
  const metaTags = {
    'og:title': title,
    'og:description': description,
    'og:type': 'article',
    'og:site_name': '太初命理',
    'twitter:card': 'summary',
    'twitter:title': title,
    'twitter:description': description
  }
  
  Object.entries(metaTags).forEach(([name, content]) => {
    let tag = document.querySelector(`meta[property="${name}"]`) || document.querySelector(`meta[name="${name}"]`)
    if (!tag) {
      tag = document.createElement('meta')
      if (name.startsWith('og:')) {
        tag.setAttribute('property', name)
      } else {
        tag.setAttribute('name', name)
      }
      document.head.appendChild(tag)
    }
    tag.setAttribute('content', content)
  })
}

onMounted(() => {
  loadShareRecord()
})
</script>

<style scoped>
.tarot-share-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 30px;
}

.section-title {
  margin: 0 0 8px;
}

.page-subtitle {
  margin: 0;
  color: var(--text-secondary);
}

.loading-state,
.error-state,
.share-result {
  max-width: 960px;
  margin: 0 auto;
}

.error-state {
  text-align: center;
}

.state-icon {
  font-size: 40px;
  color: var(--danger-color);
  margin-bottom: 16px;
}

.state-actions,
.share-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 24px;
}

.share-meta {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 24px;
  color: var(--text-secondary);
  font-size: 14px;
}

.question-box {
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
}

.question-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 4px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.12);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 12px;
}

.question-text {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
  line-height: 1.7;
}

.cards-section h3,
.interpretation-section h3 {
  text-align: center;
  margin-bottom: 20px;
  color: var(--text-primary);
}

.cards-display {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
  margin-bottom: 32px;
}

.card-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  width: min(180px, 100%);
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

.interpretation-section {
  background: var(--bg-secondary);
  border-radius: 16px;
  padding: 24px;
  border: 1px solid var(--border-light);
}

.interpretation-content {
  color: var(--text-secondary);
  line-height: 1.9;
  white-space: pre-line;
}

.hot-questions-section {
  margin-top: 40px;
  padding-bottom: 80px; /* 为底部固定按钮留出空间 */
}

.hot-questions-section h3 {
  text-align: center;
  margin-bottom: 20px;
  color: var(--text-primary);
}

.hot-questions-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.hot-question-item {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  text-decoration: none;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.hot-question-item:hover {
  background: var(--surface-hover);
  border-color: var(--primary-color);
  transform: translateX(4px);
}

.hot-question-rank {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: bold;
  margin-right: 12px;
}

.hot-question-rank.rank-1 { background: #ff4d4f; color: white; }
.hot-question-rank.rank-2 { background: #ff7a45; color: white; }
.hot-question-rank.rank-3 { background: #ffa940; color: white; }

.hot-question-text {
  flex: 1;
  font-size: 15px;
}

.share-actions-fixed {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-top: 1px solid var(--border-light);
  padding: 16px 20px;
  z-index: 100;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.05);
}

.share-actions-content {
  max-width: 600px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.share-actions-hint {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.share-actions-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  color: white;
  border-radius: 24px;
  text-decoration: none;
  font-weight: bold;
  font-size: 15px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
}

.share-actions-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.3);
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 12px;
  }

  .cards-display {
    gap: 16px;
  }
  
  .share-actions-content {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .share-actions-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
