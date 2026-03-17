<template>
  <div class="app-container">
    <el-card v-if="pageError" shadow="never" class="page-state-card">
      <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
        <template #extra>
          <el-button type="primary" :loading="pageLoading" @click="handleReload">重新加载</el-button>
        </template>
      </el-result>
    </el-card>

    <div v-else v-loading="pageLoading">
      <el-row :gutter="16" class="stats-row">
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">支付订单</div>
            <div class="stats-value">{{ stats.order_count || 0 }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">累计实收</div>
            <div class="stats-value text-success">¥{{ formatAmount(stats.total_amount) }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">累计发放积分</div>
            <div class="stats-value text-warning">{{ stats.total_points || 0 }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">待支付订单</div>
            <div class="stats-value">{{ stats.pending_count || 0 }}</div>
          </el-card>
        </el-col>
      </el-row>

      <el-card class="search-form" shadow="never">
        <el-form :model="queryForm" inline>
          <el-form-item label="订单号 / 支付单号">
            <el-input v-model="queryForm.order_no" placeholder="请输入订单号或支付单号" clearable />
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

      <el-card shadow="never">
        <el-table v-loading="pageLoading" :data="orderList" stripe border>
          <el-table-column type="index" label="#" width="56" />
          <el-table-column prop="order_no" label="订单号" min-width="190" show-overflow-tooltip />
          <el-table-column prop="pay_order_no" label="支付单号" min-width="190" show-overflow-tooltip>
            <template #default="{ row }">
              <span>{{ row.pay_order_no || '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="user_id" label="用户ID" width="90" />
          <el-table-column prop="user_nickname" label="用户昵称" min-width="120" show-overflow-tooltip>
            <template #default="{ row }">
              <span>{{ row.user_nickname || '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="amount" label="金额" width="100">
            <template #default="{ row }">
              <span class="amount">¥{{ formatAmount(row.amount) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="points" label="积分" width="90">
            <template #default="{ row }">
              <span class="points">{{ row.points }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="payment_type" label="支付方式" width="110">
            <template #default="{ row }">
              {{ getPaymentTypeText(row.payment_type) }}
            </template>
          </el-table-column>
          <el-table-column prop="pay_time" label="支付时间" width="168">
            <template #default="{ row }">
              <span>{{ row.pay_time || '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="168" />
          <el-table-column label="操作" width="220" fixed="right">
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
            v-model:page-size="queryForm.limit"
            :total="total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </el-card>

      <el-dialog v-model="detailVisible" title="充值订单详情" width="720px">
        <el-descriptions v-if="currentOrder" :column="2" border>
          <el-descriptions-item label="订单号" :span="2">{{ currentOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="支付单号" :span="2">{{ currentOrder.pay_order_no || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款单号" :span="2">{{ currentOrder.refund_no || '-' }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentOrder.user_id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentOrder.user_nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="用户手机号">{{ currentOrder.user_phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="支付方式">{{ getPaymentTypeText(currentOrder.payment_type) }}</el-descriptions-item>
          <el-descriptions-item label="充值金额">¥{{ formatAmount(currentOrder.amount) }}</el-descriptions-item>
          <el-descriptions-item label="到账积分">{{ currentOrder.points }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getStatusType(currentOrder.status)">{{ getStatusText(currentOrder.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="支付时间">{{ currentOrder.pay_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ currentOrder.created_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="过期时间">{{ currentOrder.expire_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="客户端IP">{{ currentOrder.client_ip || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款金额">{{ currentOrder.refund_amount ? `¥${formatAmount(currentOrder.refund_amount)}` : '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款时间">{{ currentOrder.refund_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款原因" :span="2">{{ currentOrder.refund_reason || '-' }}</el-descriptions-item>
        </el-descriptions>
      </el-dialog>

      <el-dialog v-model="refundVisible" title="充值订单退款" width="500px">
        <el-form :model="refundForm" label-width="100px">
          <el-form-item label="订单号">
            <span>{{ currentOrder?.order_no }}</span>
          </el-form-item>
          <el-form-item label="退款金额">
            <span class="amount">¥{{ formatAmount(currentOrder?.amount) }}</span>
          </el-form-item>
          <el-form-item label="退款原因" required>
            <el-input
              v-model="refundForm.reason"
              type="textarea"
              rows="3"
              maxlength="100"
              show-word-limit
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
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search } from '@element-plus/icons-vue'
import {
  getRechargeOrders,
  getRechargeStats,
  getOrderDetail,
  refundOrder,
  manualCompleteOrder
} from '@/api/payment'
import { useUserStore } from '@/stores/user'
import { createReadonlyErrorState } from '@/utils/page-error'

const userStore = useUserStore()
const pageLoading = ref(false)
const pageError = ref(null)
const total = ref(0)
const orderList = ref([])
const currentOrder = ref(null)
const detailVisible = ref(false)
const refundVisible = ref(false)
const refunding = ref(false)

const stats = reactive(createDefaultStats())
const queryForm = reactive({
  order_no: '',
  user_id: '',
  status: '',
  date_range: [],
  page: 1,
  limit: 20
})

const refundForm = reactive({
  reason: ''
})

const canModifyOrder = computed(() => userStore.userInfo?.role === 'admin' && !pageError.value)

onMounted(() => {
  loadPageData()
})

function createDefaultStats() {
  return {
    total_amount: 0,
    total_points: 0,
    order_count: 0,
    user_count: 0,
    pending_count: 0,
    avg_amount: 0
  }
}

function resetPageState() {
  orderList.value = []
  total.value = 0
  Object.assign(stats, createDefaultStats())
  currentOrder.value = null
  detailVisible.value = false
  refundVisible.value = false
  refundForm.reason = ''
}

function buildOrderParams() {
  const params = {
    page: queryForm.page,
    limit: queryForm.limit,
    keyword: queryForm.order_no?.trim() || undefined,
    user_id: queryForm.user_id?.trim() || undefined,
    status: queryForm.status || undefined
  }

  if (queryForm.date_range?.length === 2) {
    params.start_date = queryForm.date_range[0]
    params.end_date = queryForm.date_range[1]
  }

  return params
}

function buildStatsParams() {
  const params = {}

  if (queryForm.date_range?.length === 2) {
    params.start_date = queryForm.date_range[0]
    params.end_date = queryForm.date_range[1]
  }

  return params
}

function formatAmount(value) {
  return Number(value || 0).toFixed(2)
}

function getStatusType(status) {
  return {
    pending: 'warning',
    paid: 'success',
    cancelled: 'info',
    refunded: 'danger'
  }[status] || 'info'
}

function getStatusText(status) {
  return {
    pending: '待支付',
    paid: '已支付',
    cancelled: '已取消',
    refunded: '已退款'
  }[status] || status || '-'
}

function getPaymentTypeText(type) {
  return {
    wechat: '微信支付',
    wechat_jsapi: '微信 JSAPI',
    alipay: '支付宝'
  }[type] || type || '-'
}

async function fetchOrders() {
  const { data } = await getRechargeOrders(buildOrderParams())
  return {
    list: Array.isArray(data?.list) ? data.list : [],
    total: Number(data?.total || 0)
  }
}

async function fetchStats() {
  const { data } = await getRechargeStats(buildStatsParams())
  return {
    total_amount: Number(data?.total_amount || 0),
    total_points: Number(data?.total_points || 0),
    order_count: Number(data?.order_count || 0),
    user_count: Number(data?.user_count || 0),
    pending_count: Number(data?.pending_count || 0),
    avg_amount: Number(data?.avg_amount || 0)
  }
}

async function loadPageData() {
  pageLoading.value = true
  try {
    const [ordersResult, statsResult] = await Promise.allSettled([
      fetchOrders(),
      fetchStats()
    ])

    const failedResult = [ordersResult, statsResult].find(result => result.status === 'rejected')
    if (failedResult) {
      resetPageState()
      pageError.value = createReadonlyErrorState(failedResult.reason, '充值订单')
      return false
    }

    orderList.value = ordersResult.value.list
    total.value = ordersResult.value.total
    Object.assign(stats, statsResult.value)
    pageError.value = null
    return true
  } finally {
    pageLoading.value = false
  }
}

async function handleReload() {
  await loadPageData()
}

function handleSearch() {
  queryForm.page = 1
  loadPageData()
}

function handleReset() {
  Object.assign(queryForm, {
    order_no: '',
    user_id: '',
    status: '',
    date_range: [],
    page: 1,
    limit: 20
  })
  loadPageData()
}

function handleSizeChange(size) {
  queryForm.limit = size
  queryForm.page = 1
  loadPageData()
}

function handleCurrentChange(page) {
  queryForm.page = page
  loadPageData()
}

async function handleDetail(row) {
  try {
    const { data } = await getOrderDetail(row.order_no || row.id)
    currentOrder.value = data
    detailVisible.value = true
  } catch (error) {
    ElMessage.error(error.message || '加载订单详情失败')
  }
}

async function handleComplete(row) {
  try {
    await ElMessageBox.confirm(
      `确定要手动完成订单 ${row.order_no} 吗？该操作会为用户补发 ${row.points} 积分。`,
      '确认补单',
      {
        confirmButtonText: '确认补单',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    await manualCompleteOrder(row.order_no)
    ElMessage.success('补单成功')
    await loadPageData()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '补单失败')
    }
  }
}

function handleRefund(row) {
  currentOrder.value = row
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
    await refundOrder(currentOrder.value.order_no, { reason: refundForm.reason.trim() })
    ElMessage.success('退款成功')
    refundVisible.value = false
    await loadPageData()
  } catch (error) {
    ElMessage.error(error.message || '退款失败')
  } finally {
    refunding.value = false
  }
}
</script>

<style lang="scss" scoped>
.app-container {
  padding: 20px;
}

.page-state-card,
.stats-row,
.search-form {
  margin-bottom: 20px;
}

.stats-card {
  height: 100%;
}

.stats-label {
  color: #909399;
  font-size: 14px;
  margin-bottom: 10px;
}

.stats-value {
  font-size: 24px;
  font-weight: 700;
  color: #303133;
}

.text-success {
  color: #67c23a;
}

.text-warning {
  color: #e6a23c;
}

.amount {
  color: #f56c6c;
  font-weight: 700;
}

.points {
  color: #e6a23c;
  font-weight: 700;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
