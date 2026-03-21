<template>
  <el-tour
    v-model="showTour"
    :steps="tourSteps"
    :current="currentStep"
    @finish="handleFinish"
    @close="handleClose"
  />
</template>

<script setup>
import { useTourGuide } from '../composables/useTourGuide'

const {
  showTour,
  currentStep,
  tourSteps,
  markTourCompleted,
  startTour
} = useTourGuide()

const handleFinish = () => {
  markTourCompleted()
}

const handleClose = () => {
  // 关闭时询问是否跳过
  // 这里简化为直接关闭，不记录完成状态
  showTour.value = false
}

// 暴露方法给父组件
defineExpose({
  startTour,
  markTourCompleted
})
</script>
