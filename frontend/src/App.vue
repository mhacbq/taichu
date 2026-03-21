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
          <router-link to="/daily" class="nav-link">每日运势<span class="nav-free">免费</span></router-link>
          <!-- 更多服务下拉 -->
          <el-dropdown trigger="hover" placement="bottom-start">
            <span class="nav-link nav-link--more">
              更多服务 <el-icon :size="12" style="margin-left:2px"><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu class="nav-more-dropdown">
                <el-dropdown-item>
                  <router-link to="/yearly-fortune" class="nav-more-item">
                    <span class="more-icon">📅</span>
                    <span class="more-info">
                      <strong>流年运势</strong>
                      <span>全年运势解析</span>
                    </span>
                  </router-link>
                </el-dropdown-item>
                <el-dropdown-item>
                  <router-link to="/liuyao" class="nav-more-item">
                    <span class="more-icon">☰</span>
                    <span class="more-info">
                      <strong>六爻占卜</strong>
                      <span>传统周易问事</span>
                    </span>
                  </router-link>
                </el-dropdown-item>
                <el-dropdown-item>
                  <router-link to="/hehun" class="nav-more-item">
                    <span class="more-icon">◎</span>
                    <span class="more-info">
                      <strong>八字合婚</strong>
                      <span>婚姻配对分析</span>
                    </span>
                  </router-link>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>

        <!-- 用户操作区 -->
        <div class="user-actions">
          <template v-if="isLoggedIn">
            <el-dropdown trigger="hover" placement="bottom-end">
              <router-link to="/recharge" class="points-badge" id="tour-recharge" title="点击充值积分">
                <el-icon class="points-icon" :size="16"><Star /></el-icon>
                <span class="points-value">{{ userPoints }}</span>
                <span class="points-unit">积分</span>
              </router-link>
              <template #dropdown>
                <el-dropdown-menu class="points-dropdown">
                  <div class="points-dropdown-header">
                    <span class="points-dropdown-title">当前积分余额</span>
                    <span class="points-dropdown-value">{{ userPoints }}</span>
                  </div>
                  <el-dropdown-item>
                    <router-link to="/recharge" class="points-dropdown-link">
                      <el-icon><Money /></el-icon> 去充值
                    </router-link>
                  </el-dropdown-item>
                  <el-dropdown-item>
                    <router-link to="/profile" class="points-dropdown-link">
                      <el-icon><Calendar /></el-icon> 每日签到领积分
                    </router-link>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-dropdown @command="handleCommand" trigger="click">
              <span class="user-info">
                <span class="avatar">{{ userNickname?.[0] || '用' }}</span>
                <span class="nickname">{{ userNickname || '用户' }}</span>
                <el-icon class="dropdown-arrow"><ArrowDown /></el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">
                    <el-icon class="dropdown-icon" :size="14"><User /></el-icon> <span id="tour-profile">个人中心</span>
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
        <button
          class="mobile-menu-btn"
          @click="toggleMobileMenu"
          :class="{ active: showMobileMenu }"
          :aria-expanded="showMobileMenu ? 'true' : 'false'"
          aria-controls="app-mobile-nav"
          aria-label="打开菜单"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>

      <!-- 移动端导航菜单 -->
      <div id="app-mobile-nav" class="mobile-nav" :class="{ active: showMobileMenu }">
        <div class="mobile-nav-header">
          <span class="mobile-nav-title">菜单</span>
          <button class="mobile-nav-close" @click="closeMobileMenu" aria-label="关闭菜单">
            <el-icon><CloseBold /></el-icon>
          </button>
        </div>
        <div class="mobile-nav-links">
          <router-link to="/" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><House /></el-icon>
            <span>首页</span>
          </router-link>
          <router-link to="/bazi" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><Calendar /></el-icon>
            <span>八字排盘</span>
          </router-link>
          <router-link to="/tarot" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><MagicStick /></el-icon>
            <span>塔罗占卜</span>
          </router-link>
          <router-link to="/liuyao" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><YinYang /></el-icon>
            <span>六爻占卜</span>
          </router-link>
          <router-link to="/hehun" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><Link /></el-icon>
            <span>八字合婚</span>
          </router-link>
          <router-link to="/daily" class="mobile-nav-link" @click="closeMobileMenu">
            <el-icon class="nav-icon" :size="18"><Star /></el-icon>
            <span>每日运势</span>
          </router-link>
        </div>
        <div class="mobile-nav-footer">
          <template v-if="isLoggedIn">
            <div class="mobile-user-info">
              <span class="mobile-avatar">{{ userNickname?.[0] || '用' }}</span>
              <span class="mobile-nickname">{{ userNickname || '用户' }}</span>
              <span class="mobile-points"><el-icon :size="14"><Star /></el-icon> {{ userPoints }}</span>
            </div>
            <a href="#" class="mobile-logout-btn" @click.prevent="handleLogout">
              <el-icon :size="16"><SwitchButton /></el-icon> 退出登录
            </a>
          </template>
          <router-link v-else to="/login" class="mobile-login-btn" @click="closeMobileMenu">
            登录 / 注册
          </router-link>
        </div>
      </div>
      
      <!-- 遮罩层 -->
      <div class="mobile-overlay" :class="{ active: showMobileMenu }" @click="closeMobileMenu"></div>
    </nav>
    
    <main class="main-content" :class="{ 'main-content--companion-safe': shouldReserveCompanionSpace, 'main-content--mobile-nav': true }">
      <ErrorBoundary>
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component v-if="Component" :is="Component" />
            <div v-else class="route-loading-placeholder" aria-live="polite"></div>
          </transition>
        </router-view>
      </ErrorBoundary>
    </main>

    <!-- 移动端底部导航栏 -->
    <nav class="mobile-bottom-nav" aria-label="底部导航">
      <router-link to="/" class="bottom-nav-item" :class="{ active: $route.path === '/' }">
        <el-icon class="bottom-icon"><House /></el-icon>
        <span class="bottom-label">首页</span>
      </router-link>
      <router-link to="/bazi" class="bottom-nav-item bottom-nav-item--primary" :class="{ active: $route.path === '/bazi' }">
        <span class="bottom-primary-icon">☯</span>
        <span class="bottom-primary-label">排盘</span>
      </router-link>
      <router-link to="/liuyao" class="bottom-nav-item" :class="{ active: $route.path === '/liuyao' }">
        <el-icon class="bottom-icon"><YinYang /></el-icon>
        <span class="bottom-label">六爻</span>
      </router-link>
      <router-link :to="isLoggedIn ? '/profile' : '/login'" class="bottom-nav-item" :class="{ active: $route.path === '/profile' }">
        <el-icon class="bottom-icon"><User /></el-icon>
        <span class="bottom-label">{{ isLoggedIn ? '我的' : '登录' }}</span>
      </router-link>
    </nav>


    
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <span class="footer-logo"><YinYangIcon :size="24" style="margin-right: 10px;" /> 太初命理</span>
            <p class="footer-tagline">传承千年智慧，指引人生方向</p>
          </div>
          <div class="footer-quote">
            <p>{{ randomQuote }}</p>
          </div>
          <div class="footer-links">
            <router-link to="/help">帮助中心</router-link>
            <router-link to="/profile">个人中心</router-link>
            <router-link to="/legal/privacy">隐私政策</router-link>
            <router-link to="/legal/agreement">用户协议</router-link>
            <router-link :to="feedbackRoute">意见反馈</router-link>
          </div>
          <div class="footer-divider"></div>
          <p class="footer-copyright">© 2025 太初命理 - 愿你在迷茫中找到方向</p>
        </div>
      </div>
    </footer>
    
    <!-- 浮动心情陪伴组件 -->
    <div v-if="shouldShowCompanion" class="floating-companion" :class="{ expanded: showCompanion }" @click="toggleCompanion">
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
            <el-icon :size="16"><MagicStick /></el-icon>
            <span>塔罗占卜</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- 新用户引导 -->
    <TourGuide ref="tourGuideRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed, h, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPointsBalance } from './api'
