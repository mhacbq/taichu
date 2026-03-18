<template>
  <div class="app-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>定时任务列表</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>新建任务
          </el-button>
        </div>
      </template>

      <el-table :data="taskList" v-loading="loading" stripe>
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="name" label="任务名称" min-width="150" />
        <el-table-column prop="command" label="执行命令" min-width="200" show-overflow-tooltip />
        <el-table-column prop="cron" label="Cron表达式" width="120">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.cron }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="last_run_time" label="上次执行" width="160" />
        <el-table-column prop="next_run_time" label="下次执行" width="160" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleRun(row)">立即执行</el-button>
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-card style="margin-top: 20px;">
      <template #header>
        <span>脚本管理</span>
      </template>
      <el-table :data="scriptList" stripe>
        <el-table-column prop="name" label="脚本名称" min-width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag size="small">{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="160" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEditScript(row)">编辑</el-button>
            <el-button link type="primary" @click="handleViewLog(row)">日志</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 任务编辑弹窗 -->
    <el-dialog v-model="dialog.visible" :title="dialog.isEdit ? '编辑任务' : '新建任务'" width="600px">
      <el-form :model="dialog.form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="dialog.form.name" placeholder="请输入任务名称" />
        </el-form-item>
        <el-form-item label="执行脚本" prop="script_id">
          <el-select v-model="dialog.form.script_id" placeholder="选择脚本" style="width: 100%;">
            <el-option
              v-for="script in scriptList"
              :key="script.id"
              :label="script.name"
              :value="script.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Cron表达式" prop="cron">
          <el-input v-model="dialog.form.cron" placeholder="例如: 0 0 * * *">
            <template #append>
              <el-button @click="showCronHelp = true">?</el-button>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item label="参数">
          <el-input v-model="dialog.form.params" placeholder="JSON格式参数，可选" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="dialog.form.remark" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 脚本编辑器 -->
    <el-dialog v-model="scriptDialog.visible" title="脚本编辑器" width="800px">
      <el-form :model="scriptDialog.form" label-width="80px">
        <el-form-item label="脚本名称">
          <el-input v-model="scriptDialog.form.name" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="scriptDialog.form.description" />
        </el-form-item>
        <el-form-item label="脚本内容">
          <el-input
            v-model="scriptDialog.form.content"
            type="textarea"
            rows="15"
            class="code-editor"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="scriptDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitScript">保存</el-button>
      </template>
    </el-dialog>

    <!-- Cron帮助 -->
    <el-dialog v-model="showCronHelp" title="Cron表达式说明" width="500px">
      <pre class="cron-help">
* * * * *
│ │ │ │ │
│ │ │ │ └─ 星期 (0-7, 0和7都是周日)
│ │ │ └─── 月份 (1-12)
│ │ └───── 日期 (1-31)
│ └─────── 小时 (0-23)
└───────── 分钟 (0-59)

常用示例:
0 0 * * *     每天0点执行
0 */6 * * *   每6小时执行
0 2 * * 1     每周一2点执行
*/5 * * * *   每5分钟执行
      </pre>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getTaskList, createTask, updateTask, deleteTask, runTask, toggleTaskStatus, getTaskScripts, saveTaskScript } from '@/api/task'
import { reportAdminUiError } from '@/utils/dev-error'

const loading = ref(false)
const taskList = ref([])
const scriptList = ref([])
const showCronHelp = ref(false)
const formRef = ref(null)

const dialog = reactive({
  visible: false,
  isEdit: false,
  form: {
    name: '',
    script_id: '',
    cron: '',
    params: '',
    remark: ''
  }
})

const scriptDialog = reactive({
  visible: false,
  form: {
    name: '',
    description: '',
    content: ''
  }
})

const rules = {
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  script_id: [{ required: true, message: '请选择脚本', trigger: 'change' }],
  cron: [{ required: true, message: '请输入Cron表达式', trigger: 'blur' }]
}

onMounted(() => {
  loadTaskList()
  loadScriptList()
})

async function loadTaskList() {
  loading.value = true
  try {
    const { data } = await getTaskList()
    taskList.value = data.list
  } finally {
    loading.value = false
  }
}

async function loadScriptList() {
  const { data } = await getTaskScripts()
  scriptList.value = data.list
}

function handleAdd() {
  dialog.isEdit = false
  dialog.form = {
    name: '',
    script_id: '',
    cron: '',
    params: '',
    remark: ''
  }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.isEdit = true
  dialog.form = { ...row }
  dialog.visible = true
}

async function submitForm() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  try {
    if (dialog.isEdit) {
      await updateTask(dialog.form.id, dialog.form)
    } else {
      await createTask(dialog.form)
    }
    ElMessage.success(dialog.isEdit ? '修改成功' : '创建成功')
    dialog.visible = false
    loadTaskList()
  } catch (error) {
    reportAdminUiError('task_list', 'submit_task_failed', error, {
      mode: dialog.isEdit ? 'edit' : 'create',
      task_id: dialog.form.id ?? null
    })
  }
}

async function handleToggleStatus(row) {
  try {
    await toggleTaskStatus(row.id, row.status)
    ElMessage.success(row.status === 1 ? '任务已启用' : '任务已禁用')
  } catch {
    row.status = row.status === 1 ? 0 : 1
  }
}

async function handleRun(row) {
  try {
    await runTask(row.id)
    ElMessage.success('任务已触发执行')
    loadTaskList()
  } catch (error) {
    console.error(error)
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除该任务吗？', '提示', { type: 'warning' })
    await deleteTask(row.id)
    ElMessage.success('删除成功')
    loadTaskList()
  } catch {
    // 取消删除
  }
}

function handleEditScript(row) {
  scriptDialog.form = { ...row }
  scriptDialog.visible = true
}

async function submitScript() {
  try {
    await saveTaskScript(scriptDialog.form)
    ElMessage.success('保存成功')
    scriptDialog.visible = false
    loadScriptList()
  } catch (error) {
    reportAdminUiError('task_list', 'save_script_failed', error, {
      script_id: scriptDialog.form.id ?? null,
      script_name: scriptDialog.form.name || ''
    })
  }
}

function handleViewLog(row) {
  // 跳转到日志页面
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.code-editor {
  :deep(.el-textarea__inner) {
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 14px;
    line-height: 1.6;
  }
}

.cron-help {
  background: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  line-height: 1.8;
}
</style>
