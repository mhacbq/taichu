<template>
  <div class="user-admin">
    <div class="header">
      <h2>👥 用户管理</h2>
      <div class="header-actions">
        <el-button :icon="Download" @click="handleExport">导出</el-button>
      </div>
    </div>

    <!-- 搜索筛选 -->
    <el-card class="filter-card">
      <el-row :gutter="12">
        <el-col :xs="24" :sm="8">
          <el-input
            v-model="searchKeyword"
            placeholder="搜索用户名/手机号/邮箱"
            :prefix-icon="Search"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-col>
        <el-col :xs="12" :sm="4">
          <el-select v-model="filterStatus" placeholder="用户状态" clearable>
            <el-option label="正常" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-col>
        <el-col :xs="12" :sm="4">
          <el-select v-model="filterVip" placeholder="会员类型" clearable>
            <el-option label="普通用户" value="normal" />
            <el-option label="VIP会员" value="vip" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="注册开始"
            end-placeholder="注册结束"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width:100%"
          />
        </el-col>
      </el-row>
      <div class="filter-footer">
        <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
        <el-button @click="handleReset">重置</el-button>
        <el-button
          v-if="selectedIds.length > 0"
          type="danger"
          @click="handleBatchDisable"
        >
          批量禁用 ({{ selectedIds.length }})
        </el-button>
      </div>
    </el-card>

    <!-- 用户列表 -->
    <el-card>
      <el-table
        :data="userList"
        v-loading="loading"
        @selection-change="handleSelectionChange"
        row-key="id"
      >
        <el-table-column type="selection" width="42" />
        <el-table-column label="用户" min-width="160">
          <template #default="{ row }">
            <div class="user-cell">
              <div class="user-avatar">{{ (row.nickname || row.username || '?')[0] }}</div>
              <div>
                <div class="user-name">{{ row.nickname || row.username }}</div>
                <div class="user-sub">ID: {{ row.id }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="联系方式" min-width="140">
          <template #default="{ row }">
            <div>{{ row.phone || '-' }}</div>
            <div class="text-gray">{{ row.email || '-' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="积分" width="90" align="right">
          <template #default="{ row }">
            <span class="points-val">{{ row.points || 0 }}</span>
          </template>
        </el-table-column>
        <el-table-column label="会员" width="90" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.is_vip" type="warning" size="small">VIP</el-tag>
            <el-tag v-else type="info" size="small">普通</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleToggleStatus(row)"
              size="small"
            />
          </template>
        </el-table-column>
        <el-table-column label="注册时间" width="120">
          <template #default="{ row }">
            <span class="text-gray text-sm">{{ formatDate(row.created_at || row.create_time) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="最后活跃" width="120">
          <template #default="{ row }">
            <span class="text-gray text-sm">{{ formatDate(row.last_login || row.last_active_time) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right">
          <template #default="{ row }">
            <el-button size="small" text @click="handleViewDetail(row)">详情</el-button>
            <el-button size="small" text type="warning" @click="handleAdjustPoints(row)">调积分</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="page"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        class="pagination"
        @change="loadUsers"
      />
    </el-card>

    <!-- 用户详情弹窗 -->
    <el-dialog v-model="detailVisible" title="用户详情" width="600px" destroy-on-close>
      <div v-if="currentUser" class="user-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户ID">{{ currentUser.id }}</el-descriptions-item>
          <el-descriptions-item label="用户名">{{ currentUser.username }}</el-descriptions-item>
          <el-descriptions-item label="昵称">{{ currentUser.nickname || '-' }}</el-descriptions-item>
          <el-descriptions-item label="手机号">{{ currentUser.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="邮箱">{{ currentUser.email || '-' }}</el-descriptions-item>
          <el-descriptions-item label="积分余额">{{ currentUser.points || 0 }}</el-descriptions-item>
          <el-descriptions-item label="VIP状态">
            <el-tag :type="currentUser.is_vip ? 'warning' : 'info'" size="small">
              {{ currentUser.is_vip ? `VIP（到期：${formatDate(currentUser.vip_expire)}）` : '普通用户' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="账号状态">
            <el-tag :type="currentUser.status === 1 ? 'success' : 'danger'" size="small">
              {{ currentUser.status === 1 ? '正常' : '已禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="注册时间" :span="2">{{ formatDate(currentUser.created_at || currentUser.create_time) }}</el-descriptions-item>
          <el-descriptions-item label="最后登录" :span="2">{{ formatDate(currentUser.last_login) }}</el-descriptions-item>
        </el-descriptions>
        <div class="detail-stats" v-if="currentUser.stats">
          <h4>使用统计</h4>
          <el-row :gutter="12">
            <el-col :span="8" v-for="(val, key) in currentUser.stats" :key="key">
              <div class="mini-stat">
                <div class="ms-val">{{ val }}</div>
                <div class="ms-key">{{ statLabels[key] || key }}</div>
              </div>
            </el-col>
          </el-row>
        </div>
      </div>
    </el-dialog>

    <!-- 积分调整弹窗 -->
    <el-dialog v-model="pointsVisible" title="调整积分" width="400px" destroy-on-close>
      <el-form :model="pointsForm" label-width="90px" v-if="currentUser">
        <el-form-item label="用户">
          <span>{{ currentUser.nickname || currentUser.username }} (当前: {{ currentUser.points }} 积分)</span>
        </el-form-item>
        <el-form-item label="操作类型">
          <el-radio-group v-model="pointsForm.type">
            <el-radio value="add">增加</el-radio>
            <el-radio value="deduct">扣减</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="积分数量">
          <el-input-number v-model="pointsForm.amount" :min="1" :max="99999" />
        </el-form-item>
        <el-form-item label="调整原因">
          <el-input v-model="pointsForm.reason" placeholder="请填写调整原因（必填）" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="pointsVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="submitAdjustPoints">确认调整</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Download } from '@element-plus/icons-vue'
import { getUserList, toggleUserStatus, adjustUserPoints, batchUpdateUserStatus, exportUsers } from '@/api/admin'

const loading = ref(false)
const submitLoading = ref(false)
const detailVisible = ref(false)
const pointsVisible = ref(false)
const currentUser = ref(null)
const selectedIds = ref([])

// 筛选
const searchKeyword = ref('')
const filterStatus = ref(null)
const filterVip = ref('')
const dateRange = ref([])

// 分页
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)
const userList = ref([])

// 积分表单
const pointsForm = ref({ type: 'add', amount: 100, reason: '' })

const statLabels = {
  bazi_count: '八字次数', tarot_count: '塔罗次数', liuyao_count: '六爻次数',
  hehun_count: '合婚次数', daily_count: '运势次数', total_recharge: '累计充值',
}

const formatDate = (t) => {
  if (!t) return '-'
  const d = new Date(typeof t === 'number' ? t * 1000 : t)
  return d.toLocaleDateString('zh-CN') + ' ' + d.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
}

const loadUsers = async () => {
  loading.value = true
  try {
    const params = {
      page: page.value,
      page_size: pageSize.value,
      keyword: searchKeyword.value || undefined,
      status: filterStatus.value !== null ? filterStatus.value : undefined,
      vip_type: filterVip.value || undefined,
      start_date: dateRange.value?.[0] || undefined,
      end_date: dateRange.value?.[1] || undefined,
    }
    const res = await getUserList(params)
    if (res.code === 200) {
      userList.value = res.data?.list || []
      total.value = res.data?.total || 0
    }
  } catch { ElMessage.error('加载用户列表失败') }
  finally { loading.value = false }
}

const handleSearch = () => { page.value = 1; loadUsers() }
const handleReset = () => {
  searchKeyword.value = ''; filterStatus.value = null; filterVip.value = ''; dateRange.value = []
  handleSearch()
}

const handleSelectionChange = (rows) => { selectedIds.value = rows.map(r => r.id) }

const handleToggleStatus = async (row) => {
  try {
    const res = await toggleUserStatus(row.id, row.status)
    if (res.code === 200) {
      ElMessage.success(row.status === 1 ? '已启用' : '已禁用')
    } else {
      row.status = row.status === 1 ? 0 : 1
      ElMessage.error(res.msg || '操作失败')
    }
  } catch { row.status = row.status === 1 ? 0 : 1 }
}

const handleBatchDisable = async () => {
  try {
    await ElMessageBox.confirm(`确定批量禁用 ${selectedIds.value.length} 个用户？`, '提示', { type: 'warning' })
    const res = await batchUpdateUserStatus(selectedIds.value, 0)
    if (res.code === 200) { ElMessage.success('批量禁用成功'); loadUsers() }
    else ElMessage.error(res.msg || '操作失败')
  } catch {}
}

const handleViewDetail = async (row) => {
  currentUser.value = row
  detailVisible.value = true
}

const handleAdjustPoints = (row) => {
  currentUser.value = row
  pointsForm.value = { type: 'add', amount: 100, reason: '' }
  pointsVisible.value = true
}

const submitAdjustPoints = async () => {
  if (!pointsForm.value.reason?.trim()) {
    ElMessage.warning('请填写调整原因')
    return
  }
  submitLoading.value = true
  try {
    const res = await adjustUserPoints({
      user_id: currentUser.value.id,
      ...pointsForm.value,
    })
    if (res.code === 200) {
      ElMessage.success('积分调整成功')
      pointsVisible.value = false
      // 更新列表中的积分
      const delta = pointsForm.value.type === 'add' ? pointsForm.value.amount : -pointsForm.value.amount
      const u = userList.value.find(u => u.id === currentUser.value.id)
      if (u) u.points = (u.points || 0) + delta
    } else {
      ElMessage.error(res.msg || '调整失败')
    }
  } catch { ElMessage.error('操作失败') }
  finally { submitLoading.value = false }
}

const handleExport = async () => {
  try {
    const blob = await exportUsers({ keyword: searchKeyword.value })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url; a.download = `用户数据_${new Date().toLocaleDateString()}.csv`; a.click()
    URL.revokeObjectURL(url)
  } catch { ElMessage.error('导出失败') }
}

onMounted(loadUsers)
</script>

<style scoped>
.user-admin { padding: 20px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.header h2 { margin:0; font-size:20px; }
.filter-card { margin-bottom:16px; }
.filter-footer { margin-top:12px; display:flex; gap:8px; }
.pagination { margin-top:16px; justify-content:flex-end; }
.user-cell { display:flex; align-items:center; gap:10px; }
.user-avatar {
  width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2);
  display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex-shrink:0;
}
.user-name { font-size:14px; font-weight:500; }
.user-sub { font-size:12px; color:#909399; }
.points-val { font-weight:700; color:#e6a23c; }
.text-gray { color:#909399; }
.text-sm { font-size:12px; }
.detail-stats { margin-top:20px; }
.detail-stats h4 { margin:0 0 12px; font-size:14px; color:#303133; }
.mini-stat { text-align:center; background:#f5f7fa; border-radius:8px; padding:12px; }
.ms-val { font-size:18px; font-weight:700; color:#303133; }
.ms-key { font-size:12px; color:#909399; margin-top:4px; }
</style>
