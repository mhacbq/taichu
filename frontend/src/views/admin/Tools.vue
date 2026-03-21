<template>
  <div class="tools-manage">
    <div class="page-header">
      <h2>系统工具</h2>
    </div>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>缓存管理</span>
          </template>
          <el-form label-width="100px">
            <el-form-item label="缓存状态">
              <el-tag type="success">{{ cacheInfo.status }}</el-tag>
            </el-form-item>
            <el-form-item label="缓存大小">
              <span>{{ cacheInfo.size }} MB</span>
            </el-form-item>
            <el-form-item label="键数量">
              <span>{{ cacheInfo.keys }}</span>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="clearCache">清空缓存</el-button>
              <el-button @click="refreshCacheInfo">刷新</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card>
          <template #header>
            <span>数据库维护</span>
          </template>
          <el-form label-width="100px">
            <el-form-item label="数据库大小">
              <span>{{ dbInfo.size }} MB</span>
            </el-form-item>
            <el-form-item label="表数量">
              <span>{{ dbInfo.tables }}</span>
            </el-form-item>
            <el-form-item label="连接数">
              <span>{{ dbInfo.connections }}</span>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="optimizeDb">优化数据库</el-button>
              <el-button type="warning" @click="backupDb">备份数据库</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card>
          <template #header>
            <span>文件清理</span>
          </template>
          <el-form label-width="100px">
            <el-form-item label="临时文件">
              <span>{{ fileStats.temp }} MB</span>
            </el-form-item>
            <el-form-item label="日志文件">
              <span>{{ fileStats.logs }} MB</span>
            </el-form-item>
            <el-form-item label="缓存文件">
              <span>{{ fileStats.cache }} MB</span>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="cleanFiles('temp')">清理临时文件</el-button>
              <el-button type="warning" @click="cleanFiles('logs')">清理日志文件</el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card>
          <template #header>
            <span>系统信息</span>
          </template>
          <el-descriptions :column="1" border>
            <el-descriptions-item label="系统版本">{{ sysInfo.version }}</el-descriptions-item>
            <el-descriptions-item label="PHP版本">{{ sysInfo.php }}</el-descriptions-item>
            <el-descriptions-item label="MySQL版本">{{ sysInfo.mysql }}</el-descriptions-item>
            <el-descriptions-item label="服务器时间">{{ sysInfo.server_time }}</el-descriptions-item>
            <el-descriptions-item label="运行时间">{{ sysInfo.uptime }}</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const cacheInfo = reactive({
  status: '已连接',
  size: 0,
  keys: 0
})

const dbInfo = reactive({
  size: 0,
  tables: 0,
  connections: 0
})

const fileStats = reactive({
  temp: 0,
  logs: 0,
  cache: 0
})

const sysInfo = reactive({
  version: '',
  php: '',
  mysql: '',
  server_time: '',
  uptime: ''
})

const fetchCacheInfo = async () => {
  try {
    const response = await fetch('/api/admin/tools/cache-info')
    const data = await response.json()
    if (data.code === 200) {
      Object.assign(cacheInfo, data.data)
    }
  } catch (error) {
    ElMessage.error('获取缓存信息失败')
  }
}

const fetchDbInfo = async () => {
  try {
    const response = await fetch('/api/admin/tools/db-info')
    const data = await response.json()
    if (data.code === 200) {
      Object.assign(dbInfo, data.data)
    }
  } catch (error) {
    ElMessage.error('获取数据库信息失败')
  }
}

const fetchFileStats = async () => {
  try {
    const response = await fetch('/api/admin/tools/file-stats')
    const data = await response.json()
    if (data.code === 200) {
      Object.assign(fileStats, data.data)
    }
  } catch (error) {
    ElMessage.error('获取文件统计失败')
  }
}

const fetchSysInfo = async () => {
  try {
    const response = await fetch('/api/admin/tools/sys-info')
    const data = await response.json()
    if (data.code === 200) {
      Object.assign(sysInfo, data.data)
    }
  } catch (error) {
    ElMessage.error('获取系统信息失败')
  }
}

const refreshCacheInfo = () => {
  fetchCacheInfo()
}

const clearCache = async () => {
  try {
    await ElMessageBox.confirm('确定清空所有缓存吗？', '警告', { type: 'warning' })
    
    const response = await fetch('/api/admin/tools/cache-clear', {
      method: 'POST'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('缓存已清空')
      fetchCacheInfo()
    } else {
      ElMessage.error(data.message || '清空失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('清空失败')
  }
}

const optimizeDb = async () => {
  try {
    const response = await fetch('/api/admin/tools/db-optimize', {
      method: 'POST'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success('数据库优化完成')
      fetchDbInfo()
    } else {
      ElMessage.error(data.message || '优化失败')
    }
  } catch (error) {
    ElMessage.error('优化失败')
  }
}

const backupDb = async () => {
  try {
    await ElMessageBox.confirm('确定备份数据库吗？备份文件将保存在服务器上。', '提示', { type: 'info' })
    
    const response = await fetch('/api/admin/tools/db-backup', {
      method: 'POST'
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success(`备份成功：${data.data.file}`)
    } else {
      ElMessage.error(data.message || '备份失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('备份失败')
  }
}

const cleanFiles = async (type) => {
  const typeName = { temp: '临时文件', logs: '日志文件', cache: '缓存文件' }[type]
  
  try {
    await ElMessageBox.confirm(`确定清理${typeName}吗？此操作不可恢复！`, '警告', { type: 'warning' })
    
    const response = await fetch('/api/admin/tools/clean-files', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type })
    })
    const data = await response.json()
    
    if (data.code === 200) {
      ElMessage.success(`${typeName}已清理`)
      fetchFileStats()
    } else {
      ElMessage.error(data.message || '清理失败')
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('清理失败')
  }
}

onMounted(() => {
  fetchCacheInfo()
  fetchDbInfo()
  fetchFileStats()
  fetchSysInfo()
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
.el-card {
  margin-bottom: 20px;
}
</style>
