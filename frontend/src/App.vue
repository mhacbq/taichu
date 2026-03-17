<template>
  <div class="app">
    <nav class="navbar">
      <div class="container nav-container">
        <router-link to="/" class="logo">
          <el-icon class="logo-icon" :size="26"><YinYang /></el-icon>
          <span>太初命理</span>
        </router-link>
        
        <!-- 桌面端导航 -->
        <div class="nav-links desktop-nav">
          <router-link to="/" class="nav-link">首页</router-link>
          <router-link to="/bazi" class="nav-link">八字排盘</router-link>
          <router-link to="/tarot" class="nav-link">塔罗占卜</router-link>
          <router-link to="/liuyao" class="nav-link">六爻占卜</router-link>
          <router-link to="/hehun" class="nav-link">八字合婚</router-link>
          <router-link to="/daily" class="nav-link">每日运势</router-link>
        </div>

        <!-- 用户操作区 -->
        <div class="user-actions">
          <template v-if="isLoggedIn">
            <span class="points-badge">
              <el-icon class="points-icon" :size="16"><Diamond /></el-icon>
              <span class="points-value">{{ userPoints }}</span>
            </span>
            <el-dropdown @command="handleCommand" trigger="click">
              <span class="user-info">
                <span class="avatar">{{ userNickname?.[0] || '用' }}</span>
                <span class="nickname">{{ userNickname || '用户' }}</span>
                <span class="dropdown-arrow">▼</span>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">
                    <el-icon class="dropdown-icon" :size="14"><User /></el-icon> 个人中心
                  </el-dropdown-item>
                  <el-dropdown-item command="logout" divided>
                    <el-icon class="dropdown-icon" :size="14"><SwitchButton /></el-icon> 退出登录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
          <router-link v-else to="/login" class="login-btn">登录 / 注册</router-link>
        </div>

        <!-- 移动端汉堡菜单按钮 -->
        <button class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu" :class="{ active: showMobileMenu }">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>

      <!-- 移动端导航菜单 -->
      <div class="mobile-nav" :class="{ active: showMobileMenu }">
        <div class="mobile-nav-header">
          <span class="mobile-nav-title">菜单</span>
          <button class="mobile-nav-close" @click="showMobileMenu = false">✕</button>
        </div>
        <div class="mobile-nav-links">
          <router-link to="/" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><Home /></el-icon> 首页
          </router-link>
          <router-link to="/bazi" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><Calendar /></el-icon> 八字排盘
          </router-link>
          <router-link to="/tarot" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><Magic /></el-icon> 塔罗占卜
          </router-link>
          <router-link to="/liuyao" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><YinYang /></el-icon> 六爻占卜
          </router-link>
          <router-link to="/hehun" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><Link /></el-icon> 八字合婚
          </router-link>
          <router-link to="/daily" class="mobile-nav-link" @click="showMobileMenu = false">
            <el-icon class="nav-icon" :size="18"><Star /></el-icon> 每日运势
          </router-link>
        </div>
        <div class="mobile-nav-footer">
          <template v-if="isLoggedIn">
            <div class="mobile-user-info">
              <span class="mobile-avatar">{{ userNickname?.[0] || '用' }}</span>
              <span class="mobile-nickname">{{ userNickname || '用户' }}</span>
              <span class="mobile-points"><el-icon :size="14"><Diamond /></el-icon> {{ userPoints }}</span>
            </div>
            <a href="#" class="mobile-logout-btn" @click.prevent="handleLogout">
              <el-icon :size="16"><SwitchButton /></el-icon> 退出登录
            </a>
          </template>
          <router-link v-else to="/login" class="mobile-login-btn" @click="showMobileMenu = false">
            登录 / 注册
          </router-link>
        </div>
      </div>
      
      <!-- 遮罩层 -->
      <div class="mobile-overlay" :class="{ active: showMobileMenu }" @click="showMobileMenu = false"></div>
    </nav>
    
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <span class="footer-logo"><el-icon :size="24"><YinYang /></el-icon> 太初命理</span>
            <p class="footer-tagline">传承千年智慧，指引人生方向</p>
          </div>
          <div class="footer-quote">
            <p>{{ randomQuote }}</p>
          </div>
          <div class="footer-links">
            <router-link to="/help">帮助中心</router-link>
            <router-link to="/profile">个人中心</router-link>
            <a href="#" @click.prevent="showFeedback">意见反馈</a>
            <a href="#" @click.prevent="showAbout">关于我们</a>
          </div>
          <div class="footer-divider"></div>
          <p class="footer-copyright">© 2025 太初命理 - 愿你在迷茫中找到方向</p>
        </div>
      </div>
    </footer>
    
    <!-- 浮动心情陪伴组件 -->
    <div v-if="isLoggedIn" class="floating-companion" :class="{ expanded: showCompanion }" @click="toggleCompanion">
      <div class="companion-avatar">
        <el-icon class="companion-icon" :size="28"><Collection /></el-icon>
        <span v-if="!showCompanion" class="companion-pulse"></span>
      </div>
      <div v-if="showCompanion" class="companion-content" @click.stop>
        <div class="companion-header">
          <span class="companion-title"><el-icon :size="16"><Present /></el-icon> 今日陪伴</span>
          <button class="close-btn" @click="showCompanion = false"><el-icon :size="14"><Close /></el-icon></button>
        </div>
        <div class="companion-message">
          <p>{{ companionMessage }}</p>
        </div>
        <div class="companion-actions">
          <router-link to="/daily" class="companion-btn" @click="showCompanion = false">
            <el-icon :size="16"><Sunrise /></el-icon>
            <span>查看运势</span>
          </router-link>
          <router-link to="/bazi" class="companion-btn" @click="showCompanion = false">
            <el-icon :size="16"><Calendar /></el-icon>
            <span>八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="companion-btn" @click="showCompanion = false">
            <el-icon :size="16"><Magic /></el-icon>
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
import { 
  Calendar, 
  Magic, 
  YinYang, 
  Star, 
  User, 
  SwitchButton, 
  Diamond, 
  Close,
  Present,
  Collection,
  Sunrise,
  Link
} from '@element-plus/icons-vue'

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
  '愿你在迷茫中找到方向',
  '迷茫不是软弱，而是成长的开始',
  '你值得被这个世界温柔以待',
  '相信自己，你比想象中更有力量',
  '慢慢来，没关系',
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
    try {
      isLoggedIn.value = true
      const user = JSON.parse(userInfo)
      userNickname.value = user?.nickname || '用户'
      userPoints.value = user?.points || 0
      refreshPoints()
    } catch (e) {
      console.error('解析用户信息失败:', e)
      // 清除无效的登录状态
      localStorage.removeItem('token')
      localStorage.removeItem('userInfo')
      isLoggedIn.value = false
      userNickname.value = ''
      userPoints.value = 0
    }
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
    if (response.code === 200) {
      userPoints.value = response.data.balance
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

// 显示关于我们
const showAbout = () => {
  ElMessage.info('关于我们页面开发中')
}

// 监听路由变化
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

/* 导航栏 - 深色风格 */
.navbar {
  background: rgba(10, 10, 26, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
}

.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 70px;
}

.logo {
  font-size: 22px;
  font-weight: 700;
  color: var(--text-primary);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.logo:hover {
  color: var(--primary-color);
}

.logo-icon {
  font-size: 26px;
  color: var(--primary-color);
}

/* 桌面导航 */
.nav-links {
  display: flex;
  gap: 8px;
}

.nav-link {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 20px;
  transition: all 0.3s ease;
}

.nav-link:hover {
  color: var(--primary-color);
  background: rgba(184, 134, 11, 0.08);
}

.nav-link.router-link-active {
  color: var(--primary-color);
  background: rgba(184, 134, 11, 0.12);
}

/* 用户操作区 */
.user-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.points-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(184, 134, 11, 0.08));
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  color: var(--primary-color);
  border: 1px solid rgba(184, 134, 11, 0.25);
}

