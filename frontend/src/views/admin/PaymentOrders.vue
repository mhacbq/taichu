<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPaymentOrders, updateOrderStatus, refundOrder } from '../../api/admin'

const loading = ref(false)
const orders = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const searchForm = ref({
  order_no: '',
  status: ''
})

const loadOrders = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...searchForm.value
    }
    const response = await getPaymentOrders(params)
    
    if (response.code === 200) {
      orders.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载订单失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadOrders()
}

const handleReset = () => {
  searchForm.value = { order_no: '', status: '' }
  currentPage.value = 1
  loadOrders()
}

const handleUpdateStatus = async (row, status) => {
  try {
    const response = await updateOrderStatus(row.id, status)
    if (response.code === 200) {
      ElMessage.success('状态更新成功')
      loadOrders()
    } else {
      ElMessage.error(response.message || '更新失败')
    }
  } catch (error) {
    console.error('更新状态失败:', error)
    ElMessage.error('更新失败')
  }
}

const handleRefund = async (row) => {
  try {
    const response = await refundOrder(row.id, { reason: '管理员退款' })
    if (response.code === 200) {
      ElMessage.success('退款成功')
      loadOrders()
    } else {
      ElMessage.error(response.message || '退款失败')
    }
  } catch (error) {
    console.error('退款失败:', error)
    ElMessage.error('退款失败')
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadOrders()
}

const getStatusLabel = (status) => {
  const statusMap = {
    'pending': '待支付',
    'paid': '已支付',
    'cancelled': '已取消',
    'refunded': '已退款'
  }
  return statusMap[status] || status
}

const getStatusType = (status) => {
  const typeMap = {
    'pending': 'warning',
    'paid': 'success',
    'cancelled': 'info',
    'refunded': 'danger'
  }
  return typeMap[status] || 'info'
}

onMounted(() => {
  loadOrders()
})
</script>

<template>
  <div class="admin-payment-orders">
    <div class="page-header">
      <h2>订单管理</h2>
    </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="订单号">
          <el-input v-model="searchForm.order_no" placeholder="请输入订单号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="待支付" value="pending" />
            <el-option label="已支付" value="paid" />
            <el-option label="已取消" value="cancelled" />
            <el-option label="已退款" value="refunded" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 订单列表 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="orders" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="order_no" label="订单号" min-width="180" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="amount" label="金额（元）" width="100" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="package_name" label="套餐名称" min-width="150" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.status === 'paid'"
              size="small"
              type="danger"
              @click="handleRefund(row)"
            >
              退款
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<style scoped>
.admin-payment-orders {
  padding: 24px;
}

.page-header {
  margin-bottom: 20px;
}

.search-form {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
}

.el-pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
