<template>
  <div class="shensha-manage">
    <div class="page-header">
      <h2>神煞管理</h2>
      <el-button type="primary" @click="openDialog()">
        <el-icon><Plus /></el-icon>
        新增神煞
      </el-button>
    </div>

    <!-- 搜索筛选 -->
    <div class="filter-bar">
      <el-input
        v-model="searchKeyword"
        placeholder="搜索神煞名称"
        prefix-icon="Search"
        clearable
        style="width: 250px"
      />
      <el-select v-model="filterType" placeholder="类型筛选" clearable style="width: 150px">
        <el-option label="大吉" value="daji" />
        <el-option label="吉" value="ji" />
        <el-option label="平" value="ping" />
        <el-option label="凶" value="xiong" />
        <el-option label="大凶" value="daxiong" />
      </el-select>
      <el-select v-model="filterCategory" placeholder="分类筛选" clearable style="width: 150px">
        <el-option label="贵人" value="guiren" />
        <el-option label="学业" value="xueye" />
        <el-option label="感情" value="ganqing" />
        <el-option label="健康" value="jiankang" />
        <el-option label="财运" value="caiyun" />
        <el-option label="其他" value="qita" />
      </el-select>
    </div>

    <!-- 神煞列表 -->
    <el-table
      v-loading="loading"
      :data="filteredList"
      style="width: 100%"
      border
    >
      <el-table-column type="index" width="50" />
      <el-table-column prop="name" label="神煞名称" width="120" />
      <el-table-column prop="type" label="类型" width="100">
        <template #default="{ row }">
          <el-tag :type="getTypeTag(row.type)">{{ getTypeLabel(row.type) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="category" label="分类" width="100">
        <template #default="{ row }">
          {{ getCategoryLabel(row.category) }}
        </template>
      </el-table-column>
      <el-table-column prop="description" label="含义说明" min-width="200" show-overflow-tooltip />
      <el-table-column prop="effect" label="影响描述" min-width="200" show-overflow-tooltip />
      <el-table-column prop="checkRule" label="查法规则" min-width="250" show-overflow-tooltip />
      <el-table-column prop="status" label="状态" width="80">
        <template #default="{ row }">
          <el-switch
            v-model="row.status"
            :active-value="1"
            :inactive-value="0"
            @change="updateStatus(row)"
          />
        </template>
      </el-table-column>
      <el-table-column label="操作" width="150" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link @click="openDialog(row)">编辑</el-button>
          <el-button type="danger" link @click="deleteShensha(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination">
      <el-pagination
        v-model:current-page="page"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        @size-change="loadData"
        @current-change="loadData"
      />
    </div>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑神煞' : '新增神煞'"
      width="600px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="神煞名称" prop="name">
          <el-input v-model="form.name" placeholder="如：天乙贵人" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio-button label="daji">大吉</el-radio-button>
            <el-radio-button label="ji">吉</el-radio-button>
            <el-radio-button label="ping">平</el-radio-button>
            <el-radio-button label="xiong">凶</el-radio-button>
            <el-radio-button label="daxiong">大凶</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="分类" prop="category">
          <el-select v-model="form.category" placeholder="请选择分类">
            <el-option label="贵人" value="guiren" />
            <el-option label="学业" value="xueye" />
            <el-option label="感情" value="ganqing" />
            <el-option label="健康" value="jiankang" />
            <el-option label="财运" value="caiyun" />
            <el-option label="其他" value="qita" />
          </el-select>
        </el-form-item>
        <el-form-item label="含义说明" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="简要说明此神煞的含义"
          />
        </el-form-item>
        <el-form-item label="影响描述" prop="effect">
          <el-input
            v-model="form.effect"
            type="textarea"
            :rows="3"
            placeholder="描述此神煞对命局的影响"
          />
        </el-form-item>
        <el-form-item label="查法规则" prop="checkRule">
          <el-input
            v-model="form.checkRule"
            type="textarea"
            :rows="4"
            placeholder="说明如何查询此神煞，如：甲戊见牛羊，乙己鼠猴乡..."
          />
        </el-form-item>
        <el-form-item label="查法代码" prop="checkCode">
          <el-input
            v-model="form.checkCode"
            type="textarea"
            :rows="4"
            placeholder="PHP/JS代码实现此神煞的查询逻辑"
          />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitLoading">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search } from '@element-plus/icons-vue'
import { 
  getShenshaList, 
  saveShensha, 
  deleteShenshaApi, 
  toggleShenshaStatus 
} from '@/api/admin'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref(null)

// 搜索筛选
const searchKeyword = ref('')
const filterType = ref('')
const filterCategory = ref('')

// 数据列表
const shenshaList = ref([])

// 分页
const page = ref(1)
const pageSize = ref(20)
const total = ref(0)

// 表单
const form = ref({
  id: null,
  name: '',
  type: 'ji',
  category: 'guiren',
  description: '',
  effect: '',
  checkRule: '',
  checkCode: '',
  gan_rules: null,
  zhi_rules: null,
  sort: 0,
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入神煞名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  category: [{ required: true, message: '请选择分类', trigger: 'change' }],
  description: [{ required: true, message: '请输入含义说明', trigger: 'blur' }],
  checkRule: [{ required: true, message: '请输入查法规则', trigger: 'blur' }]
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await getShenshaList({
      page: page.value,
      pageSize: pageSize.value,
      keyword: searchKeyword.value,
      type: filterType.value,
      category: filterCategory.value
    })
    shenshaList.value = res.data.list
    total.value = res.data.total
  } catch (error) {
    console.error('Load shensha error:', error)
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 监听筛选条件变化
import { watch } from 'vue'
watch([searchKeyword, filterType, filterCategory], () => {
  page.value = 1
  loadData()
})

const getTypeTag = (type) => {
  const map = {
    daji: 'success',
    ji: 'success',
    ping: 'info',
    xiong: 'warning',
    daxiong: 'danger'
  }
  return map[type] || 'info'
}

const getTypeLabel = (type) => {
  const map = {
    daji: '大吉',
    ji: '吉',
    ping: '平',
    xiong: '凶',
    daxiong: '大凶'
  }
  return map[type] || type
}

const getCategoryLabel = (category) => {
  const map = {
    guiren: '贵人',
    xueye: '学业',
    ganqing: '感情',
    jiankang: '健康',
    caiyun: '财运',
    qita: '其他'
  }
  return map[category] || category
}

const openDialog = (row = null) => {
  if (row) {
    isEdit.value = true
    form.value = { ...row }
    // 如果 JSON 字段是字符串，尝试解析（后端模型已处理，前端预防一下）
    if (typeof form.value.gan_rules === 'string') {
        try { form.value.gan_rules = JSON.parse(form.value.gan_rules) } catch(e) {}
    }
    if (typeof form.value.zhi_rules === 'string') {
        try { form.value.zhi_rules = JSON.parse(form.value.zhi_rules) } catch(e) {}
    }
  } else {
    isEdit.value = false
    form.value = {
      id: null,
      name: '',
      type: 'ji',
      category: 'guiren',
      description: '',
      effect: '',
      checkRule: '',
      checkCode: '',
      gan_rules: null,
      zhi_rules: null,
      sort: 0,
      status: 1
    }
  }
  dialogVisible.value = true
}

const submitForm = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  submitLoading.value = true
  
  try {
    await saveShensha(form.value)
    ElMessage.success(isEdit.value ? '更新成功' : '新增成功')
    dialogVisible.value = false
    loadData()
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    submitLoading.value = false
  }
}

const deleteShensha = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除此神煞吗？', '提示', {
      type: 'warning'
    })
    await deleteShenshaApi(row.id)
    ElMessage.success('删除成功')
    loadData()
  } catch (error) {
    if (error !== 'cancel') {
        ElMessage.error('删除失败')
    }
  }
}

const updateStatus = async (row) => {
  try {
    await toggleShenshaStatus(row.id, row.status)
    ElMessage.success(row.status === 1 ? '已启用' : '已禁用')
  } catch (error) {
    row.status = row.status === 1 ? 0 : 1 // 还原状态
    ElMessage.error('状态更新失败')
  }
}

onMounted(() => {
  loadData()
})
</script>


<style scoped>
.shensha-manage {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
