<template>
  <section class="app-main">
    <router-view v-slot="{ Component, route }">
      <transition name="fade-transform" mode="out-in">
        <keep-alive :include="cachedViews">
          <component :is="Component" :key="route.path" />
        </keep-alive>
      </transition>
    </router-view>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useTagsViewStore } from '@/stores/tagsView'

const tagsViewStore = useTagsViewStore()
const cachedViews = computed(() => tagsViewStore.cachedViews)
</script>

<style lang="scss" scoped>
.app-main {
  min-height: calc(100vh - var(--navbar-height) - var(--tags-view-height));
  width: 100%;
  position: relative;
  overflow: hidden;
  padding: 20px;
  background: #f5f7fa;
}

.fixed-header + .app-main {
  padding-top: 50px;
}

.hasTagsView {
  .app-main {
    min-height: calc(100vh - var(--navbar-height) - var(--tags-view-height));
  }

  .fixed-header + .app-main {
    padding-top: 84px;
  }
}
</style>
