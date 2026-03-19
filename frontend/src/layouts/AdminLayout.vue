<template>
  <div class="admin-layout" :class="{ 'sidebar-collapsed': collapsed }">
    <!-- 侧边栏 -->
    <aside class="admin-sidebar">
      <div class="sidebar-logo">
        <span class="logo-icon">☯</span>
        <span class="logo-text" v-show="!collapsed">太初管理后台</span>
      </div>

      <el-menu
        :default-active="activeMenu"
        :collapse="collapsed"
        :collapse-transition="false"
        router
        class="sidebar-menu"
      >
        <el-menu-item index="/admin">
          <el-icon><DataLine /></el-icon>
          <template #title>仪表板</template>
        </el-menu-item>

        <el-sub-menu index="users">
          <template #title>
            <el-icon><User /></el-icon>
            <span>用户运营</span>
          </template>
          <el-menu-item index="/admin/users">用户管理</el-menu-item>
          <el-menu-item index="/admin/points">积分记录</el-menu-item>
          <el-menu-item index="/admin/feedback">用户反馈</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="points">
          <template #title>
            <el-icon><Coin /></el-icon>
            <span>积分管理</span>
          </template>
          <el-menu-item index="/admin/points">积分记录</el-menu-item>
          <el-menu-item index="/admin/points-rules">积分规则</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="orders">
          <template #title>
            <el-icon><Money /></el-icon>
            <span>支付订单</span>
          </template>
          <el-menu-item index="/admin/orders">订单列表</el-menu-item>
          <el-menu-item index="/admin/packages">套餐管理</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="content">
          <template #title>
            <el-icon><Document /></el-icon>
            <span>内容管理</span>
          </template>
          <el-menu-item index="/admin/almanac">黄历管理</el-menu-item>
          <el-menu-item index="/admin/knowledge">知识库</el-menu-item>
          <el-menu-item index="/admin/shensha">神煞管理</el-menu-item>
          <el-menu-item index="/admin/content">内容记录</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="seo">
          <template #title>
            <el-icon><TrendCharts /></el-icon>
            <span>SEO 优化</span>
          </template>
          <el-menu-item index="/admin/seo">SEO配置</el-menu-item>
          <el-menu-item index="/admin/seo/stats">SEO统计</el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="system">
          <template #title>
            <el-icon><Setting /></el-icon>
            <span>系统设置</span>
          </template>
          <el-menu-item index="/admin/config">系统配置</el-menu-item>
          <el-menu-item index="/admin/system-tools">系统工具</el-menu-item>
          <el-menu-item index="/admin/tasks">定时任务</el-menu-item>
          <el-menu-item index="/admin/logs">系统日志</el-menu-item>
          <el-menu-item index="/admin/anticheat">反作弊</el-menu-item>
        </el-sub-menu>
      </el-menu>

      <!-- 折叠按钮 -->
      <div class="sidebar-footer">
        <el-button
          :icon="collapsed ? Expand : Fold"
          text
          class="collapse-btn"
          @click="collapsed = !collapsed"
        />
      </div>
    </aside>

    <!-- 主内容区 -->
    <div class="admin-main">
      <!-- 顶部栏 -->
      <header class="admin-topbar">
        <div class="topbar-left">
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/admin' }">管理后台</el-breadcrumb-item>
            <el-breadcrumb-item v-if="currentPageTitle">{{ currentPageTitle }}</el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        <div class="topbar-right">
          <el-button text :icon="HomeFilled" @click="$router.push('/')">返回前台</el-button>
          <el-button text :icon="SwitchButton" @click="handleLogout">退出登录</el-button>
        </div>
      </header>

      <!-- 页面内容 -->
      <main class="admin-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  DataLine, User, Money, Document, TrendCharts, Setting,
  Expand, Fold, HomeFilled, SwitchButton
} from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'

const route = useRoute()
const router = useRouter()
const collapsed = ref(false)

const activeMenu = computed(() => route.path)

const pageTitles = {
  '/admin': '仪表板',
  '/admin/users': '用户管理',
  '/admin/points': '积分记录',
  '/admin/points-rules': '积分规则',
  '/admin/feedback': '用户反馈',
  '/admin/orders': '订单列表',
  '/admin/packages': '套餐管理',
  '/admin/almanac': '黄历管理',
  '/admin/knowledge': '知识库管理',
  '/admin/shensha': '神煞管理',
  '/admin/content': '内容记录',
  '/admin/seo': 'SEO配置',
  '/admin/seo/stats': 'SEO统计',
  '/admin/config': '系统配置',
  '/admin/system-tools': '系统工具',
  '/admin/tasks': '定时任务',
  '/admin/logs': '系统日志',
  '/admin/anticheat': '反作弊管理',
}
const currentPageTitle = computed(() => pageTitles[route.path] || '')

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', { type: 'warning' })
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    router.push('/login')
  } catch {}
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #f0f2f5;
}

/* 侧边栏 */
.admin-sidebar {
  width: 220px;
  background: #001529;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.25s;
  overflow: hidden;
}
.admin-layout.sidebar-collapsed .admin-sidebar { width: 64px; }

.sidebar-logo {
  height: 56px;
  display: flex;
  align-items: center;
  padding: 0 18px;
  gap: 10px;
  border-bottom: 1px solid rgba(255,255,255,.1);
  white-space: nowrap;
  overflow: hidden;
}
.logo-icon { font-size: 24px; flex-shrink: 0; }
.logo-text { color: #fff; font-weight: 700; font-size: 15px; }

.sidebar-menu {
  flex: 1;
  border-right: none;
  background: transparent;
  overflow-y: auto;
  overflow-x: hidden;
}
:deep(.el-menu) { background: transparent !important; }
:deep(.el-menu-item),
:deep(.el-sub-menu__title) {
  color: rgba(255,255,255,.65) !important;
  height: 46px !important;
  line-height: 46px !important;
}
:deep(.el-menu-item:hover),
:deep(.el-sub-menu__title:hover),
:deep(.el-menu-item.is-active) {
  color: #fff !important;
  background: #1890ff !important;
}
:deep(.el-sub-menu .el-menu) { background: rgba(0,0,0,.25) !important; }
:deep(.el-sub-menu .el-menu .el-menu-item) { padding-left: 48px !important; }

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid rgba(255,255,255,.1);
  display: flex;
  justify-content: flex-end;
}
.collapse-btn { color: rgba(255,255,255,.65); }
.collapse-btn:hover { color: #fff; }

/* 主内容区 */
.admin-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.admin-topbar {
  height: 56px;
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  flex-shrink: 0;
}
.topbar-right { display: flex; gap: 4px; }

.admin-content {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}
</style>
