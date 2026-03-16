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
import { ref, computed, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import CommonTable from '@/components/CommonTable/index.vue'

// TODO: 后端需要实现字典管理API接口
// GET /api/admin/system/dict-types - 获取字典类型列表
// POST /api/admin/system/dict-types - 创建字典类型
// PUT /api/admin/system/dict-types/:id - 更新字典类型
// DELETE /api/admin/system/dict-types/:id - 删除字典类型
// GET /api/admin/system/dict-data - 获取字典数据列表
// POST /api/admin/system/dict-data - 创建字典数据
// PUT /api/admin/system/dict-data/:id - 更新字典数据
// DELETE /api/admin/system/dict-data/:id - 删除字典数据

// 搜索关键词
const typeSearch = ref('')
const loading = ref(false)

// 字典类型列表
const dictTypes = ref([])

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

// 加载字典类型列表
const loadDictTypes = async () => {
  loading.value = true
  try {
    // TODO: 调用后端API获取字典类型列表
    // const res = await getDictTypes()
    // if (res.code === 200) {
    //   dictTypes.value = res.data
    // }

    // 临时使用模拟数据，等待后端接口
    dictTypes.value = [
      { id: 1, name: '用户状态', code: 'user_status', description: '用户账号状态' },
      { id: 2, name: '反馈类型', code: 'feedback_type', description: '用户反馈类型' },
      { id: 3, name: '风险等级', code: 'risk_level', description: '风险事件等级' },
      { id: 4, name: '积分类型', code: 'points_type', description: '积分变动类型' },
      { id: 5, name: '任务状态', code: 'task_status', description: '定时任务状态' },
      { id: 6, name: '订单状态', code: 'order_status', description: '充值订单状态' },
      { id: 7, name: '支付方式', code: 'payment_method', description: '支付方式类型' }
    ]
  } catch (error) {
    ElMessage.error('加载字典类型失败')
  } finally {
    loading.value = false
  }
}

// 选择类型
function handleTypeSelect(row) {
  selectedType.value = row
  // 加载该类型的字典数据
  if (row) {
    loadDictData(row.code)
  } else {
    dictData.value = []
  }
}

// 加载字典数据
const loadDictData = async (typeCode) => {
  try {
    // TODO: 调用后端API获取字典数据
    // const res = await getDictData(typeCode)
    // if (res.code === 200) {
    //   dictData.value = res.data
    // }

    // 临时使用模拟数据，等待后端接口
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
      ],
      points_type: [
        { id: 9, label: '充值', value: 'recharge', sort: 1, status: 1 },
        { id: 10, label: '消费', value: 'consume', sort: 2, status: 1 },
        { id: 11, label: '赠送', value: 'gift', sort: 3, status: 1 },
        { id: 12, label: '退款', value: 'refund', sort: 4, status: 1 }
      ],
      task_status: [
        { id: 13, label: '运行中', value: 'running', sort: 1, status: 1 },
        { id: 14, label: '已暂停', value: 'paused', sort: 2, status: 1 },
        { id: 15, label: '已停止', value: 'stopped', sort: 3, status: 1 }
      ],
      order_status: [
        { id: 16, label: '待支付', value: 'pending', sort: 1, status: 1 },
        { id: 17, label: '已支付', value: 'paid', sort: 2, status: 1 },
        { id: 18, label: '已取消', value: 'cancelled', sort: 3, status: 1 },
        { id: 19, label: '已退款', value: 'refunded', sort: 4, status: 1 }
      ],
      payment_method: [
        { id: 20, label: '微信支付', value: 'wechat', sort: 1, status: 1 },
        { id: 21, label: '支付宝', value: 'alipay', sort: 2, status: 1 }
      ]
    }
    dictData.value = mockData[typeCode] || []
  } catch (error) {
    ElMessage.error('加载字典数据失败')
  }
}

// 新增类型
function handleAddType() {
  typeDialog.isEdit = false
  typeDialog.form = { name: '', code: '', description: '' }
  typeDialog.visible = true
}

// 提交类型
async function submitType() {
  try {
    // TODO: 调用后端API保存字典类型
    // const api = typeDialog.isEdit ? updateDictType : createDictType
    // const res = await api(typeDialog.form)
    // if (res.code === 200) {
    //   ElMessage.success(typeDialog.isEdit ? '修改成功' : '新增成功')
    //   typeDialog.visible = false
    //   loadDictTypes()
    // }

    // 临时模拟保存成功，等待后端接口
    ElMessage.success(typeDialog.isEdit ? '修改成功' : '新增成功')
    typeDialog.visible = false
    await loadDictTypes()
  } catch (error) {
    ElMessage.error('保存失败')
  }
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

    // TODO: 调用后端API删除字典数据
    // const res = await deleteDictData(row.id)
    // if (res.code === 200) {
    //   ElMessage.success('删除成功')
    //   if (selectedType.value) {
    //     loadDictData(selectedType.value.code)
    //   }
    // }

    // 临时模拟删除成功，等待后端接口
    ElMessage.success('删除成功')
    if (selectedType.value) {
      await loadDictData(selectedType.value.code)
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 提交数据
async function submitData() {
  try {
    // TODO: 调用后端API保存字典数据
    // const api = dataDialog.isEdit ? updateDictData : createDictData
    // const data = { ...dataDialog.form, type_code: selectedType.value?.code }
    // const res = await api(data)
    // if (res.code === 200) {
    //   ElMessage.success(dataDialog.isEdit ? '修改成功' : '新增成功')
    //   dataDialog.visible = false
    //   if (selectedType.value) {
    //     loadDictData(selectedType.value.code)
    //   }
    // }

    // 临时模拟保存成功，等待后端接口
    ElMessage.success(dataDialog.isEdit ? '修改成功' : '新增成功')
    dataDialog.visible = false
    if (selectedType.value) {
      await loadDictData(selectedType.value.code)
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 页面加载时初始化
onMounted(() => {
  loadDictTypes()
})
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
