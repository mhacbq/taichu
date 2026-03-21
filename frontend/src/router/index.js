import { createRouter, createWebHistory } from 'vue-router'
import { useSEO, seoConfigs, generateWebsiteSchema } from '../composables/useSEO'

// 首屏关键页面 - 同步加载
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import NotFound from '../views/NotFound.vue'

// 功能页面 - 懒加载（需登录访问）
const Bazi = () => import('../views/Bazi.vue')

// 非首屏页面 - 懒加载
const Tarot = () => import('../views/Tarot.vue')
const TarotShare = () => import('../views/TarotShare.vue')
const Daily = () => import('../views/Daily.vue')
const Profile = () => import('../views/Profile.vue')

const Help = () => import('../views/Help.vue')
const Recharge = () => import('../views/Recharge.vue')
const Vip = () => import('../views/Vip.vue')
const Hehun = () => import('../views/Hehun.vue')
const Liuyao = () => import('../views/Liuyao.vue')
const Qiming = () => import('../views/Qiming.vue')
const YearlyFortune = () => import('../views/YearlyFortune.vue')

// 法律页面
const UserAgreement = () => import('../views/Legal/UserAgreement.vue')
const PrivacyPolicy = () => import('../views/Legal/PrivacyPolicy.vue')

// 管理后台路由 - 懒加载
const AdminLayout = () => import('../layouts/AdminLayout.vue')
const AdminLogin = () => import('../views/admin/Login.vue')
const AdminDashboard = () => import('../views/admin/Dashboard.vue')
const AdminUserList = () => import('../views/admin/UserList.vue')
const AdminUserDetail = () => import('../views/admin/UserDetail.vue')
const AdminUserBehavior = () => import('../views/admin/UserBehavior.vue')
const AdminPointsRecords = () => import('../views/admin/PointsRecords.vue')
const AdminPointsRules = () => import('../views/admin/PointsRules.vue')
const AdminPaymentOrders = () => import('../views/admin/PaymentOrders.vue')
const AdminPaymentAnalysis = () => import('../views/admin/PaymentAnalysis.vue')
const AdminVipPackages = () => import('../views/admin/VipPackages.vue')
const AdminFeedbackList = () => import('../views/admin/FeedbackList.vue')
const AdminFeedbackDetail = () => import('../views/admin/FeedbackDetail.vue')
const AdminSmsConfig = () => import('../views/admin/SmsConfig.vue')
const AdminBaziManage = () => import('../views/admin/BaziManage.vue')
const AdminTarotManage = () => import('../views/admin/TarotManage.vue')
const AdminAlmanacManage = () => import('../views/admin/AlmanacManage.vue')
const AdminDailyManage = () => import('../views/admin/DailyManage.vue')
const AdminShenshaManage = () => import('../views/admin/ShenshaManage.vue')
const AdminSeoManage = () => import('../views/admin/SeoManage.vue')
const AdminTarotCards = () => import('../views/admin/TarotCards.vue')
const AdminQuestionTemplates = () => import('../views/admin/QuestionTemplates.vue')
const AdminOperationLogs = () => import('../views/admin/OperationLogs.vue')
const AdminLoginLogs = () => import('../views/admin/LoginLogs.vue')
const AdminApiLogs = () => import('../views/admin/ApiLogs.vue')
const AdminSystemNotice = () => import('../views/admin/SystemNotice.vue')

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
  // ================== 管理后台路由 ==================
  {
    path: '/maodou/login',
    name: 'AdminLogin',
    component: AdminLogin,
    meta: {
      public: true,
      layout: false
    }
  },
  {
    path: '/maodou',
    component: AdminLayout,
    meta: {
      requiresAdmin: true
    },
    children: [
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: AdminDashboard,
        meta: {
          title: '仪表盘'
        }
      },
      {
        path: 'list',
        name: 'AdminUserList',
        component: AdminUserList,
        meta: {
          title: '用户管理'
        }
      },
      {
        path: 'users/:id',
        name: 'AdminUserDetail',
        component: AdminUserDetail,
        meta: {
          title: '用户详情'
        }
      },
      {
        path: 'users/:id/behavior',
        name: 'AdminUserBehavior',
        component: AdminUserBehavior,
        meta: {
          title: '用户行为'
        }
      },
      {
        path: 'points/records',
        name: 'AdminPointsRecords',
        component: AdminPointsRecords,
        meta: {
          title: '积分记录'
        }
      },
      {
        path: 'points/rules',
        name: 'AdminPointsRules',
        component: AdminPointsRules,
        meta: {
          title: '积分规则'
        }
      },
      {
        path: 'payment/orders',
        name: 'AdminPaymentOrders',
        component: AdminPaymentOrders,
        meta: {
          title: '订单管理'
        }
      },
      {
        path: 'payment/analysis',
        name: 'AdminPaymentAnalysis',
        component: AdminPaymentAnalysis,
        meta: {
          title: '充值分析'
        }
      },
      {
        path: 'payment/vip-packages',
        name: 'AdminVipPackages',
        component: AdminVipPackages,
        meta: {
          title: 'VIP套餐管理'
        }
      },
      {
        path: 'sms/config',
        name: 'AdminSmsConfig',
        component: AdminSmsConfig,
        meta: {
          title: '短信配置'
        }
      },
      {
        path: 'feedback/list',
        name: 'AdminFeedbackList',
        component: AdminFeedbackList,
        meta: {
          title: '反馈管理'
        }
      },
      {
        path: 'feedback/:id',
        name: 'AdminFeedbackDetail',
        component: AdminFeedbackDetail,
        meta: {
          title: '反馈详情'
        }
      },
      {
        path: 'bazi-manage',
        name: 'AdminBaziManage',
        component: AdminBaziManage,
        meta: {
          title: '八字管理'
        }
      },
      {
        path: 'tarot-manage',
        name: 'AdminTarotManage',
        component: AdminTarotManage,
        meta: {
          title: '塔罗管理'
        }
      },
      {
        path: 'almanac',
        name: 'AdminAlmanacManage',
        component: AdminAlmanacManage,
        meta: {
          title: '黄历管理'
        }
      },
      {
        path: 'daily',
        name: 'AdminDailyManage',
        component: AdminDailyManage,
        meta: {
          title: '每日运势管理'
        }
      },
      {
        path: 'shensha',
        name: 'AdminShenshaManage',
        component: AdminShenshaManage,
        meta: {
          title: '神煞管理'
        }
      },
      {
        path: 'seo',
        name: 'AdminSeoManage',
        component: AdminSeoManage,
        meta: {
          title: 'SEO管理'
        }
      },
      {
        path: 'tarot-cards',
        name: 'AdminTarotCards',
        component: AdminTarotCards,
        meta: {
          title: '塔罗牌管理'
        }
      },
      {
        path: 'question-templates',
        name: 'AdminQuestionTemplates',
        component: AdminQuestionTemplates,
        meta: {
          title: '问题模板管理'
        }
      },
      {
        path: 'log/operation',
        name: 'AdminOperationLogs',
        component: AdminOperationLogs,
        meta: {
          title: '操作日志'
        }
      },
      {
        path: 'log/login',
        name: 'AdminLoginLogs',
        component: AdminLoginLogs,
        meta: {
          title: '登录日志'
        }
      },
      {
        path: 'log/api',
        name: 'AdminApiLogs',
        component: AdminApiLogs,
        meta: {
          title: 'API日志'
        }
      },
      {
        path: 'system/notice',
        name: 'AdminSystemNotice',
        component: AdminSystemNotice,
        meta: {
          title: '系统公告'
        }
      }
    ]
  }
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

