<template>
  <div class="app-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">今日订单</div>
            <div class="stats-value">{{ stats.today_count || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">今日金额</div>
            <div class="stats-value text-success">¥{{ stats.today_amount || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">本月订单</div>
            <div class="stats-value">{{ stats.month_count || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">本月金额</div>
            <div class="stats-value text-success">¥{{ stats.month_amount || 0 }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索表单 -->
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="订单号">
          <el-input v-model="queryForm.order_no" placeholder="请输入订单号" clearable />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="订单状态">
          <el-select v-model="queryForm.status" placeholder="全部状态" clearable>
            <el-option label="待支付" value="pending" />
            <el-option label="已支付" value="paid" />
            <el-option label="已取消" value="cancelled" />
            <el-option label="已退款" value="refunded" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="queryForm.date_range"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 订单列表 -->
    <el-card shadow="never">
      <el-table v-loading="loading" :data="orderList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="order_no" label="订单号" width="180" show-overflow-tooltip />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="amount" label="金额" width="100">
          <template #default="{ row }">
            <span class="amount">¥{{ row.amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="points" label="积分" width="100">
          <template #default="{ row }">
            <span class="points">{{ row.points }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="支付方式" width="100">
          <template #default="{ row }">
            <span v-if="row.payment_method === 'wechat'">微信支付</span>
            <span v-else-if="row.payment_method === 'alipay'">支付宝</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="transaction_id" label="微信支付单号" width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span v-if="row.transaction_id">{{ row.transaction_id }}</span>
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="paid_at" label="支付时间" width="160">
          <template #default="{ row }">
            <span v-if="row.paid_at">{{ row.paid_at }}</span>
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
            <el-button 
              v-if="row.status === 'pending' && canModifyOrder" 
              link 
              type="success" 
              @click="handleComplete(row)"
            >
              补单
            </el-button>
            <el-button 
              v-if="row.status === 'paid' && canModifyOrder" 
              link 
              type="danger" 
              @click="handleRefund(row)"
            >
              退款
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 订单详情对话框 -->
    <el-dialog v-model="detailVisible" title="订单详情" width="600px">
      <el-descriptions :column="2" border v-if="currentOrder">
        <el-descriptions-item label="订单号" :span="2">{{ currentOrder.order_no }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ currentOrder.user_id }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ currentOrder.user_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="充值金额">¥{{ currentOrder.amount }}</el-descriptions-item>
        <el-descriptions-item label="获得积分">{{ currentOrder.points }}</el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag :type="getStatusType(currentOrder.status)">
            {{ getStatusText(currentOrder.status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="支付方式">
          {{ currentOrder.payment_method === 'wechat' ? '微信支付' : '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="微信支付单号" :span="2">{{ currentOrder.transaction_id || '-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ currentOrder.created_at }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ currentOrder.paid_at || '-' }}</el-descriptions-item>
        <el-descriptions-item label="客户端IP">{{ currentOrder.client_ip || '-' }}</el-descriptions-item>
        <el-descriptions-item label="过期时间">{{ currentOrder.expire_time || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 退款对话框 -->
    <el-dialog v-model="refundVisible" title="订单退款" width="500px">
      <el-form :model="refundForm" label-width="100px">
        <el-form-item label="订单号">
          <span>{{ currentOrder?.order_no }}</span>
        </el-form-item>
        <el-form-item label="退款金额">
          <span class="amount">¥{{ currentOrder?.amount }}</span>
        </el-form-item>
        <el-form-item label="退款原因" required>
          <el-input
            v-model="refundForm.reason"
            type="textarea"
            rows="3"
            placeholder="请输入退款原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="refundVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmRefund" :loading="refunding">确认退款</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import { getRechargeOrders, getRechargeStats, updateOrderStatus, refundOrder, manualCompleteOrder } from '@/api/payment'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

// 权限检查 - 只有admin可以执行补单和退款操作
const canModifyOrder = computed(() => {
  return userStore.userInfo?.role === 'admin'
})

const loading = ref(false)
const orderList = ref([])
const total = ref(0)
const stats = reactive({
  today_count: 0,
  today_amount: 0,
  month_count: 0,
  month_amount: 0
})

const queryForm = reactive({
  order_no: '',
  user_id: '',
  status: '',
  date_range: [],
  page: 1,
  page_size: 20
})

const detailVisible = ref(false)
const refundVisible = ref(false)
const refunding = ref(false)
const currentOrder = ref(null)
const refundForm = reactive({
  reason: ''
})

onMounted(() => {
  loadOrders()
  loadStats()
})

function getStatusType(status) {
  const map = {
    pending: 'warning',
    paid: 'success',
    cancelled: 'info',
    refunded: 'danger'
  }
  return map[status] || 'info'
}

function getStatusText(status) {
  const map = {
    pending: '待支付',
    paid: '已支付',
    cancelled: '已取消',
    refunded: '已退款'
  }
  return map[status] || status
}

async function loadOrders() {
  loading.value = true
  try {
    const params = { ...queryForm }
    if (params.date_range && params.date_range.length === 2) {
      params.start_date = params.date_range[0]
      params.end_date = params.date_range[1]
    }
    delete params.date_range
    
    const { data } = await getRechargeOrders(params)
    orderList.value = data.list || []
    total.value = data.total || 0
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const { data } = await getRechargeStats()
    if (data) {
      Object.assign(stats, data)
    }
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

function handleSearch() {
  queryForm.page = 1
  loadOrders()
}

function handleReset() {
  Object.assign(queryForm, {
    order_no: '',
    user_id: '',
    status: '',
    date_range: [],
    page: 1,
    page_size: 20
  })
  loadOrders()
}

function handleSizeChange(val) {
  queryForm.page_size = val
  loadOrders()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadOrders()
}

function handleDetail(row) {
  currentOrder.value = row
  detailVisible.value = true
}

async function handleComplete(row) {
  try {
    await ElMessageBox.confirm(
      `确定要手动完成订单 ${row.order_no} 吗？\n此操作将为用户充值 ${row.points} 积分，请谨慎操作！`,
      '确认补单',
      {
        confirmButtonText: '确认补单',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    await manualCompleteOrder(row.order_no)
    ElMessage.success('补单成功')
    loadOrders()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '补单失败')
    }
  }
}

function handleRefund(row) {
  currentOrder.value = row
  refundForm.reason = ''
  refundVisible.value = true
}

async function confirmRefund() {
  if (!refundForm.reason.trim()) {
    ElMessage.warning('请输入退款原因')
    return
  }
  
  refunding.value = true
  try {
    await refundOrder(currentOrder.value.order_no, refundForm)
    ElMessage.success('退款申请已提交')
    refundVisible.value = false
    loadOrders()
  } catch (error) {
    ElMessage.error(error.message || '退款失败')
  } finally {
    refunding.value = false
  }
}
</script>

<style lang="scss" scoped>
.stats-row {
  margin-bottom: 20px;
}

.stats-item {
  text-align: center;
  padding: 10px 0;
}

.stats-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 10px;
}

.stats-value {
  font-size: 24px;
  font-weight: bold;
  color: #303133;
}

.text-success {
  color: #67c23a;
}

.text-gray {
  color: #909399;
}

.search-form {
  margin-bottom: 20px;
}

.amount {
  color: #f56c6c;
  font-weight: bold;
}

.points {
  color: #e6a23c;
  font-weight: bold;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
