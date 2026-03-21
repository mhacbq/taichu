<template>
  <div class="anticheat-manage">
    <div class="page-header">
      <h2>反作弊管理</h2>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card>
          <template #header>
            <span>可疑设备列表</span>
          </template>
          <el-form :inline="true" :model="searchForm" class="search-form">
            <el-form-item label="IP地址">
              <el-input v-model="searchForm.ip" placeholder="输入IP地址" clearable />
            </el-form-item>
            <el-form-item label="设备ID">
              <el-input v-model="searchForm.device_id" placeholder="输入设备ID" clearable />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSearch">搜索</el-button>
              <el-button @click="handleReset">重置</el-button>
            </el-form-item>
          </el-form>

          <el-table :data="tableData" v-loading="loading" style="width: 100%">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="ip" label="IP地址" width="140" />
            <el-table-column prop="device_id" label="设备ID" width="200" />
            <el-table-column prop="username" label="用户名" width="120" />
            <el-table-column prop="risk_score" label="风险分数" width="100">
              <template #default="{ row }">
                <el-progress :percentage="row.risk_score" :color="getRiskColor(row.risk_score)" />
              </template>
            </el-table-column>
            <el-table-column prop="reason" label="可疑原因" min-width="200" />
            <el-table-column prop="last_seen" label="最后出现时间" width="160" />
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link size="small" @click="handleView(row)">详情</el-button>
                <el-button type="danger" link size="small" @click="handleBan(row)">封禁</el-button>
              </template>
            </el-table-column>
          </el-table>

          <el-pagination
            v-model:current-page="pagination.page"
            v-model:page-size="pagination.size"
            :total="pagination.total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @current-change="fetchData"
            @size-change="fetchData"
            style="margin-top: 20px; justify-content: flex-end;"
          />
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card>
          <template #header>
            <span>封禁列表</span>
          </template>
          <el-table :data="banList" style="width: 100%">
            <el-table-column prop="ip" label="IP地址" width="120" />
            <el-table-column prop="reason" label="原因" min-width="150" show-overflow-tooltip />
            <el-table-column prop="expire_at" label="到期时间" width="120" />
            <el-table-column label="操作" width="80">
              <template #default="{ row }">
                <el-button type="primary" link size="small" @click="handleUnban(row)">解封</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>

        <el-card style="margin-top: 20px;">
          <template #header>
            <span>添加封禁</span>
          </template>
          <el-form :model="banForm" label-width="80px">
            <el-form-item label="IP地址">
              <el-input v-model="banForm.ip" placeholder="输入IP地址" />
            </el-form-item>
            <el-form-item label="封禁时长">
              <el-select v-model="banForm.duration">
                <el-option label="1小时" value="1h" />
                <el-option label="24小时" value="24h" />
                <el-option label="7天" value="7d" />
                <el-option label="30天" value="30d" />
                <el-option label="永久" value="permanent" />
              </el-select>
            </el-form-item>
            <el-form-item label="原因">
              <el-input v-model="banForm.reason" type="textarea" :rows="2" placeholder="输入封禁原因" />
            </el-form-item>
            <el-form-item>
              <el-button type="danger" @click="handleAddBan">添加封禁</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const loading = ref(false)
const tableData = ref([])
const banList = ref([])

const searchForm = reactive({
  ip: '',
  device_id: ''
})

const banForm = reactive({
  ip: '',
  duration: '24h',
  reason: ''
})

const pagination = reactive({
  page: 1,
  size: 20,
  total: 0
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: pagination.page,
      size: pagination.size,
      ...searchForm
    })
    
    const response = await fetch(`/api/admin/anticheat/devices?${params}`)
    const data = await response.json()
    
    if (data.code === 200) {
      tableData.value = data.data.list || []
      pagination.total = data.data.total || 0
    }
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const fetchBanList = async () => {
  try {
    const response = await fetch('/api/admin/anticheat/bans')
    const data = await response.json()
    
    if (data.code === 200) {
      banList.value = data.data || []
    }
  } catch (error) {
    ElMessage.error('获取封禁列表失败')
  }
}

const getRiskColor = (score) => {
  if (score >= 80) return '#f56c6c'
  if (score >= 50) return '#e6a23c'
  return '#67c23a'
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.ip = ''
  searchForm.device_id = ''
  handleSearch()
}

const handleView = (row) => {
  ElMessage.info('查看详情功能待实现')
}

const handleBan = async (row) => {
  try {
    await ElMessageBox.confirm(`确定封禁IP ${row.ip} 吗？`, '警告', { type: 'warning' })
    
    const response = await fetch('/api/admin/anticheat/ban', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ip: row.ip,
        device_id: row.device_id,
        duration: '24h',
        reason: '可疑设备'
      })
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('封禁成功')
      fetchBanList()
    } else {
      ElMessage.error(data.message || '封禁失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('封禁失败')
  }
}

const handleUnban = async (row) => {
  try {
    await ElMessageBox.confirm(`确定解封IP ${row.ip} 吗？`, '提示', { type: 'info' })
    
    const response = await fetch(`/api/admin/anticheat/unban/${row.id}`, {
      method: 'DELETE'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('解封成功')
      fetchBanList()
    } else {
      ElMessage.error(data.message || '解封失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('解封失败')
  }
}

const handleAddBan = async () => {
  if (!banForm.ip) {
    ElMessage.warning('请输入IP地址')
    return
  }
  
  try {
    const response = await fetch('/api/admin/anticheat/ban', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(banForm)
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('封禁成功')
      banForm.ip = ''
      banForm.reason = ''
      fetchBanList()
    } else {
      ElMessage.error(data.message || '封禁失败')
    }
  } catch (error) {
    ElMessage.error('封禁失败')
  }
}

onMounted(() => {
  fetchData()
  fetchBanList()
})
</script>

<style scoped>
.page-header {
  margin-bottom: 20px;
}
.page-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}
.search-form {
  margin-bottom: 20px;
}
</style>
