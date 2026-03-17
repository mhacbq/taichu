<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="日期">
          <el-date-picker
            v-model="queryForm.date"
            type="date"
            placeholder="选择日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item label="日主">
          <el-select v-model="queryForm.dayMaster" placeholder="全部日主" clearable>
            <el-option v-for="item in stemList" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button type="success" @click="handleGenerate">生成当日运势</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="fortuneList" v-loading="loading" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="day_master" label="日主" width="80" />
        <el-table-column prop="fortune_score" label="综合评分" width="100" />
        <el-table-column prop="summary" label="运势简评" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="生成时间" width="180" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadFortunes"
          @current-change="loadFortunes"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const total = ref(0)
const fortuneList = ref([])
const stemList = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']

const queryForm = reactive({
  date: '',
  dayMaster: '',
  page: 1,
  pageSize: 20
})

onMounted(() => {
  loadFortunes()
})

async function loadFortunes() {
  loading.value = true
  // TODO: 调用真实API
  setTimeout(() => {
    fortuneList.value = []
    total.value = 0
    loading.value = false
  }, 500)
}

function handleSearch() {
  queryForm.page = 1
  loadFortunes()
}

function handleGenerate() {
  ElMessage.success('已触发AI自动生成当日运势')
}

function handleEdit(row) {
  // 编辑
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该条运势数据吗？', '提示', { type: 'warning' })
    ElMessage.success('删除成功')
    loadFortunes()
  } catch {}
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
.search-form {
  margin-bottom: 20px;
}
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
