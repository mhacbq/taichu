<template>
  <div class="points-records-page">
    <div class="page-header">
      <h2>积分记录</h2>
      <div class="header-actions">
        <el-button :icon="Download" @click="handleExport">导出记录</el-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <el-card class="filter-card" shadow="never">
      <el-form :model="filters" inline>
        <el-form-item label="用户ID">
          <el-input v-model="filters.user_id" placeholder="用户ID" clearable style="width:120px" />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="filters.phone" placeholder="手机号" clearable style="width:140px" />
        </el-form-item>
        <el-form-item label="变动类型">
          <el-select v-model="filters.type" placeholder="全部" clearable style="width:120px">
            <el-option label="消耗" value="consume" />
            <el-option label="充值" value="recharge" />
            <el-option label="奖励" value="reward" />
            <el-option label="退款" value="refund" />
            <el-option label="签到" value="checkin" />
            <el-option label="任务" value="task" />
            <el-option label="调整" value="adjust" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="filters.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width:240px"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" @click="handleSearch">查询</el-button>
          <el-button @click="resetFilters">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6" v-for="stat in stats" :key="stat.label">
        <el-card class="stat-card" shadow="never">
          <div class="stat-value" :class="stat.color">{{ stat.value }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 数据表格 -->
    <el-card shadow="never">
      <el-table :data="records" v-loading="loading" stripe>
        <el-table-column type="index" width="60" label="#" />
        <el-table-column prop="id" label="记录ID" width="80" />
        <el-table-column label="用户" width="140">
          <template #default="{ row }">
            <div>{{ row.nickname || row.username || '—' }}</div>
            <div class="text-small text-gray">ID: {{ row.user_id }}</div>
          </template>
        </el-table-column>
        <el-table-column label="积分变动" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="row.amount > 0 ? 'success' : 'danger'" size="small">
              {{ row.amount > 0 ? '+' : '' }}{{ row.amount }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="balance" label="变动后余额" width="110" align="center" />
        <el-table-column label="类型" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)" size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="备注说明" min-width="160" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="160" />
        <el-table-column label="操作" width="80" align="center" fixed="right">
          <template #default="{ row }">
            <el-button text size="small" type="primary" @click="viewDetail(row)">详情</el-button>
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
          @size-change="fetchRecords"
          @current-change="fetchRecords"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="积分记录详情" width="480px">
      <el-descriptions :column="2" border v-if="currentRecord">
        <el-descriptions-item label="记录ID">{{ currentRecord.id }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ currentRecord.user_id }}</el-descriptions-item>
        <el-descriptions-item label="用户昵称">{{ currentRecord.nickname || '—' }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ currentRecord.phone || '—' }}</el-descriptions-item>
        <el-descriptions-item label="积分变动">
          <span :style="{ color: currentRecord.amount > 0 ? '#67c23a' : '#f56c6c', fontWeight: 'bold' }">
            {{ currentRecord.amount > 0 ? '+' : '' }}{{ currentRecord.amount }}
          </span>
        </el-descriptions-item>
        <el-descriptions-item label="变动后余额">{{ currentRecord.balance }}</el-descriptions-item>
        <el-descriptions-item label="类型">{{ getTypeLabel(currentRecord.type) }}</el-descriptions-item>
        <el-descriptions-item label="时间">{{ currentRecord.created_at }}</el-descriptions-item>
        <el-descriptions-item label="备注" :span="2">{{ currentRecord.description || '无' }}</el-descriptions-item>
        <el-descriptions-item label="关联订单" :span="2">{{ currentRecord.order_no || '无' }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import { getPointsRecords, getPointsStats } from '@/api/admin'

const loading = ref(false)
const records = ref([])
const detailVisible = ref(false)
const currentRecord = ref(null)

const filters = reactive({
  user_id: '',
  phone: '',
  type: '',
  dateRange: []
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const statsData = ref({
  total_consume: 0,
  total_recharge: 0,
  total_reward: 0,
  total_adjust: 0
})

const stats = computed(() => [
  { label: '今日消耗', value: statsData.value.today_consume ?? 0, color: 'red' },
  { label: '今日充值', value: statsData.value.today_recharge ?? 0, color: 'green' },
  { label: '本月总消耗', value: statsData.value.month_consume ?? 0, color: 'orange' },
  { label: '本月总充值', value: statsData.value.month_recharge ?? 0, color: 'blue' }
])

const typeMap = {
  consume: { label: '消耗', type: 'danger' },
  recharge: { label: '充值', type: 'success' },
  reward: { label: '奖励', type: 'success' },
  refund: { label: '退款', type: 'warning' },
  checkin: { label: '签到', type: 'primary' },
  task: { label: '任务', type: 'primary' },
  adjust: { label: '调整', type: 'info' }
}

function getTypeLabel(type) {
  return typeMap[type]?.label ?? type ?? '未知'
}

function getTypeTagType(type) {
  return typeMap[type]?.type ?? 'info'
}

async function fetchRecords() {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.pageSize,
      user_id: filters.user_id || undefined,
      phone: filters.phone || undefined,
      type: filters.type || undefined,
      start_date: filters.dateRange?.[0] || undefined,
      end_date: filters.dateRange?.[1] || undefined
    }
    const res = await getPointsRecords(params)
    const data = res?.data ?? res
    records.value = data?.list ?? data?.data ?? []
    pagination.total = data?.total ?? 0
  } catch (e) {
    ElMessage.error('获取积分记录失败')
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const res = await getPointsStats()
    statsData.value = res?.data ?? res ?? {}
  } catch {}
}

function handleSearch() {
  pagination.page = 1
  fetchRecords()
}

function resetFilters() {
  Object.assign(filters, { user_id: '', phone: '', type: '', dateRange: [] })
  handleSearch()
}

function viewDetail(row) {
  currentRecord.value = row
  detailVisible.value = true
}

async function handleExport() {
  ElMessage.info('导出功能开发中')
}

onMounted(() => {
  fetchRecords()
  fetchStats()
})
</script>

<style scoped>
.points-records-page { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { margin: 0; font-size: 20px; }
.filter-card { margin-bottom: 16px; }
.filter-card :deep(.el-card__body) { padding: 16px 16px 0; }
.stats-row { margin-bottom: 16px; }
.stat-card :deep(.el-card__body) { padding: 16px; text-align: center; }
.stat-value { font-size: 24px; font-weight: bold; margin-bottom: 4px; }
.stat-value.red { color: #f56c6c; }
.stat-value.green { color: #67c23a; }
.stat-value.orange { color: #e6a23c; }
.stat-value.blue { color: #409eff; }
.stat-label { font-size: 13px; color: #909399; }
.pagination-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
.text-small { font-size: 12px; }
.text-gray { color: #909399; }
</style>
