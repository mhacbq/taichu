import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

import { getPointsBalance } from '../../api'
import { getRechargeOptions, createRechargeOrder, queryRechargeOrder, getRechargeHistory } from '../../api/payment'
import { createAlipayOrder } from '../../api/alipay'

import { formatDate } from '../../utils/format'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../../utils/tracker'

export function useRecharge() {
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
const currentStep = ref(1) // 当前步骤

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
    if (res.code === 0) {
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
    if (res.code === 0) {
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
    if (res.code === 0) {
      rechargeHistory.value = res.data || []
    }
  } catch (error) {
    reportRechargeError('load_recharge_history_failed', error)
  }
}

// 选择金额
const selectAmount = (amount) => {
  selectedAmount.value = amount
  currentStep.value = 2
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

      if (res.code !== 0) {
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

      if (res.code !== 0) {
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
    if (data.code === 0 && data.data.qr_url) {
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

return {
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
}
} // end useRecharge
