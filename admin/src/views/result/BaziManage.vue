<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="姓名">
          <el-input v-model="queryForm.name" placeholder="请输入姓名" clearable />
        </el-form-item>
        <el-form-item label="出生日期">
          <el-date-picker
            v-model="queryForm.birthday"
            type="date"
            placeholder="选择出生日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item label="测算时间">
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
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <div class="table-operations mb-4">
      <el-space>
        <el-button
          type="danger"
          :disabled="!selectedItems.length"
          @click="handleBatchDelete"
        >
          <el-icon><Delete /></el-icon>批量删除
        </el-button>
        <el-button @click="handleRefreshStats">
          <el-icon><Refresh /></el-icon>刷新统计
        </el-button>
      </el-space>
    </div>

    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row mb-4">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.total || 0 }}</div>
            <div class="stat-label">总测算次数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.today || 0 }}</div>
            <div class="stat-label">今日测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.this_month || 0 }}</div>
            <div class="stat-label">本月测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value gold-text">{{ stats.this_year || 0 }}</div>
            <div class="stat-label">本年测算</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never">
      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-button type="primary" :loading="loading" @click="loadList">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-table
          v-loading="loading"
          :data="dataList"
          stripe
          empty-text="暂无测算数据"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="55" />
          <el-table-column type="index" label="#" width="50" />
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column label="用户信息" min-width="150">
            <template #default="{ row }">
              <div class="user-info">
                <div class="user-name">{{ row.user_id }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="name" label="姓名" width="100" />
          <el-table-column prop="gender" label="性别" width="80">
            <template #default="{ row }">
              <el-tag :type="row.gender === '男' ? 'primary' : 'danger'">
                {{ row.gender }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="birthday" label="出生日期" width="120" />
          <el-table-column prop="birth_time" label="出生时辰" width="100" />
          <el-table-column prop="created_at" label="测算时间" width="160" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" @click="handleView(row)">查看详情</el-button>
              <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>

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
      </template>
    </el-card>

    <el-dialog v-model="detailDialog.visible" title="测算详情" width="800px" destroy-on-close>
      <el-descriptions v-if="detailDialog.data" :column="2" border>
        <el-descriptions-item label="ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="姓名">{{ detailDialog.data.name }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detailDialog.data.gender }}</el-descriptions-item>
        <el-descriptions-item label="出生日期">{{ detailDialog.data.birthday }}</el-descriptions-item>
        <el-descriptions-item label="出生时辰">{{ detailDialog.data.birth_time }}</el-descriptions-item>
        <el-descriptions-item label="测算时间" :span="2">{{ detailDialog.data.created_at }}</el-descriptions-item>
        <el-descriptions-item label="八字排盘" :span="2">
          <div class="bazi-content">{{ detailDialog.data.bazi_info || '暂无' }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="分析结果" :span="2">
          <div class="result-content">{{ detailDialog.data.result || '暂无' }}</div>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Refresh } from '@element-plus/icons-vue'

const loading = ref(false)
const dataList = ref([])
const total = ref(0)
const selectedItems = ref([])
const pageError = ref(null)

const queryForm = reactive({
  page: 1,
  pageSize: 20,
  user_id: '',
  name: '',
  birthday: '',
  dateRange: []
})

const stats = reactive({
  total: 0,
  today: 0,
  this_month: 0,
  this_year: 0
})

const detailDialog = reactive({
  visible: false,
  data: null
})

const API_BASE = '/api/maodou/bazi-manage'

const loadList = async () => {
  loading.value = true
  pageError.value = null

  try {
    const params = {
      ...queryForm,
      start_date: queryForm.dateRange?.[0] || '',
      end_date: queryForm.dateRange?.[1] || ''
    }
    delete params.dateRange

    const response = await fetch(`${API_BASE}?${new URLSearchParams(params)}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })

    const result = await response.json()

    if (result.code === 200) {
      dataList.value = result.data.list
      total.value = result.data.total
    } else {
      pageError.value = {
        title: '加载失败',
        description: result.message || '获取数据失败'
      }
    }
  } catch (error) {
    pageError.value = {
      title: '网络错误',
      description: error.message
    }
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await fetch(`${API_BASE}/stats`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })

    const result = await response.json()

    if (result.code === 200) {
      Object.assign(stats, result.data)
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadList()
}

const handleReset = () => {
  Object.assign(queryForm, {
    page: 1,
    pageSize: 20,
    user_id: '',
    name: '',
    birthday: '',
    dateRange: []
  })
  loadList()
}

const handleSelectionChange = (selection) => {
  selectedItems.value = selection
}

const handleView = (row) => {
  detailDialog.data = row
  detailDialog.visible = true
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确认删除此测算结果吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const response = await fetch(`${API_BASE}/${row.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })

    const result = await response.json()

    if (result.code === 200) {
      ElMessage.success('删除成功')
      loadList()
      loadStats()
    } else {
      ElMessage.error(result.message || '删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleBatchDelete = async () => {
  try {
    await ElMessageBox.confirm(`确认删除选中的 ${selectedItems.value.length} 条测算结果吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const ids = selectedItems.value.map(item => item.id)

    const response = await fetch(`${API_BASE}/batch-delete`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ ids })
    })

    const result = await response.json()

    if (result.code === 200) {
      ElMessage.success('批量删除成功')
      loadList()
      loadStats()
    } else {
      ElMessage.error(result.message || '批量删除失败')
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('批量删除失败')
    }
  }
}

const handleRefreshStats = () => {
  loadStats()
}

const handleSizeChange = () => {
  loadList()
}

const handleCurrentChange = () => {
  loadList()
}

onMounted(() => {
  loadList()
  loadStats()
})
</script>

<style scoped>
.app-container {
  padding: 20px;
  background: #fff;
}

.gold-text {
  color: #D4AF37;
  font-weight: 600;
}

.search-form {
  margin-bottom: 20px;
}

.table-operations {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 8px;
}

.stat-label {
  color: #666;
  font-size: 14px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  font-weight: 500;
}

.pagination-container {
  margin-top: 20px;
  text-align: right;
}

.bazi-content,
.result-content {
  max-height: 200px;
  overflow-y: auto;
  white-space: pre-wrap;
  line-height: 1.6;
}

.mb-4 {
  margin-bottom: 16px;
}

.page-state {
  padding: 40px 0;
}
</style>
