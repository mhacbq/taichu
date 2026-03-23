<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, View, Refresh } from '@element-plus/icons-vue'
import {
  getShenshaList,
  getShenshaOptions,
  toggleShenshaStatus
} from '@/api/admin'

// ========== 状态 ==========
const loading = ref(false)
const shenshaList = ref([])
const total = ref(0)
const options = reactive({
  types: [],
  categories: [],
  statuses: []
})

// 筛选条件
const filters = reactive({
  keyword: '',
  type: '',
  category: '',
  status: '',
  page: 1,
  pageSize: 20
})

// 查看详情对话框
const dialogVisible = ref(false)
const currentRow = ref(null)

// ========== 类型/分类映射 ==========
const typeOptions = [
  { label: '大吉', value: 'daji', color: '#e74c3c' },
  { label: '吉', value: 'ji', color: '#e67e22' },
  { label: '平', value: 'ping', color: '#3498db' },
  { label: '凶', value: 'xiong', color: '#7f8c8d' },
  { label: '大凶', value: 'daxiong', color: '#2c3e50' }
]

const categoryOptions = [
  { label: '贵人', value: 'guiren' },
  { label: '学业', value: 'xueye' },
  { label: '感情', value: 'ganqing' },
  { label: '健康', value: 'jiankang' },
  { label: '财运', value: 'caiyun' },
  { label: '事业', value: 'shiye' },
  { label: '综合', value: 'zonghe' }
]

// ========== 计算属性 ==========
const typeMap = computed(() => {
  const map = {}
  typeOptions.forEach(t => { map[t.value] = t })
  return map
})

const categoryMap = computed(() => {
  const map = {}
  categoryOptions.forEach(c => { map[c.value] = c })
  return map
})

// 统计信息
const stats = computed(() => {
  const list = shenshaList.value
  const totalCount = total.value
  const enabledCount = list.filter(i => i.status === 1).length
  const disabledCount = list.filter(i => i.status === 0).length
  const jiCount = list.filter(i => ['daji', 'ji'].includes(i.type)).length
  const xiongCount = list.filter(i => ['xiong', 'daxiong'].includes(i.type)).length
  const pingCount = list.filter(i => i.type === 'ping').length
  return { totalCount, enabledCount, disabledCount, jiCount, xiongCount, pingCount }
})

// ========== 方法 ==========

// 加载列表
const loadList = async () => {
  loading.value = true
  try {
    const res = await getShenshaList({
      page: filters.page,
      pageSize: filters.pageSize,
      keyword: filters.keyword || undefined,
      type: filters.type || undefined,
      category: filters.category || undefined,
      status: filters.status !== '' ? filters.status : undefined
    })
    if (res.data?.code === 0 || res.data?.code === 200) {
      const data = res.data?.data || res.data
      shenshaList.value = data.list || data.data || []
      total.value = data.total || 0
    } else {
      shenshaList.value = []
      total.value = 0
    }
  } catch (e) {
    console.error('加载神煞列表失败:', e)
    ElMessage.error('加载失败，请重试')
  } finally {
    loading.value = false
  }
}

// 加载筛选项
const loadOptions = async () => {
  try {
    const res = await getShenshaOptions()
    if (res.data?.code === 0 || res.data?.code === 200) {
      const data = res.data?.data || res.data
      options.types = data.types || []
      options.categories = data.categories || []
      options.statuses = data.statuses || []
    }
  } catch (e) {
    console.error('加载筛选项失败:', e)
  }
}

// 搜索
const handleSearch = () => {
  filters.page = 1
  loadList()
}

// 重置筛选
const handleReset = () => {
  filters.keyword = ''
  filters.type = ''
  filters.category = ''
  filters.status = ''
  filters.page = 1
  loadList()
}

// 翻页
const handlePageChange = (page) => {
  filters.page = page
  loadList()
}

const handleSizeChange = (size) => {
  filters.pageSize = size
  filters.page = 1
  loadList()
}

// 查看详情
const handleView = (row) => {
  currentRow.value = row
  dialogVisible.value = true
}

// 切换状态
const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const actionText = newStatus === 1 ? '启用' : '停用'

  try {
    const res = await toggleShenshaStatus(row.id, newStatus)
    if (res.data?.code === 0 || res.data?.code === 200) {
      row.status = newStatus
      ElMessage.success(`${actionText}成功 - 将影响前端排盘中「${row.name}」的显示`)
    } else {
      ElMessage.error(res.data?.msg || `${actionText}失败`)
    }
  } catch (e) {
    console.error('切换状态失败:', e)
    ElMessage.error(`${actionText}失败，请重试`)
  }
}

