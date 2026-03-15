<template>
  <div class="common-table">
    <el-table
      v-loading="loading"
      :data="data"
      :stripe="stripe"
      :border="border"
      :size="size"
      @selection-change="handleSelectionChange"
      @sort-change="handleSortChange"
    >
      <!-- 选择列 -->
      <el-table-column
        v-if="selection"
        type="selection"
        width="55"
        align="center"
      />
      
      <!-- 序号列 -->
      <el-table-column
        v-if="showIndex"
        type="index"
        label="#"
        width="50"
        align="center"
      />
      
      <!-- 自定义列 -->
      <template v-for="col in columns" :key="col.prop">
        <el-table-column
          :prop="col.prop"
          :label="col.label"
          :width="col.width"
          :min-width="col.minWidth"
          :sortable="col.sortable"
          :fixed="col.fixed"
          :align="col.align || 'left'"
          :show-overflow-tooltip="col.tooltip !== false"
        >
          <template #default="{ row, $index }">
            <!-- 自定义插槽 -->
            <slot
              v-if="col.slot"
              :name="col.prop"
              :row="row"
              :index="$index"
            >
              {{ getValue(row, col.prop) }}
            </slot>
            <!-- 图片显示 -->
            <el-image
              v-else-if="col.type === 'image'"
              :src="getValue(row, col.prop)"
              :preview-src-list="col.preview ? [getValue(row, col.prop)] : []"
              fit="cover"
              style="width: 50px; height: 50px; border-radius: 4px;"
            />
            <!-- 链接 -->
            <el-link
              v-else-if="col.type === 'link'"
              type="primary"
              @click="handleLinkClick(row, col)"
            >
              {{ getValue(row, col.prop) }}
            </el-link>
            <!-- 标签 -->
            <el-tag
              v-else-if="col.type === 'tag'"
              :type="getTagType(row, col)"
              :size="col.tagSize || 'small'"
              :effect="col.tagEffect || 'light'"
            >
              {{ getTagText(row, col) }}
            </el-tag>
            <!-- 开关 -->
            <el-switch
              v-else-if="col.type === 'switch'"
              v-model="row[col.prop]"
              :active-value="col.activeValue ?? 1"
              :inactive-value="col.inactiveValue ?? 0"
              @change="(val) => handleSwitchChange(row, col, val)"
            />
            <!-- 格式化日期 -->
            <span v-else-if="col.type === 'date'">
              {{ formatDate(getValue(row, col.prop), col.format) }}
            </span>
            <!-- 格式化金额 -->
            <span v-else-if="col.type === 'money'">
              ¥{{ formatMoney(getValue(row, col.prop)) }}
            </span>
            <!-- 默认显示 -->
            <span v-else :class="col.className">
              {{ col.formatter ? col.formatter(getValue(row, col.prop), row) : getValue(row, col.prop) }}
            </span>
          </template>
        </el-table-column>
      </template>
      
      <!-- 操作列 -->
      <el-table-column
        v-if="showOperation"
        label="操作"
        :width="operationWidth"
        fixed="right"
      >
        <template #default="{ row, $index }">
          <slot name="operation" :row="row" :index="$index">
            <el-button
              v-if="showView"
              link
              type="primary"
              size="small"
              @click="handleView(row)"
            >
              查看
            </el-button>
            <el-button
              v-if="showEdit"
              link
              type="primary"
              size="small"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="showDelete"
              link
              type="danger"
              size="small"
              @click="handleDelete(row)"
            >
              删除
            </el-button>
          </slot>
        </template>
      </el-table-column>
    </el-table>
    
    <!-- 分页 -->
    <div v-if="showPagination" class="pagination-wrapper">
      <div v-if="selection" class="batch-actions">
        <slot name="batchActions" :selection="selectedRows">
          <span class="selection-text">已选择 {{ selectedRows.length }} 项</span>
        </slot>
      </div>
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="pageSizes"
        :layout="paginationLayout"
        :background="paginationBackground"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import dayjs from 'dayjs'

const props = defineProps({
  // 数据
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  columns: { type: Array, required: true },
  total: { type: Number, default: 0 },
  
  // 表格配置
  stripe: { type: Boolean, default: true },
  border: { type: Boolean, default: false },
  size: { type: String, default: 'default' },
  selection: { type: Boolean, default: false },
  showIndex: { type: Boolean, default: true },
  
  // 分页配置
  showPagination: { type: Boolean, default: true },
  page: { type: Number, default: 1 },
  pageSize: { type: Number, default: 20 },
  pageSizes: { type: Array, default: () => [10, 20, 50, 100] },
  paginationLayout: { type: String, default: 'total, sizes, prev, pager, next, jumper' },
  paginationBackground: { type: Boolean, default: true },
  
  // 操作列配置
  showOperation: { type: Boolean, default: true },
  operationWidth: { type: [Number, String], default: 150 },
  showView: { type: Boolean, default: true },
  showEdit: { type: Boolean, default: true },
  showDelete: { type: Boolean, default: true }
})

const emit = defineEmits([
  'update:page',
  'update:pageSize',
  'selection-change',
  'sort-change',
  'view',
  'edit',
  'delete',
  'link-click',
  'switch-change',
  'page-change',
  'size-change'
])

const selectedRows = ref([])

// 计算属性
const currentPage = computed({
  get: () => props.page,
  set: (val) => emit('update:page', val)
})

const pageSize = computed({
  get: () => props.pageSize,
  set: (val) => emit('update:pageSize', val)
})

// 获取嵌套属性值
function getValue(row, prop) {
  if (!prop) return ''
  const keys = prop.split('.')
  let value = row
  for (const key of keys) {
    value = value?.[key]
    if (value === undefined || value === null) return ''
  }
  return value
}

// 获取标签类型
function getTagType(row, col) {
  if (col.tagType) {
    return typeof col.tagType === 'function' 
      ? col.tagType(getValue(row, col.prop), row)
      : col.tagType
  }
  return ''
}

// 获取标签文本
function getTagText(row, col) {
  const value = getValue(row, col.prop)
  if (col.tagMap) {
    return col.tagMap[value] || value
  }
  return value
}

// 格式化日期
function formatDate(value, format = 'YYYY-MM-DD HH:mm:ss') {
  if (!value) return '-'
  return dayjs(value).format(format)
}

// 格式化金额
function formatMoney(value) {
  if (!value) return '0.00'
  return Number(value).toFixed(2)
}

// 事件处理
function handleSelectionChange(selection) {
  selectedRows.value = selection
  emit('selection-change', selection)
}

function handleSortChange({ prop, order }) {
  emit('sort-change', { prop, order })
}

function handleView(row) {
  emit('view', row)
}

function handleEdit(row) {
  emit('edit', row)
}

function handleDelete(row) {
  emit('delete', row)
}

function handleLinkClick(row, col) {
  emit('link-click', { row, col })
}

function handleSwitchChange(row, col, val) {
  emit('switch-change', { row, col, value: val })
}

function handlePageChange(val) {
  emit('page-change', val)
}

function handleSizeChange(val) {
  emit('size-change', val)
}
</script>

<style lang="scss" scoped>
.common-table {
  .pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    
    .batch-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      
      .selection-text {
        color: #606266;
        font-size: 14px;
      }
    }
  }
}
</style>
