<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getLoginLogs } from '@/api/log'

const loading = ref(false)
const loginLogs = ref([])
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

onMounted(() => {
  fetchLoginLogs()
})

async function fetchLoginLogs() {
  loading.value = true
  try {
    const res = await getLoginLogs({
      page: pagination.value.current,
      limit: pagination.value.pageSize
    })
    if (res.code === 200) {
      loginLogs.value = res.data.list || []
      pagination.value.total = res.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取登录日志失败')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchLoginLogs()
}

function handleSizeChange(size) {
  pagination.value.pageSize = size
  pagination.value.current = 1
  fetchLoginLogs()
}

function getStatusText(status) {
  const statusMap = {
    'success': '成功',
    'failed': '失败',
    'pending': '待验证'
  }
  return statusMap[status] || status
}

function getStatusTagType(status) {
  const typeMap = {
    'success': 'success',
    'failed': 'danger',
    'pending': 'warning'
  }
  return typeMap[status] || 'info'
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>登录日志</span>
          <el-button type="primary" @click="fetchLoginLogs">刷新</el-button>
        </div>
      </template>

      <el-table :data="loginLogs" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)" size="small">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user_agent" label="浏览器" show-overflow-tooltip />
        <el-table-column prop="created_at" label="登录时间" width="180" />
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
