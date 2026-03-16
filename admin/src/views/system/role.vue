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
              <el-tag size="small" :type="role.status ? 'success' : 'info'" class="role-tag">
                {{ role.status ? '启用' : '禁用' }}
              </el-tag>
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
import { ElMessage } from 'element-plus'

const treeRef = ref(null)
const loading = ref(false)

// TODO: 后端需要实现角色管理API接口
// GET /api/admin/system/roles - 获取角色列表
// POST /api/admin/system/roles - 创建角色
// PUT /api/admin/system/roles/:id - 更新角色
// DELETE /api/admin/system/roles/:id - 删除角色
// GET /api/admin/system/roles/:id/permissions - 获取角色权限
// PUT /api/admin/system/roles/:id/permissions - 更新角色权限
// GET /api/admin/system/permissions - 获取所有权限树

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
    // TODO: 调用后端API获取角色列表
    // const res = await getRoleList()
    // if (res.code === 200) {
    //   roleList.value = res.data
    // }

    // 临时使用模拟数据，等待后端接口
    roleList.value = [
      { id: 1, name: '超级管理员', code: 'super_admin', status: 1 },
      { id: 2, name: '运营管理员', code: 'operation', status: 1 },
      { id: 3, name: '客服人员', code: 'customer_service', status: 1 },
      { id: 4, name: '审计人员', code: 'auditor', status: 0 }
    ]
  } catch (error) {
    ElMessage.error('加载角色列表失败')
  } finally {
    loading.value = false
  }
}

// 加载权限树
const loadPermissionTree = async () => {
  try {
    // TODO: 调用后端API获取权限树
    // const res = await getPermissionTree()
    // if (res.code === 200) {
    //   permissionTree.value = res.data
    // }

    // 临时使用模拟数据，等待后端接口
    permissionTree.value = [
      {
        id: 'dashboard',
        name: '仪表盘',
        children: [
          { id: 'dashboard:view', name: '查看仪表盘' },
          { id: 'dashboard:export', name: '导出报表' }
        ]
      },
      {
        id: 'user',
        name: '用户管理',
        children: [
          { id: 'user:view', name: '查看用户' },
          { id: 'user:edit', name: '编辑用户' },
          { id: 'user:disable', name: '禁用/启用用户' },
          { id: 'user:points', name: '调整积分' }
        ]
      },
      {
        id: 'order',
        name: '订单管理',
        children: [
          { id: 'order:view', name: '查看订单' },
          { id: 'order:refund', name: '订单退款' },
          { id: 'order:complete', name: '手动补单' }
        ]
      },
      {
        id: 'content',
        name: '内容管理',
        children: [
          { id: 'content:view', name: '查看内容' },
          { id: 'content:delete', name: '删除内容' },
          { id: 'content:daily', name: '每日运势管理' }
        ]
      },
      {
        id: 'feedback',
        name: '反馈管理',
        children: [
          { id: 'feedback:view', name: '查看反馈' },
          { id: 'feedback:reply', name: '回复反馈' }
        ]
      },
      {
        id: 'system',
        name: '系统设置',
        children: [
          { id: 'system:settings', name: '基础配置' },
          { id: 'system:sensitive', name: '敏感词管理' },
          { id: 'system:notice', name: '系统公告' },
          { id: 'system:admin', name: '管理员管理' },
          { id: 'system:role', name: '角色权限管理' }
        ]
      }
    ]
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
    // TODO: 调用后端API获取角色权限
    // const res = await getRolePermissions(roleId)
    // if (res.code === 200) {
    //   selectedPermissions.value = res.data
    // }

    // 临时使用模拟数据，等待后端接口
    const mockPermissions = {
      1: ['dashboard:view', 'dashboard:export', 'user:view', 'user:edit', 'user:disable', 'user:points', 'order:view', 'order:refund', 'order:complete', 'content:view', 'content:delete', 'content:daily', 'feedback:view', 'feedback:reply', 'system:settings', 'system:sensitive', 'system:notice', 'system:admin', 'system:role'],
      2: ['dashboard:view', 'user:view', 'user:edit', 'order:view', 'content:view', 'feedback:view', 'feedback:reply'],
      3: ['dashboard:view', 'user:view', 'feedback:view', 'feedback:reply'],
      4: ['dashboard:view', 'user:view', 'content:view']
    }
    selectedPermissions.value = mockPermissions[roleId] || []
  } catch (error) {
    ElMessage.error('加载角色权限失败')
  }
}

// 新增角色
function handleAddRole() {
  dialog.isEdit = false
  dialog.form = { name: '', code: '', description: '', status: 1 }
  dialog.visible = true
}

// 提交角色
async function submitRole() {
  try {
    // TODO: 调用后端API保存角色
    // const api = dialog.isEdit ? updateRole : createRole
    // const res = await api(dialog.form)
    // if (res.code === 200) {
    //   ElMessage.success(dialog.isEdit ? '修改成功' : '新增成功')
    //   dialog.visible = false
    //   loadRoleList()
    // }

    // 临时模拟保存成功，等待后端接口
    ElMessage.success(dialog.isEdit ? '修改成功' : '新增成功')
    dialog.visible = false
    // 重新加载列表
    await loadRoleList()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

// 展开/收起全部
function handleExpandAll() {
  isAllExpanded.value = !isAllExpanded.value
  // 实现展开/收起逻辑
}

// 保存权限
async function handleSavePermission() {
  if (!selectedRole.value) {
    ElMessage.warning('请先选择角色')
    return
  }
  const checkedKeys = treeRef.value?.getCheckedKeys()
  const halfCheckedKeys = treeRef.value?.getHalfCheckedKeys()
  const allKeys = [...checkedKeys, ...halfCheckedKeys]

  try {
    // TODO: 调用后端API保存角色权限
    // const res = await saveRolePermissions(selectedRole.value.id, allKeys)
    // if (res.code === 200) {
    //   ElMessage.success('权限保存成功')
    // }

    // 临时模拟保存成功，等待后端接口
    console.log('保存权限:', allKeys)
    ElMessage.success('权限保存成功')
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
    
    .role-tag {
      margin-left: auto;
    }
  }
}
</style>
