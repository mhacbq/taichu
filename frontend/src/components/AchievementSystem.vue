<template>
  <div class="achievement-system">
    <div class="achievement-header">
      <h3 class="title">
        <span class="icon">🏆</span>
        成就中心
      </h3>
      <div class="progress-info">
        <span class="completed">{{ completedCount }}</span>
        <span class="total">/ {{ achievements.length }}</span>
      </div>
    </div>
    
    <div class="progress-bar">
      <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
    </div>
    
    <div class="achievement-categories">
      <button
        v-for="cat in categories"
        :key="cat.key"
        class="category-btn"
        :class="{ active: activeCategory === cat.key }"
        @click="activeCategory = cat.key"
      >
        <span class="cat-icon">{{ cat.icon }}</span>
        <span class="cat-name">{{ cat.name }}</span>
      </button>
    </div>
    
    <div class="achievements-grid">
      <div
        v-for="achievement in filteredAchievements"
        :key="achievement.id"
        class="achievement-card"
        :class="{
          'unlocked': achievement.unlocked,
          'locked': !achievement.unlocked,
          'new': achievement.isNew,
        }"
        @click="showDetail(achievement)"
      >
        <div class="achievement-icon">
          <span v-if="achievement.unlocked">{{ achievement.icon }}</span>
          <span v-else class="locked-icon">🔒</span>
        </div>
        <div class="achievement-info">
          <h4 class="achievement-name">{{ achievement.name }}</h4>
          <p class="achievement-desc">{{ achievement.description }}</p>
          <div class="achievement-progress" v-if="!achievement.unlocked">
            <div class="progress-track">
              <div
                class="progress-fill-small"
                :style="{ width: (achievement.current / achievement.target * 100) + '%' }"
              ></div>
            </div>
            <span class="progress-text">{{ achievement.current }}/{{ achievement.target }}</span>
          </div>
        </div>
        <div class="achievement-reward" v-if="achievement.unlocked">
          <span class="reward-points">+{{ achievement.points }}</span>
        </div>
        <div class="new-badge" v-if="achievement.isNew">NEW</div>
      </div>
    </div>
    
    <!-- 成就详情弹窗 -->
    <teleport to="body">
      <div v-if="selectedAchievement" class="achievement-modal" @click.self="closeDetail">
        <div class="modal-content">
          <button class="close-btn" @click="closeDetail">✕</button>
          <div class="modal-icon">{{ selectedAchievement.icon }}</div>
          <h3>{{ selectedAchievement.name }}</h3>
          <p class="modal-desc">{{ selectedAchievement.description }}</p>
          <div class="modal-status" :class="{ unlocked: selectedAchievement.unlocked }">
            <span v-if="selectedAchievement.unlocked">✓ 已解锁</span>
            <span v-else>🔒 未解锁</span>
          </div>
          <div class="modal-date" v-if="selectedAchievement.unlockedAt">
            解锁时间: {{ formatDate(selectedAchievement.unlockedAt) }}
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  userAchievements: {
    type: Array,
    default: () => [],
  },
})

const activeCategory = ref('all')
const selectedAchievement = ref(null)

const categories = [
  { key: 'all', name: '全部', icon: '🏆' },
  { key: 'usage', name: '使用', icon: '📱' },
  { key: 'explore', name: '探索', icon: '🗺️' },
  { key: 'social', name: '社交', icon: '👥' },
  { key: 'special', name: '特殊', icon: '✨' },
]

