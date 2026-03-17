<template>
  <div class="history-record">
    <!-- 头部筛选 -->
    <div class="history-header">
      <div class="filter-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="filter-tab"
          :class="{ active: activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          <el-icon :size="16"><component :is="tab.icon" /></el-icon>
          <span>{{ tab.label }}</span>
          <span class="tab-count" v-if="tab.count">{{ tab.count }}</span>
        </button>
      </div>
      <div class="header-actions">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索历史记录..."
          prefix-icon="Search"
          clearable
          class="search-input"
        />
        <el-button type="danger" text @click="clearAll" v-if="filteredRecords.length > 0">
          <el-icon><Delete /></el-icon>
          清空
        </el-button>
      </div>
    </div>

    <!-- 记录列表 -->
    <div class="history-list" v-loading="loading">
      <div v-if="filteredRecords.length === 0" class="empty-state">
        <el-icon :size="64" color="#ddd"><Document /></el-icon>
        <p>暂无历史记录</p>
        <el-button type="primary" @click="goToExplore">去探索</el-button>
      </div>

      <TransitionGroup name="list" tag="div">
        <div
          v-for="record in filteredRecords"
          :key="record.id"
          class="history-item"
          @click="viewDetail(record)"
        >
          <div class="item-icon" :style="{ background: record.typeColor }">
            <el-icon :size="24" color="white"><component :is="record.typeIcon" /></el-icon>
          </div>
          <div class="item-content">
            <div class="item-header">
              <h4 class="item-title">{{ record.title }}</h4>
              <span class="item-time">{{ formatTime(record.createTime) }}</span>
            </div>
            <p class="item-desc">{{ record.description }}</p>
            <div class="item-tags" v-if="record.tags">
              <span v-for="(tag, index) in record.tags" :key="index" class="item-tag">
                {{ tag }}
              </span>
            </div>
          </div>
          <div class="item-actions">
            <el-button circle text @click.stop="shareRecord(record)">
              <el-icon><Share /></el-icon>
            </el-button>
            <el-button circle text @click.stop="deleteRecord(record)">
              <el-icon><Delete /></el-icon>
            </el-button>
            <el-icon class="arrow-icon"><ArrowRight /></el-icon>
          </div>
        </div>
      </TransitionGroup>

      <!-- 分页 -->
      <div class="pagination-wrapper" v-if="total > pageSize">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          layout="prev, pager, next"
          @change="loadRecords"
        />
      </div>
    </div>

    <!-- 详情弹窗 -->
    <el-dialog
      v-model="detailVisible"
      :title="currentRecord?.title"
      width="600px"
      class="history-detail-dialog"
    >
      <div class="detail-content" v-if="currentRecord">
        <div class="detail-time">
          <el-icon><Clock /></el-icon>
          <span>{{ formatFullTime(currentRecord.createTime) }}</span>
        </div>
        <div class="detail-body">
          <slot name="detail" :record="currentRecord">
            <pre>{{ JSON.stringify(currentRecord.data, null, 2) }}</pre>
          </slot>
        </div>
        <div class="detail-actions">
          <el-button @click="shareRecord(currentRecord)">
            <el-icon><Share /></el-icon>
            分享
          </el-button>
          <el-button type="primary" @click="redoAction(currentRecord)">
            <el-icon><RefreshRight /></el-icon>
            再次测算
          </el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Search,
  Delete,
  Document,
  Share,
  ArrowRight,
  Clock,
  RefreshRight,
  Calendar,
  Magic,
  Star,
  User
} from '@element-plus/icons-vue'
import { useAnalytics } from '@/utils/analytics'

const props = defineProps({
  records: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['delete', 'share', 'redo'])
const router = useRouter()
const analytics = useAnalytics()

const loading = ref(false)
const activeTab = ref('all')
const searchKeyword = ref('')
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const detailVisible = ref(false)
const currentRecord = ref(null)

// 标签配置
const tabs = [
  { key: 'all', label: '全部', icon: 'Document', count: 0 },
  { key: 'bazi', label: '八字排盘', icon: 'Calendar', count: 0 },
  { key: 'tarot', label: '塔罗占卜', icon: 'MagicStick', count: 0 },
  { key: 'fortune', label: '每日运势', icon: 'Star', count: 0 }
]

// 模拟历史记录数据
const mockRecords = ref([
  {
    id: '1',
    type: 'bazi',
    typeIcon: 'Calendar',
    typeColor: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    title: '八字排盘分析',
    description: '阳历1995年8月15日 10:30 北京',
    createTime: Date.now() - 3600000,
    tags: ['正官格', '五行平衡'],
    data: { year: '乙亥', month: '甲申', day: '丙午', hour: '癸巳' }
  },
  {
    id: '2',
    type: 'tarot',
    typeIcon: 'Magic',
    typeColor: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    title: '塔罗占卜 - 事业发展',
    description: '抽牌结果：太阳正位、星星正位、命运之轮',
    createTime: Date.now() - 86400000,
    tags: ['大吉', '光明前景'],
    data: { cards: ['太阳', '星星', '命运之轮'] }
  },
  {
    id: '3',
    type: 'fortune',
    typeIcon: 'Star',
    typeColor: 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
    title: '2024年3月15日运势',
    description: '今日运势指数：85分，贵人运旺',
    createTime: Date.now() - 172800000,
    tags: ['贵人相助'],
    data: { score: 85, luckyColor: '蓝色' }
  }
])

// 筛选后的记录
const filteredRecords = computed(() => {
  let result = mockRecords.value

  // 按类型筛选
  if (activeTab.value !== 'all') {
    result = result.filter(r => r.type === activeTab.value)
  }

  // 按关键词搜索
  if (searchKeyword.value) {
    const keyword = searchKeyword.value.toLowerCase()
    result = result.filter(r =>
      r.title.toLowerCase().includes(keyword) ||
      r.description.toLowerCase().includes(keyword)
    )
  }

  return result
})

// 格式化时间
const formatTime = (timestamp) => {
  const now = Date.now()
  const diff = now - timestamp

  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}小时前`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}天前`

  return new Date(timestamp).toLocaleDateString()
}

