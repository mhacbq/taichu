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

/* =============================================
   排盘表格样式（来自 style.css 迁移）
   ============================================= */

/* 日主信息 */
.day-master-info {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.day-master-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 32px;
  border-radius: 20px;
  background: rgba(255, 250, 241, 0.95);
  border: 1px solid rgba(212, 175, 55, 0.25);
  box-shadow: 0 4px 16px rgba(145, 103, 34, 0.08);
}

.day-master-card__ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 248, 228, 0.9);
  border: 2px solid rgba(212, 175, 55, 0.3);
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.12);
}

.day-master-card__char {
  font-size: 32px;
  font-weight: 800;
  color: #6b4a12;
}

.day-master-card__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.day-master-card__label {
  font-size: 13px;
  color: #8a7a62;
  font-weight: 600;
  letter-spacing: 0.1em;
}

.day-master-card__wuxing {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.2);
  color: #8a5c16;
}

/* 排盘表 */
.bazi-paipan {
  background: #1e1508;
  border-radius: 16px;
  padding: 16px 14px;
  margin-bottom: 20px;
  border: 1px solid rgba(212, 175, 55, 0.3);
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(212, 175, 55, 0.12);
  position: relative;
  overflow: hidden;
}

.bazi-paipan__watermark {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 120px;
  font-weight: 900;
  color: rgba(212, 175, 55, 0.04);
  letter-spacing: 0.3em;
  pointer-events: none;
  white-space: nowrap;
}

.bazi-paipan::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.5), transparent);
}

.paipan-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 4px;
  position: relative;
  opacity: 0;
  transform: translateY(30px);
  animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.paipan-row:nth-child(1) { animation-delay: 0.1s; }
.paipan-row:nth-child(2) { animation-delay: 0.2s; }
.paipan-row:nth-child(3) { animation-delay: 0.3s; }
.paipan-row:nth-child(4) { animation-delay: 0.4s; }
.paipan-row:nth-child(5) { animation-delay: 0.5s; }
.paipan-row:nth-child(6) { animation-delay: 0.6s; }

.paipan-row:last-child {
  margin-bottom: 0;
}

.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 12px 8px;
  font-size: 22px;
  font-weight: bold;
  color: rgba(232, 197, 110, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  position: relative;
  transition: background 0.2s ease;
  border-radius: 12px;
}

.paipan-cell:hover {
  background: rgba(212, 175, 55, 0.08);
}

.paipan-cell.header {
  font-size: 14px;
  color: #8a7a62;
  font-weight: 700;
  padding: 12px 10px;
  letter-spacing: 0.12em;
}

.header-label {
  display: block;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  border-radius: 5px;
  background: rgba(212, 175, 55, 0.25);
  color: #e8c56e;
  font-size: 9px;
  font-weight: 700;
  margin-top: 3px;
}

.paipan-cell.highlight {
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.35);
  box-shadow: 0 0 12px rgba(212, 175, 55, 0.12);
  border-radius: 10px;
}

.gan-text, .zhi-text {
  font-size: 22px;
  font-weight: 800;
  color: #e8c56e;
  text-shadow: 0 0 8px rgba(212, 175, 55, 0.3);
}

