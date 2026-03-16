<template>
  <div class="bazi-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">八字排盘</h1>
      </div>
      
      <!-- 暖心提示 -->
      <div class="warm-tip card" v-if="!result">
        <el-icon class="tip-icon"><HeartFilled /></el-icon>
        <div class="tip-content">
          <p class="tip-title">八字排盘能帮你了解什么？</p>
          <p class="tip-desc">你的性格优势 · 适合的发展方向 · 未来运势起伏 · 人际关系建议</p>
        </div>
      </div>
      
      <div class="bazi-form card" v-if="!result">
        <!-- 积分消耗提示 -->
        <div class="points-hint">
          <el-icon class="hint-icon"><Diamond /></el-icon>
          <span>
            <span v-if="isFirstBazi"><el-icon><Present /></el-icon> 首次排盘免费</span>
            <span v-else>本次排盘将消耗 <strong>10 积分</strong></span>
          </span>
          <span class="current-points">当前积分: {{ currentPoints }}</span>
        </div>

        <!-- 简版/专业版切换 -->
        <div class="version-toggle">
          <span class="toggle-label">排盘模式：</span>
          <el-radio-group v-model="versionMode" size="small">
            <el-radio-button label="simple">
              <span class="mode-option">
                <el-icon class="mode-icon"><Magic /></el-icon>
                简化版
              </span>
            </el-radio-button>
            <el-radio-button label="pro">
              <span class="mode-option">
                <el-icon class="mode-icon"><Diamond /></el-icon>
                专业版
              </span>
            </el-radio-button>
          </el-radio-group>
          <p class="version-hint">{{ versionHint }}</p>
        </div>

        <div class="form-group">
          <label>出生日期</label>
          <el-date-picker
            v-model="birthDate"
            type="datetime"
            placeholder="选择出生日期时间"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm:ss"
            class="full-width"
          />
          <p class="form-hint">不知道具体时辰？选个大概的时间也可以</p>
        </div>
        
        <div class="form-group">
          <label>性别</label>
          <el-radio-group v-model="gender">
            <el-radio label="male">男</el-radio>
            <el-radio label="female">女</el-radio>
          </el-radio-group>
        </div>
        
        <div class="form-group" v-if="versionMode === 'pro'">
          <label>
            出生地点
            <el-tooltip content="用于计算真太阳时，让排盘更准确" placement="top">
              <el-icon class="help-icon"><QuestionFilled /></el-icon>
            </el-tooltip>
          </label>
          <el-select-v2
            v-model="location"
            :options="cityOptions"
            placeholder="请选择出生城市（可选）"
            class="full-width"
            filterable
            clearable
            :height="200"
          />
          <p class="form-hint"><el-icon><Magic /></el-icon> 不知道出生地可以跳过，默认使用北京时间</p>
        </div>
        
        <el-button 
          type="primary" 
          size="large" 
          @click="showConfirm" 
          :loading="loading"
          :disabled="!isFirstBazi && currentPoints < 10"
        >
          <el-icon v-if="isFirstBazi"><Present /></el-icon>
          {{ isFirstBazi ? ' 首次免费排盘' : '开始排盘' }}
        </el-button>

        <!-- 积分不足提示 -->
        <div v-if="!isFirstBazi && currentPoints < 10" class="insufficient-points">
          <p><el-icon><Lightbulb /></el-icon> 积分不足，请先 <router-link to="/profile">签到领取积分</router-link></p>
        </div>
      </div>

      <!-- 确认对话框 -->
      <el-dialog
        v-model="confirmVisible"
        title="确认排盘"
        width="400px"
        class="confirm-dialog"
      >
        <div class="confirm-content">
          <p>本次排盘将消耗 <strong>10 积分</strong></p>
          <p>排盘后可在个人中心查看历史记录</p>
        </div>
        <template #footer>
          <el-button @click="confirmVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmCalculate">确认排盘</el-button>
        </template>
      </el-dialog>

      <!-- 积分消耗确认对话框 -->
      <el-dialog
        v-model="pointsConfirmVisible"
        title="确认使用积分"
        width="400px"
        class="points-confirm-dialog"
      >
        <div class="points-confirm-content">
          <div class="points-icon"><el-icon :size="48"><Diamond /></el-icon></div>
          <p class="points-title">
            {{ 
              pointsConfirmType === 'yearly' ? '流年运势分析' : 
              pointsConfirmType === 'dayun' ? '大运运势评分' : 
              '运势K线图' 
            }}
          </p>
          <p class="points-desc">
            此功能将消耗 
            <strong>
              {{ 
                pointsConfirmType === 'yearly' ? fortunePointsCost.yearly_fortune : 
                pointsConfirmType === 'dayun' ? fortunePointsCost.dayun_analysis : 
                fortunePointsCost.dayun_chart 
              }} 
              积分
            </strong>
          </p>
          <p class="points-balance">当前积分: {{ currentPoints }}</p>
        </div>
        <template #footer>
          <el-button @click="pointsConfirmVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmPointsConsume">
            确认使用
          </el-button>
        </template>
      </el-dialog>

      <!-- 加载状态 -->
      <div v-if="loading" class="loading-state card">
        <div class="loading-animation">
          <div class="yin-yang">
            <div class="yin"></div>
            <div class="yang"></div>
          </div>
        </div>
        <h3>正在为你排盘...</h3>
        <p class="loading-text">计算天干地支 · 分析五行配置 · 生成命理解读</p>
        <div class="loading-steps">
          <div class="step" :class="{ active: loadingStep >= 1, done: loadingStep > 1 }">
            <span class="step-icon">{{ loadingStep > 1 ? '✓' : '1' }}</span>
            <span class="step-text">排八字</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 2 }"></div>
          <div class="step" :class="{ active: loadingStep >= 2, done: loadingStep > 2 }">
            <span class="step-icon">{{ loadingStep > 2 ? '✓' : '2' }}</span>
            <span class="step-text">算五行</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 3 }"></div>
          <div class="step" :class="{ active: loadingStep >= 3, done: loadingStep > 3 }">
            <span class="step-icon">{{ loadingStep > 3 ? '✓' : '3' }}</span>
            <span class="step-text">析命理</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 4 }"></div>
          <div class="step" :class="{ active: loadingStep >= 4 }">
            <span class="step-icon">4</span>
            <span class="step-text">出结果</span>
          </div>
        </div>
      </div>

      <div v-else-if="result" class="bazi-result card">
        <div class="result-header">
          <h2>八字排盘结果</h2>
          <div class="result-meta">
            <span class="meta-tag" v-if="result.is_first_bazi"><el-icon><Present /></el-icon> 首次免费</span>
            <span class="meta-tag" v-if="result.from_cache"><el-icon><Lightning /></el-icon> 智能缓存</span>
          </div>
        </div>
        
        <!-- 日主信息 -->
        <div class="day-master-info">
          <div class="day-master-card">
            <span class="label">日主</span>
            <span class="value">{{ result.bazi?.day_master }}</span>
            <span class="wuxing">{{ result.bazi?.day_master_wuxing }}</span>
          </div>
        </div>
        
        <!-- 八字排盘表 -->
        <div class="bazi-paipan">
          <div class="paipan-row">
            <div class="paipan-cell header">年柱</div>
            <div class="paipan-cell header">月柱</div>
            <div class="paipan-cell header">日柱</div>
            <div class="paipan-cell header">时柱</div>
          </div>
          <!-- 天干行 -->
          <div class="paipan-row">
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi?.year?.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi?.year?.gan_wuxing">{{ result.bazi?.year?.gan_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi?.month?.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi?.month?.gan_wuxing">{{ result.bazi?.month?.gan_wuxing }}</span>
            </div>
            <div class="paipan-cell highlight">
              <span class="gan-text">{{ result.bazi?.day?.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi?.day?.gan_wuxing">{{ result.bazi?.day?.gan_wuxing }}</span>
              <span class="rizhu-tag">日主</span>
            </div>
            <div class="paipan-cell">
              <span class="gan-text">{{ result.bazi?.hour?.gan }}</span>
              <span class="wuxing-badge" :class="result.bazi?.hour?.gan_wuxing">{{ result.bazi?.hour?.gan_wuxing }}</span>
            </div>
          </div>
          <!-- 十神行 -->
          <div class="paipan-row shishen-row">
            <div class="paipan-cell shishen-cell">{{ result.bazi?.year?.shishen }}</div>
            <div class="paipan-cell shishen-cell">{{ result.bazi?.month?.shishen }}</div>
            <div class="paipan-cell shishen-cell highlight">日主</div>
            <div class="paipan-cell shishen-cell">{{ result.bazi?.hour?.shishen }}</div>
          </div>
          <!-- 地支行 -->
          <div class="paipan-row">
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi?.year?.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi?.year?.zhi_wuxing">{{ result.bazi?.year?.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi?.month?.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi?.month?.zhi_wuxing">{{ result.bazi?.month?.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell highlight">
              <span class="zhi-text">{{ result.bazi?.day?.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi?.day?.zhi_wuxing">{{ result.bazi?.day?.zhi_wuxing }}</span>
            </div>
            <div class="paipan-cell">
              <span class="zhi-text">{{ result.bazi?.hour?.zhi }}</span>
              <span class="wuxing-badge zhi" :class="result.bazi?.hour?.zhi_wuxing">{{ result.bazi?.hour?.zhi_wuxing }}</span>
            </div>
          </div>
          <!-- 藏干行 -->
          <div class="paipan-row canggan-row">
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi?.year?.canggan || []" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi?.year?.canggan_shishen?.[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi?.month?.canggan || []" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi?.month?.canggan_shishen?.[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell highlight">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi?.day?.canggan || []" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi?.day?.canggan_shishen?.[idx] }})</small>
                </span>
              </div>
            </div>
            <div class="paipan-cell canggan-cell">
              <div class="canggan-list">
                <span v-for="(cg, idx) in result.bazi?.hour?.canggan || []" :key="idx" class="canggan-item">
                  {{ cg }}<small>({{ result.bazi?.hour?.canggan_shishen?.[idx] }})</small>
                </span>
              </div>
            </div>
          </div>
          <!-- 纳音行 -->
          <div class="paipan-row nayin-row">
            <div class="paipan-cell nayin-cell">{{ result.bazi?.year?.nayin }}</div>
            <div class="paipan-cell nayin-cell">{{ result.bazi?.month?.nayin }}</div>
            <div class="paipan-cell nayin-cell highlight">{{ result.bazi?.day?.nayin }}</div>
            <div class="paipan-cell nayin-cell">{{ result.bazi?.hour?.nayin }}</div>
          </div>
        </div>
        
        <!-- 五行统计 -->
        <div class="wuxing-stats">
          <h3>五行分布</h3>
          <div class="wuxing-bars">
            <div v-for="(count, wx) in result.bazi?.wuxing_stats" :key="wx" class="wuxing-bar-item">
              <span class="wuxing-name">{{ wx }}</span>
              <div class="wuxing-bar">
                <div class="wuxing-fill" :class="wx" :style="{ width: (count / 8 * 100) + '%' }"></div>
              </div>
              <span class="wuxing-count">{{ count }}</span>
            </div>
          </div>
        </div>
        
        <!-- 专业解读卡片 -->
        <div class="professional-reading" v-if="result.fullInterpretation">
          <h3>
            <span class="section-icon">📜</span>
            命盘精解
            <span class="section-badge">专业版</span>
          </h3>
          
          <!-- 日主信息卡片 -->
          <div class="day-master-detail" v-if="result.fullInterpretation.basic">
            <div class="dm-header">
              <div class="dm-symbol">{{ result.fullInterpretation.basic.day_master_symbol }}</div>
              <div class="dm-title">
                <h4>{{ result.fullInterpretation.basic.day_master }}日主 · {{ result.fullInterpretation.basic.day_master_nature }}</h4>
                <p class="dm-traits">
                  <span v-for="(trait, idx) in result.fullInterpretation.basic.traits" :key="idx" class="trait-tag">{{ trait }}</span>
                </p>
              </div>
            </div>
            <div class="dm-content">
              <div class="dm-section">
                <h5>核心优势</h5>
                <p>{{ result.fullInterpretation.basic.strengths }}</p>
              </div>
              <div class="dm-section">
                <h5>需要注意</h5>
                <p>{{ result.fullInterpretation.basic.weaknesses }}</p>
              </div>
            </div>
          </div>

          <!-- 喜用神分析 -->
          <div class="yongshen-section" v-if="result.fullInterpretation.yongshen">
            <div class="ys-header">
              <span class="ys-icon">✨</span>
              <div class="ys-info">
                <h4>喜用神：{{ result.fullInterpretation.yongshen.shen }}、{{ result.fullInterpretation.yongshen.xi }}</h4>
                <span class="ys-type">{{ result.fullInterpretation.yongshen.type }}格</span>
              </div>
            </div>
            <p class="ys-desc">{{ result.fullInterpretation.yongshen.desc }}</p>
          </div>

          <!-- 详细解读卡片网格 -->
          <div class="reading-cards-grid">
            <div class="reading-card" v-if="result.fullInterpretation.personality">
              <div class="rc-header">
                <span class="rc-icon">🎭</span>
                <h4>性格详解</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.personality }}</p>
            </div>
            
            <div class="reading-card" v-if="result.fullInterpretation.career">
              <div class="rc-header">
                <span class="rc-icon">💼</span>
                <h4>事业财运</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.career }}</p>
            </div>
            
            <div class="reading-card" v-if="result.fullInterpretation.wealth">
              <div class="rc-header">
                <span class="rc-icon">💰</span>
                <h4>财富分析</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.wealth }}</p>
            </div>
            
            <div class="reading-card" v-if="result.fullInterpretation.relationship">
              <div class="rc-header">
                <span class="rc-icon">💕</span>
                <h4>感情婚姻</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.relationship }}</p>
            </div>
            
            <div class="reading-card" v-if="result.fullInterpretation.health">
              <div class="rc-header">
                <span class="rc-icon">🏃</span>
                <h4>健康提醒</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.health }}</p>
            </div>
            
            <div class="reading-card advice-card" v-if="result.fullInterpretation.advice">
              <div class="rc-header">
                <span class="rc-icon">🌟</span>
                <h4>开运建议</h4>
              </div>
              <p class="rc-content">{{ result.fullInterpretation.advice }}</p>
            </div>
          </div>
        </div>

        <!-- 通俗解读：这对我意味着什么 -->
        <div class="simple-interpretation" v-if="result.simpleInterpretation && !result.fullInterpretation">
          <h3>
            <span class="section-icon">💡</span>
            这对我意味着什么？
            <span class="section-subtitle">通俗解读</span>
          </h3>
          <div class="interpretation-cards">
            <div class="interp-card personality">
              <div class="interp-header">
                <span class="interp-icon">🎭</span>
                <h4>我的性格特点</h4>
              </div>
              <p class="interp-content">{{ result.simpleInterpretation.personality }}</p>
            </div>
            <div class="interp-card career">
              <div class="interp-header">
                <span class="interp-icon">💼</span>
                <h4>适合的发展方向</h4>
              </div>
              <p class="interp-content">{{ result.simpleInterpretation.career }}</p>
            </div>
            <div class="interp-card relationship">
              <div class="interp-header">
                <span class="interp-icon">💕</span>
                <h4>人际关系建议</h4>
              </div>
              <p class="interp-content">{{ result.simpleInterpretation.relationship }}</p>
            </div>
            <div class="interp-card advice">
              <div class="interp-header">
                <span class="interp-icon">🌟</span>
                <h4>给你的建议</h4>
              </div>
              <p class="interp-content">{{ result.simpleInterpretation.advice }}</p>
            </div>
          </div>
        </div>

        <div class="bazi-analysis">
          <h3>详细命理分析</h3>
          <div class="analysis-content">{{ result.analysis }}</div>
        </div>

        <!-- 大运分析 -->
        <div class="dayun-section" v-if="result.dayun && result.dayun.length > 0">
          <h3>
            大运走势
            <el-tooltip content="大运是十年一个周期的人生阶段分析，反映不同时期的性格特点" placement="top">
              <span class="help-icon">❓</span>
            </el-tooltip>
          </h3>
          <div class="dayun-timeline">
            <div 
              v-for="(yun, index) in result.dayun" 
              :key="index"
              class="dayun-item"
              :class="{ 'current': isCurrentDaYun(yun) }"
            >
              <div class="dayun-age">{{ yun.age_start }}-{{ yun.age_end }}岁</div>
              <div class="dayun-pillar">
                <span class="gan">{{ yun.gan }}</span>
                <span class="zhi">{{ yun.zhi }}</span>
              </div>
              <div class="dayun-shishen">{{ yun.shishen }}</div>
              <div class="dayun-luck" :class="yun.luck">{{ yun.luck }}</div>
              <div class="dayun-desc">{{ yun.luck_desc }}</div>
              <div class="dayun-nayin">{{ yun.nayin }}</div>
            </div>
          </div>
        </div>

        <!-- 流年分析 -->
        <div class="liunian-section" v-if="result.liunian && result.liunian.length > 0">
          <h3>
            流年运势
            <el-tooltip content="流年是每年的运势参考，结合大运提供年度生活建议" placement="top">
              <span class="help-icon">❓</span>
            </el-tooltip>
          </h3>
          <div class="liunian-grid">
            <div 
              v-for="(year, index) in result.liunian" 
              :key="index"
              class="liunian-item"
              :class="{ 'current': year.is_current }"
            >
              <div class="liunian-year">{{ year.year }}年</div>
              <div class="liunian-pillar">
                <span class="gan">{{ year.gan }}</span>
                <span class="zhi">{{ year.zhi }}</span>
              </div>
              <div class="liunian-wuxing">
                <span class="badge" :class="year.gan_wuxing">{{ year.gan_wuxing }}</span>
                <span class="badge" :class="year.zhi_wuxing">{{ year.zhi_wuxing }}</span>
              </div>
              <div class="liunian-nayin">{{ year.nayin }}</div>
            </div>
          </div>
        </div>
        
        <!-- 流年运势分析 -->
        <div class="yearly-fortune-section" v-if="result.bazi">
          <h3>
            <span class="section-icon">📅</span>
            流年运势深度分析
            <el-tag type="warning" size="small" class="ml-2">消耗{{ fortunePointsCost.yearly_fortune }}积分</el-tag>
          </h3>
          
          <!-- 年份选择 -->
          <div class="year-selector">
            <span class="selector-label">选择年份：</span>
            <el-slider
              v-model="selectedYear"
              :min="new Date().getFullYear() - 3"
              :max="new Date().getFullYear() + 7"
              :step="1"
              show-stops
              class="year-slider"
            />
            <span class="selected-year">{{ selectedYear }}年</span>
          </div>
          
          <!-- 流年分析结果 -->
          <div v-if="yearlyFortuneResult" class="yearly-result">
            <div class="yearly-header">
              <div class="year-info">
                <span class="year-number">{{ yearlyFortuneResult.year }}</span>
                <span class="year-ganzhi">{{ yearlyFortuneResult.ganzhi }}年</span>
                <span class="year-nayin">{{ yearlyFortuneResult.nayin }}</span>
              </div>
              <div class="score-display">
                <div class="score-circle" :class="getScoreClass(yearlyFortuneResult.score)">
                  <span class="score-value">{{ yearlyFortuneResult.score }}</span>
                  <span class="score-label">运势评分</span>
                </div>
                <div class="rating-badge" :class="getScoreClass(yearlyFortuneResult.score)">
                  {{ yearlyFortuneResult.rating }}
                </div>
              </div>
            </div>
            
            <div class="yearly-analysis">
              <div class="analysis-card overall">
                <h4>🎯 整体运势</h4>
                <p>{{ yearlyFortuneResult.overall }}</p>
              </div>
              
              <div class="analysis-grid">
                <div class="analysis-card">
                  <h4>💼 事业运势</h4>
                  <p>{{ yearlyFortuneResult.career }}</p>
                </div>
                <div class="analysis-card">
                  <h4>💰 财富运势</h4>
                  <p>{{ yearlyFortuneResult.wealth }}</p>
                </div>
                <div class="analysis-card">
                  <h4>💕 感情运势</h4>
                  <p>{{ yearlyFortuneResult.relationship }}</p>
                </div>
                <div class="analysis-card">
                  <h4>🏃 健康提醒</h4>
                  <p>{{ yearlyFortuneResult.health }}</p>
                </div>
              </div>
              
              <div class="analysis-card advice">
                <h4>🌟 开运建议</h4>
                <p>{{ yearlyFortuneResult.advice }}</p>
              </div>
              
              <div class="lucky-info">
                <div class="lucky-section">
                  <h5>✨ 幸运月份</h5>
                  <div class="lucky-tags">
                    <span v-for="month in yearlyFortuneResult.lucky_months" :key="month" class="lucky-tag good">
                      {{ month }}月
                    </span>
                  </div>
                </div>
                <div class="lucky-section">
                  <h5>⚠️ 注意月份</h5>
                  <div class="lucky-tags">
                    <span v-for="month in yearlyFortuneResult.unlucky_months" :key="month" class="lucky-tag bad">
                      {{ month }}月
                    </span>
                  </div>
                </div>
                <div class="lucky-section">
                  <h5>🎨 幸运颜色</h5>
                  <div class="lucky-tags">
                    <span v-for="color in yearlyFortuneResult.lucky_colors" :key="color" class="lucky-tag color">
                      {{ color }}
                    </span>
                  </div>
                </div>
                <div class="lucky-section">
                  <h5>🔢 幸运数字</h5>
                  <div class="lucky-tags">
                    <span v-for="num in yearlyFortuneResult.lucky_numbers" :key="num" class="lucky-tag number">
                      {{ num }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- 分析按钮 -->
          <div v-else class="analysis-actions">
            <p class="analysis-desc">基于你的八字，AI为你深度分析流年运势</p>
            <el-button 
              type="warning" 
              size="large"
              :loading="yearlyFortuneLoading"
              :disabled="currentPoints < fortunePointsCost.yearly_fortune"
              @click="showPointsConfirm('yearly')"
            >
              <span class="btn-icon">✨</span>
              {{ currentPoints < fortunePointsCost.yearly_fortune ? '积分不足' : '开始流年分析' }}
            </el-button>
          </div>
        </div>

        <!-- 大运运势分析 -->
        <div class="dayun-fortune-section" v-if="result.dayun && result.dayun.length > 0">
          <h3>
            <span class="section-icon">🎯</span>
            大运运势评分
            <el-tag type="warning" size="small" class="ml-2">消耗{{ fortunePointsCost.dayun_analysis }}积分</el-tag>
          </h3>
          
          <!-- 大运选择 -->
          <div class="dayun-selector">
            <span class="selector-label">选择大运：</span>
            <el-radio-group v-model="selectedDayunIndex" size="small">
              <el-radio-button 
                v-for="(yun, index) in result.dayun" 
                :key="index" 
                :label="index"
              >
                {{ yun.gan }}{{ yun.zhi }} ({{ yun.age_start }}-{{ yun.age_end }}岁)
              </el-radio-button>
            </el-radio-group>
          </div>
          
          <!-- 大运分析结果 -->
          <div v-if="dayunAnalysisResult" class="dayun-analysis-result">
            <div class="dayun-header">
              <div class="dayun-info">
                <span class="dayun-name">{{ dayunAnalysisResult.dayun.gan }}{{ dayunAnalysisResult.dayun.zhi }}</span>
                <span class="dayun-shishen">{{ dayunAnalysisResult.dayun.shishen }}</span>
                <span class="dayun-age">{{ dayunAnalysisResult.dayun.start_age }}-{{ dayunAnalysisResult.dayun.end_age }}岁</span>
              </div>
              <div class="dayun-level-badge" :class="getScoreClass(dayunAnalysisResult.overall_score)">
                {{ dayunAnalysisResult.fortune_level }}
              </div>
            </div>
            
            <div class="dayun-scores">
              <div class="score-item">
                <span class="score-name">综合</span>
                <el-progress 
                  :percentage="dayunAnalysisResult.scores.overall" 
                  :color="getScoreColor(dayunAnalysisResult.scores.overall)"
                  :stroke-width="12"
                  class="score-progress"
                />
                <span class="score-value">{{ dayunAnalysisResult.scores.overall }}</span>
              </div>
              <div class="score-item">
                <span class="score-name">事业</span>
                <el-progress 
                  :percentage="dayunAnalysisResult.scores.career" 
                  :color="getScoreColor(dayunAnalysisResult.scores.career)"
                  :stroke-width="10"
                  class="score-progress"
                />
                <span class="score-value">{{ dayunAnalysisResult.scores.career }}</span>
              </div>
              <div class="score-item">
                <span class="score-name">财运</span>
                <el-progress 
                  :percentage="dayunAnalysisResult.scores.wealth" 
                  :color="getScoreColor(dayunAnalysisResult.scores.wealth)"
                  :stroke-width="10"
                  class="score-progress"
                />
                <span class="score-value">{{ dayunAnalysisResult.scores.wealth }}</span>
              </div>
              <div class="score-item">
                <span class="score-name">感情</span>
                <el-progress 
                  :percentage="dayunAnalysisResult.scores.relationship" 
                  :color="getScoreColor(dayunAnalysisResult.scores.relationship)"
                  :stroke-width="10"
                  class="score-progress"
                />
                <span class="score-value">{{ dayunAnalysisResult.scores.relationship }}</span>
              </div>
              <div class="score-item">
                <span class="score-name">健康</span>
                <el-progress 
                  :percentage="dayunAnalysisResult.scores.health" 
                  :color="getScoreColor(dayunAnalysisResult.scores.health)"
                  :stroke-width="10"
                  class="score-progress"
                />
                <span class="score-value">{{ dayunAnalysisResult.scores.health }}</span>
              </div>
            </div>
            
            <div class="dayun-analysis-text">
              <div class="text-card" v-for="(text, key) in dayunAnalysisResult.analysis" :key="key">
                <p>{{ text }}</p>
              </div>
            </div>
            
            <div class="key-suggestions">
              <h4>💡 关键建议</h4>
              <ul>
                <li v-for="(suggestion, index) in dayunAnalysisResult.key_suggestions" :key="index">
                  {{ suggestion }}
                </li>
              </ul>
            </div>
          </div>
          
          <!-- 分析按钮 -->
          <div v-else class="analysis-actions">
            <p class="analysis-desc">深度分析此大运的各方面运势评分</p>
            <el-button 
              type="primary" 
              size="large"
              :loading="dayunAnalysisLoading"
              :disabled="currentPoints < fortunePointsCost.dayun_analysis"
              @click="showPointsConfirm('dayun')"
            >
              <span class="btn-icon">📊</span>
              {{ currentPoints < fortunePointsCost.dayun_analysis ? '积分不足' : '开始大运评分' }}
            </el-button>
          </div>
        </div>

        <!-- 运势K线图 -->
        <div class="fortune-chart-section" v-if="result.dayun && result.dayun.length > 0">
          <h3>
            <span class="section-icon">📈</span>
            运势K线图
            <el-tag type="warning" size="small" class="ml-2">消耗{{ fortunePointsCost.dayun_chart }}积分</el-tag>
          </h3>
          
          <!-- K线图结果 -->
          <div v-if="dayunChartData" class="chart-result">
            <div class="chart-summary">
              <p>{{ dayunChartData.summary }}</p>
              <div v-if="dayunChartData.best_period" class="best-period">
                <span class="best-label">最佳时期：</span>
                <span class="best-value">
                  {{ dayunChartData.best_period.dayun_name }}运 
                  ({{ dayunChartData.best_period.age_range }})
                  评分{{ dayunChartData.best_period.dayun_score }}分
                </span>
              </div>
            </div>
            
            <div class="chart-container">
              <div v-for="(dayun, index) in dayunChartData.chart_data" :key="index" class="chart-dayun">
                <div class="chart-dayun-header">
                  <span class="dayun-title">{{ dayun.dayun_name }}运</span>
                  <span class="dayun-score" :class="getScoreClass(dayun.overall_score)">
                    {{ dayun.overall_score }}分
                  </span>
                  <span class="dayun-trend">{{ dayun.trend }}</span>
                </div>
                <div class="chart-years">
                  <div 
                    v-for="year in dayun.years" 
                    :key="year.year"
                    class="chart-year-bar"
                    :class="{ 'current': year.is_current }"
                    :style="{ height: year.score + '%' }"
                    :title="`${year.year}年 (${year.age}岁): ${year.score}分`"
                  >
                    <span class="year-label">{{ year.year }}</span>
                    <span class="year-score">{{ year.score }}</span>
                  </div>
                </div>
                <div class="chart-legend">
                  <span>{{ dayun.start_age }}-{{ dayun.end_age }}岁</span>
                  <span :class="getScoreClass(dayun.overall_score)">{{ dayun.fortune_level }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- 生成按钮 -->
          <div v-else class="analysis-actions">
            <p class="analysis-desc">可视化展示你一生的大运走势，找到最佳发展时期</p>
            <el-button 
              type="success" 
              size="large"
              :loading="dayunChartLoading"
              :disabled="currentPoints < fortunePointsCost.dayun_chart"
              @click="showPointsConfirm('chart')"
            >
              <span class="btn-icon">📈</span>
              {{ currentPoints < fortunePointsCost.dayun_chart ? '积分不足' : '生成运势K线图' }}
            </el-button>
          </div>
        </div>

        <!-- AI智能解盘 -->
        <div class="ai-analysis-section" v-if="result.bazi">
          <h3>
            <span class="section-icon">🤖</span>
            AI智能解盘
            <el-tag type="warning" size="small" class="ml-2">消耗30积分</el-tag>
          </h3>
          
          <!-- AI解盘结果 -->
          <div v-if="aiAnalysisResult" class="ai-result">
            <div class="ai-result-header">
              <span class="ai-model">{{ aiAnalysisResult.model || 'AI' }} 解读</span>
              <el-button type="primary" link size="small" @click="clearAiResult">
                重新解读
              </el-button>
            </div>
            <div class="ai-content" v-html="formatAiContent(aiAnalysisResult.analysis)"></div>
          </div>
          
          <!-- AI解盘输入 -->
          <div v-else-if="!aiAnalyzing" class="ai-input">
            <p class="ai-desc">基于你的八字信息，让AI为你提供深度分析</p>
            <el-input
              v-model="aiPrompt"
              type="textarea"
              :rows="2"
              placeholder="输入你想问的问题（可选），例如：我的事业运势如何？"
              class="mb-3"
            />
            <el-button 
              type="warning" 
              size="large"
              :disabled="currentPoints < 30"
              @click="startAiAnalysis"
            >
              <span class="btn-icon">✨</span>
              {{ currentPoints < 30 ? '积分不足（需30积分）' : '开始AI解盘' }}
            </el-button>
          </div>
          
          <!-- AI解盘加载中 -->
          <div v-else class="ai-loading">
            <div class="ai-loading-spinner">
              <span class="spinner"></span>
              <span>AI正在深度分析你的八字...</span>
            </div>
            <div class="ai-loading-timeout" v-if="aiLoadingTime > 0">
              <span class="timeout-text">预计等待 {{ aiLoadingTime }} 秒</span>
            </div>
            <div class="ai-stream-content" v-if="aiStreamContent">
              {{ aiStreamContent }}
            </div>
            <div class="ai-loading-actions">
              <el-button type="danger" size="small" @click="cancelAiAnalysis">
                <el-icon><CircleClose /></el-icon>
                取消分析
              </el-button>
            </div>
          </div>
        </div>

        <!-- 操作按钮 -->
        <div class="result-actions">
          <el-button type="primary" @click="saveResult" :loading="saving">
            <span class="btn-icon">💾</span> 保存结果
          </el-button>
          <el-button @click="shareResult">
            <span class="btn-icon">📤</span> 分享
          </el-button>
          <el-button @click="result = null">
            <span class="btn-icon">🔄</span> 重新排盘
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { CircleClose, HeartFilled, Diamond, Magic, QuestionFilled, Present, Lightning } from '@element-plus/icons-vue'
import { 
  calculateBazi as calculateBaziApi, 
  getPointsBalance, 
  getYearlyFortune, 
  getDayunAnalysis, 
  getDayunChart as getDayunChartApi,
  getFortunePointsCost 
} from '../api'
import { analyzeBaziAi, analyzeBaziAiStream } from '../api/ai'
import BackButton from '../components/BackButton.vue'
import { sanitizeHtml } from '../utils/sanitize'

const birthDate = ref('')
const gender = ref('male')
const location = ref('')
const loading = ref(false)
const result = ref(null)
const currentPoints = ref(0)
const confirmVisible = ref(false)
const saving = ref(false)
const versionMode = ref('simple') // 'simple' or 'pro'
const isFirstBazi = ref(true) // 是否首次排盘
const loadingStep = ref(1) // 加载步骤

// AI解盘相关
const aiPrompt = ref('')
const aiAnalyzing = ref(false)
const aiAnalysisResult = ref(null)
const aiStreamContent = ref('')
const aiLoadingTime = ref(0)
const aiAbortController = ref(null)
const aiLoadingTimer = ref(null)

// 流年运势相关
const fortunePointsCost = ref({
  yearly_fortune: 30,
  dayun_analysis: 50,
  dayun_chart: 30
})
const selectedYear = ref(new Date().getFullYear())
const yearlyFortuneResult = ref(null)
const yearlyFortuneLoading = ref(false)

// 大运分析相关
const selectedDayunIndex = ref(0)
const dayunAnalysisResult = ref(null)
const dayunAnalysisLoading = ref(false)
const dayunChartData = ref(null)
const dayunChartLoading = ref(false)

// 积分消耗确认对话框
const pointsConfirmVisible = ref(false)
const pointsConfirmType = ref('') // 'yearly', 'dayun', 'chart'
const pointsConfirmData = ref({})

// 版本提示
const versionHint = computed(() => {
  return versionMode.value === 'simple' 
    ? '简化版：适合新手，只看核心信息，不用填出生地'
    : '专业版：适合进阶，包含真太阳时、大运流年等详细分析'
})

// 中国城市数据
const cities = [
  '北京市', '上海市', '广州市', '深圳市', '杭州市', '南京市', '武汉市', '成都市', '西安市',
  '重庆市', '天津市', '苏州市', '长沙市', '郑州市', '沈阳市', '青岛市', '宁波市', '东莞市',
  '佛山市', '合肥市', '大连市', '厦门市', '福州市', '哈尔滨市', '济南市', '温州市', '长春市',
  '石家庄市', '常州市', '泉州市', '南宁市', '贵阳市', '南昌市', '昆明市', '乌鲁木齐市',
  '兰州市', '呼和浩特市', '海口市', '银川市', '西宁市', '拉萨市', '台北市', '香港', '澳门'
]

const cityOptions = computed(() => {
  return cities.map(city => ({
    value: city,
    label: city
  }))
})

// 获取当前积分和首次排盘状态
const loadPoints = async () => {
  try {
    const response = await getPointsBalance()
    if (response.code === 0) {
      currentPoints.value = response.data.balance
      isFirstBazi.value = response.data.first_bazi !== false
    }
    
    // 获取运势分析积分消耗
    const costResponse = await getFortunePointsCost()
    if (costResponse.code === 0) {
      fortunePointsCost.value = costResponse.data
    }
  } catch (error) {
    console.error('获取积分失败:', error)
  }
}

// 显示积分消耗确认对话框
const showPointsConfirm = (type, data = {}) => {
  const costs = {
    'yearly': fortunePointsCost.value.yearly_fortune,
    'dayun': fortunePointsCost.value.dayun_analysis,
    'chart': fortunePointsCost.value.dayun_chart
  }
  
  if (currentPoints.value < costs[type]) {
    ElMessage.warning(`积分不足，需要${costs[type]}积分，请先签到领取积分`)
    return
  }
  
  pointsConfirmType.value = type
  pointsConfirmData.value = data
  pointsConfirmVisible.value = true
}

// 确认消耗积分
const confirmPointsConsume = async () => {
  pointsConfirmVisible.value = false
  
  switch (pointsConfirmType.value) {
    case 'yearly':
      await getYearlyFortuneAnalysis()
      break
    case 'dayun':
      await getDayunFortuneAnalysis()
      break
    case 'chart':
      await getDayunChartData()
      break
  }
}

// 获取流年运势分析
const getYearlyFortuneAnalysis = async () => {
  if (!result.value?.id) return
  
  yearlyFortuneLoading.value = true
  try {
    const response = await getYearlyFortune({
      bazi_id: result.value.id,
      year: selectedYear.value
    })
    
    if (response.code === 0) {
      yearlyFortuneResult.value = response.data
      currentPoints.value = response.data.remaining_points
      ElMessage.success('流年运势分析完成！')
    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
    console.error('流年运势分析错误:', error)
    ElMessage.error('分析失败，请稍后重试')
  } finally {
    yearlyFortuneLoading.value = false
  }
}

// 获取大运运势分析
const getDayunFortuneAnalysis = async () => {
  if (!result.value?.id) return
  
  dayunAnalysisLoading.value = true
  try {
    const response = await getDayunAnalysis({
      bazi_id: result.value.id,
      dayun_index: selectedDayunIndex.value
    })
    
    if (response.code === 0) {
      dayunAnalysisResult.value = response.data
      currentPoints.value = response.data.remaining_points
      ElMessage.success('大运运势分析完成！')
    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
    console.error('大运运势分析错误:', error)
    ElMessage.error('分析失败，请稍后重试')
  } finally {
    dayunAnalysisLoading.value = false
  }
}

// 获取大运K线图数据
const getDayunChartData = async () => {
  if (!result.value?.id) return
  
  dayunChartLoading.value = true
  try {
    const response = await getDayunChartApi({
      bazi_id: result.value.id
    })
    
    if (response.code === 0) {
      dayunChartData.value = response.data
      currentPoints.value = response.data.remaining_points
      ElMessage.success('运势K线图生成完成！')
    } else {
      ElMessage.error(response.message || '生成失败')
    }
  } catch (error) {
    console.error('大运K线图生成错误:', error)
    ElMessage.error('生成失败，请稍后重试')
  } finally {
    dayunChartLoading.value = false
  }
}

// 获取评分颜色
const getScoreColor = (score) => {
  if (score >= 80) return '#67c23a'
  if (score >= 60) return '#e6a23c'
  if (score >= 40) return '#f56c6c'
  return '#909399'
}

// 获取评分等级样式
const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  if (score >= 40) return 'average'
  return 'poor'
}

// 显示确认对话框
const showConfirm = () => {
  if (!birthDate.value) {
    ElMessage.warning('请选择出生日期')
    return
  }
  if (!isFirstBazi.value && currentPoints.value < 10) {
    ElMessage.warning('积分不足，请先签到领取积分')
    return
  }
  
  // 首次排盘直接计算，不显示确认框
  if (isFirstBazi.value) {
    calculateBazi()
  } else {
    confirmVisible.value = true
  }
}

// 确认排盘
const confirmCalculate = async () => {
  confirmVisible.value = false
  await calculateBazi()
}

const calculateBazi = async () => {
  loading.value = true
  loadingStep.value = 1
  
  // 模拟步骤动画
  const stepInterval = setInterval(() => {
    if (loadingStep.value < 4) {
      loadingStep.value++
    }
  }, 400)
  
  try {
    const response = await calculateBaziApi({
      birthDate: birthDate.value,
      gender: gender.value,
      location: location.value,
      mode: versionMode.value,
    })
    
    clearInterval(stepInterval)
    loadingStep.value = 4
    
    // 延迟一下让用户看到完成状态
    await new Promise(resolve => setTimeout(resolve, 300))
    
    if (response.code === 0) {
      result.value = response.data
      currentPoints.value = response.data.remaining_points
      isFirstBazi.value = false
      ElMessage.success('排盘成功！为你生成详细的命理解读')
    } else {
      ElMessage.error(response.message || '排盘失败')
      // 如果是积分不足，刷新积分
      if (response.code === 403) {
        loadPoints()
      }
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
  } finally {
    clearInterval(stepInterval)
    loading.value = false
    loadingStep.value = 1
  }
}

onMounted(() => {
  loadPoints()
})

// 保存结果
const saveResult = async () => {
  saving.value = true
  try {
    // 保存到本地存储
    const savedResults = JSON.parse(localStorage.getItem('bazi_saved') || '[]')
    savedResults.unshift({
      id: result.value.id,
      date: new Date().toISOString(),
      bazi: result.value.bazi,
      analysis: result.value.analysis
    })
    // 最多保存50条
    if (savedResults.length > 50) {
      savedResults.pop()
    }
    localStorage.setItem('bazi_saved', JSON.stringify(savedResults))
    ElMessage.success('保存成功，可在个人中心查看')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 判断是否当前大运（根据出生日期计算当前年龄）
const isCurrentDaYun = (yun) => {
  if (!birthDate.value) return false
  
  // 计算当前年龄
  const birth = new Date(birthDate.value)
  const now = new Date()
  let age = now.getFullYear() - birth.getFullYear()
  
  // 判断是否已过生日
  const monthDiff = now.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
    age--
  }
  
  return age >= yun.age_start && age <= yun.age_end
}

// 分享结果
const shareResult = () => {
  if (!result.value?.bazi) {
    ElMessage.warning('暂无排盘结果可分享')
    return
  }
  
  const shareText = `我在太初命理进行了八字排盘\n` +
    `日主：${result.value.bazi.day_master || ''}（${result.value.bazi.day_master_wuxing || ''}）\n` +
    `八字：${result.value.bazi.year?.gan || ''}${result.value.bazi.year?.zhi || ''} ${result.value.bazi.month?.gan || ''}${result.value.bazi.month?.zhi || ''} ${result.value.bazi.day?.gan || ''}${result.value.bazi.day?.zhi || ''} ${result.value.bazi.hour?.gan || ''}${result.value.bazi.hour?.zhi || ''}\n` +
    `快来测测你的八字吧！`
  
  if (navigator.share) {
    navigator.share({
      title: '我的八字排盘结果',
      text: shareText
    })
  } else {
    // 复制到剪贴板
    navigator.clipboard.writeText(shareText).then(() => {
      ElMessage.success('分享内容已复制到剪贴板')
    }).catch(() => {
      ElMessage.error('复制失败，请手动复制')
    })
  }
}

// AI解盘
const startAiAnalysis = async () => {
  if (currentPoints.value < 30) {
    ElMessage.warning('积分不足，请先签到领取积分')
    return
  }
  
  aiAnalyzing.value = true
  aiStreamContent.value = ''
  aiLoadingTime.value = 60
  
  // 创建AbortController用于取消请求
  aiAbortController.value = new AbortController()
  
  // 启动倒计时
  aiLoadingTimer.value = setInterval(() => {
    if (aiLoadingTime.value > 0) {
      aiLoadingTime.value--
    } else {
      clearInterval(aiLoadingTimer.value)
    }
  }, 1000)
  
  try {
    // 尝试使用流式API
    const response = await analyzeBaziAiStream(result.value.bazi, aiPrompt.value, aiAbortController.value?.signal)
    
    if (response.ok && response.headers.get('content-type')?.includes('text/event-stream')) {
      // 流式响应
      const reader = response.body.getReader()
      const decoder = new TextDecoder()
      
      let fullContent = ''
      
      while (true) {
        // 检查是否被取消
        if (aiAbortController.value?.signal?.aborted) {
          reader.cancel()
          break
        }
        
        const { done, value } = await reader.read()
        if (done) break
        
        const chunk = decoder.decode(value, { stream: true })
        const lines = chunk.split('\n')
        
        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const data = line.slice(6)
            if (data === '[DONE]') continue
            
            try {
              const parsed = JSON.parse(data)
              if (parsed.choices?.[0]?.delta?.content) {
                const content = parsed.choices[0].delta.content
                fullContent += content
                aiStreamContent.value = fullContent
              }
            } catch (e) {
              // 忽略解析错误
            }
          }
        }
      }
      
      if (!aiAbortController.value?.signal?.aborted) {
        aiAnalysisResult.value = {
          analysis: fullContent,
          model: 'AI'
        }
      }
    } else {
      // 非流式响应
      const res = await analyzeBaziAi(result.value.bazi, aiPrompt.value, aiAbortController.value?.signal)
      if (res.code === 0) {
        aiAnalysisResult.value = res.data
        currentPoints.value = res.data.remaining_points || currentPoints.value - 30
      } else {
        ElMessage.error(res.message || 'AI解盘失败')
      }
    }
  } catch (error) {
    if (error.name === 'AbortError') {
      ElMessage.info('已取消AI分析')
    } else {
      console.error('AI解盘错误:', error)
      ElMessage.error('AI解盘服务暂时不可用，请稍后重试')
    }
  } finally {
    aiAnalyzing.value = false
    clearInterval(aiLoadingTimer.value)
    aiLoadingTime.value = 0
    aiAbortController.value = null
  }
}

// 取消AI分析
const cancelAiAnalysis = () => {
  if (aiAbortController.value) {
    aiAbortController.value.abort()
  }
  aiAnalyzing.value = false
  clearInterval(aiLoadingTimer.value)
  aiLoadingTime.value = 0
  ElMessage.info('已取消AI分析')
}

// 清除AI结果
const clearAiResult = () => {
  aiAnalysisResult.value = null
  aiStreamContent.value = ''
  aiPrompt.value = ''
}

// 格式化AI内容（净化HTML并处理换行）
const formatAiContent = (content) => {
  if (!content) return ''
  // 先净化HTML，防止XSS攻击
  const cleanContent = sanitizeHtml(content, false) // 先转为纯文本
  // 再处理换行
  return cleanContent
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/^(.+)$/, '<p>$1</p>')
}
</script>

<style scoped>
/* 页面级动画 */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes yinYangRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* 加载状态样式 */
.loading-state {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
  padding: 60px 40px;
  background: var(--bg-card);
  border-radius: 20px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.loading-animation {
  margin-bottom: 30px;
}

/* 太极图加载动画 */
.yin-yang {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  border-radius: 50%;
  background: linear-gradient(to bottom, #fff 50%, #000 50%);
  position: relative;
  animation: yinYangRotate 2s linear infinite;
  box-shadow: 0 0 30px rgba(184, 134, 11, 0.3);
}

.yin-yang::before,
.yin-yang::after {
  content: '';
  position: absolute;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  left: 50%;
  transform: translateX(-50%);
}

.yin-yang::before {
  background: #fff;
  top: 0;
  box-shadow: 0 0 0 12px #000 inset;
}

.yin-yang::after {
  background: #000;
  bottom: 0;
  box-shadow: 0 0 0 12px #fff inset;
}

.loading-state h3 {
  color: var(--text-primary);
  font-size: 24px;
  margin-bottom: 10px;
}

.loading-text {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 40px;
}

/* 加载步骤 */
.loading-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  opacity: 0.4;
  transition: all 0.3s ease;
}

.step.active {
  opacity: 1;
}

.step.done {
  opacity: 0.7;
}

.step-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: var(--text-secondary);
  border: 2px solid var(--border-color);
  transition: all 0.3s ease;
}

.step.active .step-icon {
  background: rgba(184, 134, 11, 0.1);
  border-color: var(--primary-color);
  color: var(--primary-color);
  box-shadow: 0 0 15px rgba(184, 134, 11, 0.3);
}

.step.done .step-icon {
  background: rgba(103, 194, 58, 0.3);
  border-color: #67c23a;
  color: #67c23a;
}

.step-text {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  transition: all 0.3s ease;
}

.step.active .step-text {
  color: var(--primary-color);
  font-weight: 500;
}

.step-line {
  width: 40px;
  height: 2px;
  background: var(--border-color);
  transition: all 0.3s ease;
}

.step-line.active {
  background: linear-gradient(90deg, #67c23a, #e94560);
}

/* 结果头部 */
.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.result-header h2 {
  margin: 0;
}

.result-meta {
  display: flex;
  gap: 10px;
}

.meta-tag {
  background: rgba(103, 194, 58, 0.2);
  color: #67c23a;
  padding: 4px 12px;
  border-radius: 15px;
  font-size: 12px;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.bazi-page {
  padding: 60px 0;
  animation: fadeInUp 0.6s ease;
}

/* 结果区域动画 */
.bazi-result {
  animation: fadeInUp 0.8s ease;
}

/* 排盘表格动画 */
.paipan-row {
  opacity: 0;
  animation: fadeInUp 0.5s ease forwards;
}

.paipan-row:nth-child(1) { animation-delay: 0.1s; }
.paipan-row:nth-child(2) { animation-delay: 0.2s; }
.paipan-row:nth-child(3) { animation-delay: 0.3s; }
.paipan-row:nth-child(4) { animation-delay: 0.4s; }
.paipan-row:nth-child(5) { animation-delay: 0.5s; }
.paipan-row:nth-child(6) { animation-delay: 0.6s; }

/* 五行进度条动画 */
.wuxing-fill {
  animation: fillBar 1s ease forwards;
  animation-delay: 0.5s;
  width: 0;
}

@keyframes fillBar {
  to {
    width: var(--target-width);
  }
}

/* 解读卡片依次出现 */
.reading-card {
  opacity: 0;
  animation: fadeInUp 0.5s ease forwards;
}

.reading-card:nth-child(1) { animation-delay: 0.1s; }
.reading-card:nth-child(2) { animation-delay: 0.2s; }
.reading-card:nth-child(3) { animation-delay: 0.3s; }
.reading-card:nth-child(4) { animation-delay: 0.4s; }
.reading-card:nth-child(5) { animation-delay: 0.5s; }
.reading-card:nth-child(6) { animation-delay: 0.6s; }

/* 大运时间线动画 */
.dayun-item {
  opacity: 0;
  animation: fadeInUp 0.5s ease forwards;
}

.dayun-item:nth-child(1) { animation-delay: 0.1s; }
.dayun-item:nth-child(2) { animation-delay: 0.15s; }
.dayun-item:nth-child(3) { animation-delay: 0.2s; }
.dayun-item:nth-child(4) { animation-delay: 0.25s; }
.dayun-item:nth-child(5) { animation-delay: 0.3s; }
.dayun-item:nth-child(6) { animation-delay: 0.35s; }
.dayun-item:nth-child(7) { animation-delay: 0.4s; }
.dayun-item:nth-child(8) { animation-delay: 0.45s; }

/* 当前高亮脉冲效果 */
.dayun-item.current {
  animation: pulse 2s ease-in-out infinite;
}

.liunian-item.current {
  animation: pulse 2s ease-in-out infinite;
}

/* 按钮悬停效果增强 */
.el-button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(233, 69, 96, 0.3);
}

.el-button:active {
  transform: translateY(0);
}

/* 表单输入框焦点效果 */
:deep(.el-input__wrapper) {
  transition: all 0.3s ease;
}

:deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px rgba(233, 69, 96, 0.5);
}

:deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(233, 69, 96, 0.5);
}

/* 加载状态 shimmer 效果 */
.loading-shimmer {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.05) 25%,
    rgba(255, 255, 255, 0.1) 50%,
    rgba(255, 255, 255, 0.05) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

/* 日主信息浮动效果 */
.day-master-card {
  animation: float 3s ease-in-out infinite;
}

/* 五行统计条 */
.wuxing-bar {
  position: relative;
  overflow: hidden;
}

.wuxing-bar::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  animation: shimmer 2s infinite;
}