// 检查用户是否为管理员
function isAdmin() {
  try {
    const userInfo = JSON.parse(localStorage.getItem('userInfo') || '{}')
    // 检查用户角色，支持多种可能的字段名
    return userInfo.role === 'admin' || 
           userInfo.is_admin === true || 
           userInfo.isAdmin === true ||
           userInfo.type === 'admin'
  } catch (e) {
    return false
  }
}

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

/** 管理端访问频率限制（防暴力枚举，5分钟内超过20次拒绝访问） */
const adminAccessLog = { count: 0, resetAt: 0 }
function checkAdminRateLimit() {
  const now = Date.now()
  if (now > adminAccessLog.resetAt) {
    adminAccessLog.count = 0
    adminAccessLog.resetAt = now + 5 * 60 * 1000
  }
  adminAccessLog.count++
  return adminAccessLog.count <= 20
}

router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('token')
  const isAdminPath = to.path.startsWith('/maodou')

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

  // 管理端路由：限制访问频率（防暴力枚举）
  if (isAdminPath && !checkAdminRateLimit()) {
    next({ name: 'Home' })
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

  // 需要管理员权限的页面：未登录 → 登录页，已登录非管理员 → 首页
  if (to.meta.requiresAdmin) {
    if (!isAuthenticated) {
      next({ name: 'Login', query: { redirect: to.fullPath } })
      return
    }
    if (!isAdmin()) {
      // 使用路由跳转到管理后台登录页而不是首页
      next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
      return
    }
  }

  next()
})

// 路由后置守卫 - 设置SEO
router.afterEach((to) => {
  const isAdminRoute = typeof to?.path === 'string' && to.path.startsWith('/maodou')
  if (typeof document !== 'undefined' && document.body) {
    document.body.classList.toggle('route-admin', isAdminRoute)
    document.body.classList.toggle('route-frontend', !isAdminRoute)
  }

  // ⚠️ 管理后台路由：强制 noindex，禁止 canonical，不注入结构化数据
  if (isAdminRoute) {
    useSEO({
      title: '管理后台',
      description: '',
      keywords: '',
      robots: 'noindex, nofollow, noarchive',
      canonical: '',   // 清空 canonical，防止 link rel=canonical 被爬虫收录
    })
    return
  }

  // 获取页面SEO配置
  const seoConfig = to.meta.seo || seoConfigs.home
  
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