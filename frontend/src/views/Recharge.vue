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
            :class="{ active: selectedAmount === option.amount, hot: option.bonus > 0 }"
            @click="selectAmount(option.amount)"
          >
            <div class="amount">¥{{ option.amount }}</div>
            <div class="points">{{ option.points }}积分</div>
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
          @click="handleRecharge"
        >
          立即支付
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
import { InfoFilled, Picture, ChatDotRound, Loading, Wallet } from '@element-plus/icons-vue'
import { getPointsBalance } from '../api'
import { getRechargeOptions, createRechargeOrder, queryRechargeOrder, getRechargeHistory } from '../api/payment'
import { createAlipayOrder } from '../api/alipay'
import BackButton from '../components/BackButton.vue'
import { formatDate } from '../utils/format'

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

// 检查是否在微信浏览器中
const checkWechatBrowser = () => {
  const ua = navigator.userAgent.toLowerCase()
  return ua.includes('micromessenger')
}

onMounted(() => {
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
    console.error('加载积分余额失败:', error)
  }
}

// 加载充值选项
const loadRechargeOptions = async () => {
  try {
    const res = await getRechargeOptions()
    if (res.code === 0) {
      rechargeOptions.value = res.data.options
      // 默认选中第一个
      if (rechargeOptions.value.length > 0) {
        selectedAmount.value = rechargeOptions.value[0].amount
      }
    }
  } catch (error) {
    console.error('加载充值选项失败:', error)
  }
}