import YinYangIcon from './components/YinYangIcon.vue'
import ErrorBoundary from './components/ErrorBoundary.vue'
import TourGuide from './components/TourGuide.vue'
import { useTourGuide } from './composables/useTourGuide'
import {
  ArrowDown,
  Calendar,
  MagicStick,
  Star,
  User,
  SwitchButton,
  Close,
  CloseBold,
  Present,
  Collection,
  Sunrise,
  Link,
  House,
  Money
} from '@element-plus/icons-vue'

// 自定义太极图标组件
const YinYang = {
  render() {
    return h('svg', { viewBox: '0 0 24 24', width: '1em', height: '1em' }, [
      h('circle', { cx: '12', cy: '12', r: '10', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }),
      h('path', { d: 'M12 2a10 10 0 0 1 0 20 5 5 0 0 1 0-10 5 5 0 0 0 0-10z', fill: 'currentColor' }),
      h('circle', { cx: '12', cy: '7', r: '1.5', fill: 'currentColor' }),
      h('circle', { cx: '12', cy: '17', r: '1.5', fill: 'none', stroke: 'currentColor', 'stroke-width': '1' })
    ])
  }
}

const router = useRouter()
const route = useRoute()

// 新用户引导
const tourGuideRef = ref(null)
const { hasCompletedTour, startTour } = useTourGuide()

