<template>
  <div
    class="page-layout"
    :class="{
      'is-editing': isEditing,
      'is-preview': isPreview
    }"
  >
    <!-- 工具栏 -->
    <div v-if="isEditing && !isPreview" class="layout-toolbar">
      <div class="toolbar-left">
        <el-button-group>
          <el-button :type="device === 'desktop' ? 'primary' : ''" @click="device = 'desktop'">
            <el-icon><Monitor /></el-icon>
          </el-button>
          <el-button :type="device === 'tablet' ? 'primary' : ''" @click="device = 'tablet'">
            <el-icon><Cellphone /></el-icon>
          </el-button>
          <el-button :type="device === 'mobile' ? 'primary' : ''" @click="device = 'mobile'">
            <el-icon><Phone /></el-icon>
          </el-button>
        </el-button-group>
        
        <el-divider direction="vertical" />
        
        <el-button @click="showBlockPanel = true">
          <el-icon><Plus /></el-icon>
          添加内容块
        </el-button>
        
        <el-button @click="showTemplatePanel = true">
          <el-icon><DocumentCopy /></el-icon>
          模板
        </el-button>
      </div>
      
      <div class="toolbar-center">
        <span class="save-status">
          <el-icon v-if="saving"><Loading /></el-icon>
          <el-icon v-else-if="saved"><CircleCheck /></el-icon>
          <span>{{ saveStatusText }}</span>
        </span>
      </div>
      
      <div class="toolbar-right">
        <el-button @click="togglePreview">
          <el-icon><View /></el-icon>
          {{ isPreview ? '退出预览' : '预览' }}
        </el-button>
        
        <el-button type="primary" :loading="saving" @click="saveLayout">
          <el-icon><Check /></el-icon>
          保存
        </el-button>
        
        <el-dropdown @command="handleMoreAction">
          <el-button>
            <el-icon><More /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="history">
                <el-icon><Timer /></el-icon>
                历史版本
              </el-dropdown-item>
              <el-dropdown-item command="export">
                <el-icon><Download /></el-icon>
                导出JSON
              </el-dropdown-item>
              <el-dropdown-item command="import">
                <el-icon><Upload /></el-icon>
                导入JSON
              </el-dropdown-item>
              <el-dropdown-item divided command="clear">
                <el-icon><Delete /></el-icon>
                清空页面
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
    
    <!-- 编辑画布 -->
    <div
      class="layout-canvas"
      :class="`device-${device}`"
      :style="canvasStyle"
    >
      <!-- 空状态 -->
      <el-empty
        v-if="!blocks.length"
        description="页面为空，点击添加内容块"
      >
        <el-button type="primary" @click="showBlockPanel = true">
          添加内容块
        </el-button>
      </el-empty>
      
      <!-- 内容块列表 -->
      <draggable
        v-else
        v-model="blocks"
        item-key="id"
        class="block-list"
        :disabled="!isEditing || isPreview"
        :animation="200"
        ghost-class="ghost-block"
        drag-class="dragging-block"
        @start="dragStart"
        @end="dragEnd"
      >
        <template #item="{ element, index }">
          <ContentBlock
            :block="element"
            :can-edit="isEditing && !isPreview"
            :is-selected="selectedBlockId === element.id"
            @update="updateBlock(index, $event)"
            @delete="deleteBlock(index)"
            @select="selectBlock"
            @lock="toggleBlockLock"
          />
        </template>
      </draggable>
    </div>
    
    <!-- 属性面板 -->
    <div v-if="isEditing && !isPreview && selectedBlock" class="property-panel">
      <div class="panel-header">
        <span>属性设置</span>
        <el-icon @click="selectedBlockId = null"><Close /></el-icon>
      </div>
      <div class="panel-content">
        <el-form label-position="top" size="small">
          <el-form-item label="块类型">
            <el-tag>{{ selectedBlock.type }}</el-tag>
          </el-form-item>
          <el-form-item label="块ID">
            <el-input v-model="selectedBlock.id" disabled />
          </el-form-item>
          <el-form-item label="数据源">
            <el-select v-model="selectedBlock.dataSource" placeholder="选择数据源">
              <el-option label="静态数据" value="static" />
              <el-option label="API接口" value="api" />
              <el-option label="Vuex状态" value="store" />
            </el-select>
          </el-form-item>
          <el-form-item v-if="selectedBlock.dataSource === 'api'" label="API地址">
            <el-input v-model="selectedBlock.apiUrl" placeholder="/api/data" />
          </el-form-item>
          <el-form-item label="显示条件">
            <el-input
              v-model="selectedBlock.condition"
              placeholder="例如: user.isVip"
            />
          </el-form-item>
          <el-form-item label="动画效果">
            <el-select v-model="selectedBlock.animation" placeholder="选择动画">
              <el-option label="无" value="" />
              <el-option label="淡入" value="fade" />
              <el-option label="滑入" value="slide" />
              <el-option label="缩放" value="zoom" />
              <el-option label="弹跳" value="bounce" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </div>
    
    <!-- 添加内容块面板 -->
    <el-drawer
      v-model="showBlockPanel"
      title="添加内容块"
      size="400px"
    >
      <div class="block-types">
        <div
          v-for="type in blockTypes"
          :key="type.value"
          class="block-type-item"
          @click="addBlock(type.value)"
        >
          <div class="type-icon">
            <el-icon :size="24"><component :is="type.icon" /></el-icon>
          </div>
          <div class="type-info">
            <div class="type-name">{{ type.label }}</div>
            <div class="type-desc">{{ type.description }}</div>
          </div>
          <el-icon class="type-add"><Plus /></el-icon>
        </div>
      </div>
    </el-drawer>
    
    <!-- 模板面板 -->
    <el-drawer
      v-model="showTemplatePanel"
      title="选择模板"
      size="400px"
    >
      <div class="template-list">
        <div
          v-for="template in templates"
          :key="template.id"
          class="template-item"
          @click="applyTemplate(template)"
        >
          <el-image :src="template.thumbnail" fit="cover" />
          <div class="template-info">
            <div class="template-name">{{ template.name }}</div>
            <div class="template-desc">{{ template.description }}</div>
          </div>
        </div>
      </div>
    </el-drawer>
    
    <!-- 历史版本 -->
    <el-dialog
      v-model="showHistory"
      title="历史版本"
      width="700px"
    >
      <el-timeline>
        <el-timeline-item
          v-for="version in historyVersions"
          :key="version.id"
          :timestamp="version.time"
          :type="version.id === currentVersionId ? 'primary' : ''"
        >
          <el-card>
            <div class="version-header">
              <span class="version-author">{{ version.author }}</span>
              <el-tag size="small" :type="version.id === currentVersionId ? 'success' : ''">
                {{ version.id === currentVersionId ? '当前版本' : '历史版本' }}
              </el-tag>
            </div>
            <p class="version-desc">{{ version.description }}</p>
            <div class="version-actions">
              <el-button
                v-if="version.id !== currentVersionId"
                type="primary"
                link
                @click="restoreVersion(version)"
              >
                恢复此版本
              </el-button>
              <el-button link @click="previewVersion(version)">
                预览
              </el-button>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import draggable from 'vuedraggable'
