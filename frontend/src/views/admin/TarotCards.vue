<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Edit } from '@element-plus/icons-vue'
import {
  getTarotCardList,
  getTarotCardDetail,
  updateTarotCard,
  toggleTarotCardStatus,
  batchUpdateTarotCardStatus,
  getTarotCardStats,
} from '@/api/admin'

// ========== 状态 ==========
const loading = ref(false)
const cardList = ref([])
const total = ref(0)
const stats = ref({ total: 0, enabled: 0, disabled: 0, major: 0, minor: 0, suit_stats: [] })
const selectedIds = ref([])

// 筛选条件
const filters = reactive({
  keyword: '',
  arcana: '',
  suit: '',
  is_enabled: '',
  page: 1,
  pageSize: 20,
})

// 编辑对话框
const editDialogVisible = ref(false)
const editLoading = ref(false)
const currentCard = ref(null)
const editForm = reactive({
  is_enabled: 1,
  meaning: '',
  reversed_meaning: '',
  love_meaning: '',
  love_reversed: '',
  career_meaning: '',
  career_reversed: '',
  health_meaning: '',
  health_reversed: '',
  wealth_meaning: '',
  wealth_reversed: '',
})

// 当前编辑的含义维度 tab
const activeTab = ref('general')

// ========== 选项 ==========
const arcanaOptions = [
  { label: '大阿卡纳', value: 'major' },
  { label: '小阿卡纳', value: 'minor' },
]
const suitOptions = [
  { label: '权杖', value: 'wands' },
  { label: '圣杯', value: 'cups' },
  { label: '宝剑', value: 'swords' },
  { label: '星币', value: 'pentacles' },
]
const suitColorMap = {
  wands: '#ff4757',
  cups: '#1e90ff',
  swords: '#a4b0be',
  pentacles: '#ffa502',
}
const suitLabelMap = {
  wands: '权杖',
  cups: '圣杯',
  swords: '宝剑',
  pentacles: '星币',
}

// ========== 方法 ==========
const loadStats = async () => {
  try {
    const res = await getTarotCardStats()
    if (res.data?.code === 0 || res.data?.code === 200) {
      stats.value = res.data?.data || {}
    }
  } catch (e) {
    console.error('加载统计失败:', e)
  }
}