const isLoggedIn = ref(false)
const userNickname = ref('')
const userPoints = ref(0)
const showMobileMenu = ref(false)
const showCompanion = ref(false)
const scrollLockTop = ref(0)

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

const shouldShowCompanion = computed(() => isLoggedIn.value && !showMobileMenu.value && route.path !== '/login')
const shouldReserveCompanionSpace = computed(() => shouldShowCompanion.value)

const toggleCompanion = () => {
  if (!shouldShowCompanion.value) {
    showCompanion.value = false
    return
  }

  showCompanion.value = !showCompanion.value
}


const lockPageScroll = () => {
  if (typeof document === 'undefined') return

  const body = document.body
  const html = document.documentElement
  scrollLockTop.value = window.scrollY || window.pageYOffset || html.scrollTop || body.scrollTop || 0

  body.style.position = 'fixed'
  body.style.top = `-${scrollLockTop.value}px`
  body.style.left = '0'
  body.style.right = '0'
  body.style.width = '100%'
  body.style.overflow = 'hidden'
  body.style.touchAction = 'none'
  html.style.overflow = 'hidden'
}

const unlockPageScroll = () => {
  if (typeof document === 'undefined') return

  const body = document.body
  const html = document.documentElement
  const offset = scrollLockTop.value || 0
  const wasLocked = body.style.position === 'fixed'

  body.style.position = ''
  body.style.top = ''
  body.style.left = ''
  body.style.right = ''
  body.style.width = ''
  body.style.overflow = ''
  body.style.touchAction = ''
  html.style.overflow = ''

  if (wasLocked) {
    window.scrollTo(0, offset)
  }
}

const closeMobileMenu = () => {
  showMobileMenu.value = false
}

const openMobileMenu = () => {
  showMobileMenu.value = true
}

const toggleMobileMenu = () => {
  if (showMobileMenu.value) {
    closeMobileMenu()
    return
  }

  openMobileMenu()
}

const truncateAppShellMessage = (value, maxLength = 160) => {
  const text = typeof value === 'string' ? value.trim() : ''
  if (!text) {
    return ''
  }

  return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text
}

const logAppShellError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[AppShell]', {
    action,
    error_type: error?.name || typeof error,
    message: truncateAppShellMessage(typeof error?.message === 'string' ? error.message : String(error ?? '')) || 'unknown',
    ...extra
  })
}

const readStoredUserInfo = () => {
  const rawValue = localStorage.getItem('userInfo')
  if (!rawValue) {
    return null
  }

  try {
    const parsedValue = JSON.parse(rawValue)
    return parsedValue && typeof parsedValue === 'object' ? parsedValue : null
  } catch (error) {
    logAppShellError('parse_user_info_failed', error)
    return null
  }
}

const clearStoredUserSession = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('userInfo')
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

      // 首次登录触发引导（延迟执行确保DOM渲染完成）
      if (!hasCompletedTour.value && route.path === '/') {
        nextTick(() => {
          setTimeout(() => {
            startTour()
          }, 1000)
        })
      }
    } catch (e) {
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

const syncStoredPoints = (balance) => {
  const parsedBalance = Number(balance)
  if (!Number.isFinite(parsedBalance)) {
    return false
  }

  userPoints.value = parsedBalance
  const userInfo = readStoredUserInfo() || {}
  userInfo.points = parsedBalance
  localStorage.setItem('userInfo', JSON.stringify(userInfo))
  return true
}

// 刷新积分
const refreshPoints = async () => {
  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      syncStoredPoints(response.data.balance)
    }
  } catch (error) {
    logAppShellError('refresh_points_failed', error)
  }
}

