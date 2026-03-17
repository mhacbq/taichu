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
        <el-table-column prop="question" label="咨询问题" min-width="150" show-overflow-tooltip />
        <el-table-column prop="spread_type" label="牌阵类型" width="120">
          <template #default="{ row }">
            {{ getSpreadName(row.spread_type) }}
          </template>
        </el-table-column>
        <el-table-column label="抽牌结果" min-width="150">
          <template #default="{ row }">
            <span v-for="(card, index) in parseCards(row.cards)" :key="index">
              {{ card.name }}{{ card.reversed ? '(逆)' : '' }}{{ index < parseCards(row.cards).length - 1 ? '、' : '' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row)">详情</el-button>
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
          @size-change="loadRecords"
          @current-change="loadRecords"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="dialog.visible" title="占卜记录详情" width="600px">
      <div v-if="dialog.data" class="detail-container">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="用户ID">{{ dialog.data.user_id }}</el-descriptions-item>
          <el-descriptions-item label="问题">{{ dialog.data.question }}</el-descriptions-item>
          <el-descriptions-item label="牌阵">{{ getSpreadName(dialog.data.spread_type) }}</el-descriptions-item>
          <el-descriptions-item label="抽牌">
            <div v-for="(card, index) in parseCards(dialog.data.cards)" :key="index">
              {{ index + 1 }}. {{ card.name }} ({{ card.reversed ? '逆位' : '正位' }})
            </div>
          </el-descriptions-item>
          <el-descriptions-item label="解读">
            <div class="interpretation-text">{{ dialog.data.interpretation }}</div>
          </el-descriptions-item>
          <el-descriptions-item label="AI分析" v-if="dialog.data.ai_analysis">
             <div class="interpretation-text">{{ dialog.data.ai_analysis }}</div>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTarotRecords, deleteTarotRecord } from '@/api/content'

const loading = ref(false)
const total = ref(0)
const recordList = ref([])

const queryForm = reactive({
  user_id: '',
  spread: '',
  page: 1,
  pageSize: 20
})

const dialog = reactive({
  visible: false,
  data: null
})

onMounted(() => {
  loadRecords()
})

async function loadRecords() {
  loading.value = true
  try {
    const res = await getTarotRecords(queryForm)
    recordList.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadRecords()
}

function handleView(row) {
  dialog.data = row
  dialog.visible = true
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除这条占卜记录吗？', '提示', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    await deleteTarotRecord(row.id)
    ElMessage.success('删除成功')
    loadRecords()
  } catch {}
}

function getSpreadName(type) {
  const map = {
    'single': '单张牌',
    'three': '三张牌',
    'celtic': '凯尔特十字'
  }
  return map[type] || type
}

function parseCards(cardsJson) {
  try {
    return typeof cardsJson === 'string' ? JSON.parse(cardsJson) : cardsJson
  } catch (e) {
    return []
  }
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
.interpretation-text {
  white-space: pre-wrap;
  line-height: 1.6;
  max-height: 300px;
  overflow-y: auto;
}
</style>

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