/* AI解盘打字光标效果 */
.ai-stream-content::after {
  content: '|';
  animation: blink 1s infinite;
  color: #67c23a;
}

@keyframes blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}

/* 响应式优化 */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

.bazi-form {
  max-width: 600px;
  margin: 0 auto 40px;
}

.points-hint {
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.1), rgba(255, 107, 107, 0.1));
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 10px;
  padding: 15px 20px;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255, 255, 255, 0.9);
}

.hint-icon {
  font-size: 20px;
}

.current-points {
  margin-left: auto;
  color: #ffd700;
  font-weight: 500;
}

.insufficient-points {
  margin-top: 15px;
  padding: 12px;
  background: rgba(245, 108, 108, 0.1);
  border: 1px solid rgba(245, 108, 108, 0.3);
  border-radius: 8px;
  text-align: center;
}

.insufficient-points a {
  color: #e94560;
  text-decoration: underline;
}

.form-group {
  margin-bottom: 25px;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.help-icon {
  margin-left: 5px;
  cursor: help;
  opacity: 0.7;
}

.form-hint {
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
  margin-top: 8px;
}

.full-width {
  width: 100%;
}

.bazi-result {
  max-width: 800px;
  margin: 0 auto;
}

.bazi-result h2 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--text-primary);
}

