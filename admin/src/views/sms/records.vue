<template>
  <div class="app-container">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">今日发送</div>
            <div class="stats-value">{{ stats.today_count || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">总发送次数</div>
            <div class="stats-value">{{ stats.total_count || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">验证成功</div>
            <div class="stats-value text-success">{{ stats.success_count || 0 }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <div class="stats-item">
            <div class="stats-label">成功率</div>
            <div class="stats-value" :class="stats.success_rate >= 80 ? 'text-success' : 'text-warning'">
              {{ stats.success_rate || 0 }}%
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索表单 -->
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="手机号">
          <el-input v-model="queryForm.phone" placeholder="请输入手机号" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable>
            <el-option label="注册" value="register" />
            <el-option label="登录" value="login" />
            <el-option label="重置密码" value="reset" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 记录列表 -->
    <el-card shadow="never">
      <el-table v-loading="loading" :data="recordList" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="phone" label="手机号" width="120" />
        <el-table-column prop="code" label="验证码" width="100">
          <template #default="{ row }">
            <span class="code-text">{{ row.code }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeType(row.type)">
              {{ getTypeText(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_used" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_used ? 'success' : 'warning'">
              {{ row.is_used ? '已使用' : '未使用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="120" />
        <el-table-column prop="expire_time" label="过期时间" width="160" />
        <el-table-column prop="created_at" label="发送时间" width="160" />
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.limit"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { getSmsRecords, getSmsStats } from '@/api/sms'

const loading = ref(false)
const recordList = ref([])
const total = ref(0)
const stats = reactive({
  today_count: 0,
  total_count: 0,
  success_count: 0,
  success_rate: 0
})

const queryForm = reactive({
  phone: '',
  type: '',
  page: 1,
  limit: 20
})

onMounted(() => {
  loadRecords()
  loadStats()
})

function getTypeType(type) {
  const map = {
    register: 'primary',
    login: 'success',
    reset: 'warning'
  }
  return map[type] || 'info'
}

function getTypeText(type) {
  const map = {
    register: '注册',
    login: '登录',
    reset: '重置密码'
  }
  return map[type] || type
}

async function loadRecords() {
  loading.value = true
  try {
    const { data } = await getSmsRecords(queryForm)
    recordList.value = data.list || []
    total.value = data.total || 0
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const { data } = await getSmsStats()
    if (data) {
      Object.assign(stats, data)
    }
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

function handleSearch() {
  queryForm.page = 1
  loadRecords()
}

function handleReset() {
  Object.assign(queryForm, {
    phone: '',
    type: '',
    page: 1,
    limit: 20
  })
  loadRecords()
}

function handleSizeChange(val) {
  queryForm.limit = val
  loadRecords()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadRecords()
}
</script>

<style lang="scss" scoped>
.stats-row {
  margin-bottom: 20px;
}

.stats-item {
  text-align: center;
  padding: 10px 0;
}

.stats-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 10px;
}

.stats-value {
  font-size: 24px;
  font-weight: bold;
  color: #303133;
}

.text-success {
  color: #67c23a;
}

.text-warning {
  color: #e6a23c;
}

.search-form {
  margin-bottom: 20px;
}

.code-text {
  font-family: monospace;
  font-weight: bold;
  color: #e6a23c;
  letter-spacing: 2px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
