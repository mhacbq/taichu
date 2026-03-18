<template>
  <div class="app-container">
    <el-row :gutter="20">
      <!-- 敏感词列表 -->
      <el-col :lg="16">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>敏感词列表</span>
              <div>
                <el-button type="primary" @click="handleAdd">
                  <el-icon><Plus /></el-icon>添加敏感词
                </el-button>
                <el-button @click="handleImport">批量导入</el-button>
              </div>
            </div>
          </template>

          <el-table :data="wordList" v-loading="loading" stripe>
            <el-table-column type="index" label="#" width="50" />
            <el-table-column prop="word" label="敏感词" min-width="150">
              <template #default="{ row }">
                <el-tag type="danger" effect="dark">{{ row.word }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="type" label="类型" width="120">
              <template #default="{ row }">
                <el-tag :type="row.type === 'illegal' ? 'danger' : 'warning'">
                  {{ row.type === 'illegal' ? '违规词' : '敏感词' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="replacement" label="替换为" width="150">
              <template #default="{ row }">
                <span v-if="row.replacement">{{ row.replacement }}</span>
                <span v-else class="text-gray">***</span>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="添加时间" width="160" />
            <el-table-column label="操作" width="120" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
                <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-container">
            <el-pagination
              v-model:current-page="query.page"
              v-model:page-size="query.pageSize"
              :total="total"
              layout="total, prev, pager, next"
              @current-change="loadWordList"
            />
          </div>
        </el-card>
      </el-col>

      <!-- 分类统计 -->
      <el-col :lg="8">
        <el-card>
          <template #header>
            <span>分类统计</span>
          </template>
          <div class="stats-list">
            <div class="stats-item">
              <span class="label">违规词</span>
              <span class="value danger">{{ stats.illegal }}个</span>
            </div>
            <div class="stats-item">
              <span class="label">敏感词</span>
              <span class="value warning">{{ stats.sensitive }}个</span>
            </div>
            <div class="stats-item">
              <span class="label">总计</span>
              <span class="value">{{ stats.total }}个</span>
            </div>
          </div>
        </el-card>

        <el-card style="margin-top: 20px;">
          <template #header>
            <span>检测测试</span>
          </template>
          <el-input
            v-model="testText"
            type="textarea"
            rows="4"
            placeholder="输入文本进行敏感词检测测试..."
          />
          <el-button type="primary" style="margin-top: 10px; width: 100%;" @click="testDetection">
            检测
          </el-button>
          <div v-if="testResult" class="test-result" style="margin-top: 15px;">
            <el-alert
              :title="testResult.hasSensitive ? '检测到敏感词' : '未检测到敏感词'"
              :type="testResult.hasSensitive ? 'error' : 'success'"
              :description="testResult.words?.join(', ')"
              show-icon
            />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 添加/编辑弹窗 -->
    <el-dialog v-model="dialog.visible" :title="dialog.isEdit ? '编辑敏感词' : '添加敏感词'" width="500px">
      <el-form :model="dialog.form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="敏感词" prop="word">
          <el-input v-model="dialog.form.word" placeholder="请输入敏感词" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="dialog.form.type">
            <el-radio label="illegal">违规词（拦截）</el-radio>
            <el-radio label="sensitive">敏感词（替换）</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="替换为">
          <el-input v-model="dialog.form.replacement" placeholder="留空则替换为***" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="dialog.form.remark" type="textarea" rows="3" placeholder="可选" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量导入弹窗 -->
    <el-dialog v-model="importDialog.visible" title="批量导入敏感词" width="500px">
      <el-input
        v-model="importDialog.words"
        type="textarea"
        rows="10"
        placeholder="每行一个敏感词，格式：敏感词|类型|替换词&#10;例如：&#10;赌博|illegal|&#10;敏感词|sensitive|***"
      />
      <template #footer>
        <el-button @click="importDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="submitImport">导入</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getSensitiveWords, addSensitiveWord, updateSensitiveWord, deleteSensitiveWord, importSensitiveWords } from '@/api/system'
import { reportAdminUiError } from '@/utils/dev-error'

const loading = ref(false)
const wordList = ref([])
const total = ref(0)
const testText = ref('')
const testResult = ref(null)
const formRef = ref(null)

const query = reactive({
  page: 1,
  pageSize: 20
})

const stats = reactive({
  illegal: 0,
  sensitive: 0,
  total: 0
})

const dialog = reactive({
  visible: false,
  isEdit: false,
  form: {
    word: '',
    type: 'sensitive',
    replacement: '',
    remark: ''
  }
})

const importDialog = reactive({
  visible: false,
  words: ''
})

const rules = {
  word: [{ required: true, message: '请输入敏感词', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }]
}

onMounted(() => {
  loadWordList()
})

async function loadWordList() {
  loading.value = true
  try {
    const { data } = await getSensitiveWords(query)
    wordList.value = data.list
    total.value = data.total
    stats.illegal = data.stats?.illegal || 0
    stats.sensitive = data.stats?.sensitive || 0
    stats.total = data.total
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  dialog.isEdit = false
  dialog.form = {
    word: '',
    type: 'sensitive',
    replacement: '',
    remark: ''
  }
  dialog.visible = true
}

function handleEdit(row) {
  dialog.isEdit = true
  dialog.form = { ...row }
  dialog.visible = true
}

async function submitForm() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  try {
    if (dialog.isEdit) {
      await updateSensitiveWord(dialog.form.id, dialog.form)
    } else {
      await addSensitiveWord(dialog.form)
    }
    ElMessage.success(dialog.isEdit ? '修改成功' : '添加成功')
    dialog.visible = false
    loadWordList()
  } catch (error) {
    console.error(error)
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm('确定删除该敏感词吗？', '提示', { type: 'warning' })
    await deleteSensitiveWord(row.id)
    ElMessage.success('删除成功')
    loadWordList()
  } catch {
    // 取消删除
  }
}

function handleImport() {
  importDialog.words = ''
  importDialog.visible = true
}

async function submitImport() {
  if (!importDialog.words.trim()) {
    ElMessage.warning('请输入敏感词')
    return
  }

  try {
    await importSensitiveWords({ words: importDialog.words })
    ElMessage.success('导入成功')
    importDialog.visible = false
    loadWordList()
  } catch (error) {
    reportAdminUiError('system_sensitive', 'import_words_failed', error, {
      batch_size: importDialog.words
        .split(/\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean).length
    })
  }
}

function testDetection() {
  if (!testText.value.trim()) {
    ElMessage.warning('请输入测试文本')
    return
  }

  // 模拟检测结果
  const hasSensitive = wordList.value.some(w => testText.value.includes(w.word))
  const words = wordList.value
    .filter(w => testText.value.includes(w.word))
    .map(w => w.word)

  testResult.value = { hasSensitive, words }
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stats-list {
  .stats-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #ebeef5;
    
    &:last-child {
      border-bottom: none;
    }
    
    .label {
      color: #606266;
    }
    
    .value {
      font-size: 18px;
      font-weight: 600;
      
      &.danger {
        color: #f56c6c;
      }
      
      &.warning {
        color: #e6a23c;
      }
    }
  }
}

.text-gray {
  color: #909399;
}
</style>
