<script setup>
import { ref, onMounted } from 'vue'
import { getOperationLogs } from '@/api/admin'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const logs = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)

const filters = ref({
  admin_id: '',
  action: '',
  start_date: '',
  end_date: ''
})

const actions = [
  '登录', '退出', '创建', '更新', '删除', '查看', '导出', '导入', '审核', '发布'
]

const loadLogs = async () => {
  loading.value = true
  try {
    const response = await getOperationLogs({
      page: currentPage.value,
      page_size: pageSize.value,
      ...filters.value
    })
    if (response.code === 200) {
      logs.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '获取操作日志失败')
    }
  } catch (error) {
    console.error('获取操作日志失败:', error)
    ElMessage.error('获取操作日志失败')
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
    action: '',
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

const handleSizeChange = (size) => {
  pageSize.value = size
  currentPage.value = 1
  loadLogs()
}

const formatAdminName = (row) => {
  if (row.admin_nickname) {
    return `${row.admin_nickname}(${row.admin_username})`
  }
  return row.admin_username || '未知'
}

onMounted(() => {
  loadLogs()
})
</script>

<template>
  <div class="admin-logs-operation">
    <div class="page-header">
      <h2>操作日志</h2>
    </div>

    <el-card class="filter-card">
      <el-form :model="filters" inline>
        <el-form-item label="管理员ID">
          <el-input v-model="filters.admin_id" placeholder="请输入管理员ID" clearable />
        </el-form-item>
        <el-form-item label="操作类型">
          <el-select v-model="filters.action" placeholder="请选择操作类型" clearable>
            <el-option v-for="action in actions" :key="action" :label="action" :value="action" />
          </el-select>
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
        <el-table-column prop="action" label="操作类型" width="120" />
        <el-table-column prop="module" label="模块" width="120" />
        <el-table-column label="详情" show-overflow-tooltip>
          <template #default="{ row }">
            <span>{{ row.detail || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column prop="created_at" label="操作时间" width="180" />
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
        style="margin-top: 20px; justify-content: flex-end"
      />
    </el-card>
  </div>
</template>

<style scoped>
.admin-logs-operation {
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
