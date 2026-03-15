<template>
  <div
    class="editable-color"
    :class="{ 'is-editing': isEditing, 'is-hovered': isHovered && canEdit }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- 显示模式 -->
    <div
      v-if="!isEditing"
      class="color-display"
      @click="startEdit"
    >
      <slot :value="modelValue">
        <div class="color-preview" :style="{ backgroundColor: modelValue }">
          <span class="color-value">{{ modelValue }}</span>
        </div>
      </slot>
      
      <!-- 编辑提示 -->
      <transition name="fade">
        <div v-if="isHovered && canEdit" class="edit-hint">
          <el-icon><Edit /></el-icon>
        </div>
      </transition>
    </div>
    
    <!-- 编辑模式 -->
    <div v-else class="color-editor">
      <div class="color-picker-wrapper">
        <el-color-picker
          v-model="editValue"
          :show-alpha="showAlpha"
          :color-format="colorFormat"
          :predefine="predefineColors"
        />
        <el-input
          v-model="editValue"
          class="color-input"
          placeholder="输入颜色值"
        >
          <template #append>
            <div class="color-preview-small" :style="{ backgroundColor: editValue }"></div>
          </template>
        </el-input>
      </div>
      
      <!-- 快捷颜色 -->
      <div v-if="quickColors.length" class="quick-colors">
        <span class="quick-label">快捷选择:</span>
        <div
          v-for="color in quickColors"
          :key="color"
          class="quick-color"
          :style="{ backgroundColor: color }"
          :class="{ 'is-selected': editValue === color }"
          @click="editValue = color"
        >
          <el-icon v-if="editValue === color"><Check /></el-icon>
        </div>
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
import { ref, watch } from 'vue'
import { Edit, Check, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: {
    type: String,
    default: '#409EFF'
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  showAlpha: {
    type: Boolean,
    default: false
  },
  colorFormat: {
    type: String,
    default: 'hex'
  },
  predefineColors: {
    type: Array,
    default: () => [
      '#ff4500', '#ff8c00', '#ffd700', '#90ee90', '#00ced1',
      '#1e90ff', '#c71585', '#c7158577', '#409EFF', '#67C23A',
      '#E6A23C', '#F56C6C', '#909399'
    ]
  },
  quickColors: {
    type: Array,
    default: () => [
      '#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#303133'
    ]
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
.editable-color {
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

.color-display {
  position: relative;
  display: inline-flex;
  align-items: center;
  
  .edit-hint {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    background: var(--el-color-primary);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }
}

.color-preview {
  width: 80px;
  height: 32px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #dcdfe6;
  
  .color-value {
    font-size: 12px;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    font-family: monospace;
  }
}

.color-editor {
  min-width: 280px;
}

.color-picker-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  
  .color-input {
    flex: 1;
  }
}

.color-preview-small {
  width: 20px;
  height: 20px;
  border-radius: 2px;
  border: 1px solid #dcdfe6;
}

.quick-colors {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding: 8px;
  background: #f5f7fa;
  border-radius: 4px;
  
  .quick-label {
    font-size: 12px;
    color: #606266;
  }
  
  .quick-color {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid transparent;
    transition: all 0.2s;
    
    &:hover {
      transform: scale(1.1);
    }
    
    &.is-selected {
      border-color: #409EFF;
      color: #fff;
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