const loadList = async () => {
  loading.value = true
  try {
    const params = {
      page: filters.page,
      pageSize: filters.pageSize,
      keyword: filters.keyword || undefined,
      arcana: filters.arcana || undefined,
      suit: filters.suit || undefined,
      is_enabled: filters.is_enabled !== '' ? filters.is_enabled : undefined,
    }
    const res = await getTarotCardList(params)
    if (res.data?.code === 0 || res.data?.code === 200) {
      const data = res.data?.data || {}
      cardList.value = data.list || []
      total.value = data.total || 0
    } else {
      cardList.value = []
      total.value = 0
    }
  } catch (e) {
    console.error('加载塔罗牌列表失败:', e)
    ElMessage.error('加载失败，请重试')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  filters.page = 1
  loadList()
}

const handleReset = () => {
  filters.keyword = ''
  filters.arcana = ''
  filters.suit = ''
  filters.is_enabled = ''
  filters.page = 1
  loadList()
}

const handlePageChange = (page) => {
  filters.page = page
  loadList()
}

const handleSizeChange = (size) => {
  filters.pageSize = size
  filters.page = 1
  loadList()
}

const handleSelectionChange = (rows) => {
  selectedIds.value = rows.map(r => r.id)
}

// 打开编辑对话框
const handleEdit = async (row) => {
  try {
    const res = await getTarotCardDetail(row.id)
    if (res.data?.code === 0 || res.data?.code === 200) {
      const card = res.data?.data || row
      currentCard.value = card
      Object.assign(editForm, {
        is_enabled: card.is_enabled ?? 1,
        meaning: card.meaning || '',
        reversed_meaning: card.reversed_meaning || '',
        love_meaning: card.love_meaning || '',
        love_reversed: card.love_reversed || '',
        career_meaning: card.career_meaning || '',
        career_reversed: card.career_reversed || '',
        health_meaning: card.health_meaning || '',
        health_reversed: card.health_reversed || '',
        wealth_meaning: card.wealth_meaning || '',
        wealth_reversed: card.wealth_reversed || '',
      })
      activeTab.value = 'general'
      editDialogVisible.value = true
    }
  } catch (e) {
    ElMessage.error('获取详情失败')
  }
}

// 保存编辑
const handleSave = async () => {
  if (!currentCard.value) return
  editLoading.value = true
  try {
    const res = await updateTarotCard(currentCard.value.id, { ...editForm })
    if (res.data?.code === 0 || res.data?.code === 200) {
      ElMessage.success('保存成功')
      editDialogVisible.value = false
      loadList()
      loadStats()
    } else {
      ElMessage.error(res.data?.msg || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败，请重试')
  } finally {
    editLoading.value = false
  }
}

// 切换单张牌状态
const handleToggleStatus = async (row) => {
  const actionText = row.is_enabled ? '停用' : '启用'
  try {
    const res = await toggleTarotCardStatus(row.id)
    if (res.data?.code === 0 || res.data?.code === 200) {
      row.is_enabled = row.is_enabled ? 0 : 1
      ElMessage.success(`${actionText}成功`)
      loadStats()
    } else {
      ElMessage.error(res.data?.msg || `${actionText}失败`)
    }
  } catch (e) {
    ElMessage.error(`${actionText}失败，请重试`)
  }
}

// 批量操作
const handleBatchStatus = async (status) => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择塔罗牌')
    return
  }
  const actionText = status ? '启用' : '停用'
  try {
    await ElMessageBox.confirm(`确定要批量${actionText} ${selectedIds.value.length} 张塔罗牌吗？`, '确认操作', {
      type: 'warning',
    })
    const res = await batchUpdateTarotCardStatus(selectedIds.value, status)
    if (res.data?.code === 0 || res.data?.code === 200) {
      ElMessage.success(`批量${actionText}成功`)
      loadList()
      loadStats()
    } else {
      ElMessage.error(res.data?.msg || `批量${actionText}失败`)
    }
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(`批量${actionText}失败`)
  }
}

// ========== 初始化 ==========
onMounted(() => {
  loadStats()
  loadList()
})
</script>

<template>
  <div class="admin-tarot-cards" v-loading="loading">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <h2>塔罗牌管理</h2>
        <span class="header-tip">管理78张塔罗牌数据，支持编辑多维度含义（感情/事业/健康/财运）和启用状态</span>
      </div>
    </div>

    <!-- 运营说明 -->
    <el-alert type="info" :closable="false" show-icon style="margin-bottom: 16px">
      <template #title><span style="font-weight: 600">运营提示</span></template>
      <div style="line-height: 1.8">
        塔罗牌基础信息（名称、花色、元素等）由系统维护，<strong>此页面支持编辑各维度含义文本和启用/停用状态</strong>。<br />
        <strong>停用</strong>某张牌后，该牌将不会出现在抽牌结果中；<strong>含义编辑</strong>将直接影响前端占卜解读内容。
      </div>
    </el-alert>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">总牌数</div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-value">{{ stats.enabled }}</div>
        <div class="stat-label">已启用</div>
      </div>
      <div class="stat-card stat-danger">
        <div class="stat-value">{{ stats.disabled }}</div>
        <div class="stat-label">已停用</div>
      </div>
      <div class="stat-card stat-warning">
        <div class="stat-value">{{ stats.major }}</div>
        <div class="stat-label">大阿卡纳</div>
      </div>
      <div class="stat-card stat-info">
        <div class="stat-value">{{ stats.minor }}</div>
        <div class="stat-label">小阿卡纳</div>
      </div>
      <div
        v-for="s in (stats.suit_stats || [])"
        :key="s.suit"
        class="stat-card"
        :style="{ borderLeftColor: suitColorMap[s.suit] }"
      >
        <div class="stat-value">{{ s.count }}</div>
        <div class="stat-label">{{ s.label }}</div>
      </div>
    </div>

    <!-- 筛选区域 -->
    <div class="filter-bar">
      <el-input
        v-model="filters.keyword"
        placeholder="搜索牌名"
        clearable
        :prefix-icon="Search"
        style="width: 200px"
        @keyup.enter="handleSearch"
      />
      <el-select v-model="filters.arcana" placeholder="阿卡纳" clearable style="width: 130px">
        <el-option v-for="o in arcanaOptions" :key="o.value" :label="o.label" :value="o.value" />
      </el-select>
      <el-select v-model="filters.suit" placeholder="花色" clearable style="width: 120px">
        <el-option v-for="o in suitOptions" :key="o.value" :label="o.label" :value="o.value" />
      </el-select>
      <el-select v-model="filters.is_enabled" placeholder="状态" clearable style="width: 110px">
        <el-option label="启用" :value="1" />
        <el-option label="停用" :value="0" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
      <el-button :icon="Refresh" @click="handleReset">重置</el-button>
      <div style="flex: 1" />
      <el-button
        v-if="selectedIds.length > 0"
        type="success"
        size="small"
        @click="handleBatchStatus(1)"
      >批量启用({{ selectedIds.length }})</el-button>
      <el-button
        v-if="selectedIds.length > 0"
        type="danger"
        size="small"
        @click="handleBatchStatus(0)"
      >批量停用({{ selectedIds.length }})</el-button>
    </div>

    <!-- 数据表格 -->
    <div class="table-wrapper">
      <el-table
        :data="cardList"
        border
        stripe
        style="width: 100%"
        row-key="id"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="45" />
        <el-table-column prop="emoji" label="" width="50" align="center">
          <template #default="{ row }">
            <span style="font-size: 22px">{{ row.emoji }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="牌名" width="110">
          <template #default="{ row }">
            <div class="card-name">{{ row.name }}</div>
            <div class="card-name-en">{{ row.name_en }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="arcana" label="类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_major ? 'warning' : 'info'" size="small">
              {{ row.is_major ? '大阿卡纳' : '小阿卡纳' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="suit" label="花色" width="80" align="center">
          <template #default="{ row }">
            <el-tag
              v-if="row.suit"
              size="small"
              effect="plain"
              :style="{ color: suitColorMap[row.suit], borderColor: suitColorMap[row.suit] }"
            >{{ suitLabelMap[row.suit] || row.suit }}</el-tag>
            <span v-else style="color: #c0c4cc">—</span>
          </template>
        </el-table-column>
        <el-table-column prop="element" label="元素" width="70" align="center">
          <template #default="{ row }">
            <span class="element-badge">{{ row.element || '—' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="meaning" label="正位综合含义" min-width="200" show-overflow-tooltip />
        <el-table-column prop="reversed_meaning" label="逆位综合含义" min-width="200" show-overflow-tooltip />
        <el-table-column prop="is_enabled" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_enabled === 1"
              active-text="启用"
              inactive-text="停用"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="90" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link :icon="Edit" @click="handleEdit(row)">编辑</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper" v-if="total > 0">
        <el-pagination
          v-model:current-page="filters.page"
          v-model:page-size="filters.pageSize"
          :total="total"
          :page-sizes="[20, 50, 78]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="handlePageChange"
          @size-change="handleSizeChange"
        />
      </div>
    </div>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      :title="`编辑塔罗牌 — ${currentCard?.name || ''} ${currentCard?.name_en || ''}`"
      width="780px"
      destroy-on-close
    >
      <template v-if="currentCard">
        <!-- 基本信息（只读） -->
        <el-descriptions :column="4" border size="small" style="margin-bottom: 16px">
          <el-descriptions-item label="牌名">
            <span style="font-size: 18px; margin-right: 6px">{{ currentCard.emoji }}</span>
            <strong>{{ currentCard.name }}</strong>
          </el-descriptions-item>
          <el-descriptions-item label="英文名">{{ currentCard.name_en }}</el-descriptions-item>
          <el-descriptions-item label="类型">
            <el-tag :type="currentCard.is_major ? 'warning' : 'info'" size="small">
              {{ currentCard.is_major ? '大阿卡纳' : '小阿卡纳' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="元素">{{ currentCard.element || '—' }}</el-descriptions-item>
        </el-descriptions>

        <!-- 启用状态 -->
        <div class="status-control" style="margin-bottom: 16px">
          <span style="font-size: 14px; color: #606266; margin-right: 12px">启用状态：</span>
          <el-switch
            v-model="editForm.is_enabled"
            :active-value="1"
            :inactive-value="0"
            active-text="启用（参与抽牌）"
            inactive-text="停用（不参与抽牌）"
            style="--el-switch-on-color: #67c23a; --el-switch-off-color: #f56c6c"
          />
        </div>

        <!-- 多维度含义编辑 -->
        <el-tabs v-model="activeTab" type="border-card">
          <el-tab-pane label="综合含义" name="general">
            <div class="meaning-grid">
              <div class="meaning-item">
                <div class="meaning-label">🌟 正位含义</div>
                <el-input
                  v-model="editForm.meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="正位综合含义..."
                />
              </div>
              <div class="meaning-item">
                <div class="meaning-label">🔄 逆位含义</div>
                <el-input
                  v-model="editForm.reversed_meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="逆位综合含义..."
                />
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="💕 感情" name="love">
            <div class="meaning-grid">
              <div class="meaning-item">
                <div class="meaning-label">🌟 正位感情含义</div>
                <el-input
                  v-model="editForm.love_meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="正位感情含义..."
                />
              </div>
              <div class="meaning-item">
                <div class="meaning-label">🔄 逆位感情含义</div>
                <el-input
                  v-model="editForm.love_reversed"
                  type="textarea"
                  :rows="4"
                  placeholder="逆位感情含义..."
                />
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="💼 事业" name="career">
            <div class="meaning-grid">
              <div class="meaning-item">
                <div class="meaning-label">🌟 正位事业含义</div>
                <el-input
                  v-model="editForm.career_meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="正位事业含义..."
                />
              </div>
              <div class="meaning-item">
                <div class="meaning-label">🔄 逆位事业含义</div>
                <el-input
                  v-model="editForm.career_reversed"
                  type="textarea"
                  :rows="4"
                  placeholder="逆位事业含义..."
                />
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="🏥 健康" name="health">
            <div class="meaning-grid">
              <div class="meaning-item">
                <div class="meaning-label">🌟 正位健康含义</div>
                <el-input
                  v-model="editForm.health_meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="正位健康含义..."
                />
              </div>
              <div class="meaning-item">
                <div class="meaning-label">🔄 逆位健康含义</div>
                <el-input
                  v-model="editForm.health_reversed"
                  type="textarea"
                  :rows="4"
                  placeholder="逆位健康含义..."
                />
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="💰 财运" name="wealth">
            <div class="meaning-grid">
              <div class="meaning-item">
                <div class="meaning-label">🌟 正位财运含义</div>
                <el-input
                  v-model="editForm.wealth_meaning"
                  type="textarea"
                  :rows="4"
                  placeholder="正位财运含义..."
                />
              </div>
              <div class="meaning-item">
                <div class="meaning-label">🔄 逆位财运含义</div>
                <el-input
                  v-model="editForm.wealth_reversed"
                  type="textarea"
                  :rows="4"
                  placeholder="逆位财运含义..."
                />
              </div>
            </div>
          </el-tab-pane>
        </el-tabs>
      </template>

      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-tarot-cards {
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
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 14px 20px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  border-left: 3px solid #dcdfe6;
  min-width: 90px;
}

.stat-card.stat-success { border-left-color: #67c23a; }
.stat-card.stat-danger  { border-left-color: #f56c6c; }
.stat-card.stat-warning { border-left-color: #e6a23c; }
.stat-card.stat-info    { border-left-color: #909399; }

.stat-value {
  font-size: 26px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

/* 筛选区 */
.filter-bar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
  background: white;
  padding: 14px 16px;
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

.card-name {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
}

.card-name-en {
  font-size: 11px;
  color: #909399;
  margin-top: 2px;
}

.element-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  background: #f0f2f5;
  font-size: 12px;
  color: #606266;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

/* 编辑对话框 */
.status-control {
  display: flex;
  align-items: center;
  background: #f5f7fa;
  border-radius: 6px;
  padding: 12px 16px;
}

.meaning-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding: 12px 0;
}

.meaning-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.meaning-label {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
}

@media (max-width: 768px) {
  .meaning-grid {
    grid-template-columns: 1fr;
  }
}
</style>
