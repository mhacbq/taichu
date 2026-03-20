<template>
  <div class="bazi-professional-display">
    <!-- 排盘头部信息 -->
    <div class="paipan-header">
      <div class="header-left">
        <div class="datetime-info">
          <div class="info-row">
            <span class="label">公历:</span>
            <span class="value">{{ solarDate }}</span>
          </div>
          <div class="info-row">
            <span class="label">农历:</span>
            <span class="value">{{ lunarDate }}</span>
          </div>
          <div class="info-row">
            <span class="label">节气:</span>
            <span class="value" :class="{ 'highlight': isNearJieqi }">
              {{ jieqiInfo }}
              <el-tooltip v-if="isNearJieqi" content="八字按节气换月，当前已过节气，按新月份计算" placement="top">
                <el-icon class="warning-icon"><Warning /></el-icon>
              </el-tooltip>
            </span>
          </div>
        </div>
      </div>
      <div class="header-right">
        <div class="jmc-info">
          <div class="jmc-item">
            <span class="label">胎元:</span>
            <span class="value">{{ taisyuan }}</span>
          </div>
          <div class="jmc-item">
            <span class="label">命宫:</span>
            <span class="value">{{ minggong }}</span>
          </div>
          <div class="jmc-item">
            <span class="label">空亡:</span>
            <span class="value">{{ kongwang }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 四柱排盘主体 -->
    <div class="sizhu-container">
      <!-- 表头 -->
      <div class="sizhu-header">
        <div class="column header-cell"></div>
        <div class="column header-cell">
          <span class="zhu-label">年柱</span>
          <span class="zhu-age">{{ ages.year }}岁</span>
        </div>
        <div class="column header-cell">
          <span class="zhu-label">月柱</span>
          <span class="zhu-age">{{ ages.month }}岁</span>
        </div>
        <div class="column header-cell">
          <span class="zhu-label">日柱</span>
          <span class="zhu-age highlight">日主</span>
        </div>
        <div class="column header-cell">
          <span class="zhu-label">时柱</span>
          <span class="zhu-age">{{ ages.hour }}岁</span>
        </div>
      </div>

      <!-- 天干行 -->
      <div class="sizhu-row tiangan-row">
        <div class="row-label">天干</div>
        <div 
          v-for="(tg, index) in tiangan" 
          :key="'tg-'+index" 
          class="column cell"
          :class="{ 'day-master': index === 2 }"
        >
          <span class="ganzhi-text">{{ tg.name }}</span>
          <span class="wuxing-badge" :class="tg.wuxing">{{ tg.wuxing }}</span>
          <span class="shishen-badge" :class="tg.shishen.type">{{ tg.shishen.name }}</span>
          <span v-if="index === 2" class="day-master-tag">日主</span>
        </div>
      </div>

      <!-- 地支行 -->
      <div class="sizhu-row dizhi-row">
        <div class="row-label">地支</div>
        <div 
          v-for="(dz, index) in dizhi" 
          :key="'dz-'+index" 
          class="column cell"
          :class="{ 'day-branch': index === 2 }"
        >
          <span class="ganzhi-text">{{ dz.name }}</span>
          <span class="wuxing-badge" :class="dz.wuxing">{{ dz.wuxing }}</span>
          <span class="shishen-badge" :class="dz.shishen.type">{{ dz.shishen.name }}</span>
          <span class="zhang-sheng" :class="dz.zhangsheng.type">{{ dz.zhangsheng.name }}</span>
        </div>
      </div>

      <!-- 藏干行 -->
      <div class="sizhu-row canggan-row">
        <div class="row-label">
          藏干
          <el-tooltip content="地支中暗藏的天干，称为人元" placement="left">
            <el-icon class="help-icon"><QuestionFilled /></el-icon>
          </el-tooltip>
        </div>
        <div 
          v-for="(cg, index) in canggan" 
          :key="'cg-'+index" 
          class="column cell"
        >
          <div class="canggan-list">
            <div v-for="(gan, gIndex) in cg.gans" :key="gIndex" class="canggan-item">
              <span class="gan-name">{{ gan.name }}</span>
              <span class="gan-shishen" :class="gan.shishenType">{{ gan.shishen }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 纳音行 -->
      <div class="sizhu-row nayin-row">
        <div class="row-label">纳音</div>
        <div 
          v-for="(ny, index) in nayin" 
          :key="'ny-'+index" 
          class="column cell"
        >
          <span class="nayin-text">{{ ny }}</span>
        </div>
      </div>
    </div>

    <!-- 特殊星曜区域 -->
    <div class="shensha-section" v-if="shensha && shensha.length > 0">
      <h4 class="section-title">
        <el-icon><StarFilled /></el-icon>
        特殊星曜
        <el-tooltip content="特殊星曜是传统文化中的特殊符号，用于文化分析参考" placement="top">
          <el-icon class="help-icon"><QuestionFilled /></el-icon>
        </el-tooltip>
      </h4>
      <div class="shensha-list">
        <div 
          v-for="(ss, index) in shensha" 
          :key="index"
          class="shensha-item"
          :class="ss.type"
        >
          <span class="shensha-name">{{ ss.name }}</span>
          <span class="shensha-position">{{ ss.position }}</span>
          <el-tooltip :content="ss.description" placement="top">
            <el-icon class="info-icon"><InfoFilled /></el-icon>
          </el-tooltip>
        </div>
      </div>
    </div>

    <!-- 格局分析 -->
    <div class="geju-section" v-if="geju">
      <h4 class="section-title">
        <el-icon><Grid /></el-icon>
        格局分析
      </h4>
      <div class="geju-content">
        <div class="geju-main">
          <span class="geju-name">{{ geju.name }}</span>
          <span class="geju-status" :class="geju.status">{{ geju.statusText }}</span>
        </div>
        <div class="geju-details">
          <div class="detail-item">
            <span class="label">身旺身弱:</span>
            <span class="value" :class="shengruo.type">{{ shengruo.text }}</span>
          </div>
          <div class="detail-item">
            <span class="label">喜用神:</span>
            <span class="value xiyongshen">{{ xiyongshen }}</span>
          </div>
          <div class="detail-item">
            <span class="label">忌神:</span>
            <span class="value jishen">{{ jishen }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 节气提示 -->
    <div class="jieqi-notice" v-if="isNearJieqi">
      <el-alert
        :title="jieqiNoticeTitle"
        type="warning"
        :description="jieqiNoticeDesc"
        show-icon
        :closable="false"
      />
    </div>

    <!-- 时辰提示 -->
    <div class="shichen-notice" v-if="showShichenNotice">
      <el-alert
        title="⚠️ 时辰选择提醒"
        type="info"
        description="八字排盘对时辰敏感，2小时为一个时辰。请尽量确认准确的出生时间，特别是在时辰交界（如23:00、1:00等）附近出生的，请核实。"
        show-icon
        :closable="false"
      />
    </div>

    <!-- 夏令时提示 -->
    <div class="dst-notice" v-if="isDSTPeriod">
      <el-alert
        title="⚠️ 夏令时提示"
        type="warning"
        description="1986-1991年中国实行夏令时，出生在这些年份4月中旬至9月中旬的，时间需要减1小时。"
        show-icon
        :closable="false"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Warning,
  QuestionFilled,
  StarFilled,
  InfoFilled,
  Grid
} from '@element-plus/icons-vue'

const props = defineProps({
  // 基础信息
  solarDate: { type: String, default: '' },
  lunarDate: { type: String, default: '' },
  jieqiInfo: { type: String, default: '' },
  isNearJieqi: { type: Boolean, default: false },
  jieqiNoticeTitle: { type: String, default: '' },
  jieqiNoticeDesc: { type: String, default: '' },
  
  // 辅助信息
  taisyuan: { type: String, default: '' },
  minggong: { type: String, default: '' },
  kongwang: { type: String, default: '' },
  
  // 四柱数据
  tiangan: { type: Array, default: () => [] },
  dizhi: { type: Array, default: () => [] },
  canggan: { type: Array, default: () => [] },
  nayin: { type: Array, default: () => [] },
  ages: { type: Object, default: () => ({ year: 0, month: 0, hour: 0 }) },
  
  // 神煞
  shensha: { type: Array, default: () => [] },
  
  // 格局
  geju: { type: Object, default: null },
  shengruo: { type: Object, default: null },
  xiyongshen: { type: String, default: '' },
  jishen: { type: String, default: '' },
  
  // 提示
  showShichenNotice: { type: Boolean, default: true },
  isDSTPeriod: { type: Boolean, default: false }
})
</script>

<style scoped>
.bazi-professional-display {
  background: linear-gradient(135deg, #faf8f5 0%, #f5f0e8 100%);
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e8e0d5;
}

/* 头部信息 */
.paipan-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e8e0d5;
}

.datetime-info .info-row {
  margin-bottom: 8px;
}

.datetime-info .label {
  color: #8b7355;
  font-size: 14px;
  margin-right: 8px;
}

.datetime-info .value {
  color: #4a4a4a;
  font-size: 15px;
  font-weight: 500;
}

.datetime-info .value.highlight {
  color: #d4a574;
}

.warning-icon {
  color: #e6a23c;
  margin-left: 4px;
  cursor: help;
}

.jmc-info {
  text-align: right;
}

.jmc-item {
  margin-bottom: 6px;
}

.jmc-item .label {
  color: #8b7355;
  font-size: 13px;
  margin-right: 6px;
}

.jmc-item .value {
  color: #5a5a5a;
  font-size: 14px;
  font-family: 'Noto Serif SC', serif;
}

/* 四柱排盘 */
.sizhu-container {
  margin-bottom: 24px;
}

.sizhu-header,
.sizhu-row {
  display: grid;
  grid-template-columns: 80px repeat(4, 1fr);
  gap: 8px;
}

.column {
  text-align: center;
  padding: 12px 8px;
}

.header-cell {
  background: linear-gradient(135deg, #8b6914 0%, #a67c3a 100%);
  color: white;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.zhu-label {
  font-size: 16px;
  font-weight: 600;
}

.zhu-age {
  font-size: 12px;
  opacity: 0.9;
}

.zhu-age.highlight {
  color: #ffd700;
  font-weight: 600;
}

.row-label {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #8b7355;
  font-weight: 500;
}

.cell {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  position: relative;
}

.ganzhi-text {
  font-size: 24px;
  font-weight: 700;
  color: #4a4a4a;
  font-family: 'Noto Serif SC', serif;
}

.wuxing-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 500;
}

.wuxing-badge.jin { background: #fff3e0; color: #ff8f00; }
.wuxing-badge.mu { background: #e8f5e9; color: #2e7d32; }
.wuxing-badge.shui { background: #e3f2fd; color: #1565c0; }
.wuxing-badge.huo { background: #ffebee; color: #c62828; }
.wuxing-badge.tu { background: #f5f5dc; color: #8d6e63; }

.shishen-badge {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
}

.shishen-badge.bi { background: #e3f2fd; color: #1976d2; }
.shishen-badge.jie { background: #e8eaf6; color: #3f51b5; }
.shishen-badge.shi { background: #f3e5f5; color: #7b1fa2; }
.shishen-badge.shang { background: #fce4ec; color: #c2185b; }
.shishen-badge.cai { background: #fff8e1; color: #f9a825; }
.shishen-badge.piancai { background: #fff3e0; color: #f57c00; }
.shishen-badge.guan { background: #e8f5e9; color: #388e3c; }
.shishen-badge.qisha { background: #ffebee; color: #d32f2f; }
.shishen-badge.yin { background: #f5f5f5; color: #616161; }

.day-master-tag {
  position: absolute;
  top: -8px;
  right: -8px;
  background: linear-gradient(135deg, #d4a574 0%, #c49a6c 100%);
  color: white;
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 10px;
}

.cell.day-master {
  background: linear-gradient(135deg, #fff8e1 0%, #fff3e0 100%);
  border: 2px solid #d4a574;
}

.zhang-sheng {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 4px;
  background: #f5f5f5;
  color: #666;
}

.zhang-sheng.wang {
  background: #e8f5e9;
  color: #2e7d32;
}

.zhang-sheng.shuai {
  background: #ffebee;
  color: #c62828;
}

/* 藏干 */
.canggan-row .cell {
  padding: 16px 8px;
}

.canggan-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.canggan-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.gan-name {
  font-size: 16px;
  font-weight: 600;
  color: #5a5a5a;
}

.gan-shishen {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 4px;
  background: #f5f5f5;
  color: #666;
}

/* 纳音 */
.nayin-text {
  font-size: 13px;
  color: #8b6914;
  font-weight: 500;
}

/* 神煞 */
.shensha-section {
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  color: #4a4a4a;
  margin-bottom: 16px;
}

.shensha-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.shensha-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
}

.shensha-item.ji {
  background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
  color: #c62828;
}

.shensha-item.xiong {
  background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
  color: #e65100;
}

.shensha-item.zhongxing {
  background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
  color: #616161;
}

.shensha-item.ping {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1565c0;
}

.shensha-item.jixing {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #2e7d32;
}

.shensha-item.daji {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  color: #ff8f00;
}

.shensha-position {
  font-size: 11px;
  opacity: 0.8;
}

/* 格局 */
.geju-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.geju-main {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.geju-name {
  font-size: 20px;
  font-weight: 700;
  color: #4a4a4a;
}

.geju-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
}

.geju-status.chengge {
  background: #e8f5e9;
  color: #2e7d32;
}

.geju-status.buge {
  background: #ffebee;
  color: #c62828;
}

.geju-details {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-item .label {
  font-size: 13px;
  color: #8b7355;
}

.detail-item .value {
  font-size: 15px;
  font-weight: 600;
}

.detail-item .value.wang {
  color: #2e7d32;
}

.detail-item .value.ruo {
  color: #c62828;
}

.detail-item .value.pingheng {
  color: #f57c00;
}

.detail-item .value.xiyongshen {
  color: #2e7d32;
}

.detail-item .value.jishen {
  color: #c62828;
}

/* 提示 */
.jieqi-notice,
.shichen-notice,
.dst-notice {
  margin-bottom: 16px;
}
</style>