.wuxing-badge {
  font-size: 10px;
  padding: 2px 7px;
  border-radius: 8px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.wuxing-badge.金 { background: rgba(218, 165, 32, 0.2); color: #e8c56e; }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.2); color: #5dba5d; }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.2); color: #5aabf0; }
.wuxing-badge.火 { background: rgba(220, 20, 60, 0.2); color: #f06080; }
.wuxing-badge.土 { background: rgba(180, 120, 60, 0.2); color: #c8956a; }

.wuxing-badge.zhi {
  opacity: 0.85;
}

.rizhu-tag {
  font-size: 9px;
  background: linear-gradient(135deg, #d4af37, #c8960c);
  color: #1c1408;
  padding: 1px 6px;
  border-radius: 5px;
  position: absolute;
  top: 4px;
  right: 4px;
  font-weight: 700;
  letter-spacing: 0.05em;
  box-shadow: 0 1px 4px rgba(212, 175, 55, 0.4);
}

/* 十神行 */
.shishen-row {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.06), rgba(245, 196, 103, 0.04)) !important;
  border-radius: 14px !important;
  margin: 8px 0 !important;
  border: 1px solid rgba(212, 175, 55, 0.08) !important;
}

.shishen-cell {
  font-size: 14px !important;
  color: #6b4a12 !important;
  padding: 12px 8px !important;
  font-weight: 700 !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 4px !important;
}

.shishen-label {
  font-size: 10px;
  color: rgba(212, 175, 55, 0.45);
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.shishen-value {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 2px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  min-width: 40px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(212, 175, 55, 0.2);
  color: rgba(232, 197, 110, 0.85);
}

.shishen-value--self {
  background: rgba(212, 175, 55, 0.2);
  color: #e8c56e;
  border-color: rgba(212, 175, 55, 0.4);
}

/* 十神色彩系统 */
.shishen--bijian { background: rgba(64, 158, 255, 0.18); color: #7ec8ff; border-color: rgba(64, 158, 255, 0.3); }
.shishen--jiecai { background: rgba(144, 78, 255, 0.18); color: #c49aff; border-color: rgba(144, 78, 255, 0.3); }
.shishen--shishen { background: rgba(103, 194, 58, 0.18); color: #8de05a; border-color: rgba(103, 194, 58, 0.3); }
.shishen--shangguan { background: rgba(255, 153, 0, 0.18); color: #ffb84d; border-color: rgba(255, 153, 0, 0.3); }
.shishen--piancai { background: rgba(255, 215, 0, 0.18); color: #ffe066; border-color: rgba(255, 215, 0, 0.3); }
.shishen--zhengcai { background: rgba(218, 165, 32, 0.18); color: #e8c56e; border-color: rgba(218, 165, 32, 0.3); }
.shishen--qisha { background: rgba(220, 20, 60, 0.18); color: #ff7090; border-color: rgba(220, 20, 60, 0.3); }
.shishen--zhengguan { background: rgba(178, 34, 34, 0.18); color: #ff8080; border-color: rgba(178, 34, 34, 0.3); }
.shishen--pianyin { background: rgba(30, 144, 255, 0.18); color: #70c0ff; border-color: rgba(30, 144, 255, 0.3); }
.shishen--zhengyin { background: rgba(34, 139, 34, 0.18); color: #70d870; border-color: rgba(34, 139, 34, 0.3); }

/* 藏干行 */
.canggan-row {
  margin-top: 2px;
}

.canggan-cell {
  font-size: 11px;
  padding: 8px 4px;
  min-height: 48px;
}

.canggan-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.canggan-item {
  color: rgba(232, 197, 110, 0.75);
}

.canggan-item small {
  color: rgba(212, 175, 55, 0.45);
  font-size: 9px;
  margin-left: 2px;
}

/* 纳音行 */
.nayin-row {
  margin-top: 2px;
  background: rgba(212, 175, 55, 0.05);
  border-radius: 8px;
}

.nayin-cell {
  font-size: 11px;
  color: rgba(212, 175, 55, 0.55);
  padding: 6px 4px;
  font-weight: 500;
}

/* 旬空行 */
.xunkong-row {
  margin-top: 2px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 7px;
}

.xunkong-cell {
  font-size: 10px;
  color: rgba(240, 96, 128, 0.8);
  padding: 6px 4px;
}

.xunkong-label {
  color: rgba(212, 175, 55, 0.4);
  margin-right: 4px;
}

/* 五行统计 */
.wuxing-stats {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 24px;
  margin: 24px 0;
  border: 1px solid rgba(212, 175, 55, 0.2);
  box-shadow: 0 4px 16px rgba(145, 103, 34, 0.06);
}

.wuxing-header {
  text-align: center;
  margin-bottom: 28px;
  position: relative;
}

.wuxing-header__icon {
  font-size: 36px;
  margin-bottom: 8px;
  filter: grayscale(0.2);
}

.wuxing-stats h3 {
  margin-bottom: 8px;
  color: #3a2a10;
  font-size: 20px;
  font-weight: 800;
}

.wuxing-caption {
  margin: 0;
  color: #8a7a62;
  font-size: 13px;
  line-height: 1.8;
  font-weight: 400;
}

.wuxing-bars {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.wuxing-bar-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(255, 250, 241, 0.7);
  transition: border-color 0.2s ease;
  border: 1px solid rgba(212, 175, 55, 0.1);
}

.wuxing-bar-item:hover {
  border-color: rgba(212, 175, 55, 0.25);
}

.wuxing-bar-item.wx-金 { border-left: 3px solid #daa520; }
.wuxing-bar-item.wx-木 { border-left: 3px solid #228b22; }
.wuxing-bar-item.wx-水 { border-left: 3px solid #1e90ff; }
.wuxing-bar-item.wx-火 { border-left: 3px solid #dc143c; }
.wuxing-bar-item.wx-土 { border-left: 3px solid #8b4513; }

.wuxing-bar-main {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.wuxing-name {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 70px;
}

.wuxing-name__icon {
  font-size: 18px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.wuxing-name__icon.icon-金 { background: rgba(218, 165, 32, 0.12); }
.wuxing-name__icon.icon-木 { background: rgba(34, 139, 34, 0.12); }
.wuxing-name__icon.icon-水 { background: rgba(30, 144, 255, 0.12); }
.wuxing-name__icon.icon-火 { background: rgba(220, 20, 60, 0.1); }
.wuxing-name__icon.icon-土 { background: rgba(139, 69, 19, 0.1); }

.wuxing-name__text {
  font-weight: 700;
  color: #3a2a10;
  font-size: 16px;
}

.wuxing-bar {
  flex: 1;
  height: 24px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.06));
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  border: 1px solid rgba(212, 175, 55, 0.06);
}

.wuxing-fill {
  height: 100%;
  border-radius: 12px;
  animation: fillBar 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: 0.7s;
  width: 0;
  position: relative;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.wuxing-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.25), transparent);
  border-radius: 12px 12px 0 0;
}

.wuxing-fill.金 { background: linear-gradient(135deg, #f5c842, #daa520, #b8860b); }
.wuxing-fill.木 { background: linear-gradient(135deg, #5cb85c, #228b22, #006400); }
.wuxing-fill.水 { background: linear-gradient(135deg, #4fc3f7, #1e90ff, #0d6ebd); }
.wuxing-fill.火 { background: linear-gradient(135deg, #ff6b6b, #dc143c, #b01030); }
.wuxing-fill.土 { background: linear-gradient(135deg, #d4a056, #8b4513, #6b3410); }

.wuxing-fill.fill--favorite {
  box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
}

.wuxing-meta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  justify-content: flex-end;
  padding: 6px 14px;
  border-radius: 12px;
  background: rgba(255, 250, 241, 0.8);
  border: 1px solid rgba(212, 175, 55, 0.08);
  min-width: 120px;
}

.wuxing-count {
  min-width: 36px;
  text-align: right;
  color: #6b4a12;
  font-weight: 800;
  font-size: 16px;
}

.wuxing-share {
  color: #8a7a62;
  font-size: 12px;
  font-weight: 600;
}

/* 动画 */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

@keyframes fillBar {
  0% { width: 0; transform: scaleX(0); }
  50% { transform: scaleX(1.05); }
  100% { width: var(--target-width); transform: scaleX(1); }
}

/* 响应式 */
@media (max-width: 768px) {
  .paipan-cell {
    font-size: 12px;
    padding: 8px 3px;
    min-height: unset;
  }

  .gan-text, .zhi-text {
    font-size: 18px;
    line-height: 1.2;
  }

  .wuxing-badge {
    font-size: 9px;
    padding: 1px 4px;
    transform: scale(0.9);
  }

  .shishen-cell {
    font-size: 12px !important;
    padding: 8px 4px !important;
  }

  .shishen-value {
    font-size: 11px;
    padding: 2px 8px;
    min-width: 40px;
  }

  .shishen-label {
    font-size: 9px;
  }

  .canggan-cell {
    font-size: 9px;
    padding: 6px 2px;
    min-height: 40px;
  }

  .nayin-cell {
    font-size: 9px;
    padding: 5px 2px;
  }

  .day-master-info {
    margin-bottom: 20px;
  }

  .day-master-card {
    padding: 16px 24px;
    gap: 12px;
    width: 100%;
    justify-content: center;
  }

  .wuxing-bar-item {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .wuxing-bar-main,
  .wuxing-meta {
    width: 100%;
  }

  .wuxing-meta {
    justify-content: flex-start;
  }
}
</style>
