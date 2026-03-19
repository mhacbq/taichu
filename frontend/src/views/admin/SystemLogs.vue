<template>
  <div class="system-logs">
    <el-tabs v-model="activeTab" @tab-change="handleTabChange">

      <!-- 操作日志 -->
      <el-tab-pane label="📋 操作日志" name="operation">
        <div class="tab-toolbar">
          <el-input v-model="opQuery.keyword" placeholder="搜索操作/用户" clearable style="width:200px" @change="loadOperation" />
          <el-select v-model="opQuery.module" placeholder="模块" clearable style="width:130px" @change="loadOperation">
            <el-option label="用户管理" value="users" />
            <el-option label="内容管理" value="content" />
            <el-option label="系统配置" value="config" />
            <el-option label="订单管理" value="order" />
          </el-select>
          <el-date-picker v-model="opQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始" end-placeholder="结束" style="width:260px" @change="loadOperation" />
          <el-button :icon="Search" @click="loadOperation">搜索</el-button>
          <el-button :icon="Refresh" @click="resetOp">重置</el-button>
        </div>
        <el-table :data="opList" v-loading="opLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="admin_name" label="操作者" width="110" />
          <el-table-column prop="module" label="模块" width="100">
            <template #default="{ row }">
              <el-tag size="small" type="info">{{ row.module }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="action" label="操作" width="120" />
          <el-table-column prop="description" label="描述" min-width="220" show-overflow-tooltip />
          <el-table-column prop="ip" label="IP地址" width="130" />
          <el-table-column prop="result" label="结果" width="80">
            <template #default="{ row }">
              <el-tag size="small" :type="row.result === 'success' ? 'success' : 'danger'">
                {{ row.result === 'success' ? '成功' : '失败' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="时间" width="155" />
          <el-table-column label="详情" width="70" fixed="right">
            <template #default="{ row }">
              <el-button size="small" @click="viewLogDetail(row, 'operation')">详情</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="opQuery.page" v-model:page-size="opQuery.pageSize"
          :total="opTotal" layout="total,sizes,prev,pager,next" @change="loadOperation"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- API访问日志 -->
      <el-tab-pane label="🌐 API日志" name="api">
        <div class="tab-toolbar">
          <el-input v-model="apiQuery.keyword" placeholder="搜索路径/IP" clearable style="width:200px" @change="loadApi" />
          <el-select v-model="apiQuery.method" placeholder="方法" clearable style="width:100px" @change="loadApi">
            <el-option label="GET" value="GET" />
            <el-option label="POST" value="POST" />
            <el-option label="PUT" value="PUT" />
            <el-option label="DELETE" value="DELETE" />
          </el-select>
          <el-select v-model="apiQuery.status" placeholder="状态码" clearable style="width:110px" @change="loadApi">
            <el-option label="2xx 成功" value="2xx" />
            <el-option label="4xx 客户端错误" value="4xx" />
            <el-option label="5xx 服务端错误" value="5xx" />
          </el-select>
          <el-date-picker v-model="apiQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始" end-placeholder="结束" style="width:260px" @change="loadApi" />
          <el-button :icon="Search" @click="loadApi">搜索</el-button>
          <el-button :icon="Refresh" @click="resetApi">重置</el-button>
        </div>
        <el-table :data="apiList" v-loading="apiLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="method" label="方法" width="80">
            <template #default="{ row }">
              <el-tag size="small" :type="methodTypeMap[row.method] || 'info'">{{ row.method }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="path" label="路径" min-width="220" show-overflow-tooltip />
          <el-table-column prop="status_code" label="状态码" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="row.status_code >= 500 ? 'danger' : row.status_code >= 400 ? 'warning' : 'success'">
                {{ row.status_code }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="duration_ms" label="耗时(ms)" width="100" />
          <el-table-column prop="ip" label="IP" width="130" />
          <el-table-column prop="user_id" label="用户ID" width="80" />
          <el-table-column prop="created_at" label="时间" width="155" />
        </el-table>
        <el-pagination v-model:current-page="apiQuery.page" v-model:page-size="apiQuery.pageSize"
          :total="apiTotal" layout="total,sizes,prev,pager,next" @change="loadApi"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- 登录日志 -->
      <el-tab-pane label="🔐 登录日志" name="login">
        <div class="tab-toolbar">
          <el-input v-model="loginQuery.keyword" placeholder="搜索用户名/IP" clearable style="width:200px" @change="loadLogin" />
          <el-select v-model="loginQuery.status" placeholder="登录状态" clearable style="width:120px" @change="loadLogin">
            <el-option label="成功" value="success" />
            <el-option label="失败" value="failed" />
          </el-select>
          <el-date-picker v-model="loginQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始" end-placeholder="结束" style="width:260px" @change="loadLogin" />
          <el-button :icon="Search" @click="loadLogin">搜索</el-button>
          <el-button :icon="Refresh" @click="resetLogin">重置</el-button>
        </div>
        <el-table :data="loginList" v-loading="loginLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="username" label="用户名" width="140" />
          <el-table-column prop="user_type" label="类型" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="row.user_type === 'admin' ? 'warning' : 'info'">
                {{ row.user_type === 'admin' ? '管理员' : '用户' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="ip" label="IP地址" width="130" />
          <el-table-column prop="location" label="登录地点" width="150" show-overflow-tooltip />
          <el-table-column prop="device" label="设备" min-width="180" show-overflow-tooltip />
          <el-table-column prop="status" label="结果" width="80">
            <template #default="{ row }">
              <el-tag size="small" :type="row.status === 'success' ? 'success' : 'danger'">
                {{ row.status === 'success' ? '成功' : '失败' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="fail_reason" label="失败原因" width="130" show-overflow-tooltip />
          <el-table-column prop="created_at" label="时间" width="155" />
        </el-table>
        <el-pagination v-model:current-page="loginQuery.page" v-model:page-size="loginQuery.pageSize"
          :total="loginTotal" layout="total,sizes,prev,pager,next" @change="loadLogin"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

    </el-tabs>

    <!-- 日志详情弹窗 -->
    <el-dialog v-model="detailVisible" title="日志详情" width="640px" destroy-on-close>
      <pre class="log-detail-json" v-if="detailData">{{ JSON.stringify(detailData, null, 2) }}</pre>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'
import { getOperationLogs, getApiLogs, getLoginLogs } from '@/api/admin'

const activeTab = ref('operation')

const methodTypeMap = { GET: 'info', POST: 'success', PUT: 'warning', DELETE: 'danger' }

// 详情弹窗
const detailVisible = ref(false)
const detailData = ref(null)
const viewLogDetail = (row) => { detailData.value = row; detailVisible.value = true }

// ── 操作日志 ─────────────────────────────────────────
const opList = ref([])
const opTotal = ref(0)
const opLoading = ref(false)
const opQuery = reactive({ page: 1, pageSize: 20, keyword: '', module: '', dateRange: null })

const loadOperation = async () => {
  opLoading.value = true
  try {
    const params = { page: opQuery.page, pageSize: opQuery.pageSize, keyword: opQuery.keyword, module: opQuery.module }
    if (opQuery.dateRange?.length === 2) { params.start = opQuery.dateRange[0]; params.end = opQuery.dateRange[1] }
    const res = await getOperationLogs(params)
    opList.value = res.data?.list || res.data || []
    opTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载操作日志失败') } finally { opLoading.value = false }
}
const resetOp = () => { Object.assign(opQuery, { page: 1, keyword: '', module: '', dateRange: null }); loadOperation() }

// ── API日志 ───────────────────────────────────────────
const apiList = ref([])
const apiTotal = ref(0)
const apiLoading = ref(false)
const apiQuery = reactive({ page: 1, pageSize: 20, keyword: '', method: '', status: '', dateRange: null })

const loadApi = async () => {
  apiLoading.value = true
  try {
    const params = { page: apiQuery.page, pageSize: apiQuery.pageSize, keyword: apiQuery.keyword, method: apiQuery.method, status: apiQuery.status }
    if (apiQuery.dateRange?.length === 2) { params.start = apiQuery.dateRange[0]; params.end = apiQuery.dateRange[1] }
    const res = await getApiLogs(params)
    apiList.value = res.data?.list || res.data || []
    apiTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载API日志失败') } finally { apiLoading.value = false }
}
const resetApi = () => { Object.assign(apiQuery, { page: 1, keyword: '', method: '', status: '', dateRange: null }); loadApi() }

// ── 登录日志 ─────────────────────────────────────────
const loginList = ref([])
const loginTotal = ref(0)
const loginLoading = ref(false)
const loginQuery = reactive({ page: 1, pageSize: 20, keyword: '', status: '', dateRange: null })

const loadLogin = async () => {
  loginLoading.value = true
  try {
    const params = { page: loginQuery.page, pageSize: loginQuery.pageSize, keyword: loginQuery.keyword, status: loginQuery.status }
    if (loginQuery.dateRange?.length === 2) { params.start = loginQuery.dateRange[0]; params.end = loginQuery.dateRange[1] }
    const res = await getLoginLogs(params)
    loginList.value = res.data?.list || res.data || []
    loginTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载登录日志失败') } finally { loginLoading.value = false }
}
const resetLogin = () => { Object.assign(loginQuery, { page: 1, keyword: '', status: '', dateRange: null }); loadLogin() }

const handleTabChange = (tab) => {
  if (tab === 'api' && !apiList.value.length) loadApi()
  if (tab === 'login' && !loginList.value.length) loadLogin()
}

onMounted(() => { loadOperation() })
</script>

<style scoped>
.system-logs { padding: 0; }
.tab-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.log-detail-json { background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 6px; font-size: 12px; max-height: 500px; overflow: auto; white-space: pre-wrap; }
</style>
