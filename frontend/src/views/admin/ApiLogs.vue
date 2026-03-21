<script setup>
import { ref, onMounted } from 'vue'
import { getApiLogs } from '@/api/admin'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const logs = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const detailDialogVisible = ref(false)
const currentLog = ref(null)

const filters = ref({
  path: '',
  method: '',
  status: '',
  start_date: '',
  end_date: ''
})

const methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']

const loadLogs = async () => {
  loading.value = true
  try {
    const response = await getApiLogs({
      page: currentPage.value,
      page_size: pageSize.value,
      ...filters.value
    })
    if (response.code === 200) {
      logs.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '获取API日志失败')
    }
  } catch (error) {
    console.error('获取API日志失败:', error)
    ElMessage.error('获取API日志失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadLogs()
}

const handleReset = () => {
  filters.value = {
    path: '',
    method: '',
    status: '',
    start_date: '',
    end_date: ''
  }
  currentPage.value = 1
  loadLogs()
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadLogs()
}

const showDetail = (row) => {
  currentLog.value = row
  detailDialogVisible.value = true
}

const formatMethod = (method) => {
  const colors = {
    GET: 'success',
    POST: 'primary',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'info'
  }
  return colors[method] || ''
}

const formatStatus = (status) => {
  return status >= 200 && status < 300 ? 'success' : 'danger'
}

const formatJson = (data) => {
  try {
    if (typeof data === 'string') {
      const parsed = JSON.parse(data)
      return JSON.stringify(parsed, null, 2)
    }
    return JSON.stringify(data, null, 2)
  } catch (e) {
    return data
  }
}

onMounted(() => {
  loadLogs()
})
</script>

<template>
  <div class="admin-logs-api">
    <div class="page-header">
      <h2>API日志</h2>
    </div>

    <el-card class="filter-card">
      <el-form :model="filters" inline>
        <el-form-item label="请求路径">
          <el-input v-model="filters.path" placeholder="请输入请求路径" clearable />
        </el-form-item>
        <el-form-item label="请求方法">
          <el-select v-model="filters.method" placeholder="请选择请求方法" clearable>
            <el-option v-for="method in methods" :key="method" :label="method" :value="method" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态码">
          <el-input v-model="filters.status" placeholder="请输入状态码" clearable />
        </el-form-item>
        <el-form-item label="开始日期">
          <el-date-picker
            v-model="filters.start_date"
            type="date"
            placeholder="选择开始日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item label="结束日期">
          <el-date-picker
            v-model="filters.end_date"
            type="date"
            placeholder="选择结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <el-table :data="logs" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="method" label="请求方法" width="100">
          <template #default="{ row }">
            <el-tag :type="formatMethod(row.method)" size="small">{{ row.method }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="path" label="请求路径" width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态码" width="100">
          <template #default="{ row }">
            <el-tag :type="formatStatus(row.status)" size="small">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="response_time" label="响应时间(ms)" width="120" />
        <el-table-column label="请求参数" show-overflow-tooltip>
          <template #default="{ row }">
            <span>{{ row.request_params || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="请求时间" width="180" />
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="showDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="loadLogs"
        @current-change="handlePageChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>

    <el-dialog v-model="detailDialogVisible" title="API日志详情" width="800px">
      <el-descriptions v-if="currentLog" :column="1" border>
        <el-descriptions-item label="ID">{{ currentLog.id }}</el-descriptions-item>
        <el-descriptions-item label="请求方法">
          <el-tag :type="formatMethod(currentLog.method)">{{ currentLog.method }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="请求路径">{{ currentLog.path }}</el-descriptions-item>
        <el-descriptions-item label="状态码">
          <el-tag :type="formatStatus(currentLog.status)">{{ currentLog.status }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ currentLog.ip }}</el-descriptions-item>
        <el-descriptions-item label="响应时间">{{ currentLog.response_time }}ms</el-descriptions-item>
        <el-descriptions-item label="请求时间">{{ currentLog.created_at }}</el-descriptions-item>
        <el-descriptions-item label="请求参数" :span="2">
          <pre style="white-space: pre-wrap; word-break: break-all;">{{ formatJson(currentLog.request_params) }}</pre>
        </el-descriptions-item>
        <el-descriptions-item label="响应结果" :span="2">
          <pre style="white-space: pre-wrap; word-break: break-all;">{{ formatJson(currentLog.response_data) }}</pre>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-logs-api {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.filter-card {
  margin-bottom: 20px;
}

.table-card {
  margin-bottom: 20px;
}

pre {
  margin: 0;
  font-size: 12px;
  max-height: 400px;
  overflow-y: auto;
}
</style>
