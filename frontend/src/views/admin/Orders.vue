<template>
  <div class="orders-manage">
    <div class="page-header">
      <h2>订单列表</h2>
      <el-button type="primary" :icon="Download" @click="exportData">导出</el-button>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="订单号">
          <el-input v-model="searchForm.order_no" placeholder="输入订单号" clearable />
        </el-form-item>
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="输入用户名" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="选择状态" clearable>
            <el-option label="待支付" value="pending" />
            <el-option label="已支付" value="paid" />
            <el-option label="已取消" value="cancelled" />
            <el-option label="已退款" value="refunded" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="package_name" label="套餐名称" width="150" />
        <el-table-column prop="amount" label="金额" width="100">
          <template #default="{ row }">
            ¥{{ row.amount }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="支付方式" width="100" />
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">查看</el-button>
            <el-button
              v-if="row.status === 'paid'"
              type="danger"
              link
              size="small"
              @click="handleRefund(row)"
            >
              退款
            </el-button>
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Download } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])

const searchForm = reactive({
  order_no: '',
  username: '',
  status: ''
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
      page: pagination.page,
      size: pagination.size,
      ...searchForm
    })
    
    const response = await fetch(`/api/admin/orders?${params}`)
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

const getStatusType = (status) => {
  const map = {
    pending: 'warning',
    paid: 'success',
    cancelled: 'info',
    refunded: 'danger'
  }
  return map[status] || 'info'
}

const getStatusText = (status) => {
  const map = {
    pending: '待支付',
    paid: '已支付',
    cancelled: '已取消',
    refunded: '已退款'
  }
  return map[status] || status
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.order_no = ''
  searchForm.username = ''
  searchForm.status = ''
  handleSearch()
}

const handleView = (row) => {
  ElMessage.info('查看详情功能待实现')
}

const handleRefund = async (row) => {
  try {
    await ElMessageBox.confirm(`确定退款订单 "${row.order_no}" 吗？`, '警告', { type: 'warning' })
    ElMessage.info('退款功能待实现')
  } catch (error) {}
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
