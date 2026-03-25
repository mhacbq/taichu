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
        <el-form-item label="日期范围">
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

    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row mt-4 mb-4">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-primary">{{ stats.total || 0 }}</div>
            <div class="stat-label">总测算次数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-success">{{ stats.today || 0 }}</div>
            <div class="stat-label">今日测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-warning">{{ stats.this_month || 0 }}</div>
            <div class="stat-label">本月测算</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-danger">{{ stats.this_year || 0 }}</div>
            <div class="stat-label">本年测算</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <div class="table-operations mb-4">
      <el-button
        type="danger"
        :disabled="!selectedItems.length"
        @click="handleBatchDelete"
      >
        <el-icon><Delete /></el-icon>批量删除
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table
        v-loading="loading"
        :data="dataList"
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" min-width="120">
          <template #default="{ row }">
            <div v-if="row.user">
              <div>{{ row.user.nickname || row.user.username }}</div>
              <div class="text-xs text-gray-400">ID: {{ row.user_id }}</div>
            </div>
            <div v-else>用户ID: {{ row.user_id }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="gender" label="性别" width="80">
          <template #default="{ row }">
            <el-tag :type="row.gender === 1 ? 'primary' : 'danger'">
              {{ row.gender === 1 ? '男' : '女' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="birth_date" label="出生日期" width="120" />
        <el-table-column prop="target_year" label="流年年份" width="100" />
        <el-table-column prop="points_used" label="消耗积分" width="100" />
        <el-table-column prop="created_at" label="生成时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container mt-4">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="流年运势详情" width="800px">
      <el-descriptions v-if="detailDialog.data" :column="2" border>
        <el-descriptions-item label="ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="生成时间">{{ detailDialog.data.created_at }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="姓名">{{ detailDialog.data.name }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detailDialog.data.gender === 1 ? '男' : '女' }}</el-descriptions-item>
        <el-descriptions-item label="出生日期">{{ detailDialog.data.birth_date }}</el-descriptions-item>
        <el-descriptions-item label="流年年份">{{ detailDialog.data.target_year }}</el-descriptions-item>
        <el-descriptions-item label="消耗积分">{{ detailDialog.data.points_used }}</el-descriptions-item>
        <el-descriptions-item label="分析结果" :span="2">
          <div class="result-content">
            {{ detailDialog.data.result || '暂无数据' }}
          </div>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete } from '@element-plus/icons-vue'
import request from '@/api/request'

const loading = ref(false)
const dataList = ref([])
const total = ref(0)
const selectedItems = ref([])

const queryForm = reactive({
  page: 1,
  page_size: 20,
  user_id: '',
  name: '',
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

const loadList = async () => {
  loading.value = true
  try {
    const params = { ...queryForm }
    if (params.dateRange && params.dateRange.length === 2) {
      params.start_date = params.dateRange[0]
      params.end_date = params.dateRange[1]
    }
    delete params.dateRange

    const res = await request.get('/yearly-fortune-manage', { params })
    if (res.code === 0) {
      dataList.value = res.data.list || []
      total.value = res.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const res = await request.get('/yearly-fortune-manage/stats')
    if (res.code === 0) {
      Object.assign(stats, res.data)
    }
  } catch (error) {
    // 静默失败
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadList()
}

const handleReset = () => {
  queryForm.user_id = ''
  queryForm.name = ''
  queryForm.dateRange = []
  handleSearch()
}

const handleSizeChange = (val) => {
  queryForm.page_size = val
  loadList()
}

const handleCurrentChange = (val) => {
  queryForm.page = val
  loadList()
}

const handleSelectionChange = (val) => {
  selectedItems.value = val
}

const handleView = (row) => {
  detailDialog.data = row
  detailDialog.visible = true
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
    type: 'warning'
  }).then(async () => {
    try {
      const res = await request.delete(`/yearly-fortune-manage/${row.id}`)
      if (res.code === 0) {
        ElMessage.success('删除成功')
        loadList()
        loadStats()
      }
    } catch (error) {
      ElMessage.error('删除失败')
    }
  })
}

const handleBatchDelete = () => {
  const ids = selectedItems.value.map(item => item.id)
  ElMessageBox.confirm(`确定要删除选中的 ${ids.length} 条记录吗？`, '提示', {
    type: 'warning'
  }).then(async () => {
    try {
      const res = await request.post('/yearly-fortune-manage/batch-delete', { ids })
      if (res.code === 0) {
        ElMessage.success('批量删除成功')
        loadList()
        loadStats()
      }
    } catch (error) {
      ElMessage.error('批量删除失败')
    }
  })
}

onMounted(() => {
  loadList()
  loadStats()
})
</script>

<style scoped>
.stat-item {
  text-align: center;
}
.stat-value {
  font-size: 24px;
  font-weight: bold;
}
.stat-label {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}
.result-content {
  max-height: 400px;
  overflow-y: auto;
  white-space: pre-wrap;
  line-height: 1.6;
}
.text-xs {
  font-size: 12px;
}
.text-gray-400 {
  color: #909399;
}
</style>
