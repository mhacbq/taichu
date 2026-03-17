<template>
  <div class="app-container">
    <el-card shadow="never" class="search-form">
      <el-form :model="queryForm" inline>
        <el-form-item label="日期">
          <el-date-picker
            v-model="queryForm.date"
            type="date"
            placeholder="选择日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button type="success" @click="handleAdd">新增数据</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="almanacList" v-loading="loading" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="suit" label="宜" min-width="150" show-overflow-tooltip />
        <el-table-column prop="avoid" label="忌" min-width="150" show-overflow-tooltip />
        <el-table-column prop="ganzhi" label="干支" width="120" />
        <el-table-column prop="wuxing" label="五行" width="100" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialog.visible" title="黄历数据管理" width="600px">
      <el-form :model="dialog.form" label-width="80px">
        <el-form-item label="日期">
          <el-date-picker v-model="dialog.form.date" type="date" value-format="YYYY-MM-DD" />
        </el-form-item>
        <el-form-item label="宜">
          <el-input v-model="dialog.form.suit" placeholder="多个请用空格分隔" />
        </el-form-item>
        <el-form-item label="忌">
          <el-input v-model="dialog.form.avoid" placeholder="多个请用空格分隔" />
        </el-form-item>
        <el-form-item label="干支">
          <el-input v-model="dialog.form.ganzhi" placeholder="如：甲子" />
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
const almanacList = ref([])
const queryForm = reactive({ date: '' })
const dialog = reactive({
  visible: false,
  form: { date: '', suit: '', avoid: '', ganzhi: '' }
})

function handleSearch() {}
function handleAdd() { dialog.visible = true }
function handleEdit(row) { dialog.form = { ...row }; dialog.visible = true }
function handleDelete(row) {}
</script>

<style scoped>
.app-container { padding: 20px; }
.search-form { margin-bottom: 20px; }
</style>