const handlePointsUpdated = async (event) => {
  if (!isLoggedIn.value) {
    return
  }

  const eventBalance = Number(event?.detail?.balance)
  if (syncStoredPoints(eventBalance)) {
    return
  }

  await refreshPoints()
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
  clearStoredUserSession()
  isLoggedIn.value = false
  ElMessage.success('已退出登录')
  router.push('/')
  closeMobileMenu()
}


const feedbackRoute = {
  path: '/profile',
  hash: '#feedback-card'
}

watch(showMobileMenu, (visible) => {
  if (visible) {
    lockPageScroll()
  } else {
    unlockPageScroll()
  }
})

watch(shouldShowCompanion, (visible) => {
  if (!visible) {
    showCompanion.value = false
  }
})

// 监听路由变化
watch(() => route.path, () => {
  checkLoginStatus()
  closeMobileMenu()
  showCompanion.value = false
})

// 监听 storage 变化，同步多标签页登录状态
const handleStorageChange = (e) => {
  if (e.key === 'token' || e.key === 'userInfo') {
    checkLoginStatus()
  }
}

onMounted(() => {
  checkLoginStatus()
  window.addEventListener('points-updated', handlePointsUpdated)
  window.addEventListener('storage', handleStorageChange)
  // 滚动时导航栏加深
  const navbar = document.querySelector('.navbar')
  const handleScroll = () => {
    if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 20)
  }
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('points-updated', handlePointsUpdated)
  window.removeEventListener('storage', handleStorageChange)
  unlockPageScroll()
})

</script>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* 导航栏 - 白色玻璃态 */
.navbar {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  padding: 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid rgba(212, 175, 55, 0.2);
  box-shadow: 0 2px 12px rgba(212, 175, 55, 0.08);
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
  font-size: var(--font-small);
  font-weight: var(--weight-medium);
  padding: 10px 16px;
  border-radius: var(--radius-btn);
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}





.nav-link:hover {
  color: var(--primary-color);
  background: var(--surface-hover);
}

.nav-link.router-link-active {
  color: var(--primary-color);
  background: var(--surface-strong);
  box-shadow: inset 0 0 0 1px var(--primary-light-20);
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
  border: 1px solid transparent;
  transition: all 0.3s ease;
  min-width: 140px;
}

.user-info:hover {
  background: var(--bg-card);
  border-color: var(--primary-light-20);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
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
  color: var(--text-accent-contrast);
  font-weight: 600;
  flex-shrink: 0;
}

.nickname {
  font-size: var(--font-small);
  color: var(--text-primary);
  font-weight: var(--weight-medium);
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.dropdown-arrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: var(--text-muted);
  transition: transform 0.3s ease;
}

.user-info:hover .dropdown-arrow {
  transform: translateY(1px);
}

.dropdown-icon {
  margin-right: 8px;
}

.login-btn {
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  padding: 10px 24px;
  border-radius: 25px;
  text-decoration: none;
  font-size: var(--font-small);
  font-weight: var(--weight-semibold);
  border: 1px solid var(--primary-light-20);
  transition: all 0.3s ease;
  box-shadow: 0 8px 22px rgba(var(--primary-rgb), 0.18);
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(var(--primary-rgb), 0.24);
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
  display: none;
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  cursor: pointer;
  padding: 10px;
  width: 44px;
  height: 44px;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 6px;
  border-radius: 12px;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.mobile-menu-btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: var(--primary-color);
  opacity: 0.1;
  transform: translate(-50%, -50%);
  transition: width 0.4s ease, height 0.4s ease;
}

.mobile-menu-btn:active::before {
  width: 100px;
  height: 100px;
}

.mobile-menu-btn:hover {
  border-color: var(--primary-light-20);
  box-shadow: 0 12px 22px rgba(var(--primary-rgb), 0.12);
  transform: translateY(-2px);
}

.mobile-menu-btn:active {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
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
  width: min(84vw, 340px);
  height: 100vh;
  background: var(--bg-overlay);
  backdrop-filter: blur(28px);
  -webkit-backdrop-filter: blur(28px);
  z-index: 1001;
  flex-direction: column;
  transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: -20px 0 48px rgba(15, 23, 42, 0.16);
  border-left: 1px solid var(--border-light);
  overscroll-behavior: contain;
}

.mobile-nav.active {
  right: 0;
}

.mobile-nav-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30px 24px 24px;
  border-bottom: 1px solid var(--border-light);
}