.bazi-paipan {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 15px;
  padding: 30px;
  margin-bottom: 30px;
}

.paipan-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 15px;
}

.paipan-row:last-child {
  margin-bottom: 0;
}

.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 20px;
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
}

.paipan-cell.header {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.6);
  font-weight: normal;
}

.paipan-cell.highlight {
  color: #e94560;
  background: rgba(233, 69, 96, 0.1);
  border-radius: 10px;
}

.bazi-analysis {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 30px;
}

.bazi-analysis h3 {
  margin-bottom: 20px;
  color: var(--text-primary);
  text-align: center;
}

.analysis-content {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
  white-space: pre-line;
}

/* 暖心提示 */
.warm-tip {
  max-width: 600px;
  margin: 0 auto 25px;
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px 25px;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.1), rgba(255, 107, 107, 0.1));
  border: 1px solid rgba(233, 69, 96, 0.2);
}

.tip-icon {
  font-size: 36px;
}

.tip-content {
  text-align: left;
}

.tip-title {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 5px;
}

.tip-desc {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

/* 版本切换 */
.version-toggle {
  margin-bottom: 25px;
  text-align: center;
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
}

.toggle-label {
  color: rgba(255, 255, 255, 0.7);
  margin-right: 10px;
  font-size: 14px;
}

.mode-option {
  display: flex;
  align-items: center;
  gap: 5px;
}

.mode-icon {
  font-size: 16px;
}

.version-hint {
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
  margin-top: 10px;
}

/* 专业解读区域 */
.professional-reading {
  margin: 30px 0;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.08), rgba(255, 107, 107, 0.05));
  border: 1px solid rgba(233, 69, 96, 0.2);
  border-radius: 20px;
  padding: 30px;
}