const formatFullTime = (timestamp) => {
  return new Date(timestamp).toLocaleString()
}

// 查看详情
const viewDetail = (record) => {
  currentRecord.value = record
  detailVisible.value = true

  analytics.track('history_view_detail', {
    type: record.type,
    recordId: record.id
  })
}

// 分享记录
const shareRecord = (record) => {
  emit('share', record)

  analytics.track('history_share', {
    type: record.type,
    recordId: record.id
  })
}

// 删除记录
const deleteRecord = async (record) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const index = mockRecords.value.findIndex(r => r.id === record.id)
    if (index > -1) {
      mockRecords.value.splice(index, 1)
      ElMessage.success('删除成功')
    }

    emit('delete', record)

    analytics.track('history_delete', {
      type: record.type,
      recordId: record.id
    })
  } catch {
    // 取消删除
  }
}

// 清空所有
const clearAll = async () => {
  try {
    await ElMessageBox.confirm('确定要清空所有历史记录吗？此操作不可恢复', '警告', {
      confirmButtonText: '确定清空',
      cancelButtonText: '取消',
      type: 'danger'
    })

    mockRecords.value = []
    ElMessage.success('已清空所有记录')

    analytics.track('history_clear_all')
  } catch {
    // 取消
  }
}

// 重新测算
const redoAction = (record) => {
  emit('redo', record)

  // 根据类型跳转到对应页面
  const routes = {
    bazi: '/bazi',
    tarot: '/tarot',
    fortune: '/daily'
  }

  if (routes[record.type]) {
    router.push(routes[record.type])
  }

  analytics.track('history_redo', {
    type: record.type,
    recordId: record.id
  })
}

// 去探索
const goToExplore = () => {
  router.push('/')
}

// 加载记录
const loadRecords = async () => {
  loading.value = true
  // 实际项目中这里调用API
  await new Promise(resolve => setTimeout(resolve, 500))
  loading.value = false
}

onMounted(() => {
  loadRecords()

  analytics.track('history_page_view')
})
</script>

<style scoped>
.history-record {
  background: white;
  border-radius: 20px;
  overflow: hidden;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  flex-wrap: wrap;
  gap: 12px;
}

.filter-tabs {
  display: flex;
  gap: 8px;
}

.filter-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
  background: transparent;
  color: #666;
}

.filter-tab:hover {
  background: rgba(0, 0, 0, 0.03);
}

.filter-tab.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.tab-count {
  font-size: 11px;
  padding: 2px 6px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}

.filter-tab.active .tab-count {
  background: rgba(255, 255, 255, 0.3);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-input {
  width: 200px;
}

.history-list {
  padding: 12px;
  min-height: 300px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}

.empty-state p {
  margin: 16px 0;
  font-size: 14px;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  margin-bottom: 8px;
}

.history-item:hover {
  background: rgba(0, 0, 0, 0.02);
}

.item-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.item-content {
  flex: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.item-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.item-time {
  font-size: 12px;
  color: #999;
  flex-shrink: 0;
}

.item-desc {
  font-size: 13px;
  color: #666;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-tags {
  display: flex;
  gap: 6px;
  margin-top: 8px;
}

.item-tag {
  font-size: 11px;
  padding: 2px 8px;
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border-radius: 10px;
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s;
}

.history-item:hover .item-actions {
  opacity: 1;
}

.arrow-icon {
  color: #ccc;
  margin-left: 8px;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  padding: 20px;
}

/* 动画 */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* 详情弹窗 */
.detail-content {
  padding: 20px;
}

.detail-time {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #999;
  font-size: 13px;
  margin-bottom: 20px;
}

.detail-body {
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.detail-body pre {
  margin: 0;
  font-size: 13px;
  color: #666;
  overflow-x: auto;
}

.detail-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.detail-actions .el-button {
  padding: 12px 24px;
}
</style>
