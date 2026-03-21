<template>
  <div class="bazi-page">
    <div class="container">
      <PageHeroHeader
        title="八字排盘"
        subtitle="支持精确出生时间、大概时段与未知时辰三种口径，结果页会同步标注当前精度，避免把估算时刻误当成精确排盘。"
        :icon="Grid"
      />

      
      <!-- 暖心提示 -->
      <div class="warm-tip card" v-if="!result">
        <el-icon class="tip-icon"><StarFilled /></el-icon>
        <div class="tip-content">
          <p class="tip-title">八字排盘能帮你了解什么？</p>
          <p class="tip-desc">你的性格优势 · 适合的发展方向 · 未来运势起伏 · 人际关系建议</p>
        </div>
      </div>
      
      <div class="bazi-form card" v-if="!result">

        <!-- 版本选择 -->
        <div class="version-select-section">
          <h3 class="version-select-section__title">版本选择</h3>
          <div class="version-cards">
            <!-- 简化版 -->
            <div
              class="version-card"
              :class="{ 'version-card--active': versionMode === 'simple' }"
              @click="versionMode = 'simple'"
            >
              <div class="version-card__header">
                <div class="version-card__icon">
                  <el-icon><MagicStick /></el-icon>
                </div>
                <div class="version-card__info">
                  <span class="version-card__name">简化版</span>
                  <span v-if="versionMode === 'simple'" class="version-card__badge">当前选择</span>
                </div>
                <span class="version-card__pts">10 积分</span>
              </div>
              <ul class="version-card__features">
                <li><el-icon><Check /></el-icon> 完整的八字命盘数据（天干地支、五行、十神等）</li>
                <li><el-icon><Check /></el-icon> 专属的性格内观与事业财运分析</li>
                <li><el-icon><Check /></el-icon> 永久保存在您的历史记录中，随时查看</li>
              </ul>
            </div>
            <!-- 专业版 -->
            <div
              class="version-card"
              :class="{ 'version-card--active': versionMode === 'pro' }"
              @click="versionMode = 'pro'"
            >
              <div class="version-card__header">
                <div class="version-card__icon">
                  <el-icon><Coin /></el-icon>
                </div>
                <div class="version-card__info">
                  <span class="version-card__name">专业版</span>
                  <span v-if="versionMode === 'pro'" class="version-card__badge">当前选择</span>
                </div>
                <span class="version-card__pts">50 积分</span>
              </div>
              <ul class="version-card__features">
                <li><el-icon><Check /></el-icon> 完整的八字命盘数据（天干地支、五行、十神等）</li>
                <li><el-icon><Check /></el-icon> 专属的性格内观与事业财运分析</li>
                <li><el-icon><Check /></el-icon> 永久保存在您的历史记录中，随时查看</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="form-group form-group--time" data-bazi-field="birth-time">
          <div class="form-group__header form-group__header--time">
            <label>出生日期与时间</label>
            <span class="form-group__status">{{ birthTimeAccuracy === 'exact' ? '精确排盘' : '估算模式' }}</span>
          </div>

          <!-- 历法类型切换 -->
          <div class="calendar-type-switch">
            <div class="calendar-type-switch__label">历法类型</div>
            <el-radio-group v-model="calendarType" size="small">
              <el-radio label="solar">
                <el-icon><Calendar /></el-icon>
                公历
              </el-radio>
              <el-radio label="lunar">
                <el-icon><StarFilled /></el-icon>
                农历
              </el-radio>
            </el-radio-group>
            <p class="calendar-type-switch__hint" v-if="calendarType === 'lunar'">
              <el-icon><InfoFilled /></el-icon>
              农历生日系统会自动转换为公历进行排盘计算
            </p>
          </div>

          <div class="time-accuracy-switch">
            <div class="time-accuracy-switch__copy">
              <span class="switch-label">时间确认度</span>
              <p class="time-accuracy-switch__hint">先选你记忆的准确程度，再填写对应时间信息。</p>
            </div>
            <el-radio-group v-model="birthTimeAccuracy" size="small" class="time-accuracy-group premium-segment premium-segment--card">
              <el-radio-button label="exact">
                <span class="precision-option">
                  <span class="precision-option__title">精确到分钟</span>
                  <small class="precision-option__desc">适合已知完整出生时刻</small>
                </span>
              </el-radio-button>
              <el-radio-button label="estimated">
                <span class="precision-option">
                  <span class="precision-option__title">大概时段 / 未知时辰</span>
                  <small class="precision-option__desc">只记得白天、晚上或完全不记得</small>
                </span>
              </el-radio-button>
            </el-radio-group>
          </div>

          <div class="time-entry-panel" :class="`time-entry-panel--${birthTimeAccuracy}`">
            <div class="time-entry-panel__header">
              <span class="time-entry-panel__badge">{{ birthTimeAccuracy === 'exact' ? '精确填写' : '估算填写' }}</span>
              <p class="time-entry-panel__title">{{ birthTimeAccuracy === 'exact' ? '请选择出生时间' : '请选择出生日期与大概时段' }}</p>
            </div>

            <template v-if="birthTimeAccuracy === 'exact'">
              <!-- 公历日期选择 -->
              <template v-if="calendarType === 'solar'">
                <el-date-picker
                  v-model="exactBirthDate"
                  type="datetime"
                  placeholder="选择出生日期时间（精确到分钟）"
                  format="YYYY-MM-DD HH:mm"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  class="full-width time-entry-panel__control"
                />
              </template>

              <!-- 农历日期选择 -->
              <template v-else>
                <div class="lunar-date-input">
                  <div class="lunar-date-row">
                    <el-input-number
                      v-model="lunarYear"
                      :min="1900"
                      :max="2100"
                      placeholder="年"
                      class="lunar-input"
                      :controls="false"
                    />
                    <span class="lunar-label">年</span>
                    <el-input-number
                      v-model="lunarMonth"
                      :min="1"
                      :max="12"
                      placeholder="月"
                      class="lunar-input"
                      :controls="false"
                    />
                    <span class="lunar-label">月</span>
                    <el-input-number
                      v-model="lunarDay"
                      :min="1"
                      :max="30"
                      placeholder="日"
                      class="lunar-input"
                      :controls="false"
                    />
                    <span class="lunar-label">日</span>
                  </div>
                  <div class="lunar-time-row">
                    <el-select v-model="lunarHour" placeholder="选择时辰" class="lunar-time-select">
                      <el-option label="子时 (23:00-01:00)" :value="0" />
                      <el-option label="丑时 (01:00-03:00)" :value="1" />
                      <el-option label="寅时 (03:00-05:00)" :value="3" />
                      <el-option label="卯时 (05:00-07:00)" :value="5" />
                      <el-option label="辰时 (07:00-09:00)" :value="7" />
                      <el-option label="巳时 (09:00-11:00)" :value="9" />
                      <el-option label="午时 (11:00-13:00)" :value="11" />
                      <el-option label="未时 (13:00-15:00)" :value="13" />
                      <el-option label="申时 (15:00-17:00)" :value="15" />
                      <el-option label="酉时 (17:00-19:00)" :value="17" />
                      <el-option label="戌时 (19:00-21:00)" :value="19" />
                      <el-option label="亥时 (21:00-23:00)" :value="21" />
                    </el-select>
                  </div>
                  <div v-if="convertedSolarDate" class="lunar-converted-hint">
                    <el-icon><InfoFilled /></el-icon>
                    转换为公历：{{ convertedSolarDate }}
                  </div>
                </div>
              </template>
              <p class="form-hint time-entry-panel__hint">精确到分钟时，命盘细节最完整；若记不清，可先切到估算模式。</p>
            </template>

            <template v-else>
              <div class="estimate-birth-grid time-entry-panel__grid">
                <el-date-picker
                  v-model="estimatedBirthDate"
                  type="date"
                  placeholder="选择出生日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  class="full-width time-entry-panel__control"
                />
                <el-select v-model="estimatedTimeSlot" placeholder="选择大概时段或未知时辰" class="full-width time-entry-panel__control" clearable>
                  <el-option
                    v-for="option in estimatedTimeOptions"
                    :key="option.value"
                    :label="option.label"
                    :value="option.value"
                  />
                </el-select>
              </div>
              <p class="form-hint form-hint--precision time-entry-panel__hint"><el-icon><Warning /></el-icon> {{ estimatedModeHint }}</p>
            </template>
          </div>

        </div>
        
        <div class="form-group" data-bazi-field="gender">
          <label>性别</label>
          <el-radio-group v-model="gender">
            <el-radio label="male">男</el-radio>
            <el-radio label="female">女</el-radio>
          </el-radio-group>
        </div>
        
        <div class="form-group" data-bazi-field="location">
          <label>
            出生地点 <span class="required-mark">*</span>
            <el-tooltip content="用于计算真太阳时，让排盘更准确" placement="top">
              <el-icon class="help-icon"><QuestionFilled /></el-icon>
            </el-tooltip>
          </label>
          <el-select-v2
            v-model="location"
            :options="cityOptions"
            placeholder="请选择出生城市"
            class="full-width"
            filterable
            clearable
            :height="200"
          />
          <p class="form-hint"><el-icon><MagicStick /></el-icon> 请选择出生城市，系统会根据地点计算真太阳时，让排盘更准确。</p>
        </div>

        <!-- 提交前校验提示 -->
        <section v-if="baziSubmitIssues.length" class="submit-summary-card" role="alert" aria-live="assertive">
          <div class="submit-summary-card__header">
            <div>
              <strong>提交前还差这几步</strong>
              <p>{{ baziSubmitSummaryText }}</p>
            </div>
            <el-icon><Warning /></el-icon>
          </div>
          <div class="submit-summary-card__actions">
            <button
              v-for="issue in baziSubmitIssues"
              :key="issue.key"
              type="button"
              class="submit-summary-card__action"
              @click="handleBaziIssue(issue)"
            >
              <span>{{ issue.actionLabel }}</span>
              <small>{{ issue.message }}</small>
            </button>
          </div>
        </section>

        <!-- 费用与权益确认 -->
        <div class="cost-confirm-section">
          <div class="cost-confirm-section__header">
            <h3 class="cost-confirm-section__title">费用与权益确认</h3>
            <div class="cost-confirm-section__points">
              <span>当前积分：<strong>{{ currentPoints }}</strong></span>
              <router-link to="/profile" class="cost-confirm-section__add">+</router-link>
            </div>
          </div>
          <p class="cost-confirm-section__cost">
            本次排盘将消耗：<strong>{{ versionMode === 'pro' ? '50' : '10' }} 积分</strong>
          </p>
          <div class="cost-confirm-section__benefits">
            <div class="cost-confirm-benefit">
              <el-icon><Check /></el-icon> 完整的八字命盘数据（天干地支、五行、十神等）
            </div>
            <div class="cost-confirm-benefit">
              <el-icon><Check /></el-icon> 专属的性格内观与事业财运分析
            </div>
            <div class="cost-confirm-benefit">
              <el-icon><Check /></el-icon> 失败保护：若排盘失败或未完成，将自动退还积分
            </div>
          </div>
          <el-button
            type="primary"
            size="large"
            class="cost-confirm-section__btn"
            @click="showConfirm"
            :loading="loading"
            :disabled="loading"
          >
            <el-icon v-if="isAccountReady && isFirstBazi"><Present /></el-icon>
            {{ startBaziButtonText }}
          </el-button>
          <!-- 积分不足提示 -->
          <div v-if="accountStatus === 'ready' && !isFirstBazi && currentPoints < BAZI_BASE_COST" class="insufficient-points">
            <el-icon><StarFilled /></el-icon>
            积分不足，请先 <router-link to="/profile">签到领取积分</router-link>
          </div>
        </div>
      </div>

      <!-- 确认对话框 -->
      <el-dialog
        v-model="confirmVisible"
        :title="confirmDialogConfig.title"
        width="400px"
        class="confirm-dialog"
      >
        <div class="confirm-content" :class="{ 'confirm-content--free': isFirstBazi }">
          <p class="confirm-title">
            <el-icon v-if="isFirstBazi"><Present /></el-icon>
            <template v-if="isFirstBazi">
              本次为您的首次排盘，不会扣除积分
            </template>
            <template v-else>
              本次排盘将消耗 <strong>10 积分</strong>
            </template>
          </p>
          <p>排盘后可在个人中心查看历史记录</p>
          <p class="confirm-note">规则说明：首次排盘免费，后续每次排盘消耗 10 积分。</p>
        </div>
        <template #footer>
          <el-button @click="confirmVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmCalculate">{{ confirmDialogConfig.actionText }}</el-button>
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
          <div class="points-icon"><el-icon :size="48"><Coin /></el-icon></div>
          <p class="points-title">{{ getPointsConfirmTitle(pointsConfirmType) }}</p>
          <p class="points-desc">
            此功能将消耗
            <strong>{{ getPointsConfirmCost(pointsConfirmType) }} 积分</strong>
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
          <div class="loading-taiji"></div>
        </div>
        <h3>正在为你排盘...</h3>
        <p class="loading-text">计算天干地支 · 分析五行配置 · 生成命理解读</p>
        <div class="loading-steps">
          <div class="step" :class="{ active: loadingStep >= 1, done: loadingStep > 1 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 1"><Check /></el-icon><template v-else>1</template></span>
            <span class="step-text">排八字</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 2 }"></div>
          <div class="step" :class="{ active: loadingStep >= 2, done: loadingStep > 2 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 2"><Check /></el-icon><template v-else>2</template></span>
            <span class="step-text">算五行</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 3 }"></div>
          <div class="step" :class="{ active: loadingStep >= 3, done: loadingStep > 3 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 3"><Check /></el-icon><template v-else>3</template></span>
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
            <span class="meta-tag meta-tag--success" v-if="result.is_first_bazi"><el-icon><Present /></el-icon> 首次免费</span>
            <span class="meta-tag meta-tag--success" v-if="result.from_cache"><el-icon><Lightning /></el-icon> 智能缓存</span>
            <span class="meta-tag meta-tag--info"><el-icon><MagicStick /></el-icon> {{ resultModeLabel }}</span>
            <span class="meta-tag" :class="birthTimeAccuracy === 'estimated' ? 'meta-tag--warning' : 'meta-tag--neutral'"><el-icon><Calendar /></el-icon> {{ birthTimeAccuracyLabel }}</span>
            <span class="meta-tag meta-tag--neutral"><el-icon><QuestionFilled /></el-icon> {{ locationContextLabel }}</span>
          </div>
        </div>
        <p v-if="resultContextNote" class="result-context-note">{{ resultContextNote }}</p>

        <div class="result-tabs">
          <div class="tab-item" :class="{ active: activeTab === 'chart' }" @click="activeTab = 'chart'">
             <el-icon><Grid /></el-icon> 本命局
          </div>
          <div class="tab-item" :class="{ active: activeTab === 'personality' }" @click="activeTab = 'personality'">
             <el-icon><UserFilled /></el-icon> 性格内观
          </div>
          <div class="tab-item" :class="{ active: activeTab === 'career' }" @click="activeTab = 'career'">
             <el-icon><Briefcase /></el-icon> 事业财运
          </div>
          <div class="tab-item" :class="{ active: activeTab === 'fortune' }" @click="activeTab = 'fortune'">
             <el-icon><Calendar /></el-icon> 流年大运
          </div>
        </div>

        <div class="tab-content" v-show="activeTab === 'chart'">
          <!-- 命盘基础部分 -->
          <div class="tab-pane-content">
            <div class="pane-title">
                <el-icon class="title-icon"><Grid /></el-icon>
                <span class="title-text">命盘核心数据</span>
                <span class="title-desc">日主、八字、五行分布</span>
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
              <!-- 旬空行 -->
              <div class="paipan-row xunkong-row" v-if="result.bazi?.xunkong">
                <div class="paipan-cell xunkong-cell">
                   <span class="xunkong-label">年空:</span> {{ result.bazi?.year?.xunkong || '-' }}
                </div>
                <div class="paipan-cell xunkong-cell">
                   <span class="xunkong-label">月空:</span> {{ result.bazi?.month?.xunkong || '-' }}
                </div>
                <div class="paipan-cell xunkong-cell highlight">
                   <span class="xunkong-label">日空:</span> {{ result.bazi?.day?.xunkong || '-' }}
                </div>
                <div class="paipan-cell xunkong-cell">
                   <span class="xunkong-label">时空:</span> {{ result.bazi?.hour?.xunkong || '-' }}
                </div>
              </div>
            </div>

            
            <!-- 五行统计 -->
            <div class="wuxing-stats">
              <div class="wuxing-header">
                <h3>五行分布</h3>
                <p class="wuxing-caption">以下为加权值，综合了天干透出、地支藏干与月令司令权重，并非简单计数。</p>
              </div>
              <div class="wuxing-bars">
                <div v-for="item in wuxingDistributionItems" :key="item.name" class="wuxing-bar-item">
                  <div class="wuxing-bar-main">
                    <span class="wuxing-name">{{ item.name }}</span>
                    <div class="wuxing-bar">
                      <div class="wuxing-fill" :class="item.name" :style="{ width: `${item.width}%`, '--target-width': `${item.width}%` }"></div>
                    </div>
                  </div>
                  <div class="wuxing-meta">
                    <span class="wuxing-count">{{ item.displayValue }}</span>
                    <span class="wuxing-unit">加权值</span>
                    <span class="wuxing-share">{{ item.shareText }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="tab-content" v-show="activeTab === 'personality' || activeTab === 'career' || activeTab === 'fortune'">
          <div class="tab-pane-content">
            <div class="pane-title" v-if="activeTab === 'personality'">
                <el-icon class="title-icon"><UserFilled /></el-icon>
                <span class="title-text">性格内观</span>
            </div>
            <div class="pane-title" v-if="activeTab === 'career'">
                <el-icon class="title-icon"><Briefcase /></el-icon>
                <span class="title-text">事业财运</span>
            </div>
            <div class="pane-title" v-if="activeTab === 'fortune'">
                <el-icon class="title-icon"><Calendar /></el-icon>
                <span class="title-text">流年大运</span>
                <span class="title-desc">10年大运周期 + 当前流年重点分析</span>
            </div>

          <!-- 性格与解读部分 (Shared Content with v-show logic inside) -->
            <!-- Note: professional-reading and simple-interpretation logic will be patched later -->

            <!-- 专业解读卡片 -->
            <div class="professional-reading" v-if="result.fullInterpretation">
              <div class="section-subtitle-wrapper">
                <span class="section-badge">专业版</span>
              </div>
              
              <!-- 日主信息卡片 -->
              <div class="day-master-detail" v-if="result.fullInterpretation.basic" v-show="activeTab === 'personality'">
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
              <div class="yongshen-section" v-if="result.fullInterpretation.yongshen" v-show="activeTab === 'personality'">
                <div class="ys-header">
                  <el-icon class="ys-icon"><StarFilled /></el-icon>
                  <div class="ys-info">
                    <h4>喜用神：{{ result.fullInterpretation.yongshen.shen }}、{{ result.fullInterpretation.yongshen.xi }}</h4>
                    <span class="ys-type">{{ result.fullInterpretation.yongshen.type }}格</span>
                  </div>
                </div>
                <p class="ys-desc">{{ result.fullInterpretation.yongshen.desc }}</p>
              </div>

              <!-- 详细解读卡片网格 -->
              <div class="reading-cards-grid">
                <div class="reading-card card-hover" v-if="result.fullInterpretation.personality" v-show="activeTab === 'personality'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><UserFilled /></el-icon>
                    <h4>性格详解</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.personality }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.career" v-show="activeTab === 'career'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Briefcase /></el-icon>
                    <h4>事业财运</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.career }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.wealth" v-show="activeTab === 'career'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Money /></el-icon>
                    <h4>财富分析</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.wealth }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.relationship" v-show="activeTab === 'personality'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><UserFilled /></el-icon>
                    <h4>感情婚姻</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.relationship }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.health" v-show="activeTab === 'personality'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Aim /></el-icon>
                    <h4>健康提醒</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.health }}</p>
                </div>
                
                <div class="reading-card advice-card card-hover" v-if="result.fullInterpretation.advice" v-show="activeTab === 'personality'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><StarFilled /></el-icon>
                    <h4>开运建议</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.advice }}</p>
                </div>
                
                <!-- 盲派铁口直断 -->
                <div class="reading-card tieko-card card-hover" v-if="result.tiekoDingyu && result.tiekoDingyu.length > 0" v-show="activeTab === 'personality'">
                  <div class="rc-header tieko-header">
                    <el-icon class="rc-icon tieko-icon"><Lightning /></el-icon>
                    <div class="tieko-title-group">
                      <h4>盲派铁口直断</h4>
                      <div class="tieko-match-info">
                        <span class="match-count">匹配{{ result.tiekoMatchCount || 0 }}项</span>
                        <span class="match-level" :class="result.tiekoMatchLevel">
                          {{ result.tiekoMatchLevel === 'high' ? '高准确度' : result.tiekoMatchLevel === 'medium' ? '中等准确度' : '较低准确度' }}
                        </span>
                        <span class="match-accuracy">{{ result.tiekoAccuracy || 0 }}%置信度</span>
                      </div>
                    </div>
                  </div>
                  <div class="tieko-dingyu-list">
                    <div v-for="(item, index) in result.tiekoDingyu" :key="index" class="tieko-item">
                      <div class="tieko-item-tags">
                        <span v-for="(tag, tagIdx) in item.tags || []" :key="tagIdx" class="tieko-tag" :class="tag">
                          {{ tag }}
                        </span>
                      </div>
                      <p class="tieko-item-content">{{ item.content }}</p>
                      <div class="tieko-item-score">
                        <el-rate v-model="item.score" disabled show-score text-color="#D4AF37"></el-rate>
                      </div>
                    </div>
                  </div>
                  <div class="tieko-hint">
                    <el-icon><InfoFilled /></el-icon>
                    <span>铁口直断基于盲派命理理论，条件匹配越多，定语准确度越高。仅供参考，命运掌握在自己手中。</span>
                  </div>
                </div>
                
                <!-- 流年大运内容 -->
                <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation.fortune" v-show="activeTab === 'fortune'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Calendar /></el-icon>
                    <h4>10年大运周期</h4>
                  </div>
                  <div class="fortune-timeline">
                    <div v-for="(period, index) in result.fullInterpretation.fortune.periods" :key="index" class="fortune-period">
                      <div class="period-header">
                        <span class="period-years">{{ period.years }}</span>
                        <span class="period-status" :class="period.status">{{ period.statusText }}</span>
                      </div>
                      <p class="period-desc">{{ period.description }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation.fortune" v-show="activeTab === 'fortune'">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><TrendCharts /></el-icon>
                    <h4>当前流年重点</h4>
                  </div>
                  <div class="current-fortune">
                    <div class="fortune-year">{{ result.fullInterpretation.fortune.currentYear }}年运势</div>
                    <div class="fortune-highlights">
                      <div v-for="(highlight, index) in result.fullInterpretation.fortune.highlights" :key="index" class="highlight-item">
                        <span class="highlight-type">{{ highlight.type }}</span>
                        <span class="highlight-desc">{{ highlight.description }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 通俗解读：这对我意味着什么 -->
            <div class="simple-interpretation" v-if="result.simpleInterpretation && !result.fullInterpretation">
              <div class="section-subtitle-wrapper">
                <span class="section-subtitle">通俗解读</span>
              </div>
              <div class="interpretation-cards">
                <div class="interp-card personality card-hover" v-show="activeTab === 'personality'">
                  <div class="interp-header">
                    <el-icon class="interp-icon"><UserFilled /></el-icon>
                    <h4>我的性格特点</h4>
                  </div>
                  <p class="interp-content">{{ result.simpleInterpretation.personality }}</p>
                </div>
                <div class="interp-card career card-hover" v-show="activeTab === 'career'">
                  <div class="interp-header">
                    <el-icon class="interp-icon"><Briefcase /></el-icon>
                    <h4>适合的发展方向</h4>
                  </div>
                  <p class="interp-content">{{ result.simpleInterpretation.career }}</p>
                </div>
                <div class="interp-card relationship card-hover" v-show="activeTab === 'personality'">
                  <div class="interp-header">
                    <el-icon class="interp-icon"><UserFilled /></el-icon>
                    <h4>人际关系建议</h4>
                  </div>
                  <p class="interp-content">{{ result.simpleInterpretation.relationship }}</p>
                </div>
                <div class="interp-card advice card-hover" v-show="activeTab === 'personality'">
                  <div class="interp-header">
                    <el-icon class="interp-icon"><StarFilled /></el-icon>
                    <h4>给你的建议</h4>
                  </div>
                  <p class="interp-content">{{ result.simpleInterpretation.advice }}</p>
                </div>
              </div>
            </div>

            <div class="bazi-analysis" v-show="activeTab === 'personality'">
              <h3>详细命理分析</h3>
              <div class="analysis-content">{{ result.analysis }}</div>
            </div>
          <!-- 运势趋势部分 (For Career Tab) -->
          <div class="fortune-section-wrapper" v-if="showAdvancedResultSections" v-show="activeTab === 'career'">
            <div class="section-divider"></div>
            <div class="pane-title">
                <el-icon class="title-icon"><TrendCharts /></el-icon>
                <span class="title-text">大运与流年走势</span>
                <span class="title-desc">十年大运、逐年流年参考</span>
            </div>

            <!-- 大运分析 -->
            <div class="dayun-section" v-if="result.dayun && result.dayun.length > 0">
              <div class="section-title-with-tip">
                <h3>大运走势</h3>
                <el-tooltip content="大运是十年一个周期的人生阶段分析，反映不同时期的性格特点" placement="top">
                  <span class="help-icon"><el-icon><QuestionFilled /></el-icon></span>
                </el-tooltip>
              </div>
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
              <div class="section-title-with-tip">
                <h3>流年运势</h3>
                <el-tooltip content="流年是每年的运势参考，结合大运提供年度生活建议" placement="top">
                  <span class="help-icon"><el-icon><QuestionFilled /></el-icon></span>
                </el-tooltip>
              </div>
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
          <!-- 深度预测部分 (For Career Tab) -->
          </div>
          <div class="tools-section-wrapper" v-if="showAdvancedResultSections" v-show="activeTab === 'career'">
            <div class="section-divider"></div>
            <div class="pane-title">
                <el-icon class="title-icon"><Aim /></el-icon>
                <span class="title-text">深度预测工具</span>
                <span class="title-desc">流年深度分析、大运评分、运势K线</span>
            </div>

            <!-- 流年运势分析 -->
            <div class="yearly-fortune-section" v-if="result.bazi">
              <div class="section-title-with-tag">
                <h3>流年运势深度分析</h3>
                <el-tag type="warning" size="small">{{ getFortuneToolTagText('yearly') }}</el-tag>
              </div>
              
              <!-- 年份选择 -->
              <div class="year-selector">
                <div class="year-selector__header">
                  <div class="year-selector__meta">
                    <span class="selector-label">{{ isCompactViewport ? '年份' : '选择年份' }}</span>
                    <span class="selector-hint">拖动滑块切换当前流年分析年份</span>
                  </div>
                  <span class="selected-year">{{ selectedYear }}年</span>
                </div>
                <el-slider
                  v-model="selectedYear"
                  :min="new Date().getFullYear() - 3"
                  :max="new Date().getFullYear() + 7"
                  :step="1"
                  :show-stops="!isCompactViewport"
                  class="year-slider"
                />
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
                    <h4><el-icon><Aim /></el-icon> 整体运势</h4>
                    <p>{{ yearlyFortuneResult.overall }}</p>
                  </div>
                  
                  <div class="analysis-grid">
                    <div class="analysis-card">
                      <h4><el-icon><Briefcase /></el-icon> 事业运势</h4>
                      <p>{{ yearlyFortuneResult.career }}</p>
                    </div>
                    <div class="analysis-card">
                      <h4><el-icon><Money /></el-icon> 财富运势</h4>
                      <p>{{ yearlyFortuneResult.wealth }}</p>
                    </div>
                    <div class="analysis-card">
                      <h4><el-icon><UserFilled /></el-icon> 感情运势</h4>
                      <p>{{ yearlyFortuneResult.relationship }}</p>
                    </div>
                    <div class="analysis-card">
                      <h4><el-icon><Warning /></el-icon> 健康提醒</h4>
                      <p>{{ yearlyFortuneResult.health }}</p>
                    </div>
                  </div>
                  
                  <div class="analysis-card advice">
                    <h4><el-icon><StarFilled /></el-icon> 开运建议</h4>
                    <p>{{ yearlyFortuneResult.advice }}</p>
                  </div>
                  
                  <div class="lucky-info">
                    <div class="lucky-section">
                      <h5>幸运月份</h5>
                      <div class="lucky-tags">
                        <span v-for="month in yearlyFortuneResult.lucky_months" :key="month" class="lucky-tag good">
                          {{ month }}月
                        </span>
                      </div>
                    </div>
                    <div class="lucky-section">
                      <h5>注意月份</h5>
                      <div class="lucky-tags">
                        <span v-for="month in yearlyFortuneResult.unlucky_months" :key="month" class="lucky-tag bad">
                          {{ month }}月
                        </span>
                      </div>
                    </div>
                    <div class="lucky-section">
                      <h5>幸运颜色</h5>
                      <div class="lucky-tags">
                        <span v-for="color in yearlyFortuneResult.lucky_colors" :key="color" class="lucky-tag color">
                          {{ color }}
                        </span>
                      </div>
                    </div>
                    <div class="lucky-section">
                      <h5>幸运数字</h5>
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
                  :disabled="!canUseFortuneTool('yearly')"
                  @click="showPointsConfirm('yearly')"
                >
                  <el-icon class="btn-icon"><StarFilled /></el-icon>
                  {{ getFortuneToolActionText('yearly', '开始流年分析') }}
                </el-button>
              </div>
            </div>

            <!-- 大运运势分析 -->
            <div class="dayun-fortune-section" v-if="result.dayun && result.dayun.length > 0">
              <div class="section-title-with-tag">
                <h3>大运运势评分</h3>
                <el-tag type="warning" size="small">{{ getFortuneToolTagText('dayun') }}</el-tag>
              </div>
              
              <!-- 大运选择 -->
              <div class="dayun-selector">
                <span class="selector-label">选择大运：</span>
                <el-radio-group v-model="selectedDayunIndex" size="small" class="premium-segment premium-segment--scroll">
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
                  <h4><el-icon><StarFilled /></el-icon> 关键建议</h4>
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
                  :disabled="!canUseFortuneTool('dayun')"
                  @click="showPointsConfirm('dayun')"
                >
                  <el-icon class="btn-icon"><TrendCharts /></el-icon>
                  {{ getFortuneToolActionText('dayun', '开始大运评分') }}
                </el-button>
              </div>
            </div>

            <!-- 运势K线图 -->
            <div class="fortune-chart-section" v-if="result.dayun && result.dayun.length > 0">
              <div class="section-title-with-tag">
                <h3>运势K线图</h3>
                <el-tag type="warning" size="small">{{ getFortuneToolTagText('chart') }}</el-tag>
              </div>
              
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
                  :disabled="!canUseFortuneTool('chart')"
                  @click="showPointsConfirm('chart')"
                >
                  <el-icon><TrendCharts /></el-icon>
                  {{ getFortuneToolActionText('chart', '生成运势K线图') }}
                </el-button>
              </div>
            </div>
          <!-- AI 解盘部分 (For Personality Tab) -->
          </div>
          <div class="ai-section-wrapper" v-show="activeTab === 'personality'">
            <div class="section-divider"></div>
            <div class="pane-title">
                <el-icon class="title-icon"><Cpu /></el-icon>
                <span class="title-text">AI 智能解盘</span>
                <span class="title-desc">基于 AI 的深度命理对话与分析</span>
            </div>

            <!-- AI智能解盘 -->
            <div class="ai-analysis-section" v-if="result.bazi">
              <div class="section-title-with-tag">
                <h3>AI智能解盘</h3>
                <el-tag type="warning" size="small">{{ aiPricingTagText }}</el-tag>
              </div>
              
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
                  :disabled="!canStartAiAnalysis"
                  @click="startAiAnalysis"
                >
                  <el-icon><MagicStick /></el-icon>
                  {{ aiActionText }}
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
          </div>
        </div>
        </div> <!-- End of Tab 2/3 container -->

        <!-- 操作按钮 -->
        <div class="result-actions-wrap">
          <div class="result-actions-heading">
            <span class="result-actions-heading__eyebrow">下一步动作</span>
            <p>先保存或回看记录，再决定要不要继续深入解读；动作顺序和首页入口保持同一套节奏。</p>
          </div>
          <div class="result-actions">
            <el-button type="primary" @click="saveResult" :loading="saving">
              <el-icon><Download /></el-icon> 保存到当前设备
            </el-button>
            <el-button @click="openBaziHistoryCenter">
              <el-icon><Document /></el-icon> 查看我的记录
            </el-button>
            <el-button @click="continueBaziJourney">
              <el-icon><Cpu /></el-icon> 继续深入解读
            </el-button>
            <ShareCard
              title="八字排盘"
              :summary="baziShareSummary"
              :tags="baziShareTags"
              :sharePath="`/bazi?id=${result.id}`"
            >
              <template #trigger>
                <el-button>
                  <el-icon><Share /></el-icon> 分享摘要
                </el-button>
              </template>
            </ShareCard>
            <el-button @click="resetCurrentResult">
              <el-icon><RefreshRight /></el-icon> 重新排盘
            </el-button>
          </div>
        </div>
        <WisdomText />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'

import { ElMessage, ElMessageBox } from 'element-plus'
import { Coin, MagicStick, QuestionFilled, Present, Lightning, StarFilled, Aim, Money, Briefcase, UserFilled, Warning, Check, Calendar, TrendCharts, Document, InfoFilled, Grid, Cpu, CircleClose, Download, Share, RefreshRight, Promotion, EditPen } from '@element-plus/icons-vue'

import WisdomText from '../components/WisdomText.vue'

import {
  calculateBazi as calculateBaziApi,
  getPointsBalance,
  getYearlyFortune,
  getDayunAnalysis,
  getDayunChart as getDayunChartApi,
  getFortunePointsCost,
  getClientConfig
} from '../api'
import { analyzeBaziAi, analyzeBaziAiStream } from '../api/ai'
import PageHeroHeader from '../components/PageHeroHeader.vue'
import ShareCard from '../components/ShareCard.vue'
import { sanitizeHtml } from '../utils/sanitize'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../utils/tracker'

import { CHINA_CITIES } from '../utils/constants'
import { Lunar } from 'lunar-javascript'

const router = useRouter()
const route = useRoute()
const activeTab = ref('chart')

const BAZI_BASE_COST = 10
const AI_ANALYSIS_DEFAULT_COST = 30
const WUXING_WEIGHT_MAX = 9.5


const estimatedTimeOptions = [
  { value: 'before-dawn', label: '凌晨（约 01:30）', time: '01:30:00', shortLabel: '凌晨' },
  { value: 'morning', label: '早晨（约 07:30）', time: '07:30:00', shortLabel: '早晨' },
  { value: 'forenoon', label: '上午（约 10:30）', time: '10:30:00', shortLabel: '上午' },
  { value: 'noon', label: '中午（约 12:00）', time: '12:00:00', shortLabel: '中午' },
  { value: 'afternoon', label: '下午（约 15:30）', time: '15:30:00', shortLabel: '下午' },
  { value: 'evening', label: '晚上（约 20:30）', time: '20:30:00', shortLabel: '晚上' },
  { value: 'unknown', label: '未知时辰（仅按生日趋势）', time: '', shortLabel: '未知时辰', mode: 'date-only' },
]

const resolveEstimatedTimeSlotByClock = (clock = '') => {
  const [hour = '12'] = String(clock || '').split(':')
  const parsedHour = Number(hour)

  if (!Number.isFinite(parsedHour)) {
    return ''
  }

  if (parsedHour < 6) return 'before-dawn'
  if (parsedHour < 9) return 'morning'
  if (parsedHour < 12) return 'forenoon'
  if (parsedHour < 14) return 'noon'
  if (parsedHour < 18) return 'afternoon'
  return 'evening'
}

const calendarType = ref('solar') // 历法类型：solar公历, lunar农历

// 农历日期输入
const lunarYear = ref('')
const lunarMonth = ref('')
const lunarDay = ref('')
const lunarHour = ref('')

// 农历转公历计算
const convertedSolarDate = computed(() => {
  if (calendarType.value === 'solar') {
    return exactBirthDate.value || ''
  }

  // 农历转公历
  try {
    if (!lunarYear.value || !lunarMonth.value || !lunarDay.value) {
      return ''
    }

    const year = Number(lunarYear.value)
    const month = Number(lunarMonth.value)
    const day = Number(lunarDay.value)
    const hour = lunarHour.value ? Number(lunarHour.value) : 12

    // 创建农历对象
    const lunar = Lunar.fromYmd(year, month, day)
    // 获取公历日期
    const solar = lunar.getSolar()
    const solarYear = solar.getYear()
    const solarMonth = String(solar.getMonth()).padStart(2, '0')
    const solarDay = String(solar.getDay()).padStart(2, '0')
    const solarHour = String(hour).padStart(2, '0')
    const solarMinute = '00'

    return `${solarYear}-${solarMonth}-${solarDay} ${solarHour}:${solarMinute}:00`
  } catch (error) {
    console.error('农历转公历失败:', error)
    return ''
  }
})

// 格式化农历日期显示
const lunarDateDisplay = computed(() => {
  if (!lunarYear.value || !lunarMonth.value || !lunarDay.value) {
    return ''
  }
  const monthText = Number(lunarMonth.value) < 10 ? `0${lunarMonth.value}` : lunarMonth.value
  const dayText = Number(lunarDay.value) < 10 ? `0${lunarDay.value}` : lunarDay.value
  const hourText = lunarHour.value ? String(lunarHour.value).padStart(2, '0') : '12'
  return `${lunarYear.value}年${monthText}月${dayText}日 ${hourText}:00`
})

const birthTimeAccuracy = ref('exact')
const exactBirthDate = ref('')
const estimatedBirthDate = ref('')
const estimatedTimeSlot = ref('')
const selectedEstimatedTimeOption = computed(() => {
  return estimatedTimeOptions.find((option) => option.value === estimatedTimeSlot.value) || null
})
const isEstimatedDateOnly = computed(() => selectedEstimatedTimeOption.value?.mode === 'date-only')
const birthDate = computed(() => {
  if (birthTimeAccuracy.value === 'exact') {
    return calendarType.value === 'lunar' ? convertedSolarDate.value : exactBirthDate.value
  }

  if (!estimatedBirthDate.value || !selectedEstimatedTimeOption.value) {
    return ''
  }

  if (isEstimatedDateOnly.value) {
    return estimatedBirthDate.value
  }

  return `${estimatedBirthDate.value} ${selectedEstimatedTimeOption.value.time}`
})
const estimatedModeHint = computed(() => {
  if (!estimatedTimeSlot.value) {
    return '请选择一个大概时段，或明确选择“未知时辰”。'
  }

  if (isEstimatedDateOnly.value) {
    return '当前仅按生日趋势排盘，时柱细节会保守展示。'
  }

  return `当前按“${selectedEstimatedTimeOption.value.label}”估算时刻，结果页会同步标记为估算模式。`
})

const baziSubmitIssues = ref([])

const baziStrategySummary = computed(() => {
  const modeText = versionMode.value === 'pro' ? '专业版' : '简化版'
  const accuracyText = birthTimeAccuracy.value === 'exact' ? '精确时刻' : '估算时刻'
  return `先用${modeText} + ${accuracyText}完成一次排盘，再按结果决定是否继续深入。`
})

const baziStrategyDetails = computed(() => ([
  {
    key: 'mode',
    title: versionMode.value === 'pro' ? '当前选择：专业版' : '当前选择：简化版',
    description: versionMode.value === 'pro'
      ? '会补充出生地、进阶结构和后续分析入口，适合已经确定要看更完整结论时使用。'
      : '先看命局轮廓与核心提示，适合第一次体验或只想快速确认整体方向。'
  },
  {
    key: 'accuracy',
    title: birthTimeAccuracy.value === 'exact' ? '当前时间策略：精确到分钟' : '当前时间策略：估算时刻 / 未知时辰',
    description: birthTimeAccuracy.value === 'exact'
      ? '精确时间更适合看时柱、起运点和后续流年细节；如果暂时记不清，再切换估算模式即可。'
      : '估算模式适合先拿到方向参考，结果页会明确提示当前精度，避免把估算口径误读成精确结论。'
  },
  {
    key: 'pricing',
    title: '提交节奏',
    description: isFirstBazi.value
      ? '当前仍保留首次免费资格；填写完再提交，系统会在关键步骤前再次确认。'
      : '当前按单次排盘计费；若你想先确认投入节奏，可先保存结果、回看记录，再决定是否继续深入解读。'
  }
]))

const baziSubmitSummaryText = computed(() => {
  if (!baziSubmitIssues.value.length) {
    return ''
  }

  return `已整理出 ${baziSubmitIssues.value.length} 个待处理项，点一下即可定位或直接处理。`
})

const gender = ref('male')
const location = ref('')
const loading = ref(false)
const result = ref(null)
const currentPoints = ref(0)
const accountStatus = ref('loading')
const fortunePricingStatus = ref('loading')
const aiPricingStatus = ref('loading')
const aiAnalysisCost = ref(AI_ANALYSIS_DEFAULT_COST)
const confirmVisible = ref(false)

const saving = ref(false)
const versionMode = ref('simple') // 'simple' or 'pro'
const isFirstBazi = ref(true) // 是否首次排盘

const loadingStep = ref(1) // 加载步骤
const stepIntervalRef = ref(null) // 步骤动画定时器引用
const activeNames = ref(['basic']) // 折叠面板默认展开“命盘信息”

// AI解盘相关
const aiPrompt = ref('')
const aiAnalyzing = ref(false)
const aiAnalysisResult = ref(null)
const aiStreamContent = ref('')
const aiLoadingTime = ref(0)
const aiAbortController = ref(null)
const aiLoadingTimer = ref(null)

const isUnauthorizedResult = (settledResult) => {
  if (!settledResult) {
    return false
  }

  if (settledResult.status === 'fulfilled') {
    return settledResult.value?.code === 401
  }

  return settledResult.reason?.response?.status === 401
}

const syncCurrentPoints = (remainingPoints, fallbackCost = 0) => {

  const parsedRemainingPoints = Number(remainingPoints)
  if (Number.isFinite(parsedRemainingPoints)) {
    currentPoints.value = parsedRemainingPoints
  } else {
    const parsedFallbackCost = Number(fallbackCost)
    if (Number.isFinite(parsedFallbackCost) && parsedFallbackCost > 0) {
      currentPoints.value = Math.max(0, currentPoints.value - parsedFallbackCost)
    }
  }

  window.dispatchEvent(new CustomEvent('points-updated', {
    detail: {
      balance: currentPoints.value,
    },
  }))
}



// 流年运势相关
const fortunePointsCost = ref({
  yearly_fortune: null,
  dayun_analysis: null,
  dayun_chart: null,
})
const selectedYear = ref(new Date().getFullYear())
const yearlyFortuneResult = ref(null)
const yearlyFortuneLoading = ref(false)
const lastAnalyzedYear = ref(null)
const isCompactViewport = ref(false)

const updateViewportState = () => {
  isCompactViewport.value = window.innerWidth <= 520
}


// 大运分析相关
const selectedDayunIndex = ref(0)
const dayunAnalysisResult = ref(null)
const dayunAnalysisLoading = ref(false)
const lastAnalyzedDayunIndex = ref(null)
const dayunChartData = ref(null)
const dayunChartLoading = ref(false)


// 积分消耗确认对话框
const pointsConfirmVisible = ref(false)
const pointsConfirmType = ref('') // 'yearly', 'dayun', 'chart', 'ai'
const pointsConfirmData = ref({})


const resetDerivedAnalysisState = () => {
  yearlyFortuneResult.value = null
  dayunAnalysisResult.value = null
  dayunChartData.value = null
  aiAnalysisResult.value = null
  aiStreamContent.value = ''
  aiPrompt.value = ''
  lastAnalyzedYear.value = null
  lastAnalyzedDayunIndex.value = null
  selectedYear.value = new Date().getFullYear()
  selectedDayunIndex.value = 0

  activeNames.value = getDefaultActiveNames()

  if (aiAbortController.value) {
    aiAbortController.value.abort()
  }

  if (aiLoadingTimer.value) {
    clearInterval(aiLoadingTimer.value)
    aiLoadingTimer.value = null
  }

  aiAnalyzing.value = false
  aiLoadingTime.value = 0
  aiAbortController.value = null
}

const resetCurrentResult = () => {
  resetDerivedAnalysisState()
  result.value = null
  activeTab.value = 'chart'
}

// 版本提示
const versionHint = computed(() => {
  return versionMode.value === 'simple' 
    ? '简化版：适合新手，只看核心信息，不用填出生地'
    : '专业版：适合进阶，包含真太阳时、大运流年等详细分析'
})
const resultModeLabel = computed(() => (versionMode.value === 'pro' ? '专业版结果' : '简化版结果'))
const showAdvancedResultSections = computed(() => versionMode.value === 'pro')
const birthTimeAccuracyLabel = computed(() => {
  if (birthTimeAccuracy.value === 'exact') {
    return '精确到分钟'
  }

  if (isEstimatedDateOnly.value) {
    return '未知时辰 · 仅生日趋势'
  }

  return selectedEstimatedTimeOption.value
    ? `估算时刻 · ${selectedEstimatedTimeOption.value.shortLabel}`
    : '待确认时段'
})

const locationContextLabel = computed(() => {
  if (versionMode.value !== 'pro') {
    return '未填写出生地 · 默认北京时间'
  }

  return location.value ? `${location.value} · 真太阳时校准` : '未填写出生地 · 默认北京时间'
})
const resultContextNote = computed(() => {
  if (birthTimeAccuracy.value === 'estimated') {
    if (isEstimatedDateOnly.value) {
      return '当前按“未知时辰”仅基于生日趋势排盘，尤其时柱、起运点与流年细节更适合做方向参考。'
    }

    if (selectedEstimatedTimeOption.value) {
      return `当前按“${selectedEstimatedTimeOption.value.label}”估算时刻排盘，尤其时柱与流年细节更适合做方向参考。`
    }
  }

  if (!showAdvancedResultSections.value) {

    return '当前为简化版结果，已自动收起大运、流年与深度预测工具；想继续深入，可切换到专业版。'
  }

  return ''
})
const getDefaultActiveNames = () => (showAdvancedResultSections.value ? ['basic', 'interpretation', 'fortune'] : ['basic', 'interpretation'])

const cityOptions = computed(() => {
  return CHINA_CITIES.map(city => ({
    value: city,
    label: city
  }))
})

const formatWuxingScore = (value) => {
  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return Number(numericValue.toFixed(2)).toString()
}

const wuxingDistributionItems = computed(() => {
  const wuxingOrder = ['金', '木', '水', '火', '土']
  const stats = result.value?.bazi?.wuxing_stats || {}
  const normalizedStats = wuxingOrder.map((name) => {
    const numericValue = Number(stats[name] ?? 0)
    return {
      name,
      value: Number.isFinite(numericValue) ? Math.max(0, numericValue) : 0,
    }
  })
  const total = normalizedStats.reduce((sum, item) => sum + item.value, 0)

  return normalizedStats.map((item) => {
    const width = Math.min(100, (item.value / WUXING_WEIGHT_MAX) * 100)
    const share = total > 0 ? (item.value / total) * 100 : 0

    return {
      ...item,
      width: Number.isFinite(width) ? Number(width.toFixed(1)) : 0,
      displayValue: formatWuxingScore(item.value),
      shareText: total > 0 ? `占比 ${share.toFixed(1)}%` : '占比 0%',
    }
  })
})

const isAccountReady = computed(() => accountStatus.value === 'ready')
const isGuestAccount = computed(() => accountStatus.value === 'guest')
const isGuestFortunePricing = computed(() => fortunePricingStatus.value === 'guest')

const isFortunePricingReady = computed(() => fortunePricingStatus.value === 'ready')
const isAiPricingReady = computed(() => aiPricingStatus.value === 'ready' || aiPricingStatus.value === 'fallback')


const confirmDialogConfig = computed(() => {
  if (isFirstBazi.value) {
    return {
      title: '首次排盘确认',
      actionText: '开始排盘',
    }
  }

  return {
    title: '确认排盘',
    actionText: '确认排盘',
  }
})

const canStartBazi = computed(() => {
  if (!birthDate.value || !isAccountReady.value || !location.value) {
    return false
  }

  return isFirstBazi.value || currentPoints.value >= BAZI_BASE_COST
})

const startBaziButtonText = computed(() => {
  if (!birthDate.value) {
    return '请选择出生日期'
  }

  if (accountStatus.value === 'loading') {
    return '账户信息查询中...'
  }

  if (accountStatus.value === 'guest') {
    return '请先登录后排盘'
  }

  if (accountStatus.value === 'error') {
    return '请先同步账户信息'
  }

  return isFirstBazi.value ? '首次免费排盘' : '开始排盘'
})

const clearBaziSubmitIssues = () => {
  baziSubmitIssues.value = []
}

const focusBaziField = async (selector) => {
  if (!selector) {
    return
  }

  await nextTick()
  const target = document.querySelector(selector)
  if (!(target instanceof HTMLElement)) {
    return
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  const focusable = target.querySelector('input, textarea, button, [tabindex]:not([tabindex="-1"])')
  if (focusable instanceof HTMLElement) {
    focusable.focus({ preventScroll: true })
  }
}

const handleBaziIssue = (issue) => {
  if (issue?.handler) {
    issue.handler()
    return
  }

  if (issue?.route) {
    router.push(issue.route)
    return
  }

  focusBaziField(issue?.selector)
}

const buildBaziSubmitIssues = () => {
  const issues = []

  if (birthTimeAccuracy.value === 'estimated') {
    if (!estimatedBirthDate.value) {
      issues.push({
        key: 'estimated-date',
        actionLabel: '补充出生日期',
        message: '估算模式下仍需先选择出生日期。',
        selector: '[data-bazi-field="birth-time"]'
      })
    }

    if (!estimatedTimeSlot.value) {
      issues.push({
        key: 'estimated-slot',
        actionLabel: '选择时段',
        message: '请选择一个大概时段，或明确标记为未知时辰。',
        selector: '[data-bazi-field="birth-time"]'
      })
    }
  } else if (!birthDate.value) {
    issues.push({
      key: 'exact-date',
      actionLabel: '填写出生时间',
      message: '请先选择精确到分钟的出生日期时间。',
      selector: '[data-bazi-field="birth-time"]'
    })
  }

  if (!gender.value) {
    issues.push({
      key: 'gender',
      actionLabel: '确认性别',
      message: '排盘前还需要确认性别。',
      selector: '[data-bazi-field="gender"]'
    })
  }

  if (isGuestAccount.value) {
    issues.push({
      key: 'guest',
      actionLabel: '先去登录',
      message: '登录后才能同步积分、免费资格和提交说明。',
      route: '/login'
    })
  }

  if (accountStatus.value === 'error') {
    issues.push({
      key: 'account-error',
      actionLabel: '重新获取账户状态',
      message: '账户信息还没同步成功，刷新后再提交更稳。',
      handler: () => loadPoints()
    })
  }

  if (!isFirstBazi.value && currentPoints.value < BAZI_BASE_COST) {
    issues.push({
      key: 'points',
      actionLabel: '去充值或补积分',
      message: `当前积分不足，本次排盘还需要 ${BAZI_BASE_COST} 积分。`,
      route: '/recharge'
    })
  }

  return issues
}

const openBaziHistoryCenter = () => {
  router.push('/profile')
}

const continueBaziJourney = async () => {
  activeTab.value = showAdvancedResultSections.value ? 'career' : 'personality'
  await nextTick()
  const selector = showAdvancedResultSections.value ? '.tools-section-wrapper, .fortune-section-wrapper' : '.ai-section-wrapper, .bazi-analysis'
  const target = document.querySelector(selector)
  if (target instanceof HTMLElement) {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }

  ElMessage.info(showAdvancedResultSections.value ? '已切到进阶分析区，继续往下看即可。' : '已切到性格与 AI 解读区，继续往下看即可。')
}

watch([
  birthDate,
  estimatedBirthDate,
  estimatedTimeSlot,
  birthTimeAccuracy,
  gender,
  accountStatus,
  currentPoints,
  versionMode
], () => {
  if (baziSubmitIssues.value.length) {
    clearBaziSubmitIssues()
  }
})

const getFortuneToolCost = (type) => {
  const costKeyMap = {
    yearly: 'yearly_fortune',
    dayun: 'dayun_analysis',
    chart: 'dayun_chart',
  }

  const rawCost = fortunePointsCost.value[costKeyMap[type]]
  const parsedCost = Number(rawCost)
  return Number.isFinite(parsedCost) ? parsedCost : null
}

const getPointsConfirmTitle = (type = '') => {
  const titleMap = {
    yearly: '流年运势分析',
    dayun: '大运运势评分',
    chart: '运势K线图',
    ai: 'AI 智能解盘',
  }

  return titleMap[type] || '积分功能'
}

const getPointsConfirmCost = (type = '') => {
  if (type === 'ai') {
    return aiAnalysisCost.value
  }

  const cost = getFortuneToolCost(type)
  return Number.isFinite(cost) ? cost : 0
}

const getFortuneToolTagText = (type) => {

  if (fortunePricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (isGuestAccount.value || isGuestFortunePricing.value) {
    return '登录后显示'
  }

  if (fortunePricingStatus.value === 'error') {
    return '说明稍后确认'
  }

  const cost = getFortuneToolCost(type)
  if (!Number.isFinite(cost)) {
    return '说明待确认'
  }


  return cost > 0 ? `消耗${cost}积分` : '本次免费'
}

const canUseFortuneTool = (type) => {
  if (!isAccountReady.value || !isFortunePricingReady.value) {
    return false
  }

  const cost = getFortuneToolCost(type)
  return Number.isFinite(cost) && currentPoints.value >= cost
}

const getFortuneToolActionText = (type, readyText) => {
  if (accountStatus.value === 'loading' || fortunePricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (isGuestAccount.value || isGuestFortunePricing.value) {
    return '请先登录'
  }

  if (accountStatus.value === 'error' || fortunePricingStatus.value === 'error') {
    return '请先刷新当前说明'
  }

  const cost = getFortuneToolCost(type)
  if (!Number.isFinite(cost)) {
    return '说明待确认'
  }

  if (cost > 0 && currentPoints.value < cost) {
    return `积分不足（需${cost}积分）`
  }

  return readyText
}


watch(birthTimeAccuracy, (mode) => {
  if (mode === 'estimated') {
    if (!estimatedBirthDate.value && exactBirthDate.value) {
      estimatedBirthDate.value = exactBirthDate.value.slice(0, 10)
    }

    if (!estimatedTimeSlot.value && exactBirthDate.value) {
      const clockMatch = exactBirthDate.value.match(/(\d{2}:\d{2})/)
      estimatedTimeSlot.value = clockMatch ? resolveEstimatedTimeSlotByClock(clockMatch[1]) : ''
    }
    return
  }

  if (!exactBirthDate.value && estimatedBirthDate.value && selectedEstimatedTimeOption.value && !isEstimatedDateOnly.value) {
    exactBirthDate.value = `${estimatedBirthDate.value} ${selectedEstimatedTimeOption.value.time}`
  }
})


watch(selectedYear, (newYear, oldYear) => {
  if (newYear === oldYear) {
    return
  }

  if (lastAnalyzedYear.value !== null && newYear !== lastAnalyzedYear.value) {
    yearlyFortuneResult.value = null
  }
})

watch(selectedDayunIndex, (newIndex, oldIndex) => {
  if (newIndex === oldIndex) {
    return
  }

  if (lastAnalyzedDayunIndex.value !== null && newIndex !== lastAnalyzedDayunIndex.value) {
    dayunAnalysisResult.value = null
  }
})

const resolveAiAnalysisCost = (clientConfig = {}) => {

  const costs = clientConfig?.points?.costs || {}
  const candidates = [
    costs.ai_analysis,
    costs.aiAnalysis,
    costs.bazi_ai_analysis,
    clientConfig?.ai_analysis_cost,
  ]

  for (const candidate of candidates) {
    const parsed = Number(candidate)
    if (Number.isFinite(parsed)) {
      return Math.max(0, parsed)
    }
  }

  return null
}

const aiPricingTagText = computed(() => {
  if (accountStatus.value === 'loading' || aiPricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (accountStatus.value === 'error') {
    return '账户稍后确认'
  }

  if (aiPricingStatus.value === 'fallback') {
    return `预计消耗${aiAnalysisCost.value}积分`
  }

  return aiAnalysisCost.value > 0 ? `消耗${aiAnalysisCost.value}积分` : '本次免费'
})

const canStartAiAnalysis = computed(() => {
  if (!isAccountReady.value || !isAiPricingReady.value) {
    return false
  }

  return currentPoints.value >= aiAnalysisCost.value
})

const aiActionText = computed(() => {
  if (accountStatus.value === 'loading') {
    return '账户查询中'
  }

  if (aiPricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (accountStatus.value === 'error') {
    return '请先刷新账户信息'
  }

  if (!isAiPricingReady.value) {
    return '请先刷新价格信息'
  }

  return currentPoints.value < aiAnalysisCost.value ? `积分不足（需${aiAnalysisCost.value}积分）` : '开始AI解盘'
})

const needsFortunePriceRecovery = computed(() => accountStatus.value === 'error' || fortunePricingStatus.value === 'error')

const fortunePriceRecoveryText = computed(() => {
  if (accountStatus.value === 'error') {
    return '当前账户状态还没同步成功，重新获取后即可继续查看积分与操作说明。'
  }

  if (fortunePricingStatus.value === 'error') {
    return '深度工具说明还没同步成功，刷新后即可继续流年分析、大运评分和运势 K 线。'
  }

  return ''
})
const aiNeedsAccountRecovery = computed(() => accountStatus.value === 'error')
const aiRecoveryText = computed(() => {
  return aiNeedsAccountRecovery.value
    ? 'AI 解盘依赖当前账户积分，重新获取账户状态后即可继续使用。'
    : ''
})


const refreshFortunePricing = () => {
  loadPoints()
}


// 获取当前积分和首次排盘状态
const loadPoints = async ({ silent = false } = {}) => {
  accountStatus.value = 'loading'
  fortunePricingStatus.value = 'loading'
  aiPricingStatus.value = 'loading'

  const preloadRequestConfig = {
    silent: true,
    skipGlobalError: true,
    skipAuthRedirect: true,
  }

  const [accountResult, pricingResult, clientConfigResult] = await Promise.allSettled([
    getPointsBalance(preloadRequestConfig),
    getFortunePointsCost(preloadRequestConfig),
    getClientConfig({ silent: true, skipGlobalError: true }),
  ])


  const accountResponse = accountResult.status === 'fulfilled' ? accountResult.value : null
  if (accountResponse?.code === 200) {
    currentPoints.value = Number(accountResponse.data?.balance ?? 0)
    isFirstBazi.value = accountResponse.data?.first_bazi !== false
    accountStatus.value = 'ready'
  } else if (isUnauthorizedResult(accountResult)) {
    currentPoints.value = 0
    accountStatus.value = 'guest'
  } else {
    currentPoints.value = 0
    accountStatus.value = 'error'
    if (!silent) {
      ElMessage.error(accountResponse?.message || '获取账户信息失败，请尝试重新获取')
    }
  }

  const pricingResponse = pricingResult.status === 'fulfilled' ? pricingResult.value : null
  if (pricingResponse?.code === 200) {
    fortunePointsCost.value = {
      yearly_fortune: pricingResponse.data?.yearly_fortune ?? null,
      dayun_analysis: pricingResponse.data?.dayun_analysis ?? null,
      dayun_chart: pricingResponse.data?.dayun_chart ?? null,
    }
    fortunePricingStatus.value = 'ready'
  } else {
    fortunePointsCost.value = {
      yearly_fortune: null,
      dayun_analysis: null,
      dayun_chart: null,
    }

    if (isUnauthorizedResult(pricingResult)) {
      fortunePricingStatus.value = 'guest'
    } else {
      fortunePricingStatus.value = 'error'
      if (!silent) {
        ElMessage.error(pricingResponse?.message || '获取深度工具说明失败，请稍后重试')
      }
    }
  }

  const clientConfigResponse = clientConfigResult.status === 'fulfilled' ? clientConfigResult.value : null

  if (clientConfigResponse?.code === 200) {
    const resolvedAiCost = resolveAiAnalysisCost(clientConfigResponse.data)
    if (Number.isFinite(resolvedAiCost)) {
      aiAnalysisCost.value = resolvedAiCost
      aiPricingStatus.value = 'ready'
    } else {
      aiAnalysisCost.value = AI_ANALYSIS_DEFAULT_COST
      aiPricingStatus.value = 'fallback'
    }
  } else {
    aiAnalysisCost.value = AI_ANALYSIS_DEFAULT_COST
    aiPricingStatus.value = 'fallback'
  }
}



// 显示积分消耗确认对话框
const showPointsConfirm = (type, data = {}) => {
  const isAiAction = type === 'ai'
  const isPricingReady = isAiAction ? isAiPricingReady.value : isFortunePricingReady.value

  if (isGuestAccount.value || (!isAiAction && isGuestFortunePricing.value)) {
    ElMessage.warning('请先登录后再继续使用深度分析')
    return
  }

  if (!isAccountReady.value || !isPricingReady) {
    ElMessage.warning(isAiAction ? 'AI 解盘说明还在同步，请稍后再试' : '当前说明还在同步，请稍后再试')
    return
  }

  const cost = getPointsConfirmCost(type)
  if (!Number.isFinite(cost)) {
    ElMessage.warning(isAiAction ? 'AI 解盘说明暂未同步完成，请稍后重试' : '当前说明暂未同步完成，请稍后重试')
    return
  }


  if (currentPoints.value < cost) {
    ElMessage.warning(`积分不足，需要${cost}积分，请先签到领取积分`)
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
    case 'ai':
      await startAiAnalysisCore()
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
    
    if (response.code === 200) {
      yearlyFortuneResult.value = response.data
      lastAnalyzedYear.value = selectedYear.value
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('流年运势分析完成！')


    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
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
    
    if (response.code === 200) {
      dayunAnalysisResult.value = response.data
      lastAnalyzedDayunIndex.value = selectedDayunIndex.value
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('大运运势分析完成！')


    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
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
    
    if (response.code === 200) {
      dayunChartData.value = response.data
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('运势K线图生成完成！')

    } else {
      ElMessage.error(response.message || '生成失败')
    }
  } catch (error) {
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
  clearBaziSubmitIssues()
  const issues = buildBaziSubmitIssues()

  if (issues.length) {
    baziSubmitIssues.value = issues
    handleBaziIssue(issues[0])
    ElMessage.warning('提交前还有信息未完成，已帮你定位到第一个问题')
    return
  }
  
  // 积分不足前置拦截
  if (!isFirstBazi.value && currentPoints.value < BAZI_BASE_COST) {
    ElMessageBox.confirm(
      '当前积分不足，是否前往签到或充值获取积分？',
      '积分不足',
      {
        confirmButtonText: '去获取积分',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/profile')
    }).catch(() => {})
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
  stepIntervalRef.value = setInterval(() => {
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
      calendarType: calendarType.value,
    })
    
    clearInterval(stepIntervalRef.value)
    stepIntervalRef.value = null
    loadingStep.value = 4
    
    // 延迟一下让用户看到完成状态
    await new Promise(resolve => setTimeout(resolve, 300))
    
    if (response.code === 200) {
      trackSubmit('bazi_calculate', true, { mode: versionMode.value })
      result.value = response.data
      activeNames.value = getDefaultActiveNames()
      syncCurrentPoints(response.data.remaining_points)
      isFirstBazi.value = false
      ElMessage.success('排盘成功！为你生成详细的命理解读')

    } else {
      trackSubmit('bazi_calculate', false, { mode: versionMode.value, error: response.message })
      ElMessage.error(response.message || '排盘失败')
      // 如果是积分不足，刷新积分
      if (response.code === 403) {
        loadPoints({ silent: true })
      }
    }
  } catch (error) {
    trackSubmit('bazi_calculate', false, { mode: versionMode.value, error: error.message })
    trackError('bazi_calculate_error', error.message)
    ElMessage.error('网络错误，请稍后重试')
  } finally {
    if (stepIntervalRef.value) {
      clearInterval(stepIntervalRef.value)
      stepIntervalRef.value = null
    }
    loading.value = false
    loadingStep.value = 1
  }
}

onMounted(() => {
  trackPageView('bazi')
  updateViewportState()
  window.addEventListener('resize', updateViewportState)
  loadPoints({ silent: true })
  
  if (route.query.tab && ['chart', 'personality', 'career', 'fortune'].includes(route.query.tab)) {
    activeTab.value = route.query.tab
  }
})

// 组件卸载时清理定时器
onUnmounted(() => {
  window.removeEventListener('resize', updateViewportState)

  if (aiLoadingTimer.value) {
    clearInterval(aiLoadingTimer.value)
    aiLoadingTimer.value = null
  }
  if (stepIntervalRef.value) {
    clearInterval(stepIntervalRef.value)
    stepIntervalRef.value = null
  }
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
    ElMessage.success('已保存到当前设备；云端历史请以个人中心记录为准')
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
const buildBaziShareText = (includeFullDetails = false) => {
  if (!includeFullDetails) {
    return [
      '我刚在太初命理完成了一次八字排盘。',
      '这次结果主要帮我梳理了性格优势、发展方向和未来节奏。',
      '如果你也想先做一版低风险参考，可以先从摘要版体验开始。',
      '快来测测你的八字吧！'
    ].join('\n')
  }

  return [
    '我在太初命理完成了一次八字排盘',
    `日主：${result.value?.bazi?.day_master || ''}（${result.value?.bazi?.day_master_wuxing || ''}）`,
    `八字：${result.value?.bazi?.year?.gan || ''}${result.value?.bazi?.year?.zhi || ''} ${result.value?.bazi?.month?.gan || ''}${result.value?.bazi?.month?.zhi || ''} ${result.value?.bazi?.day?.gan || ''}${result.value?.bazi?.day?.zhi || ''} ${result.value?.bazi?.hour?.gan || ''}${result.value?.bazi?.hour?.zhi || ''}`,
    '快来测测你的八字吧！'
  ].filter(Boolean).join('\n')
}

const shareBaziText = async (shareText, clipboardSuccessText) => {
  if (navigator.share) {
    await navigator.share({
      title: '我的八字排盘结果',
      text: shareText
    })
    return
  }

  if (!navigator.clipboard?.writeText) {
    throw new Error('clipboard-unavailable')
  }

  await navigator.clipboard.writeText(shareText)
  ElMessage.success(clipboardSuccessText)
}

const baziShareSummary = computed(() => {
  if (!result.value?.bazi) return '我在太初命理测算了八字，结果很准！'
  const dm = result.value.bazi.day_master || ''
  const dmWuxing = result.value.bazi.day_master_wuxing || ''
  return `我的日主是${dmWuxing}${dm}，快来看看你的八字命盘吧！`
})

const baziShareTags = computed(() => {
  if (!result.value?.bazi) return []
  const tags = []
  if (result.value.bazi.day_master) tags.push(`日主${result.value.bazi.day_master}`)
  if (result.value.bazi.day_master_wuxing) tags.push(`五行属${result.value.bazi.day_master_wuxing}`)
  return tags
})

const shareResult = async () => {
  if (!result.value?.bazi) {
    ElMessage.warning('暂无排盘结果可分享')
    return
  }

  let includeFullDetails = false

  try {
    await ElMessageBox.confirm(
      '为保护隐私，默认推荐摘要分享，不包含完整八字信息。若你确认对方知情且愿意查看完整命盘，再选择“包含完整八字”。',
      '选择分享方式',
      {
        confirmButtonText: '包含完整八字',
        cancelButtonText: '仅分享摘要',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    )

    includeFullDetails = true
  } catch (actionOrError) {
    if (actionOrError === 'cancel') {
      includeFullDetails = false
    } else if (actionOrError === 'close' || actionOrError?.name === 'AbortError') {
      return
    } else {
      ElMessage.error('分享失败，请稍后重试')
      return
    }
  }

  try {
    await shareBaziText(
      buildBaziShareText(includeFullDetails),
      includeFullDetails ? '完整八字分享内容已复制到剪贴板' : '摘要分享内容已复制到剪贴板'
    )

    if (!includeFullDetails) {
      ElMessage.info('已按摘要版分享，默认省略完整八字信息')
    }
  } catch (shareError) {
    if (shareError?.name !== 'AbortError') {
      ElMessage.error(shareError?.message === 'clipboard-unavailable' ? '当前环境不支持自动复制，请手动复制分享内容' : '复制失败，请手动复制')
    }
  }
}

// AI解盘
const startAiAnalysis = () => {
  showPointsConfirm('ai')
}

const startAiAnalysisCore = async () => {
  if (isGuestAccount.value) {
    ElMessage.warning('请先登录后再使用 AI 解盘')
    return
  }

  if (!isAccountReady.value || !isAiPricingReady.value) {
    ElMessage.warning('AI 解盘说明还在同步，请稍后再试')
    return
  }


  if (currentPoints.value < aiAnalysisCost.value) {
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
    let streamRemainingPoints = null

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
              const parsedRemainingPoints = Number(
                parsed?.remaining_points ?? parsed?.data?.remaining_points ?? parsed?.result?.remaining_points
              )
              if (Number.isFinite(parsedRemainingPoints)) {
                streamRemainingPoints = parsedRemainingPoints
              }

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

        syncCurrentPoints(streamRemainingPoints, aiAnalysisCost.value)
      }
    } else {
      // 非流式响应
      const res = await analyzeBaziAi(result.value.bazi, aiPrompt.value, aiAbortController.value?.signal)
      if (res.code === 200) {
        aiAnalysisResult.value = res.data
        syncCurrentPoints(res.data?.remaining_points, aiAnalysisCost.value)
      } else {


        ElMessage.error(res.message || 'AI解盘失败')
      }
    }
  } catch (error) {
    if (error.name === 'AbortError') {
      ElMessage.info('已取消AI分析')
    } else {
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
/* Tab Navigation */
.result-tabs {
  display: flex;
  background: var(--bg-tertiary);
  border-radius: 16px;
  padding: 6px;
  margin-bottom: 25px;
  gap: 6px;
  border: 1px solid var(--border-color);
}

.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  border-radius: 12px;
  cursor: pointer;
  color: var(--text-secondary);
  font-weight: 500;
  transition: all 0.3s ease;
  font-size: 15px;
}

.tab-item:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.05);
}

.tab-item.active {
  background: var(--bg-card);
  color: var(--primary-color);
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tab-content {
  animation: fadeInUp 0.4s ease;
}

.tab-pane-content {
  padding-bottom: 20px;
}

.pane-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border-light);
}

/* 结果折叠面板样式 */
.result-collapse {
  border: none;
  background: transparent;
  --el-collapse-header-bg-color: transparent;
  --el-collapse-content-bg-color: transparent;
}

:deep(.el-collapse-item__header) {
  height: auto;
  min-height: 60px;
  padding: 15px 0;
  border-bottom: 1px solid var(--border-light);
}

:deep(.el-collapse-item__wrap) {
  border-bottom: 1px solid var(--border-light);
}

:deep(.el-collapse-item__content) {
  padding: 20px 0;
}

.collapse-title-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.title-icon {
  font-size: 20px;
  color: var(--primary-color);
  background: rgba(184, 134, 11, 0.1);
  padding: 8px;
  border-radius: 8px;
}

.title-text {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.title-desc {
  font-size: 13px;
  color: var(--text-tertiary);
  margin-left: 10px;
  font-weight: normal;
}

.section-title-with-tip,
.section-title-with-tag,
.section-subtitle-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.section-subtitle-wrapper {
  margin-top: 10px;
}

@media (max-width: 768px) {
  .collapse-title-wrapper {
    flex-wrap: wrap;
    align-items: flex-start;
    row-gap: 6px;
  }

  .title-text {
    font-size: 16px;
  }
  
  .title-desc {
    display: block;
    flex-basis: 100%;
    margin-left: 0;
    padding-left: 44px;
    font-size: 12px;
    line-height: 1.6;
  }
}


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
  color: var(--white-60);
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
  background: linear-gradient(90deg, #67c23a, #D4AF37);
}

/* 结果头部 */
.result-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px;
}

.result-header h2 {
  margin: 0;
}

.result-meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.meta-tag {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid transparent;
}

.meta-tag--success {
  background: rgba(var(--success-color-rgb), 0.2);
  color: var(--success-color);
}

.meta-tag--info {
  background: rgba(var(--primary-rgb), 0.12);
  border-color: rgba(var(--primary-rgb), 0.2);
  color: var(--primary-color);
}

.meta-tag--neutral {
  background: var(--bg-tertiary);
  border-color: var(--border-color);
  color: var(--text-secondary);
}

.meta-tag--warning {
  background: rgba(230, 162, 60, 0.14);
  border-color: rgba(230, 162, 60, 0.24);
  color: var(--warning-color);
}

.result-context-note {
  margin: 0 0 24px;
  padding: 14px 16px;
  border-radius: 14px;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid rgba(var(--primary-rgb), 0.16);
  color: var(--text-secondary);
  line-height: 1.7;
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

/* 高端排盘表格动画 */
.paipan-row {
  opacity: 0;
  transform: translateY(30px);
  animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.paipan-row:nth-child(1) { animation-delay: 0.1s; }
.paipan-row:nth-child(2) { animation-delay: 0.2s; }
.paipan-row:nth-child(3) { animation-delay: 0.3s; }
.paipan-row:nth-child(4) { animation-delay: 0.4s; }
.paipan-row:nth-child(5) { animation-delay: 0.5s; }
.paipan-row:nth-child(6) { animation-delay: 0.6s; }

/* 高端五行进度条动画 */
.wuxing-fill {
  animation: fillBar 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: 0.7s;
  width: 0;
  position: relative;
}

@keyframes fillBar {
  0% {
    width: 0;
    transform: scaleX(0);
  }
  50% {
    transform: scaleX(1.05);
  }
  100% {
    width: var(--target-width);
    transform: scaleX(1);
  }
}

/* 高端解读卡片动画 */
.reading-card {
  opacity: 0;
  transform: translateX(-20px);
  animation: slideInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.reading-card:nth-child(1) { animation-delay: 0.2s; }
.reading-card:nth-child(2) { animation-delay: 0.3s; }
.reading-card:nth-child(3) { animation-delay: 0.4s; }
.reading-card:nth-child(4) { animation-delay: 0.5s; }
.reading-card:nth-child(5) { animation-delay: 0.6s; }
.reading-card:nth-child(6) { animation-delay: 0.7s; }

@keyframes slideInRight {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

/* 高端大运时间线动画 */
.dayun-item {
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.dayun-item:nth-child(1) { animation-delay: 0.1s; }
.dayun-item:nth-child(2) { animation-delay: 0.15s; }
.dayun-item:nth-child(3) { animation-delay: 0.2s; }
.dayun-item:nth-child(4) { animation-delay: 0.25s; }
.dayun-item:nth-child(5) { animation-delay: 0.3s; }
.dayun-item:nth-child(6) { animation-delay: 0.35s; }

/* 高端全局动画增强 */
@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
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
  box-shadow: 0 8px 25px rgba(184, 134, 11, 0.3);
}

.el-button:active {
  transform: translateY(0);
}

/* 表单输入框焦点效果 */
:deep(.el-input__wrapper) {
  transition: all 0.3s ease;
}

:deep(.el-input__wrapper:hover) {
  box-shadow: 0 0 0 1px rgba(184, 134, 11, 0.5);
}

:deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(184, 134, 11, 0.5);
}

/* 加载状态 shimmer 效果 */
.loading-shimmer {
  background: linear-gradient(
    90deg,
    var(--white-05) 25%,
    var(--white-10) 50%,
    var(--white-05) 75%
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
    var(--white-20),
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


.bazi-form {

  max-width: 600px;
  margin: 0 auto 40px;
}

.points-hint {
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.1), rgba(212, 175, 55, 0.1));
  border: 1px solid rgba(184, 134, 11, 0.3);
  border-radius: 10px;
  padding: 15px 20px;
  margin-bottom: 25px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  flex-wrap: wrap;
  color: var(--text-primary);
}

.points-hint-content {
  flex: 1;
}

.points-hint-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.points-hint-details {
  background: rgba(255, 255, 255, 0.5);
  padding: 12px;
  border-radius: 8px;
  border: 1px solid var(--border-light);
}

.points-hint-title {
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.points-hint-list {
  list-style: none;
  padding: 0;
  margin: 0 0 12px 0;
}

.points-hint-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  color: var(--text-secondary);
}

.points-hint-list li .el-icon {
  color: var(--success-color);
}

.points-hint-guarantee {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--warning-color);
  font-size: 12px;
  margin: 0;
}

.points-hint--loading {
  border-color: rgba(var(--primary-rgb), 0.18);
  background: rgba(var(--primary-rgb), 0.08);
}

.points-hint--error {
  border-color: rgba(245, 108, 108, 0.22);
  background: rgba(245, 108, 108, 0.08);
}

.hint-icon {

  font-size: 20px;
}

.current-points {
  margin-left: auto;
  color: var(--primary-light);
  font-weight: 500;
}

.points-retry {
  margin-left: auto;
}

.strategy-summary-card,
.submit-summary-card {
  max-width: 600px;
  margin: 0 auto 24px;
  padding: 18px 20px;
  border-radius: 18px;
  border: 1px solid rgba(212, 175, 55, 0.18);
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(250, 246, 236, 0.96));
  box-shadow: 0 14px 28px rgba(149, 111, 45, 0.08);
}

.strategy-summary-card__header,
.submit-summary-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.strategy-summary-card__copy,
.submit-summary-card__header div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.strategy-summary-card__eyebrow {
  color: var(--text-tertiary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.strategy-summary-card__copy strong,
.submit-summary-card__header strong {
  color: var(--text-primary);
  font-size: 16px;
}

.strategy-summary-card__copy p,
.submit-summary-card__header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.strategy-summary-card__toggle {
  flex-shrink: 0;
  min-height: 36px;
}

.strategy-summary-card__details,
.submit-summary-card__actions {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.strategy-detail-item {
  padding: 14px 16px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(212, 175, 55, 0.12);
}

.strategy-detail-item strong {
  display: block;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 6px;
}

.strategy-detail-item p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.submit-summary-card {
  border-color: rgba(230, 162, 60, 0.24);
  background: linear-gradient(135deg, rgba(255, 250, 242, 0.98), rgba(255, 245, 228, 0.98));
}

.submit-summary-card__header > .el-icon {
  margin-top: 2px;
  color: var(--warning-color);
  font-size: 20px;
}

.submit-summary-card__action {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid rgba(230, 162, 60, 0.18);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.82);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  text-align: left;
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.submit-summary-card__action span {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
}

.submit-summary-card__action small {
  color: var(--text-secondary);
  font-size: 12px;
  line-height: 1.6;
}

.submit-summary-card__action:hover {
  transform: translateY(-1px);
  border-color: rgba(230, 162, 60, 0.32);
  box-shadow: 0 12px 24px rgba(149, 111, 45, 0.08);
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
  color: var(--primary-color);
  text-decoration: underline;
}

.form-group {
  margin-bottom: 30px;
}

.form-group label {
  display: block;
  margin-bottom: 12px;
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 500;
}

.form-group--time {
  margin-bottom: 34px;
}

.form-group__header--time {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.form-group__header--time label {
  margin-bottom: 0;
}

.form-group__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(212, 175, 55, 0.12);
  border: 1px solid rgba(212, 175, 55, 0.24);
  color: var(--primary-color);
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.help-icon {
  margin-left: 5px;
  cursor: help;
  color: var(--primary-color);
  opacity: 0.8;
}

.form-hint {
  color: var(--text-secondary);
  font-size: 13px;
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 5px;
  line-height: 1.6;
}

.time-accuracy-switch {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 16px;
  padding: 18px 20px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.68), rgba(250, 244, 228, 0.92));
  border: 1px solid rgba(212, 175, 55, 0.18);
  box-shadow: 0 12px 24px rgba(149, 111, 45, 0.08);
}

.time-accuracy-switch__copy {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-width: 240px;
}

.switch-label {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
}

.time-accuracy-switch__hint {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.time-accuracy-group {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.precision-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.precision-option__title {
  font-weight: 700;
}

.precision-option__desc {
  font-size: 12px;
  line-height: 1.45;
}

.time-entry-panel {
  padding: 20px;
  border-radius: 20px;
  border: 1px solid rgba(212, 175, 55, 0.16);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(250, 246, 236, 0.98));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78), 0 10px 22px rgba(149, 111, 45, 0.08);
}

.time-entry-panel--exact {
  border-color: rgba(64, 158, 255, 0.22);
  background: linear-gradient(180deg, rgba(248, 251, 255, 0.98), rgba(240, 247, 255, 0.98));
}

.time-entry-panel--estimated {
  border-color: rgba(230, 162, 60, 0.24);
  background: linear-gradient(180deg, rgba(255, 250, 242, 0.98), rgba(255, 246, 232, 0.98));
}

.time-entry-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.time-entry-panel__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(212, 175, 55, 0.18);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.time-entry-panel__title {
  margin: 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.time-entry-panel__grid {
  margin-top: 0;
}

.time-entry-panel__hint {
  margin-top: 12px;
}

.estimate-birth-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.full-width {
  width: 100%;
}

.bazi-result {
  max-width: 900px;
  margin: 0 auto;
  animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.bazi-result::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 200px;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(184, 134, 11, 0.5), transparent);
}

.bazi-result h2 {
  text-align: center;
  margin-bottom: 40px;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  letter-spacing: 2px;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
}

.bazi-result h2::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
  border-radius: 2px;
}

.bazi-paipan {
  background: linear-gradient(145deg, rgba(184, 134, 11, 0.05), rgba(255, 255, 255, 0.02));
  border-radius: 24px;
  padding: 40px;
  margin-bottom: 40px;
  border: 1px solid rgba(184, 134, 11, 0.15);
  backdrop-filter: blur(10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 
              0 8px 16px rgba(184, 134, 11, 0.08),
              inset 0 1px 0 rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.bazi-paipan::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(184, 134, 11, 0.3), transparent);
}

.paipan-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 20px;
  position: relative;
}

.paipan-row:last-child {
  margin-bottom: 0;
}

.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 25px 20px;
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.02);
}

.paipan-cell:hover {
  transform: translateY(-2px);
  background: rgba(184, 134, 11, 0.05);
  box-shadow: 0 8px 25px rgba(184, 134, 11, 0.1);
}

.paipan-cell.header {
  font-size: 18px;
  color: var(--text-secondary);
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.paipan-cell.highlight {
  color: var(--primary-light);
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.15), rgba(212, 175, 55, 0.1));
  border: 1px solid rgba(184, 134, 11, 0.3);
  box-shadow: 0 12px 30px rgba(184, 134, 11, 0.15),
              inset 0 1px 0 rgba(255, 255, 255, 0.2);
  position: relative;
}

.paipan-cell.highlight::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border-radius: 18px;
  background: linear-gradient(45deg, transparent, rgba(184, 134, 11, 0.2), transparent);
  z-index: -1;
}

.bazi-analysis {
  background: var(--bg-secondary);
  border-radius: 15px;
  padding: 30px;
}

.bazi-analysis h3 {
  margin-bottom: 20px;
  color: var(--text-primary);
  text-align: center;
}

.analysis-content {
  color: var(--text-secondary);
  line-height: 1.8;
  white-space: pre-line;
}

/* 暖心提示 */
.warm-tip {
  max-width: 600px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  background: rgba(184, 134, 11, 0.06);
  border: 1px solid rgba(184, 134, 11, 0.15);
  border-radius: 14px;
}

.tip-icon {
  font-size: 28px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.tip-content {
  text-align: left;
}

.tip-title {
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 4px;
}

.tip-desc {
  color: var(--text-secondary);
  font-size: 13px;
}

/* 智能填写策略卡片 */
.strategy-tip-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  background: rgba(184, 134, 11, 0.06);
  border: 1px solid rgba(184, 134, 11, 0.15);
  border-radius: 14px;
  margin-bottom: 24px;
}

.strategy-tip-card__icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(184, 134, 11, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 20px;
  color: var(--primary-color);
}

.strategy-tip-card__body {
  flex: 1;
  min-width: 0;
}

.strategy-tip-card__title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 4px;
}

.strategy-tip-card__desc {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0;
}

.strategy-tip-card__link {
  flex-shrink: 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--primary-color) !important;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* 版本选择卡片 */
.version-select-section {
  margin-bottom: 28px;
}

.version-select-section__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 14px;
  letter-spacing: 0.01em;
}

.version-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.version-card {
  padding: 16px 18px;
  border-radius: 14px;
  border: 1.5px solid var(--border-color);
  background: #fff;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.version-card:hover {
  border-color: rgba(184, 134, 11, 0.4);
  box-shadow: 0 2px 10px rgba(184, 134, 11, 0.08);
}

.version-card--active {
  border-color: var(--primary-color);
  background: linear-gradient(145deg, rgba(212, 175, 55, 0.12), rgba(245, 196, 103, 0.07));
  box-shadow: 0 4px 18px rgba(184, 134, 11, 0.14);
}

.version-card__header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.version-card__icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: rgba(184, 134, 11, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.version-card--active .version-card__icon {
  background: rgba(184, 134, 11, 0.2);
}

.version-card__info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 7px;
  min-width: 0;
}

.version-card__name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
}

.version-card__badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 7px;
  border-radius: 999px;
  background: var(--primary-color);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.version-card__pts {
  font-size: 13px;
  font-weight: 700;
  color: var(--primary-color);
  white-space: nowrap;
}

.version-card__features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.version-card__features li {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.version-card__features li .el-icon {
  color: #52c41a;
  flex-shrink: 0;
  margin-top: 2px;
}

/* 费用与权益确认 */
.cost-confirm-section {
  margin-top: 28px;
  padding: 22px 24px 24px;
  border-radius: 18px;
  border: 1px solid rgba(212, 175, 55, 0.2);
  background: linear-gradient(160deg, rgba(255, 252, 244, 0.98), rgba(255, 248, 230, 0.95));
  box-shadow: 0 2px 16px rgba(184, 134, 11, 0.07);
}

.cost-confirm-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.cost-confirm-section__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: 0.01em;
}

.cost-confirm-section__points {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px 5px 10px;
  border-radius: 999px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.22);
  font-size: 13px;
  color: #8c641f;
  font-weight: 600;
}

.cost-confirm-section__add {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary-color);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  line-height: 1;
  transition: opacity 0.15s;
}

.cost-confirm-section__add:hover {
  opacity: 0.82;
}

.cost-confirm-section__cost {
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 700;
  margin: 0 0 14px;
}

.cost-confirm-section__cost strong {
  color: #8c641f;
}

.cost-confirm-section__benefits {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 9px 20px;
  margin-bottom: 22px;
}

.cost-confirm-benefit {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.55;
}

.cost-confirm-benefit .el-icon {
  color: #52c41a;
  flex-shrink: 0;
  margin-top: 2px;
}

.cost-confirm-section__btn {
  width: 100%;
  height: 54px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 14px;
  background: linear-gradient(135deg, #c8960c, #d4af37) !important;
  border: none !important;
  color: #fff !important;
  letter-spacing: 0.06em;
  box-shadow: 0 4px 16px rgba(184, 134, 11, 0.28);
  transition: opacity 0.18s, box-shadow 0.18s;
}

.cost-confirm-section__btn:hover:not(:disabled) {
  opacity: 0.92;
  box-shadow: 0 6px 22px rgba(184, 134, 11, 0.36);
}

.insufficient-points {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  font-size: 13px;
  color: var(--text-secondary);
}

.insufficient-points .el-icon {
  color: var(--primary-color);
  flex-shrink: 0;
}

.insufficient-points a {
  color: var(--primary-color);
  font-weight: 600;
  text-decoration: none;
}

.insufficient-points a:hover {
  text-decoration: underline;
}

/* 版本切换 */
.version-toggle {
  margin-bottom: 35px;
  padding: 24px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(250, 244, 229, 0.96));
  border-radius: 22px;
  border: 1px solid rgba(212, 175, 55, 0.2);
  box-shadow: 0 18px 32px rgba(149, 111, 45, 0.1);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.version-toggle__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.version-toggle__copy {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.version-toggle__eyebrow {
  color: var(--text-tertiary);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
}

.version-toggle__title {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  line-height: 1.35;
}

.version-toggle__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(212, 175, 55, 0.12);
  border: 1px solid rgba(212, 175, 55, 0.24);
  color: var(--primary-color);
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.mode-option {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.mode-option--stacked {
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.mode-option__title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
}

.mode-option__desc {
  font-size: 12px;
  line-height: 1.45;
}

.mode-icon {
  font-size: 18px;
}

:deep(.version-toggle__group .el-radio-button),
:deep(.time-accuracy-group .el-radio-button) {
  flex: 1 1 180px;
}

:deep(.version-toggle__group .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button__inner) {
  width: 100%;
}

.version-hint {
  margin: 0;
  color: var(--primary-color);
  font-size: 14px;
  line-height: 1.7;
  font-weight: 500;
  background: rgba(184, 134, 11, 0.08);
  border: 1px solid rgba(184, 134, 11, 0.14);
  padding: 12px 14px;
  border-radius: 14px;
  display: block;
}

/* 专业解读区域 */
.professional-reading {
  margin: 30px 0;
  background: linear-gradient(135deg, var(--primary-light-05), var(--white-05));
  border: 1px solid var(--primary-light-20);
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
  background: var(--primary-gradient);
  color: var(--text-primary);
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
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
  background: linear-gradient(135deg, var(--primary-light-30), var(--primary-light-20));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  border: 2px solid var(--primary-light-60);
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
  background: var(--primary-light-20);
  color: var(--text-primary);
  padding: 10px 20px;
  border-radius: 20px;
  font-size: 14px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}

.dm-content {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.dm-section h5 {
  color: var(--text-tertiary);
  font-size: 14px;
  margin-bottom: 8px;
}

.dm-section p {
  color: var(--text-secondary);
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
  color: var(--primary-light);
  font-size: 18px;
}

.ys-type {
  background: rgba(184, 134, 11, 0.2);
  color: var(--primary-light);
  padding: 4px 12px;
  border-radius: 10px;
  font-size: 12px;
  min-height: 24px;
  display: inline-flex;
  align-items: center;
}

.ys-desc {
  color: var(--text-secondary);
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
  background: var(--bg-tertiary);
  border-radius: 14px;
  padding: 20px;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.reading-card:hover {
  transform: translateY(-5px);
  background: var(--bg-secondary);
  border-color: var(--primary-color);
}

.reading-card.advice-card {
  grid-column: span 3;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.15), rgba(133, 206, 97, 0.1));
  border-color: rgba(103, 194, 58, 0.3);
}

.reading-card.tieko-card {
  grid-column: span 3;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(255, 215, 0, 0.1));
  border-color: rgba(212, 175, 55, 0.3);
}

.tieko-header {
  justify-content: space-between;
}

.tieko-icon {
  font-size: 28px;
  color: #D4AF37;
}

.tieko-title-group {
  flex: 1;
}

.tieko-title-group h4 {
  margin: 0;
  font-size: 17px;
  color: #D4AF37;
}

.tieko-match-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.match-count {
  font-size: 12px;
  color: var(--text-secondary);
  background: rgba(0, 0, 0, 0.05);
  padding: 3px 8px;
  border-radius: 4px;
}

.match-level {
  font-size: 12px;
  padding: 3px 8px;
  border-radius: 4px;
  font-weight: 500;
}

.match-level.high {
  background: rgba(103, 194, 58, 0.2);
  color: #67c23a;
}

.match-level.medium {
  background: rgba(230, 162, 60, 0.2);
  color: #e6a23c;
}

.match-level.low {
  background: rgba(245, 108, 108, 0.2);
  color: #f56c6c;
}

.match-accuracy {
  font-size: 12px;
  color: #D4AF37;
  font-weight: 600;
}

.tieko-dingyu-list {
  margin-top: 18px;
}

.tieko-item {
  padding: 12px;
  margin-bottom: 10px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 8px;
  border-left: 3px solid #D4AF37;
}

.tieko-item:last-child {
  margin-bottom: 0;
}

.tieko-item-tags {
  display: flex;
  gap: 6px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.tieko-tag {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 3px;
  background: rgba(212, 175, 55, 0.15);
  color: #D4AF37;
}

.tieko-item-content {
  color: var(--text-primary);
  font-size: 14px;
  line-height: 1.6;
  margin: 8px 0;
}

.tieko-item-score {
  display: flex;
  align-items: center;
}

.tieko-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 1px solid rgba(212, 175, 55, 0.2);
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.tieko-hint .el-icon {
  color: #D4AF37;
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
  color: var(--text-secondary);
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
  color: var(--text-tertiary);
  font-weight: normal;
}

.interpretation-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.interp-card {
  background: var(--bg-card);
  border-radius: 12px;
  padding: 20px;
  transition: all 0.3s ease;
}

.interp-card:hover {
  transform: translateY(-3px);
  background: var(--border-light);
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
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.7;
}

.interp-card.personality {
  border-left: 3px solid var(--primary-color);
}

.interp-card.career {
  border-left: 3px solid #409eff;
}

.interp-card.relationship {
  border-left: 3px solid var(--warning-color);
}

.interp-card.advice {
  border-left: 3px solid var(--success-color);
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
  background: linear-gradient(135deg, rgba(10, 10, 26, 0.8), rgba(22, 22, 46, 0.8));
  border: 2px solid var(--primary-color);
  border-radius: 20px;
  padding: 24px 50px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(184, 134, 11, 0.3);
  backdrop-filter: blur(10px);
  animation: float 4s ease-in-out infinite;
}

.day-master-card .label {
  font-size: 15px;
  color: var(--text-secondary);
  font-weight: 500;
  letter-spacing: 2px;
}

.day-master-card .value {
  font-size: 42px;
  font-weight: 800;
  color: var(--primary-color);
  text-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.day-master-card .wuxing {
  background: var(--primary-gradient);
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 700;
  box-shadow: 0 4px 10px rgba(184, 134, 11, 0.4);
}

/* 排盘表格样式 */
.paipan-cell {
  flex: 1;
  text-align: center;
  padding: 15px 10px;
  font-size: 24px;
  font-weight: bold;
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  position: relative;
}

.paipan-cell.header {
  font-size: 16px;
  color: var(--text-tertiary);
  font-weight: normal;
  padding: 10px;
}

.paipan-cell.highlight {
  background: rgba(184, 134, 11, 0.08);
  border-radius: 10px;
}

.gan-text, .zhi-text {
  font-size: 28px;
}

.wuxing-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  background: var(--bg-tertiary);
  font-weight: normal;
}

.wuxing-badge.金 { background: rgba(255, 215, 0, 0.15); color: var(--wuxing-jin); }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.15); color: var(--wuxing-mu); }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.15); color: var(--wuxing-shui); }
.wuxing-badge.火 { background: rgba(255, 69, 0, 0.15); color: var(--wuxing-huo); }
.wuxing-badge.土 { background: rgba(139, 69, 19, 0.15); color: var(--wuxing-tu); }

.wuxing-badge.zhi {
  opacity: 0.8;
}

.rizhu-tag {
  font-size: 10px;
  background: var(--primary-color);
  color: var(--text-primary);
  padding: 2px 6px;
  border-radius: 4px;
  position: absolute;
  top: 5px;
  right: 5px;
}

/* 十神行 */
.shishen-row {
  background: var(--bg-card);
  border-radius: 8px;
  margin: 5px 0;
}

.shishen-cell {
  font-size: 14px;
  color: var(--text-secondary);
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
  color: var(--text-primary);
}

.canggan-item small {
  color: var(--text-tertiary);
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
  color: var(--primary-light);
  padding: 8px;
}

/* 旬空行 */
.xunkong-row {
  margin-top: 5px;
  background: var(--bg-hover);
  border-radius: 8px;
}

.xunkong-cell {
  font-size: 11px;
  color: var(--danger-color);
  padding: 8px;
}

.xunkong-label {
  color: var(--text-muted);
  margin-right: 4px;
}


/* 高端五行统计 */
.wuxing-stats {
  background: linear-gradient(145deg, rgba(184, 134, 11, 0.04), rgba(255, 255, 255, 0.02));
  border-radius: 20px;
  padding: 35px;
  margin: 40px 0;
  border: 1px solid rgba(184, 134, 11, 0.12);
  backdrop-filter: blur(10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12),
              0 8px 16px rgba(184, 134, 11, 0.06),
              inset 0 1px 0 rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.wuxing-stats::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(184, 134, 11, 0.25), transparent);
}

