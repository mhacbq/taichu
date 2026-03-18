<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <div class="logo">
            <el-icon class="logo-icon"><Star /></el-icon>
            <span>太初命理</span>
          </div>
          <h2>{{ loginTitle }}</h2>
          <p>{{ loginSubtitle }}</p>
          <p v-if="isRegisterIntent" class="intent-tip">当前入口会在验证成功后直接发放新手积分，无需再额外找注册按钮。</p>
        </div>

        <!-- 登录表单 -->
        <div class="login-methods">
          <div class="phone-login-form">
            <el-input
              v-model="phoneForm.phone"
              placeholder="请输入手机号"
              size="large"
              class="login-input"
              maxlength="11"
            >
              <template #prefix>
                <el-icon><Phone /></el-icon>
              </template>
            </el-input>
            <div class="code-input-row">
              <el-input
                v-model="phoneForm.code"
                placeholder="验证码"
                size="large"
                class="login-input code-input"
                maxlength="6"
              >
                <template #prefix>
                  <el-icon><Lock /></el-icon>
                </template>
              </el-input>
              <el-button 
                size="large" 
                :disabled="countdown > 0 || !isValidPhone"
                @click="sendCode"
                class="send-code-btn"
              >
                {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
              </el-button>
            </div>
            <el-button 
              type="primary" 
              size="large" 
              class="login-submit-btn"
              :loading="loading"
              :disabled="!isValidPhone || !isValidCode"
              @click="handlePhoneLogin"
            >
              {{ submitButtonText }}
            </el-button>
          </div>
        </div>

        <div class="login-tips">
          <p><el-icon><Star /></el-icon> {{ primaryTipText }}</p>
          <p><el-icon><LockIcon /></el-icon> 我们严格保护您的隐私信息</p>
          <p class="agreement-tip">
            登录即表示同意
            <el-link type="primary" :underline="false" @click="showAgreement">用户协议</el-link>
            和
            <el-link type="primary" :underline="false" @click="showPrivacy">隐私政策</el-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Phone, Lock, Star, Lock as LockIcon } from '@element-plus/icons-vue'
import { phoneLogin, sendSmsCode } from '../api'
import { validatePhone } from '../utils/validators'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const countdown = ref(0)
let countdownTimer = null

// 手机号表单
const phoneForm = ref({
  phone: '',
  code: ''
})

const isRegisterIntent = computed(() => route.query.intent === 'register')
const loginTitle = computed(() => isRegisterIntent.value ? '注册领取新手积分' : '欢迎回来')
const loginSubtitle = computed(() => isRegisterIntent.value
  ? '验证手机号后会直接创建账号并登录，八字排盘、塔罗占卜等入口都能立即使用。'
  : '登录后即可体验八字排盘、塔罗占卜等服务')
const submitButtonText = computed(() => isRegisterIntent.value ? '领取积分并登录' : '登录')
const primaryTipText = computed(() => isRegisterIntent.value ? '验证成功后即可领取 100 积分新手礼包' : '登录后可获得 100 积分新手礼包')

// 表单验证
const isValidPhone = computed(() => validatePhone(phoneForm.value.phone))
const isValidCode = computed(() => /^\d{6}$/.test(phoneForm.value.code))

const maskPhone = (phone) => {
  const normalizedPhone = String(phone ?? '').trim()
  if (normalizedPhone.length < 7) {
    return normalizedPhone
  }

  return `${normalizedPhone.slice(0, 3)}****${normalizedPhone.slice(-4)}`
}

const truncateLoginMessage = (message) => {
  if (!message) {
    return 'unknown'
  }

  return message.length > 160 ? `${message.slice(0, 157)}...` : message
}

const reportLoginError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[Login]', {
    action,
    error_type: error?.name || typeof error,
    message: truncateLoginMessage(typeof error?.message === 'string' ? error.message : String(error ?? '')),
    ...extra
  })
}

// 显示用户协议
const showAgreement = () => {
  ElMessage.info('用户协议功能开发中')
}

// 显示隐私政策
const showPrivacy = () => {
  ElMessage.info('隐私政策功能开发中')
}

// 发送验证码
const sendCode = async () => {
  if (!isValidPhone.value) {
    ElMessage.warning('请输入正确的手机号')
    return
  }
  
  try {
    const response = await sendSmsCode({ phone: phoneForm.value.phone })
    if (response.code === 200) {
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
    reportLoginError('send_sms_code_failed', error, {
      phone: maskPhone(phoneForm.value.phone)
    })
    ElMessage.error('发送失败，请稍后重试')
  }
}

// 启动倒计时
const startCountdown = () => {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
    }
  }, 1000)
}

// 手机号登录
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
      code: phoneForm.value.code
    })
    
    if (response.code === 200) {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('userInfo', JSON.stringify(response.data.user))
      ElMessage.success(isRegisterIntent.value ? '注册成功，欢迎来到太初命理！' : '登录成功！')
      const redirect = route.query.redirect || '/'
      router.push(redirect)
    } else {
      ElMessage.error(response.message || '登录失败')
    }
  } catch (error) {
    reportLoginError('phone_login_failed', error, {
      phone: maskPhone(phoneForm.value.phone),
      intent: isRegisterIntent.value ? 'register' : 'login'
    })
    ElMessage.error('登录失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

// 清理定时器
onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-primary);
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 420px;
}

.login-box {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 40px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo {
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.logo-icon {
  font-size: 36px;
  color: var(--primary-color);
}

.logo-icon svg {
  width: 36px;
  height: 36px;
}

.login-header h2 {
  color: var(--text-primary);
  font-size: 24px;
  margin-bottom: 10px;
}

.login-header p {
  color: var(--text-secondary);
  font-size: 14px;
}

.intent-tip {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--text-primary) !important;
  line-height: 1.6;
}

.login-methods {
  margin-bottom: 30px;
}

.login-tips {
  text-align: center;
}

.login-tips p {
  color: var(--text-secondary);
  font-size: 13px;
  margin: 8px 0;
}

.agreement-tip {
  margin-top: 15px;
  font-size: 12px !important;
}

/* 手机号登录表单 */
.phone-login-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.login-input :deep(.el-input__wrapper) {
  background: var(--bg-secondary);
  box-shadow: none;
  border: 1px solid var(--border-color);
}

.login-input :deep(.el-input__inner) {
  color: var(--text-primary);
}

.code-input-row {
  display: flex;
  gap: 10px;
}

.code-input {
  flex: 1;
}

.send-code-btn {
  width: 120px;
}

.send-code-btn:disabled {
  opacity: 0.6;
}

.login-submit-btn {
  width: 100%;
  min-height: 48px;
  height: auto;
  font-size: 16px;
}

.login-submit-btn:disabled {
  opacity: 0.6;
}

@media (max-width: 480px) {
  .login-box {
    padding: 30px 20px;
  }

  .code-input-row {
    flex-direction: column;
  }
  
  .send-code-btn {
    width: 100%;
    padding: 0 10px;
  }
}
</style>
