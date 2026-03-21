<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const recordsList = ref([])
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0
})

onMounted(() => {
  fetchRecordsList()
})

async function fetchRecordsList() {
  loading.value = true
  try {
    // 这里调用API获取积分记录
    // const res = await getPointsRecords({ ...pagination.value })
    // recordsList.value = res.data.list
    // pagination.value.total = res.data.total
  } catch (error) {
    ElMessage.error('获取积分记录失败')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page) {
  pagination.value.current = page
  fetchRecordsList()
}

function getTypeText(type) {
  const typeMap = {
    'recharge': '充值',
    'consume': '消费',
    'refund': '退款',
    'admin_adjust': '管理员调整',
    'daily_sign': '每日签到',
    'invite_reward': '邀请奖励'
  }
  return typeMap[type] || type
}

function getTypeTagType(type) {
  const typeMap = {
    'recharge': 'success',
    'refund': 'success',
    'consume': 'danger',
    'admin_adjust': 'warning',
    'daily_sign': 'info',
    'invite_reward': 'info'
  }
  return typeMap[type] || 'info'
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>积分记录</span>
          <el-button type="primary" @click="fetchRecordsList">刷新</el-button>
        </div>
      </template>

      <el-table :data="recordsList" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_name" label="用户名" width="120" />
        <el-table-column prop="user_phone" label="手机号" width="130" />
        <el-table-column prop="points" label="积分数值" width="120">
          <template #default="{ row }">
            <span :style="{ color: row.points > 0 ? '#67C23A' : '#F56C6C' }">
              {{ row.points > 0 ? '+' : '' }}{{ row.points }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="type" label="类型" width="120">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)" size="small">
              {{ getTypeText(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="balance_after" label="调整后余额" width="130" />
        <el-table-column prop="reason" label="原因" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="180" />
      </el-table>

      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="fetchRecordsList"
        style="margin-top: 20px; justify-content: center"
      />
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
