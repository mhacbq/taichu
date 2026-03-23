<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Plus, Edit, Delete, Refresh } from '@element-plus/icons-vue'
import {
  getShenshaList,
  getShenshaOptions,
  saveShensha,
  deleteShenshaApi,
  toggleShenshaStatus
} from '@/api/admin'

// ========== 状态 ==========
const loading = ref(false)
const shenshaList = ref([])
const total = ref(0)
const options = reactive({
  types: [],
  categories: [],
  statuses: []
})

// 筛选条件
const filters = reactive({
  keyword: '',
  type: '',
  category: '',
  status: '',
  page: 1,
  pageSize: 20
})

// 编辑对话框
const dialogVisible = ref(false)
const dialogTitle = ref('新增神煞')
const formRef = ref(null)
const saving = ref(false)
const form = reactive({
  id: 0,
  name: '',
  type: '',
  category: '',
  description: '',
  effect: '',
  check_rule: '',
  check_code: '',
  gan_rules: '',
  zhi_rules: '',
  sort: 0,
  status: 1
})

// 表单校验规则
const formRules = {
  name: [{ required: true, message: '请输入神煞名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择吉凶类型', trigger: 'change' }],
  category: [{ required: true, message: '请选择分类', trigger: 'change' }]
}

// ========== 类型/分类映射 ==========
const typeOptions = [
  { label: '大吉', value: 'daji', color: '#e74c3c' },
  { label: '吉', value: 'ji', color: '#e67e22' },
  { label: '平', value: 'ping', color: '#3498db' },
  { label: '凶', value: 'xiong', color: '#7f8c8d' },
  { label: '大凶', value: 'daxiong', color: '#2c3e50' }
]

const categoryOptions = [
  { label: '贵人', value: 'guiren' },
  { label: '学业', value: 'xueye' },
  { label: '感情', value: 'ganqing' },
  { label: '健康', value: 'jiankang' },
  { label: '财运', value: 'caiyun' },
  { label: '事业', value: 'shiye' },
  { label: '综合', value: 'zonghe' }
]

// ========== 计算属性 ==========
const typeMap = computed(() => {
  const map = {}
  typeOptions.forEach(t => { map[t.value] = t })
  return map
})

const categoryMap = computed(() => {
  const map = {}
  categoryOptions.forEach(c => { map[c.value] = c })
  return map
})

// 统计信息
const stats = computed(() => {
  const list = shenshaList.value
  const totalCount = total.value
  const enabledCount = list.filter(i => i.status === 1).length
  const disabledCount = list.filter(i => i.status === 0).length
  const jiCount = list.filter(i => ['daji', 'ji'].includes(i.type)).length
  const xiongCount = list.filter(i => ['xiong', 'daxiong'].includes(i.type)).length
  const pingCount = list.filter(i => i.type === 'ping').length
  return { totalCount, enabledCount, disabledCount, jiCount, xiongCount, pingCount }
})

// ========== 方法 ==========

// 加载列表
const loadList = async () => {
  loading.value = true
  try {
    const res = await getShenshaList({
      page: filters.page,
      pageSize: filters.pageSize,
      keyword: filters.keyword || undefined,
      type: filters.type || undefined,
      category: filters.category || undefined,
      status: filters.status !== '' ? filters.status : undefined
    })
    if (res.data?.code === 0 || res.data?.code === 200) {
      const data = res.data?.data || res.data
      shenshaList.value = data.list || data.data || []
      total.value = data.total || 0
    } else {
      shenshaList.value = []
      total.value = 0
    }
  } catch (e) {
    console.error('加载神煞列表失败:', e)
    ElMessage.error('加载失败，请重试')
  } finally {
    loading.value = false
  }
}

// 加载筛选项
const loadOptions = async () => {
  try {
    const res = await getShenshaOptions()
    if (res.data?.code === 0 || res.data?.code === 200) {
      const data = res.data?.data || res.data
      options.types = data.types || []
      options.categories = data.categories || []
      options.statuses = data.statuses || []
    }
  } catch (e) {
    console.error('加载筛选项失败:', e)
  }
}

// 搜索
const handleSearch = () => {
  filters.page = 1
  loadList()
}

// 重置筛选
const handleReset = () => {
  filters.keyword = ''
  filters.type = ''
  filters.category = ''
  filters.status = ''
  filters.page = 1
  loadList()
}

// 翻页
const handlePageChange = (page) => {
  filters.page = page
  loadList()
}

const handleSizeChange = (size) => {
  filters.pageSize = size
  filters.page = 1
  loadList()
}

