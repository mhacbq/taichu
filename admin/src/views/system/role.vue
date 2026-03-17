<template>
  <div class="app-container">
    <el-row :gutter="20">
      <!-- 角色列表 -->
      <el-col :lg="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>角色列表</span>
              <el-button type="primary" size="small" @click="handleAddRole">
                <el-icon><Plus /></el-icon>新增
              </el-button>
            </div>
          </template>
          
          <el-menu
            :default-active="selectedRole?.id?.toString()"
            class="role-menu"
            @select="handleRoleSelect"
          >
            <el-menu-item
              v-for="role in roleList"
              :key="role.id"
              :index="role.id.toString()"
            >
              <el-icon><UserFilled /></el-icon>
              <span>{{ role.name }}</span>
              <div class="role-actions">
                <el-button type="primary" link @click.stop="handleEditRole(role)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button type="danger" link @click.stop="handleDeleteRole(role)">
                  <el-icon><Delete /></el-icon>
                </el-button>
                <el-tag size="small" :type="role.status ? 'success' : 'info'" class="role-tag">
                  {{ role.status ? '启用' : '禁用' }}
                </el-tag>
              </div>
            </el-menu-item>
          </el-menu>
        </el-card>
      </el-col>
      
      <!-- 权限配置 -->
      <el-col :lg="16">
        <el-card v-if="selectedRole">
          <template #header>
            <div class="card-header">
              <span>{{ selectedRole.name }} - 权限配置</span>
              <div>
                <el-button @click="handleExpandAll">
                  {{ isAllExpanded ? '全部收起' : '全部展开' }}
                </el-button>
                <el-button type="primary" @click="handleSavePermission">保存权限</el-button>
              </div>
            </div>
          </template>
          
          <el-tree
            ref="treeRef"
            :data="permissionTree"
            show-checkbox
            node-key="id"
            :default-expand-all="isAllExpanded"
            :props="{ label: 'name', children: 'children' }"
            :default-checked-keys="selectedPermissions"
          />
        </el-card>
        
        <el-empty v-else description="请选择左侧角色进行配置" />
      </el-col>
    </el-row>

    <!-- 角色编辑弹窗 -->
    <el-dialog v-model="dialog.visible" :title="dialog.isEdit ? '编辑角色' : '新增角色'" width="500px">
      <el-form :model="dialog.form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="dialog.form.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="角色编码" prop="code">
          <el-input v-model="dialog.form.code" placeholder="请输入角色编码" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="dialog.form.description" type="textarea" rows="3" placeholder="请输入角色描述" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="dialog.form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitRole">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  getRoles, createRole, updateRole, deleteRole, 
  getPermissions, getRolePermissions, updateRolePermissions 
} from '../../api/system'
import { Plus, UserFilled, Edit, Delete } from '@element-plus/icons-vue'

const treeRef = ref(null)
const loading = ref(false)

// 角色列表
const roleList = ref([])

// 选中的角色
const selectedRole = ref(null)

// 权限树
const permissionTree = ref([])

// 加载角色列表
const loadRoleList = async () => {
  loading.value = true
  try {
    const res = await getRoles()
    if (res.code === 200) {
      roleList.value = res.data
      if (roleList.value.length > 0 && !selectedRole.value) {
        handleRoleSelect(roleList.value[0].id.toString())
      }
    }
  } catch (error) {
    console.error(error)
    ElMessage.error('加载角色列表失败')
  } finally {
    loading.value = false
  }
}

// 加载权限树
const loadPermissionTree = async () => {
  try {
    const res = await getPermissions()
    if (res.code === 200) {
      permissionTree.value = res.data
    }
  } catch (error) {
    ElMessage.error('加载权限树失败')
  }
}

// 已选权限
const selectedPermissions = ref([])
const isAllExpanded = ref(true)

// 弹窗
const dialog = reactive({
  visible: false,
  isEdit: false,
  form: {
    id: null,
    name: '',
    code: '',
    description: '',
    status: 1
  }
})

const rules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色编码', trigger: 'blur' }]
}

// 选择角色
async function handleRoleSelect(index) {
  selectedRole.value = roleList.value.find(r => r.id.toString() === index)
  if (selectedRole.value) {
    // 加载该角色的权限
    await loadRolePermissions(selectedRole.value.id)
  }
}

// 加载角色权限
const loadRolePermissions = async (roleId) => {
  try {
    const res = await getRolePermissions(roleId)
    if (res.code === 200) {
      selectedPermissions.value = res.data
      treeRef.value?.setCheckedKeys(res.data)
    }
  } catch (error) {
    ElMessage.error('加载角色权限失败')
  }
}

// 新增角色
function handleAddRole() {
  dialog.isEdit = false
  dialog.form = { id: null, name: '', code: '', description: '', status: 1 }
  dialog.visible = true
}

// 编辑角色
function handleEditRole(role) {
  dialog.isEdit = true
  dialog.form = { ...role }
  dialog.visible = true
}

// 删除角色
async function handleDeleteRole(role) {
  try {
    await ElMessageBox.confirm(`确定要删除角色 ${role.name} 吗？`, '提示', { type: 'warning' })
    const res = await deleteRole(role.id)
    if (res.code === 200) {
      ElMessage.success('删除成功')
      if (selectedRole.value?.id === role.id) {
        selectedRole.value = null
      }
      loadRoleList()
    }
  } catch (error) {
    if (error !== 'cancel') ElMessage.error('删除失败')
  }
}

// 提交角色
async function submitRole() {
  try {
    const res = dialog.isEdit 
      ? await updateRole(dialog.form.id, dialog.form)
      : await createRole(dialog.form)
      
    if (res.code === 200) {
      ElMessage.success(dialog.isEdit ? '修改成功' : '新增成功')
      dialog.visible = false
      loadRoleList()
    }
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 展开/收起全部
function handleExpandAll() {
  isAllExpanded.value = !isAllExpanded.value
  const nodes = treeRef.value?.store.nodesMap
  for (const i in nodes) {
    nodes[i].expanded = isAllExpanded.value
  }
}

// 保存权限
async function handleSavePermission() {
  if (!selectedRole.value) {
    ElMessage.warning('请先选择角色')
    return
  }
  const checkedKeys = treeRef.value?.getCheckedKeys()
  // const halfCheckedKeys = treeRef.value?.getHalfCheckedKeys()
  // 只保存叶子节点或显式选中的ID，后端会处理父子关系或仅存储权限ID
  const allKeys = [...checkedKeys]

  try {
    const res = await updateRolePermissions(selectedRole.value.id, allKeys)
    if (res.code === 200) {
      ElMessage.success('权限保存成功')
    }
  } catch (error) {
    ElMessage.error('保存权限失败')
  }
}


// 页面加载时初始化
onMounted(() => {
  loadRoleList()
  loadPermissionTree()
})
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.role-menu {
  border-right: none;
  
  :deep(.el-menu-item) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px;
    
    .role-actions {
      display: flex;
      align-items: center;
      gap: 5px;
      margin-left: auto;
    }
    
    .role-tag {
      margin-left: 10px;
    }
  }
}
</style>
