import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login, getInfo } from '@/api/user'
import router from '@/router'
import { asyncRoutes } from '@/router'

export const useUserStore = defineStore('user', () => {
  // State
  const token = ref(localStorage.getItem('admin-token') || '')
  const userInfo = ref(null)
  const roles = ref([])
  const permissions = ref([])

  // Getters
  const isLoggedIn = computed(() => !!token.value)
  const avatar = computed(() => userInfo.value?.avatar || '')
  const name = computed(() => userInfo.value?.username || '管理员')

  // Actions
  async function loginAction(loginForm) {
    const { data } = await login(loginForm)
    token.value = data.token
    localStorage.setItem('admin-token', data.token)
    return data
  }

  async function getUserInfo() {
    const { data } = await getInfo()
    userInfo.value = data
    roles.value = data.roles || ['admin']
    permissions.value = data.permissions || ['*']
    return data
  }

  function generateRoutes() {
    // 根据角色权限过滤路由
    const accessedRoutes = asyncRoutes.filter(route => {
      if (route.meta && route.meta.roles) {
        return roles.value.some(role => route.meta.roles.includes(role))
      }
      return true
    })
    return accessedRoutes
  }

  function logout() {
    token.value = ''
    userInfo.value = null
    roles.value = []
    permissions.value = []
    localStorage.removeItem('admin-token')
    router.push('/login')
  }

  return {
    token,
    userInfo,
    roles,
    permissions,
    isLoggedIn,
    avatar,
    name,
    loginAction,
    getUserInfo,
    generateRoutes,
    logout
  }
})
