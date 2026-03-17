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

        <div class="share-actions">
          <router-link to="/tarot">
            <el-button type="primary">我也要抽牌</el-button>
          </router-link>
          <router-link to="/">
            <el-button>返回首页</el-button>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { WarningFilled } from '@element-plus/icons-vue'
import { getTarotShare } from '../api'
import BackButton from '../components/BackButton.vue'
import TarotCard from '../components/TarotCard.vue'

const route = useRoute()
const loading = ref(true)
const errorMessage = ref('')
const shareRecord = ref(null)

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

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 12px;
  }

  .cards-display {
    gap: 16px;
  }
}
</style>
