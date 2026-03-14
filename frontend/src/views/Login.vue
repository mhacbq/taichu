<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <div class="logo">
            <span class="logo-icon">☯</span>
            <span>太初命理</span>
          </div>
          <h2>欢迎回来</h2>
          <p>登录后即可体验八字排盘、塔罗占卜等服务</p>
        </div>

        <!-- 登录方式选择 -->
        <div class="login-methods">
          <!-- 手机号登录 -->
          <div v-if="loginType === 'phone'" class="phone-login-form">
            <el-input
              v-model="phoneForm.phone"
              placeholder="请输入手机号"
              size="large"
              class="login-input"
            >
              <template #prefix>📱</template>
            </el-input>
            <div class="code-input-row">
              <el-input
                v-model="phoneForm.code"
                placeholder="验证码"
                size="large"
                class="login-input code-input"
              >
                <template #prefix>🔐</template>
              </el-input>
              <el-button 
                size="large" 
                :disabled="countdown > 0"
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
              @click="handlePhoneLogin"
            >
              登录
            </el-button>
            <div class="switch-login-type">
              <el-link type="primary" @click="loginType = 'wechat'">微信登录</el-link>
            </div>
          </div>

          <!-- 微信登录 -->
          <div v-else-if="loginType === 'wechat'" class="wechat-login">
            <div v-if="!wechatScanning" class="wechat-login-options">
              <el-button 
                type="success" 
                size="large" 
                class="wechat-btn"
                @click="startWechatScan"
                :loading="loading"
              >
                <span class="btn-icon">📱</span>
                微信扫码登录
              </el-button>
              <div class="divider">
                <span>或</span>
              </div>
              <el-button 
                size="large" 
                class="wechat-mini-btn"
                @click="handleWechatMiniLogin"
              >
                <span class="btn-icon">💬</span>
                微信小程序登录
              </el-button>
            </div>
            
            <!-- 微信扫码模拟 -->
            <div v-else class="wechat-scan">
              <div class="scan-box">
                <div class="qr-placeholder">
                  <span class="qr-icon">📱</span>
                  <p>请使用微信扫一扫</p>
                  <p class="qr-tip">模拟扫码中...</p>
                </div>
              </div>
              <p class="scan-tip">打开微信 > 发现 > 扫一扫</p>
              <el-link type="primary" @click="wechatScanning = false">返回其他登录方式</el-link>
            </div>
            
            <div v-if="!wechatScanning" class="switch-login-type">
              <el-link type="primary" @click="loginType = 'phone'">手机号登录</el-link>
            </div>
          </div>

          <div class="divider" v-if="loginType !== 'phone' && !wechatScanning">
            <span>或</span>
          </div>

          <!-- 游客模式 -->
          <div v-if="!wechatScanning" class="guest-login">
            <el-button 
              type="info" 
              size="large" 
              plain
              class="guest-btn"
              @click="handleGuestLogin"
            >
              <span class="btn-icon">👤</span>
              游客访问（功能受限）
            </el-button>
          </div>
        </div>

        <div class="login-tips">
          <p>💡 登录后可获得 100 积分新手礼包</p>
          <p>🔒 我们严格保护您的隐私信息</p>
          <p class="agreement-tip">
            登录即表示同意
            <el-link type="primary" :underline="false">用户协议</el-link>
            和
            <el-link type="primary" :underline="false">隐私政策</el-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { login, wechatLogin, phoneLogin, sendSmsCode } from '../api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const loginType = ref('wechat') // wechat, phone
const wechatScanning = ref(false)
const countdown = ref(0)
let countdownTimer = null

// 手机号表单
const phoneForm = ref({
  phone: '',
  code: ''
})

// 开始微信扫码
const startWechatScan = () => {
  wechatScanning.value = true
  // 模拟扫码过程
  setTimeout(() => {
    handleWechatLogin()
  }, 2000)
}

