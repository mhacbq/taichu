<template>
  <div class="favorites-manager">
    <!-- 头部 -->
    <div class="favorites-header">
      <div class="header-title">
        <el-icon :size="24" color="#f59e0b"><StarFilled /></el-icon>
        <h3>我的收藏</h3>
        <span class="count-badge">{{ favorites.length }}</span>
      </div>
      <div class="header-actions">
        <el-radio-group v-model="viewMode" size="small">
          <el-radio-button label="grid">
            <el-icon><Grid /></el-icon>
          </el-radio-button>
          <el-radio-button label="list">
            <el-icon><List /></el-icon>
          </el-radio-button>
        </el-radio-group>
        <el-button type="danger" text @click="clearAll" v-if="favorites.length > 0">
          <el-icon><Delete /></el-icon>
          清空
        </el-button>
      </div>
    </div>

    <!-- 分类筛选 -->
    <div class="category-filter">
      <button
        v-for="cat in categories"
        :key="cat.key"
        class="category-btn"
        :class="{ active: activeCategory === cat.key }"
        @click="activeCategory = cat.key"
      >
        <el-icon :size="16"><component :is="cat.icon" /></el-icon>
        <span>{{ cat.label }}</span>
        <span class="category-count">{{ cat.count }}</span>
      </button>
    </div>

    <!-- 收藏列表 -->
    <div class="favorites-content" v-loading="loading">
      <div v-if="filteredFavorites.length === 0" class="empty-state">
        <el-icon :size="80" color="#ddd"><Collection /></el-icon>
        <h4>暂无收藏内容</h4>
        <p>收藏喜欢的运势解读，随时查看</p>
        <el-button type="primary" @click="goExplore">去探索</el-button>
      </div>

      <!-- 网格视图 -->
      <div v-else-if="viewMode === 'grid'" class="grid-view">
        <div
          v-for="item in filteredFavorites"
          :key="item.id"
          class="favorite-card"
          @click="viewDetail(item)"
        >
          <div class="card-header" :style="{ background: item.color }">
            <el-icon :size="32" color="white"><component :is="item.icon" /></el-icon>
            <button class="unfavorite-btn" @click.stop="unfavorite(item)">
              <el-icon :size="18" color="#f59e0b"><StarFilled /></el-icon>
            </button>
          </div>
          <div class="card-body">
            <h4 class="card-title">{{ item.title }}</h4>
            <p class="card-desc">{{ item.description }}</p>
            <div class="card-meta">
              <span class="meta-time">
                <el-icon><Clock /></el-icon>
                {{ formatTime(item.createTime) }}
              </span>
              <span class="meta-tag" v-if="item.tag">{{ item.tag }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 列表视图 -->
      <div v-else class="list-view">
        <TransitionGroup name="list">
          <div
            v-for="item in filteredFavorites"
            :key="item.id"
            class="favorite-list-item"
            @click="viewDetail(item)"
          >
            <div class="item-icon" :style="{ background: item.color }">
              <el-icon :size="24" color="white"><component :is="item.icon" /></el-icon>
            </div>
            <div class="item-content">
              <h4 class="item-title">{{ item.title }}</h4>
              <p class="item-desc">{{ item.description }}</p>
              <div class="item-meta">
                <span class="meta-time">
                  <el-icon><Clock /></el-icon>
                  {{ formatTime(item.createTime) }}
                </span>
              </div>
            </div>
            <div class="item-actions">
              <el-button circle text @click.stop="shareItem(item)">
                <el-icon><Share /></el-icon>
              </el-button>
              <el-button circle text @click.stop="unfavorite(item)">
                <el-icon color="#f59e0b"><StarFilled /></el-icon>
              </el-button>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>

    <!-- 创建文件夹弹窗 -->
    <el-dialog
      v-model="folderDialogVisible"
      title="新建收藏夹"
      width="400px"
    >
      <el-form :model="folderForm" label-position="top">
        <el-form-item label="名称">
          <el-input v-model="folderForm.name" placeholder="输入收藏夹名称" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input
            v-model="folderForm.description"
            type="textarea"
            rows="3"
            placeholder="添加描述（可选）"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="folderDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="createFolder">创建</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  StarFilled,
  Delete,
  Grid,
  List,
  Collection,
  Clock,
  Share,
  Calendar,
  Magic,
  Star,
  Document,
  Heart
} from '@element-plus/icons-vue'
import { useAnalytics } from '@/utils/analytics'

const router = useRouter()
const analytics = useAnalytics()

const loading = ref(false)
const viewMode = ref('grid')
const activeCategory = ref('all')
const folderDialogVisible = ref(false)
const folderForm = ref({ name: '', description: '' })

// 分类配置
const categories = [
  { key: 'all', label: '全部', icon: 'Collection', count: 0 },
  { key: 'bazi', label: '八字', icon: 'Calendar', count: 0 },
  { key: 'tarot', label: '塔罗', icon: 'Magic', count: 0 },
  { key: 'fortune', label: '运势', icon: 'Star', count: 0 },
  { key: 'article', label: '文章', icon: 'Document', count: 0 }
]