.points-icon {
  font-size: 16px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 12px 6px 6px;
  border-radius: 25px;
  background: var(--bg-tertiary);
  transition: all 0.3s ease;
}

.user-info:hover {
  background: var(--border-color);
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: white;
  font-weight: 500;
}

.nickname {
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 500;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dropdown-arrow {
  font-size: 10px;
  color: var(--text-muted);
}

.dropdown-icon {
  margin-right: 8px;
}

.login-btn {
  background: var(--primary-gradient);
  color: #fff;
  padding: 10px 24px;
  border-radius: 25px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(184, 134, 11, 0.3);
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(184, 134, 11, 0.4);
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 10px;
  width: 44px;
  height: 44px;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 6px;
  border-radius: 10px;
  transition: all 0.3s ease;
}

.mobile-menu-btn span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--text-primary);
  border-radius: 2px;
  transition: all 0.3s ease;
}

.mobile-menu-btn.active span:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-btn.active span:nth-child(2) {
  opacity: 0;
}

.mobile-menu-btn.active span:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}

/* 移动端导航 */
.mobile-nav {
  display: none;
  position: fixed;
  top: 0;
  right: -100%;
  width: 80%;
  max-width: 320px;
  height: 100vh;
  background: var(--bg-secondary);
  z-index: 1001;
  flex-direction: column;
  transition: right 0.3s ease;
  box-shadow: -10px 0 40px rgba(0, 0, 0, 0.3);
}

