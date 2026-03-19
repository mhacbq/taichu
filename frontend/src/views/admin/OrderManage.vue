<template>
  <div class="orders-page">
    <div class="page-header">
      <h2>订单列表</h2>
      <el-button :icon="Download" @click="handleExport">导出订单</el-button>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6" v-for="s in statCards" :key="s.label">
        <el-card class="stat-card" shadow="never">
          <div class="stat-value" :class="s.color">{{ s.value }}</div>
          <div class="stat-label">{{ s.label }}</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 筛选栏 -->
    <el-card class="filter-card" shadow="never">
      <el-form :model="filters" inline>
        <el-form-item label="订单号">
          <el-input v-model="filters.order_no" placeholder="订单号" clearable style="width:180px" />
        </el-form-item>
        <el-form-item label="用户">
          <el-input v-model="filters.user_keyword" placeholder="用户ID/手机号" clearable style="width:140px" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="filters.status" placeholder="全部" clearable style="width:110px">
            <el-option label="待支付" value="pending" />
            <el-option label="已支付" value="paid" />
            <el-option label="已完成" value="completed" />
            <el-option label="已退款" value="refunded" />
            <el-option label="已取消" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间">
          <el-date-picker
            v-model="filters.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始"
            end-placeholder="结束"
            value-format="YYYY-MM-DD"
            style="width:230px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleSearch">查询</el-button>
          <el-button @click="resetFilters">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card shadow="never">
      <el-table :data="orders" v-loading="loading" stripe>
        <el-table-column type="index" width="55" label="#" />
        <el-table-column prop="order_no" label="订单号" width="200" />
        <el-table-column label="用户" width="130">
          <template #default="{ row }">
            <div>{{ row.nickname || row.username || '—' }}</div>
            <div class="text-small text-gray">{{ row.phone || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="140" show-overflow-tooltip>
          <template #default="{ row }">{{ row.product_name || row.package_name || '积分套餐' }}</template>
        </el-table-column>
        <el-table-column label="金额" width="90" align="right">
          <template #default="{ row }">
            <span class="price">¥{{ (row.amount / 100).toFixed(2) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="积分" width="80" align="center">
          <template #default="{ row }">
            <span class="text-primary">+{{ row.points ?? '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="155" />
        <el-table-column label="操作" width="180" align="center" fixed="right">
          <template #default="{ row }">
            <el-button text size="small" type="primary" @click="viewDetail(row)">详情</el-button>
            <el-button
              text size="small" type="success"
              v-if="row.status === 'pending'"
              @click="handleManualComplete(row)"
            >确认完成</el-button>
            <el-button
              text size="small" type="warning"
              v-if="row.status === 'paid' || row.status === 'completed'"
              @click="handleRefund(row)"
            >退款</el-button>
            <el-button
              text size="small" type="danger"
              v-if="row.status === 'pending'"
              @click="handleCancel(row)"
            >取消</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next"
          @size-change="fetchOrders"
          @current-change="fetchOrders"
        />
      </div>
    </el-card>

    <!-- 订单详情弹窗 -->
    <el-dialog v-model="detailVisible" :title="`订单详情 - ${currentOrder?.order_no}`" width="580px">
      <el-descriptions :column="2" border v-if="currentOrder">
        <el-descriptions-item label="订单号" :span="2">{{ currentOrder.order_no }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ currentOrder.user_id }}</el-descriptions-item>
        <el-descriptions-item label="用户昵称">{{ currentOrder.nickname || '—' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentOrder.phone || '—' }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="getStatusType(currentOrder.status)" size="small">{{ getStatusLabel(currentOrder.status) }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="商品名称">{{ currentOrder.product_name || '积分套餐' }}</el-descriptions-item>
        <el-descriptions-item label="支付金额">¥{{ (currentOrder.amount / 100).toFixed(2) }}</el-descriptions-item>
        <el-descriptions-item label="赠送积分">{{ currentOrder.points ?? '—' }}</el-descriptions-item>
        <el-descriptions-item label="支付方式">{{ currentOrder.payment_method || '—' }}</el-descriptions-item>
        <el-descriptions-item label="第三方单号" :span="2">{{ currentOrder.trade_no || '—' }}</el-descriptions-item>
        <el-descriptions-item label="下单时间">{{ currentOrder.created_at }}</el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ currentOrder.paid_at || '—' }}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ currentOrder.remark || '无' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 退款弹窗 -->
    <el-dialog v-model="refundVisible" title="申请退款" width="420px">
      <el-form>
        <el-form-item label="退款原因">
          <el-input v-model="refundReason" type="textarea" :rows="3" placeholder="请输入退款原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="refundVisible = false">取消</el-button>
        <el-button type="danger" :loading="operating" @click="submitRefund">确认退款</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import {
  getPaymentOrders,
  getPaymentOrderDetail,
  exportPaymentOrders,
  updateOrderStatus,
  refundOrder,
  manualCompleteOrder,
  cancelOrder
} from '@/api/admin'

const loading = ref(false)
const operating = ref(false)
const orders = ref([])
const detailVisible = ref(false)
const refundVisible = ref(false)
const currentOrder = ref(null)
const refundReason = ref('')

const filters = reactive({
  order_no: '',
  user_keyword: '',
  status: '',
  dateRange: []
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const statsData = ref({})

const statCards = computed(() => [
  { label: '今日订单数', value: statsData.value.today_count ?? 0, color: 'blue' },
  { label: '今日收入', value: `¥${((statsData.value.today_amount ?? 0) / 100).toFixed(2)}`, color: 'green' },
  { label: '本月订单数', value: statsData.value.month_count ?? 0, color: 'orange' },
  { label: '本月收入', value: `¥${((statsData.value.month_amount ?? 0) / 100).toFixed(2)}`, color: 'purple' }
])

const statusMap = {
  pending:   { label: '待支付', type: 'warning' },
  paid:      { label: '已支付', type: 'primary' },
  completed: { label: '已完成', type: 'success' },
  refunded:  { label: '已退款', type: 'info' },
  cancelled: { label: '已取消', type: 'danger' }
}

function getStatusLabel(s) { return statusMap[s]?.label ?? s ?? '未知' }
function getStatusType(s) { return statusMap[s]?.type ?? 'info' }

async function fetchOrders() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.pageSize,
      order_no: filters.order_no || undefined,
      user_keyword: filters.user_keyword || undefined,
      status: filters.status || undefined,
      start_date: filters.dateRange?.[0] || undefined,
      end_date: filters.dateRange?.[1] || undefined
    }
    const res = await getPaymentOrders(params)
    const data = res?.data ?? res
    orders.value = data?.list ?? data?.data ?? []
    pagination.total = data?.total ?? 0
    if (data?.stats) statsData.value = data.stats
  } catch {
    ElMessage.error('获取订单列表失败')
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchOrders()
}

function resetFilters() {
  Object.assign(filters, { order_no: '', user_keyword: '', status: '', dateRange: [] })
  handleSearch()
}

async function viewDetail(row) {
  try {
    const res = await getPaymentOrderDetail(row.id)
    currentOrder.value = res?.data ?? res
    detailVisible.value = true
  } catch {
    currentOrder.value = row
    detailVisible.value = true
  }
}

async function handleManualComplete(row) {
  await ElMessageBox.confirm(`确认手动完成订单 ${row.order_no}？`, '确认', { type: 'warning' })
  operating.value = true
  try {
    await manualCompleteOrder(row.id)
    ElMessage.success('操作成功')
    fetchOrders()
  } catch {
    ElMessage.error('操作失败')
  } finally {
    operating.value = false
  }
}

function handleRefund(row) {
  currentOrder.value = row
  refundReason.value = ''
  refundVisible.value = true
}

async function submitRefund() {
  if (!refundReason.value.trim()) {
    ElMessage.warning('请输入退款原因')
    return
  }
  operating.value = true
  try {
    await refundOrder(currentOrder.value.id, { reason: refundReason.value })
    ElMessage.success('退款申请已提交')
    refundVisible.value = false
    fetchOrders()
  } catch {
    ElMessage.error('退款失败')
  } finally {
    operating.value = false
  }
}

async function handleCancel(row) {
  await ElMessageBox.confirm(`确认取消订单 ${row.order_no}？`, '确认', { type: 'warning' })
  try {
    await cancelOrder(row.id)
    ElMessage.success('订单已取消')
    fetchOrders()
  } catch {
    ElMessage.error('取消失败')
  }
}

async function handleExport() {
  try {
    const params = {
      order_no: filters.order_no || undefined,
      user_keyword: filters.user_keyword || undefined,
      status: filters.status || undefined,
      start_date: filters.dateRange?.[0] || undefined,
      end_date: filters.dateRange?.[1] || undefined
    }
    const res = await exportPaymentOrders(params)
    const url = URL.createObjectURL(new Blob([res]))
    const a = document.createElement('a')
    a.href = url
    a.download = `orders_${Date.now()}.xlsx`
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    ElMessage.error('导出失败')
  }
}

onMounted(fetchOrders)
</script>

<style scoped>
.orders-page { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { margin: 0; font-size: 20px; }
.stats-row { margin-bottom: 16px; }
.stat-card :deep(.el-card__body) { padding: 16px; text-align: center; }
.stat-value { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
.stat-value.blue   { color: #409eff; }
.stat-value.green  { color: #67c23a; }
.stat-value.orange { color: #e6a23c; }
.stat-value.purple { color: #8b5cf6; }
.stat-label { font-size: 13px; color: #909399; }
.filter-card { margin-bottom: 16px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 0; }
.pagination-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
.price { font-weight: 600; color: #f56c6c; }
.text-primary { color: #409eff; font-weight: 600; }
.text-small { font-size: 12px; }
.text-gray { color: #909399; }
</style>
