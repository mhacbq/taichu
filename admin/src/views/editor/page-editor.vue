<template>
  <div class="page-editor-container">
    <!-- 页面头部 -->
    <div class="editor-header">
      <div class="header-left">
        <el-button link @click="goBack">
          <el-icon><ArrowLeft /></el-icon>
        </el-button>
        <div class="page-info">
          <EditableText
            v-model="pageTitle"
            :can-edit="true"
            :save-api="saveTitle"
            save-key="title"
            class="page-title"
          />
          <span class="page-id">ID: {{ pageId }}</span>
        </div>
      </div>
      
      <div class="header-center">
        <el-radio-group v-model="currentMode" size="small">
          <el-radio-button label="edit">
            <el-icon><Edit /></el-icon>
            编辑
          </el-radio-button>
          <el-radio-button label="preview">
            <el-icon><View /></el-icon>
            预览
          </el-radio-button>
          <el-radio-button label="code">
            <el-icon><Document /></el-icon>
            代码
          </el-radio-button>
        </el-radio-group>
      </div>
      
      <div class="header-right">
        <el-button-group>
          <el-button size="small" @click="showHistory = true">
            <el-icon><Timer /></el-icon>
            历史
          </el-button>
          <el-button size="small" @click="exportPage">
            <el-icon><Download /></el-icon>
            导出
          </el-button>
        </el-button-group>
        
        <el-button
          type="primary"
          size="small"
          :loading="saving"
          @click="savePage"
        >
          <el-icon><Check /></el-icon>
          {{ saving ? '保存中...' : '保存' }}
        </el-button>
        
        <el-dropdown @command="handleMore">
          <el-button size="small">
            <el-icon><More /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="settings">
                <el-icon><Setting /></el-icon>
                页面设置
              </el-dropdown-item>
              <el-dropdown-item command="copy">
                <el-icon><DocumentCopy /></el-icon>
                复制页面
              </el-dropdown-item>
              <el-dropdown-item divided command="clear">
                <el-icon><Delete /></el-icon>
                清空内容
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
    
    <!-- 编辑器主体 -->
    <div class="editor-body">
      <!-- 编辑模式 -->
      <PageLayout
        v-if="currentMode === 'edit'"
        v-model="blocks"
        :is-editing="true"
        :page-id="pageId"
        @save="handleSave"
        @change="handleChange"
      />
      
      <!-- 预览模式 -->
      <div v-else-if="currentMode === 'preview'" class="preview-panel">
        <div class="preview-device-tabs">
          <el-radio-group v-model="previewDevice" size="small">
            <el-radio-button label="desktop">
              <el-icon><Monitor /></el-icon>
              桌面
            </el-radio-button>
            <el-radio-button label="tablet">
              <el-icon><Cellphone /></el-icon>
              平板
            </el-radio-button>
            <el-radio-button label="mobile">
              <el-icon><Phone /></el-icon>
              手机
            </el-radio-button>
          </el-radio-group>
        </div>
        
        <div class="preview-content" :class="`device-${previewDevice}`">
          <div class="page-preview">
            <ContentBlock
              v-for="block in blocks"
              :key="block.id"
              :block="block"
              :can-edit="false"
            />
          </div>
        </div>
      </div>
      
      <!-- 代码模式 -->
      <div v-else-if="currentMode === 'code'" class="code-panel">
        <el-tabs v-model="codeTab">
          <el-tab-pane label="JSON" name="json">
            <el-input
              v-model="jsonCode"
              type="textarea"
              :rows="30"
              class="code-editor"
            />
          </el-tab-pane>
          <el-tab-pane label="Vue Template" name="vue">
            <el-input
              v-model="vueCode"
              type="textarea"
              :rows="30"
              class="code-editor"
              readonly
            />
          </el-tab-pane>
        </el-tabs>
        
        <div class="code-actions">
          <el-button type="primary" @click="applyJson">
            应用JSON
          </el-button>
          <el-button @click="copyCode">
            复制
          </el-button>
        </div>
      </div>
    </div>
    
    <!-- 页面设置弹窗 -->
    <el-dialog
      v-model="showSettings"
      title="页面设置"
      width="600px"
    >
      <el-form label-width="100px">
        <el-form-item label="页面标题">
          <el-input v-model="pageSettings.title" />
        </el-form-item>
        <el-form-item label="页面描述">
          <el-input
            v-model="pageSettings.description"
            type="textarea"
            rows="3"
          />
        </el-form-item>
        <el-form-item label="背景色">
          <el-color-picker v-model="pageSettings.backgroundColor" show-alpha />
        </el-form-item>
        <el-form-item label="背景图片">
          <div class="bg-image-setting">
            <el-image
              v-if="pageSettings.backgroundImage"
              :src="pageSettings.backgroundImage"
              style="width: 200px; height: 100px;"
              fit="cover"
            />
            <div class="bg-image-actions">
              <el-button size="small" @click="selectBgImage">
                选择图片
              </el-button>
              <el-button
                v-if="pageSettings.backgroundImage"
                size="small"
                type="danger"
                link
                @click="pageSettings.backgroundImage = ''"
              >
                移除
              </el-button>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="最大宽度">
          <el-slider v-model="pageSettings.maxWidth" :min="800" :max="1920" :step="10" />
          <span>{{ pageSettings.maxWidth }}px</span>
        </el-form-item>
        <el-form-item label="内边距">
          <el-slider v-model="pageSettings.padding" :min="0" :max="100" />
          <span>{{ pageSettings.padding }}px</span>
        </el-form-item>
        <el-form-item label="SEO标题">
          <el-input v-model="pageSettings.seoTitle" placeholder="页面SEO标题" />
        </el-form-item>
        <el-form-item label="SEO关键词">
          <el-input-tag v-model="pageSettings.seoKeywords" placeholder="输入关键词后回车" />
        </el-form-item>
        <el-form-item label="SEO描述">
          <el-input
            v-model="pageSettings.seoDescription"
            type="textarea"
            rows="3"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="showSettings = false">取消</el-button>
        <el-button type="primary" @click="saveSettings">保存</el-button>
      </template>
    </el-dialog>
    
    <!-- 历史版本 -->
    <VersionHistory
      v-model="showHistory"
      :page-id="pageId"
      @restore="handleRestore"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, Edit, View, Document, Timer, Download,
  Check, More, Setting, DocumentCopy, Delete, Monitor,
  Cellphone, Phone
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import PageLayout from '@/components/PageBuilder/PageLayout.vue'
import ContentBlock from '@/components/PageBuilder/ContentBlock.vue'
import { EditableText } from '@/components/VisualEditor'
import VersionHistory from './components/VersionHistory.vue'
import * as contentApi from '@/api/contentEditor'

