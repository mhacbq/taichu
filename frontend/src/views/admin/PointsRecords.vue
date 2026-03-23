<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPointsRecords } from '../../api/admin'

const loading = ref(false)
const records = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const searchForm = ref({
  user_id: '',
  type: ''
})

const loadRecords = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...searchForm.value
    }
    const response = await getPointsRecords(params)
    
    if (response.code === 200) {
      records.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载积分记录失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadRecords()
}

const handleReset = () => {
  searchForm.value = { user_id: '', type: '' }
  currentPage.value = 1
  loadRecords()
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadRecords()
}

const getTypeLabel = (type) => {
  const typeMap = {
    'earn': '获得',
    'spend': '消费',
    'adjust_increase': '管理员增加',
    'adjust_decrease': '管理员扣减',
    'refund': '退款返还',
    'vip_purchase': 'VIP购买'
  }
  return typeMap[type] || type
}

const getTypeTagType = (type) => {
  if (type.includes('increase') || type === 'earn' || type === 'refund') return 'success'
  if (type.includes('decrease') || type === 'spend' || type === 'vip_purchase') return 'danger'
  return 'info'
}

onMounted(() => {
  loadRecords()
})
</script>

<template>
  <div class="admin-points-records">
    <div class="page-header">
      <h2>积分记录</h2>
    </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="用户ID">
          <el-input v-model="searchForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="全部" clearable>
            <el-option label="获得" value="earn" />
            <el-option label="消费" value="spend" />
            <el-option label="管理员增加" value="adjust_increase" />
            <el-option label="管理员扣减" value="adjust_decrease" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 记录列表 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="records" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column label="类型" width="150">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)">
              {{ getTypeLabel(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="变动积分" width="120">
          <template #default="{ row }">
            <span :class="{ 'positive': row.type.includes('increase') || row.type === 'earn', 'negative': row.type.includes('decrease') || row.type === 'spend' }">
              {{ row.type.includes('increase') || row.type === 'earn' ? '+' : '-' }}{{ row.points }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="balance_after" label="变动后余额" width="120" />
        <el-table-column prop="reason" label="原因" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="180" />
      </el-table>

      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
      />
    </div>
  </div>
</template>

<style scoped>
.admin-points-records {
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

.positive {
  color: #67c23a;
  font-weight: bold;
}

.negative {
  color: #f56c6c;
  font-weight: bold;
}

.el-pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
