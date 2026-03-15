<template>
  <div class="app-container">
    <el-row :gutter="20">
      <!-- 字典类型列表 -->
      <el-col :lg="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>字典类型</span>
              <el-button type="primary" size="small" @click="handleAddType">
                <el-icon><Plus /></el-icon>新增
              </el-button>
            </div>
          </template>
          
          <el-input
            v-model="typeSearch"
            placeholder="搜索字典类型"
            prefix-icon="Search"
            clearable
            style="margin-bottom: 15px;"
          />
          
          <el-table
            :data="filteredTypes"
            highlight-current-row
            @current-change="handleTypeSelect"
            size="small"
          >
            <el-table-column prop="name" label="类型名称" show-overflow-tooltip />
            <el-table-column prop="code" label="类型编码" width="120" />
          </el-table>
        </el-card>
      </el-col>
      
      <!-- 字典数据列表 -->
      <el-col :lg="16">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>{{ selectedType?.name || '字典数据' }}</span>
              <el-button type="primary" size="small" @click="handleAddData" :disabled="!selectedType">
                <el-icon><Plus /></el-icon>新增数据
              </el-button>
            </div>
          </template>
          
          <CommonTable
            :data="dictData"
            :columns="dictColumns"
            :loading="false"
            :show-pagination="false"
            @edit="handleEditData"
            @delete="handleDeleteData"
          />
        </el-card>
      </el-col>
    </el-row>

    <!-- 类型编辑弹窗 -->
    <el-dialog v-model="typeDialog.visible" :title="typeDialog.isEdit ? '编辑字典类型' : '新增字典类型'" width="500px">
      <el-form :model="typeDialog.form" label-width="100px">
        <el-form-item label="类型名称">
          <el-input v-model="typeDialog.form.name" />
        </el-form-item>
        <el-form-item label="类型编码">
          <el-input v-model="typeDialog.form.code" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="typeDialog.form.description" type="textarea" rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="typeDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitType">确定</el-button>
      </template>
    </el-dialog>

    <!-- 数据编辑弹窗 -->
    <el-dialog v-model="dataDialog.visible" :title="dataDialog.isEdit ? '编辑字典数据' : '新增字典数据'" width="500px">
      <el-form :model="dataDialog.form" label-width="100px">
        <el-form-item label="标签">
          <el-input v-model="dataDialog.form.label" placeholder="显示的标签" />
        </el-form-item>
        <el-form-item label="值">
          <el-input v-model="dataDialog.form.value" placeholder="存储的值" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="dataDialog.form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="dataDialog.form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dataDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitData">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import CommonTable from '@/components/CommonTable/index.vue'

// 搜索关键词
const typeSearch = ref('')

// 字典类型列表
const dictTypes = ref([
  { id: 1, name: '用户状态', code: 'user_status', description: '用户账号状态' },
  { id: 2, name: '反馈类型', code: 'feedback_type', description: '用户反馈类型' },
  { id: 3, name: '风险等级', code: 'risk_level', description: '风险事件等级' },
  { id: 4, name: '积分类型', code: 'points_type', description: '积分变动类型' },
  { id: 5, name: '任务状态', code: 'task_status', description: '定时任务状态' }
])

// 过滤后的类型
const filteredTypes = computed(() => {
  if (!typeSearch.value) return dictTypes.value
  const keyword = typeSearch.value.toLowerCase()
  return dictTypes.value.filter(t => 
    t.name.toLowerCase().includes(keyword) || 
    t.code.toLowerCase().includes(keyword)
  )
})

// 选中的类型
const selectedType = ref(null)

// 字典数据
const dictData = ref([])

// 数据表格列
const dictColumns = [
  { prop: 'id', label: 'ID', width: 80 },
  { prop: 'label', label: '标签' },
  { prop: 'value', label: '值' },
  { prop: 'sort', label: '排序', width: 80 },
  { prop: 'status', label: '状态', type: 'tag', tagMap: { 1: '启用', 0: '禁用' }, width: 80 }
]

// 类型弹窗
const typeDialog = reactive({
  visible: false,
  isEdit: false,
  form: { name: '', code: '', description: '' }
})

// 数据弹窗
const dataDialog = reactive({
  visible: false,
  isEdit: false,
  form: { label: '', value: '', sort: 0, status: 1 }
})

// 选择类型
function handleTypeSelect(row) {
  selectedType.value = row
  // 加载该类型的字典数据
  loadDictData(row.code)
}

// 加载字典数据
function loadDictData(typeCode) {
  // 模拟数据
  const mockData = {
    user_status: [
      { id: 1, label: '正常', value: '1', sort: 1, status: 1 },
      { id: 2, label: '禁用', value: '0', sort: 2, status: 1 }
    ],
    feedback_type: [
      { id: 3, label: '问题反馈', value: 'bug', sort: 1, status: 1 },
      { id: 4, label: '功能建议', value: 'feature', sort: 2, status: 1 },
      { id: 5, label: '投诉举报', value: 'complaint', sort: 3, status: 1 }
    ],
    risk_level: [
      { id: 6, label: '高危', value: 'high', sort: 1, status: 1 },
      { id: 7, label: '中危', value: 'medium', sort: 2, status: 1 },
      { id: 8, label: '低危', value: 'low', sort: 3, status: 1 }
    ]
  }
  dictData.value = mockData[typeCode] || []
}

// 新增类型
function handleAddType() {
  typeDialog.isEdit = false
  typeDialog.form = { name: '', code: '', description: '' }
  typeDialog.visible = true
}

// 提交类型
function submitType() {
  typeDialog.visible = false
  ElMessage.success('保存成功')
}

// 新增数据
function handleAddData() {
  dataDialog.isEdit = false
  dataDialog.form = { label: '', value: '', sort: 0, status: 1 }
  dataDialog.visible = true
}

// 编辑数据
function handleEditData(row) {
  dataDialog.isEdit = true
  dataDialog.form = { ...row }
  dataDialog.visible = true
}

// 删除数据
async function handleDeleteData(row) {
  try {
    await ElMessageBox.confirm('确定删除该字典数据吗？', '提示', { type: 'warning' })
    ElMessage.success('删除成功')
  } catch {
    // 取消
  }
}

// 提交数据
function submitData() {
  dataDialog.visible = false
  ElMessage.success('保存成功')
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
