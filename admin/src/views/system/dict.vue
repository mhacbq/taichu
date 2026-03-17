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
import { 
  getDictTypes, createDictType, updateDictType, deleteDictType,
  getDictData, saveDictData, deleteDictData 
} from '../../api/system'
import { Plus, Search } from '@element-plus/icons-vue'

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
    t.type.toLowerCase().includes(keyword)
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
  { prop: 'sort_order', label: '排序', width: 80 },
  { prop: 'status', label: '状态', type: 'tag', tagMap: { 1: '启用', 0: '禁用' }, width: 80 }
]

// 类型弹窗
const typeDialog = reactive({
  visible: false,
  isEdit: false,
  form: { id: null, name: '', type: '', remark: '' }
})

// 数据弹窗
const dataDialog = reactive({
  visible: false,
  isEdit: false,
  form: { id: null, label: '', value: '', sort_order: 0, status: 1 }
})

// 加载字典类型列表
const loadDictTypes = async () => {
  loading.value = true
  try {
    const res = await getDictTypes()
    if (res.code === 200) {
      dictTypes.value = res.data
      if (dictTypes.value.length > 0 && !selectedType.value) {
        handleTypeSelect(dictTypes.value[0])
      }
    }
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
    loadDataList(row.type)
  } else {
    dictData.value = []
  }
}

// 加载字典数据
const loadDataList = async (typeCode) => {
  try {
    const res = await getDictData(typeCode)
    if (res.code === 200) {
      dictData.value = res.data
    }
  } catch (error) {
    ElMessage.error('加载字典数据失败')
  }
}

// 新增类型
function handleAddType() {
  typeDialog.isEdit = false
  typeDialog.form = { id: null, name: '', type: '', remark: '' }
  typeDialog.visible = true
}

// 提交类型
async function submitType() {
  try {
    const res = typeDialog.isEdit 
      ? await updateDictType(typeDialog.form.id, typeDialog.form)
      : await createDictType(typeDialog.form)
      
    if (res.code === 200) {
      ElMessage.success(typeDialog.isEdit ? '修改成功' : '新增成功')
      typeDialog.visible = false
      loadDictTypes()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 新增数据
function handleAddData() {
  dataDialog.isEdit = false
  dataDialog.form = { id: null, label: '', value: '', sort_order: 0, status: 1 }
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
    const res = await deleteDictData(row.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      if (selectedType.value) {
        loadDataList(selectedType.value.type)
      }
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
    const data = { ...dataDialog.form, dict_type: selectedType.value?.type }
    const res = await saveDictData(data)
    if (res.code === 200) {
      ElMessage.success(dataDialog.isEdit ? '修改成功' : '新增成功')
      dataDialog.visible = false
      if (selectedType.value) {
        loadDataList(selectedType.value.type)
      }
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
