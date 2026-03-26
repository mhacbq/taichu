<template>
  <div ref="chartRef" :style="{ width, height }"></div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart, PieChart, ScatterChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import * as echarts from 'echarts'

// 按需注册echarts组件
use([CanvasRenderer, LineChart, BarChart, PieChart, ScatterChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

const props = defineProps({
  option: { type: Object, required: true },
  width: { type: String, default: '100%' },
  height: { type: String, default: '300px' },
  theme: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  autoResize: { type: Boolean, default: true }
})

const emit = defineEmits(['click', 'mouseover', 'mouseout'])

const chartRef = ref(null)
let chartInstance = null

// 初始化图表
function initChart() {
  if (!chartRef.value) return
  
  chartInstance = echarts.init(chartRef.value, props.theme)
  chartInstance.setOption(props.option)
  
  // 绑定事件
  chartInstance.on('click', (params) => emit('click', params))
  chartInstance.on('mouseover', (params) => emit('mouseover', params))
  chartInstance.on('mouseout', (params) => emit('mouseout', params))
  
  // 自动调整大小
  if (props.autoResize) {
    window.addEventListener('resize', handleResize)
  }
}

// 调整大小
function handleResize() {
  chartInstance?.resize()
}

// 更新图表配置
function updateChart() {
  if (chartInstance) {
    chartInstance.setOption(props.option, true)
  }
}

// 显示加载状态
function showLoading() {
  chartInstance?.showLoading({
    text: '加载中...',
    color: '#409eff',
    textColor: '#409eff',
    maskColor: 'rgba(255, 255, 255, 0.8)'
  })
}

// 隐藏加载状态
function hideLoading() {
  chartInstance?.hideLoading()
}

watch(() => props.option, () => {
  nextTick(updateChart)
}, { deep: true })

watch(() => props.loading, (val) => {
  if (val) {
    showLoading()
  } else {
    hideLoading()
  }
})

onMounted(() => {
  nextTick(initChart)
})

onUnmounted(() => {
  if (props.autoResize) {
    window.removeEventListener('resize', handleResize)
  }
  chartInstance?.dispose()
})

// 暴露方法
defineExpose({
  getInstance: () => chartInstance,
  resize: handleResize,
  updateOption: (option) => chartInstance?.setOption(option),
  clear: () => chartInstance?.clear()
})
</script>
