<template>
  <el-breadcrumb separator="/">
    <el-breadcrumb-item v-for="(item, index) in breadcrumbs" :key="item.path">
      <span v-if="index === breadcrumbs.length - 1" class="no-redirect">{{ item.meta.title }}</span>
      <a v-else @click.prevent="handleLink(item)">{{ item.meta.title }}</a>
    </el-breadcrumb-item>
  </el-breadcrumb>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const breadcrumbs = ref([])

function getBreadcrumb() {
  let matched = route.matched.filter(item => item.meta && item.meta.title)
  const first = matched[0]
  
  if (!isDashboard(first)) {
    matched = [{ path: '/dashboard', meta: { title: '仪表盘' } }].concat(matched)
  }
  
  breadcrumbs.value = matched.filter(item => {
    return item.meta && item.meta.title && item.meta.breadcrumb !== false
  })
}

function isDashboard(route) {
  const name = route && route.name
  if (!name) return false
  return name.trim().toLocaleLowerCase() === 'Dashboard'.toLocaleLowerCase()
}

function handleLink(item) {
  router.push(item.path)
}

watch(() => route.path, getBreadcrumb, { immediate: true })
</script>

<style lang="scss" scoped>
.el-breadcrumb {
  font-size: 14px;
  
  .no-redirect {
    color: #97a8be;
    cursor: text;
  }
  
  a {
    color: #606266;
    
    &:hover {
      color: #409eff;
    }
  }
}
</style>
