<script setup lang="ts">
import { Grid, UserFilled } from '@element-plus/icons-vue'

const props = defineProps({
  bazi: { type: Object, default: null },
  wuxingDistributionItems: { type: Array, default: () => [] },
  getShishenClass: { type: Function, default: () => '' },
})
</script>

<template>
  <div class="tab-pane-content">
    <div class="pane-title">
      <el-icon class="title-icon"><Grid /></el-icon>
      <span class="title-text">命盘核心数据</span>
      <span class="title-desc">日主、八字、五行分布</span>
    </div>

    <!-- 日主信息 -->
    <div class="day-master-info">
      <div class="day-master-card" :class="'wx-' + bazi?.day_master_wuxing">
        <div class="day-master-card__ring">
          <span class="day-master-card__char">{{ bazi?.day_master }}</span>
        </div>
        <div class="day-master-card__meta">
          <span class="day-master-card__label">日主</span>
          <span class="day-master-card__wuxing">{{ bazi?.day_master_wuxing }}</span>
        </div>
      </div>
    </div>

    <!-- 八字排盘表 -->
    <div class="bazi-paipan">
      <div class="bazi-paipan__watermark">八字</div>
      <div class="paipan-row">
        <div class="paipan-cell header"><span class="header-label">年柱</span></div>
        <div class="paipan-cell header"><span class="header-label">月柱</span></div>
        <div class="paipan-cell header"><span class="header-label">日柱</span><span class="header-badge">命主</span></div>
        <div class="paipan-cell header"><span class="header-label">时柱</span></div>
      </div>
      <!-- 天干行 -->
      <div class="paipan-row">
        <div v-for="col in ['year', 'month', 'day', 'hour']" :key="col" class="paipan-cell" :class="{ highlight: col === 'day' }">
          <span class="gan-text">{{ bazi?.[col]?.gan }}</span>
          <span class="wuxing-badge" :class="bazi?.[col]?.gan_wuxing">{{ bazi?.[col]?.gan_wuxing }}</span>
          <span v-if="col === 'day'" class="rizhu-tag">日主</span>
        </div>
      </div>
      <!-- 十神行 -->
      <div class="paipan-row shishen-row">
        <div v-for="col in ['year', 'month', 'day', 'hour']" :key="col" class="paipan-cell shishen-cell" :class="{ highlight: col === 'day' }">
          <span class="shishen-label">十神</span>
          <span v-if="col === 'day'" class="shishen-value shishen-value--self">日主</span>
          <span v-else class="shishen-value" :class="getShishenClass(bazi?.[col]?.shishen)">{{ bazi?.[col]?.shishen || '-' }}</span>
        </div>
      </div>
      <!-- 地支行 -->
      <div class="paipan-row">
        <div v-for="col in ['year', 'month', 'day', 'hour']" :key="col" class="paipan-cell" :class="{ highlight: col === 'day' }">
          <span class="zhi-text">{{ bazi?.[col]?.zhi }}</span>
          <span class="wuxing-badge zhi" :class="bazi?.[col]?.zhi_wuxing">{{ bazi?.[col]?.zhi_wuxing }}</span>
        </div>
      </div>
      <!-- 藏干行 -->
      <div class="paipan-row canggan-row">
        <div v-for="col in ['year', 'month', 'day', 'hour']" :key="col" class="paipan-cell canggan-cell" :class="{ highlight: col === 'day' }">
          <div class="canggan-list">
            <span v-for="(cg, idx) in bazi?.[col]?.canggan || []" :key="idx" class="canggan-item">
              {{ cg }}<small>({{ bazi?.[col]?.canggan_shishen?.[idx] }})</small>
            </span>
          </div>
        </div>
      </div>
      <!-- 纳音行 -->
      <div class="paipan-row nayin-row">
        <div v-for="col in ['year', 'month', 'day', 'hour']" :key="col" class="paipan-cell nayin-cell" :class="{ highlight: col === 'day' }">
          {{ bazi?.[col]?.nayin }}
        </div>
      </div>
      <!-- 旬空行 -->
      <div class="paipan-row xunkong-row" v-if="bazi?.xunkong">
        <div v-for="(col, label) in { year: '年空', month: '月空', day: '日空', hour: '时空' }" :key="col" class="paipan-cell xunkong-cell" :class="{ highlight: col === 'day' }">
          <span class="xunkong-label">{{ label }}:</span> {{ bazi?.[col]?.xunkong || '-' }}
        </div>
      </div>
    </div>

    <!-- 五行统计 -->
    <div class="wuxing-stats">
      <div class="wuxing-header">
        <div class="wuxing-header__icon">☯</div>
        <h3>五行分布</h3>
        <p class="wuxing-caption">以下为加权值，综合了天干透出、地支藏干与月令司令权重，并非简单计数。</p>
      </div>
      <div class="wuxing-bars">
        <div v-for="item in wuxingDistributionItems" :key="item.name" class="wuxing-bar-item" :class="'wx-' + item.name">
          <div class="wuxing-bar-main">
            <span class="wuxing-name">
              <span class="wuxing-name__icon" :class="'icon-' + item.name">
                {{ item.name === '金' ? '✨' : item.name === '木' ? '🌿' : item.name === '水' ? '💧' : item.name === '火' ? '🔥' : '⛰️' }}
              </span>
              <span class="wuxing-name__text">{{ item.name }}</span>
            </span>
            <div class="wuxing-bar">
              <div class="wuxing-fill" :class="item.name" :style="{ width: `${item.width}%`, '--target-width': `${item.width}%` }"></div>
            </div>
          </div>
          <div class="wuxing-meta">
            <span class="wuxing-count">{{ item.displayValue }}</span>
            <span class="wuxing-share">{{ item.shareText }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
