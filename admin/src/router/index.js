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
    redirect: '/content/bazi',
    meta: { title: '内容管理', icon: 'Document' },
    children: [
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
    path: '/points',
    component: () => import('@/layout/index.vue'),
    redirect: '/points/records',
    meta: { title: '积分管理', icon: 'Coin' },
    children: [
      {
        path: 'records',
        name: 'PointsRecords',
        component: () => import('@/views/points/records.vue'),
        meta: { title: '积分记录' }
      },
      {
        path: 'rules',
        name: 'PointsRules',
        component: () => import('@/views/points/rules.vue'),
        meta: { title: '积分规则' }
      },
      {
        path: 'adjust',
        name: 'PointsAdjust',
        component: () => import('@/views/points/adjust.vue'),
        meta: { title: '积分调整' }
      }
    ]
  },
  {
    path: '/feedback',
    component: () => import('@/layout/index.vue'),
    redirect: '/feedback/list',
    meta: { title: '反馈管理', icon: 'ChatDotRound' },
    children: [
      {
        path: 'list',
        name: 'FeedbackList',
        component: () => import('@/views/feedback/list.vue'),
        meta: { title: '反馈列表' }
      },
      {
        path: 'category',
        name: 'FeedbackCategory',
        component: () => import('@/views/feedback/category.vue'),
        meta: { title: '分类管理' }
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
    path: '/system',
    component: () => import('@/layout/index.vue'),
    redirect: '/system/settings',
    meta: { title: '系统设置', icon: 'Setting' },
    children: [
      {
        path: 'settings',
        name: 'SystemSettings',
        component: () => import('@/views/system/settings.vue'),
        meta: { title: '基础配置' }
      },
      {
        path: 'sensitive',
        name: 'SensitiveWords',
        component: () => import('@/views/system/sensitive.vue'),
        meta: { title: '敏感词管理' }
      },
      {
        path: 'notice',
        name: 'SystemNotice',
        component: () => import('@/views/system/notice.vue'),
        meta: { title: '系统公告' }
      },
      {
        path: 'admin',
        name: 'AdminUsers',
        component: () => import('@/views/system/admin.vue'),
        meta: { title: '管理员管理' }
      }
    ]
  },
  {
    path: '/log',
    component: () => import('@/layout/index.vue'),
    redirect: '/log/operation',
    meta: { title: '日志管理', icon: 'List' },
    children: [
      {
        path: 'operation',
        name: 'OperationLog',
        component: () => import('@/views/log/operation.vue'),
        meta: { title: '操作日志' }
      },
      {
        path: 'login',
        name: 'LoginLog',
        component: () => import('@/views/log/login.vue'),
        meta: { title: '登录日志' }
      },
      {
        path: 'api',
        name: 'ApiLog',
        component: () => import('@/views/log/api.vue'),
        meta: { title: 'API日志' }
      }
    ]
  },
  {
    path: '/task',
    component: () => import('@/layout/index.vue'),
    redirect: '/task/list',
    meta: { title: '任务调度', icon: 'Timer' },
    children: [
      {
        path: 'list',
        name: 'TaskList',
        component: () => import('@/views/task/list.vue'),
        meta: { title: '任务列表' }
      },
      {
        path: 'logs',
        name: 'TaskLogs',
        component: () => import('@/views/task/logs.vue'),
        meta: { title: '执行日志' }
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
    next()
  }
})

router.afterEach(() => {
  NProgress.done()
})

export default router
