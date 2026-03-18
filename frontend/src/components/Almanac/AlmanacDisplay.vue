<template>
  <div class="almanac-display">
    <!-- 基础信息 -->
    <div class="almanac-header">
      <div class="date-info">
        <div class="lunar-date">
          <span class="label">农历</span>
          <span class="value">{{ lunarDate }}</span>
        </div>
        <div class="ganzhi-date">
          <span class="label">干支</span>
          <span class="value">{{ ganzhiDate }}</span>
        </div>
        <div class="nayin">
          <span class="label">纳音</span>
          <span class="value">{{ nayin }}</span>
        </div>
      </div>
      <div class="astro-info">
        <div class=" constellation">
          <span class="label">星座</span>
          <span class="value">{{ constellation }}</span>
        </div>
        <div class="xingsu">
          <span class="label">星宿</span>
          <span class="value">{{ xingsu }}</span>
        </div>
        <div class="pengzu">
          <span class="label">彭祖百忌</span>
          <span class="value">{{ pengzu }}</span>
        </div>
      </div>
    </div>

    <!-- 宜忌 -->
    <div class="yi-ji-section">
      <div class="yi-section">
        <div class="yi-header">
          <span class="yi-icon">宜</span>
        </div>
        <div class="yi-tags">
          <el-tag v-for="(item, index) in yi" :key="index" type="success" effect="dark" size="large">
            {{ item }}
          </el-tag>
        </div>
      </div>
      <div class="ji-section">
        <div class="ji-header">
          <span class="ji-icon">忌</span>
        </div>
        <div class="ji-tags">
          <el-tag v-for="(item, index) in ji" :key="index" type="danger" effect="dark" size="large">
            {{ item }}
          </el-tag>
        </div>
      </div>
    </div>

    <!-- 冲煞与值日 -->
    <div class="chongsha-section">
      <div class="chongsha-card">
        <div class="card-title">冲煞</div>
        <div class="chongsha-content">
          <div class="chong">
            <span class="label">今日冲</span>
            <span class="value">{{ chong }}</span>
          </div>
          <div class="sha">
            <span class="label">煞方</span>
            <span class="value">{{ sha }}</span>
          </div>
        </div>
        <div class="chong-desc" v-if="chongDesc">
          <el-alert :title="chongDesc" type="warning" :closable="false" show-icon />
        </div>
      </div>
      <div class="zhiri-card">
        <div class="card-title">值日</div>
        <div class="zhiri-content">
          <div class="zhiri-item">
            <span class="label">十二值日</span>
            <span class="value" :class="zhiri.type">{{ zhiri.name }}</span>
            <el-tooltip :content="zhiri.desc" placement="top">
              <el-icon class="help-icon"><QuestionFilled /></el-icon>
            </el-tooltip>
          </div>
          <div class="zhiri-item">
            <span class="label">二十八宿</span>
            <span class="value">{{ xiu }}</span>
          </div>
          <div class="zhiri-item">
            <span class="label">胎神占方</span>
            <span class="value">{{ taishen }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 时辰吉凶 -->
    <div class="shichen-section">
      <h4 class="section-title">
        <el-icon><Clock /></el-icon>
        时辰吉凶
        <el-tooltip content="一天12个时辰的吉凶程度，帮助选择办事时间" placement="top">
          <el-icon class="help-icon"><QuestionFilled /></el-icon>
        </el-tooltip>
      </h4>
      <div class="shichen-grid">
        <div 
          v-for="(sc, index) in shichenList" 
          :key="index"
          class="shichen-item"
          :class="sc.type"
        >
          <div class="shichen-time">{{ sc.time }}</div>
          <div class="shichen-name">{{ sc.name }}</div>
          <div class="shichen-status">{{ sc.status }}</div>
          <div class="shichen-yiji">{{ sc.yiji }}</div>
        </div>
      </div>
    </div>

    <!-- 吉神凶煞 -->
    <div class="jishen-section">
      <div class="jishen-row">
        <div class="jishen-col ji">
          <h5 class="col-title">
            <el-icon><CircleCheck /></el-icon>
            吉神宜趋
          </h5>
          <div class="jishen-tags">
            <el-tag v-for="(js, index) in jishen" :key="index" type="success" effect="plain">
              {{ js }}
            </el-tag>
          </div>
        </div>
        <div class="jishen-col xiong">
          <h5 class="col-title">
            <el-icon><CircleClose /></el-icon>
            凶煞宜忌
          </h5>
          <div class="jishen-tags">
            <el-tag v-for="(xs, index) in xiongsha" :key="index" type="danger" effect="plain">
              {{ xs }}
            </el-tag>
          </div>
        </div>
      </div>
    </div>

    <!-- 生肖运势 -->
    <div class="shengxiao-section">
      <h4 class="section-title">
        <el-icon><User /></el-icon>
        生肖运势
      </h4>
      <div class="shengxiao-grid">
        <div class="shengxiao-card teji" v-if="shengxiao.teji">
          <div class="card-badge">特吉</div>
          <div class="shengxiao-name">{{ shengxiao.teji }}</div>
        </div>
        <div class="shengxiao-card ciji" v-if="shengxiao.ciji">
          <div class="card-badge">次吉</div>
          <div class="shengxiao-name">{{ shengxiao.ciji }}</div>
        </div>
        <div class="shengxiao-card daidai" v-if="shengxiao.daidai">
          <div class="card-badge">带衰</div>
          <div class="shengxiao-name">{{ shengxiao.daidai }}</div>
        </div>
      </div>
    </div>

    <!-- 方位吉凶 -->
    <div class="fangwei-section">
      <h4 class="section-title">
        <el-icon><Compass /></el-icon>
        方位吉凶
      </h4>
      <div class="fangwei-grid">
        <div class="fangwei-item">
          <span class="label">喜神方位</span>
          <span class="value">{{ fangwei.xishen }}</span>
        </div>
        <div class="fangwei-item">
          <span class="label">财神方位</span>
          <span class="value">{{ fangwei.caishen }}</span>
        </div>
        <div class="fangwei-item">
          <span class="label">福神方位</span>
          <span class="value">{{ fangwei.fushen }}</span>
        </div>
        <div class="fangwei-item">
          <span class="label">阳贵神</span>
          <span class="value">{{ fangwei.yanggui }}</span>
        </div>
        <div class="fangwei-item">
          <span class="label">阴贵神</span>
          <span class="value">{{ fangwei.yingui }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  QuestionFilled,
  Clock,
  CircleCheck,
  CircleClose,
  User,
  Compass
} from '@element-plus/icons-vue'

defineProps({
  lunarDate: { type: String, default: '' },
  ganzhiDate: { type: String, default: '' },
  nayin: { type: String, default: '' },
  constellation: { type: String, default: '' },
  xingsu: { type: String, default: '' },
  pengzu: { type: String, default: '' },
  yi: { type: Array, default: () => [] },
  ji: { type: Array, default: () => [] },
  chong: { type: String, default: '' },
  sha: { type: String, default: '' },
  chongDesc: { type: String, default: '' },
  zhiri: { type: Object, default: () => ({ name: '', type: '', desc: '' }) },
  xiu: { type: String, default: '' },
  taishen: { type: String, default: '' },
  shichenList: { type: Array, default: () => [] },
  jishen: { type: Array, default: () => [] },
  xiongsha: { type: Array, default: () => [] },
  shengxiao: { type: Object, default: () => ({}) },
  fangwei: { type: Object, default: () => ({}) }
})
</script>

<style scoped>
.almanac-display {
  background: white;
  border-radius: 16px;
  padding: 24px;
}

/* 头部 */
.almanac-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-light);
}

