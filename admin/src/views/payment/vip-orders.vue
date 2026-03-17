<template>
  <div class="app-container">
    <!-- 搜索表单 -->
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="订单号">
          <el-input v-model="queryForm.order_no" placeholder="请输入订单号" clearable />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="queryForm.status" placeholder="全部状态" clearable style="width: 150px;">
            <el-option label="待支付" :value="0" />
            <el-option label="已支付" :value="1" />
            <el-option label="已取消" :value="2" />
            <el-option label="已退款" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间">
          <el-date-picker
            v-model="queryForm.date_range"
            type="daterange"
            range-separator="至"
            start-placeholder="开始"
            end-placeholder="结束"
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
      <el-table v-loading="loading" :data="orderList" stripe border>
        <el-table-column type="index" label="#" width="60" />
        <el-table-column prop="order_no" label="订单号" width="200" show-overflow-tooltip />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column label="用户姓名" width="120">
          <template #default="{ row }">
            <span>{{ row.nickname || row.phone || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="package_name" label="套餐名称" width="150" />
        <el-table-column label="支付金额" width="120">
          <template #default="{ row }">
            <span class="amount">¥{{ row.pay_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="180" />
        <el-table-column prop="pay_time" label="支付时间" width="180">
          <template #default="{ row }">
            {{ row.pay_time || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" min-width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
            <el-button 
              v-if="row.status === 1 && canModifyOrder" 
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
    <el-dialog v-model="detailVisible" title="VIP订单详情" width="650px">
      <el-descriptions :column="2" border v-if="currentOrder">
        <el-descriptions-item label="订单ID">{{ currentOrder.id }}</el-descriptions-item>
        <el-descriptions-item label="订单号" :span="2">{{ currentOrder.order_no }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ currentOrder.user_id }}</el-descriptions-item>
        <el-descriptions-item label="用户昵称">{{ currentOrder.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="用户电话">{{ currentOrder.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="套餐名称">{{ currentOrder.package_name }}</el-descriptions-item>
        <el-descriptions-item label="套餐价格">¥{{ currentOrder.package_price }}</el-descriptions-item>
        <el-descriptions-item label="实付金额"><span class="amount">¥{{ currentOrder.pay_amount }}</span></el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag :type="getStatusType(currentOrder.status)">{{ getStatusText(currentOrder.status) }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="支付平台">{{ currentOrder.platform || 'wechat' }}</el-descriptions-item>
        <el-descriptions-item label="第三方单号" :span="2">{{ currentOrder.transaction_id || '-' }}</el-descriptions-item>
        <el-descriptions-item label="下单时间">{{ currentOrder.created_at }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ currentOrder.pay_time || '-' }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.status === 3" label="退款单号" :span="2">{{ currentOrder.refund_no || '-' }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.status === 3" label="退款金额">¥{{ currentOrder.refund_amount }}</el-descriptions-item>
        <el-descriptions-item v-if="currentOrder.status === 3" label="退款原因" :span="2">{{ currentOrder.refund_reason || '-' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 退款对话框 -->
    <el-dialog v-model="refundVisible" title="订单退款" width="500px">
      <el-form :model="refundForm" label-width="100px">
        <el-form-item label="订单号">
          <span>{{ currentOrder?.order_no }}</span>
        </el-form-item>
        <el-form-item label="支付金额">
          <span class="amount">¥{{ currentOrder?.pay_amount }}</span>
        </el-form-item>
        <el-form-item label="退款金额" required>
          <el-input-number 
            v-model="refundForm.amount" 
            :precision="2" 
            :min="0.01" 
            :max="currentOrder?.pay_amount"
          />
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
import { getVipOrders, getVipOrderDetail, refundVipOrder } from '@/api/payment'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
const canModifyOrder = computed(() => userStore.userInfo?.role === 'admin')

const loading = ref(false)
const orderList = ref([])
const total = ref(0)
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
  amount: 0,
  reason: '管理员后台退款'
})

onMounted(() => {
  loadOrders()
})

function getStatusType(status) {
  const map = {
    0: 'warning',
    1: 'success',
    2: 'info',
    3: 'danger'
  }
  return map[status] || 'info'
}

function getStatusText(status) {
  const map = {
    0: '待支付',
    1: '已支付',
    2: '已取消',
    3: '已退款'
  }
  return map[status] || status
}

async function loadOrders() {
  loading.value = true
  try {
    const params = { ...queryForm }
    if (params.date_range && params.date_range.length === 2) {
      params.date_start = params.date_range[0]
      params.date_end = params.date_range[1]
    }
    delete params.date_range
    
    const { data } = await getVipOrders(params)
    orderList.value = data.list || []
    total.value = data.total || 0
  } finally {
    loading.value = false
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

async function handleDetail(row) {
  try {
    const { data } = await getVipOrderDetail(row.id)
    currentOrder.value = data
    detailVisible.value = true
  } catch (error) {
    ElMessage.error('加载详情失败')
  }
}

function handleRefund(row) {
  currentOrder.value = row
  refundForm.amount = row.pay_amount
  refundForm.reason = '管理员后台退款'
  refundVisible.value = true
}

async function confirmRefund() {
  if (!refundForm.reason.trim()) {
    ElMessage.warning('请输入退款原因')
    return
  }
  
  refunding.value = true
  try {
    await refundVipOrder({
      order_id: currentOrder.value.id,
      amount: refundForm.amount,
      reason: refundForm.reason
    })
    ElMessage.success('退款成功')
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
.search-form {
  margin-bottom: 20px;
}

.amount {
  color: #f56c6c;
  font-weight: bold;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
