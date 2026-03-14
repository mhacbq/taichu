<template>
  <div class="app">
    <nav class="navbar">
      <div class="container nav-container">
        <router-link to="/" class="logo">
          <span class="logo-icon">☯</span>
          <span>太初命理</span>
        </router-link>
        
        <!-- 桌面端导航 -->
        <div class="nav-links desktop-nav">
          <router-link to="/" class="nav-link">首页</router-link>
          <router-link to="/bazi" class="nav-link">八字排盘</router-link>
          <router-link to="/tarot" class="nav-link">塔罗占卜</router-link>
          <router-link to="/daily" class="nav-link">每日运势</router-link>
          <router-link to="/profile" class="nav-link">个人中心</router-link>
          <router-link to="/help" class="nav-link">帮助</router-link>
        </div>

        <!-- 用户操作区 -->
        <div class="user-actions">
          <template v-if="isLoggedIn">
            <span class="points-badge">💎 {{ userPoints }}</span>
            <el-dropdown @command="handleCommand">
              <span class="user-info">
                <span class="avatar">{{ userNickname?.[0] || '用' }}</span>
                <span class="nickname">{{ userNickname }}</span>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">个人中心</el-dropdown-item>
                  <el-dropdown-item command="logout" divided>退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
          <router-link v-else to="/login" class="login-btn">登录</router-link>
        </div>

        <!-- 移动端汉堡菜单按钮 -->
        <button class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu">
          <span class="menu-icon">☰</span>
        </button>
      </div>

      <!-- 移动端导航菜单 -->
      <div class="mobile-nav" :class="{ active: showMobileMenu }">
        <router-link to="/" class="mobile-nav-link" @click="showMobileMenu = false">首页</router-link>
        <router-link to="/bazi" class="mobile-nav-link" @click="showMobileMenu = false">八字排盘</router-link>
        <router-link to="/tarot" class="mobile-nav-link" @click="showMobileMenu = false">塔罗占卜</router-link>
        <router-link to="/daily" class="mobile-nav-link" @click="showMobileMenu = false">每日运势</router-link>
        <router-link to="/profile" class="mobile-nav-link" @click="showMobileMenu = false">个人中心</router-link>
        <router-link to="/help" class="mobile-nav-link" @click="showMobileMenu = false">帮助中心</router-link>
        <div class="mobile-nav-divider"></div>
        <template v-if="isLoggedIn">
          <div class="mobile-user-info">
            <span class="mobile-points">💎 积分: {{ userPoints }}</span>
          </div>
          <a href="#" class="mobile-nav-link logout" @click.prevent="handleLogout">退出登录</a>
        </template>
        <router-link v-else to="/login" class="mobile-nav-link login" @click="showMobileMenu = false">登录</router-link>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-links">
            <router-link to="/help">帮助中心</router-link>
            <router-link to="/profile">个人中心</router-link>
            <a href="#" @click.prevent="showFeedback">意见反馈</a>
          </div>
          <p>&copy; 2025 太初命理 - AI智能命理分析平台</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPointsBalance } from './api'

const router = useRouter()
const route = useRoute()

const isLoggedIn = ref(false)
const userNickname = ref('')
const userPoints = ref(0)
const showMobileMenu = ref(false)

// 检查登录状态
const checkLoginStatus = () => {
  const token = localStorage.getItem('token')
  const userInfo = localStorage.getItem('userInfo')
  
  if (token && userInfo) {
    isLoggedIn.value = true
    const user = JSON.parse(userInfo)
    userNickname.value = user.nickname || '用户'
    userPoints.value = user.points || 0
    // 刷新积分
    refreshPoints()
  } else {
    isLoggedIn.value = false
    userNickname.value = ''
    userPoints.value = 0
  }
}

// 刷新积分
const refreshPoints = async () => {
  try {
    const response = await getPointsBalance()
    if (response.code === 0) {
      userPoints.value = response.data.balance
      // 更新本地存储
      const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}')
      userInfo.points = response.data.balance
      localStorage.setItem('userInfo', JSON.stringify(userInfo))
    }
  } catch (error) {
    console.error('刷新积分失败:', error)
  }
}

// 处理下拉菜单命令
const handleCommand = (command) => {
  if (command === 'profile') {
    router.push('/profile')
  } else if (command === 'logout') {
    handleLogout()
  }
}

// 退出登录
const handleLogout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('userInfo')
  isLoggedIn.value = false
  ElMessage.success('已退出登录')
  router.push('/')
  showMobileMenu.value = false
}

// 显示反馈
const showFeedback = () => {
  router.push('/profile')
}

// 监听路由变化，检查登录状态
watch(() => route.path, () => {
  checkLoginStatus()
  showMobileMenu.value = false
})

onMounted(() => {
  checkLoginStatus()
})
</script>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  padding: 15px 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-icon {
  font-size: 28px;
  color: #e94560;
}

.nav-links {
  display: flex;
  gap: 30px;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 16px;
  transition: all 0.3s ease;
  position: relative;
}

.nav-link:hover,
.nav-link.router-link-active {
  color: #fff;
}

.nav-link.router-link-active::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #e94560, #ff6b6b);
  border-radius: 1px;
}

/* 用户操作区 */
.user-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.points-badge {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 14px;
  color: #fff;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: #fff;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.nickname {
  font-size: 14px;
}

.login-btn {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  color: #fff;
  padding: 8px 20px;
  border-radius: 20px;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.3s ease;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(233, 69, 96, 0.3);
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  color: #fff;
  font-size: 24px;
  cursor: pointer;
  padding: 5px;
}

/* 移动端导航 */
.mobile-nav {
  display: none;
  flex-direction: column;
  padding: 20px;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-nav.active {
  display: flex;
}

.mobile-nav-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  padding: 15px 0;
  font-size: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-nav-link:hover,
.mobile-nav-link.router-link-active {
  color: #fff;
}

.mobile-nav-link.login {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  text-align: center;
  border-radius: 10px;
  margin-top: 10px;
  border: none;
}

.mobile-nav-link.logout {
  color: #f56c6c;
}

.mobile-nav-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 10px 0;
}

.mobile-user-info {
  padding: 15px 0;
  color: rgba(255, 255, 255, 0.8);
}

.mobile-points {
  font-size: 14px;
}

.main-content {
  flex: 1;
}

.footer {
  background: rgba(0, 0, 0, 0.3);
  padding: 30px 0;
  color: rgba(255, 255, 255, 0.6);
}

.footer-content {
  text-align: center;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-bottom: 15px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #e94560;
}

.footer p {
  font-size: 13px;
}

/* 响应式 */
@media (max-width: 768px) {
  .desktop-nav {
    display: none;
  }
  
  .user-actions {
    display: none;
  }
  
  .mobile-menu-btn {
    display: block;
  }
}

@media (min-width: 769px) {
  .mobile-nav {
    display: none !important;
  }
}
</style>
