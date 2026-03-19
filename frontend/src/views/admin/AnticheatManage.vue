<template>
  <div class="anticheat-manage">
    <el-tabs v-model="activeTab" @tab-change="handleTabChange">

      <!-- 风险事件 -->
      <el-tab-pane label="🚨 风险事件" name="events">
        <div class="tab-toolbar">
          <el-select v-model="evtQuery.status" placeholder="处理状态" clearable style="width:120px" @change="loadEvents">
            <el-option label="待处理" value="pending" />
            <el-option label="已处理" value="handled" />
            <el-option label="已忽略" value="ignored" />
          </el-select>
          <el-select v-model="evtQuery.risk_level" placeholder="风险等级" clearable style="width:120px" @change="loadEvents">
            <el-option label="高风险" value="high" />
            <el-option label="中风险" value="medium" />
            <el-option label="低风险" value="low" />
          </el-select>
          <el-date-picker v-model="evtQuery.dateRange" type="daterange" range-separator="至"
            start-placeholder="开始" end-placeholder="结束" style="width:260px" @change="loadEvents" />
          <el-button :icon="Search" @click="loadEvents">搜索</el-button>
          <el-button :icon="Refresh" @click="resetEvt">重置</el-button>
        </div>
        <el-table :data="eventList" v-loading="evtLoading" stripe border @row-click="viewEventDetail">
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="user_id" label="用户ID" width="90" />
          <el-table-column prop="risk_type" label="风险类型" width="140">
            <template #default="{ row }">
              <el-tag size="small" :type="riskTypeColor(row.risk_type)">{{ row.risk_type }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="risk_level" label="风险等级" width="100">
            <template #default="{ row }">
              <el-tag size="small" :type="levelColor(row.risk_level)">
                {{ levelText(row.risk_level) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
          <el-table-column prop="ip" label="IP地址" width="130" />
          <el-table-column prop="status" label="状态" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="statusColor(row.status)">{{ statusText(row.status) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="发生时间" width="155" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button size="small" @click.stop="viewEventDetail(row)">详情</el-button>
              <el-button v-if="row.status === 'pending'" size="small" type="warning"
                @click.stop="handleEvent(row, 'handle')">处理</el-button>
              <el-button v-if="row.status === 'pending'" size="small" type="info"
                @click.stop="handleEvent(row, 'ignore')">忽略</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="evtQuery.page" v-model:page-size="evtQuery.pageSize"
          :total="evtTotal" layout="total,sizes,prev,pager,next" @change="loadEvents"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- 风险规则 -->
      <el-tab-pane label="📋 风险规则" name="rules">
        <div class="tab-toolbar">
          <el-button type="primary" :icon="Plus" @click="openRuleDialog()">新增规则</el-button>
        </div>
        <el-table :data="ruleList" v-loading="ruleLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="name" label="规则名称" min-width="160" />
          <el-table-column prop="type" label="风险类型" width="140">
            <template #default="{ row }">
              <el-tag size="small" :type="riskTypeColor(row.type)">{{ row.type }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="threshold" label="触发阈值" width="110" />
          <el-table-column prop="time_window" label="时间窗口(分)" width="130" />
          <el-table-column prop="action" label="触发动作" width="120">
            <template #default="{ row }">
              <el-tag size="small" :type="actionColor(row.action)">{{ actionText(row.action) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="is_active" label="状态" width="80">
            <template #default="{ row }">
              <el-switch v-model="row.is_active" :active-value="1" :inactive-value="0"
                @change="toggleRule(row)" />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openRuleDialog(row)">编辑</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteRule(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- 设备指纹 -->
      <el-tab-pane label="📱 设备管理" name="devices">
        <div class="tab-toolbar">
          <el-input v-model="devQuery.keyword" placeholder="搜索设备ID/IP" clearable style="width:220px" @change="loadDevices" />
          <el-select v-model="devQuery.status" placeholder="封禁状态" clearable style="width:120px" @change="loadDevices">
            <el-option label="正常" value="normal" />
            <el-option label="已封禁" value="blocked" />
          </el-select>
          <el-button :icon="Search" @click="loadDevices">搜索</el-button>
          <el-button :icon="Refresh" @click="resetDev">重置</el-button>
        </div>
        <el-table :data="deviceList" v-loading="devLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="fingerprint" label="设备指纹" width="200" show-overflow-tooltip>
            <template #default="{ row }">
              <code class="fp-code">{{ row.fingerprint }}</code>
            </template>
          </el-table-column>
          <el-table-column prop="ip" label="最近IP" width="130" />
          <el-table-column prop="user_count" label="关联账号数" width="110" />
          <el-table-column prop="risk_score" label="风险分" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="row.risk_score >= 80 ? 'danger' : row.risk_score >= 50 ? 'warning' : 'success'">
                {{ row.risk_score }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="90">
            <template #default="{ row }">
              <el-tag size="small" :type="row.status === 'blocked' ? 'danger' : 'success'">
                {{ row.status === 'blocked' ? '已封禁' : '正常' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="last_seen_at" label="最后活跃" width="155" />
          <el-table-column label="操作" width="130" fixed="right">
            <template #default="{ row }">
              <el-button v-if="row.status !== 'blocked'" size="small" type="danger"
                @click="blockDev(row, 'block')">封禁</el-button>
              <el-button v-else size="small" type="success" @click="blockDev(row, 'unblock')">解封</el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="devQuery.page" v-model:page-size="devQuery.pageSize"
          :total="devTotal" layout="total,sizes,prev,pager,next" @change="loadDevices"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

    </el-tabs>

    <!-- 风险事件详情 -->
    <el-dialog v-model="eventDetailVisible" title="风险事件详情" width="600px" destroy-on-close>
      <template v-if="currentEvent">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="事件ID">{{ currentEvent.id }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentEvent.user_id }}</el-descriptions-item>
          <el-descriptions-item label="风险类型">
            <el-tag size="small" :type="riskTypeColor(currentEvent.risk_type)">{{ currentEvent.risk_type }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="风险等级">
            <el-tag size="small" :type="levelColor(currentEvent.risk_level)">{{ levelText(currentEvent.risk_level) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="IP地址">{{ currentEvent.ip }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag size="small" :type="statusColor(currentEvent.status)">{{ statusText(currentEvent.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="发生时间" :span="2">{{ currentEvent.created_at }}</el-descriptions-item>
          <el-descriptions-item label="描述" :span="2">{{ currentEvent.description }}</el-descriptions-item>
        </el-descriptions>
        <div v-if="currentEvent.raw_data" style="margin-top:12px">
          <div class="detail-label">原始数据</div>
          <pre class="raw-data">{{ JSON.stringify(currentEvent.raw_data, null, 2) }}</pre>
        </div>
        <div v-if="currentEvent.status === 'pending'" style="margin-top:16px;display:flex;gap:8px">
          <el-input v-model="handleRemark" placeholder="处理备注（可选）" style="flex:1" />
          <el-button type="warning" :loading="handling" @click="doHandle('handle')">标记处理</el-button>
          <el-button type="info" :loading="handling" @click="doHandle('ignore')">忽略</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 规则编辑弹窗 -->
    <el-dialog v-model="ruleDialogVisible" :title="ruleForm.id ? '编辑规则' : '新增规则'" width="500px" destroy-on-close>
      <el-form :model="ruleForm" :rules="ruleRules" ref="ruleFormRef" label-width="110px">
        <el-form-item label="规则名称" prop="name">
          <el-input v-model="ruleForm.name" />
        </el-form-item>
        <el-form-item label="风险类型" prop="type">
          <el-select v-model="ruleForm.type" style="width:100%">
            <el-option label="频繁请求" value="frequent_request" />
            <el-option label="批量注册" value="batch_register" />
            <el-option label="积分刷取" value="points_abuse" />
            <el-option label="异常设备" value="abnormal_device" />
            <el-option label="IP欺诈" value="ip_fraud" />
          </el-select>
        </el-form-item>
        <el-form-item label="触发阈值" prop="threshold">
          <el-input-number v-model="ruleForm.threshold" :min="1" style="width:100%" />
          <div class="form-tip">时间窗口内超过此次数触发</div>
        </el-form-item>
        <el-form-item label="时间窗口(分)" prop="time_window">
          <el-input-number v-model="ruleForm.time_window" :min="1" style="width:100%" />
        </el-form-item>
        <el-form-item label="触发动作" prop="action">
          <el-select v-model="ruleForm.action" style="width:100%">
            <el-option label="记录日志" value="log" />
            <el-option label="发送告警" value="alert" />
            <el-option label="临时封禁" value="temp_ban" />
            <el-option label="永久封禁" value="perm_ban" />
          </el-select>
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="ruleForm.is_active" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="ruleDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="ruleSaving" @click="saveRule">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import {
  getRiskEvents, getRiskEventDetail, handleRiskEvent,
  getRiskRules, saveRiskRule, updateRiskRule, deleteRiskRule,
  getDeviceFingerprints, blockDevice
} from '@/api/admin'

const activeTab = ref('events')

// ── 辅助函数 ─────────────────────────────────────────
const levelColor = (l) => ({ high: 'danger', medium: 'warning', low: 'info' }[l] || 'info')
const levelText = (l) => ({ high: '高风险', medium: '中风险', low: '低风险' }[l] || l)
const statusColor = (s) => ({ pending: 'warning', handled: 'success', ignored: 'info' }[s] || 'info')
const statusText = (s) => ({ pending: '待处理', handled: '已处理', ignored: '已忽略' }[s] || s)
const riskTypeColor = (t) => ['batch_register', 'ip_fraud'].includes(t) ? 'danger' : 'warning'
const actionColor = (a) => ({ log: 'info', alert: 'warning', temp_ban: 'danger', perm_ban: 'danger' }[a] || 'info')
const actionText = (a) => ({ log: '记录日志', alert: '发送告警', temp_ban: '临时封禁', perm_ban: '永久封禁' }[a] || a)

// ── 风险事件 ─────────────────────────────────────────
const eventList = ref([])
const evtTotal = ref(0)
const evtLoading = ref(false)
const evtQuery = reactive({ page: 1, pageSize: 20, status: '', risk_level: '', dateRange: null })
const eventDetailVisible = ref(false)
const currentEvent = ref(null)
const handleRemark = ref('')
const handling = ref(false)

const loadEvents = async () => {
  evtLoading.value = true
  try {
    const params = { page: evtQuery.page, pageSize: evtQuery.pageSize, status: evtQuery.status, risk_level: evtQuery.risk_level }
    if (evtQuery.dateRange?.length === 2) { params.start = evtQuery.dateRange[0]; params.end = evtQuery.dateRange[1] }
    const res = await getRiskEvents(params)
    eventList.value = res.data?.list || res.data || []
    evtTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载失败') } finally { evtLoading.value = false }
}
const resetEvt = () => { Object.assign(evtQuery, { page: 1, status: '', risk_level: '', dateRange: null }); loadEvents() }

const viewEventDetail = async (row) => {
  try {
    const res = await getRiskEventDetail(row.id)
    currentEvent.value = res.data || row
    handleRemark.value = ''
    eventDetailVisible.value = true
  } catch { currentEvent.value = row; eventDetailVisible.value = true }
}

const handleEvent = (row, action) => {
  currentEvent.value = row
  if (action === 'ignore') doHandle('ignore', row)
  else { eventDetailVisible.value = true; handleRemark.value = '' }
}

const doHandle = async (action, row = null) => {
  const target = row || currentEvent.value
  handling.value = true
  try {
    await handleRiskEvent(target.id, { action, remark: handleRemark.value })
    ElMessage.success(action === 'handle' ? '已标记处理' : '已忽略')
    eventDetailVisible.value = false
    loadEvents()
  } catch { ElMessage.error('操作失败') } finally { handling.value = false }
}

// ── 风险规则 ─────────────────────────────────────────
const ruleList = ref([])
const ruleLoading = ref(false)
const ruleDialogVisible = ref(false)
const ruleSaving = ref(false)
const ruleFormRef = ref(null)
const ruleForm = reactive({ id: null, name: '', type: 'frequent_request', threshold: 10, time_window: 60, action: 'log', is_active: 1 })
const ruleRules = {
  name: [{ required: true, message: '请输入规则名称' }],
  type: [{ required: true }],
  threshold: [{ required: true }],
  time_window: [{ required: true }],
  action: [{ required: true }],
}

const loadRules = async () => {
  ruleLoading.value = true
  try {
    const res = await getRiskRules()
    ruleList.value = res.data || []
  } catch { ElMessage.error('加载规则失败') } finally { ruleLoading.value = false }
}

const openRuleDialog = (row = null) => {
  if (row) Object.assign(ruleForm, { id: row.id, name: row.name, type: row.type, threshold: row.threshold, time_window: row.time_window, action: row.action, is_active: row.is_active })
  else Object.assign(ruleForm, { id: null, name: '', type: 'frequent_request', threshold: 10, time_window: 60, action: 'log', is_active: 1 })
  ruleDialogVisible.value = true
}

const saveRule = async () => {
  await ruleFormRef.value.validate()
  ruleSaving.value = true
  try {
    if (ruleForm.id) await updateRiskRule(ruleForm.id, { ...ruleForm })
    else await saveRiskRule({ ...ruleForm })
    ElMessage.success('保存成功')
    ruleDialogVisible.value = false
    loadRules()
  } catch { ElMessage.error('保存失败') } finally { ruleSaving.value = false }
}

const deleteRule = async (id) => {
  try {
    await deleteRiskRule(id)
    ElMessage.success('删除成功')
    loadRules()
  } catch { ElMessage.error('删除失败') }
}

const toggleRule = async (row) => {
  try {
    await updateRiskRule(row.id, { is_active: row.is_active })
    ElMessage.success(row.is_active ? '已启用' : '已停用')
  } catch { ElMessage.error('操作失败') }
}

// ── 设备指纹 ─────────────────────────────────────────
const deviceList = ref([])
const devTotal = ref(0)
const devLoading = ref(false)
const devQuery = reactive({ page: 1, pageSize: 20, keyword: '', status: '' })

const loadDevices = async () => {
  devLoading.value = true
  try {
    const res = await getDeviceFingerprints({ ...devQuery })
    deviceList.value = res.data?.list || res.data || []
    devTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载失败') } finally { devLoading.value = false }
}
const resetDev = () => { Object.assign(devQuery, { page: 1, keyword: '', status: '' }); loadDevices() }

const blockDev = async (row, action) => {
  try {
    await blockDevice(row.id, { action })
    ElMessage.success(action === 'block' ? '封禁成功' : '已解封')
    loadDevices()
  } catch { ElMessage.error('操作失败') }
}

const handleTabChange = (tab) => {
  if (tab === 'rules' && !ruleList.value.length) loadRules()
  if (tab === 'devices' && !deviceList.value.length) loadDevices()
}

onMounted(() => { loadEvents() })
</script>

<style scoped>
.anticheat-manage { padding: 0; }
.tab-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.fp-code { font-family: monospace; font-size: 12px; color: #606266; }
.detail-label { font-size: 13px; font-weight: 600; color: #606266; margin-bottom: 6px; }
.raw-data { background: #1e1e1e; color: #d4d4d4; padding: 12px; border-radius: 6px; font-size: 12px; max-height: 300px; overflow: auto; white-space: pre-wrap; }
.form-tip { font-size: 12px; color: #c0c4cc; margin-top: 4px; }
</style>
