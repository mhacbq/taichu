<template>
  <div class="users-manage">
    <div class="page-header">
      <h2>用户管理</h2>
      <el-button type="primary" :icon="Download" @click="exportData">导出</el-button>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="输入用户名" clearable />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="searchForm.phone" placeholder="输入手机号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="选择状态" clearable>
            <el-option label="正常" value="1" />
            <el-option label="禁用" value="0" />
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
        <el-table-column prop="phone" label="手机号" width="130" />
        <el-table-column prop="email" label="邮箱" min-width="180" />
        <el-table-column prop="points" label="积分" width="100" />
        <el-table-column prop="vip_level" label="VIP等级" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.vip_level">VIP{{ row.vip_level }}</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="160" />
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Download } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])

const searchForm = reactive({
  username: '',
  phone: '',
  status: ''
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
    
    const response = await fetch(`/api/admin/users?${params}`)
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

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.username = ''
  searchForm.phone = ''
  searchForm.status = ''
  handleSearch()
}

const handleEdit = (row) => {
  ElMessage.info('编辑功能待实现')
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定删除用户 "${row.username}" 吗？`, '警告', { type: 'warning' })
    
    const response = await fetch(`/api/admin/users/${row.id}`, { method: 'DELETE' })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('删除成功')
      fetchData()
    } else {
      ElMessage.error(data.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('删除失败')
  }
}

const exportData = () => {
  ElMessage.info('导出功能待实现')
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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
