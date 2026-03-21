<template>
  <div class="almanac-manage">
    <div class="page-header">
      <h2>黄历管理</h2>
      <el-button type="primary" :icon="Calendar" @click="handleGenerate">生成黄历</el-button>
    </div>

    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="年份">
          <el-date-picker
            v-model="searchForm.year"
            type="year"
            placeholder="选择年份"
            value-format="YYYY"
          />
        </el-form-item>
        <el-form-item label="月份">
          <el-select v-model="searchForm.month" placeholder="选择月份" clearable>
            <el-option v-for="m in 12" :key="m" :label="`${m}月`" :value="m" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
          <el-button :icon="Refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="tableData" v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="lunar_date" label="农历" width="120" />
        <el-table-column prop="ganzhi" label="干支" width="100" />
        <el-table-column label="宜" width="300">
          <template #default="{ row }">
            <el-tag
              v-for="(item, index) in row.yi"
              :key="index"
              size="small"
              style="margin-right: 4px; margin-bottom: 4px;"
            >
              {{ item }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="忌" width="300">
          <template #default="{ row }">
            <el-tag
              v-for="(item, index) in row.ji"
              :key="index"
              size="small"
              type="danger"
              style="margin-right: 4px; margin-bottom: 4px;"
            >
              {{ item }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="生成时间" width="160" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
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

    <!-- 生成黄历对话框 -->
    <el-dialog v-model="generateDialogVisible" title="生成黄历" width="500px">
      <el-form :model="generateForm" label-width="80px">
        <el-form-item label="年份">
          <el-date-picker
            v-model="generateForm.year"
            type="year"
            placeholder="选择年份"
            value-format="YYYY"
          />
        </el-form-item>
        <el-form-item label="月份">
          <el-select v-model="generateForm.month" placeholder="选择月份">
            <el-option v-for="m in 12" :key="m" :label="`${m}月`" :value="m" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="generateDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="generating" @click="handleGenerateSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Calendar, Search, Refresh } from '@element-plus/icons-vue'

const loading = ref(false)
const tableData = ref([])
const generateDialogVisible = ref(false)
const generating = ref(false)

const searchForm = reactive({
  year: new Date().getFullYear().toString(),
  month: ''
})

const generateForm = reactive({
  year: new Date().getFullYear().toString(),
  month: new Date().getMonth() + 1
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
    
    const response = await fetch(`/api/admin/almanac?${params}`)
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
  searchForm.month = ''
  handleSearch()
}

const handleGenerate = () => {
  generateDialogVisible.value = true
}

const handleGenerateSubmit = async () => {
  try {
    generating.value = true
    
    const response = await fetch('/api/admin/almanac/generate-month', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(generateForm)
    })
    
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('黄历生成成功')
      generateDialogVisible.value = false
      fetchData()
    } else {
      ElMessage.error(data.message || '生成失败')
    }
  } catch (error) {
    ElMessage.error('生成失败')
  } finally {
    generating.value = false
  }
}

const handleEdit = (row) => {
  ElMessage.info('编辑功能待实现')
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
