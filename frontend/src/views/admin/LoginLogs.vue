<script setup>
import { ref, onMounted } from 'vue'
import { getLoginLogs } from '@/api/admin'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const logs = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)

const filters = ref({
  admin_id: '',
  start_date: '',
  end_date: ''
})

const loadLogs = async () => {
  loading.value = true
  try {
    const response = await getLoginLogs({
      page: currentPage.value,
      page_size: pageSize.value,
      ...filters.value
    })
    if (response.code === 200) {
      logs.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '获取登录日志失败')
    }
  } catch (error) {
    console.error('获取登录日志失败:', error)
    ElMessage.error('获取登录日志失败')
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
    admin_id: '',
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

const formatAdminName = (row) => {
  if (row.admin_nickname) {
    return `${row.admin_nickname}(${row.admin_username})`
  }
  return row.admin_username || '未知'
}

const getStatusType = (status) => {
  return status === 'success' ? 'success' : 'danger'
}

const getStatusText = (status) => {
  return status === 'success' ? '成功' : '失败'
}

onMounted(() => {
  loadLogs()
})
</script>

<template>
  <div class="admin-logs-login">
    <div class="page-header">
      <h2>登录日志</h2>
    </div>

    <el-card class="filter-card">
      <el-form :model="filters" inline>
        <el-form-item label="管理员ID">
          <el-input v-model="filters.admin_id" placeholder="请输入管理员ID" clearable />
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
        <el-table-column label="操作人" width="180">
          <template #default="{ row }">
            {{ formatAdminName(row) }}
          </template>
        </el-table-column>
        <el-table-column label="登录状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="user_agent" label="浏览器信息" show-overflow-tooltip />
        <el-table-column prop="fail_reason" label="失败原因" show-overflow-tooltip>
          <template #default="{ row }">
            <span>{{ row.fail_reason || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="登录时间" width="180" />
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
  </div>
</template>

<style scoped>
.admin-logs-login {
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
</style>
