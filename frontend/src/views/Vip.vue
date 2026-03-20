<template>
  <div class="vip-page">
    <div class="container">
      <PageHeroHeader
        title="VIP 会员"
        subtitle="开通太初命理 VIP 会员，享受更多专属权益与深度解读服务"
        :icon="Trophy"
      />

      <div class="vip-content">
        <div class="vip-status card card-hover">
          <div class="status-header">
            <el-icon class="status-icon"><UserFilled /></el-icon>
            <div class="status-info">
              <h3>{{ userInfo.nickname || '用户' }}</h3>
              <p class="status-text" :class="{ 'is-vip': isVip }">
                {{ isVip ? `VIP 会员 (到期时间: ${vipExpireTime})` : '当前为普通用户' }}
              </p>
            </div>
          </div>
        </div>

        <div class="vip-plans">
          <div class="plan-card card card-hover" v-for="plan in vipPlans" :key="plan.id" :class="{ 'is-recommended': plan.recommended }">
            <div v-if="plan.recommended" class="plan-badge">最划算</div>
            <h3 class="plan-name">{{ plan.name }}</h3>
            <div class="plan-price">
              <span class="currency">¥</span>
              <span class="amount">{{ plan.price }}</span>
              <span class="duration">/ {{ plan.duration }}</span>
            </div>
            <ul class="plan-features">
              <li v-for="(feature, index) in plan.features" :key="index">
                <el-icon class="feature-icon"><Check /></el-icon>
                {{ feature }}
              </li>
            </ul>
            <el-button type="primary" class="plan-btn" @click="handleSubscribe(plan)">
              {{ isVip ? '立即续费' : '立即开通' }}
            </el-button>
          </div>
        </div>

        <div class="vip-privileges card card-hover">
          <h3>VIP 专属特权</h3>
          <div class="privilege-grid">
            <div class="privilege-item" v-for="privilege in privileges" :key="privilege.title">
              <div class="privilege-icon-wrapper">
                <el-icon class="privilege-icon"><component :is="privilege.icon" /></el-icon>
              </div>
              <h4>{{ privilege.title }}</h4>
              <p>{{ privilege.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Trophy, UserFilled, Check, Star, MagicStick, Calendar, Document } from '@element-plus/icons-vue'
import PageHeroHeader from '../components/PageHeroHeader.vue'

const userInfo = ref({})
const isVip = ref(false)
const vipExpireTime = ref('')

const vipPlans = [
  {
    id: 'month',
    name: '连续包月',
    price: '29',
    duration: '月',
    features: ['每月赠送 500 积分', '解锁八字流年大运深度解读', '塔罗占卜专属牌阵', '优先客服响应'],
    recommended: false
  },
  {
    id: 'quarter',
    name: '连续包季',
    price: '68',
    duration: '季',
    features: ['每季赠送 1800 积分', '解锁八字流年大运深度解读', '塔罗占卜专属牌阵', '优先客服响应', '专属身份标识'],
    recommended: true
  },
  {
    id: 'year',
    name: '连续包年',
    price: '198',
    duration: '年',
    features: ['每年赠送 8000 积分', '解锁八字流年大运深度解读', '塔罗占卜专属牌阵', '优先客服响应', '专属身份标识', '新功能优先体验'],
    recommended: false
  }
]

const privileges = [
  {
    icon: Star,
    title: '海量积分赠送',
    desc: '开通即送大量积分，畅享全站核心功能，排盘占卜无压力。'
  },
  {
    icon: MagicStick,
    title: '深度解读解锁',
    desc: '解锁八字流年大运、性格内观等专业版深度解读内容。'
  },
  {
    icon: Calendar,
    title: '专属塔罗牌阵',
    desc: '使用 VIP 专属的高级塔罗牌阵，获得更全面、多维度的指引。'
  },
  {
    icon: Document,
    title: '专属身份标识',
    desc: '全站展示 VIP 专属尊贵标识，彰显独特身份。'
  }
]

const loadUserInfo = () => {
  try {
    const storedUserInfo = localStorage.getItem('userInfo')
    if (storedUserInfo) {
      userInfo.value = JSON.parse(storedUserInfo)
      // 模拟 VIP 状态，实际应从后端获取
      isVip.value = userInfo.value.is_vip || false
      vipExpireTime.value = userInfo.value.vip_expire_time || ''
    }
  } catch (error) {
    console.error('解析用户信息失败:', error)
  }
}

const handleSubscribe = (plan) => {
  ElMessage.info(`正在为您跳转到 ${plan.name} 支付页面，功能开发中...`)
}

onMounted(() => {
  loadUserInfo()
})
</script>

<style scoped>
.vip-page {
  padding: 60px 0;
  background: linear-gradient(180deg, #fffaf1 0%, #fff7ee 100%);
}

.vip-content {
  max-width: 1000px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.vip-status {
  padding: 24px 30px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.95));
  border: 1px solid rgba(227, 184, 104, 0.3);
  border-radius: var(--radius-xl);
}

.status-header {
  display: flex;
  align-items: center;
  gap: 20px;
}

.status-icon {
  font-size: 48px;
  color: var(--primary-color);
  background: rgba(245, 196, 103, 0.2);
  padding: 12px;
  border-radius: 50%;
}

.status-info h3 {
  margin: 0 0 8px;
  font-size: 24px;
  color: var(--text-primary);
}

.status-text {
  margin: 0;
  font-size: 16px;
  color: var(--text-secondary);
}

.status-text.is-vip {
  color: var(--primary-color);
  font-weight: 600;
}

.vip-plans {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.plan-card {
  position: relative;
  padding: 30px 24px;
  text-align: center;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-radius: var(--radius-xl);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.plan-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 32px rgba(145, 103, 34, 0.12);
  border-color: rgba(212, 175, 55, 0.4);
}

.plan-card.is-recommended {
  background: linear-gradient(180deg, rgba(255, 253, 246, 0.98), rgba(255, 248, 231, 0.95));
  border: 2px solid var(--primary-color);
  transform: scale(1.05);
  z-index: 1;
}

.plan-card.is-recommended:hover {
  transform: scale(1.05) translateY(-5px);
}

.plan-badge {
  position: absolute;
  top: -12px;
  right: 20px;
  background: linear-gradient(135deg, #ff6b6b, #f56c6c);
  color: white;
  padding: 4px 12px;
  border-radius: 12px 12px 12px 0;
  font-size: 12px;
  font-weight: bold;
  box-shadow: 0 4px 8px rgba(245, 108, 108, 0.3);
}

.plan-name {
  margin: 0 0 16px;
  font-size: 20px;
  color: var(--text-primary);
}

.plan-price {
  margin-bottom: 24px;
  color: var(--primary-color);
}

.currency {
  font-size: 24px;
  font-weight: bold;
}

.amount {
  font-size: 48px;
  font-weight: 900;
  line-height: 1;
}

.duration {
  font-size: 16px;
  color: var(--text-secondary);
}

.plan-features {
  list-style: none;
  padding: 0;
  margin: 0 0 30px;
  text-align: left;
  flex: 1;
}

.plan-features li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.feature-icon {
  color: var(--success-color);
  font-size: 16px;
  margin-top: 2px;
  flex-shrink: 0;
}

.plan-btn {
  width: 100%;
  height: 44px;
  font-size: 16px;
  border-radius: 22px;
  background: linear-gradient(135deg, #e2af4f 0%, #f3c86f 100%);
  border: none;
  color: #5a3f17;
  font-weight: bold;
  transition: all 0.3s ease;
}

.plan-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(186, 135, 41, 0.2);
}

.vip-privileges {
  padding: 40px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-radius: var(--radius-xl);
}

.vip-privileges h3 {
  text-align: center;
  margin: 0 0 30px;
  font-size: 24px;
  color: var(--text-primary);
}

.privilege-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 30px;
}

.privilege-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px;
  background: rgba(245, 196, 103, 0.05);
  border-radius: 16px;
  border: 1px solid rgba(227, 184, 104, 0.15);
  transition: all 0.3s ease;
}

.privilege-item:hover {
  background: rgba(245, 196, 103, 0.1);
  transform: translateY(-3px);
}

.privilege-icon-wrapper {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, rgba(226, 175, 79, 0.2), rgba(243, 200, 111, 0.2));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.privilege-icon {
  font-size: 32px;
  color: var(--primary-color);
}

.privilege-item h4 {
  margin: 0 0 10px;
  font-size: 18px;
  color: var(--text-primary);
}

.privilege-item p {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
}

@media (max-width: 992px) {
  .vip-plans {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .plan-card.is-recommended {
    transform: none;
  }

  .plan-card.is-recommended:hover {
    transform: translateY(-5px);
  }
}

@media (max-width: 768px) {
  .vip-page {
    padding: 30px 0 60px;
  }

  .status-header {
    flex-direction: column;
    text-align: center;
  }

  .privilege-grid {
    grid-template-columns: 1fr;
  }

  .vip-privileges {
    padding: 24px;
  }
}
</style>