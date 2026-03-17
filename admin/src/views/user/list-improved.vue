<template>
  <div class="app-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-row">
      <el-col :xs="24" :sm="12" :lg="6">
        <StatCard
          title="总用户数"
          :value="stats.total"
          icon="UserFilled"
          icon-bg-color="#409eff"
          :trend="stats.totalTrend"
        />
      </el-col>
      <el-col :xs="24" :sm="12" :lg="6">
        <StatCard
          title="今日新增"
          :value="stats.today"
          icon="User"
          icon-bg-color="#67c23a"
          :trend="stats.todayTrend"
        />
      </el-col>
      <el-col :xs="24" :sm="12" :lg="6">
        <StatCard
          title="活跃用户"
          :value="stats.active"
          icon="View"
          icon-bg-color="#e6a23c"
        />
      </el-col>
      <el-col :xs="24" :sm="12" :lg="6">
        <StatCard
          title="禁用用户"
          :value="stats.disabled"
          icon="WarningFilled"
          icon-bg-color="#f56c6c"
        />
      </el-col>
    </el-row>

    <!-- 搜索表单 -->
    <el-card shadow="never" class="search-card">
      <SearchForm
        v-model="queryParams"
        :items="searchItems"
        show-export
        @search="handleSearch"
        @reset="handleReset"
        @export="handleExport"
      >
        <template #buttons>
          <el-button type="success" @click="handleBatchEnable" :disabled="!selectedRows.length">
            批量启用
          </el-button>
          <el-button type="danger" @click="handleBatchDisable" :disabled="!selectedRows.length">
            批量禁用
          </el-button>
        </template>
      </SearchForm>
    </el-card>

    <!-- 数据表格 -->
    <el-card shadow="never">
      <CommonTable
        :data="dataList"
        :columns="columns"
        :loading="loading"
        :total="total"
        v-model:page="queryParams.page"
        v-model:page-size="queryParams.pageSize"
        selection
        @selection-change="handleSelectionChange"
        @page-change="handlePageChange"
        @size-change="handleSizeChange"
        @view="handleView"
        @edit="handleEdit"
        @delete="handleDelete"
        @switch-change="handleStatusChange"
      >
        <!-- 自定义用户信息列 -->
        <template #userInfo="{ row }">
          <div class="user-info">
            <el-avatar :size="40" :src="row.avatar" />
            <div class="user-detail">
              <div class="nickname">{{ row.nickname }}</div>
              <div class="username">{{ row.username }}</div>
            </div>
          </div>
        </template>
        
        <!-- 自定义积分列 -->
        <template #points="{ row }">
          <el-tag type="warning" effect="plain">{{ row.points }}</el-tag>
        </template>
        
        <!-- 自定义状态列 -->
        <template #status="{ row }">
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            @change="(val) => handleStatusChange({ row, value: val })"
          />
        </template>
      </CommonTable>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useTable } from '@/hooks'
import { getUserList, updateUserStatus, exportUsers, batchUpdateUserStatus } from '@/api/user'

import StatCard from '@/components/StatCard/index.vue'
import SearchForm from '@/components/SearchForm/index.vue'
import CommonTable from '@/components/CommonTable/index.vue'
import { exportCsv } from '@/utils/export'

const router = useRouter()

// 统计信息
const stats = ref({
  total: 12580,
  totalTrend: 12.5,
  today: 128,
  todayTrend: 8.2,
  active: 3421,
  disabled: 23
})

// 搜索配置
const searchItems = [
  { type: 'input', prop: 'username', label: '用户名', placeholder: '请输入用户名' },
  { type: 'input', prop: 'phone', label: '手机号', placeholder: '请输入手机号' },
  { type: 'select', prop: 'status', label: '状态', options: [
    { label: '正常', value: 1 },
    { label: '禁用', value: 0 }
  ]},
  { type: 'daterange', prop: 'dateRange', label: '注册时间' }
]

// 表格列配置
const columns = [
  { prop: 'id', label: 'ID', width: 80 },
  { prop: 'userInfo', label: '用户信息', minWidth: 180, slot: true },
  { prop: 'phone', label: '手机号', width: 120 },
  { prop: 'points', label: '积分', width: 100, slot: true },
  { prop: 'bazi_count', label: '八字次数', width: 100 },
  { prop: 'tarot_count', label: '塔罗次数', width: 100 },
  { prop: 'created_at', label: '注册时间', width: 160, type: 'date' },
  { prop: 'status', label: '状态', width: 80, slot: true }
]

// 使用表格hook
const {
  loading,
  dataList,
  total,
  selectedRows,
  queryParams,
  handleSearch,
  handleReset,
  handlePageChange,
  handleSizeChange,
  handleSelectionChange,
  refresh
} = useTable({
  fetchApi: getUserList
})

onMounted(() => {
  handleSearch()
})

// 查看详情
function handleView(row) {
  router.push(`/user/detail/${row.id}`)
}

// 编辑
function handleEdit(row) {
  // 编辑逻辑
}

// 删除
async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除该用户吗？', '提示', { type: 'warning' })
    // 调用删除API
    ElMessage.success('删除成功')
    refresh()
  } catch {
    // 取消删除
  }
}

// 状态改变
async function handleStatusChange({ row, value }) {
  try {
    await updateUserStatus(row.id, value)
    ElMessage.success(value === 1 ? '用户已启用' : '用户已禁用')
  } catch {
    row.status = value === 1 ? 0 : 1
  }
}

async function handleBatchStatus(status) {
  const ids = selectedRows.value.map(row => row.id)
  if (!ids.length) {
    return
  }

  const actionText = status === 1 ? '启用' : '禁用'

  try {
    await ElMessageBox.confirm(
      `确定要批量${actionText}选中的 ${ids.length} 个用户吗？`,
      '批量操作',
      { type: 'warning' }
    )
    await batchUpdateUserStatus(ids, status)
    ElMessage.success(`已批量${actionText}${ids.length}个用户`)
    selectedRows.value = []
    refresh()
  } catch (error) {
    if (error !== 'cancel' && error !== 'close') {
      ElMessage.error(`批量${actionText}失败`)
    }
  }
}

// 批量启用
async function handleBatchEnable() {
  await handleBatchStatus(1)
}

// 批量禁用
async function handleBatchDisable() {
  await handleBatchStatus(0)
}


// 导出
async function handleExport() {
  try {
    const data = await exportUsers(queryParams)
    exportCsv(data, columns, `用户列表_${new Date().toISOString().split('T')[0]}.csv`)
    ElMessage.success('导出成功')
  } catch (error) {
    ElMessage.error('导出失败')
  }
}
</script>

<style lang="scss" scoped>
.stat-row {
  margin-bottom: 20px;
}

.search-card {
  margin-bottom: 20px;
}

.user-info {
  display: flex;
  align-items: center;
  
  .user-detail {
    margin-left: 10px;
    
    .nickname {
      font-weight: 500;
      color: #303133;
    }
    
    .username {
      font-size: 12px;
      color: #909399;
      margin-top: 4px;
    }
  }
}
</style>
