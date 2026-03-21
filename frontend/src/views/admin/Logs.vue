<template>
  <div class="logs-manage">
    <div class="page-header">
      <h2>系统日志</h2>
      <el-button type="danger" :icon="Delete" @click="handleClearLogs">清空日志</el-button>
    </div>

    <el-card>
      <el-tabs v-model="activeTab" @tab-change="fetchData">
        <el-tab-pane label="操作日志" name="operation" />
        <el-tab-pane label="登录日志" name="login" />
        <el-tab-pane label="API日志" name="api" />
      </el-tabs>

      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="输入用户名" clearable />
        </el-form-item>
        <el-form-item label="IP地址">
          <el-input v-model="searchForm.ip" placeholder="输入IP地址" clearable />
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
          <el-button :icon="Download" @click="exportData">导出</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="action" label="操作/请求" min-width="200" show-overflow-tooltip />
        <el-table-column prop="method" label="方法" width="80">
          <template #default="{ row }">
            <el-tag v-if="row.method" :type="getMethodType(row.method)">{{ row.method }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 'success' ? 'success' : 'danger'">
              {{ row.status === 'success' ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="时间" width="160" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.size"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="fetchData"
        @size-change="fetchData"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="日志详情" width="700px">
      <el-descriptions :column="1" border>
        <el-descriptions-item label="ID">{{ currentRow.id }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ currentRow.username }}</el-descriptions-item>
        <el-descriptions-item label="操作/请求">{{ currentRow.action }}</el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ currentRow.ip }}</el-descriptions-item>
        <el-descriptions-item label="User-Agent">{{ currentRow.ua }}</el-descriptions-item>
        <el-descriptions-item label="时间">{{ currentRow.created_at }}</el-descriptions-item>
        <el-descriptions-item label="详情" v-if="currentRow.detail">
          <pre style="white-space: pre-wrap; word-break: break-all;">{{ currentRow.detail }}</pre>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Download, Delete } from '@element-plus/icons-vue'

const activeTab = ref('operation')
const loading = ref(false)
const tableData = ref([])
const detailDialogVisible = ref(false)
const currentRow = ref({})
const dateRange = ref([])

const searchForm = reactive({
  username: '',
  ip: ''
})

const pagination = reactive({
  page: 1,
  size: 20,
  total: 0
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      type: activeTab.value,
      page: pagination.page,
      size: pagination.size,
      ...searchForm
    })
    
    if (dateRange.value && dateRange.value.length === 2) {
      params.append('start_date', dateRange.value[0])
      params.append('end_date', dateRange.value[1])
    }
    
    const response = await fetch(`/api/admin/logs?${params}`)
    const data = await response.json()
    
    if (data.code === 200) {
      tableData.value = data.data.list || []
      pagination.total = data.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const getMethodType = (method) => {
  const map = { GET: 'success', POST: 'warning', PUT: 'primary', DELETE: 'danger' }
  return map[method] || 'info'
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.username = ''
  searchForm.ip = ''
  dateRange.value = []
  handleSearch()
}

const handleView = (row) => {
  currentRow.value = row
  detailDialogVisible.value = true
}

const handleClearLogs = async () => {
  try {
    await ElMessageBox.confirm(`确定清空${activeTab.value === 'operation' ? '操作' : activeTab.value === 'login' ? '登录' : 'API'}日志吗？此操作不可恢复！`, '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/logs/clear?type=${activeTab.value}`, {
      method: 'DELETE'
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('清空成功')
      fetchData()
    } else {
      ElMessage.error(data.message || '清空失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('清空失败')
  }
}

const exportData = () => {
  ElMessage.info('导出功能待实现')
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.search-form {
  margin-bottom: 20px;
}
</style>
