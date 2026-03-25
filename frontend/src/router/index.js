import { createRouter, createWebHistory } from 'vue-router'
import { useSEO, seoConfigs, generateWebsiteSchema } from '../composables/useSEO'
import { getActiveSeoConfigs } from '../api/index'

// 首屏关键页面 - 同步加载
import Home from '../views/Home/index.vue'
import Login from '../views/Login/index.vue'
import NotFound from '../views/NotFound/index.vue'

// 功能页面 - 懒加载（需登录访问）
const Bazi = () => import('../views/Bazi/index.vue')

// 非首屏页面 - 懒加载
const Tarot = () => import('../views/Tarot/index.vue')
const TarotShare = () => import('../views/TarotShare/index.vue')
const Daily = () => import('../views/Daily/index.vue')
const Profile = () => import('../views/Profile/index.vue')

const Help = () => import('../views/Help/index.vue')
const Recharge = () => import('../views/Recharge/index.vue')
const Vip = () => import('../views/Vip/index.vue')
const Hehun = () => import('../views/Hehun/index.vue')
const Liuyao = () => import('../views/Liuyao/index.vue')
const Qiming = () => import('../views/Qiming/index.vue')
const YearlyFortune = () => import('../views/YearlyFortune/index.vue')

// 法律页面
const UserAgreement = () => import('../views/Legal/UserAgreement.vue')
const PrivacyPolicy = () => import('../views/Legal/PrivacyPolicy.vue')

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { 
      public: true,
      seo: seoConfigs.home,
      breadcrumb: [{ name: '首页', url: '/' }]
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { 
      public: true,
      seo: seoConfigs.login,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '登录', url: '/login' }]
    }
  },
  {
    path: '/bazi',
    name: 'Bazi',
    component: Bazi,
    meta: { 
      requiresAuth: true,
      seo: seoConfigs.bazi,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '八字排盘', url: '/bazi' }]
    }
  },
  {
    path: '/tarot',
    name: 'Tarot',
    component: Tarot,
    meta: { 
      public: true,
      seo: seoConfigs.tarot,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '塔罗占卜', url: '/tarot' }]
    }
  },
  {
    path: '/tarot/share/:code',
    name: 'TarotShare',
    component: TarotShare,
    meta: {
      public: true,
      seo: {
        title: '塔罗分享结果 - 太初命理',
        description: '查看来自太初命理的塔罗占卜分享结果与详细解读',
        keywords: '塔罗分享,塔罗占卜,塔罗解读'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '塔罗分享', url: '/tarot/share' }]
    }
  },
  {
    path: '/daily',
    name: 'Daily',
    component: Daily,
    meta: { 
      public: true,
      seo: seoConfigs.daily,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '每日运势', url: '/daily' }]
    }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { 
      requiresAuth: true,
      seo: seoConfigs.profile,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '个人中心', url: '/profile' }]
    }
  },
  {
    path: '/recharge',
    name: 'Recharge',
    component: Recharge,
    meta: { 
      requiresAuth: true,
      seo: seoConfigs.recharge,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '积分充值', url: '/recharge' }]
    }
  },
  {
    path: '/vip',
    name: 'Vip',
    component: Vip,
    meta: { 
      requiresAuth: true,
      seo: {
        title: 'VIP会员 - 太初命理',
        description: '开通太初命理VIP会员，享受更多专属权益',
        keywords: 'VIP,会员,特权,专属服务'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: 'VIP会员', url: '/vip' }]
    }
  },
  {
    path: '/hehun',
    name: 'Hehun',
    component: Hehun,
    meta: { 
      public: true,
      seo: {
        title: '八字合婚 - 太初命理',
        description: '通过双方八字分析婚姻匹配度，了解缘分深浅',
        keywords: '八字合婚,婚姻配对,八字匹配,缘分分析'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '八字合婚', url: '/hehun' }]
    }
  },
  {
    path: '/liuyao',
    name: 'Liuyao',
    component: Liuyao,
    meta: { 
      public: true,
      seo: {
        title: '六爻占卜 - 太初命理',
        description: '传统周易六爻占卜，解答心中疑惑',
        keywords: '六爻占卜,周易,算卦,问事,预测'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '六爻占卜', url: '/liuyao' }]
    }
  },
  {
    path: '/qiming',
    name: 'Qiming',
    component: Qiming,
    meta: { 
      public: true,
      seo: {
        title: '取名建议 - 太初命理',
        description: '结合生辰八字与五行，由AI为新生儿推荐寓意美好的名字',
        keywords: '取名,起名,八字取名,五行取名,宝宝取名'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '取名建议', url: '/qiming' }]
    }
  },
  {
    path: '/yearly-fortune',
    name: 'YearlyFortune',
    component: YearlyFortune,
    meta: {
      public: true,
      seo: {
        title: '流年运势 - 太初命理',
        description: '结合个人八字，AI深度解析全年运势，提供每月吉凶提醒与开运建议',
        keywords: '流年运势,全年运势,运势分析,月运,开运建议'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '流年运势', url: '/yearly-fortune' }]
    }
  },
  {
    path: '/help',
    name: 'Help',
    component: Help,
    meta: { 
      public: true,
      seo: seoConfigs.help,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '帮助中心', url: '/help' }]
    }
  },
  {
    path: '/legal/agreement',
    name: 'UserAgreement',
    component: UserAgreement,
    meta: {
      public: true,
      seo: {
        title: '用户协议 - 太初命理',
        description: '太初命理用户服务协议',
        keywords: '用户协议,服务条款'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '用户协议', url: '/legal/agreement' }]
    }
  },
  {
    path: '/legal/privacy',
    name: 'PrivacyPolicy',
    component: PrivacyPolicy,
    meta: {
      public: true,
      seo: {
        title: '隐私政策 - 太初命理',
        description: '太初命理隐私政策说明',
        keywords: '隐私政策,隐私保护'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '隐私政策', url: '/legal/privacy' }]
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: { 
      public: true,
      seo: seoConfigs.notFound,
      breadcrumb: [{ name: '首页', url: '/' }, { name: '页面未找到', url: '/404' }]
    }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }

    if (to.hash) {
      return {
        el: to.hash,
        top: 96,
        behavior: 'smooth'
      }
    }

    return { top: 0 }
  }
})

