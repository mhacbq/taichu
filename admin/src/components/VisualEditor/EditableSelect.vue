<template>
  <div
    class="editable-select"
    :class="{ 'is-editing': isEditing, 'is-hovered': isHovered && canEdit }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- 显示模式 -->
    <div
      v-if="!isEditing"
      class="select-display"
      @click="startEdit"
    >
      <slot :value="modelValue" :label="currentLabel">
        <el-tag v-if="showTag" :type="currentType" :effect="tagEffect">
          {{ currentLabel || placeholder }}
        </el-tag>
        <span v-else>{{ currentLabel || placeholder }}</span>
      </slot>
      
      <!-- 编辑提示 -->
      <transition name="fade">
        <div v-if="isHovered && canEdit" class="edit-hint">
          <el-icon><Edit /></el-icon>
        </div>
      </transition>
    </div>
    
    <!-- 编辑模式 -->
    <div v-else class="select-editor">
      <el-select
        v-model="editValue"
        :placeholder="placeholder"
        :multiple="multiple"
        :clearable="clearable"
        :filterable="filterable"
        v-bind="selectProps"
        ref="selectRef"
        style="width: 100%"
      >
        <el-option
          v-for="option in options"
          :key="option.value"
          :label="option.label"
          :value="option.value"
          :disabled="option.disabled"
        >
          <slot name="option" :option="option">
            <span v-if="option.icon" class="option-icon">
              <el-icon v-if="typeof option.icon === 'string'">
                <component :is="option.icon" />
              </el-icon>
              <component v-else :is="option.icon" />
            </span>
            {{ option.label }}
          </slot>
        </el-option>
      </el-select>
      
      <!-- 操作按钮 -->
      <div class="editor-actions">
        <el-button
          type="primary"
          size="small"
          :loading="saving"
          @click="confirmEdit"
        >
          <el-icon><Check /></el-icon>
        </el-button>
        <el-button
          size="small"
          @click="cancelEdit"
        >
          <el-icon><Close /></el-icon>
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Edit, Check, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: {
    type: [String, Number, Array, Boolean],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: '请选择'
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  multiple: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: true
  },
  filterable: {
    type: Boolean,
    default: true
  },
  showTag: {
    type: Boolean,
    default: true
  },
  tagEffect: {
    type: String,
    default: 'light'
  },
  selectProps: {
    type: Object,
    default: () => ({})
  },
  saveApi: {
    type: Function,
    default: null
  },
  saveKey: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'save'])

const isEditing = ref(false)
const isHovered = ref(false)
const editValue = ref('')
const saving = ref(false)
const selectRef = ref(null)

// 当前显示的label
const currentLabel = computed(() => {
  if (props.multiple && Array.isArray(props.modelValue)) {
    const labels = props.modelValue.map(val => {
      const option = props.options.find(opt => opt.value === val)
      return option?.label || val
    })
    return labels.join(', ')
  }
  
  const option = props.options.find(opt => opt.value === props.modelValue)
  return option?.label || props.modelValue
})

// 当前tag类型
const currentType = computed(() => {
  const option = props.options.find(opt => opt.value === props.modelValue)
  return option?.type || ''
})

// 开始编辑
const startEdit = () => {
  if (!props.canEdit) return
  
  editValue.value = props.modelValue
  isEditing.value = true
  
  nextTick(() => {
    selectRef.value?.focus()
  })
}

// 确认编辑
const confirmEdit = async () => {
  saving.value = true
  
  try {
    if (props.saveApi) {
      await props.saveApi({
        key: props.saveKey,
        value: editValue.value
      })
    }
    
    emit('update:modelValue', editValue.value)
    emit('change', editValue.value)
    emit('save', { key: props.saveKey, value: editValue.value })
    
    ElMessage.success('保存成功')
    isEditing.value = false
  } catch (error) {
    ElMessage.error('保存失败: ' + error.message)
  } finally {
    saving.value = false
  }
}

// 取消编辑
const cancelEdit = () => {
  isEditing.value = false
  editValue.value = props.modelValue
}

// 监听外部值变化
watch(() => props.modelValue, (newVal) => {
  if (!isEditing.value) {
    editValue.value = newVal
  }
})
</script>

<style scoped lang="scss">
.editable-select {
  position: relative;
  display: inline-block;
  min-width: 80px;
  min-height: 24px;
  
  &.is-hovered {
    cursor: pointer;
    
    :deep(.el-tag) {
      background-color: var(--el-color-primary-light-9);
    }
  }
  
  &.is-editing {
    display: block;
    background: #fff;
    padding: 8px;
    border-radius: 4px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    min-width: 150px;
  }
}

.select-display {
  position: relative;
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  
  .edit-hint {
    margin-left: 8px;
    color: var(--el-color-primary);
    opacity: 0;
    transition: opacity 0.2s;
  }
  
  &:hover .edit-hint {
    opacity: 1;
  }
}

.select-editor {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.option-icon {
  margin-right: 6px;
}

.editor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>