.mobile-nav-title {
  font-size: var(--font-h4);
  font-weight: var(--weight-bold);
  color: var(--primary-color);
  letter-spacing: var(--tracking-normal);
}

.mobile-nav-close {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-light);
  color: var(--text-secondary);
  cursor: pointer;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.mobile-nav-close:hover {
  background: var(--surface-hover);
  color: var(--primary-color);
  border-color: var(--primary-light-20);
  transform: rotate(90deg);
}

.mobile-nav-links {
  flex: 1;
  padding: 20px 0;
  overflow-y: auto;
}

.mobile-nav-link {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--text-secondary);
  text-decoration: none;
  padding: 16px 28px;
  font-size: var(--font-body);
  font-weight: var(--weight-medium);
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}


.mobile-nav-link > span {
  flex: 1;
}




.mobile-nav-link:hover,
.mobile-nav-link.router-link-active {
  color: var(--primary-color);
  background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.12), transparent 85%);
  border-left-color: var(--primary-color);
  padding-left: 34px;
}

.nav-icon {
  font-size: 20px;
  color: var(--primary-color);
}

.mobile-nav-footer {
  padding: 28px 24px;
  border-top: 1px solid var(--border-light);
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.03), rgba(var(--primary-rgb), 0.08));
}

.mobile-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}

.mobile-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-accent-contrast);
  font-weight: 600;
  font-size: 16px;
  box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.28);
}

.mobile-nickname {
  flex: 1;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 15px;
}

.mobile-points {
  background: rgba(var(--primary-rgb), 0.1);
  padding: 6px 12px;
  border-radius: 20px;
  color: var(--primary-color);
  font-weight: 700;
  font-size: 12px;
  border: 1px solid rgba(var(--primary-rgb), 0.18);
}

.mobile-login-btn,
.mobile-logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  transition: all 0.3s ease;
  min-height: 48px;
}

.mobile-login-btn {
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.2);
}

.mobile-logout-btn {
  background: var(--bg-card);
  color: var(--text-secondary);
  border: 1px solid var(--border-light);
}

.mobile-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 999;
  opacity: 0;
  transition: all 0.4s ease;
  touch-action: none;
}

.mobile-overlay.active {
  display: block;
  opacity: 1;
}

/* 主要内容区 */
.main-content {
  flex: 1;
}

.route-loading-placeholder {
  min-height: 120px;
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
  background: rgba(184, 134, 11, 0.05);
  border-radius: 12px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
  border: 1px solid rgba(184, 134, 11, 0.1);
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
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--primary-color) 0%, #f0e68c 50%, var(--primary-color) 100%);
  background-size: 200% 200%;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 
    0 4px 20px rgba(184, 134, 11, 0.4),
    0 0 0 3px rgba(255, 255, 255, 0.3),
    inset 0 2px 4px rgba(255, 255, 255, 0.3),
    inset 0 -2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  animation: gradient-shift 4s ease infinite;
}

.companion-avatar:hover {
  transform: scale(1.12) translateY(-4px);
  box-shadow: 
    0 12px 32px rgba(184, 134, 11, 0.5),
    0 0 0 4px rgba(255, 255, 255, 0.5),
    inset 0 2px 4px rgba(255, 255, 255, 0.4),
    inset 0 -2px 4px rgba(0, 0, 0, 0.1);
}

.companion-avatar:active {
  transform: scale(1.05) translateY(-2px);
  box-shadow: 
    0 6px 20px rgba(184, 134, 11, 0.4),
    0 0 0 3px rgba(255, 255, 255, 0.4),
    inset 0 1px 3px rgba(255, 255, 255, 0.3),
    inset 0 -1px 3px rgba(0, 0, 0, 0.15);
}

.companion-icon {
  font-size: 32px;
  color: white;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 2;
}

.companion-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 2px solid var(--primary-color);
  animation: pulse-ring 2s ease-out infinite;
  pointer-events: none;
}

@keyframes gradient-shift {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.6);
    opacity: 0;
  }
}

.companion-content {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 320px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  backdrop-filter: blur(12px);
  border-radius: 24px;
  padding: 28px;
  box-shadow: 
    0 20px 60px rgba(0, 0, 0, 0.15),
    0 8px 32px rgba(184, 134, 11, 0.12);
  animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: 2px solid var(--border-light);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes checkmark {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

.companion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--border-light);
}

.companion-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 8px;
}

