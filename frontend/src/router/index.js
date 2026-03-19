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
const Hehun = () => import('../views/Hehun.vue')
const Liuyao = () => import('../views/Liuyao.vue')

// 法律页面
const UserAgreement = () => import('../views/Legal/UserAgreement.vue')
const PrivacyPolicy = () => import('../views/Legal/PrivacyPolicy.vue')

// 管理后台页面（懒加载）
const AdminConfig = () => import('../views/admin/Config.vue')
const AdminAlmanacManage = () => import('../views/admin/AlmanacManage.vue')
const AdminKnowledgeManage = () => import('../views/admin/KnowledgeManage.vue')
const AdminSEOManage = () => import('../views/admin/SEOManage.vue')
const AdminSEOStats = () => import('../views/admin/SEOStats.vue')
const AdminShenshaManage = () => import('../views/admin/ShenshaManage.vue')

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
      requiresAuth: true,
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
    path: '/admin/config',
    name: 'AdminConfig',
    component: AdminConfig,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: '系统配置管理 - 太初命理后台',
        description: '太初命理后台系统配置管理页面',
        keywords: '后台配置,系统配置,管理后台'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '系统配置', url: '/admin/config' }]
    }
  },
  {
    path: '/admin/almanac',
    name: 'AdminAlmanacManage',
    component: AdminAlmanacManage,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: '黄历管理 - 太初命理后台',
        description: '太初命理后台黄历内容管理页面',
        keywords: '黄历管理,后台管理'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '黄历管理', url: '/admin/almanac' }]
    }
  },
  {
    path: '/admin/knowledge',
    name: 'AdminKnowledgeManage',
    component: AdminKnowledgeManage,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: '知识库管理 - 太初命理后台',
        description: '太初命理后台知识库管理页面',
        keywords: '知识库管理,后台管理'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '知识库管理', url: '/admin/knowledge' }]
    }
  },
  {
    path: '/admin/seo',
    name: 'AdminSEOManage',
    component: AdminSEOManage,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: 'SEO管理 - 太初命理后台',
        description: '太初命理后台 SEO 配置管理页面',
        keywords: 'SEO管理,后台管理'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: 'SEO管理', url: '/admin/seo' }]
    }
  },
  {
    path: '/admin/seo/stats',
    name: 'AdminSEOStats',
    component: AdminSEOStats,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: 'SEO统计 - 太初命理后台',
        description: '太初命理后台 SEO 统计页面',
        keywords: 'SEO统计,后台管理'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: 'SEO统计', url: '/admin/seo/stats' }]
    }
  },
  {
    path: '/admin/shensha',
    name: 'AdminShenshaManage',
    component: AdminShenshaManage,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      seo: {
        title: '神煞管理 - 太初命理后台',
        description: '太初命理后台神煞配置管理页面',
        keywords: '神煞管理,后台管理'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '神煞管理', url: '/admin/shensha' }]
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
    path: '/hehun',
    name: 'Hehun',
    component: Hehun,
    meta: { 
      requiresAuth: true,
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
      requiresAuth: true,
      seo: {
        title: '六爻占卜 - 太初命理',
        description: '传统周易六爻占卜，解答心中疑惑',
        keywords: '六爻占卜,周易,算卦,问事,预测'
      },
      breadcrumb: [{ name: '首页', url: '/' }, { name: '六爻占卜', url: '/liuyao' }]
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
    } else {
      return { top: 0 }
    }
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
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
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

  // 需要管理员权限的页面
  if (to.meta.requiresAdmin && !isAdmin()) {
    // 可以重定向到403页面或首页
    next({
      name: 'Home'
    })
    return
  }

  next()
})

// 路由后置守卫 - 设置SEO
router.afterEach((to) => {
  const isAdminRoute = typeof to?.path === 'string' && to.path.startsWith('/admin')
  if (typeof document !== 'undefined' && document.body) {
    document.body.classList.toggle('route-admin', isAdminRoute)
    document.body.classList.toggle('route-frontend', !isAdminRoute)
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
