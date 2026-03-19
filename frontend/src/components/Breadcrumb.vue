<template>
  <nav class="breadcrumb" aria-label="面包屑导航">
    <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
      <li 
        v-for="(item, index) in items" 
        :key="index"
        class="breadcrumb-item"
        :class="{ 'is-active': index === items.length - 1 }"
        itemprop="itemListElement"
        itemscope
        itemtype="https://schema.org/ListItem"
      >
        <router-link
          v-if="index !== items.length - 1"
          :to="item.path"
          class="breadcrumb-link"
          itemprop="item"
        >
          <span itemprop="name">{{ item.name }}</span>
        </router-link>
        <span v-else class="breadcrumb-text" itemprop="name">
          {{ item.name }}
        </span>
        <meta itemprop="position" :content="String(index + 1)" />
        <span v-if="index !== items.length - 1" class="breadcrumb-separator">
          <svg viewBox="0 0 24 24" width="16" height="16">
            <path fill="currentColor" d="M9.29 6.71a.996.996 0 000 1.41L13.17 12l-3.88 3.88a.996.996 0 101.41 1.41l4.59-4.59a.996.996 0 000-1.41L10.7 6.7c-.38-.38-1.02-.38-1.41.01z"/>
          </svg>
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// 面包屑配置
const breadcrumbMap = {
  'Home': { name: '首页', path: '/' },
  'Bazi': { name: '文化测算', path: '/cultural_calculation' },
  'Tarot': { name: '文化分析', path: '/cultural_analysis' },
  'Daily': { name: '日常参考', path: '/daily' },
  'Profile': { name: '个人中心', path: '/profile' },
  'Recharge': { name: '积分充值', path: '/recharge' },
  'Help': { name: '帮助中心', path: '/help' },
  'Login': { name: '登录', path: '/login' }
}

// 生成面包屑数据
const items = computed(() => {
  const matched = route.matched
  const breadcrumbs = [{ name: '首页', path: '/' }]
  
  matched.forEach(match => {
    if (match.name && match.name !== 'Home') {
      const config = breadcrumbMap[match.name]
      if (config) {
        breadcrumbs.push(config)
      }
    }
  })
  
  return breadcrumbs
})
</script>

<style scoped>
.breadcrumb {
  padding: 12px 0;
  font-size: 14px;
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  list-style: none;
  margin: 0;
  padding: 0;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
}

.breadcrumb-link {
  color: var(--text-secondary);
  text-decoration: none;
  transition: color 0.3s ease;
}

.breadcrumb-link:hover {
  color: var(--primary-color);
}

.breadcrumb-text {
  color: var(--text-primary);
  font-weight: 500;
}

.breadcrumb-separator {
  margin: 0 8px;
  color: var(--text-tertiary);
  display: flex;
  align-items: center;
}

.breadcrumb-separator svg {
  opacity: 0.6;
}

/* 暗黑模式适配 */
@media (prefers-color-scheme: dark) {
  .breadcrumb-link {
    color: #94a3b8;
  }
  
  .breadcrumb-link:hover {
    color: #818cf8;
  }
  
  .breadcrumb-text {
    color: #f1f5f9;
  }
  
  .breadcrumb-separator {
    color: #64748b;
  }
}
</style>