.close-btn {
  background: linear-gradient(135deg, var(--bg-tertiary), var(--bg-secondary));
  border: 2px solid var(--border-light);
  color: var(--text-muted);
  cursor: pointer;
  font-size: 14px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.close-btn:hover {
  background: linear-gradient(135deg, var(--border-color), var(--bg-tertiary));
  border-color: var(--primary-color);
  color: var(--primary-color);
  transform: scale(1.1);
}

.companion-message {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.04));
  border-radius: 18px;
  padding: 20px;
  margin-bottom: 20px;
  border: 2px solid rgba(var(--primary-rgb), 0.15);
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
  gap: 12px;
}

.companion-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  border: 2px solid var(--border-light);
  border-radius: 16px;
  text-decoration: none;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.companion-btn:hover {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.12), rgba(var(--primary-rgb), 0.06));
  border-color: rgba(var(--primary-rgb), 0.3);
  color: var(--primary-color);
  transform: translateX(6px);
  box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.12);
}

/* 全局背景：古典云纹纹理 */
body {
  background-color: #FFFFFF;
  background-image:
    radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(212, 175, 55, 0.02) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.015) 0%, transparent 60%),
    repeating-linear-gradient(
      45deg,
      transparent,
      transparent 35px,
      rgba(212, 175, 55, 0.01) 35px,
      rgba(212, 175, 55, 0.01) 36px
    );
  background-attachment: fixed;
  min-height: 100vh;
}

/* 页面过渡动画 */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.98);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(1.02);
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
    bottom: calc(60px + env(safe-area-inset-bottom, 0px) + 12px);
    right: 20px;
  }

  .companion-avatar {
    width: 56px;
    height: 56px;
  }

  .companion-icon {
    font-size: 28px;
  }
  
  .companion-content {
    width: 280px;
    right: 0;
    bottom: 75px;
    padding: 24px;
  }

  .companion-header {
    margin-bottom: 16px;
    padding-bottom: 14px;
  }

  .companion-title {
    font-size: 15px;
  }

  .close-btn {
    width: 34px;
    height: 34px;
  }

  .companion-message {
    padding: 16px;
    margin-bottom: 16px;
  }

  .companion-actions {
    gap: 10px;
  }

  .companion-btn {
    padding: 12px 14px;
    font-size: 13px;
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

/* 2026-03 UI polish: global shell refresh */
.navbar {
  padding: 0;
  background: rgba(8, 8, 20, 0.88);
  backdrop-filter: blur(20px) saturate(160%);
  -webkit-backdrop-filter: blur(20px) saturate(160%);
  border-bottom: 1px solid rgba(184, 134, 11, 0.2);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
  transition: background 0.3s ease, border-color 0.3s ease;
}

.navbar.scrolled {
  background: rgba(6, 6, 16, 0.97);
  border-bottom-color: rgba(212, 175, 55, 0.35);
}

.nav-container {
  min-height: 68px;
  height: auto;
  padding: 12px 20px;
  border-radius: 0;
  background: transparent;
  border: none;
  box-shadow: none;
}

.nav-links {
  gap: 4px;
  padding: 0;
  border-radius: 0;
  background: transparent;
  border: none;
}

.nav-link {
  color: rgba(212, 175, 55, 0.75);
  font-size: 14px;
  font-weight: 500;
  padding: 8px 14px;
  border-radius: 8px;
  transition: color 0.2s ease, background 0.2s ease;
  letter-spacing: 0.02em;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
}

.nav-link:hover {
  color: #D4AF37;
  background: rgba(212, 175, 55, 0.1);
  box-shadow: none;
}

.nav-link.router-link-active {
  color: #D4AF37;
  background: rgba(212, 175, 55, 0.14);
  box-shadow: inset 0 0 0 1px rgba(212, 175, 55, 0.2);
  text-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
}


.nav-free {
  font-size: 10px;
  background: rgba(76, 175, 130, 0.18);
  color: rgba(76, 175, 130, 0.85);
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid rgba(76, 175, 130, 0.2);
  margin-left: 2px;
}

/* 更多服务下拉 */
.nav-link--more {
  user-select: none;
}

.nav-more-dropdown .el-dropdown-menu__item {
  padding: 0 !important;
}

.nav-more-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  text-decoration: none;
  color: inherit;
  width: 100%;
  min-width: 180px;
}

