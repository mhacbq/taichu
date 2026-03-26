<script setup lang="ts">
import { Grid, UserFilled } from '@element-plus/icons-vue'

const props = defineProps({
  bazi: { type: Object, default: null },
  wuxingDistributionItems: { type: Array, default: () => [] },
  getShishenClass: { type: Function, default: () => '' },
  trueSolarTimeInfo: { type: Object, default: null },
})
</script>

<template>
  <div class="tab-pane-content">
    <div class="pane-title">
      <el-icon class="title-icon"><Grid /></el-icon>
      <span class="title-text">命盘核心数据</span>
      <span class="title-desc">日主、八字、五行分布</span>
    </div>

    <!-- 真太阳时调整提示 -->
    <div v-if="trueSolarTimeInfo?.adjusted" class="true-solar-notice">
      <el-alert
        title="☀️ 真太阳时已修正"
        type="success"
        :description="trueSolarTimeInfo.adjustment_desc"
        show-icon
        :closable="false"
      />
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
      <!-- 日主强弱状态提示 -->
      <div v-if="wuxingDistributionItems[0]?.strengthStatus" class="strength-status-bar">
        <span class="strength-status-label">日主强弱：</span>
        <span class="strength-status-value" :class="['身旺','中和偏旺'].includes(wuxingDistributionItems[0]?.strengthStatus) ? 'status--strong' : 'status--weak'">
          {{ wuxingDistributionItems[0]?.strengthStatus }}
        </span>
        <span class="strength-status-hint">
          {{ ['身旺','中和偏旺'].includes(wuxingDistributionItems[0]?.strengthStatus) ? '· 宜泄耗克制，以官杀食伤财星为喜' : '· 宜生扶助旺，以印星比劫为喜' }}
        </span>
      </div>
      <div class="wuxing-bars">
        <div
          v-for="item in wuxingDistributionItems"
          :key="item.name"
          class="wuxing-bar-item"
          :class="['wx-' + item.name, { 'is-favorite': item.isFavorite, 'is-missing': item.isMissing }]"
        >
          <div class="wuxing-bar-main">
            <span class="wuxing-name">
              <span class="wuxing-name__icon" :class="'icon-' + item.name">
                {{ item.name === '金' ? '✨' : item.name === '木' ? '🌿' : item.name === '水' ? '💧' : item.name === '火' ? '🔥' : '⛰️' }}
              </span>
              <span class="wuxing-name__text">{{ item.name }}</span>
              <!-- 状态 badge -->
              <span v-if="item.badge" class="wuxing-badge-tag" :class="'badge-' + item.badge.type">
                {{ item.badge.text }}
              </span>
            </span>
            <div class="wuxing-bar">
              <div
                class="wuxing-fill"
                :class="[item.name, { 'fill--favorite': item.isFavorite, 'fill--missing': item.isMissing }]"
                :style="{ width: `${item.width}%`, '--target-width': `${item.width}%` }"
              ></div>
            </div>
          </div>
          <div class="wuxing-meta">
            <span class="wuxing-count">{{ item.displayValue }}</span>
            <span class="wuxing-share">{{ item.shareText }}</span>
          </div>
        </div>
      </div>
      <!-- 图例说明 -->
      <div class="wuxing-legend">
        <span class="legend-item"><span class="legend-dot dot-favorite"></span>喜用（宜补充）</span>
        <span class="legend-item"><span class="legend-dot dot-missing"></span>缺失（需留意）</span>
        <span class="legend-item"><span class="legend-dot dot-dominant"></span>偏旺（宜克泄）</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 真太阳时提示 */
.true-solar-notice {
  margin-bottom: 16px;
}

/* 日主强弱状态栏 */
.strength-status-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  margin-bottom: 14px;
  background: rgba(212, 175, 55, 0.06);
  border-radius: 8px;
  border: 1px solid rgba(212, 175, 55, 0.15);
  font-size: 13px;
  flex-wrap: wrap;
}

.strength-status-label {
  color: var(--text-tertiary);
}

.strength-status-value {
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
}

.status--strong {
  color: #c0392b;
  background: rgba(192, 57, 43, 0.1);
}

.status--weak {
  color: #2980b9;
  background: rgba(41, 128, 185, 0.1);
}

.strength-status-hint {
  color: var(--text-tertiary);
  font-size: 12px;
}

/* 五行条目 badge 标签 */
.wuxing-badge-tag {
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 6px;
  vertical-align: middle;
  line-height: 1.6;
}

.badge-favorite {
  background: rgba(212, 175, 55, 0.18);
  color: #b8860b;
  border: 1px solid rgba(212, 175, 55, 0.4);
}

.badge-missing {
  background: rgba(41, 128, 185, 0.12);
  color: #1a6fa8;
  border: 1px solid rgba(41, 128, 185, 0.3);
}

.badge-dominant {
  background: rgba(192, 57, 43, 0.1);
  color: #c0392b;
  border: 1px solid rgba(192, 57, 43, 0.25);
}

.badge-weak {
  background: rgba(127, 140, 141, 0.1);
  color: #7f8c8d;
  border: 1px solid rgba(127, 140, 141, 0.25);
}

/* 喜用条目高亮 */
.wuxing-bar-item.is-favorite .wuxing-name__text {
  color: #b8860b;
  font-weight: 700;
}

.wuxing-bar-item.is-favorite .wuxing-fill {
  box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
}

/* 缺失条目样式 */
.wuxing-bar-item.is-missing .wuxing-name__text {
  color: #1a6fa8;
}

.wuxing-bar-item.is-missing .wuxing-bar {
  background: rgba(41, 128, 185, 0.08);
}

/* 图例 */
.wuxing-legend {
  display: flex;
  gap: 16px;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid var(--border-light, rgba(0,0,0,0.06));
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-tertiary, #999);
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot-favorite {
  background: #D4AF37;
}

.dot-missing {
  background: #2980b9;
}

.dot-dominant {
  background: #c0392b;
}
</style>
