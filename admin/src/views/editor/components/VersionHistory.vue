<template>
  <el-dialog
    v-model="dialogVisible"
    title="版本历史"
    width="800px"
    destroy-on-close
  >
    <div class="version-history">
      <!-- 对比模式 -->
      <div v-if="compareMode" class="compare-header">
        <span>对比模式</span>
        <el-button link @click="compareMode = false">退出对比</el-button>
      </div>
      
      <!-- 版本列表 -->
      <el-timeline v-loading="loading">
        <el-timeline-item
          v-for="version in versions"
          :key="version.id"
          :timestamp="formatTime(version.created_at)"
          :type="version.id === currentVersionId ? 'primary' : ''"
          placement="top"
        >
          <el-card :class="{ 'is-current': version.id === currentVersionId }">
            <div class="version-card-header">
              <div class="version-info">
                <el-avatar :size="32" :src="version.author_avatar">
                  {{ version.author_name?.charAt(0) }}
                </el-avatar>
                <div class="version-meta">
                  <div class="version-author">{{ version.author_name }}</div>
                  <div class="version-time">{{ formatTime(version.created_at) }}</div>
                </div>
              </div>
              
              <div class="version-badges">
                <el-tag
                  v-if="version.id === currentVersionId"
                  type="success"
                  size="small"
                >
                  当前版本
                </el-tag>
                <el-tag
                  v-if="version.auto_save"
                  type="info"
                  size="small"
                >
                  自动保存
                </el-tag>
                <el-tag
                  v-if="version.is_draft"
                  type="warning"
                  size="small"
                >
                  草稿
                </el-tag>
              </div>
            </div>
            
            <p class="version-description">{{ version.description || '无描述' }}</p>
            
            <!-- 版本统计 -->
            <div class="version-stats">
              <span>
                <el-icon><Document /></el-icon>
                {{ version.block_count || 0 }} 个块
              </span>
              <span>
                <el-icon><View /></el-icon>
                {{ version.preview_count || 0 }} 次预览
              </span>
            </div>
            
            <!-- 操作按钮 -->
            <div class="version-actions">
              <template v-if="!compareMode">
                <el-button
                  v-if="version.id !== currentVersionId"
                  type="primary"
                  link
                  size="small"
                  @click="handleRestore(version)"
                >
                  <el-icon><RefreshLeft /></el-icon>
                  恢复此版本
                </el-button>
                
                <el-button
                  link
                  size="small"
                  @click="handlePreview(version)"
                >
                  <el-icon><View /></el-icon>
                  预览
                </el-button>
                
                <el-button
                  link
                  size="small"
                  @click="startCompare(version)"
                >
                  <el-icon><ScaleToOriginal /></el-icon>
                  对比
                </el-button>
                
                <el-dropdown @command="(cmd) => handleMore(cmd, version)">
                  <el-button link size="small">
                    <el-icon><More /></el-icon>
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="rename">
                        <el-icon><Edit /></el-icon>
                        重命名
                      </el-dropdown-item>
                      <el-dropdown-item command="export">
                        <el-icon><Download /></el-icon>
                        导出
                      </el-dropdown-item>
                      <el-dropdown-item divided command="delete">
                        <el-icon><Delete /></el-icon>
                        删除
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </template>
              
              <template v-else>
                <el-radio-group v-model="compareLeft" size="small">
                  <el-radio-button :label="version.id">左</el-radio-button>
                </el-radio-group>
                <el-radio-group v-model="compareRight" size="small">
                  <el-radio-button :label="version.id">右</el-radio-button>
                </el-radio-group>
              </template>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>
      
      <!-- 空状态 -->
      <el-empty v-if="!loading && !versions.length" description="暂无版本历史" />
      
      <!-- 分页 -->
      <el-pagination
        v-if="total > pageSize"
        v-model:current-page="page"
        v-model:page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        @change="loadVersions"
      />
    </div>
    
    <!-- 预览弹窗 -->
    <el-dialog
      v-model="previewVisible"
      title="版本预览"
      width="900px"
      append-to-body
    >
      <div class="version-preview">
        <div class="preview-header">
          <div class="preview-info">
            <span>版本: {{ previewVersion?.version }}</span>
            <span>作者: {{ previewVersion?.author_name }}</span>
            <span>时间: {{ formatTime(previewVersion?.created_at) }}</span>
          </div>
          <el-radio-group v-model="previewDevice" size="small">
            <el-radio-button label="desktop">桌面</el-radio-button>
            <el-radio-button label="tablet">平板</el-radio-button>
            <el-radio-button label="mobile">手机</el-radio-button>
          </el-radio-group>
        </div>
        
        <div class="preview-content" :class="`device-${previewDevice}`">
          <ContentBlock
            v-for="block in previewBlocks"
            :key="block.id"
            :block="block"
            :can-edit="false"
          />
        </div>
      </div>
    </el-dialog>
    
    <!-- 对比弹窗 -->
    <el-dialog
      v-model="compareVisible"
      title="版本对比"
      width="1200px"
      append-to-body
    >
      <div class="version-compare">
        <div class="compare-panel left">
          <div class="compare-header">
            <span>版本 {{ leftVersion?.version }}</span>
            <span>{{ formatTime(leftVersion?.created_at) }}</span>
          </div>
          <div class="compare-content">
            <pre>{{ JSON.stringify(leftVersion?.content, null, 2) }}</pre>
          </div>
        </div>
        <div class="compare-panel right">
          <div class="compare-header">
            <span>版本 {{ rightVersion?.version }}</span>
            <span>{{ formatTime(rightVersion?.created_at) }}</span>
          </div>
          <div class="compare-content">
            <pre>{{ JSON.stringify(rightVersion?.content, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </el-dialog>
    
    <template #footer>
      <el-button v-if="compareMode" type="primary" @click="executeCompare">
        开始对比
      </el-button>
      <el-button @click="dialogVisible = false">关闭</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  Document, View, RefreshLeft, ScaleToOriginal,
  More, Edit, Download, Delete
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ContentBlock from '@/components/PageBuilder/ContentBlock.vue'
import * as contentApi from '@/api/contentEditor'
import { formatDateTime } from '@/utils/format'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  pageId: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'restore'])

// 状态
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const loading = ref(false)
const versions = ref([])
const page = ref(1)
const pageSize = ref(10)
const total = ref(0)
const currentVersionId = ref(null)

// 预览
const previewVisible = ref(false)
const previewVersion = ref(null)
const previewBlocks = ref([])
const previewDevice = ref('desktop')

// 对比
const compareMode = ref(false)
const compareVisible = ref(false)
const compareLeft = ref(null)
const compareRight = ref(null)
const leftVersion = ref(null)
const rightVersion = ref(null)

// 监听弹窗打开
watch(dialogVisible, (val) => {
  if (val) {
    loadVersions()
  }
})

// 加载版本列表
const loadVersions = async () => {
  loading.value = true
  
  try {
    const res = await contentApi.getVersions(props.pageId, {
      page: page.value,
      pageSize: pageSize.value
    })
    
    if (res.code === 200) {
      versions.value = res.data.list
      total.value = res.data.total
      
      // 找到当前版本
      const current = versions.value.find(v => v.is_current)
      if (current) {
        currentVersionId.value = current.id
      }
    }
  } catch (error) {
    ElMessage.error('加载版本历史失败')
  } finally {
    loading.value = false
  }
}

// 格式化时间
const formatTime = (time) => {
  return formatDateTime(time)
}

// 恢复版本
const handleRestore = async (version) => {
  try {
    await ElMessageBox.confirm(
      `确定恢复到 ${formatTime(version.created_at)} 的版本吗？当前版本将被备份。`,
      '恢复版本',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    const res = await contentApi.restoreVersion(version.id)
    
    if (res.code === 200) {
      ElMessage.success('恢复成功')
      emit('restore')
      dialogVisible.value = false
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('恢复失败')
    }
  }
}

// 预览版本
const handlePreview = async (version) => {
  try {
    const res = await contentApi.previewVersion(version.id)
    
    if (res.code === 200) {
      previewVersion.value = res.data
      previewBlocks.value = res.data.blocks || []
      previewVisible.value = true
    }
  } catch (error) {
    ElMessage.error('加载预览失败')
  }
}

// 开始对比
const startCompare = (version) => {
  compareMode.value = true
  if (!compareLeft.value) {
    compareLeft.value = version.id
  } else if (!compareRight.value && compareLeft.value !== version.id) {
    compareRight.value = version.id
  }
}

// 执行对比
const executeCompare = () => {
  if (!compareLeft.value || !compareRight.value) {
    ElMessage.warning('请选择两个版本进行对比')
    return
  }
  
  leftVersion.value = versions.value.find(v => v.id === compareLeft.value)
  rightVersion.value = versions.value.find(v => v.id === compareRight.value)
  
  compareVisible.value = true
}

// 更多操作
const handleMore = async (command, version) => {
  switch (command) {
    case 'rename':
      await renameVersion(version)
      break
    case 'export':
      exportVersion(version)
      break
    case 'delete':
      await deleteVersion(version)
      break
  }
}

// 重命名版本
const renameVersion = async (version) => {
  try {
    const { value } = await ElMessageBox.prompt('请输入版本描述', '重命名', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputValue: version.description || ''
    })
    
    // 调用API更新描述
    // await contentApi.updateVersion(version.id, { description: value })
    
    version.description = value
    ElMessage.success('重命名成功')
  } catch {
    // 取消
  }
}

// 导出版本
const exportVersion = (version) => {
  const data = {
    page_id: props.pageId,
    version: version.version,
    content: version.content,
    settings: version.settings,
    exported_at: new Date().toISOString()
  }
  
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${props.pageId}-v${version.version}.json`
  link.click()
  URL.revokeObjectURL(url)
  
  ElMessage.success('导出成功')
}

// 删除版本
const deleteVersion = async (version) => {
  try {
    await ElMessageBox.confirm('确定删除此版本吗？', '删除版本', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'danger'
    })
    
    // await contentApi.deleteVersion(version.id)
    
    versions.value = versions.value.filter(v => v.id !== version.id)
    ElMessage.success('删除成功')
  } catch {
    // 取消
  }
}
</script>

<style scoped lang="scss">
.version-history {
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 10px;
}

.compare-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: var(--el-color-primary-light-9);
  border-radius: 4px;
  margin-bottom: 16px;
}

.el-card {
  &.is-current {
    border-color: var(--el-color-success);
  }
}

.version-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.version-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.version-meta {
  .version-author {
    font-weight: 500;
  }
  
  .version-time {
    font-size: 12px;
    color: #909399;
    margin-top: 2px;
  }
}

.version-badges {
  display: flex;
  gap: 8px;
}

.version-description {
  color: #606266;
  margin-bottom: 12px;
  line-height: 1.6;
}

.version-stats {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #909399;
  
  span {
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

.version-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid #e4e7ed;
}

.version-preview {
  .preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    background: #f5f7fa;
    border-radius: 4px;
    margin-bottom: 16px;
    
    .preview-info {
      display: flex;
      gap: 16px;
      font-size: 13px;
      color: #606266;
    }
  }
  
  .preview-content {
    padding: 20px;
    background: #fff;
    border-radius: 4px;
    min-height: 400px;
    
    &.device-tablet {
      max-width: 768px;
      margin: 0 auto;
    }
    
    &.device-mobile {
      max-width: 375px;
      margin: 0 auto;
    }
  }
}

.version-compare {
  display: flex;
  gap: 16px;
  height: 60vh;
  
  .compare-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    overflow: hidden;
    
    .compare-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px;
      background: #f5f7fa;
      border-bottom: 1px solid #e4e7ed;
    }
    
    .compare-content {
      flex: 1;
      overflow: auto;
      padding: 12px;
      
      pre {
        margin: 0;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        line-height: 1.6;
      }
    }
    
    &.left {
      border-color: #909399;
    }
    
    &.right {
      border-color: var(--el-color-primary);
    }
  }
}

:deep(.el-timeline-item__node) {
  background-color: var(--el-color-primary);
}

:deep(.el-timeline-item__tail) {
  border-left-color: var(--el-color-primary-light-5);
}
</style>