.professional-reading h3 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.section-badge {
  background: linear-gradient(135deg, #D4AF37, #B8860B);
  color: #fff;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

/* 日主详情卡片 */
.day-master-detail {
  background: var(--bg-secondary);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  border: 1px solid var(--border-light);
}

.dm-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-color);
}

.dm-symbol {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.3), rgba(255, 107, 107, 0.2));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  border: 2px solid rgba(233, 69, 96, 0.5);
}

.dm-title h4 {
  color: var(--text-primary);
  font-size: 20px;
  margin-bottom: 10px;
}

.dm-traits {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.trait-tag {
  background: rgba(233, 69, 96, 0.2);
  color: rgba(255, 255, 255, 0.9);
  padding: 4px 12px;
  border-radius: 15px;
  font-size: 13px;
}

.dm-content {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.dm-section h5 {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  margin-bottom: 8px;
}

.dm-section p {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.7;
  font-size: 14px;
}

/* 喜用神区域 */
.yongshen-section {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.05));
  border: 1px solid rgba(255, 215, 0, 0.3);
  border-radius: 12px;
  padding: 20px 25px;
  margin-bottom: 25px;
}

.ys-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 10px;
}

.ys-icon {
  font-size: 28px;
}

.ys-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ys-info h4 {
  color: #ffd700;
  font-size: 18px;
}

