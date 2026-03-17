<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增规则
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="ruleList" v-loading="loading" stripe>
        <el-table-column prop="name" label="规则名称" width="150" />
        <el-table-column prop="type" label="检测类型" width="120">
          <template #default="{ row }">
            <el-tag>{{ row.type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="threshold" label="阈值" width="100" />
        <el-table-column prop="action" label="拦截动作" width="120" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" title="反作弊规则" width="500px">
      <el-form :model="dialog.form" label-width="100px">
        <el-form-item label="规则名称">
          <el-input v-model="dialog.form.name" />
        </el-form-item>
        <el-form-item label="检测类型">
          <el-select v-model="dialog.form.type">
            <el-option label="IP频率" value="ip_rate" />
            <el-option label="用户频率" value="user_rate" />
            <el-option label="设备频率" value="device_rate" />
          </el-select>
        </el-form-item>
        <el-form-item label="阈值(次/分)">
          <el-input-number v-model="dialog.form.threshold" />
        </el-form-item>
        <el-form-item label="拦截动作">
          <el-select v-model="dialog.form.action">
            <el-option label="记录日志" value="log" />
            <el-option label="验证码" value="captcha" />
            <el-option label="直接拦截" value="block" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const loading = ref(false)
const ruleList = ref([
  { id: 1, name: 'IP访问限频', type: 'ip_rate', threshold: 60, action: 'block', status: 1 },
  { id: 2, name: '单设备注册限制', type: 'device_rate', threshold: 3, action: 'block', status: 1 }
])

const dialog = reactive({
  visible: false,
  form: {}
})

function handleAdd() {
  dialog.form = { name: '', type: 'ip_rate', threshold: 60, action: 'block' }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.form = { ...row }
  dialog.visible = true
}

function handleDelete(row) {}
</script>

<style scoped>
.app-container { padding: 20px; }
.table-operations { margin-bottom: 20px; }
</style>
