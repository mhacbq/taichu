<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getApiLogs } from '@/api/log'

const loading = ref(false)
const apiLogs = ref([])
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

onMounted(() => {
  fetchApiLogs()
})

async function fetchApiLogs() {
  loading.value = true
  try {
    const res = await getApiLogs({
      page: pagination.value.current,
      limit: pagination.value.pageSize
    })
    if (res.code === 0) {
      apiLogs.value = res.data.list || []
      pagination.value.total = res.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取API日志失败')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchApiLogs()
}

function handleSizeChange(size) {
  pagination.value.pageSize = size
  pagination.value.current = 1
  fetchApiLogs()
}

function getStatusText(status) {
  return status === 200 ? '成功' : status
}

function getStatusTagType(status) {
  return status === 200 ? 'success' : 'danger'
}

function formatJson(json) {
  if (!json) return '-'
  try {
    return JSON.stringify(JSON.parse(json), null, 2)
  } catch {
    return json
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>API日志</span>
          <el-button type="primary" @click="fetchApiLogs">刷新</el-button>
        </div>
      </template>

      <el-table :data="apiLogs" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="method" label="方法" width="80">
          <template #default="{ row }">
            <el-tag :type="row.method === 'GET' ? 'info' : row.method === 'POST' ? 'success' : 'warning'" size="small">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="path" label="路径" show-overflow-tooltip width="250" />
        <el-table-column prop="status_code" label="状态码" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status_code)" size="small">
              {{ getStatusText(row.status_code) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="response_time" label="响应时间(ms)" width="130" />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="created_at" label="请求时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small">查看详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
        style="margin-top: 20px; justify-content: center"
      />
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
