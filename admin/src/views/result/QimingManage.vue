<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="姓氏">
          <el-input v-model="queryForm.surname" placeholder="请输入姓氏" clearable />
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
            <div class="stat-label">总取名次数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-success">{{ stats.today || 0 }}</div>
            <div class="stat-label">今日取名</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-warning">{{ stats.this_month || 0 }}</div>
            <div class="stat-label">本月取名</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value text-danger">{{ stats.this_year || 0 }}</div>
            <div class="stat-label">本年取名</div>
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
        <el-table-column prop="surname" label="姓氏" width="100" />
        <el-table-column prop="gender" label="性别" width="80">
          <template #default="{ row }">
            <el-tag :type="row.gender === 1 ? 'primary' : 'danger'">
              {{ row.gender === 1 ? '男' : '女' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="birth_date" label="出生日期" width="120" />
        <el-table-column prop="birth_time" label="出生时间" width="100" />
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
    <el-dialog v-model="detailDialog.visible" title="取名详情" width="800px">
      <el-descriptions v-if="detailDialog.data" :column="2" border>
        <el-descriptions-item label="ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="生成时间">{{ detailDialog.data.created_at }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="姓氏">{{ detailDialog.data.surname }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detailDialog.data.gender === 1 ? '男' : '女' }}</el-descriptions-item>
        <el-descriptions-item label="出生日期">{{ detailDialog.data.birth_date }}</el-descriptions-item>
        <el-descriptions-item label="出生时间">{{ detailDialog.data.birth_time }}</el-descriptions-item>
        <el-descriptions-item label="消耗积分">{{ detailDialog.data.points_used }}</el-descriptions-item>
        <el-descriptions-item label="取名建议" :span="2">
          <div class="suggestions-content" v-html="formatSuggestions(detailDialog.data.name_suggestions)"></div>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Refresh } from '@element-plus/icons-vue'
import request from '@/api/request'

const loading = ref(false)
const dataList = ref([])
const total = ref(0)
const selectedItems = ref([])

const queryForm = reactive({
  page: 1,
  page_size: 20,
  user_id: '',
  surname: '',
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

const res = await request.get('/qiming-manage', { params })
    if (res.code === 0) {
      dataList.value = res.data.list
      total.value = res.data.total
    }
  } catch (error) {
    console.error('获取列表失败:', error)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
const res = await request.get('/qiming-manage/stats')
    if (res.code === 0) {
      Object.assign(stats, res.data)
    }
  } catch (error) {
    console.error('获取统计失败:', error)
  }
}

const handleSearch = () => {
  queryForm.page = 1
  loadList()
}

const handleReset = () => {
  queryForm.user_id = ''
  queryForm.surname = ''
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
const res = await request.delete(`/qiming-manage/${row.id}`)
      if (res.code === 0) {
        ElMessage.success('删除成功')
        loadList()
        loadStats()
      }
    } catch (error) {
      console.error('删除失败:', error)
    }
  })
}

const handleBatchDelete = () => {
  const ids = selectedItems.value.map(item => item.id)
  ElMessageBox.confirm(`确定要删除选中的 ${ids.length} 条记录吗？`, '提示', {
    type: 'warning'
  }).then(async () => {
    try {
const res = await request.post('/qiming-manage/batch-delete', { ids })
      if (res.code === 0) {
        ElMessage.success('批量删除成功')
        loadList()
        loadStats()
      }
    } catch (error) {
      console.error('批量删除失败:', error)
    }
  })
}

const formatSuggestions = (suggestions) => {
  if (!suggestions) return '暂无数据'
  try {
    const data = JSON.parse(suggestions)
    if (data.names && Array.isArray(data.names)) {
      let html = '<div class="name-suggestions-admin">'
      data.names.forEach((item) => {
        html += `
          <div class="name-card-admin" style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee; border-radius: 4px;">
            <div style="font-weight: bold; color: #409EFF; font-size: 16px;">${item.name}</div>
            <div style="margin-top: 5px;"><span style="color: #999;">寓意：</span>${item.meaning}</div>
            <div style="margin-top: 3px;"><span style="color: #999;">五行：</span>${item.wuxing}</div>
            <div style="margin-top: 3px;"><span style="color: #999;">音律：</span>${item.phonetics}</div>
          </div>
        `
      })
      html += '</div>'
      return html
    }
    return `<pre>${JSON.stringify(data, null, 2)}</pre>`
  } catch (e) {
    return suggestions.replace(/\n/g, '<br>')
  }
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
.suggestions-content {
  max-height: 400px;
  overflow-y: auto;
}
.text-xs {
  font-size: 12px;
}
.text-gray-400 {
  color: #909399;
}
</style>

