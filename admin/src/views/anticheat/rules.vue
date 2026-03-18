<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增规则
      </el-button>
      <el-button :loading="loading" @click="loadRuleList">刷新</el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="ruleList" v-loading="loading" stripe>
        <el-table-column prop="name" label="规则名称" min-width="160" />
        <el-table-column prop="type" label="检测类型" width="140">
          <template #default="{ row }">
            <el-tag>{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="threshold" label="阈值" width="120" />
        <el-table-column prop="action" label="拦截动作" width="120" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="value => handleToggleStatus(row, value)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.form.id ? '编辑反作弊规则' : '新增反作弊规则'" width="500px">
      <el-form :model="dialog.form" label-width="100px">
        <el-form-item label="规则名称">
          <el-input v-model="dialog.form.name" />
        </el-form-item>
        <el-form-item label="检测类型">
          <el-select v-model="dialog.form.type" style="width: 100%">
            <el-option label="IP频率" value="ip_rate" />
            <el-option label="用户频率" value="user_rate" />
            <el-option label="设备频率" value="device_rate" />
          </el-select>
        </el-form-item>
        <el-form-item label="阈值(次/分)">
          <el-input-number v-model="dialog.form.threshold" :min="1" :max="999999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="拦截动作">
          <el-select v-model="dialog.form.action" style="width: 100%">
            <el-option label="记录日志" value="log" />
            <el-option label="验证码" value="captcha" />
            <el-option label="直接拦截" value="block" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>

        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { deleteRiskRule, getRiskRules, saveRiskRule, updateRiskRule } from '@/api/anticheat'

const loading = ref(false)
const submitLoading = ref(false)
const ruleList = ref([])
const dialog = reactive({
  visible: false,
  form: createDefaultForm()
})

onMounted(() => {
  loadRuleList()
})

function createDefaultForm() {
  return {
    id: 0,
    name: '',
    type: 'ip_rate',
    threshold: 60,
    action: 'block',
    status: 1
  }
}

async function loadRuleList() {
  loading.value = true
  try {
    const { data } = await getRiskRules()
    ruleList.value = (data || []).map(item => ({
      ...item,
      threshold: Number(item.threshold ?? 0),
      status: Number(item.status ?? 0)
    }))
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.form = createDefaultForm()
  dialog.visible = true
}

function handleEdit(row) {
  dialog.form = {
    id: row.id,
    name: row.name,
    type: row.type,
    threshold: Number(row.threshold ?? 60),
    action: row.action || 'block',
    status: Number(row.status ?? 1)
  }
  dialog.visible = true
}

async function handleSubmit() {
  submitLoading.value = true
  try {
    if (dialog.form.id) {
      await updateRiskRule(dialog.form.id, { ...dialog.form })
      ElMessage.success('规则已更新')
    } else {
      await saveRiskRule({ ...dialog.form })
      ElMessage.success('规则已创建')
    }
    dialog.visible = false
    await loadRuleList()
  } finally {
    submitLoading.value = false
  }
}

async function handleToggleStatus(row, value) {
  const previousStatus = value === 1 ? 0 : 1
  try {
    await updateRiskRule(row.id, {
      id: row.id,
      name: row.name,
      type: row.type,
      threshold: Number(row.threshold ?? 0),
      action: row.action,
      status: value
    })
    row.status = value
    ElMessage.success('状态已更新')
  } catch (error) {
    row.status = previousStatus
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(`确定删除规则“${row.name}”吗？`, '警告', { type: 'warning' })
    await deleteRiskRule(row.id)
    ElMessage.success('规则已删除')
    await loadRuleList()
  } catch (error) {
    if (error !== 'cancel') {
      // 统一由请求层提示错误
    }
  }
}
</script>

<style scoped>
.app-container { padding: 20px; }
.table-operations { margin-bottom: 20px; display: flex; gap: 12px; }
</style>