// 加载充值记录
const loadRechargeHistory = async () => {
  try {
    const res = await getRechargeHistory()
    if (res.code === 0) {
      rechargeHistory.value = res.data || []
    }
  } catch (error) {
    console.error('加载充值记录失败:', error)
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

  creatingOrder.value = true
  try {
    if (paymentMethod.value === 'wechat') {
      // 微信支付
      const res = await createRechargeOrder({
        amount: selectedAmount.value
      })

      if (res.code === 0) {
        currentOrderNo.value = res.data.order_no
        
        // 如果在微信浏览器中，直接调起微信支付
        if (isWechatBrowser.value && res.data.pay_params) {
          callWechatPay(res.data.pay_params)
        } else {
          // PC端或其他浏览器显示支付弹窗
          payDialogVisible.value = true
          // 生成支付二维码
          generateQRCode()
          startQueryTimer()
        }
      } else {
        ElMessage.error(res.message || '创建订单失败')
      }
    } else if (paymentMethod.value === 'alipay') {
      // 支付宝支付
      const res = await createAlipayOrder({
        amount: selectedAmount.value
      })

      if (res.code === 0) {
        currentOrderNo.value = res.data.order_no
        
        if (res.data.pay_form) {
          // PC端：插入表单并提交
          const div = document.createElement('div')
          div.innerHTML = res.data.pay_form
          document.body.appendChild(div)
          // 支付宝表单会自动提交
        } else if (res.data.pay_url) {
          // 移动端：跳转支付URL
          window.location.href = res.data.pay_url
        }
        
        startQueryTimer()
      } else {
        ElMessage.error(res.message || '创建订单失败')
      }
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
  } finally {
    creatingOrder.value = false
  }
}

// 生成支付二维码
const generateQRCode = async () => {
  generatingQR.value = true
  qrCodeUrl.value = ''
  
  try {
    // 调用后端API生成微信支付二维码
    const res = await fetch(`/api/payment/qrcode?order_no=${currentOrderNo.value}&amount=${selectedAmount.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (res.ok) {
      const data = await res.json()
      if (data.code === 0 && data.data.qr_url) {
        qrCodeUrl.value = data.data.qr_url
      } else {
        // 如果后端没有二维码接口，使用二维码生成API
        const payUrl = encodeURIComponent(`weixin://wxpay/bizpayurl?pr=${currentOrderNo.value}`)
        qrCodeUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${payUrl}`
      }
    } else {
      // 备用方案：使用在线二维码生成服务
      const payUrl = encodeURIComponent(`weixin://wxpay/bizpayurl?pr=${currentOrderNo.value}`)
      qrCodeUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${payUrl}`
    }
  } catch (error) {
    console.error('生成二维码失败:', error)
    // 使用备用方案
    const payUrl = encodeURIComponent(`weixin://wxpay/bizpayurl?pr=${currentOrderNo.value}`)
    qrCodeUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${payUrl}`
  } finally {
    generatingQR.value = false
  }
}

// 调用微信支付
const callWechatPay = (payParams) => {
  if (typeof WeixinJSBridge === 'undefined') {
    // 等待WeixinJSBridge准备就绪
    if (document.addEventListener) {
      document.addEventListener('WeixinJSBridgeReady', () => {
        doWechatPay(payParams)
      }, false)
    } else if (document.attachEvent) {
      document.attachEvent('WeixinJSBridgeReady', () => {
        doWechatPay(payParams)
      })
      document.attachEvent('onWeixinJSBridgeReady', () => {
        doWechatPay(payParams)
      })
    }
  } else {
    doWechatPay(payParams)
  }
}

// 执行微信支付
const doWechatPay = (payParams) => {
  WeixinJSBridge.invoke('getBrandWCPayRequest', {
    appId: payParams.appId,
    timeStamp: payParams.timeStamp,
    nonceStr: payParams.nonceStr,
    package: payParams.package,
    signType: payParams.signType,
    paySign: payParams.paySign
  }, (res) => {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      // 支付成功
      ElMessage.success('支付成功！')
      loadPointsBalance()
      loadRechargeHistory()
    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      // 用户取消
      ElMessage.info('已取消支付')
    } else {
      // 支付失败
      ElMessage.error('支付失败：' + res.err_msg)
    }
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
    
    if (res.code === 0) {
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

.current-points {
  text-align: center;
  padding: 30px;
  margin-bottom: 20px;
}

.points-display {
  margin-bottom: 10px;
}

.points-label {
  display: block;
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  margin-bottom: 10px;
}

.points-value {
  display: block;
  font-size: 48px;
  font-weight: bold;
  color: #ffd700;
}

.points-tip {
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  margin: 0;
}

.recharge-options {
  margin-bottom: 20px;
}

.recharge-options h3 {
  color: #fff;
  margin-bottom: 20px;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.option-item {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid transparent;
  border-radius: 12px;
  padding: 20px 15px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.option-item:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.option-item.active {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

.option-item.hot {
  border-color: #ffd700;
}

.option-item .amount {
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 5px;
}

.option-item .points {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
}

.option-item .bonus {
  font-size: 12px;
  color: #ffd700;
  margin-top: 5px;
}

.hot-tag {
  position: absolute;
  top: -10px;
  right: -10px;
  background: linear-gradient(135deg, #ffd700, #ff6b6b);
  color: #fff;
  font-size: 10px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 10px;
}

.payment-info {
  margin-bottom: 20px;
}

.payment-info h3 {
  color: #fff;
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
}

.info-row.total {
  border-bottom: none;
  font-size: 18px;
  font-weight: bold;
}

.info-row .highlight {
  color: #fff;
  font-weight: 500;
}

.info-row .total-amount {
  color: #e94560;
  font-size: 24px;
}

/* 支付方式选择 */
.payment-methods {
  margin-bottom: 20px;
}

.payment-label {
  display: block;
  color: rgba(255, 255, 255, 0.8);
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
  padding: 15px 20px;
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: rgba(255, 255, 255, 0.8);
}

.payment-option:hover {
  background: rgba(255, 255, 255, 0.1);
}

.payment-option.active {
  border-color: var(--primary-color, #B8860B);
  background: rgba(184, 134, 11, 0.1);
}

.payment-option span {
  font-size: 14px;
}

.pay-btn {
  width: 100%;
  margin-top: 20px;
  height: 48px;
  font-size: 16px;
}

.pay-tip {
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
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
  color: #fff;
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
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
  color: #fff;
  font-weight: bold;
  font-size: 16px;
}

.history-points {
  color: #ffd700;
  font-size: 14px;
}

.history-time {
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
}

.history-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
}

.history-status.pending {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.history-status.paid {
  background: rgba(76, 175, 80, 0.2);
  color: #4caf50;
}

.history-status.cancelled,
.history-status.refunded {
  background: rgba(158, 158, 158, 0.2);
  color: #9e9e9e;
}

/* 支付弹窗样式 */
.pay-dialog-content {
  text-align: center;
  padding: 20px 0;
}

.pay-amount {
  font-size: 36px;
  font-weight: bold;
  color: #e94560;
  margin-bottom: 20px;
}

.pay-qrcode {
  width: 200px;
  height: 200px;
  margin: 0 auto 20px;
  background: #fff;
  border-radius: 8px;
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
