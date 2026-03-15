<template>
  <div class="search-filter">
    <!-- 搜索输入框 -->
    <div class="search-input-wrapper">
      <el-input
        v-model="searchKeyword"
        :placeholder="placeholder"
        :prefix-icon="Search"
        clearable
        @input="handleInput"
        @keyup.enter="handleSearch"
        class="search-input"
      />
      <el-button type="primary" @click="handleSearch" class="search-btn">
        搜索
      </el-button>
    </div>
    
    <!-- 筛选标签 -->
    <div class="filter-tags" v-if="filterOptions.length > 0">
      <span class="filter-label">筛选：</span>
      <el-radio-group v-model="currentFilter" size="small" @change="handleFilterChange">
        <el-radio-button label="">全部</el-radio-button>
        <el-radio-button 
          v-for="option in filterOptions" 
          :key="option.value" 
          :label="option.value"
        >
          {{ option.label }}
        </el-radio-button>
      </el-radio-group>
    </div>
    
    <!-- 排序选项 -->
    <div class="sort-options" v-if="sortOptions.length > 0">
      <span class="sort-label">排序：</span>
      <el-radio-group v-model="currentSort" size="small" @change="handleSortChange">
        <el-radio-button 
          v-for="option in sortOptions" 
          :key="option.value" 
          :label="option.value"
        >
          {{ option.label }}
        </el-radio-button>
      </el-radio-group>
    </div>
    
    <!-- 日期范围筛选 -->
    <div class="date-filter" v-if="showDateFilter">
      <span class="date-label">时间：</span>
      <el-radio-group v-model="dateRange" size="small" @change="handleDateChange">
        <el-radio-button label="">全部</el-radio-button>
        <el-radio-button label="today">今天</el-radio-button>
        <el-radio-button label="week">本周</el-radio-button>
        <el-radio-button label="month">本月</el-radio-button>
      </el-radio-group>
    </div>
    
    <!-- 高级筛选 -->
    <div class="advanced-filter" v-if="advancedFilters.length > 0">
      <el-collapse>
        <el-collapse-item title="高级筛选" name="advanced">
          <div class="advanced-filters-grid">
            <div 
              v-for="filter in advancedFilters" 
              :key="filter.field"
              class="filter-item"
            >
              <label>{{ filter.label }}</label>
              <el-select 
                v-model="advancedFilterValues[filter.field]" 
                :placeholder="`选择${filter.label}`"
                clearable
                @change="handleAdvancedFilterChange"
              >
                <el-option 
                  v-for="option in filter.options" 
                  :key="option.value" 
                  :label="option.label" 
                  :value="option.value" 
                />
              </el-select>
            </div>
          </div>
        </el-collapse-item>
      </el-collapse>
    </div>
    
    <!-- 已选条件 -->
    <div class="selected-filters" v-if="showSelectedFilters && selectedFilters.length > 0">
      <span class="selected-label">已选条件：</span>
      <el-tag
        v-for="(filter, index) in selectedFilters"
        :key="index"
        closable
        @close="removeFilter(filter)"
        class="filter-tag"
      >
        {{ filter.label }}: {{ filter.valueLabel }}
      </el-tag>
      <el-button link type="primary" @click="clearAllFilters">清空筛选</el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { debounce } from '../utils/validators'

const props = defineProps({
  // 占位符文本
  placeholder: {
    type: String,
    default: '请输入搜索关键词'
  },
  // 筛选选项
  filterOptions: {
    type: Array,
    default: () => []
  },
  // 排序选项
  sortOptions: {
    type: Array,
    default: () => []
  },
  // 是否显示日期筛选
  showDateFilter: {
    type: Boolean,
    default: false
  },
  // 高级筛选配置
  advancedFilters: {
    type: Array,
    default: () => []
  },
  // 是否显示已选条件
  showSelectedFilters: {
    type: Boolean,
    default: true
  },
  // 防抖延迟
  debounceDelay: {
    type: Number,
    default: 300
  }
})

const emit = defineEmits(['search', 'filter-change', 'sort-change', 'date-change'])

// 状态
const searchKeyword = ref('')
const currentFilter = ref('')
const currentSort = ref(props.sortOptions[0]?.value || '')
const dateRange = ref('')
const advancedFilterValues = ref({})

// 初始化高级筛选值
props.advancedFilters.forEach(filter => {
  advancedFilterValues.value[filter.field] = ''
})