import {
  Monitor, Cellphone, Phone, Plus, DocumentCopy, Loading,
  CircleCheck, View, Check, More, Timer, Download, Upload,
  Delete, Close, Document, Picture, List, DataLine, PieChart,
  Grid, VideoCamera, Link
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ContentBlock from './ContentBlock.vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  isEditing: {
    type: Boolean,
    default: true
  },
  pageId: {
    type: String,
    default: ''
  },
  autoSave: {
    type: Boolean,
    default: true
  },
  autoSaveInterval: {
    type: Number,
    default: 30000 // 30秒
  }
})

const emit = defineEmits(['update:modelValue', 'save', 'change'])

// 状态
const device = ref('desktop')
const isPreview = ref(false)
const showBlockPanel = ref(false)
const showTemplatePanel = ref(false)
const showHistory = ref(false)
const selectedBlockId = ref(null)
const saving = ref(false)
const saved = ref(true)
const saveStatusText = ref('已保存')

// 内容块数据
const blocks = ref([])

// 初始化数据
onMounted(() => {
  blocks.value = props.modelValue.length ? [...props.modelValue] : []
  
  // 自动保存
  if (props.autoSave) {
    setInterval(() => {
      if (!saved.value) {
        autoSave()
      }
    }, props.autoSaveInterval)
  }
})

// 监听外部数据变化
watch(() => props.modelValue, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(blocks.value)) {
    blocks.value = [...newVal]
  }
}, { deep: true })

// 监听内部数据变化
watch(blocks, (newVal) => {
  emit('update:modelValue', newVal)
  emit('change', newVal)
  saved.value = false
  saveStatusText.value = '未保存'
}, { deep: true })

