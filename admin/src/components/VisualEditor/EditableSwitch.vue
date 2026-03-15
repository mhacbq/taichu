<template>
  <div
    class="editable-switch"
    :class="{ 'is-editing': isEditing, 'is-hovered': isHovered && canEdit }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- 显示模式 -->
    <div
      v-if="!isEditing"
      class="switch-display"
      @click="startEdit"
    >
      <slot :value="modelValue" :active="isActive">
        <el-switch
          :model-value="modelValue"
          :active-text="activeText"
          :inactive-text="inactiveText"
          :active-color="activeColor"
          :inactive-color="inactiveColor"
          :disabled="true"
        />
        <span class="status-text" :class="{ 'is-active': isActive }">
          {{ isActive ? activeLabel : inactiveLabel }}
        </span>
      </slot>
      
      <!-- 编辑提示 -->
      <transition name="fade">
        <div v-if="isHovered && canEdit" class="edit-hint">
          <el-icon><Edit /></el-icon>
          <span>点击切换</span>
        </div>
      </transition>
    </div>
    
    <!-- 编辑模式 -->
    <div v-else class="switch-editor">
      <div class="switch-preview">
        <el-switch
          v-model="editValue"
          :active-text="activeText"
          :inactive-text="inactiveText"
          :active-color="activeColor"
          :inactive-color="inactiveColor"
          size="large"
        />
        <span class="preview-text" :class="{ 'is-active': editValue }">
          {{ editValue ? activeLabel : inactiveLabel }}
        </span>
      </div>
      
      <!-- 操作按钮 -->
      <div class="editor-actions">
        <el-button
          type="primary"
          size="small"
          :loading="saving"
          @click="confirmEdit"
        >
          <el-icon><Check /></el-icon>
          确认
        </el-button>
        <el-button
          size="small"
          @click="cancelEdit"
        >
          <el-icon><Close /></el-icon>
          取消
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Edit, Check, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  activeText: {
    type: String,
    default: ''
  },
  inactiveText: {
    type: String,
    default: ''
  },
  activeLabel: {
    type: String,
    default: '开启'
  },
  inactiveLabel: {
    type: String,
    default: '关闭'
  },
  activeColor: {
    type: String,
    default: '#67C23A'
  },
  inactiveColor: {
    type: String,
    default: '#909399'
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
const editValue = ref(false)
const saving = ref(false)

const isActive = computed(() => props.modelValue)

// 开始编辑
const startEdit = () => {
  if (!props.canEdit) return
  
  editValue.value = props.modelValue
  isEditing.value = true
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
.editable-switch {
  position: relative;
  display: inline-block;
  
  &.is-hovered {
    cursor: pointer;
  }
  
  &.is-editing {
    background: #fff;
    padding: 12px;
    border-radius: 4px;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  }
}

.switch-display {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 4px 8px;
  
  .status-text {
    font-size: 14px;
    color: #909399;
    transition: color 0.3s;
    
    &.is-active {
      color: #67C23A;
      font-weight: 500;
    }
  }
  
  .edit-hint {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--el-color-primary);
    color: #fff;
    padding: 4px 10px;
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

.switch-editor {
  min-width: 200px;
}

.switch-preview {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 4px;
  margin-bottom: 12px;
  
  .preview-text {
    font-size: 16px;
    font-weight: 500;
    color: #909399;
    transition: color 0.3s;
    
    &.is-active {
      color: #67C23A;
    }
  }
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