const route = useRoute()
const router = useRouter()

// 页面ID
const pageId = computed(() => route.params.id || 'home')

// 状态
const currentMode = ref('edit')
const previewDevice = ref('desktop')
const codeTab = ref('json')
const saving = ref(false)
const showSettings = ref(false)
const showHistory = ref(false)
const hasChanges = ref(false)

// 页面数据
const pageTitle = ref('未命名页面')
const blocks = ref([])
const pageSettings = ref({
  title: '',
  description: '',
  backgroundColor: '#f5f7fa',
  backgroundImage: '',
  maxWidth: 1200,
  padding: 20,
  seoTitle: '',
  seoKeywords: [],
  seoDescription: ''
})

// 代码编辑
const jsonCode = computed({
  get: () => JSON.stringify(blocks.value, null, 2),
  set: (val) => {
    try {
      blocks.value = JSON.parse(val)
    } catch {
      ElMessage.error('JSON格式错误')
    }
  }
})

const vueCode = computed(() => {
  // 生成Vue模板代码
  return generateVueTemplate(blocks.value)
})

// 初始化
onMounted(async () => {
  await loadPage()
  
  // 监听页面关闭
  window.addEventListener('beforeunload', (e) => {
    if (hasChanges.value) {
      e.preventDefault()
      e.returnValue = ''
    }
  })
})

// 加载页面
const loadPage = async () => {
  try {
    const res = await contentApi.getPage(pageId.value)
    if (res.code === 200) {
      const data = res.data
      pageTitle.value = data.title
      blocks.value = data.blocks || []
      Object.assign(pageSettings.value, data.settings || {})
    }
  } catch (error) {
    ElMessage.error('加载页面失败')
  }
}