.date-info,
.astro-info {
  display: flex;
  gap: 24px;
}

.date-info > div,
.astro-info > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.label {
  font-size: 12px;
  color: var(--text-tertiary);
}

.value {
  font-size: 15px;
  font-weight: 500;
  color: var(--text-primary);
}

/* 宜忌 */
.yi-ji-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.yi-section,
.ji-section {
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 20px;
}

.yi-header,
.ji-header {
  margin-bottom: 16px;
}

.yi-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: var(--success-gradient);
  color: white;
  border-radius: 50%;
  font-size: 18px;
  font-weight: 700;
}

.ji-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: var(--danger-gradient);
  color: white;
  border-radius: 50%;
  font-size: 18px;
  font-weight: 700;
}

.yi-tags,
.ji-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

/* 冲煞与值日 */
.chongsha-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.chongsha-card,
.zhiri-card {
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 20px;
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 16px;
}

.chongsha-content {
  display: flex;
  gap: 20px;
  margin-bottom: 12px;
}

.chong,
.sha {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chong .value,
.sha .value {
  font-size: 18px;
  font-weight: 700;
  color: var(--danger-color);
}

.zhiri-item {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.zhiri-item .label {
  width: 80px;
}

.zhiri-item .value {
  font-weight: 600;
}

.zhiri-item .value.ji {
  color: var(--danger-color);
}

.zhiri-item .value.xiong {
  color: var(--warning-color);
}

.zhiri-item .value.ping {
  color: var(--info-color);
}

.zhiri-item .value.xiaoJi {
  color: var(--success-color);
}

/* 时辰吉凶 */
.shichen-section {
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 16px;
}

.shichen-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}

.shichen-item {
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 12px 8px;
  text-align: center;
  transition: all 0.3s;
}

.shichen-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.shichen-item.ji {
  background: linear-gradient(135deg, var(--danger-light-10) 0%, var(--danger-light-05) 100%);
  border: 1px solid var(--danger-light);
}

.shichen-item.xiong {
  background: linear-gradient(135deg, var(--warning-light-10) 0%, var(--warning-light-05) 100%);
  border: 1px solid var(--warning-light);
}

.shichen-item.xiaoJi {
  background: linear-gradient(135deg, var(--success-light-10) 0%, var(--success-light-05) 100%);
  border: 1px solid var(--success-light);
}

.shichen-time {
  font-size: 12px;
  color: var(--text-tertiary);
  margin-bottom: 4px;
}

.shichen-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.shichen-status {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 16px;
  display: inline-block;
  margin-bottom: 4px;
}

.shichen-item.ji .shichen-status {
  background: var(--danger-color);
  color: white;
}

.shichen-item.xiong .shichen-status {
  background: var(--warning-color);
  color: white;
}

.shichen-item.xiaoJi .shichen-status {
  background: var(--success-color);
  color: white;
}

.shichen-yiji {
  font-size: 11px;
  color: var(--text-secondary);
}

/* 吉神凶煞 */
.jishen-section {
  margin-bottom: 24px;
}

.jishen-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.jishen-col {
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 20px;
}

.jishen-col.ji {
  background: linear-gradient(135deg, var(--success-light-10) 0%, var(--success-light-05) 100%);
}

.jishen-col.xiong {
  background: linear-gradient(135deg, var(--danger-light-10) 0%, var(--danger-light-05) 100%);
}

.col-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 16px;
}

