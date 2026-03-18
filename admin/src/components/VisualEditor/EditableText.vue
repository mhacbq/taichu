<template>
  <div
    class="editable-text"
    :class="{ 'is-editing': isEditing, 'is-hovered': isHovered && canEdit }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- 显示模式 -->
    <div
      v-if="!isEditing"
      class="text-display"
      @click="startEdit"
    >
      <slot :value="displayValue">
        <span>{{ displayValue || placeholder }}</span>
      </slot>
      
      <!-- 编辑提示 -->
      <transition name="fade">
        <div v-if="isHovered && canEdit" class="edit-hint">
          <el-icon><Edit /></el-icon>
          <span>点击编辑</span>
        </div>
      </transition>
    </div>
    
    <!-- 编辑模式 -->
    <div v-else class="text-editor">
      <el-input
        v-if="type === 'text'"
        v-model="editValue"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :show-word-limit="!!maxlength"
        v-bind="inputProps"
        @keyup.enter="confirmEdit"
        @keyup.esc="cancelEdit"
        ref="inputRef"
      />
      
      <el-input
        v-else-if="type === 'textarea'"
        v-model="editValue"
        type="textarea"
        :rows="rows"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :show-word-limit="!!maxlength"
        v-bind="inputProps"
        @keyup.esc="cancelEdit"
        ref="inputRef"
      />
      
      <el-input-number
        v-else-if="type === 'number'"
        v-model="editValue"
        :placeholder="placeholder"
        :min="min"
        :max="max"
        :precision="precision"
        v-bind="inputProps"
        @keyup.esc="cancelEdit"
        ref="inputRef"
      />
      
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
import { ElMessage, ElMessageBox } from 'element-plus'


const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text', // text, textarea, number
    validator: (val) => ['text', 'textarea', 'number'].includes(val)
  },
  placeholder: {
    type: String,
    default: '点击编辑'
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  maxlength: {
    type: Number,
    default: null
  },
  rows: {
    type: Number,
    default: 3
  },
  min: {
    type: Number,
    default: -Infinity
  },
  max: {
    type: Number,
    default: Infinity
  },
  precision: {
    type: Number,
    default: 0
  },
  inputProps: {
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
  },
  confirmBeforeSave: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'save'])

const isEditing = ref(false)
const isHovered = ref(false)
const editValue = ref('')
const saving = ref(false)
const inputRef = ref(null)

const displayValue = computed(() => {
  return props.modelValue
})

const resolveSaveErrorMessage = (error) => {
  const message = typeof error?.message === 'string' ? error.message.trim() : ''
  const errorCode = Number(error?.code) || 0

  if (message && [400, 403, 404, 409, 422].includes(errorCode)) {
    return message
  }

  return '保存失败，请稍后重试'
}

// 开始编辑
const startEdit = () => {

  if (!props.canEdit) return
  
  editValue.value = props.modelValue
  isEditing.value = true
  
  nextTick(() => {
    inputRef.value?.focus?.()
    if (props.type === 'text' || props.type === 'textarea') {
      inputRef.value?.select?.()
    }
  })
}

// 确认编辑
const confirmEdit = async () => {
  if (props.confirmBeforeSave) {
    try {
      await ElMessageBox.confirm('确定保存修改吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
    } catch {
      return
    }
  }
  
  saving.value = true
  
  try {
    // 调用保存API
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
    ElMessage.error(resolveSaveErrorMessage(error))
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
.editable-text {
  position: relative;
  display: inline-block;
  min-width: 60px;
  min-height: 24px;
  transition: all 0.2s;
  
  &.is-hovered {
    background-color: var(--el-color-primary-light-9);
    border-radius: 4px;
    cursor: pointer;
  }
  
  &.is-editing {
    display: block;
    background: #fff;
    padding: 8px;
    border-radius: 4px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  }
}

.text-display {
  position: relative;
  padding: 4px 8px;
  
  .edit-hint {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--el-color-primary);
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 4px;
    
    &::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 50%;
      transform: translateX(-50%);
      border-left: 4px solid transparent;
      border-right: 4px solid transparent;
      border-top: 4px solid var(--el-color-primary);
    }
  }
}

.text-editor {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 200px;
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