// 画布样式
const canvasStyle = computed(() => {
  const deviceWidth = {
    desktop: '100%',
    tablet: '768px',
    mobile: '375px'
  }
  
  return {
    maxWidth: deviceWidth[device.value],
    margin: device.value === 'desktop' ? '0' : '0 auto'
  }
})

// 选中的块
const selectedBlock = computed(() => {
  return blocks.value.find(b => b.id === selectedBlockId.value)
})

// 内容块类型
const blockTypes = [
  { value: 'text', label: '文本', icon: 'Document', description: '纯文本或富文本内容' },
  { value: 'image', label: '图片', icon: 'Picture', description: '单张图片展示' },
  { value: 'carousel', label: '轮播图', icon: 'VideoCamera', description: '图片轮播组件' },
  { value: 'card', label: '卡片', icon: 'Grid', description: '带标题的内容卡片' },
  { value: 'list', label: '列表', icon: 'List', description: '列表展示' },
  { value: 'stat', label: '统计', icon: 'DataLine', description: '数据统计卡片' },
  { value: 'chart', label: '图表', icon: 'PieChart', description: '数据可视化图表' },
  { value: 'custom', label: '自定义', icon: 'Link', description: '自定义组件' }
]

// 模板列表
const templates = ref([
  {
    id: 'home',
    name: '首页模板',
    description: '包含轮播图、统计数据、功能卡片的完整首页',
    thumbnail: 'https://placeholder.com/300x200',
    blocks: []
  },
  {
    id: 'dashboard',
    name: '仪表盘模板',
    description: '数据展示仪表盘，包含多种图表',
    thumbnail: 'https://placeholder.com/300x200',
    blocks: []
  }
])

// 历史版本
const historyVersions = ref([
  { id: 'v3', time: '2024-01-15 14:30', author: '管理员', description: '添加轮播图组件' },
  { id: 'v2', time: '2024-01-14 10:20', author: '管理员', description: '修改首页布局' },
  { id: 'v1', time: '2024-01-13 09:00', author: '管理员', description: '初始版本' }
])
const currentVersionId = ref('v3')

// 添加内容块
const addBlock = (type) => {
  const id = `block-${Date.now()}`
  const newBlock = {
    id,
    type,
    title: '',
    data: getDefaultData(type),
    style: {
      width: '100%',
      backgroundColor: '#ffffff',
      padding: '16px',
      borderRadius: '8px',
      shadow: 'default'
    },
    locked: false
  }
  
  blocks.value.push(newBlock)
  showBlockPanel.value = false
  selectedBlockId.value = id
  ElMessage.success('已添加内容块')
}

// 获取默认数据
const getDefaultData = (type) => {
  const defaults = {
    text: { content: '点击编辑文本内容' },
    image: { url: '' },
    carousel: { items: [] },
    card: { title: '卡片标题', content: '卡片内容' },
    list: { items: [{ text: '列表项1' }] },
    stat: { title: '统计项', value: 0, trend: 0, trendType: 'up' },
    chart: { chartData: {}, chartType: 'line' },
    custom: {}
  }
  return defaults[type] || {}
}

// 更新块
const updateBlock = (index, data) => {
  blocks.value[index] = { ...blocks.value[index], ...data }
}

// 删除块
const deleteBlock = (index) => {
  blocks.value.splice(index, 1)
  if (selectedBlockId.value === blocks.value[index]?.id) {
    selectedBlockId.value = null
  }
}

// 选择块
const selectBlock = (id) => {
  selectedBlockId.value = id === selectedBlockId.value ? null : id
}

// 切换块锁定
const toggleBlockLock = (id, locked) => {
  const index = blocks.value.findIndex(b => b.id === id)
  if (index > -1) {
    blocks.value[index].locked = locked
  }
}

// 拖拽开始
const dragStart = () => {
  // 拖拽开始
}

// 拖拽结束
const dragEnd = () => {
  saved.value = false
}

// 切换预览
const togglePreview = () => {
  isPreview.value = !isPreview.value
  selectedBlockId.value = null
}

// 自动保存
const autoSave = async () => {
  saving.value = true
  saveStatusText.value = '保存中...'
  
  try {
    // 调用保存API
    emit('save', blocks.value)
    saved.value = true
    saveStatusText.value = '已保存'
  } catch (error) {
    saveStatusText.value = '保存失败'
  } finally {
    saving.value = false
  }
}

