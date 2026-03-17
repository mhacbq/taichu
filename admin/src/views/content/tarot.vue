<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="用户ID">
          <el-input v-model="queryForm.userId" placeholder="用户ID" clearable />
        </el-form-item>
        <el-form-item label="牌阵">
          <el-select v-model="queryForm.spread" placeholder="全部牌阵" clearable>
            <el-option label="单张" value="single" />
            <el-option label="三张" value="three" />
            <el-option label="凯尔特十字" value="celtic" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="recordList" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user_id" label="用户ID" width="100" />
        <el-table-column prop="question" label="问题" min-width="150" show-overflow-tooltip />
        <el-table-column prop="spread_name" label="牌阵" width="120" />
        <el-table-column prop="cards" label="所抽牌面" min-width="150" />
        <el-table-column prop="created_at" label="时间" width="180" />
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadRecords"
          @current-change="loadRecords"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

const loading = ref(false)
const total = ref(0)
const recordList = ref([])

const queryForm = reactive({
  userId: '',
  spread: '',
  page: 1,
  pageSize: 20
})

onMounted(() => {
  loadRecords()
})

async function loadRecords() {
  loading.value = true
  // TODO: 调用真实API
  setTimeout(() => {
    recordList.value = []
    total.value = 0
    loading.value = false
  }, 500)
}

function handleSearch() {
  queryForm.page = 1
  loadRecords()
}

function handleView(row) {
  // 查看详情
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