// 计算已选条件
const selectedFilters = computed(() => {
  const filters = []
  
  // 关键词
  if (searchKeyword.value) {
    filters.push({
      type: 'keyword',
      field: 'keyword',
      label: '关键词',
      value: searchKeyword.value,
      valueLabel: searchKeyword.value
    })
  }
  
  // 筛选
  if (currentFilter.value) {
    const option = props.filterOptions.find(o => o.value === currentFilter.value)
    if (option) {
      filters.push({
        type: 'filter',
        field: 'filter',
        label: '类型',
        value: currentFilter.value,
        valueLabel: option.label
      })
    }
  }
  
  // 日期
  if (dateRange.value) {
    const dateLabels = { today: '今天', week: '本周', month: '本月' }
    filters.push({
      type: 'date',
      field: 'date',
      label: '时间',
      value: dateRange.value,
      valueLabel: dateLabels[dateRange.value]
    })
  }
  
  // 高级筛选
  Object.entries(advancedFilterValues.value).forEach(([field, value]) => {
    if (value) {
      const filter = props.advancedFilters.find(f => f.field === field)
      const option = filter?.options.find(o => o.value === value)
      if (filter && option) {
        filters.push({
          type: 'advanced',
          field,
          label: filter.label,
          value,
          valueLabel: option.label
        })
      }
    }
  })
  
  return filters
})

// 防抖搜索
const debouncedSearch = debounce(() => {
  handleSearch()
}, props.debounceDelay)

// 输入处理
const handleInput = () => {
  debouncedSearch()
}

// 搜索
const handleSearch = () => {
  emit('search', {
    keyword: searchKeyword.value,
    filter: currentFilter.value,
    sort: currentSort.value,
    dateRange: dateRange.value,
    advancedFilters: { ...advancedFilterValues.value }
  })
}

// 筛选变化
const handleFilterChange = (value) => {
  currentFilter.value = value
  emit('filter-change', value)
  handleSearch()
}

// 排序变化
const handleSortChange = (value) => {
  currentSort.value = value
  emit('sort-change', value)
  handleSearch()
}

// 日期变化
const handleDateChange = (value) => {
  dateRange.value = value
  emit('date-change', value)
  handleSearch()
}

// 高级筛选变化
const handleAdvancedFilterChange = () => {
  handleSearch()
}

// 移除单个筛选
const removeFilter = (filter) => {
  switch (filter.type) {
    case 'keyword':
      searchKeyword.value = ''
      break
    case 'filter':
      currentFilter.value = ''
      break
    case 'date':
      dateRange.value = ''
      break
    case 'advanced':
      advancedFilterValues.value[filter.field] = ''
      break
  }
  handleSearch()
}

// 清空所有筛选
const clearAllFilters = () => {
  searchKeyword.value = ''
  currentFilter.value = ''
  dateRange.value = ''
  Object.keys(advancedFilterValues.value).forEach(key => {
    advancedFilterValues.value[key] = ''
  })
  handleSearch()
}

// 暴露方法
defineExpose({
  clearAllFilters,
  getSearchParams: () => ({
    keyword: searchKeyword.value,
    filter: currentFilter.value,
    sort: currentSort.value,
    dateRange: dateRange.value,
    advancedFilters: { ...advancedFilterValues.value }
  })
})
</script>

<style scoped>
.search-filter {
  margin-bottom: 20px;
}

.search-input-wrapper {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}

.search-input {
  flex: 1;
}

.search-btn {
  min-width: 80px;
}

.filter-tags,
.sort-options,
.date-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.filter-label,
.sort-label,
.date-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  white-space: nowrap;
}

.advanced-filter {
  margin-top: 10px;
}

.advanced-filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  padding: 10px 0;
}

.filter-item label {
  display: block;
  color: rgba(255, 255, 255, 0.6);
  font-size: 12px;
  margin-bottom: 5px;
}

.selected-filters {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  flex-wrap: wrap;
}

.selected-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.filter-tag {
  margin-right: 5px;
}

/* 移动端适配 */
@media (max-width: 768px) {
  .search-input-wrapper {
    flex-direction: column;
  }
  
  .search-btn {
    width: 100%;
  }
  
  .advanced-filters-grid {
    grid-template-columns: 1fr;
  }
  
  .selected-filters {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
