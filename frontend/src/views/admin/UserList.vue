<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getUserList,
  getUserDetail,
  toggleUserStatus,
  batchUpdateUserStatus
} from '../../api/admin'

const router = useRouter()
const loading = ref(false)
const users = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const searchForm = ref({
  keyword: '',
  status: '',
  source: ''
})
const selectedUsers = ref([])

const loadUsers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...searchForm.value
    }
    const response = await getUserList(params)
    
    if (response.code === 200) {
      users.value = response.data.list || []
      total.value = response.data.total || 0
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载用户列表失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  currentPage.value = 1
  loadUsers()
}

const handleReset = () => {
  searchForm.value = {
    keyword: '',
    status: '',
    source: ''
  }
  currentPage.value = 1
  loadUsers()
}

const handleViewDetail = (row) => {
  router.push(`/maodou/users/${row.id}`)
}

const handleViewBehavior = (row) => {
  router.push(`/maodou/users/${row.id}/behavior`)
}

const handleToggleStatus = async (row) => {
  const newStatus = row.status === 'active' ? 'inactive' : 'active'
  const action = newStatus === 'active' ? '启用' : '禁用'
  
  try {
    await ElMessageBox.confirm(`确定要${action}用户 "${row.username}" 吗？`, '确认操作', {
      type: 'warning'
    })
    
    const response = await toggleUserStatus(row.id, newStatus)
    if (response.code === 200) {
      ElMessage.success(`${action}成功`)
      loadUsers()
    } else {
      ElMessage.error(response.message || '操作失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('切换状态失败:', error)
      ElMessage.error('操作失败')
    }
  }
}

const handleBatchStatus = async (status) => {
  if (selectedUsers.value.length === 0) {
    ElMessage.warning('请选择要操作的用户')
    return
  }

  const action = status === 'active' ? '启用' : '禁用'
  try {
    await ElMessageBox.confirm(`确定要${action}选中的 ${selectedUsers.value.length} 个用户吗？`, '确认操作', {
      type: 'warning'
    })
    
    const response = await batchUpdateUserStatus(selectedUsers.value, status)
    if (response.code === 200) {
      ElMessage.success(`${action}成功`)
      selectedUsers.value = []
      loadUsers()
    } else {
      ElMessage.error(response.message || '操作失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('批量操作失败:', error)
      ElMessage.error('操作失败')
    }
  }
}

const handleSelectionChange = (selection) => {
  selectedUsers.value = selection.map(item => item.id)
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadUsers()
}

const handleSizeChange = (size) => {
  pageSize.value = size
  currentPage.value = 1
  loadUsers()
}

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <div class="admin-user-list">
    <div class="page-header">
      <h2>用户管理</h2>
    </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="用户名/手机号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="正常" value="active" />
            <el-option label="禁用" value="inactive" />
          </el-select>
        </el-form-item>
        <el-form-item label="来源">
          <el-select v-model="searchForm.source" placeholder="全部" clearable>
            <el-option label="微信" value="wechat" />
            <el-option label="QQ" value="qq" />
            <el-option label="注册" value="register" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 批量操作 -->
    <div class="batch-actions" v-if="selectedUsers.length > 0">
      <el-button type="success" @click="handleBatchStatus('active')">批量启用</el-button>
      <el-button type="danger" @click="handleBatchStatus('inactive')">批量禁用</el-button>
      <span class="selection-info">已选择 {{ selectedUsers.length }} 项</span>
    </div>

    <!-- 用户列表 -->
    <div class="table-container">
      <el-table
        v-loading="loading"
        :data="users"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="nickname" label="昵称" min-width="120" />
        <el-table-column prop="phone" label="手机号" min-width="120" />
        <el-table-column prop="points" label="积分" width="100" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 'active' ? 'success' : 'danger'">
              {{ row.status === 'active' ? '正常' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="180" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleViewDetail(row)">查看</el-button>
            <el-button size="small" @click="handleViewBehavior(row)">行为日志</el-button>
            <el-button
              size="small"
              :type="row.status === 'active' ? 'danger' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 'active' ? '禁用' : '启用' }}
            </el-button>
          </template>
        </el-table-column>
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
.admin-user-list {
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

.batch-actions {
  background: #f5f7fa;
  padding: 12px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.selection-info {
  margin-left: auto;
  color: #666;
  font-size: 14px;
}

.table-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
}

.el-pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