.more-icon {
  font-size: 20px;
  color: #D4AF37;
  width: 28px;
  text-align: center;
  flex-shrink: 0;
}

.more-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.more-info strong {
  font-size: 14px;
  font-weight: 600;
}

.more-info span {
  font-size: 12px;
  opacity: 0.6;
}

/* Logo：金色渐变文字 */
.logo {
  background: linear-gradient(135deg, #F0D060 0%, #D4AF37 50%, #B8860B 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 20px;
}

.logo:hover {
  opacity: 0.85;
  color: unset;
}

.logo-icon {
  -webkit-text-fill-color: #D4AF37;
  color: #D4AF37;
}

/* 积分徽章：可点击，链接到充值 */
.points-badge {
  display: flex;
  align-items: center;
  gap: 5px;
  background: rgba(184, 134, 11, 0.12);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #D4AF37;
  border: 1px solid rgba(184, 134, 11, 0.25);
  text-decoration: none;
  transition: all 0.2s ease;
  cursor: pointer;
}

.points-badge:hover {
  background: rgba(184, 134, 11, 0.2);
  border-color: rgba(212, 175, 55, 0.4);
  transform: translateY(-1px);
}

.points-unit {
  font-size: 11px;
  opacity: 0.7;
}

.points-dropdown {
  padding: 0;
  min-width: 200px;
}

.points-dropdown-header {
  padding: 16px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(184, 134, 11, 0.05));
  border-bottom: 1px solid rgba(184, 134, 11, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.points-dropdown-title {
  font-size: 12px;
  color: var(--text-secondary);
}

.points-dropdown-value {
  font-size: 24px;
  font-weight: bold;
  color: var(--primary-color);
}

.points-dropdown-link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  color: var(--text-primary);
  text-decoration: none;
  transition: all 0.2s ease;
}

.points-dropdown-link:hover {
  color: var(--primary-color);
  background: rgba(212, 175, 55, 0.05);
}

.user-info {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: none;
}

.user-info:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(212, 175, 55, 0.25);
  box-shadow: none;
}

.nickname {
  color: rgba(255, 255, 255, 0.85);
}

