import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

NProgress.configure({ showSpinner: false })

export const constantRoutes = [
  {
    path: '/login',
    component: () => import('@/views/login/index.vue'),
    hidden: true
  },
  {
    path: '/404',
    component: () => import('@/views/error/404.vue'),
    hidden: true
  }
]

export const asyncRoutes = [
  {
    path: '/',
    component: () => import('@/layout/index.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘', icon: 'HomeFilled' }
      }
    ]
  },
  {
    path: '/user',
    component: () => import('@/layout/index.vue'),
    redirect: '/user/list',
    meta: { title: '用户管理', icon: 'UserFilled' },
    children: [
      {
        path: 'list',
        name: 'UserList',
        component: () => import('@/views/user/list.vue'),
        meta: { title: '用户列表' }
      },
      {
        path: 'detail/:id',
        name: 'UserDetail',
        component: () => import('@/views/user/detail.vue'),
        meta: { title: '用户详情' },
        hidden: true
      },
      {
        path: 'behavior',
        name: 'UserBehavior',
        component: () => import('@/views/user/behavior.vue'),
        meta: { title: '行为日志' }
      }
    ]
  },
  {
    path: '/content',
    component: () => import('@/layout/index.vue'),
    redirect: '/content/pages',
    meta: { title: '内容管理', icon: 'Document' },
    children: [
      {
        path: 'pages',
        name: 'Pages',
        component: () => import('@/views/content/pages.vue'),
        meta: { title: '页面管理' }
      },
      {
        path: 'pages/:id/history',
        name: 'PageHistory',
        component: () => import('@/views/content/page-history.vue'),
        meta: { title: '页面历史', hidden: true }
      },
      {
        path: 'bazi',
        name: 'BaziRecords',
        component: () => import('@/views/content/bazi.vue'),
        meta: { title: '八字记录' }
      },
      {
        path: 'tarot',
        name: 'TarotRecords',
        component: () => import('@/views/content/tarot.vue'),
        meta: { title: '塔罗记录' }
      },
      {
        path: 'daily',
        name: 'DailyFortune',
        component: () => import('@/views/content/daily.vue'),
        meta: { title: '每日运势' }
      }
    ]
  },
  {
    path: '/site',
    component: () => import('@/layout/index.vue'),
    redirect: '/site/content',
    meta: { title: '网站内容', icon: 'Monitor' },
    children: [
      {
        path: 'content',
        name: 'ContentManager',
        component: () => import('@/views/site-content/content-manager.vue'),
        meta: { title: '内容管理' }
      },
      {
        path: 'testimonials',
        name: 'TestimonialsManager',
        component: () => import('@/views/site-content/testimonials.vue'),
        meta: { title: '用户评价' }
      },
      {
        path: 'faq',
        name: 'FaqManager',
        component: () => import('@/views/site-content/faq.vue'),
        meta: { title: 'FAQ管理' }
      },
      {
        path: 'tarot-cards',
        name: 'TarotCardsManager',
        component: () => import('@/views/site-content/tarot-cards.vue'),
        meta: { title: '塔罗牌管理' }
      },
      {
        path: 'question-templates',
        name: 'QuestionTemplatesManager',
        component: () => import('@/views/site-content/question-templates.vue'),
        meta: { title: '问题模板' }
      }
    ]
  },
  {
    path: '/editor',
    component: () => import('@/layout/index.vue'),
    meta: { title: '页面编辑', icon: 'Edit', hidden: true },
    children: [
      {
        path: 'page/:id',
        name: 'PageEditor',
        component: () => import('@/views/editor/page-editor.vue'),
        meta: { title: '编辑页面', hidden: true }
      }
    ]
  },
  {
    path: '/points',
    component: () => import('@/layout/index.vue'),
    redirect: '/points/records',
    meta: { title: '积分管理', icon: 'Coin', roles: ['admin', 'operator'] },
    children: [
      {
        path: 'records',
        name: 'PointsRecords',
        component: () => import('@/views/points/records.vue'),
        meta: { title: '积分记录', roles: ['admin', 'operator'] }
      },
      {
        path: 'rules',
        name: 'PointsRules',
        component: () => import('@/views/points/rules.vue'),
        meta: { title: '积分规则', roles: ['admin'] }
      },
      {
        path: 'adjust',
        name: 'PointsAdjust',
        component: () => import('@/views/points/adjust.vue'),
        meta: { title: '积分调整', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/payment',
    component: () => import('@/layout/index.vue'),
    redirect: '/payment/orders',
    meta: { title: '支付管理', icon: 'Wallet', roles: ['admin', 'operator'] },
    children: [
      {
        path: 'orders',
        name: 'PaymentOrders',
        component: () => import('@/views/payment/orders.vue'),
        meta: { title: '充值订单', roles: ['admin', 'operator'] }
      },
      {
        path: 'config',
        name: 'PaymentConfig',
        component: () => import('@/views/payment/config.vue'),
        meta: { title: '支付配置', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/sms',
    component: () => import('@/layout/index.vue'),
    redirect: '/sms/config',
    meta: { title: '短信管理', icon: 'Message' },
    children: [
      {
        path: 'config',
        name: 'SmsConfig',
        component: () => import('@/views/sms/config.vue'),
        meta: { title: '短信配置' }
      },
      {
        path: 'records',
        name: 'SmsRecords',
        component: () => import('@/views/sms/records.vue'),
        meta: { title: '发送记录' }
      }
    ]
  },
  {
    path: '/feedback',
    component: () => import('@/layout/index.vue'),
    redirect: '/feedback/list',
    meta: { title: '反馈管理', icon: 'ChatDotRound', roles: ['admin', 'operator'] },
    children: [
      {
        path: 'list',
        name: 'FeedbackList',
        component: () => import('@/views/feedback/list.vue'),
        meta: { title: '反馈列表', roles: ['admin', 'operator'] }
      },
      {
        path: 'category',
        name: 'FeedbackCategory',
        component: () => import('@/views/feedback/category.vue'),
        meta: { title: '分类管理', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/anticheat',
    component: () => import('@/layout/index.vue'),
    redirect: '/anticheat/events',
    meta: { title: '反作弊系统', icon: 'WarningFilled' },
    children: [
      {
        path: 'events',
        name: 'RiskEvents',
        component: () => import('@/views/anticheat/events.vue'),
        meta: { title: '风险事件' }
      },
      {
        path: 'rules',
        name: 'RiskRules',
        component: () => import('@/views/anticheat/rules.vue'),
        meta: { title: '风险规则' }
      },
      {
        path: 'devices',
        name: 'DeviceFingerprint',
        component: () => import('@/views/anticheat/devices.vue'),
        meta: { title: '设备指纹' }
      }
    ]
  },
  {
    path: '/ai',
    component: () => import('@/layout/index.vue'),
    redirect: '/ai/prompts',
    meta: { title: 'AI管理', icon: 'Cpu' },
    children: [
      {
        path: 'prompts',
        name: 'AiPrompts',
        component: () => import('@/views/ai/prompts.vue'),
        meta: { title: '提示词管理' }
      },
      {
        path: 'config',
        name: 'AiConfig',
        component: () => import('@/views/system/settings.vue'),
        meta: { title: 'AI配置' }
      }
    ]
  },
  {
    path: '/system',
    component: () => import('@/layout/index.vue'),
    redirect: '/system/settings',
    meta: { title: '系统设置', icon: 'Setting', roles: ['admin'] },
    children: [
      {
        path: 'settings',
        name: 'SystemSettings',
        component: () => import('@/views/system/settings.vue'),
        meta: { title: '基础配置', roles: ['admin'] }
      },
      {
        path: 'sensitive',
        name: 'SensitiveWords',
        component: () => import('@/views/system/sensitive.vue'),
        meta: { title: '敏感词管理', roles: ['admin'] }
      },
      {
        path: 'notice',
        name: 'SystemNotice',
        component: () => import('@/views/system/notice.vue'),
        meta: { title: '系统公告', roles: ['admin', 'operator'] }
      },
      {
        path: 'admin',
        name: 'AdminUsers',
        component: () => import('@/views/system/admin.vue'),
        meta: { title: '管理员管理', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/log',
    component: () => import('@/layout/index.vue'),
    redirect: '/log/operation',
    meta: { title: '日志管理', icon: 'List', roles: ['admin'] },
    children: [
      {
        path: 'operation',
        name: 'OperationLog',
        component: () => import('@/views/log/operation.vue'),
        meta: { title: '操作日志', roles: ['admin'] }
      },
      {
        path: 'login',
        name: 'LoginLog',
        component: () => import('@/views/log/login.vue'),
        meta: { title: '登录日志', roles: ['admin'] }
      },
      {
        path: 'api',
        name: 'ApiLog',
        component: () => import('@/views/log/api.vue'),
        meta: { title: 'API日志', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/task',
    component: () => import('@/layout/index.vue'),
    redirect: '/task/list',
    meta: { title: '任务调度', icon: 'Timer', roles: ['admin'] },
    children: [
      {
        path: 'list',
        name: 'TaskList',
        component: () => import('@/views/task/list.vue'),
        meta: { title: '任务列表', roles: ['admin'] }
      },
      {
        path: 'logs',
        name: 'TaskLogs',
        component: () => import('@/views/task/logs.vue'),
        meta: { title: '执行日志', roles: ['admin'] }
      }
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: '/404', hidden: true }
]

const router = createRouter({
  history: createWebHistory(),
  routes: constantRoutes,
  scrollBehavior: () => ({ top: 0 })
})

router.beforeEach((to, from, next) => {
  NProgress.start()
  const userStore = useUserStore()
  
  if (to.path === '/login') {
    next()
  } else if (!userStore.token) {
    next('/login')
  } else {
    // 检查角色权限
    const requiredRoles = to.meta?.roles
    if (requiredRoles && requiredRoles.length > 0) {
      const userRole = userStore.userInfo?.role || 'operator'
      if (!requiredRoles.includes(userRole)) {
        // 无权限访问，重定向到403页面或首页
        next('/')
        return
      }
    }
    next()
  }
})

router.afterEach(() => {
  NProgress.done()
})

export default router
