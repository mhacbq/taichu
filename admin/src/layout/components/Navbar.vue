<template>
  <div class="navbar">
    <div class="left-menu">
      <el-icon class="hamburger" :size="20" @click="toggleSidebar">
        <Fold v-if="sidebar.opened" />
        <Expand v-else />
      </el-icon>
      <breadcrumb />
    </div>
    <div class="right-menu">
      <el-tooltip content="全屏" placement="bottom">
        <el-icon class="right-menu-item" :size="18" @click="toggleFullScreen">
          <FullScreen />
        </el-icon>
      </el-tooltip>
      <el-dropdown class="avatar-container" @command="handleCommand">
        <div class="avatar-wrapper">
          <el-avatar :size="30" :src="userStore.avatar" />
          <span class="user-name">{{ userStore.name }}</span>
          <el-icon><ArrowDown /></el-icon>
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="profile">个人中心</el-dropdown-item>
            <el-dropdown-item command="settings">系统设置</el-dropdown-item>
            <el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import Breadcrumb from './Breadcrumb.vue'

const router = useRouter()
const appStore = useAppStore()
const userStore = useUserStore()

const sidebar = computed(() => appStore.sidebar)

function toggleSidebar() {
  appStore.toggleSidebar()
}

function toggleFullScreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

function handleCommand(command) {
  switch (command) {
    case 'profile':
      router.push('/profile')
      break
    case 'settings':
      router.push('/system/settings')
      break
    case 'logout':
      userStore.logout()
      ElMessage.success('已退出登录')
      break
  }
}
</script>

<style lang="scss" scoped>
.navbar {
  height: var(--navbar-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 21, 41, 0.08);
  padding: 0 15px;
}

.left-menu {
  display: flex;
  align-items: center;
}

.hamburger {
  padding: 0 15px;
  cursor: pointer;
  color: #606266;
  
  &:hover {
    color: #409eff;
  }
}

.right-menu {
  display: flex;
  align-items: center;
}

.right-menu-item {
  padding: 0 12px;
  cursor: pointer;
  color: #606266;
  
  &:hover {
    color: #409eff;
  }
}

.avatar-container {
  margin-left: 15px;
  cursor: pointer;
}

.avatar-wrapper {
  display: flex;
  align-items: center;
  
  .user-name {
    margin: 0 8px;
    font-size: 14px;
    color: #606266;
  }
}
</style>
