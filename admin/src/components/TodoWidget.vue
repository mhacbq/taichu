<template>
  <div class="todo-widget">
    <el-badge :value="totalTodos" :hidden="totalTodos === 0" type="danger">
      <el-button :icon="Bell" circle @click="showTodos" />
    </el-badge>

    <el-drawer v-model="drawerVisible" title="运营待办" size="400px">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="全部" name="all">
          <div v-if="allTodos.length === 0" class="empty-state">
            <el-empty description="暂无待办事项" />
          </div>
          <div v-else class="todo-list">
            <div v-for="todo in allTodos" :key="todo.id" class="todo-item">
              <div class="todo-icon">
                <el-icon :color="getIconColor(todo.type)">
                  <component :is="getIcon(todo.type)" />
                </el-icon>
              </div>
              <div class="todo-content">
                <div class="todo-title">{{ todo.title }}</div>
                <div class="todo-desc">{{ todo.description }}</div>
                <div class="todo-time">{{ todo.time }}</div>
              </div>
              <el-button link type="primary" size="small" @click="handleDismiss(todo)">忽略</el-button>
            </div>
          </div>
        </el-tab-pane>

        <el-tab-pane label="VIP即将到期" name="vip">
          <div v-if="vipTodos.length === 0" class="empty-state">
            <el-empty description="暂无即将到期VIP" />
          </div>
          <div v-else class="todo-list">
            <div v-for="todo in vipTodos" :key="todo.id" class="todo-item">
              <div class="todo-icon">
                <el-icon color="#f56c6c">
                  <Clock />
                </el-icon>
              </div>
              <div class="todo-content">
                <div class="todo-title">{{ todo.title }}</div>
                <div class="todo-desc">{{ todo.description }}</div>
                <div class="todo-time">{{ todo.time }}</div>
              </div>
              <el-button link type="primary" size="small" @click="handleDismiss(todo)">忽略</el-button>
            </div>
          </div>
        </el-tab-pane>

        <el-tab-pane label="长期未活跃" name="inactive">
          <div v-if="inactiveTodos.length === 0" class="empty-state">
            <el-empty description="暂无长期未活跃用户" />
          </div>
          <div v-else class="todo-list">
            <div v-for="todo in inactiveTodos" :key="todo.id" class="todo-item">
              <div class="todo-icon">
                <el-icon color="#e6a23c">
                  <User />
                </el-icon>
              </div>
              <div class="todo-content">
                <div class="todo-title">{{ todo.title }}</div>
                <div class="todo-desc">{{ todo.description }}</div>
                <div class="todo-time">{{ todo.time }}</div>
              </div>
              <el-button link type="primary" size="small" @click="handleDismiss(todo)">忽略</el-button>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-drawer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bell, Clock, User, Warning } from '@element-plus/icons-vue'
import { getTodoList, getVipExpiringUsers, getInactiveUsers, dismissTodo } from '@/api/todo'
import { ElMessage } from 'element-plus'

const drawerVisible = ref(false)
const activeTab = ref('all')
const todos = ref([])

const allTodos = computed(() => todos.value)
const vipTodos = computed(() => todos.value.filter(t => t.type === 'vip'))
const inactiveTodos = computed(() => todos.value.filter(t => t.type === 'inactive'))
const totalTodos = computed(() => todos.value.length)

onMounted(() => {
  loadTodos()
})

async function loadTodos() {
  try {
    const { data } = await getTodoList()
    todos.value = data || []

    const vipData = await getVipExpiringUsers()
    if (vipData.data?.length) {
      vipData.data.forEach(user => {
        todos.value.push({
          id: `vip_${user.id}`,
          type: 'vip',
          title: 'VIP即将到期',
          description: `用户 ${user.username} 的VIP会员将在 ${user.expiry_date} 到期`,
          time: user.expiry_date
        })
      })
    }

    const inactiveData = await getInactiveUsers()
    if (inactiveData.data?.length) {
      inactiveData.data.forEach(user => {
        todos.value.push({
          id: `inactive_${user.id}`,
          type: 'inactive',
          title: '长期未活跃用户',
          description: `用户 ${user.username} 已超过 30 天未登录`,
          time: user.last_login
        })
      })
    }
  } catch (error) {
    console.error('加载待办失败:', error)
  }
}

function showTodos() {
  drawerVisible.value = true
}

async function handleDismiss(todo) {
  try {
    await dismissTodo(todo.id)
    todos.value = todos.value.filter(t => t.id !== todo.id)
    ElMessage.success('已忽略')
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

function getIcon(type) {
  const iconMap = {
    vip: Clock,
    inactive: User,
    warning: Warning
  }
  return iconMap[type] || Warning
}

function getIconColor(type) {
  const colorMap = {
    vip: '#f56c6c',
    inactive: '#e6a23c',
    warning: '#909399'
  }
  return colorMap[type] || '#909399'
}
</script>

<style lang="scss" scoped>
.todo-widget {
  display: inline-block;
}

.todo-list {
  max-height: 500px;
  overflow-y: auto;
}

.todo-item {
  display: flex;
  align-items: flex-start;
  padding: 16px 0;
  border-bottom: 1px solid #ebeef5;

  &:last-child {
    border-bottom: none;
  }
}

.todo-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
  border-radius: 50%;
  margin-right: 12px;
}

.todo-content {
  flex: 1;
  min-width: 0;
}

.todo-title {
  font-weight: 500;
  color: #303133;
  margin-bottom: 4px;
}

.todo-desc {
  font-size: 13px;
  color: #606266;
  margin-bottom: 4px;
  line-height: 1.4;
}

.todo-time {
  font-size: 12px;
  color: #909399;
}

.empty-state {
  padding: 40px 0;
}
</style>
