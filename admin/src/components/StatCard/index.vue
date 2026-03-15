<template>
  <el-card class="stat-card" shadow="hover">
    <div class="stat-content">
      <div class="stat-icon" :style="{ background: iconBgColor }">
        <el-icon :size="iconSize" :color="iconColor">
          <component :is="icon" />
        </el-icon>
      </div>
      <div class="stat-info">
        <div class="stat-title">{{ title }}</div>
        <div class="stat-value" :style="{ color: valueColor }">{{ displayValue }}</div>
        <div v-if="showTrend" class="stat-trend" :class="trendClass">
          <el-icon v-if="trend > 0"><ArrowUp /></el-icon>
          <el-icon v-else-if="trend < 0"><ArrowDown /></el-icon>
          <span>{{ Math.abs(trend) }}% 较{{ trendText }}</span>
        </div>
        <div v-if="subtitle" class="stat-subtitle">{{ subtitle }}</div>
      </div>
    </div>
    <div v-if="footer" class="stat-footer">
      {{ footer }}
    </div>
    <slot />
  </el-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  subtitle: { type: String, default: '' },
  footer: { type: String, default: '' },
  icon: { type: String, default: 'Histogram' },
  iconSize: { type: Number, default: 24 },
  iconBgColor: { type: String, default: '#409eff' },
  iconColor: { type: String, default: '#fff' },
  valueColor: { type: String, default: '#303133' },
  showTrend: { type: Boolean, default: true },
  trend: { type: Number, default: 0 },
  trendText: { type: String, default: '昨日' },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
  decimals: { type: Number, default: 0 }
})

const displayValue = computed(() => {
  let val = props.value
  if (typeof val === 'number') {
    val = val.toFixed(props.decimals)
  }
  return `${props.prefix}${val}${props.suffix}`
})

const trendClass = computed(() => {
  if (props.trend > 0) return 'up'
  if (props.trend < 0) return 'down'
  return ''
})
</script>

<style lang="scss" scoped>
.stat-card {
  height: 100%;
  
  :deep(.el-card__body) {
    padding: 20px;
  }
}

.stat-content {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-title {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 5px;
  line-height: 1.2;
}

.stat-trend {
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
  
  &.up {
    color: #67c23a;
  }
  
  &.down {
    color: #f56c6c;
  }
  
  &:not(.up):not(.down) {
    color: #909399;
  }
}

.stat-subtitle {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.stat-footer {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #ebeef5;
  font-size: 13px;
  color: #606266;
}
</style>