// 微信小程序登录
const handleWechatMiniLogin = () => {
  ElMessage.info('请使用微信小程序搜索"太初命理"')
}

// 微信登录
const handleWechatLogin = async () => {
  loading.value = true
  try {
    // 模拟微信登录（实际应调用微信OAuth API）
    const mockCode = 'wx_' + Date.now()
    const response = await wechatLogin({
      code: mockCode,
      type: 'wechat_official'
    })
    
    if (response.code === 0) {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('userInfo', JSON.stringify(response.data.user))
      ElMessage.success('微信登录成功！')
      const redirect = route.query.redirect || '/'
      router.push(redirect)
    } else {
      ElMessage.error(response.message || '登录失败')
      wechatScanning.value = false
    }
  } catch (error) {
    ElMessage.error('登录失败，请稍后重试')
    wechatScanning.value = false
    console.error(error)
  } finally {
    loading.value = false
  }
}

// 发送验证码
const sendCode = async () => {
  if (!phoneForm.value.phone || phoneForm.value.phone.length !== 11) {
    ElMessage.warning('请输入正确的手机号')
    return
  }
  
  try {
    const response = await sendSmsCode({ phone: phoneForm.value.phone })
    if (response.code === 0) {
      ElMessage.success('验证码已发送')
      countdown.value = 60
      countdownTimer = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
          clearInterval(countdownTimer)
        }
      }, 1000)
    } else {
      ElMessage.error(response.message || '发送失败')
    }
  } catch (error) {
    // 模拟发送成功
    ElMessage.success('验证码已发送（模拟）')
    countdown.value = 60
    countdownTimer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(countdownTimer)
      }
    }, 1000)
  }
}

// 手机号登录
const handlePhoneLogin = async () => {
  if (!phoneForm.value.phone || phoneForm.value.phone.length !== 11) {
    ElMessage.warning('请输入正确的手机号')
    return
  }
  if (!phoneForm.value.code || phoneForm.value.code.length !== 6) {
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
    // 模拟登录成功
    const mockUser = {
      id: 'user_' + Date.now(),
      nickname: '用户' + phoneForm.value.phone.slice(-4),
      phone: phoneForm.value.phone,
      points: 100
    }
    localStorage.setItem('token', 'mock_token_' + Date.now())
    localStorage.setItem('userInfo', JSON.stringify(mockUser))
    ElMessage.success('登录成功！（模拟）')
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } finally {
    loading.value = false
  }
}

// 游客登录
const handleGuestLogin = () => {
  ElMessage.info('游客模式仅可浏览，功能需登录后使用')
  router.push('/')
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

.wechat-btn,
.guest-btn {
  width: 100%;
  height: 50px;
  font-size: 16px;
}

.wechat-btn {
  background: #07c160;
  border-color: #07c160;
}

.wechat-btn:hover {
  background: #06ad56;
  border-color: #06ad56;
}

.btn-icon {
  margin-right: 8px;
  font-size: 20px;
}

.divider {
  text-align: center;
  margin: 20px 0;
  position: relative;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
}

.divider span {
  background: rgba(255, 255, 255, 0.05);
  padding: 0 15px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  position: relative;
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

.login-submit-btn {
  width: 100%;
  height: 50px;
  font-size: 16px;
}

.switch-login-type {
  text-align: center;
  margin-top: 15px;
}

/* 微信扫码 */
.wechat-scan {
  text-align: center;
}

.scan-box {
  background: #fff;
  border-radius: 10px;
  padding: 30px;
  margin-bottom: 20px;
}

.qr-placeholder {
  color: #333;
}

.qr-icon {
  font-size: 60px;
}

.qr-tip {
  color: #07c160;
  font-size: 14px;
  margin-top: 10px;
}

.scan-tip {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  margin-bottom: 15px;
}

.wechat-mini-btn {
  width: 100%;
  height: 50px;
  font-size: 16px;
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
  color: #fff;
}

.wechat-mini-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.5);
  color: #fff;
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
