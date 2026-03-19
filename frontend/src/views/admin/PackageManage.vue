<template>
  <div class="package-manage">
    <div class="page-header">
      <div class="header-desc">管理充值套餐与积分包，配置价格、积分数量、赠送比例等参数。</div>
      <el-button type="primary" :icon="Plus" @click="openDialog()">新增套餐</el-button>
    </div>

    <el-row :gutter="16" v-loading="loading" class="package-grid">
      <el-col v-for="pkg in packages" :key="pkg.id" :xs="24" :sm="12" :md="8" :lg="6">
        <el-card class="package-card" :class="{ 'is-recommended': pkg.is_recommended, 'is-disabled': !pkg.is_active }" shadow="hover">
          <div class="pkg-header">
            <span class="pkg-name">{{ pkg.name }}</span>
            <el-tag v-if="pkg.is_recommended" type="warning" size="small" effect="dark">推荐</el-tag>
            <el-tag v-if="!pkg.is_active" type="info" size="small">已停用</el-tag>
          </div>
          <div class="pkg-price">¥{{ pkg.price }}</div>
          <div class="pkg-points">
            <span class="points-main">{{ pkg.points }} 积分</span>
            <span v-if="pkg.bonus_points" class="points-bonus">+{{ pkg.bonus_points }} 赠送</span>
          </div>
          <div class="pkg-desc">{{ pkg.description || '暂无描述' }}</div>
          <div class="pkg-actions">
            <el-button size="small" @click="openDialog(pkg)">编辑</el-button>
            <el-button size="small" :type="pkg.is_active ? 'warning' : 'success'" @click="toggleActive(pkg)">
              {{ pkg.is_active ? '停用' : '启用' }}
            </el-button>
            <el-popconfirm title="确定删除此套餐?" @confirm="deletePackage(pkg.id)">
              <template #reference>
                <el-button size="small" type="danger">删除</el-button>
              </template>
            </el-popconfirm>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="24" :sm="12" :md="8" :lg="6">
        <div class="add-card" @click="openDialog()">
          <el-icon size="32"><Plus /></el-icon>
          <span>新增套餐</span>
        </div>
      </el-col>
    </el-row>

    <!-- 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑套餐' : '新增套餐'" width="520px" destroy-on-close>
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="套餐名称" prop="name">
          <el-input v-model="form.name" placeholder="如：基础包、月卡、年卡" />
        </el-form-item>
        <el-form-item label="价格(元)" prop="price">
          <el-input-number v-model="form.price" :min="0" :precision="2" :step="1" style="width:100%" />
        </el-form-item>
        <el-form-item label="基础积分" prop="points">
          <el-input-number v-model="form.points" :min="1" :step="100" style="width:100%" />
        </el-form-item>
        <el-form-item label="赠送积分">
          <el-input-number v-model="form.bonus_points" :min="0" :step="10" style="width:100%" />
          <div class="form-tip">购买后额外赠送的积分，设0则不赠送</div>
        </el-form-item>
        <el-form-item label="套餐描述">
          <el-input v-model="form.description" type="textarea" :rows="2" placeholder="简短描述此套餐的特点" />
        </el-form-item>
        <el-form-item label="有效期(天)">
          <el-input-number v-model="form.validity_days" :min="0" style="width:100%" placeholder="0表示永久有效" />
        </el-form-item>
        <el-form-item label="排序权重">
          <el-input-number v-model="form.sort_order" :min="0" style="width:100%" />
          <div class="form-tip">数字越大越靠前</div>
        </el-form-item>
        <el-row>
          <el-col :span="12">
            <el-form-item label="推荐套餐">
              <el-switch v-model="form.is_recommended" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="启用">
              <el-switch v-model="form.is_active" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="savePackageItem">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getPackages, savePackage } from '@/api/admin'

const packages = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref(null)

const form = reactive({
  id: null,
  name: '',
  price: 0,
  points: 100,
  bonus_points: 0,
  description: '',
  validity_days: 0,
  sort_order: 0,
  is_recommended: false,
  is_active: true,
})

const rules = {
  name: [{ required: true, message: '请输入套餐名称' }],
  price: [{ required: true, message: '请设置价格' }],
  points: [{ required: true, message: '请设置积分数量' }],
}

const loadPackages = async () => {
  loading.value = true
  try {
    const res = await getPackages()
    packages.value = (res.data || []).sort((a, b) => (b.sort_order || 0) - (a.sort_order || 0))
  } catch { ElMessage.error('加载套餐失败') } finally { loading.value = false }
}

const openDialog = (pkg = null) => {
  if (pkg) {
    Object.assign(form, { id: pkg.id, name: pkg.name, price: pkg.price, points: pkg.points, bonus_points: pkg.bonus_points || 0, description: pkg.description || '', validity_days: pkg.validity_days || 0, sort_order: pkg.sort_order || 0, is_recommended: !!pkg.is_recommended, is_active: !!pkg.is_active })
  } else {
    Object.assign(form, { id: null, name: '', price: 0, points: 100, bonus_points: 0, description: '', validity_days: 0, sort_order: 0, is_recommended: false, is_active: true })
  }
  dialogVisible.value = true
}

const savePackageItem = async () => {
  await formRef.value.validate()
  saving.value = true
  try {
    await savePackage({ ...form })
    ElMessage.success('保存成功')
    dialogVisible.value = false
    loadPackages()
  } catch { ElMessage.error('保存失败') } finally { saving.value = false }
}

const toggleActive = async (pkg) => {
  try {
    await savePackage({ id: pkg.id, is_active: !pkg.is_active })
    ElMessage.success(pkg.is_active ? '已停用' : '已启用')
    loadPackages()
  } catch { ElMessage.error('操作失败') }
}

const deletePackage = async (id) => {
  try {
    await savePackage({ id, _delete: true })
    ElMessage.success('删除成功')
    loadPackages()
  } catch { ElMessage.error('删除失败') }
}

onMounted(loadPackages)
</script>

<style scoped>
.package-manage { padding: 0; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.header-desc { color: #909399; font-size: 13px; }
.package-grid { margin-top: 0; }
.package-card { border-radius: 12px; transition: all .2s; }
.package-card.is-recommended { border-color: #e6a23c; }
.package-card.is-disabled { opacity: .6; }
.pkg-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.pkg-name { font-size: 16px; font-weight: 600; color: #303133; }
.pkg-price { font-size: 32px; font-weight: 700; color: #f56c6c; margin-bottom: 8px; }
.pkg-points { margin-bottom: 8px; }
.points-main { font-size: 15px; color: #409eff; font-weight: 600; }
.points-bonus { font-size: 12px; color: #67c23a; margin-left: 6px; }
.pkg-desc { font-size: 13px; color: #909399; min-height: 36px; margin-bottom: 12px; }
.pkg-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.add-card { height: 200px; border: 2px dashed #dcdfe6; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #c0c4cc; cursor: pointer; transition: all .2s; margin-bottom: 16px; }
.add-card:hover { border-color: #409eff; color: #409eff; }
.form-tip { font-size: 12px; color: #c0c4cc; margin-top: 4px; }
</style>
