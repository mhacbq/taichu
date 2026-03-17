<template>
  <div class="app-container">
    <div class="table-operations">
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>发布公告
      </el-button>
    </div>

    <el-card shadow="never">
      <el-table :data="noticeList" v-loading="loading" stripe>
        <el-table-column prop="title" label="公告标题" min-width="200" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'important' ? 'danger' : 'info'">{{ row.type === 'important' ? '重要' : '普通' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '已发布' : '草稿' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="发布时间" width="180" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" title="公告管理" width="600px">
      <el-form :model="dialog.form" label-width="80px">
        <el-form-item label="标题">
          <el-input v-model="dialog.form.title" placeholder="请输入公告标题" />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="dialog.form.type">
            <el-option label="普通" value="normal" />
            <el-option label="重要" value="important" />
          </el-select>
        </el-form-item>
        <el-form-item label="内容">
          <el-input v-model="dialog.form.content" type="textarea" rows="5" placeholder="请输入公告内容" />
        </el-form-item>
        <el-form-item label="发布状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">立即发布</el-radio>
            <el-radio :label="0">保存草稿</el-radio>
          </el-radio-group>
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
const noticeList = ref([])
const dialog = reactive({
  visible: false,
  form: { title: '', type: 'normal', content: '', status: 1 }
})

function handleAdd() {
  dialog.form = { title: '', type: 'normal', content: '', status: 1 }
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
