<template>
  <div class="vip-page">
    <div class="container">
      <PageHeroHeader
        title="VIP 会员"
        subtitle="开通太初命理 VIP 会员，享受更多专属权益与深度解读服务"
        :icon="Trophy"
      />

      <!-- 加载骨架屏 -->
      <div v-if="loading" class="vip-skeleton">
        <div class="skeleton-status"></div>
        <div class="skeleton-plans">
          <div class="skeleton-plan" v-for="i in 3" :key="i"></div>
        </div>
        <div class="skeleton-privileges"></div>
      </div>

      <div v-else class="vip-content">
        <!-- VIP 状态卡片 -->
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

        <!-- 套餐列表 -->
        <div class="vip-plans">
          <div
            class="plan-card card card-hover"
            v-for="plan in vipPlans"
            :key="plan.id"
            :class="{ 'is-recommended': plan.recommended }"
          >
            <div v-if="plan.recommended" class="plan-badge">最划算</div>
            <h3 class="plan-name">{{ plan.name }}</h3>
            <div class="plan-price">
              <span class="currency">¥</span>
              <span class="amount">{{ plan.price }}</span>
              <span class="duration">/ {{ plan.duration }}</span>
            </div>
            <div class="plan-points">
              <el-icon><Coin /></el-icon>
              <span>消耗 {{ plan.price }} 积分</span>
            </div>
            <ul class="plan-features">
              <li v-for="(feature, index) in plan.features" :key="index">
                <el-icon class="feature-icon"><Check /></el-icon>
                {{ feature }}
              </li>
            </ul>
            <el-button
              type="primary"
              class="plan-btn"
              :loading="purchasing"
              @click="handleSubscribe(plan)"
            >
              {{ isVip ? '立即续费' : '立即开通' }}
            </el-button>
          </div>
        </div>

        <!-- VIP 专属特权 -->
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
import { Trophy, UserFilled, Check, Coin } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import { useVip } from './useVip'

const {
  userInfo,
  isVip,
  vipExpireTime,
  vipPlans,
  privileges,
  loading,
  purchasing,
  handleSubscribe,
} = useVip()
</script>

<style scoped>
@import './style.css';
</style>