.mobile-nav.active {
  right: 0;
}

.mobile-nav-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border-light);
}

.mobile-nav-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.mobile-nav-close {
  background: none;
  border: none;
  font-size: 20px;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 12px;
  min-width: 44px;
  min-height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.mobile-nav-close:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.mobile-nav-links {
  flex: 1;
  padding: 10px 0;
  overflow-y: auto;
}

.mobile-nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-secondary);
  text-decoration: none;
  padding: 16px 20px;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.mobile-nav-link:hover,
.mobile-nav-link.router-link-active {
  color: var(--primary-color);
  background: rgba(184, 134, 11, 0.08);
  border-left-color: var(--primary-color);
}

.nav-icon {
  font-size: 20px;
  width: 28px;
  text-align: center;
}

.mobile-nav-footer {
  padding: 20px;
  border-top: 1px solid var(--border-light);
  background: var(--bg-secondary);
}

.mobile-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.mobile-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 500;
}

.mobile-nickname {
  flex: 1;
  font-weight: 500;
  color: var(--text-primary);
}

.mobile-points {
  background: rgba(212, 175, 55, 0.12);
  padding: 6px 12px;
  border-radius: 15px;
  color: var(--primary-color);
  font-weight: 600;
  font-size: 13px;
}

.mobile-login-btn,
.mobile-logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.mobile-login-btn {
  background: var(--primary-gradient);
  color: white;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);
}

.mobile-logout-btn {
  background: white;
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
}

.mobile-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.mobile-overlay.active {
  display: block;
  opacity: 1;
}

/* 主要内容区 */
.main-content {
  flex: 1;
}

/* 页脚 - 深色风格 */
.footer {
  background: var(--bg-primary);
  padding: 60px 0 30px;
  border-top: 1px solid var(--border-color);
}

.footer-content {
  text-align: center;
}

.footer-brand {
  margin-bottom: 24px;
}

.footer-logo {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.footer-tagline {
  font-size: 14px;
  color: var(--text-tertiary);
  margin-top: 8px;
}

.footer-quote {
  margin-bottom: 30px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.05), rgba(184, 134, 11, 0.05));
  border-radius: 12px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.footer-quote p {
  color: var(--primary-color);
  font-size: 16px;
  font-weight: 500;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-bottom: 30px;
  flex-wrap: wrap;
}

.footer-links a {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: var(--primary-color);
}

.footer-divider {
  height: 1px;
  background: var(--border-color);
  max-width: 400px;
  margin: 0 auto 20px;
}

.footer-copyright {
  font-size: 13px;
  color: var(--text-muted);
}

/* 浮动陪伴组件 - 优化版 */
.floating-companion {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 998;
}

.companion-avatar {
  width: 56px;
  height: 56px;
  background: var(--primary-gradient);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(184, 134, 11, 0.35);
  transition: all 0.3s ease;
  position: relative;
}

.companion-avatar:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(184, 134, 11, 0.45);
}

.companion-icon {
  font-size: 28px;
  color: white;
}

.companion-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(184, 134, 11, 0.4);
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
  width: 300px;
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  box-shadow: var(--shadow-xl);
  animation: slideUp 0.3s ease;
  border: 1px solid var(--border-color);
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
  margin-bottom: 16px;
}

.companion-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.close-btn {
  background: var(--bg-tertiary);
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 14px;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: var(--border-color);
  color: var(--text-primary);
}

.companion-message {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.05), rgba(184, 134, 11, 0.05));
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  border: 1px solid rgba(212, 175, 55, 0.1);
}

.companion-message p {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.8;
  margin: 0;
}

.companion-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.companion-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: var(--bg-secondary);
  border-radius: 12px;
  text-decoration: none;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.companion-btn:hover {
  background: rgba(212, 175, 55, 0.1);
  color: var(--primary-color);
  transform: translateX(5px);
}

/* 页面过渡动画 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
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
    display: flex;
  }
  
  .mobile-nav {
    display: flex;
  }
  
  .nav-container {
    height: 60px;
  }
  
  .logo {
    font-size: 20px;
  }
  
  .floating-companion {
    bottom: 20px;
    right: 20px;
  }
  
  .companion-content {
    width: 280px;
    right: 0;
  }
  
  .footer {
    padding: 40px 0 20px;
  }
  
  .footer-links {
    gap: 20px;
  }
  
  .footer-quote {
    margin: 0 16px 24px;
  }
}

@media (min-width: 769px) {
  .mobile-nav {
    display: none !important;
  }
  
  .mobile-overlay {
    display: none !important;
  }
}
</style>
