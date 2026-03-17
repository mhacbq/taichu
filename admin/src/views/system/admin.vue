<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>新增管理员
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="adminList" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="nickname" label="昵称" width="120" />
        <el-table-column prop="role_name" label="角色" width="120">
          <template #default="{ row }">
            <el-tag>{{ row.role_name }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="last_login_at" label="最后登录" width="180" />
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

    <el-dialog v-model="dialog.visible" title="管理员管理" width="500px">
      <el-form :model="dialog.form" label-width="80px">
        <el-form-item label="用户名">
          <el-input v-model="dialog.form.username" />
        </el-form-item>
        <el-form-item label="昵称">
          <el-input v-model="dialog.form.nickname" />
        </el-form-item>
        <el-form-item label="密码" v-if="!dialog.isEdit">
          <el-input v-model="dialog.form.password" type="password" show-password />
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="dialog.form.role">
            <el-option label="超级管理员" value="admin" />
            <el-option label="运营人员" value="operator" />
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
const adminList = ref([])
const dialog = reactive({
  visible: false,
  isEdit: false,
  form: { username: '', nickname: '', role: 'operator' }
})

function handleAdd() {
  dialog.isEdit = false
  dialog.form = { username: '', nickname: '', role: 'operator' }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.isEdit = true
  dialog.form = { ...row }
  dialog.visible = true
}

function handleDelete(row) {}
</script>

<style scoped>
.app-container { padding: 20px; }
.table-operations { margin-bottom: 20px; }
</style>
