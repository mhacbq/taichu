<template>
  <div class="recharge-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">积分充值</h1>
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
        <h3>选择充值金额</h3>
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

      <!-- 支付信息 -->
      <div class="payment-info card" v-if="selectedAmount">
        <h3>确认支付</h3>
        
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
        <h3>充值记录</h3>
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
import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { InfoFilled, Picture, ChatDotRound, Loading, Wallet, ArrowRight } from '@element-plus/icons-vue'
import { getPointsBalance } from '../api'
import { getRechargeOptions, createRechargeOrder, queryRechargeOrder, getRechargeHistory } from '../api/payment'
import { createAlipayOrder } from '../api/alipay'
import BackButton from '../components/BackButton.vue'
import { formatDate } from '../utils/format'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../utils/tracker'

const pointsBalance = ref(0)
const rechargeOptions = ref([])
const selectedAmount = ref(null)
const creatingOrder = ref(false)
const payDialogVisible = ref(false)
const checkingStatus = ref(false)
const currentOrderNo = ref('')
const rechargeHistory = ref([])
const isWechatBrowser = ref(false)
const qrCodeUrl = ref('')
const generatingQR = ref(false)
const paymentMethod = ref('wechat') // 'wechat' | 'alipay'

// 计算选中的积分数量
const selectedPoints = computed(() => {
  const option = rechargeOptions.value.find(opt => opt.amount === selectedAmount.value)
  return option ? option.points : 0
})

const truncateRechargeMessage = (message) => {
  if (!message) {
    return 'unknown'
  }

  return message.length > 160 ? `${message.slice(0, 157)}...` : message
}

const maskOrderNo = (orderNo) => {
  const normalizedOrderNo = String(orderNo ?? '').trim()
  if (normalizedOrderNo.length <= 8) {
    return normalizedOrderNo
  }

  return `${normalizedOrderNo.slice(0, 4)}***${normalizedOrderNo.slice(-4)}`
}

const reportRechargeError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[Recharge]', {
    action,
    error_type: error?.name || typeof error,
    message: truncateRechargeMessage(typeof error?.message === 'string' ? error.message : String(error ?? '')),
    ...extra
  })
}

const buildWechatPayUrl = (orderNo) => `weixin://wxpay/bizpayurl?pr=${orderNo}`

