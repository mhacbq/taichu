<template>
  <div v-if="!item.hidden">
    <template v-if="hasOneShowingChild(item.children, item) && (!onlyOneChild.children || onlyOneChild.noShowingChildren)">
      <el-menu-item :index="resolvePath(onlyOneChild.path)" :class="{ 'submenu-title-noDropdown': !isNest }">
        <el-icon v-if="onlyOneChild.meta?.icon">
          <component :is="onlyOneChild.meta.icon" />
        </el-icon>
        <template #title>
          <span>{{ onlyOneChild.meta?.title }}</span>
        </template>
      </el-menu-item>
    </template>

    <el-sub-menu v-else :index="resolvePath(item.path)" popper-class="sidebar-popper">
      <template #title>
        <el-icon v-if="item.meta?.icon">
          <component :is="item.meta.icon" />
        </el-icon>
        <span v-if="item.meta?.title">{{ item.meta.title }}</span>
      </template>
      <SidebarItem
        v-for="child in item.children"
        :key="child.path"
        :item="child"
        :is-nest="true"
        :base-path="resolvePath(child.path)"
      />
    </el-sub-menu>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { isExternal } from '@/utils/validate'

const props = defineProps({
  item: { type: Object, required: true },
  isNest: { type: Boolean, default: false },
  basePath: { type: String, default: '' }
})

const onlyOneChild = ref(null)

function hasOneShowingChild(children = [], parent) {
  const showingChildren = children.filter(item => !item.hidden)

  // 如果没有可见的子菜单，显示父菜单本身
  if (showingChildren.length === 0) {
    onlyOneChild.value = { ...parent, path: '', noShowingChildren: true }
    return true
  }

  // 如果只有一个可见的子菜单
  if (showingChildren.length === 1) {
    const child = showingChildren[0]
    // 检查这个唯一的子菜单是否还有它自己的可见子菜单
    const childShowingChildren = (child.children || []).filter(item => !item.hidden)
    if (childShowingChildren.length > 0) {
      // 如果唯一的子菜单还有子菜单，那么不应该直接显示它，而应该显示子树
      return false
    }
    onlyOneChild.value = child
    return true
  }

  return false
}

function resolvePath(routePath) {
  if (isExternal(routePath)) {
    return routePath
  }

  let base = props.basePath || ''
  let sub = routePath || ''

  // 如果子路径已经是绝对路径
  if (sub.startsWith('/')) {
    return sub
  }

  // 处理路径拼接
  let fullPath = base
  if (sub) {
    if (!fullPath.endsWith('/')) {
      fullPath += '/'
    }
    fullPath += sub.replace(/^\//, '')
  }

  // 确保以 / 开头
  if (!fullPath.startsWith('/')) {
    fullPath = '/' + fullPath
  }

  // 规范化路径，处理 // 的情况
  fullPath = fullPath.replace(/\/+/g, '/')

  return fullPath
}
</script>
