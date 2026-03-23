<template>
  <div class="recharge-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">积分充值</h1>
      </div>

      <!-- 步骤指示器 -->
      <div class="steps-indicator">
        <div class="step-item" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
          <div class="step-number">1</div>
          <div class="step-label">选择金额</div>
        </div>
        <div class="step-line"></div>
        <div class="step-item" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
          <div class="step-number">2</div>
          <div class="step-label">确认支付</div>
        </div>
        <div class="step-line"></div>
        <div class="step-item" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
          <div class="step-number">3</div>
          <div class="step-label">完成</div>
        </div>
      </div>

      <!-- 当前积分 -->
      <div class="current-points card">
        <div class="points-display">
          <span class="points-label">当前积分</span>
          <span class="points-value">{{ pointsBalance }}</span>
        </div>
        <p class="points-tip">充值后可解锁更多AI解盘次数</p>
      </div>

      <!-- 充值选项 -->
      <div class="recharge-options card">
        <div class="section-header">
          <h3>选择充值金额</h3>
          <span class="step-badge">步骤 1</span>
        </div>
        <div class="options-grid">
          <div 
            v-for="option in rechargeOptions" 
            :key="option.amount"
            class="option-item"
            :class="{
              active: selectedAmount === option.amount,
              hot: option.bonus > 0,
              vip: option.type === 'vip'
            }"
            @click="selectAmount(option.amount)"
          >
            <div v-if="option.type === 'vip'" class="vip-crown-icon">👑</div>
            <div class="option-content">
              <div class="amount">¥{{ option.amount }}</div>
              <div class="points" :class="{ 'vip-points': option.type === 'vip' }">
                {{ option.type === 'vip' ? option.label : option.points + '积分' }}
              </div>
              <div v-if="option.desc" class="desc">{{ option.desc }}</div>
              <div v-if="option.type !== 'vip'" class="unlock-hint">
                可解锁 {{ Math.floor((option.points + (option.bonus || 0)) / 5) }}+ 次占卜
              </div>
              <div v-if="option.bonus > 0" class="bonus">+{{ option.bonus }}赠送</div>
              <div v-if="option.bonus > 0" class="hot-tag">HOT</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 支付信息 -->
      <div class="payment-info card" v-if="selectedAmount">
        <div class="section-header">
          <h3>确认支付</h3>
          <span class="step-badge">步骤 2</span>
        </div>
        
        <!-- 支付方式选择 -->
        <div class="payment-methods">
          <label class="payment-label">选择支付方式</label>
          <div class="payment-options">
            <div 
              class="payment-option"
              :class="{ active: paymentMethod === 'wechat' }"
              @click="paymentMethod = 'wechat'"
            >
              <el-icon size="24" color="#07c160"><ChatDotRound /></el-icon>
              <span>微信支付</span>
            </div>
            <div 
              class="payment-option"
              :class="{ active: paymentMethod === 'alipay' }"
              @click="paymentMethod = 'alipay'"
            >
              <el-icon size="24" color="#1677ff"><Wallet /></el-icon>
              <span>支付宝</span>
            </div>
          </div>
        </div>
        
        <div class="info-row">
          <span>充值金额</span>
          <span class="highlight">¥{{ selectedAmount }}</span>
        </div>
        <div class="info-row">
          <span>获得积分</span>
          <span class="highlight">{{ selectedPoints }}积分</span>
        </div>
        <div class="info-row total">
          <span>实付金额</span>
          <span class="total-amount">¥{{ selectedAmount }}</span>
        </div>
        
        <el-button 
          type="primary" 
          size="large" 
          class="pay-btn"
          :loading="creatingOrder"
          :disabled="creatingOrder || !selectedAmount"
          @click="handleRecharge"
        >
          <span v-if="!creatingOrder" class="btn-content">
            <span class="btn-text">立即支付</span>
            <el-icon class="btn-icon"><ArrowRight /></el-icon>
          </span>
          <span v-else>处理中...</span>
        </el-button>
        
        <p class="pay-tip">
          <el-icon><InfoFilled /></el-icon>
          支付完成后积分将自动到账
        </p>
      </div>

      <!-- 充值记录 -->
      <div class="recharge-history card">
        <div class="section-header">
          <h3>充值记录</h3>
          <el-icon class="header-icon"><Clock /></el-icon>
        </div>
        <div class="history-list" v-if="rechargeHistory.length > 0">
          <div v-for="record in rechargeHistory" :key="record.id" class="history-item">
            <div class="history-info">
              <span class="history-amount">¥{{ record.amount }}</span>
              <span class="history-points">+{{ record.points }}积分</span>
              <span class="history-time">{{ formatDate(record.created_at) }}</span>
            </div>
            <span class="history-status" :class="record.status">
              {{ getStatusText(record.status) }}
            </span>
          </div>
        </div>
        <el-empty v-else description="暂无充值记录" />
      </div>
    </div>

    <!-- 支付二维码弹窗 -->
    <el-dialog
      v-model="payDialogVisible"
      title="微信支付"
      width="360px"
      :close-on-click-modal="false"
      @close="handlePayDialogClose"
    >
      <div class="pay-dialog-content">
        <div class="pay-amount">¥{{ selectedAmount }}</div>
        <div class="pay-qrcode" v-if="!isWechatBrowser">
          <!-- 真实二维码图片 -->
          <div v-if="qrCodeUrl" class="qrcode-image">
            <img :src="qrCodeUrl" alt="支付二维码" />
          </div>
          <!-- 二维码生成中 -->
          <div v-else-if="generatingQR" class="qrcode-placeholder">
            <el-icon :size="60" class="is-loading"><Loading /></el-icon>
            <p>正在生成支付二维码...</p>
          </div>
          <!-- 生成失败 -->
          <div v-else class="qrcode-placeholder">
            <el-icon :size="60"><Picture /></el-icon>
            <p>二维码生成失败</p>
            <el-button type="primary" size="small" @click="generateQRCode">重新生成</el-button>
          </div>
        </div>
        <div class="pay-wechat" v-else>
          <el-icon :size="60" color="#07c160"><ChatDotRound /></el-icon>
          <p>正在唤起微信支付...</p>
        </div>
        <p class="pay-tip">支付完成后请点击下方按钮</p>
        <div class="pay-actions">
          <el-button type="primary" @click="checkPayStatus" :loading="checkingStatus">
            已完成支付
          </el-button>
          <el-button @click="cancelPay">取消支付</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { InfoFilled, Picture, ChatDotRound, Loading, Wallet, ArrowRight, Clock } from '@element-plus/icons-vue'
import BackButton from '../../components/BackButton.vue'

import { useRecharge } from './useRecharge'

const {
  // 状态
  pointsBalance, rechargeOptions, selectedAmount,
  creatingOrder, payDialogVisible, checkingStatus,
  currentOrderNo, rechargeHistory,
  isWechatBrowser, qrCodeUrl, generatingQR,
  paymentMethod, currentStep,

  // 计算属性
  selectedPoints,

  // 方法
  selectAmount, handleRecharge, generateQRCode,
  checkPayStatus, cancelPay, handlePayDialogClose,
  getStatusText, formatDate,
} = useRecharge()
</script>

<style scoped>
@import './style.css';
</style>