const buildFallbackQrCodeUrl = (orderNo) => {
  const payUrl = encodeURIComponent(buildWechatPayUrl(orderNo))
  return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${payUrl}`
}

const applyFallbackQrCode = () => {
  qrCodeUrl.value = buildFallbackQrCodeUrl(currentOrderNo.value)
}

const showCreateOrderError = (message) => {
  ElMessage.error(message || '创建订单失败')
}

const getWechatBridge = () => window.WeixinJSBridge

// 检查是否在微信浏览器中
const checkWechatBrowser = () => {
  const ua = navigator.userAgent.toLowerCase()
  return ua.includes('micromessenger')
}

onMounted(() => {
  trackPageView('recharge')
  isWechatBrowser.value = checkWechatBrowser()
  loadPointsBalance()
  loadRechargeOptions()
  loadRechargeHistory()
})

// 加载积分余额
const loadPointsBalance = async () => {
  try {
    const res = await getPointsBalance()
    if (res.code === 200) {
      pointsBalance.value = res.data.balance
    }
  } catch (error) {
    reportRechargeError('load_points_balance_failed', error)
  }
}

// 加载充值选项
const loadRechargeOptions = async () => {
  try {
    const res = await getRechargeOptions()
    if (res.code === 200) {
      // 过滤掉旧的100元选项，添加新的68元会员选项
      let options = res.data.options.filter(opt => opt.amount !== 100)

      const membershipOption = {
        amount: 68,
        points: 500,
        bonus: 0,
        type: 'vip',
        label: '年度会员',
        desc: '含500积分 + 解锁深度报告'
      }

      // 将会员选项放在第一位
      options.unshift(membershipOption)

      rechargeOptions.value = options

      // 默认选中会员选项
      selectedAmount.value = 68
    }
  } catch (error) {
    reportRechargeError('load_recharge_options_failed', error)
  }
}

// 加载充值记录
const loadRechargeHistory = async () => {
  try {
    const res = await getRechargeHistory()
    if (res.code === 200) {
      rechargeHistory.value = res.data || []
    }
  } catch (error) {
    reportRechargeError('load_recharge_history_failed', error)
  }
}

// 选择金额
const selectAmount = (amount) => {
  selectedAmount.value = amount
}

// 处理充值
const handleRecharge = async () => {
  if (!selectedAmount.value) {
    ElMessage.warning('请选择充值金额')
    return
  }

  trackEvent('recharge_click', { amount: selectedAmount.value, method: paymentMethod.value })
  creatingOrder.value = true
  try {
    if (paymentMethod.value === 'wechat') {
      const res = await createRechargeOrder({
        amount: selectedAmount.value
      })

      if (res.code !== 200) {
        trackSubmit('recharge_order', false, { amount: selectedAmount.value, method: 'wechat', error: res.message })
        showCreateOrderError(res.message)
        return
      }

      trackSubmit('recharge_order', true, { amount: selectedAmount.value, method: 'wechat', order_no: res.data.order_no })
      currentOrderNo.value = res.data.order_no

      if (isWechatBrowser.value && res.data.pay_params) {
        callWechatPay(res.data.pay_params)
        return
      }

      payDialogVisible.value = true
      generateQRCode()
      startQueryTimer()
      return
    }

    if (paymentMethod.value === 'alipay') {
      const res = await createAlipayOrder({
        amount: selectedAmount.value
      })

      if (res.code !== 200) {
        trackSubmit('recharge_order', false, { amount: selectedAmount.value, method: 'alipay', error: res.message })
        showCreateOrderError(res.message)
        return
      }

      trackSubmit('recharge_order', true, { amount: selectedAmount.value, method: 'alipay', order_no: res.data.order_no })
      currentOrderNo.value = res.data.order_no

      if (res.data.pay_form) {
        const div = document.createElement('div')
        div.innerHTML = res.data.pay_form
        document.body.appendChild(div)
      } else if (res.data.pay_url) {
        window.location.href = res.data.pay_url
      }

      startQueryTimer()
    }
  } catch (error) {
    reportRechargeError('create_order_failed', error, {
      amount: selectedAmount.value,
      payment_method: paymentMethod.value,
      order_no: maskOrderNo(currentOrderNo.value)
    })
    ElMessage.error('网络错误，请稍后重试')
  } finally {
    creatingOrder.value = false
  }
}

// 生成支付二维码
const generateQRCode = async () => {
  generatingQR.value = true
  qrCodeUrl.value = ''

  try {
    const res = await fetch(`/api/payment/qrcode?order_no=${currentOrderNo.value}&amount=${selectedAmount.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })

    if (!res.ok) {
      applyFallbackQrCode()
      return
    }

    const data = await res.json()
    if (data.code === 200 && data.data.qr_url) {
      qrCodeUrl.value = data.data.qr_url
      return
    }

    applyFallbackQrCode()
  } catch (error) {
    reportRechargeError('generate_qr_code_failed', error, {
      amount: selectedAmount.value,
      order_no: maskOrderNo(currentOrderNo.value)
    })
    applyFallbackQrCode()
  } finally {
    generatingQR.value = false
  }
}

// 调用微信支付
const callWechatPay = (payParams) => {
  if (!getWechatBridge()) {
    const handleBridgeReady = () => {
      doWechatPay(payParams)
    }

    if (document.addEventListener) {
      document.addEventListener('WeixinJSBridgeReady', handleBridgeReady, false)
    } else if (document.attachEvent) {
      document.attachEvent('WeixinJSBridgeReady', handleBridgeReady)
      document.attachEvent('onWeixinJSBridgeReady', handleBridgeReady)
    }
    return
  }

  doWechatPay(payParams)
}

// 执行微信支付
const doWechatPay = (payParams) => {
  const bridge = getWechatBridge()
  if (!bridge) {
    reportRechargeError('wechat_bridge_missing', new Error('WeixinJSBridge unavailable'), {
      order_no: maskOrderNo(currentOrderNo.value)
    })
    ElMessage.error('微信支付环境尚未就绪，请稍后重试')
    return
  }

  bridge.invoke('getBrandWCPayRequest', {
    appId: payParams.appId,
    timeStamp: payParams.timeStamp,
    nonceStr: payParams.nonceStr,
    package: payParams.package,
    signType: payParams.signType,
    paySign: payParams.paySign
  }, (res) => {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      ElMessage.success('支付成功！')
      loadPointsBalance()
      loadRechargeHistory()
      return
    }

    if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      ElMessage.info('已取消支付')
      return
    }

    reportRechargeError('wechat_pay_failed', new Error(res.err_msg || 'wechat pay failed'), {
      err_msg: truncateRechargeMessage(res.err_msg || ''),
      order_no: maskOrderNo(currentOrderNo.value)
    })
    ElMessage.error('支付失败，请重试')
  })
}

// 查询支付状态的定时器
let queryTimer = null

