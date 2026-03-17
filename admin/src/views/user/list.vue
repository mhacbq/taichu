<template>
  <div class="app-container">
    <!-- 搜索表单 -->
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户名">
          <el-input v-model="queryForm.username" placeholder="请输入用户名" clearable />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="queryForm.phone" placeholder="请输入手机号" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="queryForm.status" placeholder="全部状态" clearable>
            <el-option label="正常" value="1" />
            <el-option label="禁用" value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="注册时间">
          <el-date-picker
            v-model="queryForm.dateRange"
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

    <!-- 操作栏 -->
    <div class="table-operations mb-4">
      <el-space>
        <el-button type="primary" @click="handleExport">
          <el-icon><Download /></el-icon>导出
        </el-button>
        <el-button 
          type="success" 
          :disabled="!selectedUsers.length" 
          @click="handleBatchStatus(1)"
        >
          批量启用
        </el-button>
        <el-button 
          type="danger" 
          :disabled="!selectedUsers.length" 
          @click="handleBatchStatus(0)"
        >
          批量禁用
        </el-button>
      </el-space>
    </div>

    <!-- 用户列表 -->
    <el-card shadow="never">
      <el-table 
        v-loading="loading" 
        :data="userList" 
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column type="index" label="#" width="50" />

        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户信息" min-width="200">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :size="40" :src="row.avatar" />
              <div class="user-detail">
                <div class="nickname">{{ row.nickname }}</div>
                <div class="username">{{ row.username }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="120" />
        <el-table-column prop="points" label="积分" width="100" sortable>
          <template #default="{ row }">
            <el-tag type="warning">{{ row.points }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="bazi_count" label="八字次数" width="100" />
        <el-table-column prop="tarot_count" label="塔罗次数" width="100" />
        <el-table-column prop="created_at" label="注册时间" width="160" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button link type="primary" @click="handleAdjustPoints(row)">调积分</el-button>
            <el-dropdown @command="handleMore($event, row)">
              <el-button link type="primary">
                更多<el-icon class="el-icon--right"><arrow-down /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="log">行为日志</el-dropdown-item>
                  <el-dropdown-item command="risk" divided>风险标记</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 调整积分弹窗 -->
    <el-dialog v-model="pointsDialog.visible" title="调整用户积分" width="500px">
      <el-form :model="pointsDialog.form" label-width="100px">
        <el-form-item label="当前积分">
          <span>{{ pointsDialog.currentPoints }}</span>
        </el-form-item>
        <el-form-item label="调整类型">
          <el-radio-group v-model="pointsDialog.form.type">
            <el-radio label="add">增加</el-radio>
            <el-radio label="reduce">减少</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="调整数量">
          <el-input-number v-model="pointsDialog.form.amount" :min="1" :max="10000" />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input
            v-model="pointsDialog.form.reason"
            type="textarea"
            rows="3"
            placeholder="请输入调整原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="pointsDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="confirmAdjustPoints">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getUserList, updateUserStatus, exportUsers, batchUpdateUserStatus } from '@/api/user'
import { adjustPoints } from '@/api/points'

const router = useRouter()

const loading = ref(false)
const total = ref(0)
const userList = ref([])
const selectedUsers = ref([])

const queryForm = reactive({

  username: '',
  phone: '',
  status: '',
  dateRange: [],
  page: 1,
  pageSize: 20
})

const pointsDialog = reactive({
  visible: false,
  currentPoints: 0,
  userId: null,
  form: {
    type: 'add',
    amount: 10,
    reason: ''
  }
})

onMounted(() => {
  loadUserList()
})

async function loadUserList() {
  loading.value = true
  try {
    const { data } = await getUserList(queryForm)
    userList.value = data.list
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadUserList()
}

function handleReset() {
  Object.assign(queryForm, {
    username: '',
    phone: '',
    status: '',
    dateRange: [],
    page: 1,
    pageSize: 20
  })
  loadUserList()
}

async function handleStatusChange(row) {
  try {
    await ElMessageBox.confirm(
      `确定${row.status === 1 ? '启用' : '禁用'}该用户吗？`,
      '提示',
      { type: 'warning' }
    )
    await updateUserStatus(row.id, row.status)
    ElMessage.success('操作成功')
  } catch {
    row.status = row.status === 1 ? 0 : 1
  }
}

function handleView(row) {
  router.push(`/user/detail/${row.id}`)
}

function handleAdjustPoints(row) {
  pointsDialog.userId = row.id
  pointsDialog.currentPoints = row.points
  pointsDialog.visible = true
}

async function confirmAdjustPoints() {
  try {
    await adjustPoints({
      user_id: pointsDialog.userId,
      ...pointsDialog.form
    })
    ElMessage.success('积分调整成功')
    pointsDialog.visible = false
    loadUserList()
  } catch (error) {
    console.error(error)
  }
}

function handleMore(command, row) {
  if (command === 'log') {
    router.push({
      path: '/user/behavior',
      query: { userId: row.id }
    })
  } else if (command === 'risk') {
    // 标记风险用户
  }
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  loadUserList()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadUserList()
}

function handleSelectionChange(selection) {
  selectedUsers.value = selection
}

async function handleBatchStatus(status) {
  const ids = selectedUsers.value.map(u => u.id)
  const actionText = status === 1 ? '启用' : '禁用'
  
  try {
    await ElMessageBox.confirm(
      `确定要批量${actionText}选中的 ${ids.length} 个用户吗？`,
      '批量操作',
      { type: 'warning' }
    )
    loading.value = true
    await batchUpdateUserStatus(ids, status)
    ElMessage.success('批量操作成功')
    loadUserList()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('操作失败')
    }
  } finally {
    loading.value = false
  }
}

async function handleExport() {

  try {
    const blob = await exportUsers(queryForm)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `用户列表_${new Date().toISOString().split('T')[0]}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
    ElMessage.success('导出成功')
  } catch (error) {
    ElMessage.error('导出失败')
  }
}
</script>

<style lang="scss" scoped>
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

.search-form {
  margin-bottom: 20px;
}
</style>
