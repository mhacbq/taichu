<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <div class="logo">
            <el-icon class="logo-icon"><YinYang /></el-icon>
            <span>太初命理</span>
          </div>
          <h2>欢迎回来</h2>
          <p>登录后即可体验八字排盘、塔罗占卜等服务</p>
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
              登录
            </el-button>
          </div>
        </div>

        <div class="login-tips">
          <p><el-icon><Lightbulb /></el-icon> 登录后可获得 100 积分新手礼包</p>
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
import { Phone, Lock, Lightbulb, Lock as LockIcon, YinYang } from '@element-plus/icons-vue'
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

// 表单验证
const isValidPhone = computed(() => validatePhone(phoneForm.value.phone))
const isValidCode = computed(() => /^\d{6}$/.test(phoneForm.value.code))

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
    if (response.code === 0) {
      ElMessage.success('验证码已发送')
      startCountdown()
    } else {
      ElMessage.error(response.message || '发送失败')
    }
  } catch (error) {
    console.error('发送验证码失败:', error)
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
    
    if (response.code === 0) {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('userInfo', JSON.stringify(response.data.user))
      ElMessage.success('登录成功！')
      const redirect = route.query.redirect || '/'
      router.push(redirect)
    } else {
      ElMessage.error(response.message || '登录失败')
    }
  } catch (error) {
    ElMessage.error('登录失败，请稍后重试')
    console.error(error)
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
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 420px;
}

.login-box {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo {
  font-size: 28px;
  font-weight: bold;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.logo-icon {
  font-size: 36px;
  color: #e94560;
}

.logo-icon svg {
  width: 36px;
  height: 36px;
}

.login-header h2 {
  color: #fff;
  font-size: 24px;
  margin-bottom: 10px;
}

.login-header p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.login-methods {
  margin-bottom: 30px;
}

.login-tips {
  text-align: center;
}

.login-tips p {
  color: rgba(255, 255, 255, 0.5);
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
  background: rgba(255, 255, 255, 0.1);
  box-shadow: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.login-input :deep(.el-input__inner) {
  color: #fff;
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
  height: 50px;
  font-size: 16px;
}

.login-submit-btn:disabled {
  opacity: 0.6;
}

@media (max-width: 480px) {
  .login-box {
    padding: 30px 20px;
  }
  
  .send-code-btn {
    width: 100px;
    padding: 0 10px;
  }
}
</style>