const startQueryTimer = () => {
  stopQueryTimer()
  queryTimer = setInterval(() => {
    checkPayStatus(true)
  }, 3000) // 每3秒查询一次
  
  // 5分钟后自动停止查询
  setTimeout(() => {
    stopQueryTimer()
  }, 300000)
}

const stopQueryTimer = () => {
  if (queryTimer) {
    clearInterval(queryTimer)
    queryTimer = null
  }
}

// 检查支付状态
const checkPayStatus = async (silent = false) => {
  if (!currentOrderNo.value) return
  
  checkingStatus.value = true
  try {
    const res = await queryRechargeOrder({ order_no: currentOrderNo.value })
    
    if (res.code === 200) {
      if (res.data.status === 'paid') {
        stopQueryTimer()
        payDialogVisible.value = false
        ElMessage.success('支付成功！积分已到账')
        loadPointsBalance()
        loadRechargeHistory()
        currentOrderNo.value = ''
      } else if (res.data.status === 'cancelled') {
        stopQueryTimer()
        payDialogVisible.value = false
        ElMessage.info('订单已取消')
        currentOrderNo.value = ''
      } else if (!silent) {
        ElMessage.info('订单支付中，请稍后...')
      }
    }
  } catch (error) {
    if (!silent) {
      ElMessage.error('查询失败')
    }
  } finally {
    checkingStatus.value = false
  }
}

// 取消支付
const cancelPay = () => {
  stopQueryTimer()
  payDialogVisible.value = false
  currentOrderNo.value = ''
  qrCodeUrl.value = ''
}

// 支付弹窗关闭
const handlePayDialogClose = () => {
  stopQueryTimer()
  currentOrderNo.value = ''
}

// 获取状态文本
const getStatusText = (status) => {
  const statusMap = {
    'pending': '待支付',
    'paid': '已支付',
    'cancelled': '已取消',
    'refunded': '已退款'
  }
  return statusMap[status] || status
}
</script>

<style scoped>
.recharge-page {
  padding: 60px 0;
  max-width: 960px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .recharge-page {
    padding: 40px 0 100px 0;
  }
}

@media (max-width: 480px) {
  .recharge-page {
    padding: 30px 0 80px 0;
  }
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

@media (max-width: 768px) {
  .page-header {
    margin-bottom: 20px;
    gap: 12px;
  }
}

.page-header .section-title {
  margin: 0;
  font-size: clamp(24px, 5vw, 32px);
}

.current-points {
  text-align: center;
  padding: 30px;
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .current-points {
    padding: 20px;
    margin-bottom: 15px;
  }
}

.points-display {
  margin-bottom: 10px;
}

.points-label {
  display: block;
  color: var(--text-tertiary);
  font-size: 14px;
  margin-bottom: 10px;
}

.points-value {
  display: block;
  font-size: 48px;
  font-weight: bold;
  color: var(--primary-light);
}

.points-tip {
  color: var(--text-tertiary);
  font-size: 14px;
  margin: 0;
}

.recharge-options {
  margin-bottom: 20px;
}

.recharge-options h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  transition: all 0.3s ease;
}

@media (max-width: 992px) {
  .options-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .options-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }
}

.option-item {
  background: var(--bg-card);
  border: 2px solid transparent;
  border-radius: var(--radius-md);
  padding: 20px 15px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.option-item::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: var(--primary-color);
  opacity: 0.1;
  transform: translate(-50%, -50%);
  transition: width 0.6s ease, height 0.6s ease;
}

.option-item:active::before {
  width: 300px;
  height: 300px;
}

