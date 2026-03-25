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

        <!-- 版本标识（固定为 AI 专业版） -->
        <div class="version-select-section">
          <div class="version-badge-pro">
            <div class="version-badge-pro__icon"><el-icon><Cpu /></el-icon></div>
            <div class="version-badge-pro__info">
              <span class="version-badge-pro__name">AI 专业分析版</span>
              <span class="version-badge-pro__desc">完整八字命盘 + AI 深度解读 + 流年大运走势</span>
            </div>
            <span class="version-badge-pro__pts">{{ BAZI_BASE_COST }} pts</span>
          </div>
        </div>

        <div class="form-group form-group--time" data-bazi-field="birth-time">
          <div class="form-group__header form-group__header--time">
            <label>出生日期与时间</label>
            <span class="form-group__status">{{ birthTimeAccuracy === 'exact' ? '精确排盘' : '估算模式' }}</span>
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
              <el-date-picker
                v-model="exactBirthDate"
                type="datetime"
                placeholder="选择出生日期时间（精确到分钟）"
                format="YYYY-MM-DD HH:mm"
                value-format="YYYY-MM-DD HH:mm:ss"
                class="full-width time-entry-panel__control"
              />
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

          <!-- 性别选择（内联在出生时间区域底部） -->
          <div class="gender-inline" data-bazi-field="gender">
            <span class="gender-inline__label">性别</span>
            <div class="gender-selector">
              <button
                type="button"
                class="gender-option"
                :class="{ active: gender === 'male' }"
                @click="gender = 'male'"
              >
                <span class="gender-option__icon">♂</span>
                <span class="gender-option__text">男</span>
              </button>
              <button
                type="button"
                class="gender-option"
                :class="{ active: gender === 'female' }"
                @click="gender = 'female'"
              >
                <span class="gender-option__icon">♀</span>
                <span class="gender-option__text">女</span>
              </button>
            </div>
          </div>
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
            本次排盘将消耗：<strong>{{ isFirstBazi ? '免费' : `${BAZI_BASE_COST} 积分` }}</strong>
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
              本次排盘将消耗 <strong>{{ BAZI_BASE_COST }} 积分</strong>
            </template>
          </p>
          <p>排盘后可在个人中心查看历史记录</p>
          <p class="confirm-note">规则说明：首次排盘免费，后续每次排盘消耗 {{ BAZI_BASE_COST }} 积分。</p>
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
        <!-- 结果页顶部装饰 -->
        <div class="result-hero">
          <div class="result-hero__ornament"></div>
          <div class="result-hero__badge">命盘已成</div>
          <h2 class="result-hero__title">八字排盘结果</h2>
          <p class="result-hero__subtitle">天干地支 · 五行流转 · 命理格局</p>
          <div class="result-meta">
            <span class="meta-tag meta-tag--success" v-if="result.is_first_bazi"><el-icon><Present /></el-icon> 首次免费</span>
            <span class="meta-tag meta-tag--success" v-if="result.from_cache"><el-icon><Lightning /></el-icon> 智能缓存</span>
            <span class="meta-tag meta-tag--info"><el-icon><MagicStick /></el-icon> {{ resultModeLabel }}</span>
            <span class="meta-tag" :class="birthTimeAccuracy === 'estimated' ? 'meta-tag--warning' : 'meta-tag--neutral'"><el-icon><Calendar /></el-icon> {{ birthTimeAccuracyLabel }}</span>
            <span class="meta-tag meta-tag--neutral"><el-icon><QuestionFilled /></el-icon> {{ locationContextLabel }}</span>
          </div>
        </div>
        <p v-if="resultContextNote" class="result-context-note">{{ resultContextNote }}</p>

        <!-- 当前流年速览卡片 -->
        <div class="current-fortune-brief" v-if="result.liunian && currentYearLiunian">
          <div class="fortune-brief__header">
            <el-icon><Calendar /></el-icon>
            <h3>{{ new Date().getFullYear() }}年流年速览</h3>
          </div>
          <div class="fortune-brief__content" v-if="currentYearLiunian">
            <div class="fortune-brief__pillar">
              <span class="gan">{{ currentYearLiunian.gan }}</span>
              <span class="zhi">{{ currentYearLiunian.zhi }}</span>
            </div>
            <div class="fortune-brief__info">
              <span class="fortune-brief__nayin">{{ currentYearLiunian.nayin }}</span>
              <div class="fortune-brief__wuxing">
                <span class="badge" :class="currentYearLiunian.gan_wuxing">{{ currentYearLiunian.gan_wuxing }}</span>
                <span class="badge" :class="currentYearLiunian.zhi_wuxing">{{ currentYearLiunian.zhi_wuxing }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== 本命局 ===== -->
        <div class="result-section">
          <!-- 命盘基础部分 -->
          <div class="tab-pane-content">
            <div class="pane-title">
                <el-icon class="title-icon"><Grid /></el-icon>
                <span class="title-text">命盘核心数据</span>
                <span class="title-desc">日主、八字、五行分布</span>
            </div>

            <!-- 日主信息 -->
            <div class="day-master-info">
              <div class="day-master-card" :class="'wx-' + result.bazi?.day_master_wuxing">
                <div class="day-master-card__ring">
                  <span class="day-master-card__char">{{ result.bazi?.day_master }}</span>
                </div>
                <div class="day-master-card__meta">
                  <span class="day-master-card__label">日主</span>
                  <span class="day-master-card__wuxing">{{ result.bazi?.day_master_wuxing }}</span>
                </div>
              </div>
            </div>
            
            <!-- 八字排盘表 -->
            <div class="bazi-paipan">
              <div class="bazi-paipan__watermark">八字</div>
              <div class="paipan-row">
                <div class="paipan-cell header"><span class="header-label">年柱</span></div>
                <div class="paipan-cell header"><span class="header-label">月柱</span></div>
                <div class="paipan-cell header"><span class="header-label">日柱</span><span class="header-badge">命主</span></div>
                <div class="paipan-cell header"><span class="header-label">时柱</span></div>
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
                <div class="paipan-cell shishen-cell">
                  <span class="shishen-label">十神</span>
                  <span class="shishen-value" :class="getShishenClass(result.bazi?.year?.shishen)">{{ result.bazi?.year?.shishen || '-' }}</span>
                </div>
                <div class="paipan-cell shishen-cell">
                  <span class="shishen-label">十神</span>
                  <span class="shishen-value" :class="getShishenClass(result.bazi?.month?.shishen)">{{ result.bazi?.month?.shishen || '-' }}</span>
                </div>
                <div class="paipan-cell shishen-cell highlight">
                  <span class="shishen-label">十神</span>
                  <span class="shishen-value shishen-value--self">日主</span>
                </div>
                <div class="paipan-cell shishen-cell">
                  <span class="shishen-label">十神</span>
                  <span class="shishen-value" :class="getShishenClass(result.bazi?.hour?.shishen)">{{ result.bazi?.hour?.shishen || '-' }}</span>
                </div>
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
                <div class="wuxing-header__icon">☯</div>
                <h3>五行分布</h3>
                <p class="wuxing-caption">以下为加权值，综合了天干透出、地支藏干与月令司令权重，并非简单计数。</p>
              </div>
              <div class="wuxing-bars">
                <div v-for="item in wuxingDistributionItems" :key="item.name" class="wuxing-bar-item" :class="'wx-' + item.name">
                  <div class="wuxing-bar-main">
                    <span class="wuxing-name">
                      <span class="wuxing-name__icon" :class="'icon-' + item.name">
                        {{ item.name === '金' ? '✨' : item.name === '木' ? '🌿' : item.name === '水' ? '💧' : item.name === '火' ? '🔥' : '⛰️' }}
                      </span>
                      <span class="wuxing-name__text">{{ item.name }}</span>
                    </span>
                    <div class="wuxing-bar">
                      <div class="wuxing-fill" :class="item.name" :style="{ width: `${item.width}%`, '--target-width': `${item.width}%` }"></div>
                    </div>
                  </div>
                  <div class="wuxing-meta">
                    <span class="wuxing-count">{{ item.displayValue }}</span>
                    <span class="wuxing-share">{{ item.shareText }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- ===== 性格/事业/流年 ===== -->
        <div class="result-section">
          <div class="tab-pane-content">
            <div class="pane-title">
                <el-icon class="title-icon"><UserFilled /></el-icon>
                <span class="title-text">性格内观</span>
            </div>

          <!-- 性格与解读部分 (Shared Content with v-show logic inside) -->
            <!-- Note: professional-reading and simple-interpretation logic will be patched later -->

            <!-- 专业解读卡片 -->
            <div class="professional-reading" v-if="result.fullInterpretation">
              <div class="section-subtitle-wrapper">
                <span class="section-badge">专业版</span>
              </div>
              
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
                <div class="reading-card card-hover" v-if="result.fullInterpretation.personality">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><UserFilled /></el-icon>
                    <h4>性格详解</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.personality }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.career">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Briefcase /></el-icon>
                    <h4>事业财运</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.career }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.wealth">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Money /></el-icon>
                    <h4>财富分析</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.wealth }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.relationship">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><UserFilled /></el-icon>
                    <h4>感情婚姻</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.relationship }}</p>
                </div>
                
                <div class="reading-card card-hover" v-if="result.fullInterpretation.health">
                  <div class="rc-header">
                    <el-icon class="rc-icon"><Aim /></el-icon>
                    <h4>健康提醒</h4>
                  </div>
                  <p class="rc-content">{{ result.fullInterpretation.health }}</p>
                </div>
                
                <div class="reading-card advice-card card-hover" v-if="result.fullInterpretation.advice">
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
                <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation.fortune">
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
                
                <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation.fortune">
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

            <div class="bazi-analysis">
              <h3>详细命理分析</h3>
              <div class="analysis-content">{{ result.analysis }}</div>
            </div>
          <!-- ===== 大运与流年走势 ===== -->
          <div class="fortune-section-wrapper">
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
                <el-tooltip content="大运是每10年一个阶段的人生运势，就像人生的“季节”，每个阶段都有不同的机遇和挑战" placement="top">
                  <span class="help-icon"><el-icon><QuestionFilled /></el-icon></span>
                </el-tooltip>
              </div>
              <!-- AI 评分 loading 提示 -->
              <div v-if="dayunScoring" class="dayun-scoring-tip">
                <el-icon class="is-loading"><Loading /></el-icon>
                <span>AI 正在根据你的八字分析大运评分中…</span>
              </div>
              <div class="dayun-timeline">
                <div 
                  v-for="(yun, index) in result.dayun" 
                  :key="index"
                  class="dayun-item"
                  :class="{ 'current': isCurrentDaYun(yun), [`level-${yun.trend_level || 'neutral'}`]: true }"
                >
                  <!-- 评分图标：AI 评分中显示转圈，完成后显示星星 -->
                  <div class="dayun-score-icon" :class="`score-${yun.trend_level || 'neutral'}`">
                    <template v-if="dayunScoring">
                      <el-icon class="is-loading score-star"><Loading /></el-icon>
                    </template>
                    <template v-else>
                      <el-icon class="score-star"><StarFilled /></el-icon>
                      <el-icon class="score-star" v-if="yun.score >= 60"><StarFilled /></el-icon>
                      <el-icon class="score-star" v-if="yun.score >= 75"><StarFilled /></el-icon>
                      <span class="score-num" v-if="yun.score">{{ yun.score }}</span>
                    </template>
                  </div>
                  <div class="dayun-age">{{ yun.age_start }}-{{ yun.age_end }}岁</div>
                  <div class="dayun-pillar">
                    <span class="gan">{{ yun.gan }}</span>
                    <span class="zhi">{{ yun.zhi }}</span>
                  </div>
                  <div class="dayun-shishen">{{ yun.shishen }}</div>
                  <div class="dayun-luck" :class="yun.trend_level || yun.luck">{{ yun.luck }}</div>
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
          </div>
          <!-- ===== 深度预测工具 ===== -->
          <div class="tools-section-wrapper">
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
        </div>
        </div> <!-- End of result sections -->

        <!-- 操作按钮 -->
        <div class="result-actions-wrap">
          <div class="result-actions-heading">
            <div class="result-actions-heading__divider"></div>
            <span class="result-actions-heading__eyebrow">下一步动作</span>
            <p>保存记录 · 查看历史 · 深入解读</p>
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
  </div>
