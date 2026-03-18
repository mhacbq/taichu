<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="操作人">
          <el-input v-model="queryForm.operator" placeholder="用户名/ID" clearable />
        </el-form-item>
        <el-form-item label="操作模块">
          <el-select v-model="queryForm.module" placeholder="全部模块" clearable>
            <el-option label="用户管理" value="user" />
            <el-option label="内容管理" value="content" />
            <el-option label="积分管理" value="points" />
            <el-option label="系统设置" value="system" />
            <el-option label="反作弊" value="anticheat" />
            <el-option label="日志管理" value="log" />
          </el-select>
        </el-form-item>
        <el-form-item label="操作类型">
          <el-select v-model="queryForm.action" placeholder="全部类型" clearable>
            <el-option label="新增" value="create" />
            <el-option label="修改" value="update" />
            <el-option label="删除" value="delete" />
            <el-option label="查询" value="view" />
            <el-option label="导出" value="export" />
          </el-select>
        </el-form-item>
        <el-form-item label="操作时间">
          <el-date-picker
            v-model="queryForm.dateRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
          <el-button :loading="exportLoading" @click="handleExport">导出日志</el-button>
          <el-button type="danger" @click="handleClear">清空日志</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table v-loading="loading" :data="logList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="日志ID" width="100" />
        <el-table-column prop="operator" label="操作人" width="120" />
        <el-table-column prop="module" label="操作模块" width="120">
          <template #default="{ row }">
            <el-tag size="small">{{ getModuleText(row.module) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="action" label="操作类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getActionType(row.action)" size="small">{{ getActionText(row.action) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="操作描述" min-width="220" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP地址" width="130" />
        <el-table-column prop="duration" label="耗时" width="80">
          <template #default="{ row }">
            <span :class="row.duration > 1000 ? 'text-danger' : ''">{{ row.duration }}ms</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="操作时间" width="180" />
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleViewDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="detailDialog.visible" title="操作详情" width="700px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="日志ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="操作人">{{ detailDialog.data.operator }}</el-descriptions-item>
        <el-descriptions-item label="操作模块">{{ getModuleText(detailDialog.data.module) }}</el-descriptions-item>
        <el-descriptions-item label="操作类型">{{ getActionText(detailDialog.data.action) }}</el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ detailDialog.data.ip }}</el-descriptions-item>
        <el-descriptions-item label="耗时">{{ detailDialog.data.duration }}ms</el-descriptions-item>
        <el-descriptions-item label="操作时间" :span="2">{{ detailDialog.data.created_at }}</el-descriptions-item>
      </el-descriptions>
      <div style="margin-top: 20px;">
        <h4>操作描述</h4>
        <div class="detail-content">{{ detailDialog.data.description }}</div>
      </div>
      <div v-if="detailDialog.data.request" style="margin-top: 20px;">
        <h4>请求参数</h4>
        <pre class="json-content">{{ formatJson(detailDialog.data.request) }}</pre>
      </div>
      <div v-if="detailDialog.data.response" style="margin-top: 20px;">
        <h4>响应结果</h4>
        <pre class="json-content">{{ formatJson(detailDialog.data.response) }}</pre>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { clearLogs, exportLogs, getOperationLogs } from '@/api/log'

const loading = ref(false)
const exportLoading = ref(false)
const logList = ref([])
const total = ref(0)

const queryForm = reactive({
  operator: '',
  module: '',
  action: '',
  dateRange: [],
  page: 1,
  pageSize: 20
})

const detailDialog = reactive({
  visible: false,
  data: {}
})

onMounted(() => {
  loadLogList()
})

function buildRequestParams() {
  const params = {
    operator: queryForm.operator,
    module: queryForm.module,
    action: queryForm.action,
    page: queryForm.page,
    pageSize: queryForm.pageSize
  }

  if (Array.isArray(queryForm.dateRange) && queryForm.dateRange.length === 2) {
    params.start_time = queryForm.dateRange[0]
    params.end_time = queryForm.dateRange[1]
  }

  return params
}

async function loadLogList() {
  loading.value = true
  try {
    const { data } = await getOperationLogs(buildRequestParams())
    logList.value = data.list || []
    total.value = Number(data.total || 0)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadLogList()
}

function handleReset() {
  Object.assign(queryForm, {
    operator: '',
    module: '',
    action: '',
    dateRange: [],
    page: 1,
    pageSize: 20
  })
  loadLogList()
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  loadLogList()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadLogList()
}

async function handleExport() {
  exportLoading.value = true
  try {
    const response = await exportLogs('operation', buildRequestParams())
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `operation_logs_${Date.now()}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    ElMessage.success('日志导出成功')
  } finally {
    exportLoading.value = false
  }
}

async function handleClear() {
  try {
    await ElMessageBox.confirm('确定清空所有操作日志吗？此操作不可恢复！', '警告', { type: 'warning' })
    await clearLogs('operation')
    ElMessage.success('日志已清空')
    await loadLogList()
  } catch (error) {
    if (error !== 'cancel') {
      // 统一由请求层提示错误
    }
  }
}

function handleViewDetail(row) {
  detailDialog.data = row
  detailDialog.visible = true
}

function formatJson(json) {
  try {
    return JSON.stringify(JSON.parse(json), null, 2)
  } catch {
    return json
  }
}

function getModuleText(module) {
  const map = {
    user: '用户管理',
    content: '内容管理',
    points: '积分管理',
    system: '系统设置',
    anticheat: '反作弊',
    log: '日志管理'
  }
  return map[module] || module
}

function getActionType(action) {
  const map = { create: 'success', update: 'primary', delete: 'danger', view: 'info', export: 'warning' }
  return map[action] || ''
}

function getActionText(action) {
  const map = { create: '新增', update: '修改', delete: '删除', view: '查询', export: '导出' }
  return map[action] || action
}
</script>

<style lang="scss" scoped>
.detail-content {
  background: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  line-height: 1.8;
}

.json-content {
  background: #282c34;
  color: #abb2bf;
  padding: 15px;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 13px;
  line-height: 1.6;
}

.text-danger {
  color: #f56c6c;
}
</style>
