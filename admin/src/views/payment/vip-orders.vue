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
            <div class="stats-label">当前筛选总订单</div>
            <div class="stats-value">{{ total }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">已支付</div>
            <div class="stats-value text-success">{{ paidCount }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">已退款</div>
            <div class="stats-value text-danger">{{ refundedCount }}</div>
          </el-card>
        </el-col>
        <el-col :xs="12" :sm="12" :md="6">
          <el-card shadow="hover" class="stats-card">
            <div class="stats-label">当前页实收金额</div>
            <div class="stats-value text-warning">¥{{ formatAmount(currentPageAmount) }}</div>
          </el-card>
        </el-col>
      </el-row>

      <el-card class="search-form" shadow="never">
        <el-form :model="queryForm" inline>
          <el-form-item label="订单号">
            <el-input v-model="queryForm.order_no" placeholder="请输入订单号" clearable />
          </el-form-item>
          <el-form-item label="用户ID">
            <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
          </el-form-item>
          <el-form-item label="订单状态">
            <el-select v-model="queryForm.status" placeholder="全部状态" clearable style="width: 150px">
              <el-option label="待支付" :value="0" />
              <el-option label="已支付" :value="1" />
              <el-option label="已取消" :value="2" />
              <el-option label="已退款" :value="3" />
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
          <el-table-column prop="user_id" label="用户ID" width="90" />
          <el-table-column label="用户信息" min-width="140">
            <template #default="{ row }">
              <div class="user-cell">
                <span>{{ row.nickname || '-' }}</span>
                <span class="sub-text">{{ row.phone || '未留手机号' }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="package_name" label="套餐名称" min-width="140" show-overflow-tooltip />
          <el-table-column prop="package_price" label="套餐原价" width="110">
            <template #default="{ row }">
              <span>{{ row.package_price ? `¥${formatAmount(row.package_price)}` : '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="pay_amount" label="实付金额" width="110">
            <template #default="{ row }">
              <span class="amount">¥{{ formatAmount(row.pay_amount) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="下单时间" width="168" />
          <el-table-column prop="pay_time" label="支付时间" width="168">
            <template #default="{ row }">
              {{ row.pay_time || '-' }}
            </template>
          </el-table-column>
          <el-table-column prop="refund_time" label="退款时间" width="168">
            <template #default="{ row }">
              {{ row.refund_time || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="操作" min-width="160" fixed="right">
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

      <el-dialog v-model="detailVisible" title="VIP订单详情" width="720px">
        <el-descriptions v-if="currentOrder" :column="2" border>
          <el-descriptions-item label="订单ID">{{ currentOrder.id }}</el-descriptions-item>
          <el-descriptions-item label="订单号" :span="2">{{ currentOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentOrder.user_id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentOrder.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentOrder.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="套餐名称">{{ currentOrder.package_name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="套餐原价">{{ currentOrder.package_price ? `¥${formatAmount(currentOrder.package_price)}` : '-' }}</el-descriptions-item>
          <el-descriptions-item label="实付金额"><span class="amount">¥{{ formatAmount(currentOrder.pay_amount) }}</span></el-descriptions-item>
          <el-descriptions-item label="支付平台">{{ currentOrder.platform || 'wechat' }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getStatusType(currentOrder.status)">{{ getStatusText(currentOrder.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="第三方单号" :span="2">{{ currentOrder.transaction_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="下单时间">{{ currentOrder.created_at || '-' }}</el-descriptions-item>
          <el-descriptions-item label="支付时间">{{ currentOrder.pay_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款单号" :span="2">{{ currentOrder.refund_no || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款金额">{{ currentOrder.refund_amount ? `¥${formatAmount(currentOrder.refund_amount)}` : '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款时间">{{ currentOrder.refund_time || '-' }}</el-descriptions-item>
          <el-descriptions-item label="退款原因" :span="2">{{ currentOrder.refund_reason || '-' }}</el-descriptions-item>
        </el-descriptions>
      </el-dialog>

      <el-dialog v-model="refundVisible" title="VIP订单退款" width="500px">
        <el-form :model="refundForm" label-width="100px">
          <el-form-item label="订单号">
            <span>{{ currentOrder?.order_no }}</span>
          </el-form-item>
          <el-form-item label="支付金额">
            <span class="amount">¥{{ formatAmount(currentOrder?.pay_amount) }}</span>
          </el-form-item>
          <el-form-item label="退款金额" required>
            <el-input-number
              v-model="refundForm.amount"
              :precision="2"
              :min="0.01"
              :max="Number(currentOrder?.pay_amount || 0)"
            />
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
import { getVipOrders, getVipOrderDetail, refundVipOrder } from '@/api/payment'
import { useUserStore } from '@/stores/user'
import { createReadonlyErrorState } from '@/utils/page-error'

const userStore = useUserStore()
const pageLoading = ref(false)
const pageError = ref(null)
const orderList = ref([])
const total = ref(0)
const currentOrder = ref(null)
const detailVisible = ref(false)
const refundVisible = ref(false)
const refunding = ref(false)

const queryForm = reactive({
  order_no: '',
  user_id: '',
  status: '',
  date_range: [],
  page: 1,
  page_size: 20
})

const refundForm = reactive({
  amount: 0,
  reason: '管理员后台退款'
})

const readonlyMode = computed(() => Boolean(pageError.value))
const canModifyOrder = computed(() => {
  const hasWritePermission = userStore.permissions?.includes('*')
    || userStore.permissions?.includes('config_manage')
    || userStore.roles?.includes('admin')

  return hasWritePermission && !readonlyMode.value
})
const paidCount = computed(() => orderList.value.filter(item => Number(item.status) === 1).length)

const refundedCount = computed(() => orderList.value.filter(item => Number(item.status) === 3).length)
const currentPageAmount = computed(() => orderList.value.reduce((sum, item) => sum + Number(item.pay_amount || 0), 0))

onMounted(() => {
  loadOrders()
})

function resetPageState() {
  orderList.value = []
  total.value = 0
  currentOrder.value = null
  detailVisible.value = false
  refundVisible.value = false
  refundForm.amount = 0
  refundForm.reason = '管理员后台退款'
}

function buildParams() {
  const params = {
    order_no: queryForm.order_no?.trim() || undefined,
    user_id: queryForm.user_id?.trim() || undefined,
    status: queryForm.status === '' ? undefined : queryForm.status,
    page: queryForm.page,
    page_size: queryForm.page_size
  }

  if (queryForm.date_range?.length === 2) {
    params.date_start = `${queryForm.date_range[0]} 00:00:00`
    params.date_end = `${queryForm.date_range[1]} 23:59:59`
  }

  return params
}

function formatAmount(value) {
  return Number(value || 0).toFixed(2)
}

function getStatusType(status) {
  return {
    0: 'warning',
    1: 'success',
    2: 'info',
    3: 'danger'
  }[Number(status)] || 'info'
}

function getStatusText(status) {
  return {
    0: '待支付',
    1: '已支付',
    2: '已取消',
    3: '已退款'
  }[Number(status)] || status || '-'
}

async function loadOrders() {
  pageLoading.value = true
  try {
    const { data } = await getVipOrders(buildParams(), { showErrorMessage: false })
    orderList.value = Array.isArray(data?.list) ? data.list : []
    total.value = Number(data?.total || 0)
    pageError.value = null
    return true
  } catch (error) {
    resetPageState()
    pageError.value = createReadonlyErrorState(error, 'VIP 订单')
    return false
  } finally {
    pageLoading.value = false
  }
}

async function handleReload() {
  await loadOrders()
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

function handleSizeChange(size) {
  queryForm.page_size = size
  queryForm.page = 1
  loadOrders()
}

function handleCurrentChange(page) {
  queryForm.page = page
  loadOrders()
}

async function handleDetail(row) {
  try {
    const { data } = await getVipOrderDetail(row.id, { showErrorMessage: false })
    currentOrder.value = data
    detailVisible.value = true
  } catch (error) {
    ElMessage.error(error.message || '加载订单详情失败')
  }
}

function handleRefund(row) {
  currentOrder.value = row
  refundForm.amount = Number(row.pay_amount || 0)
  refundForm.reason = '管理员后台退款'
  refundVisible.value = true
}

async function confirmRefund() {
  if (!refundForm.reason.trim()) {
    ElMessage.warning('请输入退款原因')
    return
  }

  if (!refundForm.amount || refundForm.amount <= 0) {
    ElMessage.warning('请输入有效的退款金额')
    return
  }

  refunding.value = true
  try {
    await ElMessageBox.confirm(
      `确定为订单 ${currentOrder.value.order_no} 退款 ¥${formatAmount(refundForm.amount)} 吗？`,
      '确认退款',
      {
        confirmButtonText: '确认退款',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )

    await refundVipOrder({
      order_id: currentOrder.value.id,
      amount: refundForm.amount,
      reason: refundForm.reason.trim()
    }, { showErrorMessage: false })
    ElMessage.success('VIP 订单退款成功')
    refundVisible.value = false
    await loadOrders()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(error.message || '退款失败')
    }
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

.text-danger {
  color: #f56c6c;
}

.user-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sub-text {
  color: #909399;
  font-size: 12px;
}

.amount {
  color: #f56c6c;
  font-weight: 700;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
