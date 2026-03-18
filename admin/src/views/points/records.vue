<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="变动类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable>
            <el-option label="增加" value="add" />
            <el-option label="减少" value="reduce" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table v-loading="loading" :data="recordList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="direction" label="增减方向" width="100">
          <template #default="{ row }">
            <el-tag :type="row.direction === 'add' ? 'success' : 'danger'">
              {{ row.direction_label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="business_label" label="记录类型" min-width="140" show-overflow-tooltip />
        <el-table-column prop="amount" label="变动数量" width="110">
          <template #default="{ row }">
            <span :class="row.direction === 'add' ? 'text-success' : 'text-danger'">
              {{ formatAmount(row) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="balance" label="变动后余额" width="120">
          <template #default="{ row }">
            <span>{{ formatBalance(row.balance) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="reason" label="变动原因" min-width="220" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="160" />
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { getPointsRecords } from '@/api/points'

const loading = ref(false)
const recordList = ref([])
const total = ref(0)

const queryForm = reactive({
  user_id: '',
  type: '',
  page: 1,
  pageSize: 20
})

onMounted(() => {
  loadRecords()
})

function normalizeRecordRow(row = {}) {
  const direction = String(row.direction || row.type || '').toLowerCase() === 'reduce' ? 'reduce' : 'add'
  const fallbackAmount = Math.abs(Number(row.points || 0))
  const rawAmount = Number(row.amount ?? fallbackAmount)
  const balance = row.balance === '' || row.balance === null || row.balance === undefined
    ? null
    : Number(row.balance)

  return {
    ...row,
    direction,
    direction_label: row.direction_label || (direction === 'reduce' ? '减少' : '增加'),
    business_label: String(row.business_label || row.action || row.business_type || '积分变动'),
    amount: Number.isFinite(rawAmount) ? Math.abs(rawAmount) : 0,
    balance,
    reason: String(row.reason || row.remark || row.action || '积分变动'),
    created_at: String(row.created_at || '-')
  }
}

async function loadRecords() {
  loading.value = true
  try {
    const { data } = await getPointsRecords(queryForm)
    const list = Array.isArray(data?.list) ? data.list : []
    recordList.value = list.map(normalizeRecordRow)
    total.value = Number(data?.total || 0)
  } finally {
    loading.value = false
  }
}

function formatAmount(row) {
  return `${row.direction === 'reduce' ? '-' : '+'}${Number(row.amount || 0)}`
}

function formatBalance(balance) {
  return balance === null ? '-' : balance
}

function handleSearch() {
  queryForm.page = 1
  loadRecords()
}

function handleReset() {
  Object.assign(queryForm, {
    user_id: '',
    type: '',
    page: 1,
    pageSize: 20
  })
  loadRecords()
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  queryForm.page = 1
  loadRecords()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadRecords()
}
</script>

<style lang="scss" scoped>
.search-form {
  margin-bottom: 20px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.text-success {
  color: #67c23a;
  font-weight: 600;
}

.text-danger {
  color: #f56c6c;
  font-weight: 600;
}
</style>