.login-btn {
  border-radius: 20px;
  background: linear-gradient(135deg, #D4AF37, #B8860B);
  color: #0a0a1a !important;
  border: none;
  box-shadow: 0 4px 16px rgba(212, 175, 55, 0.28);
}

.login-btn:hover {
  box-shadow: 0 6px 24px rgba(212, 175, 55, 0.4);
  transform: translateY(-1px);
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: none;
}

.mobile-menu-btn span {
  background: rgba(212, 175, 55, 0.9);
}

/* 移动端侧滑导航 */
.mobile-nav {
  background: rgba(8, 8, 20, 0.97);
  border-left: 1px solid rgba(184, 134, 11, 0.2);
  box-shadow: -20px 0 48px rgba(0, 0, 0, 0.5);
}

.mobile-nav-title {
  color: #D4AF37;
}

.mobile-nav-link {
  color: rgba(212, 175, 55, 0.7);
}

.mobile-nav-link:hover,
.mobile-nav-link.router-link-active {
  color: #D4AF37;
  background: linear-gradient(90deg, rgba(212, 175, 55, 0.12), transparent 85%);
  border-left-color: #D4AF37;
  padding-left: 34px;
}

.mobile-overlay {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.main-content--companion-safe {
  padding-bottom: 112px;
}

/* ── 移动端底部导航栏 ── */
.mobile-bottom-nav {
  display: none;
}

@media (max-width: 768px) {
  .mobile-bottom-nav {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    padding-bottom: env(safe-area-inset-bottom, 0px);
    background: rgba(8, 8, 20, 0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid rgba(184, 134, 11, 0.25);
    box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.5);
    justify-content: space-around;
    align-items: center;
    z-index: 1000;
  }

  .bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 6px 14px;
    color: rgba(212, 175, 55, 0.45);
    text-decoration: none;
    transition: color 0.2s ease;
    min-width: 52px;
    flex: 1;
  }

  .bottom-icon {
    font-size: 20px;
    transition: transform 0.2s ease, filter 0.2s ease;
  }

  .bottom-label {
    font-size: 10px;
    letter-spacing: 0.02em;
  }

  .bottom-nav-item.active {
    color: #D4AF37;
  }

  .bottom-nav-item.active .bottom-icon {
    transform: translateY(-2px);
    filter: drop-shadow(0 0 6px rgba(212, 175, 55, 0.5));
  }

  /* 中间主功能按钮（八字排盘） */
  .bottom-nav-item--primary {
    position: relative;
    top: -10px;
    background: linear-gradient(135deg, #D4AF37, #B8860B);
    border-radius: 50%;
    width: 52px;
    height: 52px;
    min-width: 52px;
    max-width: 52px;
    flex: none;
    padding: 0;
    color: #0a0a1a;
    box-shadow: 0 4px 16px rgba(212, 175, 55, 0.45);
    font-size: 22px;
    border: 2px solid rgba(240, 208, 96, 0.3);
  }

  .bottom-primary-icon {
    font-size: 22px;
    line-height: 1;
  }

  .bottom-primary-label {
    font-size: 9px;
    color: #0a0a1a;
    font-weight: 600;
    margin-top: 1px;
  }

  .bottom-nav-item--primary.active {
    color: #0a0a1a;
    box-shadow: 0 6px 24px rgba(212, 175, 55, 0.6);
  }

  /* 页面内容底部留出导航栏高度 */
  .main-content--mobile-nav {
    padding-bottom: calc(60px + env(safe-area-inset-bottom, 0px));
  }
}

/* Footer 主题适配风格 */
.footer {
  padding: 60px 0 30px;
  background: var(--bg-secondary, #f9f6f0);
  border-top: 1px solid var(--border-color);
  position: relative;
}

.footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 40px;
  background-image: url('/patterns/wave-decor.svg');
  background-repeat: repeat-x;
  background-size: contain;
  background-position: top;
}

.footer-content {
  padding: 32px;
}

.footer-logo {
  color: var(--primary-color);
}

.footer-tagline {
  color: var(--text-tertiary);
}

.footer-quote {
  background: rgba(184, 134, 11, 0.05);
  border-color: rgba(184, 134, 11, 0.12);
}

.footer-links a {
  color: var(--text-secondary);
  min-height: 36px;
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  background: var(--bg-secondary, #f9f6f0);
  border: 1px solid var(--border-color);
  transition: color 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.footer-links a:hover {
  color: var(--primary-color);
  border-color: rgba(184, 134, 11, 0.3);
  background: rgba(184, 134, 11, 0.08);
}

.footer-divider {
  max-width: 100%;
  background: linear-gradient(90deg, transparent, var(--border-color), transparent);
}

.footer-copyright {
  color: var(--text-muted);
}

/* 浮动陪伴组件 */
.floating-companion {
  bottom: 24px;
  right: 24px;
}

.companion-avatar {
  border: 1px solid rgba(184, 134, 11, 0.25);
  box-shadow: 0 8px 28px rgba(184, 134, 11, 0.25), 0 4px 16px rgba(0, 0, 0, 0.3);
}

.companion-content {
  width: 300px;
  background: linear-gradient(160deg, rgba(255, 252, 244, 0.99), rgba(255, 248, 230, 0.97));
  border: 1px solid rgba(212, 175, 55, 0.22);
  box-shadow: 0 12px 40px rgba(184, 134, 11, 0.12), 0 4px 16px rgba(0, 0, 0, 0.08);
}

.companion-title {
  color: #5c3d0e;
}

.companion-message {
  background: rgba(212, 175, 55, 0.08);
  border-color: rgba(212, 175, 55, 0.18);
}

.companion-message p {
  color: #6b4c1e;
}

.companion-btn {
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(212, 175, 55, 0.18);
  color: #7a5520;
}

.companion-btn:hover {
  background: rgba(212, 175, 55, 0.12);
  border-color: rgba(212, 175, 55, 0.35);
  color: #8c641f;
  transform: translateX(3px);
}

.close-btn {
  background: rgba(212, 175, 55, 0.1);
  color: #8c641f;
}

.close-btn:hover {
  background: rgba(212, 175, 55, 0.2);
  color: #5c3d0e;
}

@media (max-width: 768px) {
  .navbar {
    padding: 0;
  }

  .nav-container {
    min-height: 56px;
    padding: 8px 14px;
    border-radius: 0;
  }

  .main-content--companion-safe {
    padding-bottom: 96px;
  }

  .footer {
    padding: 40px 0 24px;
  }

  .footer-content {
    padding: 22px 18px;
    border-radius: 20px;
  }

  .companion-content {
    width: min(300px, calc(100vw - 24px));
  }
}
</style>