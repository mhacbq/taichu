import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { hasRoutePermission, normalizeAdminRoles } from '@/utils/admin-permission'
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
    meta: { roles: ['admin', 'operator'] },
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘', icon: 'HomeFilled', roles: ['admin', 'operator'] }
      }
    ]
  },
  {
    path: '/user',
    component: () => import('@/layout/index.vue'),
    redirect: '/user/list',
    meta: { title: '用户管理', icon: 'UserFilled', roles: ['admin', 'operator'] },
    children: [
      {
        path: '/list',
        name: 'UserList',
        component: () => import('@/views/user/list.vue'),
        meta: { title: '用户列表', roles: ['admin', 'operator'] }
      },
      {
        path: '/detail/:id',
        name: 'UserDetail',
        component: () => import('@/views/user/detail.vue'),
        meta: { title: '用户详情', roles: ['admin', 'operator'] },
        hidden: true
      },
      {
        path: '/behavior',
        name: 'UserBehavior',
        component: () => import('@/views/user/behavior.vue'),
        meta: { title: '行为日志', roles: ['admin', 'operator'] }
      },
      {
        path: '/analysis',
        name: 'UserAnalysis',
        component: () => import('@/views/user/analysis.vue'),
        meta: { title: '用户分析', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/result',
    component: () => import('@/layout/index.vue'),
    redirect: '/result/bazi',
    meta: { title: '测算管理', icon: 'DataAnalysis', roles: ['admin', 'operator'] },
    children: [
      {
        path: '/bazi',
        name: 'BaziManage',
        component: () => import('@/views/result/BaziManage.vue'),
        meta: { title: '八字测算', roles: ['admin', 'operator'] }
      },
      {
        path: '/tarot',
        name: 'TarotManage',
        component: () => import('@/views/result/TarotManage.vue'),
        meta: { title: '塔罗测算', roles: ['admin', 'operator'] }
      },
      {
        path: '/liuyao',
        name: 'LiuyaoManage',
        component: () => import('@/views/result/LiuyaoManage.vue'),
        meta: { title: '六爻测算', roles: ['admin', 'operator'] }
      },
      {
        path: '/hehun',
        name: 'HehunManage',
        component: () => import('@/views/result/HehunManage.vue'),
        meta: { title: '合婚测算', roles: ['admin', 'operator'] }
      },
      {
        path: '/analysis',
        name: 'ResultAnalysis',
        component: () => import('@/views/result/analysis.vue'),
        meta: { title: '测算统计', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/content',
    component: () => import('@/layout/index.vue'),
    redirect: '/content/pages',
    meta: { title: '内容管理', icon: 'Document', roles: ['admin', 'operator'] },
    children: [
      {
        path: '/pages',
        name: 'Pages',
        component: () => import('@/views/content/pages.vue'),
        meta: { title: '页面管理', roles: ['admin', 'operator'] }
      },
      {
        path: '/pages/:id/history',
        name: 'PageHistory',
        component: () => import('@/views/content/page-history.vue'),
        meta: { title: '页面历史', hidden: true, roles: ['admin', 'operator'] }
      },
      {
        path: '/almanac',
        name: 'AlmanacManager',
        component: () => import('@/views/content/almanac.vue'),
        meta: { title: '黄历管理', roles: ['admin', 'operator'] }
      },
      {
        path: '/bazi',
        name: 'BaziRecords',
        component: () => import('@/views/content/bazi.vue'),
        meta: { title: '八字记录', roles: ['admin', 'operator'] }
      },
      {
        path: '/tarot',
        name: 'TarotRecords',
        component: () => import('@/views/content/tarot.vue'),
        meta: { title: '塔罗记录', roles: ['admin', 'operator'] }
      },
      {
        path: '/daily',
        name: 'DailyFortune',
        component: () => import('@/views/content/daily.vue'),
        meta: { title: '每日运势', roles: ['admin', 'operator'] }
      },
      {
        path: '/shensha',
        name: 'ShenshaManager',
        component: () => import('@/views/content/shensha.vue'),
        meta: { title: '神煞管理', roles: ['admin', 'operator'] }
      }
    ]
  },
  {
    path: '/site',
    component: () => import('@/layout/index.vue'),
    redirect: '/site/content',
    meta: { title: '网站内容', icon: 'Monitor', roles: ['admin', 'operator'] },
    children: [
      {
        path: '/content',
        name: 'ContentManager',
        component: () => import('@/views/site-content/content-manager.vue'),
        meta: { title: '内容管理', roles: ['admin', 'operator'] }
      },
      {
        path: '/knowledge',
        name: 'KnowledgeManager',
        component: () => import('@/views/site-content/knowledge.vue'),
        meta: { title: '知识库文章', roles: ['admin', 'operator'] }
      },
      {
        path: '/seo',
        name: 'SeoManager',
        component: () => import('@/views/site-content/seo.vue'),
        meta: { title: 'SEO管理', roles: ['admin'] }
      },
      {
        path: '/testimonials',
        name: 'TestimonialsManager',
        component: () => import('@/views/site-content/testimonials.vue'),
        meta: { title: '用户评价', roles: ['admin', 'operator'] }
      },
      {
        path: '/faq',
        name: 'FaqManager',
        component: () => import('@/views/site-content/faq.vue'),
        meta: { title: 'FAQ管理', roles: ['admin', 'operator'] }
      },
      {
        path: '/tarot-cards',
        name: 'TarotCardsManager',
        component: () => import('@/views/site-content/tarot-cards.vue'),
        meta: { title: '塔罗牌管理', roles: ['admin', 'operator'] }
      },
      {
        path: '/question-templates',
        name: 'QuestionTemplatesManager',
        component: () => import('@/views/site-content/question-templates.vue'),
        meta: { title: '问题模板', roles: ['admin', 'operator'] }
      }
    ]
  },
  {
    path: '/editor',
    component: () => import('@/layout/index.vue'),
    meta: { title: '页面编辑', icon: 'Edit', hidden: true, roles: ['admin', 'operator'] },
    children: [
      {
        path: 'page/:id',
        name: 'PageEditor',
        component: () => import('@/views/editor/page-editor.vue'),
        meta: { title: '编辑页面', hidden: true, roles: ['admin', 'operator'] }
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
        path: '/records',
        name: 'PointsRecords',
        component: () => import('@/views/points/records.vue'),
        meta: { title: '积分记录', roles: ['admin', 'operator'] }
      },
      {
        path: '/rules',
        name: 'PointsRules',
        component: () => import('@/views/points/rules.vue'),
        meta: { title: '积分规则', roles: ['admin'] }
      },
      {
        path: '/adjust',
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
        path: '/orders',
        name: 'PaymentOrders',
        component: () => import('@/views/payment/orders.vue'),
        meta: { title: '充值订单', roles: ['admin', 'operator'] }
      },
      {
        path: '/vip-orders',
        name: 'VipOrders',
        component: () => import('@/views/payment/vip-orders.vue'),
        meta: { title: 'VIP订单', roles: ['admin', 'operator'] }
      },
      {
        path: '/vip-packages',
        name: 'VipPackages',
        component: () => import('@/views/payment/vip-packages.vue'),
        meta: { title: 'VIP套餐', roles: ['admin'] }
      },
      {
        path: '/config',
        name: 'PaymentConfig',
        component: () => import('@/views/payment/config.vue'),
        meta: { title: '支付配置', roles: ['admin'] }
      },
      {
        path: '/analysis',
        name: 'PaymentAnalysis',
        component: () => import('@/views/payment/analysis.vue'),
        meta: { title: '充值分析', roles: ['admin'] }
      }
    ]

  },
  {
    path: '/sms',
    component: () => import('@/layout/index.vue'),
    redirect: '/sms/config',
    meta: { title: '短信管理', icon: 'Message', roles: ['admin'] },
    children: [
      {
        path: '/config',
        name: 'SmsConfig',
        component: () => import('@/views/sms/config.vue'),
        meta: { title: '短信配置', roles: ['admin'] }
      },
      {
        path: '/records',
        name: 'SmsRecords',
        component: () => import('@/views/sms/records.vue'),
        meta: { title: '发送记录', roles: ['admin', 'operator'] }
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
        path: '/list',
        name: 'FeedbackList',
        component: () => import('@/views/feedback/list.vue'),
        meta: { title: '反馈列表', roles: ['admin', 'operator'] }
      },
      {
        path: '/category',
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
    meta: { title: '反作弊系统', icon: 'WarningFilled', roles: ['admin'] },
    children: [
      {
        path: '/events',
        name: 'RiskEvents',
        component: () => import('@/views/anticheat/events.vue'),
        meta: { title: '风险事件', roles: ['admin'] }
      },
      {
        path: '/rules',
        name: 'RiskRules',
        component: () => import('@/views/anticheat/rules.vue'),
        meta: { title: '风险规则', roles: ['admin'] }
      },
      {
        path: '/devices',
        name: 'DeviceFingerprint',
        component: () => import('@/views/anticheat/devices.vue'),
        meta: { title: '设备指纹', roles: ['admin'] }
      }
    ]
  },
  {
    path: '/ai',
    component: () => import('@/layout/index.vue'),
    redirect: '/ai/prompts',
    meta: { title: 'AI管理', icon: 'Cpu', roles: ['admin'] },
    children: [
      {
        path: '/prompts',
        name: 'AiPrompts',
        component: () => import('@/views/ai/prompts.vue'),
        meta: { title: '提示词管理', roles: ['admin'] }
      },
      {
        path: '/config',
        name: 'AiConfig',
        component: () => import('@/views/system/settings.vue'),
        meta: { title: 'AI配置', roles: ['admin'] }
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
        path: '/settings',
        name: 'SystemSettings',
        component: () => import('@/views/system/settings.vue'),
        meta: { title: '基础配置', roles: ['admin'] }
      },
      {
        path: '/system-config',
        name: 'SystemConfig',
        component: () => import('@/views/system/systemConfig.vue'),
        meta: { title: '系统配置', roles: ['admin'] }
      },
      {
        path: '/sensitive',
        name: 'SensitiveWords',
        component: () => import('@/views/system/sensitive.vue'),
        meta: { title: '敏感词管理', roles: ['admin'] }
      },
      {
        path: '/notice',
        name: 'SystemNotice',
        component: () => import('@/views/system/notice.vue'),
        meta: { title: '系统公告', roles: ['admin', 'operator'] }
      },
      {
        path: '/notification',
        name: 'NotificationConfig',
        component: () => import('@/views/system/notification.vue'),
        meta: { title: '通知配置', roles: ['admin', 'operator'] }
      },
      {
        path: '/admin',
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
        path: '/operation',
        name: 'OperationLog',
        component: () => import('@/views/log/operation.vue'),
        meta: { title: '操作日志', roles: ['admin'] }
      },
      {
        path: '/login',
        name: 'LoginLog',
        component: () => import('@/views/log/login.vue'),
        meta: { title: '登录日志', roles: ['admin'] }
      },
      {
        path: '/api',
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
        path: '/list',
        name: 'TaskList',
        component: () => import('@/views/task/list.vue'),
        meta: { title: '任务列表', roles: ['admin'] }
      },
      {
        path: '/logs',
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
  routes: [...constantRoutes, ...asyncRoutes],
  scrollBehavior: () => ({ top: 0 })
})

router.beforeEach(async (to, from, next) => {
  NProgress.start()
  const userStore = useUserStore()
  
  if (to.path === '/login') {
    next()
    return
  }

  if (!userStore.token) {
    next('/login')
    return
  }

  if (!userStore.userInfo || userStore.roles.length === 0) {
    try {
      await userStore.getUserInfo()
    } catch (error) {
      userStore.logout()
      next('/login')
      return
    }
  }

  const requiredRoles = to.meta?.roles || []
  if (requiredRoles.length > 0) {
    const currentRoles = normalizeAdminRoles(userStore.roles || userStore.userInfo?.roles || [])
    if (!hasRoutePermission(currentRoles, requiredRoles)) {
      next('/')
      return
    }
  }


  next()
})

router.afterEach(() => {
  NProgress.done()
})

export default router