.wuxing-header {
  text-align: center;
  margin-bottom: 25px;
  position: relative;
}

.wuxing-stats h3 {
  margin-bottom: 10px;
  color: var(--text-primary);
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 1px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.wuxing-caption {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.8;
  font-weight: 500;
}

.wuxing-bars {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.wuxing-bar-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 20px;
  padding: 15px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.03);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(184, 134, 11, 0.05);
}

.wuxing-bar-item:hover {
  transform: translateY(-2px);
  background: rgba(184, 134, 11, 0.08);
  box-shadow: 0 8px 25px rgba(184, 134, 11, 0.1);
}

.wuxing-bar-main {
  display: flex;
  align-items: center;
  gap: 20px;
  min-width: 0;
}

.wuxing-name {
  width: 40px;
  font-weight: 700;
  color: var(--text-primary);
  font-size: 18px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.wuxing-bar {
  flex: 1;
  height: 28px;
  background: linear-gradient(145deg, rgba(0, 0, 0, 0.15), rgba(255, 255, 255, 0.05));
  border-radius: 14px;
  overflow: hidden;
  position: relative;
  border: 1px solid rgba(184, 134, 11, 0.1);
  box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.1);
}

.wuxing-fill {
  height: 100%;
  border-radius: 14px;
  transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.wuxing-fill::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shimmer 3s infinite;
  border-radius: 14px;
}

