import { createRouter, createWebHistory } from 'vue-router'
import { useSEO, seoConfigs, generateWebsiteSchema } from '../composables/useSEO'
import Home from '../views/Home.vue'
import Bazi from '../views/Bazi.vue'
import Tarot from '../views/Tarot.vue'
import Daily from '../views/Daily.vue'
import Profile from '../views/Profile.vue'
import Login from '../views/Login.vue'
import Help from '../views/Help.vue'
import NotFound from '../views/NotFound.vue'
import Recharge from '../views/Recharge.vue'
import Hehun from '../views/Hehun.vue'
import Liuyao from '../views/Liuyao.vue'

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

// 路由守卫 - SEO优化
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

  next()
})

// 路由后置守卫 - 设置SEO
router.afterEach((to) => {
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
