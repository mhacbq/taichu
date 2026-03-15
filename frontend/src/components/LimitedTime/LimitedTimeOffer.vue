<template>
  <div v-if="show && offer" class="limited-offer" :class="{ 'is-ending': isEnding }">
    <!-- 顶部横幅 -->
    <div class="offer-banner" @click="openDetail">
      <div class="banner-content">
        <div class="offer-badge">
          <el-icon><Timer /></el-icon>
          <span>限时特惠</span>
        </div>
        <div class="offer-title">{{ offer.title }}</div>
        <div class="offer-desc">{{ offer.description }}</div>
      </div>
      <div class="countdown-wrapper">
        <div class="countdown-label">距结束仅剩</div>
        <div class="countdown-timer">
          <div class="time-block">
            <span class="time-num">{{ countdown.hours }}</span>
            <span class="time-unit">时</span>
          </div>
          <span class="time-separator">:</span>
          <div class="time-block">
            <span class="time-num">{{ countdown.minutes }}</span>
            <span class="time-unit">分</span>
          </div>
          <span class="time-separator">:</span>
          <div class="time-block">
            <span class="time-num">{{ countdown.seconds }}</span>
            <span class="time-unit">秒</span>
          </div>
        </div>
      </div>
      <div class="banner-arrow">
        <el-icon><ArrowRight /></el-icon>
      </div>
    </div>
    
    <!-- 详情弹窗 -->
    <el-dialog
      v-model="detailVisible"
      :title="offer.title"
      width="500px"
      center
      class="offer-dialog"
    >
      <div class="offer-detail">
        <!-- 倒计时 -->
        <div class="detail-countdown">
          <div class="countdown-title">⏰ 限时优惠倒计时</div>
          <div class="countdown-display">
            <div class="count-item">
              <div class="count-num">{{ countdown.hours }}</div>
              <div class="count-label">小时</div>
            </div>
            <div class="count-separator">:</div>
            <div class="count-item">
              <div class="count-num">{{ countdown.minutes }}</div>
              <div class="count-label">分钟</div>
            </div>
            <div class="count-separator">:</div>
            <div class="count-item">
              <div class="count-num">{{ countdown.seconds }}</div>
              <div class="count-label">秒钟</div>
            </div>
          </div>
        </div>
        
        <!-- 优惠信息 -->
        <div class="offer-info">
          <div class="price-section">
            <div class="original-price">
              <span class="price-label">原价</span>
              <span class="price-value">¥{{ offer.originalPrice }}</span>
            </div>
            <div class="current-price">
              <span class="price-label">限时价</span>
              <span class="price-value">¥{{ offer.currentPrice }}</span>
            </div>
            <div class="discount-badge">
              {{ discountPercent }}折
            </div>
          </div>
          
          <!-- 进度条 -->
          <div class="progress-section">
            <div class="progress-header">
              <span>已抢 {{ soldPercent }}%</span>
              <span>仅剩 {{ remainingCount }} 份</span>
            </div>
            <div class="progress-bar">
              <div 
                class="progress-fill"
                :style="{ width: `${soldPercent}%` }"
              ></div>
            </div>
          </div>
          
          <!-- 权益列表 -->
          <div class="benefits-section">
            <h4>包含权益</h4>
            <div class="benefits-list">
              <div 
                v-for="(benefit, index) in offer.benefits" 
                :key="index"
                class="benefit-item"
              >
                <el-icon color="#10b981"><CircleCheck /></el-icon>
                <span>{{ benefit }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="offer-actions">
          <el-button
            type="primary"
            size="large"
            class="buy-btn"
            :loading="buying"
            @click="handleBuy"
          >
            <div class="btn-content">
              <span class="btn-price">¥{{ offer.currentPrice }}</span>
              <span class="btn-text">立即抢购</span>
            </div>
          </el-button>
          <p class="tips">⚡ 已有 {{ offer.soldCount }} 人购买</p>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Timer, ArrowRight, CircleCheck } from '@element-plus/icons-vue'
import { useAnalytics } from '@/utils/analytics'

const props = defineProps({
  offer: {
    type: Object,
    default: () => ({
      id: 'vip_annual_sale',
      title: '年度VIP限时特惠',
      description: '解锁全部高级功能，开启命理之旅',
      originalPrice: 298,
      currentPrice: 168,
      totalCount: 100,
      soldCount: 67,
      endTime: Date.now() + 24 * 60 * 60 * 1000, // 24小时后
      benefits: [
        '全年无限次八字排盘',
        'VIP专属运势解读',
        '每月塔罗占卜3次',
        '优先客服支持',
        '专属运势提醒',
        '去除广告展示'
      ]
    })
  }
})

const emit = defineEmits(['buy'])
const analytics = useAnalytics()

const show = ref(true)
const detailVisible = ref(false)
const buying = ref(false)
const countdown = ref({ hours: '00', minutes: '00', seconds: '00' })
let countdownTimer = null

// 折扣百分比
const discountPercent = computed(() => {
  return Math.round((props.offer.currentPrice / props.offer.originalPrice) * 10)
})

