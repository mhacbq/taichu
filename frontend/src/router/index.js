import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Bazi from '../views/Bazi.vue'
import Tarot from '../views/Tarot.vue'
import Daily from '../views/Daily.vue'
import Profile from '../views/Profile.vue'
import Login from '../views/Login.vue'
import Help from '../views/Help.vue'
import NotFound from '../views/NotFound.vue'
import Recharge from '../views/Recharge.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { public: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { public: true }
  },
  {
    path: '/bazi',
    name: 'Bazi',
    component: Bazi,
    meta: { requiresAuth: true }
  },
  {
    path: '/tarot',
    name: 'Tarot',
    component: Tarot,
    meta: { requiresAuth: true }
  },
  {
    path: '/daily',
    name: 'Daily',
    component: Daily,
    meta: { public: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true }
  },
  {
    path: '/recharge',
    name: 'Recharge',
    component: Recharge,
    meta: { requiresAuth: true }
  },
  {
    path: '/help',
    name: 'Help',
    component: Help,
    meta: { public: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: { public: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
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

export default router
