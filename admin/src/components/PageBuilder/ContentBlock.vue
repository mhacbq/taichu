<template>
  <div
    class="content-block"
    :class="{
      'is-editing': isEditing,
      'is-selected': isSelected,
      'is-dragging': isDragging,
      'is-locked': block.locked
    }"
    :style="blockStyle"
    :data-block-id="block.id"
    @click="handleClick"
  >
    <!-- 拖拽手柄 -->
    <div v-if="canEdit" class="block-handle" @mousedown="startDrag">
      <el-icon><Rank /></el-icon>
    </div>
    
    <!-- 块头部 -->
    <div v-if="showHeader" class="block-header">
      <div class="block-title">
        <el-icon v-if="block.icon"><component :is="block.icon" /></el-icon>
        <span>{{ block.title || typeLabel }}</span>
      </div>
      <div v-if="canEdit" class="block-actions">
        <el-tooltip content="编辑">
          <el-icon @click.stop="toggleEdit"><Edit /></el-icon>
        </el-tooltip>
        <el-tooltip content="设置">
          <el-icon @click.stop="openSettings"><Setting /></el-icon>
        </el-tooltip>
        <el-tooltip content="锁定">
          <el-icon @click.stop="toggleLock"><Lock v-if="!block.locked" /><Unlock v-else /></el-icon>
        </el-tooltip>
        <el-tooltip content="删除">
          <el-icon @click.stop="handleDelete"><Delete /></el-icon>
        </el-tooltip>
      </div>
    </div>
    
    <!-- 块内容 -->
    <div class="block-content">
      <!-- 文本块 -->
      <template v-if="block.type === 'text'">
        <EditableText
          v-model="blockData.content"
          :type="block.props?.type || 'text'"
          :placeholder="block.props?.placeholder"
          :can-edit="canEdit && !block.locked"
          @save="handleSave"
        />
      </template>
      
      <!-- 图片块 -->
      <template v-else-if="block.type === 'image'">
        <EditableImage
          v-model="blockData.url"
          :width="block.props?.width || '100%'"
          :height="block.props?.height || '200px'"
          :fit="block.props?.fit || 'cover'")
          :can-edit="canEdit && !block.locked"
          @save="handleSave"
        />
      </template>
      
      <!-- 轮播块 -->
      <template v-else-if="block.type === 'carousel'">
        <el-carousel
          :height="block.props?.height || '300px'"
          :interval="block.props?.interval || 4000"
          :type="block.props?.type"
        >
          <el-carousel-item v-for="(item, index) in blockData.items" :key="index">
            <el-image :src="item.url" fit="cover" />
            <div v-if="item.title" class="carousel-title">{{ item.title }}</div>
          </el-carousel-item>
        </el-carousel>
        <div v-if="canEdit && !block.locked" class="block-edit-btn" @click="editCarousel">
          <el-icon><Edit /></el-icon>
          编辑轮播
        </div>
      </template>
      
      <!-- 卡片块 -->
      <template v-else-if="block.type === 'card'">
        <el-card :shadow="block.props?.shadow || 'hover'">
          <template #header v-if="blockData.title">
            <div class="card-header">
              <EditableText
                v-model="blockData.title"
                :can-edit="canEdit && !block.locked"
                @save="handleSave"
              />
            </div>
          </template>
          <EditableText
            v-model="blockData.content"
            type="textarea"
            :rows="block.props?.rows || 4"
            :can-edit="canEdit && !block.locked"
            @save="handleSave"
          />
        </el-card>
      </template>
      
      <!-- 列表块 -->
      <template v-else-if="block.type === 'list'">
        <el-list>
          <el-list-item v-for="(item, index) in blockData.items" :key="index">
            <EditableText
              v-model="item.text"
              :can-edit="canEdit && !block.locked"
              @save="handleSave"
            />
            <el-icon
              v-if="canEdit && !block.locked"
              class="delete-item"
              @click="removeListItem(index)"
            >
              <Close />
            </el-icon>
          </el-list-item>
        </el-list>
        <el-button
          v-if="canEdit && !block.locked"
          type="primary"
          link
          @click="addListItem"
        >
          <el-icon><Plus /></el-icon>
          添加项
        </el-button>
      </template>
      
      <!-- 统计块 -->
      <template v-else-if="block.type === 'stat'">
        <StatCard
          :title="blockData.title"
          :value="blockData.value"
          :trend="blockData.trend"
          :trend-type="blockData.trendType"
          :icon="blockData.icon"
          :loading="blockData.loading"
        />
      </template>
      
      <!-- 图表块 -->
      <template v-else-if="block.type === 'chart'">
        <Chart
          :type="block.props?.chartType || 'line'"
          :data="blockData.chartData"
          :options="block.props?.chartOptions"
        />
      </template>
      
      <!-- 自定义块 -->
      <template v-else-if="block.type === 'custom'">
        <component
          :is="block.component"
          v-bind="block.props"
          v-model="blockData"
          :can-edit="canEdit && !block.locked"
          @save="handleSave"
        />
      </template>
    </div>
    
    <!-- 块设置弹窗 -->
    <el-dialog
      v-model="settingsVisible"
      title="块设置"
      width="500px"
    >
      <el-form label-width="100px">
        <el-form-item label="标题">
          <el-input v-model="settings.title" placeholder="块标题" />
        </el-form-item>
        <el-form-item label="图标">
          <el-select v-model="settings.icon" placeholder="选择图标" clearable>
            <el-option
              v-for="icon in iconOptions"
              :key="icon.value"
              :label="icon.label"
              :value="icon.value"
            >
              <el-icon><component :is="icon.value" /></el-icon>
              {{ icon.label }}
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="宽度">
          <el-slider v-model="settings.width" :min="10" :max="100" show-stops />
        </el-form-item>
        <el-form-item label="背景色">
          <el-color-picker v-model="settings.backgroundColor" show-alpha />
        </el-form-item>
        <el-form-item label="内边距">
          <el-slider v-model="settings.padding" :min="0" :max="48" />
        </el-form-item>
        <el-form-item label="圆角">
          <el-slider v-model="settings.borderRadius" :min="0" :max="24" />
        </el-form-item>
        <el-form-item label="阴影">
          <el-select v-model="settings.shadow">
            <el-option label="无" value="none" />
            <el-option label="默认" value="default" />
            <el-option label="中等" value="medium" />
            <el-option label="强烈" value="strong" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="settingsVisible = false">取消</el-button>
        <el-button type="primary" @click="saveSettings">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import {
  Rank, Edit, Setting, Lock, Unlock, Delete, Plus, Close,
  Document, Picture, List, DataLine, PieChart, Grid, Star
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { EditableText, EditableImage } from '../VisualEditor'
import StatCard from '../StatCard/index.vue'
import Chart from '../Chart/index.vue'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update', 'delete', 'select', 'drag-start', 'drag-end', 'lock'])

// 状态
const isEditing = ref(false)
const isDragging = ref(false)
const settingsVisible = ref(false)
const blockData = ref({ ...props.block.data })

// 设置
const settings = reactive({
  title: props.block.title || '',
  icon: props.block.icon || '',
  width: props.block.style?.width || 100,
  backgroundColor: props.block.style?.backgroundColor || '#ffffff',
  padding: parseInt(props.block.style?.padding) || 16,
  borderRadius: parseInt(props.block.style?.borderRadius) || 8,
  shadow: props.block.style?.shadow || 'default'
})

// 图标选项
const iconOptions = [
  { label: '文档', value: 'Document' },
  { label: '图片', value: 'Picture' },
  { label: '列表', value: 'List' },
  { label: '图表', value: 'DataLine' },
  { label: '饼图', value: 'PieChart' },
  { label: '网格', value: 'Grid' },
  { label: '收藏', value: 'Star' }
]

// 类型标签
const typeLabel = computed(() => {
  const typeMap = {
    text: '文本',
    image: '图片',
    carousel: '轮播',
    card: '卡片',
    list: '列表',
    stat: '统计',
    chart: '图表',
    custom: '自定义'
  }
  return typeMap[props.block.type] || '未知'
})

// 块样式
const blockStyle = computed(() => {
  const shadowMap = {
    none: 'none',
    default: '0 2px 12px 0 rgba(0,0,0,0.1)',
    medium: '0 4px 16px 0 rgba(0,0,0,0.15)',
    strong: '0 8px 24px 0 rgba(0,0,0,0.2)'
  }
  
  return {
    width: `${settings.width}%`,
    backgroundColor: settings.backgroundColor,
    padding: `${settings.padding}px`,
    borderRadius: `${settings.borderRadius}px`,
    boxShadow: shadowMap[settings.shadow]
  }
})

// 是否显示头部
const showHeader = computed(() => {
  return props.canEdit || props.block.title
})

// 处理点击
const handleClick = () => {
  emit('select', props.block.id)
}

// 切换编辑
const toggleEdit = () => {
  isEditing.value = !isEditing.value
}

// 打开设置
const openSettings = () => {
  settingsVisible.value = true
}

// 保存设置
const saveSettings = () => {
  emit('update', {
    ...props.block,
    title: settings.title,
    icon: settings.icon,
    style: {
      width: `${settings.width}%`,
      backgroundColor: settings.backgroundColor,
      padding: `${settings.padding}px`,
      borderRadius: `${settings.borderRadius}px`,
      shadow: settings.shadow
    }
  })
  settingsVisible.value = false
  ElMessage.success('设置已保存')
}

// 切换锁定
const toggleLock = () => {
  emit('lock', props.block.id, !props.block.locked)
}

// 删除块
const handleDelete = async () => {
  try {
    await ElMessageBox.confirm('确定删除此内容块吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    emit('delete', props.block.id)
  } catch {
    // 取消删除
  }
}

// 保存数据
const handleSave = (data) => {
  emit('update', {
    ...props.block,
    data: { ...blockData.value, ...data }
  })
}

// 编辑轮播
const editCarousel = () => {
  // 打开轮播编辑器
}

// 添加列表项
const addListItem = () => {
  if (!blockData.value.items) {
    blockData.value.items = []
  }
  blockData.value.items.push({ text: '新项' })
  handleSave({ items: blockData.value.items })
}

// 删除列表项
const removeListItem = (index) => {
  blockData.value.items.splice(index, 1)
  handleSave({ items: blockData.value.items })
}

// 拖拽开始
const startDrag = (e) => {
  if (props.block.locked) return
  isDragging.value = true
  emit('drag-start', props.block.id, e)
}
</script>

<style scoped lang="scss">
.content-block {
  position: relative;
  background: #fff;
  border: 2px solid transparent;
  border-radius: 8px;
  transition: all 0.3s;
  
  &.is-selected {
    border-color: var(--el-color-primary);
  }
  
  &.is-dragging {
    opacity: 0.5;
    cursor: grabbing;
  }
  
  &.is-locked {
    .block-actions {
      opacity: 0.5;
      pointer-events: none;
    }
  }
}

.block-handle {
  position: absolute;
  top: 4px;
  left: 4px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  color: #909399;
  background: #f5f7fa;
  border-radius: 4px;
  opacity: 0;
  transition: opacity 0.2s;
  z-index: 10;
  
  &:hover {
    background: #e4e7ed;
  }
  
  &:active {
    cursor: grabbing;
  }
  
  .content-block:hover & {
    opacity: 1;
  }
}

.block-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  border-bottom: 1px solid #e4e7ed;
  margin-bottom: 12px;
}

.block-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  color: #303133;
  
  .el-icon {
    color: var(--el-color-primary);
  }
}

.block-actions {
  display: flex;
  gap: 12px;
  
  .el-icon {
    cursor: pointer;
    color: #909399;
    transition: color 0.2s;
    
    &:hover {
      color: var(--el-color-primary);
    }
  }
}

.block-content {
  min-height: 40px;
}

.block-edit-btn {
  margin-top: 8px;
  padding: 8px;
  text-align: center;
  color: var(--el-color-primary);
  cursor: pointer;
  border: 1px dashed var(--el-color-primary-light-5);
  border-radius: 4px;
  transition: all 0.2s;
  
  &:hover {
    background: var(--el-color-primary-light-9);
  }
  
  .el-icon {
    margin-right: 4px;
  }
}

.carousel-title {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20px;
  background: linear-gradient(transparent, rgba(0,0,0,0.6));
  color: #fff;
  font-size: 16px;
}

.card-header {
  font-weight: 500;
}

.delete-item {
  margin-left: 8px;
  color: #909399;
  cursor: pointer;
  
  &:hover {
    color: #f56c6c;
  }
}

:deep(.el-list-item) {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>