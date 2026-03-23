<template>
  <div class="qiming-page">
    <div class="container">
      <PageHeroHeader
        title="AI 取名建议"
        subtitle="融合生辰八字、五行喜用与古典诗词意境，为新生儿推荐寓意美好的名字方案。"
        :icon="EditPen"
      />

      <!-- 表单 -->
      <div class="qiming-form card" v-if="!result">
        <div class="form-group">
          <label>姓氏 <span class="required">*</span></label>
          <el-input v-model="form.surname" placeholder="请输入姓氏" maxlength="10" clearable size="large" />
        </div>

        <div class="form-group">
          <label>性别 <span class="required">*</span></label>
          <el-radio-group v-model="form.gender" size="large">
            <el-radio-button label="male">👦 男孩</el-radio-button>
            <el-radio-button label="female">👧 女孩</el-radio-button>
          </el-radio-group>
        </div>

        <div class="form-group">
          <label>出生日期 <span class="required">*</span></label>
          <el-date-picker
            v-model="form.birthDate"
            type="date"
            placeholder="选择出生日期"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            class="full-width"
            size="large"
          />
        </div>

        <div class="form-group">
          <label>出生时间（可选）</label>
          <el-time-picker
            v-model="form.birthHour"
            placeholder="选择出生时间"
            format="HH:mm"
            value-format="HH:mm:ss"
            class="full-width"
            size="large"
          />
        </div>

        <div class="form-group">
          <label>期望风格（可选）</label>
          <div class="style-tags">
            <span
              v-for="tag in styleTags"
              :key="tag"
              class="style-tag"
              :class="{ active: form.style === tag }"
              @click="form.style = form.style === tag ? '' : tag"
            >{{ tag }}</span>
          </div>
          <el-input v-model="form.style" placeholder="也可以自定义风格描述" maxlength="20" clearable />
        </div>

        <div class="form-group">
          <label>忌讳字/词（可选）</label>
          <el-input v-model="form.taboos" placeholder="如：避免使用生僻字等" maxlength="50" clearable />
        </div>

        <el-button
          type="primary"
          size="large"
          @click="submitForm"
          :loading="loading"
          :disabled="loading"
          class="submit-btn"
        >
          <el-icon v-if="!loading"><EditPen /></el-icon>
          {{ loading ? 'AI 正在为您取名...' : '开始取名' }}
        </el-button>
        <p class="cost-hint">消耗 {{ qimingCost }} 积分 · 每次生成 5 个名字方案</p>
      </div>

      <!-- 结果 -->
      <div v-else class="qiming-result card">
        <div class="result-header">
          <h2>
            <el-icon><Checked /></el-icon>
            取名结果
          </h2>
          <el-button @click="resetForm" type="primary" plain>
            <el-icon><RefreshRight /></el-icon>
            重新取名
          </el-button>
        </div>

        <div class="result-content rich-content" v-html="formattedResult"></div>
      </div>

      <!-- 温馨提示 -->
      <div class="qiming-tips" v-if="!result">
        <p class="tips-title">💡 取名小贴士</p>
        <ul class="tips-list">
          <li>提供准确的出生日期和时间，有助于分析五行喜用神</li>
          <li>名字方案融合五行补缺、音韵和谐与寓意美好三大维度</li>
          <li>每次生成 5 个取名方案，您可以多次尝试不同风格</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { EditPen, Checked, RefreshRight } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import { useQiming } from './useQiming'

const styleTags = ['诗意', '大气', '文雅', '简约', '古风', '时尚']

const {
  form,
  loading,
  result,
  qimingCost,
  formattedResult,
  submitForm,
  resetForm,
} = useQiming()
</script>

<style scoped>
@import './style.css';
</style>
