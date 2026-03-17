import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login, getInfo } from '@/api/user'
import { hasRoutePermission, normalizeAdminRoles } from '@/utils/admin-permission'
import router, { asyncRoutes } from '@/router'

function filterAsyncRoutes(routes, currentRoles) {
  return routes.reduce((result, route) => {
    const nextRoute = { ...route }
    const hasPermission = hasRoutePermission(currentRoles, route.meta?.roles || [])

    if (Array.isArray(route.children) && route.children.length > 0) {
      nextRoute.children = filterAsyncRoutes(route.children, currentRoles)
      if (hasPermission || nextRoute.children.length > 0) {
        result.push(nextRoute)
      }
      return result
    }

    if (hasPermission) {
      result.push(nextRoute)
    }

    return result
  }, [])
}

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('admin-token') || '')
  const userInfo = ref(null)
  const roles = ref([])
  const permissions = ref([])

  const isLoggedIn = computed(() => !!token.value)
  const avatar = computed(() => userInfo.value?.avatar || '')
  const name = computed(() => userInfo.value?.username || '管理员')
  const accessRoutes = computed(() => filterAsyncRoutes(asyncRoutes, roles.value))

  async function loginAction(loginForm) {
    const { data } = await login(loginForm)
    const nextRoles = normalizeAdminRoles(data.admin?.roles || data.roles || ['admin'])

    token.value = data.token
    userInfo.value = data.admin
      ? { ...data.admin, roles: nextRoles }
      : null
    roles.value = nextRoles
    localStorage.setItem('admin-token', data.token)

    return data
  }

  async function getUserInfo() {
    const { data } = await getInfo()
    const nextRoles = normalizeAdminRoles(data.roles || data.role || ['admin'])

    userInfo.value = {
      ...data,
      roles: nextRoles,
    }
    roles.value = nextRoles
    permissions.value = data.permissions || ['*']

    return data
  }

  function generateRoutes() {
    return accessRoutes.value
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
    accessRoutes,
    loginAction,
    getUserInfo,
    generateRoutes,
    logout
  }
})
