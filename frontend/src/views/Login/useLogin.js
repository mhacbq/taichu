import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { phoneLogin, sendSmsCode } from '../../api'
import { validatePhone } from '../../utils/validators'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../../utils/tracker'

export function useLogin() {
  const router = useRouter()
  const route = useRoute()
  const loading = ref(false)
  const countdown = ref(0)
  let countdownTimer = null

  // 手机号表单
  const phoneForm = ref({
    phone: '',
    code: '',
  })

  // ===== 计算属性 =====
  const isRegisterIntent = computed(() => route.query.intent === 'register')
  const loginTitle = computed(() => (isRegisterIntent.value ? '注册领取新手积分' : '欢迎回来'))
  const loginSubtitle = computed(() =>
    isRegisterIntent.value
      ? '验证手机号后会直接创建账号并登录，八字排盘、塔罗占卜等入口都能立即使用。'
      : '登录后即可体验八字排盘、塔罗占卜等服务'
  )
  const submitButtonText = computed(() => {
    if (loading.value) return '验证中...'
    return isRegisterIntent.value ? '领取积分并登录' : '登录'
  })
  const primaryTipText = computed(() =>
    isRegisterIntent.value ? '验证成功后即可领取 100 积分新手礼包' : '登录后可获得 100 积分新手礼包'
  )

  // 表单验证
  const isValidPhone = computed(() => validatePhone(phoneForm.value.phone))
  const isValidCode = computed(() => /^\d{6}$/.test(phoneForm.value.code))
  const isLoggedIn = computed(() => !!localStorage.getItem('token'))

  // ===== 工具方法 =====
  const maskPhone = (phone) => {
    const normalizedPhone = String(phone ?? '').trim()
    if (normalizedPhone.length < 7) return normalizedPhone
    return `${normalizedPhone.slice(0, 3)}****${normalizedPhone.slice(-4)}`
  }

  const truncateLoginMessage = (message) => {
    if (!message) return 'unknown'
    return message.length > 160 ? `${message.slice(0, 157)}...` : message
  }

  const reportLoginError = (action, error, extra = {}) => {
    if (!import.meta.env.DEV) return
    console.error('[Login]', {
      action,
      error_type: error?.name || typeof error,
      message: truncateLoginMessage(typeof error?.message === 'string' ? error.message : String(error ?? '')),
      ...extra,
    })
  }

  // ===== 导航 =====
  const showAgreement = () => {
    const routeData = router.resolve({ path: '/legal/agreement' })
    window.open(routeData.href, '_blank')
  }

  const showPrivacy = () => {
    const routeData = router.resolve({ path: '/legal/privacy' })
    window.open(routeData.href, '_blank')
  }

  // ===== 验证码 =====
  const startCountdown = () => {
    countdown.value = 60
    countdownTimer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) clearInterval(countdownTimer)
    }, 1000)
  }

  const sendCode = async () => {
    if (!isValidPhone.value) {
      ElMessage.warning('请输入正确的手机号')
      return
    }
    try {
      const response = await sendSmsCode({ phone: phoneForm.value.phone })
      if (response.code === 0) {
        const testCode = response.data?.test_code
        if (testCode) {
          ElMessage.success(`测试模式验证码：${testCode}`)
        } else {
          ElMessage.success('验证码已发送')
        }
        startCountdown()
      } else {
        ElMessage.error(response.message || '发送失败')
      }
    } catch (error) {
      reportLoginError('send_sms_code_failed', error, { phone: maskPhone(phoneForm.value.phone) })
      ElMessage.error('发送失败，请稍后重试')
    }
  }

  // ===== 登录 =====
  const handlePhoneLogin = async () => {
    if (!isValidPhone.value) {
      ElMessage.warning('请输入正确的手机号')
      return
    }
    if (!isValidCode.value) {
      ElMessage.warning('请输入6位验证码')
      return
    }

    loading.value = true
    try {
      const response = await phoneLogin({
        phone: phoneForm.value.phone,
        code: phoneForm.value.code,
      })

      if (response.code === 0) {
        localStorage.setItem('token', response.data.token)
        localStorage.setItem('userInfo', JSON.stringify(response.data.user))

        if (response.data.is_new_user) {
          localStorage.setItem('isNewUser', 'true')
          trackEvent('register_success', { method: 'phone' })
        } else {
          trackEvent('login_success', { method: 'phone' })
        }
        trackSubmit('login_form', true, { method: 'phone' })

        ElMessage.success(isRegisterIntent.value ? '注册成功，欢迎来到太初命理！' : '登录成功！')
        const redirect = route.query.redirect || '/'
        router.push(redirect)
      } else {
        trackSubmit('login_form', false, { method: 'phone', error: response.message })
        ElMessage.error(response.message || '登录失败')
      }
    } catch (error) {
      trackSubmit('login_form', false, { method: 'phone', error: error.message })
      trackError('login_error', error.message)
      reportLoginError('phone_login_failed', error, {
        phone: maskPhone(phoneForm.value.phone),
        intent: isRegisterIntent.value ? 'register' : 'login',
      })
      ElMessage.error('登录失败，请稍后重试')
    } finally {
      loading.value = false
    }
  }

  // ===== 生命周期 =====
  onMounted(() => {
    trackPageView('login')
    if (isRegisterIntent.value) {
      trackEvent('register_intent_view')
    }
  })

  onUnmounted(() => {
    if (countdownTimer) clearInterval(countdownTimer)
  })

  return {
    phoneForm,
    loading,
    countdown,
    isRegisterIntent,
    loginTitle,
    loginSubtitle,
    submitButtonText,
    primaryTipText,
    isValidPhone,
    isValidCode,
    isLoggedIn,
    sendCode,
    handlePhoneLogin,
    showAgreement,
    showPrivacy,
  }
}
