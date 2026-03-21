<script setup lang="ts">
import { ref } from 'vue'
import FeatureCard from './FeatureCard.vue'
import YearlyBanner from './YearlyBanner.vue'

const emit = defineEmits<{
  reserve: []
  yearlyClick: []
}>()

type CostType = 'free' | 'normal' | 'none'
type AccessType = 'normal' | 'free' | 'none'
type Size = 'primary' | 'secondary'

interface Feature {
  type: string
  symbol: string
  title: string
  description: string
  cost: string
  costType: CostType
  access: string
  accessType: AccessType
  link: string
  size: Size
  coming?: boolean
}

const baziOfferState = ref<'guest' | 'loading' | 'free' | 'priced'>('loading')

const features = ref<Feature[]>([
  {
    type: 'bazi',
    symbol: '命',
    title: '八字命理',
    description: '搞清楚自己的性格优势、适合的方向，以及当前处于人生的哪个阶段',
    cost: '首测免费',
    costType: 'free',
    access: '后续每次 100 积分',
    accessType: 'normal',
    link: '/bazi',
    size: 'primary'
  },
  {
    type: 'daily',
    symbol: '运',
    title: '每日运势',
    description: '每天看看今天适合做什么、该注意什么，把握机会规避风险',
    cost: '免费',
    costType: 'free',
    access: '每日可看',
    accessType: 'free',
    link: '/daily',
    size: 'primary'
  },
  {
    type: 'tarot',
    symbol: '占',
    title: '塔罗占卜',
    description: '面对具体选择时，帮你理清思路，看到被忽略的细节和可能性',
    cost: '每次 50 积分',
    costType: 'normal',
    access: '无限制',
    accessType: 'normal',
    link: '/tarot',
    size: 'primary'
  },
  {
    type: 'career',
    symbol: '业',
    title: '事业测算',
    description: '分析事业发展路径，给出职业方向建议和关键时刻的决策参考',
    cost: '每次 150 积分',
    costType: 'normal',
    access: 'VIP 用户专享',
    accessType: 'free',
    link: '/career',
    size: 'primary',
    coming: true
  }
])

const yearlyBanner = ref({
  badge: '限时优惠',
  title: '2025 年度运势',
  description: '看清楚全年的关键节点：哪些月份适合突破，哪些需要稳扎稳打，提前规划',
})
</script>

<template>
  <section class="features-section">
    <div class="features-container">
      <div class="section-header">
        <h2 class="section-title">我们的服务</h2>
        <p class="section-subtitle">不是神秘学，而是帮你理解自己、看清问题的工具</p>
      </div>

      <YearlyBanner
        :badge="yearlyBanner.badge"
        :title="yearlyBanner.title"
        :description="yearlyBanner.description"
        @click="emit('yearlyClick')"
      />

      <div class="features-grid">
        <FeatureCard
          v-for="feature in features"
          :key="feature.type"
          v-bind="feature"
          @reserve="emit('reserve', $event)"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.features-section {
  padding: 60px 24px;
  background: linear-gradient(180deg, #ffffff 0%, #fffbf5 100%);
}

.features-container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 48px;
}

.section-title {
  font-size: clamp(28px, 4vw, 36px);
  font-weight: var(--weight-bold);
  color: #5e4318;
  margin-bottom: 12px;
  letter-spacing: 0.04em;
}

.section-subtitle {
  font-size: clamp(14px, 2vw, 16px);
  color: #6b6254;
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}

@media (max-width: 768px) {
  .features-section {
    padding: 40px 16px;
  }
  
  .section-header {
    margin-bottom: 32px;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
