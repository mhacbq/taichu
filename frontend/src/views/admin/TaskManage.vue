<template>
  <div class="task-manage">
    <el-row :gutter="16">
      <!-- 左侧任务列表 -->
      <el-col :span="15">
        <el-card shadow="never">
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>定时任务列表</span>
              <el-button type="primary" :icon="Plus" size="small" @click="openDialog()">新增任务</el-button>
            </div>
          </template>
          <el-table :data="taskList" v-loading="loading" stripe border>
            <el-table-column prop="id" label="ID" width="60" />
            <el-table-column prop="name" label="任务名称" min-width="150" show-overflow-tooltip />
            <el-table-column prop="cron" label="Cron表达式" width="150">
              <template #default="{ row }">
                <el-tooltip :content="cronDesc(row.cron)" placement="top">
                  <code class="cron-code">{{ row.cron }}</code>
                </el-tooltip>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="90">
              <template #default="{ row }">
                <el-switch v-model="row.status" :active-value="1" :inactive-value="0"
                  @change="toggleTask(row)" />
              </template>
            </el-table-column>
            <el-table-column prop="last_run_at" label="上次执行" width="150" />
            <el-table-column prop="last_result" label="上次结果" width="90">
              <template #default="{ row }">
                <el-tag v-if="row.last_result" size="small"
                  :type="row.last_result === 'success' ? 'success' : 'danger'">
                  {{ row.last_result === 'success' ? '成功' : '失败' }}
                </el-tag>
                <span v-else class="text-muted">-</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="160" fixed="right">
              <template #default="{ row }">
                <el-button size="small" type="success" :loading="runningIds.has(row.id)" @click="runTask(row)">立即执行</el-button>
                <el-button size="small" @click="openDialog(row)">编辑</el-button>
                <el-popconfirm title="确定删除?" @confirm="deleteTask(row.id)">
                  <template #reference>
                    <el-button size="small" type="danger">删除</el-button>
                  </template>
                </el-popconfirm>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>

      <!-- 右侧执行日志 -->
      <el-col :span="9">
        <el-card shadow="never">
          <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span>最近执行日志</span>
              <el-button size="small" :icon="Refresh" @click="loadLogs">刷新</el-button>
            </div>
          </template>
          <div class="log-list" v-loading="logsLoading">
            <div v-for="log in taskLogs" :key="log.id" class="log-item" :class="log.result">
              <div class="log-head">
                <span class="log-name">{{ log.task_name }}</span>
                <el-tag size="small" :type="log.result === 'success' ? 'success' : 'danger'">
                  {{ log.result === 'success' ? '✓ 成功' : '✗ 失败' }}
                </el-tag>
              </div>
              <div class="log-meta">
                <span>{{ log.created_at }}</span>
                <span>耗时 {{ log.duration_ms }}ms</span>
              </div>
              <div v-if="log.result === 'failed' && log.error" class="log-error">{{ log.error }}</div>
            </div>
            <el-empty v-if="!taskLogs.length && !logsLoading" description="暂无执行记录" :image-size="60" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑任务' : '新增任务'" width="520px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="110px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="form.name" placeholder="如：每日积分清理" />
        </el-form-item>
        <el-form-item label="脚本/命令" prop="script">
          <el-input v-model="form.script" placeholder="如：artisan:clean-points 或完整命令" />
        </el-form-item>
        <el-form-item label="Cron表达式" prop="cron">
          <el-input v-model="form.cron" placeholder="如：0 2 * * *（每天凌晨2点）" />
          <div class="form-tip">{{ cronDesc(form.cron) }}</div>
        </el-form-item>
        <el-form-item label="超时(秒)">
          <el-input-number v-model="form.timeout" :min="10" :max="3600" style="width:100%" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveTask">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import { getTaskList, saveTaskItem, deleteTaskItem, runTaskNow, getTaskLogs } from '@/api/admin'

const taskList = ref([])
const loading = ref(false)
const taskLogs = ref([])
const logsLoading = ref(false)
const runningIds = ref(new Set())
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)
const form = reactive({ id: null, name: '', script: '', cron: '', timeout: 60, description: '', status: 1 })
const rules = {
  name: [{ required: true, message: '请输入任务名称' }],
  script: [{ required: true, message: '请输入脚本/命令' }],
  cron: [{ required: true, message: '请输入Cron表达式' }],
}

const cronDesc = (cron) => {
  if (!cron) return ''
  const presets = {
    '* * * * *': '每分钟',
    '0 * * * *': '每小时整点',
    '0 0 * * *': '每天 00:00',
    '0 2 * * *': '每天 02:00',
    '0 0 * * 1': '每周一 00:00',
    '0 0 1 * *': '每月1日 00:00',
  }
  return presets[cron] || '自定义周期'
}

const loadTasks = async () => {
  loading.value = true
  try {
    const res = await getTaskList()
    taskList.value = res.data || []
  } catch { ElMessage.error('加载失败') } finally { loading.value = false }
}

const loadLogs = async () => {
  logsLoading.value = true
  try {
    const res = await getTaskLogs({ pageSize: 30 })
    taskLogs.value = res.data?.list || res.data || []
  } catch {} finally { logsLoading.value = false }
}

const openDialog = (row = null) => {
  if (row) Object.assign(form, { id: row.id, name: row.name, script: row.script, cron: row.cron, timeout: row.timeout || 60, description: row.description || '', status: row.status })
  else Object.assign(form, { id: null, name: '', script: '', cron: '', timeout: 60, description: '', status: 1 })
  dialogVisible.value = true
}

const saveTask = async () => {
  await formRef.value.validate()
  saving.value = true
  try {
    await saveTaskItem({ ...form })
    ElMessage.success('保存成功')
    dialogVisible.value = false
    loadTasks()
  } catch { ElMessage.error('保存失败') } finally { saving.value = false }
}

const deleteTask = async (id) => {
  try {
    await deleteTaskItem(id)
    ElMessage.success('删除成功')
    loadTasks()
  } catch { ElMessage.error('删除失败') }
}

const runTask = async (row) => {
  runningIds.value.add(row.id)
  try {
    await runTaskNow(row.id)
    ElMessage.success(`任务 "${row.name}" 已触发执行`)
    setTimeout(() => { loadLogs(); loadTasks() }, 2000)
  } catch { ElMessage.error('触发失败') } finally {
    runningIds.value.delete(row.id)
  }
}

const toggleTask = async (row) => {
  try {
    await saveTaskItem({ id: row.id, status: row.status })
    ElMessage.success(row.status ? '已启用' : '已停用')
  } catch { ElMessage.error('操作失败') }
}

// 定时刷新日志
let timer = null
onMounted(() => { loadTasks(); loadLogs(); timer = setInterval(loadLogs, 30000) })
onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
.task-manage { padding: 0; }
.cron-code { background: #f0f2f5; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-family: monospace; }
.log-list { max-height: 520px; overflow-y: auto; }
.log-item { padding: 10px 12px; border-radius: 6px; margin-bottom: 8px; background: #f9f9f9; border-left: 3px solid #dcdfe6; }
.log-item.success { border-left-color: #67c23a; }
.log-item.failed { border-left-color: #f56c6c; background: #fef0f0; }
.log-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.log-name { font-weight: 500; font-size: 13px; color: #303133; }
.log-meta { display: flex; gap: 12px; font-size: 12px; color: #909399; }
.log-error { margin-top: 6px; font-size: 12px; color: #f56c6c; background: #fff0f0; padding: 4px 8px; border-radius: 4px; }
.form-tip { font-size: 12px; color: #909399; margin-top: 4px; }
.text-muted { color: #c0c4cc; }
</style>