// 示例成就数据
const achievements = ref([
  {
    id: 1,
    name: '初次见面',
    description: '完成首次八字排盘',
    icon: '🎯',
    category: 'usage',
    points: 10,
    unlocked: true,
    unlockedAt: '2024-01-15',
  },
  {
    id: 2,
    name: '命理探索者',
    description: '累计完成10次八字排盘',
    icon: '🔮',
    category: 'explore',
    points: 30,
    unlocked: true,
    unlockedAt: '2024-02-01',
  },
  {
    id: 3,
    name: '塔罗大师',
    description: '累计完成50次塔罗占卜',
    icon: '🎴',
    category: 'explore',
    points: 50,
    unlocked: false,
    current: 23,
    target: 50,
  },
  {
    id: 4,
    name: '持之以恒',
    description: '连续签到7天',
    icon: '📅',
    category: 'usage',
    points: 20,
    unlocked: true,
    unlockedAt: '2024-01-22',
  },
  {
    id: 5,
    name: '社交达人',
    description: '成功邀请3位好友',
    icon: '👥',
    category: 'social',
    points: 50,
    unlocked: false,
    current: 1,
    target: 3,
  },
  {
    id: 6,
    name: '分享之星',
    description: '分享运势到朋友圈5次',
    icon: '📤',
    category: 'social',
    points: 30,
    unlocked: false,
    current: 2,
    target: 5,
  },
  {
    id: 7,
    name: '夜猫子',
    description: '在凌晨0-5点进行占卜',
    icon: '🌙',
    category: 'special',
    points: 20,
    unlocked: false,
    current: 0,
    target: 1,
  },
  {
    id: 8,
    name: '完美一周',
    description: '连续7天运势评分都在8分以上',
    icon: '⭐',
    category: 'special',
    points: 100,
    unlocked: false,
    current: 3,
    target: 7,
  },
])

const filteredAchievements = computed(() => {
  if (activeCategory.value === 'all') {
    return achievements.value
  }
  return achievements.value.filter(a => a.category === activeCategory.value)
})

const completedCount = computed(() => {
  return achievements.value.filter(a => a.unlocked).length
})

const progressPercent = computed(() => {
  return (completedCount.value / achievements.value.length) * 100
})

const showDetail = (achievement) => {
  selectedAchievement.value = achievement
}

const closeDetail = () => {
  selectedAchievement.value = null
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('zh-CN')
}
</script>

<style scoped>
.achievement-system {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.achievement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.title {
  color: #fff;
  font-size: 18px;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.icon {
  font-size: 24px;
}

.progress-info {
  color: rgba(255, 255, 255, 0.6);
}

.completed {
  color: #e94560;
  font-size: 24px;
  font-weight: bold;
}

.total {
  font-size: 16px;
}

.progress-bar {
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 20px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #e94560, #ff6b6b);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.achievement-categories {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.category-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.category-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.category-btn.active {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  border-color: transparent;
  color: #fff;
}

.cat-icon {
  font-size: 16px;
}

.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
}

.achievement-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.achievement-card:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-2px);
}

.achievement-card.unlocked {
  background: rgba(82, 196, 26, 0.1);
  border-color: rgba(82, 196, 26, 0.3);
}

.achievement-card.locked {
  opacity: 0.6;
}

.achievement-card.new {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(233, 69, 96, 0.4); }
  50% { box-shadow: 0 0 0 8px rgba(233, 69, 96, 0); }
}

.achievement-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.unlocked .achievement-icon {
  background: linear-gradient(135deg, #52c41a, #73d13d);
}

.locked-icon {
  font-size: 20px;
  opacity: 0.5;
}

.achievement-info {
  flex: 1;
  min-width: 0;
}

.achievement-name {
  color: #fff;
  font-size: 15px;
  margin: 0 0 4px 0;
}

.achievement-desc {
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.achievement-progress {
  margin-top: 8px;
}

.progress-track {
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill-small {
  height: 100%;
  background: linear-gradient(90deg, #e94560, #ff6b6b);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
  margin-top: 4px;
  display: block;
}

.achievement-reward {
  background: linear-gradient(135deg, #faad14, #ffc53d);
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.reward-points {
  color: #fff;
}

.new-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: #e94560;
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: bold;
}

/* Modal Styles */
.achievement-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: linear-gradient(135deg, #1a1a2e, #16213e);
  border-radius: 20px;
  padding: 40px;
  max-width: 400px;
  width: 100%;
  text-align: center;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.close-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  font-size: 20px;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.modal-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.modal-content h3 {
  color: #fff;
  font-size: 22px;
  margin: 0 0 12px 0;
}

.modal-desc {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  margin-bottom: 24px;
  line-height: 1.6;
}

.modal-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.modal-status.unlocked {
  background: rgba(82, 196, 26, 0.2);
  color: #52c41a;
}

.modal-date {
  margin-top: 16px;
  color: rgba(255, 255, 255, 0.4);
  font-size: 13px;
}

@media (max-width: 768px) {
  .achievements-grid {
    grid-template-columns: 1fr;
  }
}
</style>
