<script setup>
import { ref, onMounted } from 'vue'
import { getInviteList, getInviteStats } from '../../api/admin'
import { ElMessage } from 'element-plus'

// 统计数据
const stats = ref({ total_invites: 0, consumed_count: 0, month_invites: 0, total_points: 0 })

// 列表数据
const tableData = ref([])
const total = ref(0)
const loading = ref(false)

// 查询参数
const query = ref({ page: 1, limit: 20, keyword: '', consumed: '' })

const loadStats = async () => {
  try {
    const res = await getInviteStats()
    if (res.code === 200) stats.value = res.data
  } catch {}
}

const loadList = async () => {
  loading.value = true
  try {
    const res = await getInviteList(query.value)
    if (res.code === 200) {
      tableData.value = res.data.list || []
      total.value = res.data.total || 0
    }
  } catch (e) {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  query.value.page = 1
  loadList()
}

const handleReset = () => {
  query.value = { page: 1, limit: 20, keyword: '', consumed: '' }
  loadList()
}

const handlePageChange = (page) => {
  query.value.page = page
  loadList()
}

onMounted(() => {
  loadStats()
  loadList()
})
</script>

<template>
  <div class="invite-manage-page">
    <!-- 统计卡片 -->
    <el-row :gutter="16" class="stats-row">
      <el-col :span="6">
        <el-card shadow="never" class="stat-card">
          <div class="stat-value">{{ stats.total_invites }}</div>
          <div class="stat-label">累计邀请人数</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="stat-card success">
          <div class="stat-value">{{ stats.consumed_count }}</div>
          <div class="stat-label">邀请成功（已消费）</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="stat-card info">
          <div class="stat-value">{{ stats.month_invites }}</div>
          <div class="stat-label">本月新增邀请</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="never" class="stat-card warning">
          <div class="stat-value">{{ stats.total_points }}</div>
          <div class="stat-label">累计发放积分</div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 查询栏 -->
    <el-card shadow="never" class="filter-card">
      <el-form inline>
        <el-form-item label="关键词">
          <el-input
            v-model="query.keyword"
            placeholder="邀请人/被邀请人昵称或手机号"
            clearable
            style="width: 260px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="消费状态">
          <el-select v-model="query.consumed" placeholder="全部" clearable style="width: 120px">
            <el-option label="已消费" value="1" />
            <el-option label="未消费" value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card shadow="never">
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="inviter_nickname" label="邀请人" min-width="120">
          <template #default="{ row }">
            <div>{{ row.inviter_nickname || '-' }}</div>
            <div class="sub-text">{{ row.inviter_mobile || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="invitee_nickname" label="被邀请人" min-width="120">
          <template #default="{ row }">
            <div>{{ row.invitee_nickname || '-' }}</div>
            <div class="sub-text">{{ row.invitee_mobile || '' }}</div>
          </template>
        </el-table-column>
        <el-table-column label="消费状态" width="110" align="center">
          <template #default="{ row }">
            <el-tag :type="row.has_consumed ? 'success' : 'info'" size="small">
              {{ row.has_consumed ? '已消费' : '未消费' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="points_reward" label="奖励积分" width="100" align="center">
          <template #default="{ row }">
            <span :class="row.points_reward > 0 ? 'points-positive' : ''">
              {{ row.points_reward > 0 ? `+${row.points_reward}` : '-' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="邀请时间" width="160" />
        <el-table-column label="邀请成功" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.has_consumed ? 'success' : 'warning'" size="small">
              {{ row.has_consumed ? '✓ 成功' : '待消费' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-bar">
        <el-pagination
          v-model:current-page="query.page"
          :total="total"
          :page-size="query.limit"
          layout="total, prev, pager, next"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.invite-manage-page { padding: 20px; }

.stats-row { margin-bottom: 16px; }
.stat-card { text-align: center; }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: #303133; }
.stat-card .stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
.stat-card.success .stat-value { color: #67c23a; }
.stat-card.info .stat-value { color: #409eff; }
.stat-card.warning .stat-value { color: #e6a23c; }

.filter-card { margin-bottom: 16px; }
.sub-text { font-size: 12px; color: #909399; }
.points-positive { color: #67c23a; font-weight: 600; }

.pagination-bar {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