.jishen-col.ji .col-title {
  color: var(--success-color);
}

.jishen-col.xiong .col-title {
  color: var(--danger-color);
}

.jishen-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* 生肖运势 */
.shengxiao-section {
  margin-bottom: 24px;
}

.shengxiao-grid {
  display: flex;
  gap: 16px;
}

.shengxiao-card {
  flex: 1;
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 20px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.shengxiao-card.teji {
  background: linear-gradient(135deg, var(--warning-light-10) 0%, var(--warning-light-05) 100%);
  border: 1px solid var(--warning-color);
}

.shengxiao-card.ciji {
  background: linear-gradient(135deg, var(--success-light-10) 0%, var(--success-light-05) 100%);
  border: 1px solid var(--success-color);
}

.shengxiao-card.daidai {
  background: linear-gradient(135deg, var(--danger-light-10) 0%, var(--danger-light-05) 100%);
  border: 1px solid var(--danger-color);
}

.card-badge {
  position: absolute;
  top: 0;
  right: 0;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 600;
  border-bottom-left-radius: 16px;
}

.shengxiao-card.teji .card-badge {
  background: var(--warning-color);
  color: var(--warning-text);
}

.shengxiao-card.ciji .card-badge {
  background: var(--success-color);
  color: var(--success-text);
}

.shengxiao-card.daidai .card-badge {
  background: var(--danger-color);
  color: var(--danger-text);
}

.shengxiao-name {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-top: 20px;
}

/* 方位 */
.fangwei-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}

.fangwei-item {
  background: var(--bg-card-hover);
  border-radius: 16px;
  padding: 16px;
  text-align: center;
}

.fangwei-item .label {
  display: block;
  margin-bottom: 8px;
}

.fangwei-item .value {
  font-size: 18px;
  font-weight: 600;
  color: var(--info-color);
}
</style>
