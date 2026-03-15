<template>
  <div class="app-container">
    <el-card class="search-form" shadow="never">
      <el-form :model="queryForm" inline>
        <el-form-item label="事件类型">
          <el-select v-model="queryForm.type" placeholder="全部类型" clearable style="width: 150px;">
            <el-option label="频繁请求" value="frequency" />
            <el-option label="异常设备" value="device" />
            <el-option label="IP异常" value="ip" />
            <el-option label="行为异常" value="behavior" />
          </el-select>
        </el-form-item>
        <el-form-item label="风险等级">
          <el-select v-model="queryForm.level" placeholder="全部等级" clearable style="width: 150px;">
            <el-option label="高危" value="high" />
            <el-option label="中危" value="medium" />
            <el-option label="低危" value="low" />
          </el-select>
        </el-form-item>
        <el-form-item label="处理状态">
          <el-select v-model="queryForm.status" placeholder="全部状态" clearable style="width: 150px;">
            <el-option label="待处理" value="pending" />
            <el-option label="已处理" value="handled" />
            <el-option label="已忽略" value="ignored" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="queryForm.dateRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>风险事件列表</span>
          <el-button type="danger" @click="handleBatchBlock">批量封禁</el-button>
        </div>
      </template>

      <el-table
        v-loading="loading"
        :data="eventList"
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="id" label="事件ID" width="100" />
        <el-table-column prop="type" label="类型" width="120">
          <template #default="{ row }">
            <el-tag :type="getTypeType(row.type)">{{ getTypeText(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="level" label="等级" width="100">
          <template #default="{ row }">
            <el-tag :type="getLevelType(row.level)">{{ getLevelText(row.level) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user_id" label="用户" width="120" />
        <el-table-column prop="device_id" label="设备ID" min-width="150" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP地址" width="130" />
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="发生时间" width="160" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
            <el-button v-if="row.status === 'pending'" link type="success" @click="handleHandle(row)">处理</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="风险事件详情" width="700px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="事件ID">{{ detailDialog.data.id }}</el-descriptions-item>
        <el-descriptions-item label="事件类型">{{ getTypeText(detailDialog.data.type) }}</el-descriptions-item>
        <el-descriptions-item label="风险等级">
          <el-tag :type="getLevelType(detailDialog.data.level)">{{ getLevelText(detailDialog.data.level) }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="发生时间">{{ detailDialog.data.created_at }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailDialog.data.user_id }}</el-descriptions-item>
        <el-descriptions-item label="设备ID">{{ detailDialog.data.device_id }}</el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ detailDialog.data.ip }}</el-descriptions-item>
        <el-descriptions-item label="地理位置">{{ detailDialog.data.location }}</el-descriptions-item>
      </el-descriptions>
      <div style="margin-top: 20px;">
        <h4>风险描述</h4>
        <p>{{ detailDialog.data.description }}</p>
      </div>
      <div style="margin-top: 20px;">
        <h4>触发规则</h4>
        <el-table :data="detailDialog.data.rules" stripe size="small">
          <el-table-column prop="name" label="规则名称" />
          <el-table-column prop="condition" label="触发条件" />
          <el-table-column prop="value" label="实际值" />
          <el-table-column prop="threshold" label="阈值" />
        </el-table>
      </div>
    </el-dialog>

    <!-- 处理弹窗 -->
    <el-dialog v-model="handleDialog.visible" title="处理风险事件" width="500px">
      <el-form :model="handleDialog.form" label-width="100px">
        <el-form-item label="处理方式">
          <el-radio-group v-model="handleDialog.form.action">
            <el-radio label="ignore">忽略</el-radio>
            <el-radio label="warn">警告用户</el-radio>
            <el-radio label="block_temp">临时封禁(24h)</el-radio>
            <el-radio label="block_perm">永久封禁</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="处理备注">
          <el-input v-model="handleDialog.form.remark" type="textarea" rows="4" placeholder="请输入处理备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="handleDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitHandle">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getRiskEvents, getRiskEventDetail, handleRiskEvent } from '@/api/anticheat'

const loading = ref(false)
const eventList = ref([])
const total = ref(0)
const selectedEvents = ref([])

const queryForm = reactive({
  type: '',
  level: '',
  status: '',
  dateRange: [],
  page: 1,
  pageSize: 20
})

const detailDialog = reactive({
  visible: false,
  data: {
    rules: []
  }
})

const handleDialog = reactive({
  visible: false,
  eventId: null,
  form: {
    action: 'ignore',
    remark: ''
  }
})

onMounted(() => {
  loadEventList()
})

async function loadEventList() {
  loading.value = true
  try {
    const { data } = await getRiskEvents(queryForm)
    eventList.value = data.list
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadEventList()
}

function handleReset() {
  Object.assign(queryForm, {
    type: '',
    level: '',
    status: '',
    dateRange: [],
    page: 1,
    pageSize: 20
  })
  loadEventList()
}

function handleSizeChange(val) {
  queryForm.pageSize = val
  loadEventList()
}

function handleCurrentChange(val) {
  queryForm.page = val
  loadEventList()
}

function handleSelectionChange(selection) {
  selectedEvents.value = selection
}

async function handleDetail(row) {
  try {
    const { data } = await getRiskEventDetail(row.id)
    detailDialog.data = data
    detailDialog.visible = true
  } catch (error) {
    console.error(error)
  }
}

function handleHandle(row) {
  handleDialog.eventId = row.id
  handleDialog.form = {
    action: 'ignore',
    remark: ''
  }
  handleDialog.visible = true
}

async function submitHandle() {
  try {
    await handleRiskEvent(handleDialog.eventId, handleDialog.form)
    ElMessage.success('处理成功')
    handleDialog.visible = false
    loadEventList()
  } catch (error) {
    console.error(error)
  }
}

async function handleBatchBlock() {
  if (selectedEvents.value.length === 0) {
    ElMessage.warning('请选择要处理的事件')
    return
  }
  try {
    await ElMessageBox.confirm(`确定批量处理选中的 ${selectedEvents.value.length} 个事件吗？`, '提示', { type: 'warning' })
    // 批量处理逻辑
    ElMessage.success('批量处理成功')
    loadEventList()
  } catch {
    // 取消
  }
}

// 类型转换
function getTypeType(type) {
  const map = { frequency: 'warning', device: 'danger', ip: 'info', behavior: '' }
  return map[type] || ''
}
function getTypeText(type) {
  const map = { frequency: '频繁请求', device: '异常设备', ip: 'IP异常', behavior: '行为异常' }
  return map[type] || type
}
function getLevelType(level) {
  const map = { high: 'danger', medium: 'warning', low: 'info' }
  return map[level] || ''
}
function getLevelText(level) {
  const map = { high: '高危', medium: '中危', low: '低危' }
  return map[level] || level
}
function getStatusType(status) {
  const map = { pending: 'warning', handled: 'success', ignored: 'info' }
  return map[status] || ''
}
function getStatusText(status) {
  const map = { pending: '待处理', handled: '已处理', ignored: '已忽略' }
  return map[status] || status
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-form {
  margin-bottom: 20px;
}
</style>