const dynamicImportErrorPattern = /Failed to fetch dynamically imported module|Importing a module script failed|Loading chunk [\d]+ failed/i

const truncateRouterErrorMessage = (message) => {
  const normalizedMessage = typeof message === 'string' ? message.trim() : ''
  if (!normalizedMessage) {
    return 'unknown'
  }

  return normalizedMessage.length > 160 ? `${normalizedMessage.slice(0, 157)}...` : normalizedMessage
}

const resolveRouteTarget = (to) => {
  const candidate = typeof to?.fullPath === 'string' && to.fullPath
    ? to.fullPath
    : (typeof to?.path === 'string' ? to.path : '')

  return candidate.split('?')[0]
}

const collectMatchedRouteInfo = (to) => (Array.isArray(to?.matched)
  ? to.matched.map((record) => ({
      name: record.name,
      path: record.path,
      hasComponent: !!record.components?.default || !!record.component,
    }))
  : [])

const reportRouterLoadError = (error, to) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[RouterLoad]', {
    target: resolveRouteTarget(to),
    matched: collectMatchedRouteInfo(to),
    error_type: error?.name || typeof error,
    message: truncateRouterErrorMessage(error?.message)
  })
}

router.onError((error, to) => {
  if (!dynamicImportErrorPattern.test(String(error?.message || ''))) {
    reportRouterLoadError(error, to)
    return
  }

  const targetPath = typeof to?.fullPath === 'string' && to.fullPath ? to.fullPath : window.location.pathname
  if (window.location.pathname !== targetPath) {
    window.location.assign(targetPath)
    return
  }

  window.location.reload()
})

// 路由守卫 - SEO优化和权限验证
/**
 * 解析 JWT payload（不验签，仅读取 exp 字段做前端过期检测）
 * 真正的验签在后端 AdminAuth 中间件完成
 */
function parseJwtPayload(token) {
  try {
    const parts = token.split('.')
    if (parts.length !== 3) return null
    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')))
    return payload
  } catch {
    return null
  }
}

/** 检查 Token 是否已过期（前端快速判断，减少无效请求） */
function isTokenExpired(token) {
  const payload = parseJwtPayload(token)
  if (!payload || !payload.exp) return false // 没有 exp 字段则交给后端判断
  return Date.now() / 1000 > payload.exp
}

router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('token')

  // 检测 Token 格式（防止 localStorage 被注入伪造数据）
  if (token && (typeof token !== 'string' || token.split('.').length !== 3)) {
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    token = null
  }

  // 前端检测 Token 是否过期（仅快速判断，真正鉴权在后端）
  if (token && isTokenExpired(token)) {
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    token = null
  }

  const isAuthenticated = !!token

  // 已登录用户访问登录页，重定向到首页
  if (to.name === 'Login' && isAuthenticated) {
    next('/')
    return
  }

  // 需要登录的页面
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({
      name: 'Login',
      query: { redirect: to.fullPath }
    })
    return
  }

  next()
})

// ==================== SEO配置缓存 ====================
// 数据库SEO配置缓存（启动时加载一次，以route_path为key）
let dbSeoConfigCache = null
let dbSeoConfigLoading = false

async function loadDbSeoConfigs() {
  if (dbSeoConfigCache !== null || dbSeoConfigLoading) return
  dbSeoConfigLoading = true
  try {
    const res = await getActiveSeoConfigs()
    if (res.data?.code === 0) {
      dbSeoConfigCache = res.data.data || {}
    }
  } catch (e) {
    // 加载失败不影响正常使用，回退到静态配置
    dbSeoConfigCache = {}
  } finally {
    dbSeoConfigLoading = false
  }
}

/**
 * 根据路由路径从数据库缓存中获取SEO配置
 * 返回null则表示无数据库配置，使用静态兜底
 */
function getDbSeoConfig(routePath) {
  if (!dbSeoConfigCache) return null
  // 精确匹配路由路径
  const config = dbSeoConfigCache[routePath]
  if (!config) return null
  
  // 转换为useSEO需要的格式
  return {
    title: config.title,
    description: config.description,
    keywords: config.keywords ? config.keywords.split(',') : [],
    image: config.og_image || '/images/og-default.jpg',
    robots: config.robots || 'index,follow',
  }
}

// 路由创建后立即开始加载数据库SEO配置
loadDbSeoConfigs()

// 路由后置守卫 - 设置SEO
router.afterEach((to) => {
  // 获取页面SEO配置：数据库优先 → 路由meta静态配置 → 默认首页配置
  const dbConfig = getDbSeoConfig(to.path)
  const seoConfig = dbConfig || to.meta.seo || seoConfigs.home
  
  // 添加当前URL
  const seoOptions = {
    ...seoConfig,
    url: to.fullPath,
    canonical: `${window.location.origin}${to.fullPath}`
  }
  
  // 首页添加网站结构化数据
  if (to.name === 'Home') {
    seoOptions.structuredData = generateWebsiteSchema()
  }
  
  // 应用SEO配置
  useSEO(seoOptions)
})

export default router