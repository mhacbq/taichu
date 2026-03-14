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

        <!-- 微信登录 -->
        <div class="login-methods">
          <div class="wechat-login">
            <el-button 
              type="success" 
              size="large" 
              class="wechat-btn"
              @click="handleWechatLogin"
              :loading="loading"
            >
              <span class="btn-icon">📱</span>
              微信一键登录
            </el-button>
          </div>

          <div class="divider">
            <span>或</span>
          </div>

          <!-- 游客模式 -->
          <div class="guest-login">
            <el-button 
              type="primary" 
              size="large" 
              plain
              class="guest-btn"
              @click="handleGuestLogin"
            >
              游客访问（功能受限）
            </el-button>
          </div>
        </div>

        <div class="login-tips">
          <p>💡 登录后可获得 100 积分新手礼包</p>
          <p>🔒 我们严格保护您的隐私信息</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { login } from '../api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)

// 模拟微信登录
const handleWechatLogin = async () => {
  loading.value = true
  try {
    // 模拟微信登录（实际应调用微信API）
    const code = 'wx_' + Date.now()
    const response = await login({
      code,
      nickname: '微信用户',
    })
    
    if (response.code === 0) {
      // 保存token
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('userInfo', JSON.stringify(response.data.user))
      
      ElMessage.success('登录成功！欢迎回来')
      
      // 跳转到原页面或首页
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

// 游客登录
const handleGuestLogin = () => {
  ElMessage.info('游客模式仅可浏览，功能需登录后使用')
  router.push('/')
}
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

@media (max-width: 480px) {
  .login-box {
    padding: 30px 20px;
  }
}
</style>
