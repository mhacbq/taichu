<template>
  <el-form
    ref="formRef"
    :model="model"
    :inline="inline"
    :label-width="labelWidth"
    class="search-form"
  >
    <template v-for="item in items" :key="item.prop">
      <!-- 输入框 -->
      <el-form-item
        v-if="item.type === 'input'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-input
          v-model="model[item.prop]"
          :placeholder="item.placeholder || `请输入${item.label}`"
          :clearable="item.clearable !== false"
          :style="{ width: item.width || '200px' }"
          @keyup.enter="handleSearch"
        />
      </el-form-item>
      
      <!-- 选择框 -->
      <el-form-item
        v-else-if="item.type === 'select'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-select
          v-model="model[item.prop]"
          :placeholder="item.placeholder || `请选择${item.label}`"
          :clearable="item.clearable !== false"
          :multiple="item.multiple"
          :collapse-tags="item.collapseTags"
          :style="{ width: item.width || '200px' }"
        >
          <el-option
            v-for="opt in item.options"
            :key="opt.value"
            :label="opt.label"
            :value="opt.value"
          />
        </el-select>
      </el-form-item>
      
      <!-- 日期选择 -->
      <el-form-item
        v-else-if="item.type === 'date'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-date-picker
          v-model="model[item.prop]"
          :type="item.dateType || 'date'"
          :placeholder="item.placeholder || '选择日期'"
          :start-placeholder="item.startPlaceholder || '开始日期'"
          :end-placeholder="item.endPlaceholder || '结束日期'"
          :value-format="item.valueFormat || 'YYYY-MM-DD'"
          :style="{ width: item.width || '200px' }"
        />
      </el-form-item>
      
      <!-- 日期范围 -->
      <el-form-item
        v-else-if="item.type === 'daterange'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-date-picker
          v-model="model[item.prop]"
          type="daterange"
          :start-placeholder="item.startPlaceholder || '开始日期'"
          :end-placeholder="item.endPlaceholder || '结束日期'"
          :value-format="item.valueFormat || 'YYYY-MM-DD'"
          :style="{ width: item.width || '240px' }"
        />
      </el-form-item>
      
      <!-- 日期时间范围 -->
      <el-form-item
        v-else-if="item.type === 'datetimerange'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-date-picker
          v-model="model[item.prop]"
          type="datetimerange"
          :start-placeholder="item.startPlaceholder || '开始时间'"
          :end-placeholder="item.endPlaceholder || '结束时间'"
          :value-format="item.valueFormat || 'YYYY-MM-DD HH:mm:ss'"
          :style="{ width: item.width || '360px' }"
        />
      </el-form-item>
      
      <!-- 级联选择 -->
      <el-form-item
        v-else-if="item.type === 'cascader'"
        :label="item.label"
        :prop="item.prop"
      >
        <el-cascader
          v-model="model[item.prop]"
          :options="item.options"
          :props="item.props"
          :placeholder="item.placeholder || `请选择${item.label}`"
          :clearable="item.clearable !== false"
          :style="{ width: item.width || '200px' }"
        />
      </el-form-item>
      
      <!-- 自定义插槽 -->
      <el-form-item
        v-else-if="item.type === 'slot'"
        :label="item.label"
        :prop="item.prop"
      >
        <slot :name="item.slotName || item.prop" :item="item" :model="model" />
      </el-form-item>
    </template>
    
    <!-- 操作按钮 -->
    <el-form-item>
      <el-button type="primary" @click="handleSearch">
        <el-icon><Search /></el-icon>搜索
      </el-button>
      <el-button @click="handleReset">重置</el-button>
      <el-button v-if="showExport" @click="handleExport">
        <el-icon><Download /></el-icon>导出
      </el-button>
      <slot name="buttons" />
    </el-form-item>
  </el-form>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  model: { type: Object, required: true },
  items: { type: Array, required: true },
  inline: { type: Boolean, default: true },
  labelWidth: { type: String, default: '80px' },
  showExport: { type: Boolean, default: false }
})

const emit = defineEmits(['search', 'reset', 'export'])

const formRef = ref(null)

function handleSearch() {
  emit('search')
}

function handleReset() {
  formRef.value?.resetFields()
  emit('reset')
}

function handleExport() {
  emit('export')
}

// 暴露方法
defineExpose({
  resetFields: () => formRef.value?.resetFields(),
  validate: () => formRef.value?.validate(),
  clearValidate: () => formRef.value?.clearValidate()
})
</script>

<style lang="scss" scoped>
.search-form {
  :deep(.el-form-item) {
    margin-bottom: 18px;
  }
}
</style>
