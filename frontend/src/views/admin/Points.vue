<template>
  <div class="points-manage">
    <div class="page-header">
      <h2>积分记录</h2>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="输入用户名" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="选择类型" clearable>
            <el-option label="充值" value="recharge" />
            <el-option label="消费" value="consume" />
            <el-option label="奖励" value="reward" />
            <el-option label="扣除" value="deduct" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)">
              {{ getTypeText(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="amount" label="积分变化" width="120">
          <template #default="{ row }">
            <span :style="{ color: row.amount > 0 ? '#67c23a' : '#f56c6c' }">
              {{ row.amount > 0 ? '+' : '' }}{{ row.amount }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="balance" label="余额" width="100" />
        <el-table-column prop="reason" label="原因" min-width="200" />
        <el-table-column prop="created_at" label="时间" width="160" />
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.size"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="fetchData"
        @size-change="fetchData"
        style="margin-top: 20px; justify-content: flex-end;"
      />
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])

const searchForm = reactive({
  username: '',
  type: ''
})

const pagination = reactive({
  page: 1,
  size: 20,
  total: 0
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.page,
      size: pagination.size,
      ...searchForm
    })
    
    const response = await fetch(`/api/admin/points?${params}`)
    const data = await response.json()
    
    if (data.code === 200) {
      tableData.value = data.data.list || []
      pagination.total = data.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const getTypeTagType = (type) => {
  const map = {
    recharge: 'success',
    consume: 'danger',
    reward: 'warning',
    deduct: 'info'
  }
  return map[type] || 'info'
}

const getTypeText = (type) => {
  const map = {
    recharge: '充值',
    consume: '消费',
    reward: '奖励',
    deduct: '扣除'
  }
  return map[type] || type
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.username = ''
  searchForm.type = ''
  handleSearch()
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.search-form {
  margin-bottom: 20px;
}
</style>