.ys-type {
  background: rgba(255, 215, 0, 0.2);
  color: #ffd700;
  padding: 3px 10px;
  border-radius: 10px;
  font-size: 12px;
}

.ys-desc {
  color: rgba(255, 255, 255, 0.85);
  font-size: 14px;
  line-height: 1.7;
  padding-left: 43px;
}

/* 解读卡片网格 */
.reading-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.reading-card {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 14px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition: all 0.3s ease;
}

.reading-card:hover {
  transform: translateY(-5px);
  background: rgba(0, 0, 0, 0.3);
  border-color: rgba(233, 69, 96, 0.3);
}

.reading-card.advice-card {
  grid-column: span 3;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.15), rgba(133, 206, 97, 0.1));
  border-color: rgba(103, 194, 58, 0.3);
}

.rc-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.rc-icon {
  font-size: 24px;
}

.rc-header h4 {
  color: var(--text-primary);
  font-size: 16px;
}

.rc-content {
  color: rgba(255, 255, 255, 0.85);
  font-size: 14px;
  line-height: 1.7;
}

@media (max-width: 992px) {
  .reading-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .reading-card.advice-card {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .dm-content {
    grid-template-columns: 1fr;
  }
  
  .reading-cards-grid {
    grid-template-columns: 1fr;
  }
  
  .reading-card.advice-card {
    grid-column: span 1;
  }
}

/* 通俗解读 */
.simple-interpretation {
  margin: 30px 0;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.2);
  border-radius: 15px;
  padding: 25px;
}