// 已售百分比
const soldPercent = computed(() => {
  return Math.round((props.offer.soldCount / props.offer.totalCount) * 100)
})

// 剩余数量
const remainingCount = computed(() => {
  return props.offer.totalCount - props.offer.soldCount
})

// 是否即将结束（少于1小时）
const isEnding = computed(() => {
  const end = new Date(props.offer.endTime).getTime()
  const now = Date.now()
  return end - now < 60 * 60 * 1000
})

// 更新倒计时
const updateCountdown = () => {
  const end = new Date(props.offer.endTime).getTime()
  const now = Date.now()
  const diff = end - now

  if (diff <= 0) {
    show.value = false
    clearInterval(countdownTimer)
    return
  }

  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)

  countdown.value = {
    hours: hours.toString().padStart(2, '0'),
    minutes: minutes.toString().padStart(2, '0'),
    seconds: seconds.toString().padStart(2, '0')
  }
}

// 打开详情
const openDetail = () => {
  detailVisible.value = true
  
  analytics.track('limited_offer_click', {
    offerId: props.offer.id,
    title: props.offer.title
  })
}

// 购买
const handleBuy = async () => {
  buying.value = true
  
  analytics.track('limited_offer_buy_click', {
    offerId: props.offer.id,
    price: props.offer.currentPrice
  })
  
  try {
    // 实际项目中调用支付API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    ElMessage.success('已添加到订单，请完成支付')
    detailVisible.value = false
    emit('buy', props.offer)
    
    analytics.trackConversion('limited_offer_purchase', props.offer.currentPrice, {
      offerId: props.offer.id
    })
  } catch (error) {
    ElMessage.error('购买失败，请重试')
  } finally {
    buying.value = false
  }
}

onMounted(() => {
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  clearInterval(countdownTimer)
})
</script>

<style scoped>
.limited-offer {
  margin-bottom: 16px;
}

.offer-banner {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  border-radius: 16px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 20px rgba(238, 90, 111, 0.3);
}

.offer-banner:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(238, 90, 111, 0.4);
}

.limited-offer.is-ending .offer-banner {
  animation: pulse-red 1.5s infinite;
}

@keyframes pulse-red {
  0%, 100% {
    box-shadow: 0 4px 20px rgba(238, 90, 111, 0.3);
  }
  50% {
    box-shadow: 0 4px 30px rgba(238, 90, 111, 0.6);
  }
}

.banner-content {
  flex: 1;
  color: white;
}

.offer-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  margin-bottom: 8px;
}

.offer-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}

.offer-desc {
  font-size: 13px;
  opacity: 0.9;
}

.countdown-wrapper {
  text-align: center;
  color: white;
}

.countdown-label {
  font-size: 11px;
  opacity: 0.8;
  margin-bottom: 4px;
}

.countdown-timer {
  display: flex;
  align-items: center;
  gap: 4px;
}

.time-block {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.time-num {
  background: rgba(0, 0, 0, 0.2);
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 18px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.time-unit {
  font-size: 10px;
  margin-top: 2px;
  opacity: 0.8;
}

.time-separator {
  font-size: 16px;
  font-weight: 600;
}

.banner-arrow {
  color: white;
  opacity: 0.8;
}

/* 详情弹窗 */
.offer-detail {
  padding: 0 10px;
}

.detail-countdown {
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  border-radius: 16px;
  margin-bottom: 24px;
  color: white;
}

.countdown-title {
  font-size: 14px;
  margin-bottom: 12px;
  opacity: 0.9;
}

.countdown-display {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.count-item {
  text-align: center;
}

.count-num {
  background: rgba(0, 0, 0, 0.2);
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 32px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.count-label {
  font-size: 12px;
  margin-top: 6px;
  opacity: 0.8;
}

.count-separator {
  font-size: 28px;
  font-weight: 300;
  opacity: 0.8;
}

.price-section {
  display: flex;
  align-items: baseline;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.original-price {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.original-price .price-label {
  font-size: 12px;
  color: #999;
}

.original-price .price-value {
  font-size: 18px;
  color: #999;
  text-decoration: line-through;
}

.current-price {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.current-price .price-label {
  font-size: 12px;
  color: #ee5a6f;
}

.current-price .price-value {
  font-size: 36px;
  font-weight: 700;
  color: #ee5a6f;
}

.discount-badge {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  color: white;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
}

.progress-section {
  margin-bottom: 24px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #666;
  margin-bottom: 8px;
}

.progress-bar {
  height: 8px;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #ff6b6b 0%, #ee5a6f 100%);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.benefits-section {
  margin-bottom: 24px;
}

.benefits-section h4 {
  font-size: 16px;
  margin-bottom: 12px;
  color: #333;
}

.benefits-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #555;
}

.offer-actions {
  text-align: center;
}

.buy-btn {
  width: 100%;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
  border: none;
}

.buy-btn:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-price {
  font-size: 24px;
  font-weight: 700;
}

.btn-text {
  font-size: 16px;
}

.tips {
  margin-top: 12px;
  font-size: 13px;
  color: #999;
}
</style>
