<template>
  <div class="sidebar-container">
    <div class="logo">
      <router-link to="/">
        <span class="logo-title">太初管理后台</span>
      </router-link>
    </div>
    <el-scrollbar wrap-class="scrollbar-wrapper">
      <el-menu
        :default-active="activeMenu"
        :collapse="isCollapse"
        :background-color="variables.menuBg"
        :text-color="variables.menuText"
        :active-text-color="variables.menuActiveText"
        :collapse-transition="false"
        mode="vertical"
      >
        <SidebarItem
          v-for="route in routes"
          :key="route.path"
          :item="route"
          :base-path="route.path"
        />
      </el-menu>
    </el-scrollbar>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { asyncRoutes } from '@/router'
import SidebarItem from './SidebarItem.vue'

const route = useRoute()
const appStore = useAppStore()

const variables = {
  menuBg: '#304156',
  menuText: '#bfcbd9',
  menuActiveText: '#409eff'
}

const routes = computed(() => asyncRoutes.filter(r => !r.hidden))

const activeMenu = computed(() => {
  const { meta, path } = route
  if (meta?.activeMenu) {
    return meta.activeMenu
  }
  return path
})

const isCollapse = computed(() => !appStore.sidebar.opened)
</script>

<style lang="scss" scoped>
.logo {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #2b3649;
  
  .logo-title {
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    white-space: nowrap;
  }
}

.scrollbar-wrapper {
  overflow-x: hidden !important;
}

:deep(.el-menu) {
  border: none;
  height: 100%;
  width: 100% !important;
}
</style>