// 新增
const handleAdd = () => {
  dialogTitle.value = '新增神煞'
  Object.assign(form, {
    id: 0,
    name: '',
    type: '',
    category: '',
    description: '',
    effect: '',
    check_rule: '',
    check_code: '',
    gan_rules: '',
    zhi_rules: '',
    sort: 0,
    status: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑神煞'
  Object.assign(form, {
    id: row.id,
    name: row.name || '',
    type: row.type || '',
    category: row.category || '',
    description: row.description || '',
    effect: row.effect || '',
    check_rule: row.check_rule || '',
    check_code: row.check_code || '',
    gan_rules: row.gan_rules ? (typeof row.gan_rules === 'string' ? row.gan_rules : JSON.stringify(row.gan_rules, null, 2)) : '',
    zhi_rules: row.zhi_rules ? (typeof row.zhi_rules === 'string' ? row.zhi_rules : JSON.stringify(row.zhi_rules, null, 2)) : '',
    sort: row.sort || 0,
    status: row.status ?? 1
  })
  dialogVisible.value = true
}

// 保存
const handleSave = async () => {
  if (!formRef.value) return
  await formRef.value.validate()

  saving.value = true
  try {
    const submitData = { ...form }
    // 解析 JSON 字段
    if (submitData.gan_rules && typeof submitData.gan_rules === 'string') {
      try {
        submitData.gan_rules = JSON.parse(submitData.gan_rules)
      } catch {
        ElMessage.warning('天干规则JSON格式不正确，将作为文本保存')
        submitData.gan_rules = null
      }
    }
    if (submitData.zhi_rules && typeof submitData.zhi_rules === 'string') {
      try {
        submitData.zhi_rules = JSON.parse(submitData.zhi_rules)
      } catch {
        ElMessage.warning('地支规则JSON格式不正确，将作为文本保存')
        submitData.zhi_rules = null
      }
    }

    const res = await saveShensha(submitData)
    if (res.data?.code === 0 || res.data?.code === 200) {
      ElMessage.success(form.id ? '更新成功' : '新增成功')
      dialogVisible.value = false
      loadList()
    } else {
      ElMessage.error(res.data?.msg || '保存失败')
    }
  } catch (e) {
    console.error('保存失败:', e)
    ElMessage.error('保存失败，请重试')
  } finally {
    saving.value = false
  }
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定要删除「${row.name}」吗？删除后不可恢复。`, '确认删除', {
      type: 'warning',
      confirmButtonText: '确定删除',
      cancelButtonText: '取消'
    })

    const res = await deleteShenshaApi(row.id)
    if (res.data?.code === 0 || res.data?.code === 200) {
      ElMessage.success('删除成功')
      loadList()
    } else {
      ElMessage.error(res.data?.msg || '删除失败')
    }
  } catch {
    // 用户取消
  }
}

// 切换状态
const handleToggleStatus = async (row) => {
  const newStatus = row.status === 1 ? 0 : 1
  const actionText = newStatus === 1 ? '启用' : '停用'

  try {
    const res = await toggleShenshaStatus(row.id, newStatus)
    if (res.data?.code === 0 || res.data?.code === 200) {
      row.status = newStatus
      ElMessage.success(`${actionText}成功 - 将影响前端排盘中「${row.name}」的显示`)
    } else {
      ElMessage.error(res.data?.msg || `${actionText}失败`)
    }
  } catch (e) {
    console.error('切换状态失败:', e)
    ElMessage.error(`${actionText}失败，请重试`)
  }
}

// 获取类型标签样式
const getTypeTagType = (type) => {
  const map = { daji: 'danger', ji: 'warning', ping: 'info', xiong: '', daxiong: 'info' }
  return map[type] || 'info'
}

// 获取类型显示文字
const getTypeLabel = (type) => {
  return typeMap.value[type]?.label || type || '-'
}

// 获取分类显示文字
const getCategoryLabel = (category) => {
  return categoryMap.value[category]?.label || category || '-'
}

// ========== 初始化 ==========
onMounted(() => {
  loadList()
  loadOptions()
})
</script>

<template>
  <div class="admin-shensha-manage" v-loading="loading">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-left">
        <h2>神煞管理</h2>
        <span class="header-tip">管理八字算法中的神煞数据，启用/停用将直接影响前端排盘结果中对应神煞的显示</span>
      </div>
      <el-button type="primary" :icon="Plus" @click="handleAdd">新增神煞</el-button>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ stats.totalCount }}</div>
        <div class="stat-label">总数</div>
      </div>
      <div class="stat-card stat-success">
        <div class="stat-value">{{ stats.enabledCount }}</div>
        <div class="stat-label">已启用</div>
      </div>
      <div class="stat-card stat-danger">
        <div class="stat-value">{{ stats.disabledCount }}</div>
        <div class="stat-label">已停用</div>
      </div>
      <div class="stat-card stat-warning">
        <div class="stat-value">{{ stats.jiCount }}</div>
        <div class="stat-label">吉神</div>
      </div>
      <div class="stat-card stat-info">
        <div class="stat-value">{{ stats.xiongCount }}</div>
        <div class="stat-label">凶煞</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.pingCount }}</div>
        <div class="stat-label">平</div>
      </div>
    </div>

    <!-- 筛选区域 -->
    <div class="filter-bar">
      <el-input
        v-model="filters.keyword"
        placeholder="搜索名称/描述/影响"
        clearable
        :prefix-icon="Search"
        style="width: 220px"
        @keyup.enter="handleSearch"
      />
      <el-select v-model="filters.type" placeholder="吉凶类型" clearable style="width: 140px">
        <el-option v-for="t in typeOptions" :key="t.value" :label="t.label" :value="t.value" />
      </el-select>
      <el-select v-model="filters.category" placeholder="分类" clearable style="width: 140px">
        <el-option v-for="c in categoryOptions" :key="c.value" :label="c.label" :value="c.value" />
      </el-select>
      <el-select v-model="filters.status" placeholder="状态" clearable style="width: 120px">
        <el-option label="启用" :value="1" />
        <el-option label="停用" :value="0" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="handleSearch">搜索</el-button>
      <el-button :icon="Refresh" @click="handleReset">重置</el-button>
    </div>

    <!-- 数据表格 -->
    <div class="table-wrapper">
      <el-table :data="shenshaList" border stripe style="width: 100%" row-key="id">
        <el-table-column prop="sort" label="排序" width="70" align="center" />
        <el-table-column prop="name" label="名称" width="120">
          <template #default="{ row }">
            <span class="shensha-name">{{ row.name }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="type" label="吉凶" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)" size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="category" label="分类" width="90" align="center">
          <template #default="{ row }">
            <el-tag type="info" size="small" effect="plain">{{ getCategoryLabel(row.category) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="含义" min-width="180" show-overflow-tooltip />
        <el-table-column prop="effect" label="影响" min-width="180" show-overflow-tooltip />
        <el-table-column prop="check_rule" label="查法口诀" min-width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.status === 1"
              active-text="启用"
              inactive-text="停用"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link :icon="Edit" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link :icon="Delete" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper" v-if="total > 0">
        <el-pagination
          v-model:current-page="filters.page"
          v-model:page-size="filters.pageSize"
          :total="total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="handlePageChange"
          @size-change="handleSizeChange"
        />
      </div>
    </div>

    <!-- 编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="720px" destroy-on-close>
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px" label-position="right">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="名称" prop="name">
              <el-input v-model="form.name" placeholder="如：天乙贵人" maxlength="20" show-word-limit />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序" prop="sort">
              <el-input-number v-model="form.sort" :min="0" :max="999" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="吉凶类型" prop="type">
              <el-select v-model="form.type" placeholder="请选择" style="width: 100%">
                <el-option v-for="t in typeOptions" :key="t.value" :label="t.label" :value="t.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="分类" prop="category">
              <el-select v-model="form.category" placeholder="请选择" style="width: 100%">
                <el-option v-for="c in categoryOptions" :key="c.value" :label="c.label" :value="c.value" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="含义" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="2" placeholder="神煞的含义说明" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="影响" prop="effect">
          <el-input v-model="form.effect" type="textarea" :rows="2" placeholder="对命主的具体影响" maxlength="200" show-word-limit />
        </el-form-item>
        <el-form-item label="查法口诀" prop="check_rule">
          <el-input v-model="form.check_rule" type="textarea" :rows="2" placeholder="传统查法口诀" maxlength="300" show-word-limit />
        </el-form-item>
        <el-form-item label="算法代码" prop="check_code">
          <el-input v-model="form.check_code" type="textarea" :rows="2" placeholder="算法中对应的 key（如 tianyi_guiren），仅供参考" maxlength="100" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="天干规则">
              <el-input v-model="form.gan_rules" type="textarea" :rows="4" placeholder='JSON格式，如：{"甲":["丑","未"],...}' />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="地支规则">
              <el-input v-model="form.zhi_rules" type="textarea" :rows="4" placeholder='JSON格式，如：{"寅":"申",...}' />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用（前端排盘显示此神煞）</el-radio>
            <el-radio :value="0">停用（前端排盘隐藏此神煞）</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.admin-shensha-manage {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.header-left h2 {
  margin: 0 0 4px 0;
  font-size: 20px;
}

.header-tip {
  font-size: 13px;
  color: #909399;
}

/* 统计卡片 */
.stats-row {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  border-left: 3px solid #dcdfe6;
}

.stat-card.stat-success { border-left-color: #67c23a; }
.stat-card.stat-danger { border-left-color: #f56c6c; }
.stat-card.stat-warning { border-left-color: #e6a23c; }
.stat-card.stat-info { border-left-color: #909399; }

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

/* 筛选区 */
.filter-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
  background: white;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* 表格 */
.table-wrapper {
  background: white;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.shensha-name {
  font-weight: 600;
  color: #303133;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

/* 响应式 */
@media (max-width: 1200px) {
  .stats-row {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .page-header {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