// 保存页面
const savePage = async () => {
  saving.value = true
  
  try {
    await contentApi.savePage(pageId.value, {
      title: pageTitle.value,
      blocks: blocks.value,
      settings: pageSettings.value,
      description: '手动保存'
    })
    
    hasChanges.value = false
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 自动保存处理
const handleSave = async (data) => {
  // 触发自动保存
  try {
    await contentApi.autoSave(pageId.value, {
      blocks: data,
      settings: pageSettings.value
    })
  } catch (error) {
    console.error('自动保存失败:', error)
  }
}

// 内容变化
const handleChange = () => {
  hasChanges.value = true
}

// 保存标题
const saveTitle = async (data) => {
  pageTitle.value = data.value
  await savePage()
}

// 导出页面
const exportPage = async () => {
  try {
    const res = await contentApi.exportPage(pageId.value)
    if (res.code === 200) {
      // 下载JSON文件
      const blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${pageId.value}-export.json`
      link.click()
      URL.revokeObjectURL(url)
      
      ElMessage.success('导出成功')
    }
  } catch (error) {
    ElMessage.error('导出失败')
  }
}

// 更多操作
const handleMore = async (command) => {
  switch (command) {
    case 'settings':
      showSettings.value = true
      break
    case 'copy':
      await copyPage()
      break
    case 'clear':
      await clearPage()
      break
  }
}

// 复制页面
const copyPage = async () => {
  try {
    const { value } = await ElMessageBox.prompt('请输入新页面ID', '复制页面', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputValidator: (val) => val ? true : '页面ID不能为空'
    })
    
    await contentApi.importPage({
      page_id: value,
      title: `${pageTitle.value} - 副本`,
      content: blocks.value,
      settings: pageSettings.value
    })
    
    ElMessage.success('复制成功')
    router.push(`/editor/page/${value}`)
  } catch {
    // 取消
  }
}

// 清空页面
const clearPage = async () => {
  try {
    await ElMessageBox.confirm('确定清空所有内容吗？此操作不可恢复', '警告', {
      type: 'warning'
    })
    
    blocks.value = []
    hasChanges.value = true
    ElMessage.success('已清空')
  } catch {
    // 取消
  }
}

// 保存设置
const saveSettings = async () => {
  await savePage()
  showSettings.value = false
}

// 选择背景图片
const selectBgImage = () => {
  // 打开图片选择器
}

// 应用JSON
const applyJson = () => {
  try {
    blocks.value = JSON.parse(jsonCode.value)
    ElMessage.success('应用成功')
  } catch {
    ElMessage.error('JSON格式错误')
  }
}

// 复制代码
const copyCode = () => {
  const code = codeTab.value === 'json' ? jsonCode.value : vueCode.value
  navigator.clipboard.writeText(code)
  ElMessage.success('已复制到剪贴板')
}

// 恢复版本
const handleRestore = async () => {
  await loadPage()
  ElMessage.success('恢复成功')
}

// 返回
const goBack = () => {
  if (hasChanges.value) {
    ElMessageBox.confirm('有未保存的更改，确定离开吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(() => {
      router.back()
    }).catch(() => {})
  } else {
    router.back()
  }
}

// 生成Vue模板
function generateVueTemplate(blocks) {
  let template = '<template>\n  <div class="page-content">\n'
  
  blocks.forEach(block => {
    switch (block.type) {
      case 'text':
        template += `    <div class="text-block">${block.data?.content || ''}</div>\n`
        break
      case 'image':
        template += `    <el-image src="${block.data?.url || ''}" fit="cover" />\n`
        break
      case 'card':
        template += `    <el-card>\n      <template #header>${block.data?.title || ''}</template>\n      ${block.data?.content || ''}\n    </el-card>\n`
        break
      default:
        template += `    <!-- ${block.type} block -->\n`
    }
  })
  
  template += '  </div>\n</template>'
  
  return template
}
</script>

<style scoped lang="scss">
.page-editor-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f0f2f5;
}

.editor-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: #fff;
  border-bottom: 1px solid #e4e7ed;
  
  .header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    
    .page-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
      
      .page-title {
        font-size: 16px;
        font-weight: 500;
      }
      
      .page-id {
        font-size: 12px;
        color: #909399;
      }
    }
  }
  
  .header-center {
    .el-radio-group {
      .el-icon {
        margin-right: 4px;
      }
    }
  }
  
  .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }
}

.editor-body {
  flex: 1;
  overflow: hidden;
}

.preview-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  
  .preview-device-tabs {
    padding: 12px;
    text-align: center;
    background: #fff;
    border-bottom: 1px solid #e4e7ed;
  }
  
  .preview-content {
    flex: 1;
    padding: 20px;
    overflow: auto;
    background: #f0f2f5;
    
    &.device-desktop .page-preview {
      max-width: 100%;
    }
    
    &.device-tablet .page-preview {
      max-width: 768px;
      margin: 0 auto;
    }
    
    &.device-mobile .page-preview {
      max-width: 375px;
      margin: 0 auto;
    }
  }
  
  .page-preview {
    background: #fff;
    min-height: 100%;
    padding: 20px;
  }
}

.code-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px;
  
  .code-editor {
    flex: 1;
    
    :deep(.el-textarea__inner) {
      font-family: 'Consolas', 'Monaco', monospace;
      font-size: 13px;
      line-height: 1.6;
    }
  }
  
  .code-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 16px;
  }
}

.bg-image-setting {
  display: flex;
  gap: 16px;
  
  .bg-image-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
}

:deep(.el-slider) {
  margin-right: 16px;
}
</style>