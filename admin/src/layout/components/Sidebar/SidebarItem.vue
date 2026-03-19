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

  if (showingChildren.length === 0) {
    onlyOneChild.value = { ...parent, path: '', noShowingChildren: true }
    return true
  }

  if (showingChildren.length === 1) {
    onlyOneChild.value = showingChildren[0]
    return true
  }

  return false
}

function resolvePath(routePath) {
  if (isExternal(routePath)) {
    return routePath
  }
  if (props.basePath && routePath) {
    return props.basePath + '/' + routePath.replace(/^\//, '')
  }
  return routePath
}
</script>
