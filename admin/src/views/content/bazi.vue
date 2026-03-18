<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.user_id" placeholder="请输入用户ID" clearable />
        </el-form-item>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="queryForm.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table v-loading="loading" :data="recordList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="name" label="姓名" width="100" />
        <el-table-column prop="birth_date" label="出生日期" width="120" />
        <el-table-column prop="birth_time" label="出生时辰" width="100" />
        <el-table-column prop="gender" label="性别" width="80">
          <template #default="{ row }">
            <el-tag size="small">{{ row.gender === 1 ? '男' : '女' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="bazi" label="八字" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">查看</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="八字详情" width="600px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="姓名">{{ detailDialog.data.name }}</el-descriptions-item>
        <el-descriptions-item label="性别">{{ detailDialog.data.gender === 1 ? '男' : '女' }}</el-descriptions-item>
        <el-descriptions-item label="出生日期">{{ detailDialog.data.birth_date }}</el-descriptions-item>
        <el-descriptions-item label="出生时辰">{{ detailDialog.data.birth_time }}</el-descriptions-item>
        <el-descriptions-item label="八字" :span="2">{{ detailDialog.data.bazi }}</el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getBaziRecords, getBaziDetail, deleteBaziRecord } from '@/api/content'
import { reportAdminUiError } from '@/utils/dev-error'

const loading = ref(false)
const recordList = ref([])
const total = ref(0)

const queryForm = reactive({
  user_id: '',
  dateRange: [],
  page: 1,
  pageSize: 20
})

const detailDialog = reactive({
  visible: false,
  data: {}
})

onMounted(() => {
  loadRecords()
})

async function loadRecords() {
  loading.value = true
  try {
    const { data } = await getBaziRecords(queryForm)
    recordList.value = data.list || []
    total.value = data.total || 0
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadRecords()
}

function handleReset() {
  Object.assign(queryForm, {
    user_id: '',
    dateRange: [],
    page: 1,
    pageSize: 20
  })
  loadRecords()
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  loadRecords()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadRecords()
}

async function handleView(row) {
  try {
    const { data } = await getBaziDetail(row.id)
    detailDialog.data = data
    detailDialog.visible = true
  } catch (error) {
    reportAdminUiError('content_bazi', 'load_detail_failed', error, {
      record_id: row.id,
      user_id: row.user_id
    })
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除该记录吗？', '提示', { type: 'warning' })
    await deleteBaziRecord(row.id)
    ElMessage.success('删除成功')
    loadRecords()
  } catch {
    // 取消删除
  }
}
</script>

<style lang="scss" scoped>
.search-form {
  margin-bottom: 20px;
}
</style>