.simple-interpretation h3 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.section-icon {
  font-size: 24px;
}

.section-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  font-weight: normal;
}

.interpretation-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.interp-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
  transition: all 0.3s ease;
}

.interp-card:hover {
  transform: translateY(-3px);
  background: rgba(255, 255, 255, 0.08);
}

.interp-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.interp-icon {
  font-size: 28px;
}

.interp-header h4 {
  color: var(--text-primary);
  font-size: 16px;
}

.interp-content {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  line-height: 1.7;
}

.interp-card.personality {
  border-left: 3px solid #e94560;
}

.interp-card.career {
  border-left: 3px solid #409eff;
}

.interp-card.relationship {
  border-left: 3px solid #e6a23c;
}

.interp-card.advice {
  border-left: 3px solid #67c23a;
}

@media (max-width: 768px) {
  .interpretation-cards {
    grid-template-columns: 1fr;
  }
}

/* 日主信息 */
.day-master-info {
  display: flex;
  justify-content: center;
  margin-bottom: 30px;
}

.day-master-card {
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.2), rgba(255, 107, 107, 0.2));
  border: 2px solid rgba(233, 69, 96, 0.5);
  border-radius: 15px;
  padding: 20px 40px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.day-master-card .label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.day-master-card .value {
  font-size: 36px;
  font-weight: bold;
  color: #e94560;
}

