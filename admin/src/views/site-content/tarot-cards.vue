<template>
  <div class="tarot-cards-manager">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>塔罗牌管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>添加塔罗牌
          </el-button>
        </div>
      </template>

      <!-- 搜索 -->
      <el-form :inline="true" :model="queryForm" class="mb-4">
        <el-form-item label="类型">
          <el-select v-model="queryForm.is_major" placeholder="全部" clearable>
            <el-option label="大阿尔卡那" :value="1" />
            <el-option label="小阿尔卡那" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
        </el-form-item>
      </el-form>

      <!-- 塔罗牌列表 -->
      <el-table :data="tableData" v-loading="loading" border>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column label="图片" width="100" align="center">
          <template #default="{ row }">
            <el-image
              :src="row.image_url || defaultImage"
              :preview-src-list="[row.image_url || defaultImage]"
              style="width: 60px; height: 90px; object-fit: cover"
            />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" width="120" />
        <el-table-column prop="name_en" label="英文名" width="150" />
        <el-table-column prop="is_major" label="类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_major ? 'primary' : 'info'">
              {{ row.is_major ? '大阿尔卡那' : '小阿尔卡那' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="keywords" label="关键词" width="180" show-overflow-tooltip />
        <el-table-column prop="upright_meaning" label="正位含义" min-width="200" show-overflow-tooltip />
        <el-table-column prop="reversed_meaning" label="逆位含义" min-width="200" show-overflow-tooltip />
        <el-table-column prop="is_enabled" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_enabled ? 'success' : 'danger'">
              {{ row.is_enabled ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">
              <el-icon><Edit /></el-icon>编辑
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="queryForm.page"
          v-model:page-size="queryForm.limit"
          :total="total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="700px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="塔罗牌名称" />
        </el-form-item>
        <el-form-item label="英文名">
          <el-input v-model="form.name_en" placeholder="英文名称" />
        </el-form-item>
        <el-form-item label="图片">
          <el-input v-model="form.image_url" placeholder="图片URL">
            <template #append>
              <el-button>选择图片</el-button>
            </template>
          </el-input>
          <el-image
            v-if="form.image_url"
            :src="form.image_url"
            style="width: 100px; height: 150px; margin-top: 10px; object-fit: cover"
          />
        </el-form-item>
        <el-form-item label="类型" prop="is_major">
          <el-radio-group v-model="form.is_major">
            <el-radio :label="1">大阿尔卡那</el-radio>
            <el-radio :label="0">小阿尔卡那</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="form.keywords" placeholder="逗号分隔的关键词" />
        </el-form-item>
        <el-form-item label="正位含义">
          <el-input
            v-model="form.upright_meaning"
            type="textarea"
            :rows="3"
            placeholder="正位时的含义"
          />
        </el-form-item>
        <el-form-item label="逆位含义">
          <el-input
            v-model="form.reversed_meaning"
            type="textarea"
            :rows="3"
            placeholder="逆位时的含义"
          />
        </el-form-item>
        <el-form-item label="感情正位">
          <el-input v-model="form.love_meaning" type="textarea" :rows="2" placeholder="感情方面正位含义" />
        </el-form-item>
        <el-form-item label="感情逆位">
          <el-input v-model="form.love_reversed" type="textarea" :rows="2" placeholder="感情方面逆位含义" />
        </el-form-item>
        <el-form-item label="事业正位">
          <el-input v-model="form.career_meaning" type="textarea" :rows="2" placeholder="事业方面正位含义" />
        </el-form-item>
        <el-form-item label="事业逆位">
          <el-input v-model="form.career_reversed" type="textarea" :rows="2" placeholder="事业方面逆位含义" />
        </el-form-item>
        <el-form-item label="健康正位">
          <el-input v-model="form.health_meaning" type="textarea" :rows="2" placeholder="健康方面正位含义" />
        </el-form-item>
        <el-form-item label="健康逆位">
          <el-input v-model="form.health_reversed" type="textarea" :rows="2" placeholder="健康方面逆位含义" />
        </el-form-item>
        <el-form-item label="财运正位">
          <el-input v-model="form.wealth_meaning" type="textarea" :rows="2" placeholder="财运方面正位含义" />
        </el-form-item>
        <el-form-item label="财运逆位">
          <el-input v-model="form.wealth_reversed" type="textarea" :rows="2" placeholder="财运方面逆位含义" />
        </el-form-item>
        <el-form-item label="详细描述">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="4"
            placeholder="塔罗牌的详细描述"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="form.is_enabled"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Search, Edit } from '@element-plus/icons-vue'
import {
  getTarotCardList,
  saveTarotCard
} from '@/api/siteContent'

const defaultImage = 'https://via.placeholder.com/60x90?text=Card'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('添加塔罗牌')
const formRef = ref(null)
const tableData = ref([])
const total = ref(0)

const queryForm = reactive({
  page: 1,
  limit: 10,
  is_major: null
})

const form = reactive({
  id: null,
  name: '',
  name_en: '',
  image_url: '',
  is_major: 1,
  upright_meaning: '',
  reversed_meaning: '',
  love_meaning: '',
  love_reversed: '',
  career_meaning: '',
  career_reversed: '',
  health_meaning: '',
  health_reversed: '',
  wealth_meaning: '',
  wealth_reversed: '',
  keywords: '',
  description: '',
  is_enabled: 1
})

const rules = {
  name: [{ required: true, message: '请输入塔罗牌名称', trigger: 'blur' }],
  is_major: [{ required: true, message: '请选择类型', trigger: 'change' }]
}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const res = await getTarotCardList(queryForm)
    if (res.code === 0) {
      tableData.value = res.data?.list || []
      total.value = res.data?.total || 0
    }
  } catch (error) {
    ElMessage.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  queryForm.page = 1
  loadData()
}

// 分页
const handleSizeChange = (val) => {
  queryForm.limit = val
  loadData()
}

const handleCurrentChange = (val) => {
  queryForm.page = val
  loadData()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '添加塔罗牌'
  Object.assign(form, {
    id: null,
    name: '',
    name_en: '',
    image_url: '',
    is_major: 1,
    upright_meaning: '',
    reversed_meaning: '',
    love_meaning: '',
    love_reversed: '',
    career_meaning: '',
    career_reversed: '',
    health_meaning: '',
    health_reversed: '',
    wealth_meaning: '',
    wealth_reversed: '',
    keywords: '',
    description: '',
    is_enabled: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑塔罗牌'
  Object.assign(form, row)
  dialogVisible.value = true
}

// 提交
const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    const res = await saveTarotCard(form)
    if (res.code === 0) {
      ElMessage.success('保存成功')
      dialogVisible.value = false
      loadData()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    submitLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.tarot-cards-manager {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>