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
          <div class="footer-quote">
            <p>{{ randomQuote }}</p>
          </div>
          <div class="footer-links">
            <router-link to="/help">帮助中心</router-link>
            <router-link to="/profile">个人中心</router-link>
            <a href="#" @click.prevent="showFeedback">意见反馈</a>
          </div>
          <p>&copy; 2025 太初命理 - 愿你在迷茫中找到方向 ✨</p>
        </div>
      </div>
    </footer>
    
    <!-- 浮动心情陪伴组件 -->
    <div v-if="isLoggedIn" class="floating-companion" :class="{ expanded: showCompanion }">
      <div class="companion-avatar" @click="toggleCompanion">
        <span class="companion-icon">🌸</span>
        <span v-if="!showCompanion" class="companion-pulse"></span>
      </div>
      <div v-if="showCompanion" class="companion-content">
        <div class="companion-header">
          <span>💝 今日陪伴</span>
          <button class="close-btn" @click="toggleCompanion">✕</button>
        </div>
        <div class="companion-message">
          <p>{{ companionMessage }}</p>
        </div>
        <div class="companion-actions">
          <router-link to="/daily" class="companion-btn" @click="showCompanion = false">
            <span>🌟</span>
            <span>查看运势</span>
          </router-link>
          <router-link to="/bazi" class="companion-btn" @click="showCompanion = false">
            <span>📅</span>
            <span>八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="companion-btn" @click="showCompanion = false">
            <span>🎴</span>
            <span>塔罗占卜</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPointsBalance } from './api'

const router = useRouter()
const route = useRoute()

const isLoggedIn = ref(false)
const userNickname = ref('')
const userPoints = ref(0)
const showMobileMenu = ref(false)
const showCompanion = ref(false)

// 暖心语录
const companionMessages = [
  '无论今天过得怎样，都要记得你值得被温柔以待。',
  '有时候走得慢一点没关系，重要的是方向正确。',
  '你已经做得很好了，给自己一点鼓励吧！',
  '迷茫的时候，不妨先停下来，听听内心的声音。',
  '生活总有起起落落，但请相信，一切都会好起来的。',
  '不要和别人比较，每个人的花期都不同。',
  '累了就休息一下，这不是放弃，而是为了走得更远。',
  '你的感受很重要，不要忽视自己的情绪。',
  '今天的你，已经很棒了！',
  '相信自己，你比想象中更有力量。',
]

const companionMessage = computed(() => {
  const dayOfYear = Math.floor((new Date() - new Date(new Date().getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24))
  return companionMessages[dayOfYear % companionMessages.length]
})

const footerQuotes = [
  '✨ 愿你在迷茫中找到方向',
  '🌸 迷茫不是软弱，而是成长的开始',
  '💝 你值得被这个世界温柔以待',
  '🌟 相信自己，你比想象中更有力量',
  '🌱 慢慢来，没关系',
]

const randomQuote = computed(() => {
  const dayOfYear = Math.floor((new Date() - new Date(new Date().getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24))
  return footerQuotes[dayOfYear % footerQuotes.length]
})

const toggleCompanion = () => {
  showCompanion.value = !showCompanion.value
}

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
  padding: 40px 0;
  color: rgba(255, 255, 255, 0.6);
}

.footer-content {
  text-align: center;
}

.footer-quote {
  margin-bottom: 20px;
}

.footer-quote p {
  color: rgba(233, 69, 96, 0.9);
  font-size: 16px;
  font-style: italic;
  animation: fadeIn 2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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

/* 浮动陪伴组件 */
.floating-companion {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 999;
}

.companion-avatar {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(233, 69, 96, 0.4);
  transition: all 0.3s ease;
  position: relative;
}

.companion-avatar:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(233, 69, 96, 0.5);
}

.companion-icon {
  font-size: 28px;
}

.companion-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(233, 69, 96, 0.4);
  animation: pulse-ring 2s ease-out infinite;
}

@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}

.companion-content {
  position: absolute;
  bottom: 70px;
  right: 0;
  width: 280px;
  background: rgba(30, 30, 50, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.companion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  color: #fff;
  font-size: 14px;
}

.close-btn {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  font-size: 16px;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.3s ease;
}

.close-btn:hover {
  color: #fff;
}

.companion-message {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
}

.companion-message p {
  color: rgba(255, 255, 255, 0.85);
  font-size: 14px;
  line-height: 1.7;
  margin: 0;
}

.companion-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.companion-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  text-decoration: none;
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px;
  transition: all 0.3s ease;
}

.companion-btn:hover {
  background: rgba(233, 69, 96, 0.2);
  color: #fff;
  transform: translateX(5px);
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
  
  .floating-companion {
    bottom: 20px;
    right: 20px;
  }
  
  .companion-content {
    width: 260px;
    right: -10px;
  }
}

@media (min-width: 769px) {
  .mobile-nav {
    display: none !important;
  }
}
</style>