.day-master-card .wuxing {
  background: rgba(233, 69, 96, 0.3);
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 14px;
  color: #fff;
}

/* 排盘表格样式 */
.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 15px 10px;
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  position: relative;
}

.paipan-cell.header {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.6);
  font-weight: normal;
  padding: 10px;
}

.paipan-cell.highlight {
  background: rgba(233, 69, 96, 0.1);
  border-radius: 10px;
}

.gan-text, .zhi-text {
  font-size: 28px;
}

.wuxing-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.1);
  font-weight: normal;
}

.wuxing-badge.金 { background: rgba(255, 215, 0, 0.3); color: #ffd700; }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.3); color: #90ee90; }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.3); color: #87ceeb; }
.wuxing-badge.火 { background: rgba(255, 69, 0, 0.3); color: #ff6347; }
.wuxing-badge.土 { background: rgba(139, 69, 19, 0.3); color: #deb887; }

.wuxing-badge.zhi {
  opacity: 0.8;
}

.rizhu-tag {
  font-size: 10px;
  background: #e94560;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  position: absolute;
  top: 5px;
  right: 5px;
}

/* 十神行 */
.shishen-row {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  margin: 5px 0;
}

.shishen-cell {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  padding: 8px;
}

/* 藏干行 */
.canggan-row {
  margin-top: 5px;
}

.canggan-cell {
  font-size: 12px;
  padding: 10px 5px;
  min-height: 60px;
}

.canggan-list {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.canggan-item {
  color: rgba(255, 255, 255, 0.8);
}

.canggan-item small {
  color: rgba(255, 255, 255, 0.5);
  font-size: 10px;
  margin-left: 2px;
}

/* 纳音行 */
.nayin-row {
  margin-top: 5px;
  background: rgba(255, 215, 0, 0.05);
  border-radius: 8px;
}

.nayin-cell {
  font-size: 12px;
  color: rgba(255, 215, 0, 0.9);
  padding: 8px;
}

/* 五行统计 */
.wuxing-stats {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 25px;
  margin: 30px 0;
}

.wuxing-stats h3 {
  text-align: center;
  margin-bottom: 20px;
  color: #fff;
}

.wuxing-bars {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.wuxing-bar-item {
  display: flex;
  align-items: center;
  gap: 15px;
}

.wuxing-name {
  width: 30px;
  font-weight: bold;
  color: #fff;
}

.wuxing-bar {
  flex: 1;
  height: 20px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  overflow: hidden;
}

.wuxing-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.5s ease;
}

.wuxing-fill.金 { background: linear-gradient(90deg, #ffd700, #ffec8b); }
.wuxing-fill.木 { background: linear-gradient(90deg, #228b22, #90ee90); }
.wuxing-fill.水 { background: linear-gradient(90deg, #1e90ff, #87ceeb); }
.wuxing-fill.火 { background: linear-gradient(90deg, #ff4500, #ff6347); }
.wuxing-fill.土 { background: linear-gradient(90deg, #8b4513, #deb887); }

.wuxing-count {
  width: 30px;
  text-align: center;
  color: rgba(255, 255, 255, 0.8);
}

/* 操作按钮 */
.result-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 30px;
  flex-wrap: wrap;
}

.result-actions .btn-icon {
  margin-right: 5px;
}

/* 大运区域样式 */
.dayun-section {
  margin-top: 30px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 25px;
}

.dayun-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.dayun-timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

.dayun-item {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

.dayun-item:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-3px);
}

.dayun-item.current {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

.dayun-age {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 10px;
}

.dayun-pillar {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 8px;
}

.dayun-pillar .gan,
.dayun-pillar .zhi {
  font-size: 24px;
  font-weight: bold;
  color: #fff;
}

.dayun-shishen {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 8px;
}

.dayun-luck {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 8px;
}

.dayun-luck.吉 {
  background: rgba(103, 194, 58, 0.3);
  color: #67c23a;
}

.dayun-luck.凶 {
  background: rgba(245, 108, 108, 0.3);
  color: #f56c6c;
}

.dayun-luck.平 {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
}

.dayun-desc {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
  line-height: 1.4;
  margin-bottom: 8px;
}

.dayun-nayin {
  font-size: 11px;
  color: rgba(255, 215, 0, 0.8);
}

/* 流年区域样式 */
.liunian-section {
  margin-top: 30px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 15px;
  padding: 25px;
}

.liunian-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.liunian-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 15px;
}

.liunian-item {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

.liunian-item:hover {
  background: rgba(255, 255, 255, 0.08);
}

.liunian-item.current {
  border-color: #ffd700;
  background: rgba(255, 215, 0, 0.1);
}

.liunian-year {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 10px;
  font-weight: 500;
}

.liunian-pillar {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 10px;
}

.liunian-pillar .gan,
.liunian-pillar .zhi {
  font-size: 22px;
  font-weight: bold;
  color: #fff;
}

.liunian-wuxing {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 8px;
}

.liunian-wuxing .badge {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
}

.liunian-nayin {
  font-size: 11px;
  color: rgba(255, 215, 0, 0.8);
}

/* 积分确认对话框 */
.points-confirm-dialog .points-confirm-content {
  text-align: center;
  padding: 20px;
}

.points-confirm-dialog .points-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.points-confirm-dialog .points-title {
  font-size: 20px;
  color: #fff;
  font-weight: bold;
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc strong {
  color: #ffd700;
  font-size: 20px;
}

.points-confirm-dialog .points-balance {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

/* 流年运势分析 */
.yearly-fortune-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(233, 69, 96, 0.05));
  border: 1px solid rgba(255, 107, 107, 0.3);
  border-radius: 20px;
  padding: 30px;
}

.yearly-fortune-section h3 {
  color: var(--text-primary);
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.year-selector {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
}

.selector-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  white-space: nowrap;
}

.year-slider {
  flex: 1;
}

.selected-year {
  color: #ff6b6b;
  font-size: 18px;
  font-weight: bold;
  min-width: 70px;
}

/* 流年分析结果 */
.yearly-result {
  animation: fadeInUp 0.5s ease;
}

.yearly-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(0, 0, 0, 0.25);
  border-radius: 16px;
}

.year-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.year-number {
  font-size: 36px;
  font-weight: bold;
  color: #ff6b6b;
}

.year-ganzhi {
  font-size: 20px;
  color: #fff;
}

.year-nayin {
  font-size: 14px;
  color: rgba(255, 215, 0, 0.9);
}

.score-display {
  display: flex;
  align-items: center;
  gap: 15px;
}

.score-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 3px solid;
  background: rgba(0, 0, 0, 0.3);
}

.score-circle.excellent { border-color: #67c23a; }
.score-circle.good { border-color: #e6a23c; }
.score-circle.average { border-color: #f56c6c; }
.score-circle.poor { border-color: #909399; }

.score-value {
  font-size: 28px;
  font-weight: bold;
  color: #fff;
}

.score-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.rating-badge {
  padding: 8px 20px;
  border-radius: 20px;
  font-size: 18px;
  font-weight: bold;
  color: #fff;
}

.rating-badge.excellent { background: linear-gradient(135deg, #67c23a, #85ce61); }
.rating-badge.good { background: linear-gradient(135deg, #e6a23c, #ebb563); }
.rating-badge.average { background: linear-gradient(135deg, #f56c6c, #f78989); }
.rating-badge.poor { background: linear-gradient(135deg, #909399, #a6a9ad); }

/* 分析卡片 */
.yearly-analysis {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.analysis-card {
  background: var(--bg-secondary);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--border-light);
}

.analysis-card h4 {
  color: var(--text-primary);
  font-size: 16px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.analysis-card p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
}

.analysis-card.overall {
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.1), rgba(212, 175, 55, 0.05));
  border: 1px solid rgba(184, 134, 11, 0.3);
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.analysis-card.advice {
  background: linear-gradient(135deg, rgba(34, 139, 34, 0.08), rgba(60, 179, 113, 0.05));
  border: 1px solid rgba(34, 139, 34, 0.3);
}

/* 幸运信息 */
.lucky-info {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
  margin-top: 10px;
}

.lucky-section h5 {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  margin-bottom: 10px;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.lucky-tag {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 13px;
}

.lucky-tag.good {
  background: rgba(103, 194, 58, 0.3);
  color: #67c23a;
}

.lucky-tag.bad {
  background: rgba(245, 108, 108, 0.3);
  color: #f56c6c;
}

.lucky-tag.color {
  background: rgba(255, 215, 0, 0.3);
  color: #ffd700;
}

.lucky-tag.number {
  background: rgba(64, 158, 255, 0.3);
  color: #409eff;
}

/* 大运运势分析 */
.dayun-fortune-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(64, 158, 255, 0.1), rgba(83, 168, 255, 0.05));
  border: 1px solid rgba(64, 158, 255, 0.3);
  border-radius: 20px;
  padding: 30px;
}

.dayun-fortune-section h3 {
  color: var(--text-primary);
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.dayun-selector {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  padding: 15px 20px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  flex-wrap: wrap;
}

/* 大运分析结果 */
.dayun-analysis-result {
  animation: fadeInUp 0.5s ease;
}

.dayun-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(0, 0, 0, 0.25);
  border-radius: 16px;
}

.dayun-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.dayun-name {
  font-size: 32px;
  font-weight: bold;
  color: #409eff;
}

.dayun-shishen {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  padding: 5px 12px;
  background: rgba(64, 158, 255, 0.2);
  border-radius: 15px;
}

.dayun-age {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

.dayun-level-badge {
  padding: 10px 25px;
  border-radius: 25px;
  font-size: 20px;
  font-weight: bold;
  color: #fff;
}

.dayun-level-badge.excellent { background: linear-gradient(135deg, #67c23a, #85ce61); }
.dayun-level-badge.good { background: linear-gradient(135deg, #e6a23c, #ebb563); }
.dayun-level-badge.average { background: linear-gradient(135deg, #f56c6c, #f78989); }
.dayun-level-badge.poor { background: linear-gradient(135deg, #909399, #a6a9ad); }

/* 分数条 */
.dayun-scores {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.score-item {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.score-item:last-child {
  margin-bottom: 0;
}

.score-name {
  width: 50px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.score-progress {
  flex: 1;
}

.score-value {
  width: 40px;
  text-align: right;
  color: #fff;
  font-weight: bold;
}

/* 分析文本 */
.dayun-analysis-text {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 25px;
}

.text-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 15px;
}

.text-card p {
  color: rgba(255, 255, 255, 0.85);
  line-height: 1.8;
  font-size: 14px;
}

/* 关键建议 */
.key-suggestions {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.05));
  border: 1px solid rgba(255, 215, 0, 0.3);
  border-radius: 12px;
  padding: 20px;
}

.key-suggestions h4 {
  color: #ffd700;
  margin-bottom: 15px;
  font-size: 16px;
}

.key-suggestions ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.key-suggestions li {
  color: rgba(255, 255, 255, 0.85);
  padding: 8px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
}

.key-suggestions li::before {
  content: '💡';
  position: absolute;
  left: 0;
}

/* 运势K线图 */
.fortune-chart-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.3);
  border-radius: 20px;
  padding: 30px;
}

.fortune-chart-section h3 {
  color: var(--text-primary);
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.chart-result {
  animation: fadeInUp 0.5s ease;
}

.chart-summary {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.chart-summary p {
  color: rgba(255, 255, 255, 0.85);
  margin-bottom: 10px;
}

.best-period {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.best-label {
  color: rgba(255, 255, 255, 0.6);
}

.best-value {
  color: #67c23a;
  font-weight: bold;
  font-size: 16px;
}

/* K线图表容器 */
.chart-container {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.chart-dayun {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 20px;
}

.chart-dayun-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.dayun-title {
  font-size: 18px;
  font-weight: bold;
  color: #fff;
}

.dayun-score {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 14px;
  font-weight: bold;
}

.dayun-score.excellent { background: rgba(103, 194, 58, 0.3); color: #67c23a; }
.dayun-score.good { background: rgba(230, 162, 60, 0.3); color: #e6a23c; }
.dayun-score.average { background: rgba(245, 108, 108, 0.3); color: #f56c6c; }
.dayun-score.poor { background: rgba(144, 147, 153, 0.3); color: #909399; }

.dayun-trend {
  margin-left: auto;
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.chart-years {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 150px;
  padding: 10px 0;
  margin-bottom: 10px;
}

.chart-year-bar {
  flex: 1;
  min-width: 20px;
  background: linear-gradient(to top, #67c23a, #85ce61);
  border-radius: 4px 4px 0 0;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  padding-bottom: 5px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.chart-year-bar:hover {
  opacity: 0.8;
  transform: scaleX(1.1);
}

.chart-year-bar.current {
  background: linear-gradient(to top, #ffd700, #ffec8b);
  box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

.year-label {
  position: absolute;
  bottom: -20px;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.6);
  white-space: nowrap;
}

.year-score {
  font-size: 10px;
  color: rgba(0, 0, 0, 0.7);
  font-weight: bold;
}

.chart-legend {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 25px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

/* 分析按钮区域 */
.analysis-actions {
  text-align: center;
  padding: 30px;
}

.analysis-desc {
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 20px;
  font-size: 14px;
}

/* AI解盘区域 */
.ai-analysis-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.2);
  border-radius: 15px;
  padding: 25px;
}

.ai-analysis-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.ai-desc {
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
  margin-bottom: 15px;
}

.ai-input {
  text-align: center;
}

.ai-result {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
}

.ai-result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ai-model {
  color: #67c23a;
  font-weight: 500;
}

.ai-content {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.8;
  font-size: 15px;
}

.ai-content p {
  margin-bottom: 12px;
}

.ai-loading {
  text-align: center;
  padding: 30px;
}

.ai-loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  color: rgba(255, 255, 255, 0.8);
}

.ai-loading-spinner .spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(103, 194, 58, 0.3);
  border-top-color: #67c23a;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.ai-stream-content {
  margin-top: 20px;
  padding: 15px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  text-align: left;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  min-height: 100px;
  max-height: 400px;
  overflow-y: auto;
}

.ai-loading-timeout {
  text-align: center;
  margin: 10px 0;
  color: rgba(255, 255, 255, 0.6);
}

.ai-loading-timeout .timeout-text {
  font-size: 14px;
}

.ai-loading-actions {
  display: flex;
  justify-content: center;
  margin-top: 15px;
}

@media (max-width: 768px) {
  .paipan-cell {
    font-size: 18px;
    padding: 10px 5px;
  }
  
  .gan-text, .zhi-text {
    font-size: 22px;
  }
  
  .shishen-cell {
    font-size: 12px;
  }
  
  .canggan-cell {
    font-size: 10px;
    min-height: 50px;
  }
  
  .nayin-cell {
    font-size: 10px;
  }
  
  .day-master-card .value {
    font-size: 28px;
  }
  
  .dayun-timeline {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .liunian-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .ai-analysis-section {
    padding: 15px;
  }
  
  .ai-content {
    font-size: 14px;
  }
  
  /* 流年运势响应式 */
  .yearly-header {
    flex-direction: column;
    gap: 20px;
  }
  
  .year-info {
    flex-direction: column;
    align-items: center;
  }
  
  .year-number {
    font-size: 28px;
  }
  
  .analysis-grid {
    grid-template-columns: 1fr;
  }
  
  .lucky-info {
    grid-template-columns: repeat(2, 1fr);
  }
  
  /* 大运分析响应式 */
  .dayun-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .dayun-info {
    flex-direction: column;
    align-items: center;
  }
  
  .dayun-name {
    font-size: 24px;
  }
  
  .score-item {
    flex-wrap: wrap;
  }
  
  .score-name {
    width: 40px;
  }
  
  /* K线图响应式 */
  .chart-years {
    height: 120px;
  }
  
  .chart-year-bar {
    min-width: 15px;
  }
  
  .year-label {
    font-size: 8px;
    transform: rotate(-45deg);
    bottom: -15px;
  }
  
  .year-score {
    font-size: 8px;
  }
}
</style>
