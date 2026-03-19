<template>
  <div class="system-tools">
    <el-tabs v-model="activeTab">
      <!-- 公告管理 -->
      <el-tab-pane label="📢 公告管理" name="notices">
        <div class="tab-toolbar">
          <el-button type="primary" :icon="Plus" @click="openNoticeDialog()">发布公告</el-button>
        </div>
        <el-table :data="noticeList" v-loading="noticeLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
          <el-table-column prop="type" label="类型" width="100">
            <template #default="{ row }">
              <el-tag :type="noticeTypeMap[row.type]?.tag || 'info'" size="small">
                {{ noticeTypeMap[row.type]?.label || row.type }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="is_active" label="状态" width="80">
            <template #default="{ row }">
              <el-tag :type="row.is_active ? 'success' : 'info'" size="small">{{ row.is_active ? '启用' : '停用' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="start_time" label="开始时间" width="150" />
          <el-table-column prop="end_time" label="结束时间" width="150" />
          <el-table-column prop="created_at" label="创建时间" width="150" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openNoticeDialog(row)">编辑</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteNoticeItem(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- 敏感词管理 -->
      <el-tab-pane label="🚫 敏感词" name="sensitive">
        <div class="tab-toolbar">
          <el-input v-model="sensitiveKeyword" placeholder="搜索敏感词" clearable style="width:200px" />
          <el-button :icon="Search" @click="loadSensitive">搜索</el-button>
          <el-button type="primary" :icon="Plus" @click="openSensitiveDialog()">新增</el-button>
          <el-button :icon="Upload" @click="importDialogVisible = true">批量导入</el-button>
        </div>
        <el-table :data="sensitiveList" v-loading="sensitiveLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="word" label="敏感词" width="200" />
          <el-table-column prop="category" label="分类" width="120">
            <template #default="{ row }">
              <el-tag size="small">{{ row.category || '默认' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="level" label="级别" width="80">
            <template #default="{ row }">
              <el-tag :type="row.level >= 3 ? 'danger' : row.level >= 2 ? 'warning' : 'info'" size="small">
                {{ ['低', '中', '高', '极高'][row.level - 1] || '未知' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="replace_with" label="替换为" width="100">
            <template #default="{ row }">{{ row.replace_with || '***' }}</template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="150" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openSensitiveDialog(row)">编辑</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteSensitive(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination v-model:current-page="sensitivePage" v-model:page-size="sensitivePageSize"
          :total="sensitiveTotal" layout="total,sizes,prev,pager,next" @change="loadSensitive"
          style="margin-top:16px;justify-content:flex-end" />
      </el-tab-pane>

      <!-- 管理员账号 -->
      <el-tab-pane label="👤 管理员" name="admins">
        <div class="tab-toolbar">
          <el-button type="primary" :icon="Plus" @click="openAdminDialog()">新增管理员</el-button>
        </div>
        <el-table :data="adminList" v-loading="adminLoading" stripe border>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="username" label="用户名" width="140" />
          <el-table-column prop="email" label="邮箱" min-width="180" />
          <el-table-column prop="role_names" label="角色" width="160">
            <template #default="{ row }">
              <el-tag v-for="r in (row.role_names || [])" :key="r" size="small" style="margin:2px">{{ r }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="status" label="状态" width="80">
            <template #default="{ row }">
              <el-tag :type="row.status ? 'success' : 'danger'" size="small">{{ row.status ? '启用' : '禁用' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="last_login_at" label="最近登录" width="150" />
          <el-table-column label="操作" width="120" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="openAdminDialog(row)">编辑</el-button>
              <el-popconfirm title="确定删除?" @confirm="deleteAdminItem(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">删除</el-button>
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>

      <!-- 短信管理 -->
      <el-tab-pane label="📱 短信" name="sms">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-card header="短信服务配置" shadow="never">
              <el-form :model="smsConfig" label-width="100px" v-loading="smsLoading">
                <el-form-item label="服务商">
                  <el-select v-model="smsConfig.provider" style="width:100%">
                    <el-option label="腾讯云" value="tencent" />
                    <el-option label="阿里云" value="aliyun" />
                  </el-select>
                </el-form-item>
                <el-form-item label="AppID">
                  <el-input v-model="smsConfig.app_id" placeholder="应用ID" />
                </el-form-item>
                <el-form-item label="AppKey">
                  <el-input v-model="smsConfig.app_key" type="password" show-password placeholder="应用密钥" />
                </el-form-item>
                <el-form-item label="签名">
                  <el-input v-model="smsConfig.sign" placeholder="短信签名" />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="smsSaving" @click="saveSms">保存配置</el-button>
                  <el-button @click="testSms">发送测试</el-button>
                </el-form-item>
              </el-form>
            </el-card>
          </el-col>
          <el-col :span="12">
            <el-card shadow="never">
              <template #header>
                <span>发送统计</span>
              </template>
              <el-row :gutter="12" v-loading="smsStatsLoading">
                <el-col :span="12">
                  <div class="stat-item">
                    <div class="stat-num">{{ smsStats.today_count || 0 }}</div>
                    <div class="stat-label">今日发送</div>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="stat-item">
                    <div class="stat-num">{{ smsStats.month_count || 0 }}</div>
                    <div class="stat-label">本月发送</div>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="stat-item success">
                    <div class="stat-num">{{ smsStats.success_rate || '-' }}</div>
                    <div class="stat-label">成功率</div>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="stat-item danger">
                    <div class="stat-num">{{ smsStats.fail_count || 0 }}</div>
                    <div class="stat-label">失败次数</div>
                  </div>
                </el-col>
              </el-row>
            </el-card>
          </el-col>
        </el-row>
        <!-- 发送记录 -->
        <el-card style="margin-top:16px" shadow="never" header="发送记录">
          <el-table :data="smsRecords" v-loading="smsRecordsLoading" stripe border>
            <el-table-column prop="phone" label="手机号" width="130" />
            <el-table-column prop="template" label="模板" width="120" />
            <el-table-column prop="content" label="内容" min-width="200" show-overflow-tooltip />
            <el-table-column prop="status" label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="row.status === 'success' ? 'success' : 'danger'" size="small">
                  {{ row.status === 'success' ? '成功' : '失败' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="发送时间" width="150" />
          </el-table>
          <el-pagination v-model:current-page="smsRecordsPage" :page-size="15" :total="smsRecordsTotal"
            layout="total,prev,pager,next" @change="loadSmsRecords" style="margin-top:12px;justify-content:flex-end" />
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <!-- 公告弹窗 -->
    <el-dialog v-model="noticeDialogVisible" :title="noticeForm.id ? '编辑公告' : '发布公告'" width="560px" destroy-on-close>
      <el-form :model="noticeForm" :rules="noticeRules" ref="noticeFormRef" label-width="90px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="noticeForm.title" placeholder="公告标题" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="noticeForm.type" style="width:100%">
            <el-option label="通知" value="info" />
            <el-option label="警告" value="warning" />
            <el-option label="维护" value="maintenance" />
            <el-option label="活动" value="activity" />
          </el-select>
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <el-input v-model="noticeForm.content" type="textarea" :rows="4" placeholder="公告内容" />
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker v-model="noticeForm.timeRange" type="datetimerange"
            range-separator="至" start-placeholder="开始" end-placeholder="结束" style="width:100%" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="noticeForm.is_active" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="noticeDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="noticeSaving" @click="saveNoticeItem">保存</el-button>
      </template>
    </el-dialog>

    <!-- 敏感词弹窗 -->
    <el-dialog v-model="sensitiveDialogVisible" :title="sensitiveForm.id ? '编辑敏感词' : '新增敏感词'" width="480px" destroy-on-close>
      <el-form :model="sensitiveForm" :rules="sensitiveRules" ref="sensitiveFormRef" label-width="90px">
        <el-form-item label="敏感词" prop="word">
          <el-input v-model="sensitiveForm.word" placeholder="输入敏感词" />
        </el-form-item>
        <el-form-item label="分类">
          <el-input v-model="sensitiveForm.category" placeholder="如：政治、广告、色情" />
        </el-form-item>
        <el-form-item label="级别">
          <el-select v-model="sensitiveForm.level" style="width:100%">
            <el-option :value="1" label="低" />
            <el-option :value="2" label="中" />
            <el-option :value="3" label="高" />
            <el-option :value="4" label="极高" />
          </el-select>
        </el-form-item>
        <el-form-item label="替换为">
          <el-input v-model="sensitiveForm.replace_with" placeholder="留空则用***替换" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="sensitiveDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="sensitiveSaving" @click="saveSensitiveItem">保存</el-button>
      </template>
    </el-dialog>

    <!-- 批量导入敏感词弹窗 -->
    <el-dialog v-model="importDialogVisible" title="批量导入敏感词" width="480px">
      <el-alert type="info" :closable="false" style="margin-bottom:12px">
        每行一个敏感词，支持 txt/csv 格式
      </el-alert>
      <el-input v-model="importContent" type="textarea" :rows="8" placeholder="每行一个敏感词..." />
      <template #footer>
        <el-button @click="importDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="importing" @click="doImport">导入</el-button>
      </template>
    </el-dialog>

    <!-- 管理员弹窗 -->
    <el-dialog v-model="adminDialogVisible" :title="adminForm.id ? '编辑管理员' : '新增管理员'" width="480px" destroy-on-close>
      <el-form :model="adminForm" :rules="adminRules" ref="adminFormRef" label-width="90px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="adminForm.username" :disabled="!!adminForm.id" />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="adminForm.email" type="email" />
        </el-form-item>
        <el-form-item :label="adminForm.id ? '新密码' : '密码'" :prop="adminForm.id ? undefined : 'password'">
          <el-input v-model="adminForm.password" type="password" show-password :placeholder="adminForm.id ? '留空不修改' : '设置密码'" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="adminForm.status" active-text="启用" inactive-text="禁用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adminDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="adminSaving" @click="saveAdminItem">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Plus, Upload, Refresh } from '@element-plus/icons-vue'
import {
  getNotices, saveNotice, deleteNotice,
  getSensitiveWords, createSensitiveWord, updateSensitiveWord, deleteSensitiveWord, importSensitiveWords,
  getAdminUsers, saveAdminUser, deleteAdminUser,
  getSmsConfig, saveSmsConfig, testSmsSend, getSmsStats, getSmsRecords
} from '@/api/admin'

const activeTab = ref('notices')

const noticeTypeMap = {
  info: { label: '通知', tag: 'info' },
  warning: { label: '警告', tag: 'warning' },
  maintenance: { label: '维护', tag: 'danger' },
  activity: { label: '活动', tag: 'success' },
}

// ── 公告 ─────────────────────────────────────────────
const noticeList = ref([])
const noticeLoading = ref(false)
const noticeDialogVisible = ref(false)
const noticeSaving = ref(false)
const noticeFormRef = ref(null)
const noticeForm = reactive({ id: null, title: '', type: 'info', content: '', timeRange: null, is_active: true })
const noticeRules = {
  title: [{ required: true, message: '请输入公告标题' }],
  type: [{ required: true, message: '请选择类型' }],
  content: [{ required: true, message: '请输入公告内容' }],
}

const loadNotices = async () => {
  noticeLoading.value = true
  try {
    const res = await getNotices()
    noticeList.value = res.data?.list || res.data || []
  } catch { ElMessage.error('加载失败') } finally { noticeLoading.value = false }
}

const openNoticeDialog = (row = null) => {
  if (row) {
    Object.assign(noticeForm, { id: row.id, title: row.title, type: row.type, content: row.content, timeRange: row.start_time ? [row.start_time, row.end_time] : null, is_active: !!row.is_active })
  } else {
    Object.assign(noticeForm, { id: null, title: '', type: 'info', content: '', timeRange: null, is_active: true })
  }
  noticeDialogVisible.value = true
}

const saveNoticeItem = async () => {
  await noticeFormRef.value.validate()
  noticeSaving.value = true
  try {
    const payload = { ...noticeForm, start_time: noticeForm.timeRange?.[0] || null, end_time: noticeForm.timeRange?.[1] || null }
    delete payload.timeRange
    await saveNotice(payload)
    ElMessage.success('保存成功')
    noticeDialogVisible.value = false
    loadNotices()
  } catch { ElMessage.error('保存失败') } finally { noticeSaving.value = false }
}

const deleteNoticeItem = async (id) => {
  try {
    await deleteNotice(id)
    ElMessage.success('删除成功')
    loadNotices()
  } catch { ElMessage.error('删除失败') }
}

// ── 敏感词 ───────────────────────────────────────────
const sensitiveList = ref([])
const sensitiveTotal = ref(0)
const sensitivePage = ref(1)
const sensitivePageSize = ref(20)
const sensitiveKeyword = ref('')
const sensitiveLoading = ref(false)
const sensitiveDialogVisible = ref(false)
const sensitiveSaving = ref(false)
const sensitiveFormRef = ref(null)
const sensitiveForm = reactive({ id: null, word: '', category: '', level: 2, replace_with: '' })
const sensitiveRules = { word: [{ required: true, message: '请输入敏感词' }] }
const importDialogVisible = ref(false)
const importContent = ref('')
const importing = ref(false)

const loadSensitive = async () => {
  sensitiveLoading.value = true
  try {
    const res = await getSensitiveWords({ page: sensitivePage.value, pageSize: sensitivePageSize.value, keyword: sensitiveKeyword.value })
    sensitiveList.value = res.data?.list || res.data || []
    sensitiveTotal.value = res.data?.total || 0
  } catch { ElMessage.error('加载失败') } finally { sensitiveLoading.value = false }
}

const openSensitiveDialog = (row = null) => {
  if (row) Object.assign(sensitiveForm, { id: row.id, word: row.word, category: row.category || '', level: row.level || 2, replace_with: row.replace_with || '' })
  else Object.assign(sensitiveForm, { id: null, word: '', category: '', level: 2, replace_with: '' })
  sensitiveDialogVisible.value = true
}

const saveSensitiveItem = async () => {
  await sensitiveFormRef.value.validate()
  sensitiveSaving.value = true
  try {
    if (sensitiveForm.id) await updateSensitiveWord(sensitiveForm.id, sensitiveForm)
    else await createSensitiveWord(sensitiveForm)
    ElMessage.success('保存成功')
    sensitiveDialogVisible.value = false
    loadSensitive()
  } catch { ElMessage.error('保存失败') } finally { sensitiveSaving.value = false }
}

const deleteSensitive = async (id) => {
  try {
    await deleteSensitiveWord(id)
    ElMessage.success('删除成功')
    loadSensitive()
  } catch { ElMessage.error('删除失败') }
}

const doImport = async () => {
  if (!importContent.value.trim()) return ElMessage.warning('请输入要导入的内容')
  importing.value = true
  try {
    await importSensitiveWords({ words: importContent.value })
    ElMessage.success('导入成功')
    importDialogVisible.value = false
    importContent.value = ''
    loadSensitive()
  } catch { ElMessage.error('导入失败') } finally { importing.value = false }
}

// ── 管理员 ───────────────────────────────────────────
const adminList = ref([])
const adminLoading = ref(false)
const adminDialogVisible = ref(false)
const adminSaving = ref(false)
const adminFormRef = ref(null)
const adminForm = reactive({ id: null, username: '', email: '', password: '', status: true })
const adminRules = {
  username: [{ required: true, message: '请输入用户名' }],
  password: [{ required: true, message: '请设置密码' }],
}

const loadAdmins = async () => {
  adminLoading.value = true
  try {
    const res = await getAdminUsers()
    adminList.value = res.data || []
  } catch { ElMessage.error('加载失败') } finally { adminLoading.value = false }
}

const openAdminDialog = (row = null) => {
  if (row) Object.assign(adminForm, { id: row.id, username: row.username, email: row.email || '', password: '', status: !!row.status })
  else Object.assign(adminForm, { id: null, username: '', email: '', password: '', status: true })
  adminDialogVisible.value = true
}

const saveAdminItem = async () => {
  await adminFormRef.value.validate()
  adminSaving.value = true
  try {
    const payload = { ...adminForm }
    if (!payload.password) delete payload.password
    await saveAdminUser(payload)
    ElMessage.success('保存成功')
    adminDialogVisible.value = false
    loadAdmins()
  } catch { ElMessage.error('保存失败') } finally { adminSaving.value = false }
}

const deleteAdminItem = async (id) => {
  try {
    await deleteAdminUser(id)
    ElMessage.success('删除成功')
    loadAdmins()
  } catch { ElMessage.error('删除失败') }
}

// ── 短信 ─────────────────────────────────────────────
const smsConfig = reactive({ provider: 'tencent', app_id: '', app_key: '', sign: '' })
const smsLoading = ref(false)
const smsSaving = ref(false)
const smsStats = ref({})
const smsStatsLoading = ref(false)
const smsRecords = ref([])
const smsRecordsPage = ref(1)
const smsRecordsTotal = ref(0)
const smsRecordsLoading = ref(false)

const loadSmsConfig = async () => {
  smsLoading.value = true
  try {
    const res = await getSmsConfig()
    Object.assign(smsConfig, res.data || {})
  } catch {} finally { smsLoading.value = false }
}

const loadSmsStats = async () => {
  smsStatsLoading.value = true
  try {
    const res = await getSmsStats()
    smsStats.value = res.data || {}
  } catch {} finally { smsStatsLoading.value = false }
}

const loadSmsRecords = async () => {
  smsRecordsLoading.value = true
  try {
    const res = await getSmsRecords({ page: smsRecordsPage.value, pageSize: 15 })
    smsRecords.value = res.data?.list || []
    smsRecordsTotal.value = res.data?.total || 0
  } catch {} finally { smsRecordsLoading.value = false }
}

const saveSms = async () => {
  smsSaving.value = true
  try {
    await saveSmsConfig(smsConfig)
    ElMessage.success('配置保存成功')
  } catch { ElMessage.error('保存失败') } finally { smsSaving.value = false }
}

const testSms = async () => {
  try {
    await testSmsSend({ phone: '' })
    ElMessage.success('测试短信已发送')
  } catch { ElMessage.error('发送失败，请检查配置') }
}

onMounted(() => {
  loadNotices()
})
</script>

<style scoped>
.system-tools { padding: 0; }
.tab-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.stat-item { text-align: center; padding: 16px; background: #f5f7fa; border-radius: 8px; margin-bottom: 12px; }
.stat-item.success .stat-num { color: #67c23a; }
.stat-item.danger .stat-num { color: #f56c6c; }
.stat-num { font-size: 28px; font-weight: 700; color: #303133; line-height: 1.2; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
</style>