// 获取类型标签样式
const getTypeTagType = (type) => {
  const map = { daji: 'danger', ji: 'warning', ping: 'info', xiong: '', daxiong: 'info' }
  return map[type] || 'info'
}

// 获取类型显示文字
const getTypeLabel = (type) => {
  return typeMap.value[type]?.label || type || '-'
}

// 获取分类显示文字
const getCategoryLabel = (category) => {
  return categoryMap.value[category]?.label || category || '-'
}

// ========== 初始化 ==========
onMounted(() => {
  loadList()
  loadOptions()
})
</script>

<template>
  <div class="admin-shensha-manage" v-loading="loading">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <h2>神煞管理</h2>
        <span class="header-tip">管理八字算法中的神煞数据，启用/停用将直接影响前端排盘结果中对应神煞的显示</span>
      </div>
    </div>

    <!-- 运营说明 -->
    <el-alert
      type="info"
      :closable="false"
      show-icon
      style="margin-bottom: 16px"
    >
      <template #title>
        <span style="font-weight: 600">运营提示</span>
      </template>
      <div style="line-height: 1.8">
        神煞数据由算法程序维护，此页面<strong>仅支持控制「启用/停用」状态</strong>。<br />
        <strong>启用</strong>：该神煞会在前端排盘结果中正常显示；<strong>停用</strong>：前端排盘将隐藏该神煞。<br />
        其他信息（名称、类型、分类、含义、规则等）如需修改，请联系开发人员调整算法代码。
      </div>
    </el-alert>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ stats.totalCount }}</div>
        <div class="stat-label">总数</div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-value">{{ stats.enabledCount }}</div>
        <div class="stat-label">已启用</div>
      </div>
      <div class="stat-card stat-danger">
        <div class="stat-value">{{ stats.disabledCount }}</div>
        <div class="stat-label">已停用</div>
      </div>
      <div class="stat-card stat-warning">
        <div class="stat-value">{{ stats.jiCount }}</div>
        <div class="stat-label">吉神</div>
      </div>
      <div class="stat-card stat-info">
        <div class="stat-value">{{ stats.xiongCount }}</div>
        <div class="stat-label">凶煞</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.pingCount }}</div>
        <div class="stat-label">平</div>
      </div>
    </div>

    <!-- 筛选区域 -->
    <div class="filter-bar">
      <el-input
        v-model="filters.keyword"
        placeholder="搜索名称/描述/影响"
        clearable
        :prefix-icon="Search"
        style="width: 220px"
        @keyup.enter="handleSearch"
      />
      <el-select v-model="filters.type" placeholder="吉凶类型" clearable style="width: 140px">
        <el-option v-for="t in typeOptions" :key="t.value" :label="t.label" :value="t.value" />
      </el-select>
      <el-select v-model="filters.category" placeholder="分类" clearable style="width: 140px">
        <el-option v-for="c in categoryOptions" :key="c.value" :label="c.label" :value="c.value" />
      </el-select>
      <el-select v-model="filters.status" placeholder="状态" clearable style="width: 120px">
        <el-option label="启用" :value="1" />
        <el-option label="停用" :value="0" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
      <el-button :icon="Refresh" @click="handleReset">重置</el-button>
    </div>

    <!-- 数据表格 -->
    <div class="table-wrapper">
      <el-table :data="shenshaList" border stripe style="width: 100%" row-key="id">
        <el-table-column prop="sort" label="排序" width="70" align="center" />
        <el-table-column prop="name" label="名称" width="120">
          <template #default="{ row }">
            <span class="shensha-name">{{ row.name }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="type" label="吉凶" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)" size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="category" label="分类" width="90" align="center">
          <template #default="{ row }">
            <el-tag type="info" size="small" effect="plain">{{ getCategoryLabel(row.category) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="含义" min-width="180" show-overflow-tooltip />
        <el-table-column prop="effect" label="影响" min-width="180" show-overflow-tooltip />
        <el-table-column prop="check_rule" label="查法口诀" min-width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              active-text="启用"
              inactive-text="停用"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link :icon="View" @click="handleView(row)">查看</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper" v-if="total > 0">
        <el-pagination
          v-model:current-page="filters.page"
          v-model:page-size="filters.pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="handlePageChange"
          @size-change="handleSizeChange"
        />
      </div>
    </div>

    <!-- 查看详情对话框 -->
    <el-dialog v-model="dialogVisible" title="神煞详情" width="680px" destroy-on-close>
      <template v-if="currentRow">
        <!-- 基本信息（只读展示） -->
        <el-descriptions :column="2" border style="margin-bottom: 20px">
          <el-descriptions-item label="名称">{{ currentRow.name }}</el-descriptions-item>
          <el-descriptions-item label="排序">{{ currentRow.sort }}</el-descriptions-item>
          <el-descriptions-item label="吉凶类型">
            <el-tag :type="getTypeTagType(currentRow.type)" size="small">{{ getTypeLabel(currentRow.type) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="分类">
            <el-tag type="info" size="small" effect="plain">{{ getCategoryLabel(currentRow.category) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="含义" :span="2">{{ currentRow.description || '-' }}</el-descriptions-item>
          <el-descriptions-item label="影响" :span="2">{{ currentRow.effect || '-' }}</el-descriptions-item>
          <el-descriptions-item label="查法口诀" :span="2">{{ currentRow.check_rule || '-' }}</el-descriptions-item>
          <el-descriptions-item label="算法代码" :span="2">{{ currentRow.check_code || '-' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 规则数据（只读展示） -->
        <el-row :gutter="16" v-if="currentRow.gan_rules || currentRow.zhi_rules" style="margin-bottom: 20px">
          <el-col :span="12" v-if="currentRow.gan_rules">
            <div class="rule-block">
              <div class="rule-title">天干规则</div>
              <pre class="rule-content">{{ typeof currentRow.gan_rules === 'string' ? currentRow.gan_rules : JSON.stringify(currentRow.gan_rules, null, 2) }}</pre>
            </div>
          </el-col>
          <el-col :span="12" v-if="currentRow.zhi_rules">
            <div class="rule-block">
              <div class="rule-title">地支规则</div>
              <pre class="rule-content">{{ typeof currentRow.zhi_rules === 'string' ? currentRow.zhi_rules : JSON.stringify(currentRow.zhi_rules, null, 2) }}</pre>
            </div>
          </el-col>
        </el-row>

        <!-- 状态控制（唯一可编辑项） -->
        <el-divider content-position="left">展示控制（可修改）</el-divider>
        <div class="status-control">
          <div class="status-desc">
            <el-icon style="color: #e6a23c; margin-right: 4px"><View /></el-icon>
            <span>控制此神煞是否在前端排盘结果中显示：</span>
          </div>
          <el-switch
            :model-value="currentRow.status === 1"
            active-text="启用（前端显示）"
            inactive-text="停用（前端隐藏）"
            inline-prompt
            style="--el-switch-on-color: #67c23a; --el-switch-off-color: #f56c6c"
            @change="handleToggleStatus(currentRow)"
          />
        </div>
        <div class="status-hint">
          以上信息（名称、类型、分类、含义、规则等）由算法程序维护，如需修改请联系开发人员。
        </div>
      </template>
      <template #footer>
        <el-button @click="dialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-shensha-manage {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.header-left h2 {
  margin: 0 0 4px 0;
  font-size: 20px;
}

.header-tip {
  font-size: 13px;
  color: #909399;
}

/* 统计卡片 */
.stats-row {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  border-left: 3px solid #dcdfe6;
}

.stat-card.stat-success { border-left-color: #67c23a; }
.stat-card.stat-danger { border-left-color: #f56c6c; }
.stat-card.stat-warning { border-left-color: #e6a23c; }
.stat-card.stat-info { border-left-color: #909399; }

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

/* 筛选区 */
.filter-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
  background: white;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* 表格 */
.table-wrapper {
  background: white;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.shensha-name {
  font-weight: 600;
  color: #303133;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

/* 规则展示 */
.rule-block {
  background: #f5f7fa;
  border-radius: 6px;
  padding: 12px;
}

.rule-title {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 8px;
}

.rule-content {
  font-size: 12px;
  color: #909399;
  margin: 0;
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 160px;
  overflow-y: auto;
  font-family: 'Courier New', Courier, monospace;
}

/* 状态控制区 */
.status-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f0f9eb;
  border: 1px solid #e1f3d8;
  border-radius: 8px;
  padding: 16px 20px;
}

.status-desc {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #606266;
}

.status-hint {
  font-size: 12px;
  color: #c0c4cc;
  margin-top: 12px;
  text-align: center;
  font-style: italic;
}

/* 响应式 */
@media (max-width: 1200px) {
  .stats-row {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .page-header {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
