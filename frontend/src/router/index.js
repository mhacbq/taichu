import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Bazi from '../views/Bazi.vue'
import Tarot from '../views/Tarot.vue'
import Daily from '../views/Daily.vue'
import Profile from '../views/Profile.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/bazi',
    name: 'Bazi',
    component: Bazi,
  },
  {
    path: '/tarot',
    name: 'Tarot',
    component: Tarot,
  },
  {
    path: '/daily',
    name: 'Daily',
    component: Daily,
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
