<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const generating = ref(false)
const almanacList = ref([])
const total = ref(0)
const detailVisible = ref(false)
const currentDetail = ref<any>(null)

const queryForm = ref({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  page: 1,
  page_size: 20
})

const generateForm = ref({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1
})

const months = Array.from({ length: 12 }, (_, i) => ({ label: `${i + 1}月`, value: i + 1 }))
const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 1 + i)

onMounted(() => {
  fetchList()
})

async function fetchList() {
  loading.value = true
  try {
    const res = await request({
      url: '/almanac/list',
      method: 'get',
      params: queryForm.value
    })
    almanacList.value = res.data?.list || []
    total.value = res.data?.total || 0
  } catch (error) {
    // 错误已由request拦截器处理
  } finally {
    loading.value = false
  }
}

async function handleViewDetail(date: string) {
  try {
    const res = await request({
      url: '/almanac/detail',
      method: 'get',
      params: { date }
    })
    currentDetail.value = res.data
    detailVisible.value = true
  } catch (error) {
    // 错误已由request拦截器处理
  }
}

async function handleDelete(date: string) {
  try {
    await ElMessageBox.confirm(`确定要删除 ${date} 的黄历数据吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await request({
      url: '/almanac/delete',
      method: 'post',
      data: { date }
    })
    ElMessage.success('删除成功')
    fetchList()
  } catch (error: any) {
    if (error !== 'cancel') {
      // 错误已由request拦截器处理
    }
  }
}

async function handleGenerateMonth() {
  try {
    await ElMessageBox.confirm(
      `确定要生成 ${generateForm.value.year}年${generateForm.value.month}月 的黄历数据吗？已有数据将被覆盖。`,
      '提示',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    generating.value = true
    await request({
      url: '/almanac/generate-month',
      method: 'post',
      data: { year: generateForm.value.year, month: generateForm.value.month }
    })
    ElMessage.success('生成成功')
    // 同步查询条件并刷新
    queryForm.value.year = generateForm.value.year
    queryForm.value.month = generateForm.value.month
    queryForm.value.page = 1
    fetchList()
  } catch (error: any) {
    if (error !== 'cancel') {
      // 错误已由request拦截器处理
    }
  } finally {
    generating.value = false
  }
}

function handleSearch() {
  queryForm.value.page = 1
  fetchList()
}

function handlePageChange(page: number) {
  queryForm.value.page = page
  fetchList()
}

function getLunarTag(item: any) {
  const tags = []
  if (item.festival) tags.push(item.festival)
  if (item.solar_term) tags.push(item.solar_term)
  return tags
}
</script>

<template>
  <div class="app-container">
    <!-- 工具栏 -->
    <el-card shadow="never" class="toolbar-card">
      <div class="toolbar">
        <div class="query-area">
          <el-select v-model="queryForm.year" style="width: 100px" @change="handleSearch">
            <el-option v-for="y in years" :key="y" :label="`${y}年`" :value="y" />
          </el-select>
          <el-select v-model="queryForm.month" style="width: 90px" @change="handleSearch">
            <el-option v-for="m in months" :key="m.value" :label="m.label" :value="m.value" />
          </el-select>
          <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>
        <div class="generate-area">
          <span class="generate-label">批量生成：</span>
          <el-select v-model="generateForm.year" style="width: 100px">
            <el-option v-for="y in years" :key="y" :label="`${y}年`" :value="y" />
          </el-select>
          <el-select v-model="generateForm.month" style="width: 90px">
            <el-option v-for="m in months" :key="m.value" :label="m.label" :value="m.value" />
          </el-select>
          <el-button type="warning" :loading="generating" @click="handleGenerateMonth">
            生成整月数据
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 数据列表 -->
    <el-card shadow="never">
      <el-table :data="almanacList" v-loading="loading" stripe border>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="lunar_date" label="农历" width="150" />
        <el-table-column prop="day_stem_branch" label="干支" width="100" />
        <el-table-column label="节日/节气" width="160">
          <template #default="{ row }">
            <el-tag
              v-for="tag in getLunarTag(row)"
              :key="tag"
              size="small"
              type="success"
              style="margin-right: 4px"
            >
              {{ tag }}
            </el-tag>
            <span v-if="!getLunarTag(row).length" class="text-muted">—</span>
          </template>
        </el-table-column>
        <el-table-column prop="yi" label="宜" show-overflow-tooltip />
        <el-table-column prop="ji" label="忌" show-overflow-tooltip />
        <el-table-column label="操作" width="130" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleViewDetail(row.date)">
              详情
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row.date)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.page_size"
          :total="total"
          :page-sizes="[20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="handlePageChange"
          @size-change="handleSearch"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="黄历详情" width="600px">
      <template v-if="currentDetail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="日期">{{ currentDetail.date }}</el-descriptions-item>
          <el-descriptions-item label="农历">{{ currentDetail.lunar_date }}</el-descriptions-item>
          <el-descriptions-item label="干支">{{ currentDetail.day_stem_branch }}</el-descriptions-item>
          <el-descriptions-item label="星期">{{ currentDetail.week_day }}</el-descriptions-item>
          <el-descriptions-item label="节日">{{ currentDetail.festival || '—' }}</el-descriptions-item>
          <el-descriptions-item label="节气">{{ currentDetail.solar_term || '—' }}</el-descriptions-item>
          <el-descriptions-item label="宜" :span="2">{{ currentDetail.yi || '—' }}</el-descriptions-item>
          <el-descriptions-item label="忌" :span="2">{{ currentDetail.ji || '—' }}</el-descriptions-item>
          <el-descriptions-item label="吉神" :span="2">{{ currentDetail.lucky_god || '—' }}</el-descriptions-item>
          <el-descriptions-item label="凶神" :span="2">{{ currentDetail.evil_god || '—' }}</el-descriptions-item>
        </el-descriptions>
      </template>
      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.app-container {
  padding: 20px;
}
.toolbar-card {
  margin-bottom: 16px;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.query-area,
.generate-area {
  display: flex;
  align-items: center;
  gap: 8px;
}
.generate-label {
  font-size: 14px;
  color: #606266;
  white-space: nowrap;
}
.pagination-wrap {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}
.text-muted {
  color: #c0c4cc;
}
</style>