// 手动保存
const saveLayout = async () => {
  await autoSave()
  ElMessage.success('保存成功')
}

// 更多操作
const handleMoreAction = async (command) => {
  switch (command) {
    case 'history':
      showHistory.value = true
      break
    case 'export':
      exportJSON()
      break
    case 'import':
      importJSON()
      break
    case 'clear':
      await clearLayout()
      break
  }
}

// 导出JSON
const exportJSON = () => {
  const data = JSON.stringify(blocks.value, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `layout-${props.pageId || 'export'}-${Date.now()}.json`
  link.click()
  URL.revokeObjectURL(url)
  ElMessage.success('导出成功')
}

// 导入JSON
const importJSON = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.json'
  input.onchange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    
    const reader = new FileReader()
    reader.onload = (event) => {
      try {
        const data = JSON.parse(event.target.result)
        blocks.value = data
        ElMessage.success('导入成功')
      } catch {
        ElMessage.error('文件格式错误')
      }
    }
    reader.readAsText(file)
  }
  input.click()
}

// 清空页面
const clearLayout = async () => {
  try {
    await ElMessageBox.confirm('确定清空所有内容块吗？此操作不可恢复', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    blocks.value = []
    ElMessage.success('已清空')
  } catch {
    // 取消
  }
}

// 应用模板
const applyTemplate = (template) => {
  blocks.value = [...template.blocks]
  showTemplatePanel.value = false
  ElMessage.success(`已应用模板: ${template.name}`)
}

// 恢复版本
const restoreVersion = async (version) => {
  try {
    await ElMessageBox.confirm(`确定恢复到 ${version.time} 的版本吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    // 恢复逻辑
    currentVersionId.value = version.id
    ElMessage.success('恢复成功')
  } catch {
    // 取消
  }
}

// 预览版本
const previewVersion = (version) => {
  // 预览逻辑
  ElMessage.info('版本预览功能')
}
</script>

<style scoped lang="scss">
.page-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f0f2f5;
  
  &.is-preview {
    .layout-toolbar {
      display: none;
    }
    
    .layout-canvas {
      border: none;
      box-shadow: none;
    }
  }
}

.layout-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: #fff;
  border-bottom: 1px solid #e4e7ed;
  
  .toolbar-left,
  .toolbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .toolbar-center {
    .save-status {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: #909399;
      
      .el-icon {
        font-size: 14px;
      }
    }
  }
}

.layout-canvas {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  transition: all 0.3s;
  
  &.device-tablet,
  &.device-mobile {
    background: #fff;
    border-left: 1px solid #e4e7ed;
    border-right: 1px solid #e4e7ed;
  }
}

.block-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 200px;
}

.ghost-block {
  opacity: 0.5;
  background: var(--el-color-primary-light-9);
  border: 2px dashed var(--el-color-primary);
}

.dragging-block {
  opacity: 0.8;
  transform: rotate(2deg);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.property-panel {
  position: fixed;
  right: 0;
  top: 60px;
  bottom: 0;
  width: 300px;
  background: #fff;
  border-left: 1px solid #e4e7ed;
  z-index: 100;
  
  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #e4e7ed;
    font-weight: 500;
    
    .el-icon {
      cursor: pointer;
      color: #909399;
      
      &:hover {
        color: #606266;
      }
    }
  }
  
  .panel-content {
    padding: 16px;
  }
}

.block-types {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.block-type-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  
  &:hover {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }
  
  .type-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--el-color-primary-light-9);
    border-radius: 8px;
    color: var(--el-color-primary);
  }
  
  .type-info {
    flex: 1;
    
    .type-name {
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .type-desc {
      font-size: 13px;
      color: #909399;
    }
  }
  
  .type-add {
    color: var(--el-color-primary);
  }
}

.template-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.template-item {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  
  &:hover {
    border-color: var(--el-color-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  
  .el-image {
    width: 100%;
    height: 160px;
  }
  
  .template-info {
    padding: 12px;
    
    .template-name {
      font-weight: 500;
      margin-bottom: 4px;
    }
    
    .template-desc {
      font-size: 13px;
      color: #909399;
    }
  }
}

.version-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  
  .version-author {
    font-weight: 500;
  }
}

.version-desc {
  color: #606266;
  font-size: 13px;
  margin-bottom: 12px;
}

.version-actions {
  display: flex;
  gap: 12px;
}
</style>