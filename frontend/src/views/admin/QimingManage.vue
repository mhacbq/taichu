
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '../../api/adminRequest'

const loading = ref(false)
const list = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(20)
const selectedIds = ref([])
const stats = ref({ total: 0, today: 0, this_month: 0, this_year: 0 })
const detailVisible = ref(false)
const detailData = ref(null)
const detailLoading = ref(false)

const searchForm = ref({
  surname: '',
  user_id: '',
  start_date: '',
  end_date: ''
})

// 加载列表
const loadList = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...searchForm.value
    }
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
    const res = await request.get('/maodou/qiming-manage', { params })
    if (res.code === 200) {
      list.value = res.data.list || []
      total.value = res.data.total || 0
    } else {
      ElMessage.error(res.message || '加载失败')
    }
  } catch (e) {
    console.error('加载取名记录失败:', e)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

// 加载统计
const loadStats = async () => {
  try {
    const res = await request.get('/maodou/qiming-manage/stats')
    if (res.code === 200) {
      stats.value = res.data
    }
  } catch (e) {
    console.error('加载统计失败:', e)
  }
}

// 查看详情
const handleView = async (row) => {
  detailVisible.value = true
  detailLoading.value = true
  try {
    const res = await request.get(`/maodou/qiming-manage/${row.id}`)
    if (res.code === 200) {
      detailData.value = res.data
    } else {
      ElMessage.error(res.message || '加载详情失败')
    }
  } catch (e) {
    ElMessage.error('加载详情失败')
  } finally {
    detailLoading.value = false
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条取名记录吗？', '确认删除', { type: 'warning' })
    const res = await request.delete(`/maodou/qiming-manage/${row.id}`)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      loadList()
      loadStats()
    } else {
      ElMessage.error(res.message || '删除失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('删除失败')
  }
}

// 批量删除
const handleBatchDelete = async () => {
  if (!selectedIds.value.length) {
    ElMessage.warning('请先选择要删除的记录')
    return
  }
  try {
    await ElMessageBox.confirm(`确定要删除选中的 ${selectedIds.value.length} 条记录吗？`, '批量删除', { type: 'warning' })
    const res = await request.post('/maodou/qiming-manage/batch-delete', { ids: selectedIds.value })
    if (res.code === 200) {
      ElMessage.success('批量删除成功')
      selectedIds.value = []
      loadList()
      loadStats()
    } else {
      ElMessage.error(res.message || '批量删除失败')
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('批量删除失败')
  }
}

const handleSelectionChange = (rows) => {
  selectedIds.value = rows.map(r => r.id)
}

const handleSearch = () => {
  currentPage.value = 1
  loadList()
}

const handleReset = () => {
  searchForm.value = { surname: '', user_id: '', start_date: '', end_date: '' }
  currentPage.value = 1
  loadList()
}

const handlePageChange = (page) => {
  currentPage.value = page
  loadList()
}

const handleSizeChange = (size) => {
  pageSize.value = size
  currentPage.value = 1
  loadList()
}

// 性别映射
const getGenderLabel = (gender) => {
  const map = { male: '男', female: '女', 1: '男', 2: '女' }
  return map[gender] || gender || '未知'
}

onMounted(() => {
  loadList()
  loadStats()
})
</script>

<template>
  <div class="admin-manage-page">
    <div class="page-header">
      <h2>取名记录管理</h2>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-cards">
      <el-card shadow="hover" class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">总记录数</div>
      </el-card>
      <el-card shadow="hover" class="stat-card">
        <div class="stat-value today">{{ stats.today }}</div>
        <div class="stat-label">今日新增</div>
      </el-card>
      <el-card shadow="hover" class="stat-card">
        <div class="stat-value month">{{ stats.this_month }}</div>
        <div class="stat-label">本月新增</div>
      </el-card>
      <el-card shadow="hover" class="stat-card">
        <div class="stat-value year">{{ stats.this_year }}</div>
        <div class="stat-label">本年累计</div>
      </el-card>
    </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="姓氏">
          <el-input v-model="searchForm.surname" placeholder="搜索姓氏" clearable />
        </el-form-item>
        <el-form-item label="用户ID">
          <el-input v-model="searchForm.user_id" placeholder="用户ID" clearable />
        </el-form-item>
        <el-form-item label="开始日期">
          <el-date-picker v-model="searchForm.start_date" type="date" value-format="YYYY-MM-DD" placeholder="开始日期" />
        </el-form-item>
        <el-form-item label="结束日期">
          <el-date-picker v-model="searchForm.end_date" type="date" value-format="YYYY-MM-DD" placeholder="结束日期" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 操作栏 -->
    <div class="action-bar">
      <el-button type="danger" :disabled="!selectedIds.length" @click="handleBatchDelete">
        批量删除 ({{ selectedIds.length }})
      </el-button>
    </div>

    <!-- 数据表格 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="list" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="90" />
        <el-table-column prop="surname" label="姓氏" width="80" />
        <el-table-column label="性别" width="70">
          <template #default="{ row }">{{ getGenderLabel(row.gender) }}</template>
        </el-table-column>
        <el-table-column prop="birthday" label="生辰" min-width="130" />
        <el-table-column prop="name_count" label="推荐数" width="80" />
        <el-table-column prop="created_at" label="测算时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleView(row)">详情</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
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

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="取名记录详情" width="700px">
      <div v-loading="detailLoading">
        <template v-if="detailData">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="记录ID">{{ detailData.id }}</el-descriptions-item>
            <el-descriptions-item label="用户ID">{{ detailData.user_id }}</el-descriptions-item>
            <el-descriptions-item label="姓氏">{{ detailData.surname }}</el-descriptions-item>
            <el-descriptions-item label="性别">{{ getGenderLabel(detailData.gender) }}</el-descriptions-item>
            <el-descriptions-item label="生辰">{{ detailData.birthday }}</el-descriptions-item>
            <el-descriptions-item label="测算时间">{{ detailData.created_at }}</el-descriptions-item>
          </el-descriptions>
          <div v-if="detailData.result" class="detail-result">
            <h4>推荐名字</h4>
            <div class="result-content" v-html="detailData.result"></div>
          </div>
          <div v-if="detailData.names && detailData.names.length" class="detail-result">
            <h4>推荐名字列表</h4>
            <div class="names-grid">
              <el-tag v-for="(name, idx) in detailData.names" :key="idx" class="name-tag" size="large">
                {{ detailData.surname }}{{ name.name || name }}
              </el-tag>
            </div>
          </div>
        </template>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-manage-page { padding: 24px; }
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 20px; color: #333; margin: 0; }

.stats-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { text-align: center; }
.stat-value { font-size: 28px; font-weight: 700; color: #409eff; }
.stat-value.today { color: #67c23a; }
.stat-value.month { color: #e6a23c; }
.stat-value.year { color: #f56c6c; }
.stat-label { font-size: 13px; color: #999; margin-top: 4px; }

.search-form { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
.action-bar { margin-bottom: 12px; }
.table-container { background: #fff; padding: 20px; border-radius: 8px; }
.el-pagination { margin-top: 20px; justify-content: flex-end; }

.detail-result { margin-top: 20px; }
.detail-result h4 { margin-bottom: 12px; color: #333; }
.result-content { padding: 16px; background: #f5f7fa; border-radius: 8px; line-height: 1.8; white-space: pre-wrap; }
.names-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.name-tag { font-size: 15px; }
</style>