</template>

<script setup>
import { Coin, MagicStick, QuestionFilled, Present, Lightning, StarFilled, Aim, Money, Briefcase, UserFilled, Warning, Check, Calendar, TrendCharts, Document, InfoFilled, Grid, Cpu, Download, Share, RefreshRight, Loading } from '@element-plus/icons-vue'
import WisdomText from '../../components/WisdomText.vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import ShareCard from '../../components/ShareCard.vue'

import { useBazi } from './useBazi'

const {
  // 状态
  activeTab,
  calendarType,
  birthTimeAccuracy,
  exactBirthDate,
  estimatedBirthDate,
  estimatedTimeSlot,
  selectedEstimatedTimeOption,
  isEstimatedDateOnly,
  birthDate,
  estimatedModeHint,
  estimatedTimeOptions,
  baziSubmitIssues,
  baziSubmitSummaryText,
  gender,
  loading,
  result,
  currentPoints,
  accountStatus,
  fortunePricingStatus,
  aiPricingStatus,
  aiAnalysisCost,
  confirmVisible,
  saving,
  versionMode,
  isFirstBazi,
  loadingStep,
  activeNames,

  // 流年 & 大运
  fortunePointsCost,
  selectedYear,
  yearlyFortuneResult,
  yearlyFortuneLoading,
  lastAnalyzedYear,
  isCompactViewport,
  selectedDayunIndex,
  dayunAnalysisResult,
  dayunAnalysisLoading,
  lastAnalyzedDayunIndex,
  dayunChartData,
  dayunChartLoading,
  dayunScoring,
  pointsConfirmVisible,
  pointsConfirmType,
  pointsConfirmData,

  // 计算属性
  versionHint,
  resultModeLabel,
  showAdvancedResultSections,
  birthTimeAccuracyLabel,
  locationContextLabel,
  resultContextNote,
  cityOptions,
  wuxingDistributionItems,
  isAccountReady,
  isGuestAccount,
  isGuestFortunePricing,
  isFortunePricingReady,
  isAiPricingReady,
  confirmDialogConfig,
  canStartBazi,
  startBaziButtonText,
  needsFortunePriceRecovery,
  fortunePriceRecoveryText,
  baziShareSummary,
  baziShareTags,

  // 常量
  BAZI_BASE_COST,

  // 方法
  resetCurrentResult,
  handleBaziIssue,
  openBaziHistoryCenter,
  continueBaziJourney,
  getFortuneToolCost,
  getPointsConfirmTitle,
  getPointsConfirmCost,
  getFortuneToolTagText,
  canUseFortuneTool,
  getFortuneToolActionText,
  refreshFortunePricing,
  showPointsConfirm,
  confirmPointsConsume,
  getScoreColor,
  getScoreClass,
  showConfirm,
  confirmCalculate,
  saveResult,
  isCurrentDaYun,
  shareResult,
  formatWuxingScore,
  upgradeToProVersion,
  getShishenClass,
  currentYearLiunian,
} = useBazi()

// 大运评分图标已改用 Element Plus StarFilled，无需 emoji 函数
</script>

<style scoped>
@import './style.css';
</style>