// 模拟收藏数据
const favorites = ref([
  {
    id: '1',
    type: 'bazi',
    icon: 'Calendar',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    title: '八字命盘详解',
    description: '我的八字命盘分析：正官格，五行平衡，事业财运解析...',
    createTime: Date.now() - 3600000,
    tag: '重要',
    data: {}
  },
  {
    id: '2',
    type: 'tarot',
    icon: 'Magic',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    title: '塔罗占卜 - 事业发展',
    description: '太阳正位，事业光明前景，把握机遇',
    createTime: Date.now() - 86400000,
    tag: '',
    data: {}
  },
  {
    id: '3',
    type: 'fortune',
    icon: 'Star',
    color: 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
    title: '2024年运势详解',
    description: '今年整体运势分析，每月运程预测',
    createTime: Date.now() - 172800000,
    tag: '年度',
    data: {}
  },
  {
    id: '4',
    type: 'article',
    icon: 'Document',
    color: 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
    title: '如何看懂自己的八字',
    description: '八字入门指南，教你快速读懂命盘信息',
    createTime: Date.now() - 259200000,
    tag: '学习',
    data: {}
  },
  {
    id: '5',
    type: 'bazi',
    icon: 'Calendar',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    title: '八字合婚分析',
    description: '双方八字匹配度：85分，天作之合',
    createTime: Date.now() - 345600000,
    tag: '',
    data: {}
  }
])

// 筛选后的收藏
const filteredFavorites = computed(() => {
  if (activeCategory.value === 'all') {
    return favorites.value
  }
  return favorites.value.filter(item => item.type === activeCategory.value)
})

// 更新分类计数
const updateCategoryCounts = () => {
  categories.forEach(cat => {
    if (cat.key === 'all') {
      cat.count = favorites.value.length
    } else {
      cat.count = favorites.value.filter(item => item.type === cat.key).length
    }
  })
}

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

// 查看详情
const viewDetail = (item) => {
  analytics.track('favorites_view_detail', {
    type: item.type,
    itemId: item.id
  })
}

// 取消收藏
const unfavorite = async (item) => {
  try {
    await ElMessageBox.confirm('确定要取消收藏吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })

    const index = favorites.value.findIndex(f => f.id === item.id)
    if (index > -1) {
      favorites.value.splice(index, 1)
      updateCategoryCounts()
      ElMessage.success('已取消收藏')
    }

    analytics.track('favorites_remove', {
      type: item.type,
      itemId: item.id
    })
  } catch {
    // 取消
  }
}

// 分享
const shareItem = (item) => {
  ElMessage.success('分享功能开发中')

  analytics.track('favorites_share', {
    type: item.type,
    itemId: item.id
  })
}

// 清空所有
const clearAll = async () => {
  try {
    await ElMessageBox.confirm('确定要清空所有收藏吗？', '警告', {
      confirmButtonText: '确定清空',
      cancelButtonText: '取消',
      type: 'danger'
    })

    favorites.value = []
    updateCategoryCounts()
    ElMessage.success('已清空所有收藏')

    analytics.track('favorites_clear_all')
  } catch {
    // 取消
  }
}

// 创建文件夹
const createFolder = () => {
  if (!folderForm.value.name.trim()) {
    ElMessage.warning('请输入收藏夹名称')
    return
  }

  ElMessage.success('收藏夹创建成功')
  folderDialogVisible.value = false
  folderForm.value = { name: '', description: '' }
}

// 去探索
const goExplore = () => {
  router.push('/')
}

onMounted(() => {
  updateCategoryCounts()
  analytics.track('favorites_page_view')
})
</script>

<style scoped>
.favorites-manager {
  background: white;
  border-radius: 20px;
  overflow: hidden;
}

.favorites-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-title h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
}

.count-badge {
  background: #f59e0b;
  color: white;
  font-size: 12px;
  padding: 2px 10px;
  border-radius: 12px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.category-filter {
  display: flex;
  gap: 8px;
  padding: 16px 24px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  overflow-x: auto;
}

.category-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
  background: rgba(0, 0, 0, 0.03);
  color: #666;
  white-space: nowrap;
}

.category-btn:hover {
  background: rgba(0, 0, 0, 0.06);
}

.category-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.category-count {
  font-size: 11px;
  padding: 2px 6px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}

.category-btn.active .category-count {
  background: rgba(255, 255, 255, 0.3);
}

.favorites-content {
  padding: 20px;
  min-height: 400px;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-state h4 {
  font-size: 18px;
  color: #333;
  margin: 20px 0 8px;
}

.empty-state p {
  color: #999;
  margin-bottom: 24px;
}

/* 网格视图 */
.grid-view {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.favorite-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: all 0.3s;
}

.favorite-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.card-header {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.unfavorite-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.unfavorite-btn:hover {
  transform: scale(1.1);
}

.card-body {
  padding: 16px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-desc {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin: 0 0 12px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.card-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.meta-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #999;
}

.meta-tag {
  font-size: 11px;
  padding: 2px 8px;
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border-radius: 10px;
}

/* 列表视图 */
.list-view {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.favorite-list-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  background: rgba(0, 0, 0, 0.02);
}

.favorite-list-item:hover {
  background: rgba(0, 0, 0, 0.04);
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

.item-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin: 0 0 4px;
}

.item-desc {
  font-size: 13px;
  color: #666;
  margin: 0 0 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-meta {
  display: flex;
  gap: 12px;
}

.item-actions {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s;
}

.favorite-list-item:hover .item-actions {
  opacity: 1;
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
</style>