.option-item:hover {
  background: var(--white-08);
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.option-item:active {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.option-item.active {
  border-color: var(--primary-color);
  background: var(--primary-light-10);
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
}

.option-item.active::after {
  content: '✓';
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: bold;
  animation: checkmark 0.3s ease;
}

@keyframes checkmark {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

.option-item.hot {
  border-color: var(--primary-light);
}

.option-item.vip {
  background: linear-gradient(135deg, #2c2c2c, #1a1a1a);
  border: 2px solid #D4AF37;
  grid-column: span 3; /* Make it full width if possible, or handle grid */
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 25px 40px;
}

@media (max-width: 768px) {
  .option-item.vip {
    grid-column: span 2;
    flex-direction: column;
    padding: 20px;
    gap: 10px;
  }
}

@media (max-width: 480px) {
  .option-item.vip {
    grid-column: span 1;
  }
}

.option-item.vip.active {
  background: linear-gradient(135deg, #3d3d3d, #252525);
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
}

.vip-crown-icon {
  font-size: 32px;
  margin-right: 20px;
  animation: float 3s ease-in-out infinite;
}

.option-item.vip .amount {
  color: #D4AF37;
  font-size: 36px;
  margin-bottom: 0;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.option-item.vip .points {
  color: #f0e68c;
  font-size: 24px;
  font-weight: bold;
}

.option-item.vip .desc {
  color: #bdbdbd;
  font-size: 14px;
  margin-top: 5px;
}

/* Animations */
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

.payment-info {
  margin-bottom: 20px;
}

.payment-info h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-secondary);
}

.info-row.total {
  border-bottom: none;
  font-size: 18px;
  font-weight: bold;
}

.info-row .highlight {
  color: var(--text-primary);
  font-weight: 500;
}

.info-row .total-amount {
  color: var(--primary-color);
  font-size: 24px;
}

/* 支付方式选择 */
.payment-methods {
  margin-bottom: 20px;
}

.payment-label {
  display: block;
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 12px;
}

.payment-options {
  display: flex;
  gap: 15px;
}

.payment-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px 20px;
  background: var(--bg-card);
  border: 2px solid var(--border-light);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: var(--text-secondary);
  position: relative;
  overflow: hidden;
}

.payment-option::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: var(--primary-color);
  opacity: 0.05;
  transform: translate(-50%, -50%);
  transition: width 0.4s ease, height 0.4s ease;
}

.payment-option:active::before {
  width: 200px;
  height: 200px;
}

.payment-option:hover {
  background: var(--white-08);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.payment-option:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.payment-option.active {
  border-color: var(--primary-color);
  background: var(--primary-light-10);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(212, 175, 55, 0.25);
}

.payment-option.active::after {
  content: '✓';
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  animation: checkmark 0.3s ease;
}

.payment-option span {
  font-size: 15px;
  font-weight: 500;
  color: var(--text-primary);
}

@media (max-width: 480px) {
  .payment-option {
    padding: 14px 16px;
  }

  .payment-option span {
    font-size: 14px;
  }
}

.pay-btn {
  width: 100%;
  margin-top: 20px;
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  border: none;
}

.pay-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
  background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
}

.pay-btn:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3);
}

.pay-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: var(--bg-tertiary);
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-icon {
  transition: transform 0.3s ease;
}

.pay-btn:hover:not(:disabled) .btn-icon {
  transform: translateX(4px);
}

@media (max-width: 768px) {
  .pay-btn {
    height: 52px;
    font-size: 17px;
  }
}

@media (max-width: 480px) {
  .pay-btn {
    height: 50px;
    font-size: 16px;
  }
}

.pay-tip {
  text-align: center;
  color: var(--text-tertiary);
  font-size: 13px;
  margin-top: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.recharge-history {
  margin-bottom: 20px;
}

.recharge-history h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
}

.history-list {
  max-height: 300px;
  overflow-y: auto;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid var(--border-color);
}

.history-item:last-child {
  border-bottom: none;
}

.history-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.history-amount {
  color: var(--text-primary);
  font-weight: bold;
  font-size: 16px;
}

.history-points {
  color: var(--primary-light);
  font-size: 14px;
}

.history-time {
  color: var(--text-tertiary);
  font-size: 12px;
}

.history-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
}

.history-status.pending {
  background: var(--warning-light);
  color: var(--warning-color);
}

.history-status.paid {
  background: var(--success-light);
  color: var(--success-color);
}

.history-status.cancelled,
.history-status.refunded {
  background: var(--bg-tertiary);
  color: var(--text-tertiary);
}

/* 支付弹窗样式 */
.pay-dialog-content {
  text-align: center;
  padding: 20px 0;
}

.pay-amount {
  font-size: 36px;
  font-weight: bold;
  color: var(--primary-color);
  margin-bottom: 20px;
}

.pay-qrcode {
  width: 200px;
  height: 200px;
  margin: 0 auto 20px;
  background: #fff;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qrcode-placeholder {
  color: #999;
  text-align: center;
}

.qrcode-placeholder p {
  margin-top: 10px;
  font-size: 14px;
}

.pay-wechat {
  margin-bottom: 20px;
}

.pay-wechat p {
  margin-top: 10px;
  color: #07c160;
}

.pay-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .options-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .points-value {
    font-size: 36px;
  }
}

@media (max-width: 480px) {
  .options-grid {
    grid-template-columns: 1fr;
  }
}

/* 支付二维码样式 */
.qrcode-image {
  width: 200px;
  height: 200px;
  margin: 0 auto;
  background: #fff;
  border-radius: 8px;
  padding: 10px;
}

.qrcode-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
</style>
