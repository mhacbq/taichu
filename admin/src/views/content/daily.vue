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
        <el-form-item label="日主">
          <el-select v-model="queryForm.dayMaster" placeholder="全部日主" clearable>
            <el-option v-for="item in stemList" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button type="success" @click="handleGenerate">生成当日运势</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never">
      <el-table :data="fortuneList" v-loading="loading" stripe>
        <el-table-column prop="date" label="日期" width="120" />
        <el-table-column prop="lunar_date" label="农历" width="150" />
        <el-table-column prop="overall_score" label="综合评分" width="100">
          <template #default="{ row }">
            <el-tag :type="row.overall_score >= 80 ? 'success' : (row.overall_score >= 60 ? 'warning' : 'danger')">
              {{ row.overall_score }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="summary" label="运势简评" min-width="200" show-overflow-tooltip />
        <el-table-column prop="created_at" label="生成时间" width="180" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.pageSize"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadFortunes"
          @current-change="loadFortunes"
        />
      </div>
    </el-card>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialog.visible" title="编辑每日运势" width="700px">
      <el-form :model="dialog.form" label-width="100px" ref="formRef">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="日期" prop="date">
              <el-date-picker v-model="dialog.form.date" type="date" value-format="YYYY-MM-DD" disabled />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="综合评分" prop="overall_score">
              <el-input-number v-model="dialog.form.overall_score" :min="0" :max="100" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="运势简评" prop="summary">
          <el-input v-model="dialog.form.summary" type="textarea" :rows="2" />
        </el-form-item>

        <el-divider content-position="left">运势详情</el-divider>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="事业评分" prop="career_score">
              <el-input-number v-model="dialog.form.career_score" :min="0" :max="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="财运评分" prop="wealth_score">
              <el-input-number v-model="dialog.form.wealth_score" :min="0" :max="100" />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item label="事业描述" prop="career_desc">
          <el-input v-model="dialog.form.career_desc" type="textarea" :rows="2" />
        </el-form-item>
        
        <el-form-item label="财运描述" prop="wealth_desc">
          <el-input v-model="dialog.form.wealth_desc" type="textarea" :rows="2" />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="感情评分" prop="love_score">
              <el-input-number v-model="dialog.form.love_score" :min="0" :max="100" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="健康评分" prop="health_score">
              <el-input-number v-model="dialog.form.health_score" :min="0" :max="100" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="感情描述" prop="love_desc">
          <el-input v-model="dialog.form.love_desc" type="textarea" :rows="2" />
        </el-form-item>

        <el-form-item label="健康描述" prop="health_desc">
          <el-input v-model="dialog.form.health_desc" type="textarea" :rows="2" />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="宜" prop="yi">
              <el-input v-model="dialog.form.yi" placeholder="多个用逗号分隔" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="忌" prop="ji">
              <el-input v-model="dialog.form.ji" placeholder="多个用逗号分隔" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getDailyFortuneList, updateDailyFortune, deleteDailyFortune } from '@/api/content'

const loading = ref(false)
const submitLoading = ref(false)
const total = ref(0)
const fortuneList = ref([])

const queryForm = reactive({
  date: '',
  page: 1,
  pageSize: 20
})

const dialog = reactive({
  visible: false,
  form: {
    id: null,
    date: '',
    overall_score: 80,
    summary: '',
    career_score: 80,
    career_desc: '',
    wealth_score: 80,
    wealth_desc: '',
    love_score: 80,
    love_desc: '',
    health_score: 80,
    health_desc: '',
    yi: '',
    ji: ''
  }
})

onMounted(() => {
  loadFortunes()
})

async function loadFortunes() {
  loading.value = true
  try {
    const res = await getDailyFortuneList(queryForm)
    fortuneList.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  queryForm.page = 1
  loadFortunes()
}

function handleGenerate() {
  ElMessage.warning('后台已配置自动任务生成每日运势，手动生成功能开发中')
}

function handleEdit(row) {
  dialog.form = { ...row }
  dialog.visible = true
}

async function handleSubmit() {
  submitLoading.value = true
  try {
    await updateDailyFortune(dialog.form.id, dialog.form)
    ElMessage.success('更新成功')
    dialog.visible = false
    loadFortunes()
  } catch (error) {
    console.error(error)
  } finally {
    submitLoading.value = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定要删除该条运势数据吗？', '提示', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })
    await deleteDailyFortune(row.id)
    ElMessage.success('删除成功')
    loadFortunes()
  } catch {}
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}
.search-form {
  margin-bottom: 20px;
}
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
