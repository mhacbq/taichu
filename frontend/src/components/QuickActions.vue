<template>
  <div class="quick-actions">
    <h3 class="section-title">
      <el-icon class="title-icon"><Lightning /></el-icon>
      快捷入口
    </h3>
    <div class="actions-grid">
      <router-link
        v-for="action in quickActions"
        :key="action.path"
        :to="action.path"
        class="action-card"
        :style="{ '--card-color': action.color }"
      >
        <div class="action-icon-wrapper">
          <span class="action-icon">
            <el-icon v-if="action.icon === 'calendar'"><Calendar /></el-icon>
            <el-icon v-else-if="action.icon === 'magic'"><MagicStick /></el-icon>

            <el-icon v-else-if="action.icon === 'yinyang'"><YinYangIcon /></el-icon>
            <el-icon v-else-if="action.icon === 'heart'"><HeartFilled /></el-icon>
            <el-icon v-else-if="action.icon === 'star'"><Star /></el-icon>
            <el-icon v-else-if="action.icon === 'user'"><UserFilled /></el-icon>
          </span>
          <div class="icon-glow"></div>
        </div>
        <div class="action-content">
          <h4 class="action-title">{{ action.title }}</h4>
          <p class="action-desc">{{ action.description }}</p>
        </div>
        <div class="action-arrow">
          <el-icon><ArrowRight /></el-icon>
        </div>
        <div class="card-shine"></div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Calendar, MagicStick, Star, UserFilled, ArrowRight, Lightning } from '@element-plus/icons-vue'

// 自定义太极图标组件
const YinYangIcon = {
  render() {
    return h('svg', { viewBox: '0 0 24 24', width: '1em', height: '1em' }, [
      h('circle', { cx: '12', cy: '12', r: '10', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }),
      h('path', { d: 'M12 2a10 10 0 0 1 0 20 5 5 0 0 1 0-10 5 5 0 0 0 0-10z', fill: 'currentColor' }),
      h('circle', { cx: '12', cy: '7', r: '1.5', fill: 'currentColor' }),
      h('circle', { cx: '12', cy: '17', r: '1.5', fill: 'none', stroke: 'currentColor', 'stroke-width': '1' })
    ])
  }
}

// 自定义心形图标组件
const HeartFilled = {
  render() {
    return h('svg', { viewBox: '0 0 24 24', width: '1em', height: '1em' }, [
      h('path', { d: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z', fill: 'currentColor' })
    ])
  }
}

import { h } from 'vue'

const quickActions = [
  {
    path: '/bazi',
    icon: 'calendar',
    title: '八字排盘',
    description: '探索你的命理密码',
    color: '#B8860B',
  },
  {
    path: '/tarot',
    icon: 'magic',
    title: '塔罗占卜',
    description: '揭晓未知的答案',
    color: '#D4AF37',
  },
  {
    path: '/liuyao',
    icon: 'yinyang',
    title: '六爻占卜',
    description: '周易问事解疑惑',
    color: '#8B6914',
  },
  {
    path: '/hehun',
    icon: 'heart',
    title: '八字合婚',
    description: '分析婚姻匹配度',
    color: '#D4AF37',
  },
  {
    path: '/daily',
    icon: 'star',
    title: '每日运势',
    description: '掌握今日运程',
    color: '#f6ad55',
  },
  {
    path: '/profile',
    icon: 'user',
    title: '个人中心',
    description: '查看积分与成就',
    color: '#4fd1c5',
  },
]
</script>

<style scoped>
.quick-actions {
  margin-bottom: 32px;
}

.section-title {
  color: #fff;
  font-size: 20px;
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-icon {
  font-size: 24px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  text-decoration: none;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.action-card:hover {
  transform: translateY(-4px);
  border-color: var(--card-color);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.action-card:hover .action-icon-wrapper {
  transform: scale(1.1);
}

.action-card:hover .action-arrow {
  transform: translateX(4px);
}

.action-icon-wrapper {
  position: relative;
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--card-color), rgba(255, 255, 255, 0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.3s ease;
}

.action-icon {
  font-size: 28px;
  z-index: 1;
}

.icon-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, var(--card-color) 0%, transparent 70%);
  opacity: 0.3;
  filter: blur(10px);
}

.action-content {
  flex: 1;
  min-width: 0;
}

.action-title {
  color: #fff;
  font-size: 16px;
  margin: 0 0 4px 0;
}

.action-desc {
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
  margin: 0;
}

.action-arrow {
  color: var(--card-color);
  font-size: 20px;
  transition: transform 0.3s ease;
}

.card-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.1),
    transparent
  );
  transition: left 0.5s ease;
}

.action-card:hover .card-shine {
  left: 100%;
}

@media (max-width: 640px) {
  .actions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