.wuxing-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), transparent);
  border-radius: 14px;
}

/* 高端五行颜色定义 */
.wuxing-fill.金 { 
  background: linear-gradient(135deg, #FFD700, #D4AF37, #B8860B);
}

.wuxing-fill.木 { 
  background: linear-gradient(135deg, #32CD32, #228B22, #006400);
}

.wuxing-fill.水 { 
  background: linear-gradient(135deg, #1E90FF, #4169E1, #0000CD);
}

.wuxing-fill.火 { 
  background: linear-gradient(135deg, #FF4500, #DC143C, #B22222);
}

.wuxing-fill.土 { 
  background: linear-gradient(135deg, #CD853F, #8B4513, #654321);
}

.wuxing-meta {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  justify-content: flex-end;
  flex-wrap: wrap;
  background: rgba(255, 255, 255, 0.05);
  padding: 8px 16px;
  border-radius: 12px;
  border: 1px solid rgba(184, 134, 11, 0.1);
  backdrop-filter: blur(5px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.wuxing-bar-item:hover .wuxing-meta {
  background: rgba(184, 134, 11, 0.08);
  border-color: rgba(184, 134, 11, 0.2);
  transform: scale(1.02);
}

.wuxing-count {
  min-width: 40px;
  text-align: right;
  color: var(--text-primary);
  font-weight: 800;
  font-size: 16px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.wuxing-unit,
.wuxing-share {
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.wuxing-unit {
  color: rgba(184, 134, 11, 0.8);
}

.wuxing-share {
  color: var(--success-color);
  font-weight: 700;
}


.form-hint--precision {
  color: var(--warning-color);
  font-weight: 600;
}

/* 操作按钮 */
.result-actions-wrap {
  margin-top: 30px;
}

.result-actions-heading {
  max-width: 720px;
  margin: 0 auto 16px;
  text-align: center;
}

.result-actions-heading__eyebrow {
  display: inline-block;
  margin-bottom: 8px;
  color: var(--text-tertiary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.result-actions-heading p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.result-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 0;
  flex-wrap: wrap;
}

.result-share-hint {
  margin-top: 12px;
  text-align: center;
  color: var(--text-tertiary);
  font-size: 13px;
}

.result-actions .btn-icon {
  margin-right: 5px;
}




/* 大运区域样式 */
.dayun-section {
  margin-top: 30px;
  background: var(--bg-card);
  border-radius: 15px;
  padding: 25px;
  border: 1px solid var(--border-color);
}

.dayun-section h3 {
  color: var(--text-primary);
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}

.dayun-timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

.dayun-item {
  background: var(--white-05);
  border-radius: 12px;
  padding: 15px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid var(--white-10);
  position: relative;
  overflow: hidden;
}

.dayun-item:hover {
  background: var(--white-10);
  border-color: var(--primary-light-30);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.dayun-item.current {
  border-color: var(--primary-color);
  background: var(--primary-light-10);
  box-shadow: 0 0 15px var(--primary-light-20);
}

.dayun-item.current::after {
  content: '当前';
  position: absolute;
  top: 0;
  right: 0;
  background: var(--primary-color);
  color: #000;
  font-size: 10px;
  padding: 2px 8px;
  font-weight: bold;
  border-bottom-left-radius: 8px;
}

.dayun-age {
  font-size: 14px;
  color: var(--white-80);
  margin-bottom: 10px;
  font-weight: 500;
}

.dayun-pillar {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 8px;
}

.dayun-pillar .gan,
.dayun-pillar .zhi {
  font-size: 26px;
  font-weight: 800;
  color: var(--text-primary);
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.dayun-shishen {
  font-size: 13px;
  color: var(--primary-light);
  margin-bottom: 8px;
  font-weight: 600;
}

.dayun-luck {
  display: inline-block;
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.dayun-luck.吉 {
  background: var(--success-gradient);
  color: #fff;
  box-shadow: 0 2px 8px rgba(103, 194, 58, 0.4);
}

.dayun-luck.凶 {
  background: var(--danger-gradient);
  color: #fff;
  box-shadow: 0 2px 8px rgba(245, 108, 108, 0.4);
}

.dayun-luck.平 {
  background: var(--white-20);
  color: var(--white-90);
}

.dayun-desc {
  font-size: 12px;
  color: var(--white-60);
  line-height: 1.5;
  margin-bottom: 10px;
  padding: 0 5px;
}

.dayun-nayin {
  font-size: 11px;
  color: var(--primary-light);
  font-style: italic;
  opacity: 0.9;
}

/* 流年区域样式 */
.liunian-section {
  margin-top: 40px;
  background: var(--white-03);
  border-radius: 20px;
  padding: 30px;
  border: 1px solid var(--white-05);
}

.liunian-section h3 {
  color: var(--text-primary);
  margin-bottom: 25px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-weight: 800;
}

.liunian-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 15px;
}

.liunian-item {
  background: var(--white-05);
  border-radius: 14px;
  padding: 18px 12px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid var(--white-10);
  position: relative;
}

.liunian-item:hover {
  background: var(--white-10);
  border-color: var(--primary-light-20);
  transform: scale(1.03);
}

.liunian-item.current {
  border-color: var(--primary-color);
  background: var(--primary-light-15);
  box-shadow: 0 0 12px var(--primary-light-20);
  z-index: 1;
}

.liunian-item.current::before {
  content: '今年';
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--primary-gradient);
  color: #000;
  font-size: 10px;
  padding: 2px 10px;
  border-radius: 10px;
  font-weight: bold;
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.liunian-year {
  font-size: 15px;
  color: var(--white-90);
  margin-bottom: 12px;
  font-weight: 700;
}

.liunian-pillar {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-bottom: 12px;
}

.liunian-pillar .gan,
.liunian-pillar .zhi {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-primary);
}

.liunian-wuxing {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 10px;
}

.liunian-wuxing .badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: bold;
}

.liunian-nayin {
  font-size: 11px;
  color: var(--primary-light);
  opacity: 0.8;
}

/* 排盘确认对话框 */
.confirm-dialog .confirm-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 8px 4px;
  color: var(--text-secondary);
  line-height: 1.7;
}

.confirm-dialog .confirm-content p {
  margin: 0;
}

.confirm-dialog .confirm-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.confirm-dialog .confirm-title strong {
  color: var(--star-color);
}

.confirm-dialog .confirm-note {
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--primary-light-05);
  border: 1px solid var(--primary-light-20);
  color: var(--text-secondary);
  font-size: 13px;
}

.confirm-dialog .confirm-content--free .confirm-title {
  color: var(--success-color);
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
  color: var(--text-primary);
  font-weight: bold;
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc {
  font-size: 16px;
  color: var(--white-80);
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc strong {
  color: var(--star-color);
  font-size: 20px;
}

.points-confirm-dialog .points-balance {
  font-size: 14px;
  color: var(--white-60);
}

/* 流年运势分析 */
.yearly-fortune-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.1), rgba(212, 175, 55, 0.05));
  border: 1px solid rgba(184, 134, 11, 0.3);
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
  flex-direction: column;
  gap: 16px;
  margin-bottom: 25px;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 12px;
}

.year-selector__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.year-selector__meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.selector-label {
  color: var(--text-secondary);
  font-size: 14px;
  white-space: nowrap;
}

.selector-hint {
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.6;
}

.year-slider {
  width: 100%;
}

.selected-year {
  color: var(--primary-color);
  font-size: 18px;
  font-weight: bold;
  min-width: 70px;
  text-align: right;
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
  background: var(--bg-tertiary);
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
  color: var(--primary-color);
}

.year-ganzhi {
  font-size: 20px;
  color: var(--text-primary);
}

.year-nayin {
  font-size: 14px;
  color: var(--primary-light);
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

.score-circle.excellent { border-color: var(--success-color); }
.score-circle.good { border-color: var(--warning-color); }
.score-circle.average { border-color: var(--danger-color); }
.score-circle.poor { border-color: var(--info-color); }

.score-value {
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
}

.score-label {
  font-size: 12px;
  color: var(--white-60);
}

.rating-badge {
  padding: 8px 20px;
  border-radius: 20px;
  font-size: 18px;
  font-weight: bold;
  color: var(--text-primary);
}

.rating-badge.excellent { background: var(--success-gradient); }
.rating-badge.good { background: var(--warning-gradient); }
.rating-badge.average { background: var(--danger-gradient); }
.rating-badge.poor { background: linear-gradient(135deg, var(--info-color), #a6a9ad); }

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

/* 幸运信息 - 高端优化 */
.lucky-info {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 15px;
  padding: 20px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.98));
  border-radius: 20px;
  border: 1px solid rgba(184, 134, 11, 0.2);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06),
              0 4px 16px rgba(184, 134, 11, 0.05),
              inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.lucky-section h5 {
  color: var(--text-primary);
  font-size: 15px;
  margin-bottom: 12px;
  font-weight: 600;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.lucky-tag {
  padding: 6px 14px;
  border-radius: 18px;
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.2px;
  border: 1px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.lucky-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.lucky-tag.good {
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.4), rgba(103, 194, 58, 0.2));
  color: #67c23a;
  border-color: rgba(103, 194, 58, 0.3);
}

.lucky-tag.bad {
  background: linear-gradient(135deg, rgba(245, 108, 108, 0.4), rgba(245, 108, 108, 0.2));
  color: #f56c6c;
  border-color: rgba(245, 108, 108, 0.3);
}

.lucky-tag.color {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.4), rgba(255, 215, 0, 0.2));
  color: var(--star-color);
  border-color: rgba(255, 215, 0, 0.3);
}

.lucky-tag.number {
  background: linear-gradient(135deg, rgba(64, 158, 255, 0.4), rgba(64, 158, 255, 0.2));
  color: #409eff;
  border-color: rgba(64, 158, 255, 0.3);
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
  color: var(--white-80);
  padding: 5px 12px;
  background: rgba(64, 158, 255, 0.2);
  border-radius: 15px;
}

.dayun-age {
  font-size: 14px;
  color: var(--white-60);
}

.dayun-level-badge {
  padding: 10px 25px;
  border-radius: 25px;
  font-size: 20px;
  font-weight: bold;
  color: var(--text-primary);
}

.dayun-level-badge.excellent { background: var(--success-gradient); }
.dayun-level-badge.good { background: var(--warning-gradient); }
.dayun-level-badge.average { background: var(--danger-gradient); }
.dayun-level-badge.poor { background: linear-gradient(135deg, var(--info-color), #a6a9ad); }

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
  color: var(--white-80);
  font-size: 14px;
}

.score-progress {
  flex: 1;
}

.score-value {
  width: 40px;
  text-align: right;
  color: var(--text-primary);
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
  background: var(--white-05);
  border-radius: 10px;
  padding: 15px;
}

.text-card p {
  color: var(--white-85);
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
  color: var(--star-color);
  margin-bottom: 15px;
  font-size: 16px;
}

.key-suggestions ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.key-suggestions li {
  color: var(--white-85);
  padding: 8px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
}

.key-suggestions li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 14px;
  width: 6px;
  height: 6px;
  background: var(--primary-color);
  border-radius: 50%;
}

/* 运势K线图 */
.fortune-chart-section {
  margin-top: 30px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.2);
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
  color: var(--white-85);
  margin-bottom: 10px;
}

.best-period {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-top: 15px;
  border-top: 1px solid var(--white-10);
}

.best-label {
  color: var(--white-60);
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
  color: var(--text-primary);
}

.dayun-score {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 14px;
  font-weight: bold;
}

.dayun-score.excellent { background: var(--success-light); color: var(--success-color); }
.dayun-score.good { background: var(--warning-light); color: var(--warning-color); }
.dayun-score.average { background: var(--danger-light); color: var(--danger-color); }
.dayun-score.poor { background: rgba(144, 147, 153, 0.2); color: var(--info-color); }

.dayun-trend {
  margin-left: auto;
  color: var(--white-60);
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
  background: var(--success-gradient);
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
  background: linear-gradient(to top, var(--star-color), #ffec8b);
  box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

.year-label {
  position: absolute;
  bottom: -20px;
  font-size: 10px;
  color: var(--white-60);
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
  border-top: 1px solid var(--white-10);
  font-size: 14px;
  color: var(--white-60);
}

/* 分析按钮区域 */
.analysis-actions {
  text-align: center;
  padding: 30px;
}

.analysis-desc {
  color: var(--white-70);
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
  color: var(--white-70);
  text-align: center;
  margin-bottom: 15px;
}

.ai-input {
  text-align: center;
}

.ai-result {
  background: var(--white-05);
  border-radius: 12px;
  padding: 20px;
}

.ai-result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--white-10);
}

.ai-model {
  color: #67c23a;
  font-weight: 500;
}

.ai-content {
  color: var(--white-90);
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
  color: var(--white-80);
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
  color: var(--white-80);
  line-height: 1.6;
  min-height: 100px;
  max-height: 400px;
  overflow-y: auto;
}

.ai-loading-timeout {
  text-align: center;
  margin: 10px 0;
  color: var(--white-60);
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
  .bazi-page {
    padding: 30px 0;
  }

  .result-header {
    flex-direction: column;
  }

  .result-meta {
    justify-content: flex-start;
  }

  .version-toggle,
  .time-entry-panel,
  .year-selector {
    padding: 18px;
  }

  .version-toggle__header,
  .time-accuracy-switch,
  .time-entry-panel__header,
  .form-group__header--time,
  .year-selector__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .version-toggle__status,
  .form-group__status,
  .time-entry-panel__badge {
    white-space: normal;
  }

  .time-accuracy-switch__copy,
  .year-selector__meta,
  .time-accuracy-group,
  .estimate-birth-grid {
    width: 100%;
    max-width: none;
  }

  .estimate-birth-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .time-entry-panel__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .time-entry-panel__badge {
    align-self: flex-start;
  }


  .selected-year {
    text-align: left;
    min-width: auto;
  }

  .selector-hint {
    font-size: 11px;
  }


  .wuxing-bar-item {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .wuxing-bar-main,
  .wuxing-meta {
    width: 100%;
  }

  .wuxing-meta {
    justify-content: flex-start;
  }

  .fortune-recovery-banner {

    flex-direction: column;
    align-items: flex-start;
  }


  .paipan-cell {
    font-size: 16px;
    padding: 12px 4px;
    min-height: 80px;
  }
  
  .gan-text, .zhi-text {
    font-size: 20px;
    line-height: 1.2;
  }

  .wuxing-badge {
    font-size: 9px;
    padding: 1px 4px;
    transform: scale(0.9);
  }
  
  .shishen-cell {
    font-size: 11px;
    padding: 6px 2px;
  }
  
  .canggan-cell {
    font-size: 9px;
    padding: 8px 2px;
    min-height: 45px;
  }

  .canggan-list {
    gap: 1px;
  }
  
  .nayin-cell {
    font-size: 9px;
    padding: 6px 2px;
  }
  
  .day-master-info {
    margin-bottom: 20px;
  }

  .day-master-card {
    padding: 16px 24px;
    gap: 12px;
    width: 100%;
    justify-content: center;
  }

  .day-master-card .value {
    font-size: 32px;
  }

  .day-master-card .label {
    font-size: 13px;
  }
  
  .dayun-timeline {
    display: flex;
    overflow-x: auto;
    gap: 15px;
    padding: 10px 5px 20px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }

  .dayun-timeline::-webkit-scrollbar {
    height: 4px;
  }

  .dayun-timeline::-webkit-scrollbar-thumb {
    background: var(--primary-light-30);
    border-radius: 2px;
  }

  .dayun-item {
    flex: 0 0 220px;
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    gap: 10px;
    padding: 16px;
  }

  .dayun-age {
    width: 100%;
    margin-bottom: 0;
  }

  .dayun-pillar {
    justify-content: flex-start;
    margin-bottom: 0;
    gap: 5px;
  }

  .dayun-pillar .gan, .dayun-pillar .zhi {
    font-size: 20px;
  }

  .dayun-shishen,
  .dayun-luck,
  .dayun-nayin {
    margin-bottom: 0;
  }

  .dayun-luck,
  .dayun-nayin {
    align-self: flex-start;
  }

  .dayun-desc {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    width: 100%;
    min-height: calc(1.6em * 3);
    padding: 0;
    margin-bottom: 0;
    color: var(--white-80);
    line-height: 1.6;
  }

  
  .liunian-grid {
    display: flex;
    overflow-x: auto;
    gap: 12px;
    padding: 10px 5px 20px;
    scroll-snap-type: x mandatory;
  }

  .liunian-grid::-webkit-scrollbar {
    height: 4px;
  }

  .liunian-grid::-webkit-scrollbar-thumb {
    background: var(--primary-light-30);
    border-radius: 2px;
  }

  .liunian-item {
    flex: 0 0 100px;
    scroll-snap-align: start;
    padding: 12px;
  }

  .liunian-year {
    font-size: 12px;
    margin-bottom: 5px;
  }

  .liunian-pillar .gan, .liunian-pillar .zhi {
    font-size: 18px;
  }
  
  .professional-reading {
    padding: 20px 15px;
  }

  .dm-header {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }

  .dm-symbol {
    width: 60px;
    height: 60px;
    font-size: 28px;
  }

  .dm-title h4 {
    font-size: 18px;
  }

  .dm-traits {
    justify-content: center;
  }

  .reading-card {
    padding: 15px;
  }

  .rc-header {
    margin-bottom: 8px;
  }

  .rc-icon {
    font-size: 20px;
  }
  
  .rc-content {
    font-size: 13px;
  }

  .result-actions {
    flex-direction: column;
    width: 100%;
  }

  .result-actions .el-button {
    width: 100%;
    margin-left: 0 !important;
    margin-bottom: 10px;
  }
}

/* 2026-03 UI polish: bazi refresh */
.bazi-page {
  padding: 10px 0 78px;
  background:
    radial-gradient(circle at 0% 0%, rgba(var(--primary-rgb), 0.14), transparent 34%),
    radial-gradient(circle at 100% 12%, rgba(245, 196, 103, 0.18), transparent 26%),
    linear-gradient(180deg, #fffdf8 0%, #fff9f1 46%, #fff7ee 100%);
}

.warm-tip,
.bazi-form,
.bazi-result {
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
}

.warm-tip {
  max-width: 920px;
  margin: 0 auto 22px;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 18px;
  align-items: center;
  padding: 22px 24px;
  border-radius: 26px;
  background: linear-gradient(135deg, rgba(255, 250, 239, 0.98), rgba(255, 255, 255, 0.96));
}

.tip-icon {
  width: 52px;
  height: 52px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #9a6612;
  background: linear-gradient(135deg, rgba(245, 196, 103, 0.28), rgba(255, 243, 214, 0.92));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.76);
}

.tip-title {
  margin-bottom: 6px;
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
}

.tip-desc {
  color: #5c5143;
  line-height: 1.7;
}

.bazi-form,
.bazi-result {
  max-width: 920px;
  margin-left: auto;
  margin-right: auto;
  padding: 30px;
  border-radius: 30px;
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

.points-hint,
.strategy-summary-card,
.version-toggle,
.form-group,
.time-entry-panel,
.result-context-note {
  border-radius: 22px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.92));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.points-hint {
  margin-bottom: 20px;
  padding: 18px 20px;
  gap: 12px;
}

.current-points {
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  color: #8a5c16;
  font-weight: 700;
}

.strategy-summary-card,
.version-toggle,
.form-group {
  padding: 22px 22px 20px;
}

.strategy-summary-card__header,
.version-toggle__header,
.form-group__header--time {
  align-items: flex-start;
  gap: 16px;
}

.strategy-summary-card__eyebrow,
.version-toggle__eyebrow,
.switch-label,
.time-entry-panel__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.1);
  color: #8d5f1c;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.strategy-summary-card__copy strong,
.version-toggle__title,
.time-entry-panel__title {
  color: var(--text-primary);
}

.strategy-summary-card__copy p,
.version-hint,
.time-accuracy-switch__hint,
.time-entry-panel__hint,
.form-hint,
.form-hint--precision {
  color: #5f5548;
  line-height: 1.75;
}

.strategy-summary-card__toggle,
.version-toggle__status,
.form-group__status {
  border-radius: 999px;
}

.version-toggle__status,
.form-group__status {
  display: inline-flex;
  align-items: center;
  min-height: 38px;
  padding: 0 14px;
  background: rgba(255, 248, 232, 0.92);
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  color: #8f611c;
  font-weight: 700;
}

.version-toggle__group {
  margin: 18px 0 14px;
}

:deep(.version-toggle__group .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button__inner) {
  min-height: 74px;
  padding: 14px 18px;
  border-radius: 18px !important;
  border: 1px solid rgba(var(--primary-rgb), 0.12) !important;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: none !important;
}

:deep(.version-toggle__group .el-radio-button:first-child .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button:first-child .el-radio-button__inner),
:deep(.version-toggle__group .el-radio-button:last-child .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button:last-child .el-radio-button__inner) {
  border-radius: 18px !important;
}

:deep(.version-toggle__group .el-radio-button__original-radio:checked + .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button__original-radio:checked + .el-radio-button__inner) {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.14), rgba(245, 196, 103, 0.18));
  border-color: rgba(var(--primary-rgb), 0.24) !important;
  color: var(--text-primary);
  box-shadow: 0 12px 26px rgba(var(--primary-rgb), 0.14) !important;
}

.time-accuracy-switch,
.time-entry-panel {
  padding: 18px;
}

.time-entry-panel {
  margin-top: 16px;
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(255, 246, 232, 0.94));
}

.time-entry-panel__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

:deep(.time-entry-panel__control .el-input__wrapper),
:deep(.form-group .el-input__wrapper),
:deep(.form-group .el-select__wrapper),
:deep(.form-group .el-textarea__inner) {
  border-radius: 16px;
  box-shadow: 0 0 0 1px rgba(var(--primary-rgb), 0.1) inset;
  background: rgba(255, 255, 255, 0.96);
}

:deep(.form-group .el-radio) {
  min-height: 40px;
  margin-right: 18px;
}

.bazi-form > .el-button {
  width: 100%;
  min-height: 54px;
  margin-top: 4px;
  border: none;
  border-radius: 18px;
  font-size: 16px;
  font-weight: 700;
  box-shadow: 0 18px 34px rgba(var(--primary-rgb), 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.insufficient-points {
  margin-top: 18px;
  border-radius: 18px;
  border: 1px solid rgba(217, 119, 6, 0.18);
  background: rgba(255, 247, 237, 0.92);
}

.bazi-result {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 250, 241, 0.95));
}

.result-header {
  margin-bottom: 22px;
  padding: 24px 24px 20px;
  border-radius: 24px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: linear-gradient(135deg, rgba(255, 252, 246, 0.98), rgba(255, 245, 225, 0.94));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.result-header h2 {
  font-size: clamp(26px, 3vw, 32px);
  font-weight: 800;
  letter-spacing: -0.02em;
}

.result-meta {
  gap: 12px;
}

.meta-tag {
  min-height: 42px;
  padding: 8px 14px;
  border-radius: 999px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.result-tabs {
  padding: 8px;
  border-radius: 20px;
  border-color: rgba(var(--primary-rgb), 0.1);
  background: rgba(255, 250, 241, 0.9);
}

.tab-item {
  min-height: 48px;
  border-radius: 14px;
}

.tab-item.active {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.16), rgba(255, 255, 255, 0.96));
  box-shadow: 0 10px 24px rgba(var(--primary-rgb), 0.12);
}

@media (max-width: 768px) {
  .bazi-page {
    padding: 0 0 56px;
  }

  /* 修复移动端大表单输入键盘遮挡问题 */
  .bazi-form {
    padding-bottom: 30vh; /* 为键盘留出空间 */
  }

  .warm-tip {
    grid-template-columns: 1fr;
    padding: 18px;
    gap: 14px;
  }

  .bazi-form,
  .bazi-result {
    padding: 22px 18px;
    border-radius: var(--radius-xl);
  }

  .strategy-summary-card,
  .version-toggle,
  .form-group,
  .time-entry-panel {
    padding: 18px 16px;
  }

  .strategy-summary-card__header,
  .version-toggle__header,
  .form-group__header--time {
    flex-direction: column;
  }

  .version-toggle__status,
  .form-group__status,
  .current-points {
    width: fit-content;
  }

  :deep(.version-toggle__group .el-radio-button__inner),
  :deep(.time-accuracy-group .el-radio-button__inner) {
    min-height: 66px;
    padding: 12px 14px;
  }

  .result-header {
    padding: 18px;
  }
}
</style>
