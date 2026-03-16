# 太初命理网站 - 待办修复列表

> 自动生成时间: 2026-03-16
> 本文件由自动化检查任务维护

## 自动化任务说明

已设置3个自动化任务：

| 任务名称 | 频率 | 说明 |
|---------|------|------|
| 网站逻辑检查任务 | 每小时 | 检查前端/后端逻辑问题 |
| 待办处理执行器 | 每小时 | 自动处理待办列表中的任务 |
| UI设计检查官 | 每小时 | 检查UI设计问题 |

## UI设计检查报告 - 2026-03-16 第八轮

### 本次检查重点
- 检查范围：前端Vue项目全部视图页面和组件
- 检查维度：整体视觉风格、首页设计、功能页面、交互体验、移动端适配
- 发现问题：核心主题不一致问题仍然存在，需要决策

---

## 待处理项目

### 🔴 高优先级（功能性问题）

- [x] [2026-03-16] 后端Vip.php返回格式不一致 - backend/app/controller/Vip.php - 已统一使用BaseController的success()和error()方法，所有返回格式保持一致（成功code=0，错误返回对应错误码），并添加了分页参数验证 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端AdminAuthService异常处理不完整 - backend/app/service/AdminAuthService.php第177-220行 - assignRole和removeRole捕获异常但仅返回false，未记录错误日志 - 建议添加日志记录便于排查问题
- [ ] [UI] 主题方向决策 - 这是最核心的设计问题，影响整个网站的视觉一致性 - 建议：考虑到命理玄学的行业属性，建议统一为深色主题（神秘、专业感），或全面改为白色主题（清新、现代感）
- [ ] [UI] 导航栏与页面内容区视觉割裂 - App.vue使用白色导航栏，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue多处使用白色文字（color: #fff, rgba(255,255,255,0.7)等），在浅色背景下不可见 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [x] [2026-03-16] 前端SEOStats.vue RankBadge组件缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件JWT密钥硬编码风险 - backend/app/middleware/AdminAuth.php - 已移除默认值，强制从环境变量ADMIN_JWT_SECRET读取，未设置时抛出异常 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件日志记录敏感信息 - backend/app/middleware/AdminAuth.php - 已添加敏感字段脱敏处理（password、pwd、token、secret、key、authorization等字段会被替换为***） - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Auth.php邀请码暴力枚举风险 - backend/app/controller/Auth.php - 已修复：尝试次数超过10次后阻止操作（return） - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端AiAnalysis.php类型检查缺失 - backend/app/controller/AiAnalysis.php第49行 - $request->param('bazi')未验证是否为数组，传入字符串会导致后续错误 - 建议添加is_array检查
- [ ] [2026-03-16] 后端Admin.php feedbackList缺少权限检查 - backend/app/controller/Admin.php第472行 - feedbackList方法未调用checkPermission进行权限验证 - 建议添加权限检查
- [ ] [2026-03-16] 前端Bazi.vue潜在空值访问 - frontend/src/views/Bazi.vue第1357行 - analyzeBaziAi调用时aiAbortController.value可能为null - 建议使用可选链或添加空值检查

- [ ] [UI] 全局主题方向需要决策 - style.css定义白色主题，但所有页面使用深色背景，这是最核心的设计问题 - 建议：评估品牌定位后统一为深色主题（更符合命理玄学调性）或全面改为白色主题
- [ ] [UI] 页面背景色与导航栏/页脚严重割裂 - App.vue使用白色导航栏和页脚，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色在白色主题下不可见 - Home.vue第344、351、387、394、456、461、516、524、571行使用白色文字（color: #fff, rgba(255,255,255,0.7)等） - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [x] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php第17行 - 已添加构造函数从环境变量读取JWT密钥：Env::get('ADMIN_JWT_SECRET')，并添加默认回退值 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端Content.php缺少SQL注入防护 - backend/app/controller/Content.php第363-365行 - keyword参数直接拼接到SQL中，缺少preg_replace净化 - 建议添加$keyword = preg_replace('/[%_]/', '', $keyword);
- [ ] [2026-03-16] 后端AiAnalysis.php类型检查缺失 - backend/app/controller/AiAnalysis.php第49行 - $request->param('bazi')期望数组但可能返回字符串，导致后续处理错误 - 建议添加类型检查：if (!is_array($baziData)) { return json(['code' => 400, 'message' => '八字数据格式错误']); }
- [ ] [2026-03-16] Bazi.vue未使用变量和函数 - frontend/src/views/Bazi.vue第950行、1068-1084行 - yearlyTrendData变量声明后从未使用，getYearlyTrendData函数定义后从未调用 - 建议删除未使用的代码
- [ ] [UI] 主题系统严重不一致 - style.css定义白色主题（--bg-primary: #ffffff），但所有页面使用深色背景（rgba(0,0,0,0.2)、rgba(255,255,255,0.05)）和白色文字（color: #fff） - 建议统一主题方向：要么改为深色主题，要么将所有页面改为白色主题
- [ ] [UI] 文字颜色与背景冲突 - 多处使用白色文字（color: #fff、rgba(255,255,255,0.8)），在白色背景下完全不可读 - 建议根据主题统一文字颜色
- [ ] [UI] 登录页与整体主题严重不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与其他页面白色主题定义冲突 - 建议统一登录页主题风格
- [ ] [UI] 页面背景色与导航栏/页脚不协调 - App.vue中导航栏和页脚使用白色主题，但各页面内容区使用深色背景，视觉割裂感严重 - 建议统一全站背景色
- [ ] [UI] BackButton组件样式与主题不匹配 - BackButton.vue使用深色背景样式（rgba(255,255,255,0.1)），与白色主题不协调 - 建议改为使用var(--bg-card)和var(--text-primary)
- [ ] [UI] EmptyState组件配色与主题不匹配 - EmptyState.vue使用深色边框色（#d9d9d9、#f0f0f0）和深色文字色（#1a1a1a、#666），与整体白色主题不协调 - 建议更新为使用主题CSS变量
- [x] [2026-03-16] 后端Auth.php缺少Cache类导入 - backend/app/controller/Auth.php第288行 - 已添加use think\facade\Cache;导入语句 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php第17行 - 已添加构造函数从环境变量读取JWT密钥 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端Content.php缺少SQL注入防护 - backend/app/controller/Content.php第363-365行 - keyword参数直接拼接到SQL中，缺少preg_replace净化 - 建议添加$keyword = preg_replace('/[%_]/', '', $keyword);
- [ ] [UI] 主题系统严重不一致 - style.css定义白色主题（--bg-primary: #ffffff），但所有页面使用深色背景（rgba(0,0,0,0.2)、rgba(255,255,255,0.05)）和白色文字（color: #fff） - 建议统一主题方向：要么改为深色主题，要么将所有页面改为白色主题
- [ ] [UI] 文字颜色与背景冲突 - 多处使用白色文字（color: #fff、rgba(255,255,255,0.8)），在白色背景下完全不可读 - 建议根据主题统一文字颜色
- [ ] [UI] 登录页与整体主题严重不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与其他页面白色主题定义冲突 - 建议统一登录页主题风格
- [x] [2026-03-16] 管理端路由缺失 - frontend/src/router/index.js - 6个admin管理页面没有在路由中注册，无法通过URL访问 - 已添加6个admin路由配置（/admin, /admin/config, /admin/almanac, /admin/knowledge, /admin/seo, /admin/seo-stats, /admin/shensha） - 修复时间: 2026-03-16
- [x] [2026-03-16] 管理端权限验证缺失 - frontend/src/router/index.js - 路由守卫只检查普通用户登录状态，没有管理员角色权限检查 - 已添加requiresAdmin路由元信息和isAdmin()权限验证函数，支持多种角色字段（role/is_admin/isAdmin/type） - 修复时间: 2026-03-16
- [x] [2026-03-16] Config.vue中updateFeature函数命名冲突导致无限递归 - frontend/src/views/admin/Config.vue第401-404行 - 已重命名本地函数为handleUpdateFeature，模板调用处同步更新 - 修复时间: 2026-03-16
- [x] [2026-03-16] Bazi.vue使用TypeScript类型注解 - frontend/src/views/Bazi.vue第938-939行 - 已改为纯JavaScript写法：const aiAbortController = ref(null) 和 const aiLoadingTimer = ref(null) - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端Content.php缺少SQL注入防护 - backend/app/controller/Content.php第363-365行 - keyword参数直接拼接到SQL中，缺少preg_replace净化 - 建议添加$keyword = preg_replace('/[%_]/', '', $keyword);
- [ ] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php第17行 - JWT密钥硬编码为'your-admin-jwt-secret-key-change-in-production'，存在严重安全隐患 - 建议从环境变量读取：env('ADMIN_JWT_SECRET')
- [ ] [2026-03-16] 后端Auth.php缺少Cache类导入 - backend/app/controller/Auth.php第288行 - 使用Cache::get()和Cache::set()但未导入think\facade\Cache - 建议添加use think\facade\Cache;
- [ ] [UI] 主题系统严重不一致 - style.css定义白色主题（--bg-primary: #ffffff），但所有页面使用深色背景（rgba(0,0,0,0.2)、rgba(255,255,255,0.05)）和白色文字（color: #fff） - 建议统一主题方向：要么改为深色主题，要么将所有页面改为白色主题
- [ ] [UI] 文字颜色与背景冲突 - 多处使用白色文字（color: #fff、rgba(255,255,255,0.8)），在白色背景下完全不可读 - 建议根据主题统一文字颜色
- [ ] [UI] 登录页与整体主题严重不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与其他页面白色主题定义冲突 - 建议统一登录页主题风格
- [ ] [UI] 页面背景色与导航栏/页脚不协调 - App.vue中导航栏和页脚使用白色主题，但各页面内容区使用深色背景，视觉割裂感严重 - 建议统一全站背景色
- [ ] [UI] 大量emoji图标使用不专业 - 各页面使用emoji（💎、☯、🎴、💕等）作为图标，在不同平台显示效果不一致 - 建议引入@element-plus/icons-vue统一图标系统
- [ ] [UI] 按钮圆角不统一 - 各页面按钮圆角不一致（12px/20px/25px/30px混用） - 建议统一为16px或20px圆角
- [ ] [UI] 卡片样式不统一 - 各页面卡片背景色（rgba(255,255,255,0.05) vs rgba(0,0,0,0.2)）、圆角（12px/15px/16px/20px）、边框样式差异大 - 建议统一卡片设计规范
- [ ] [UI] 表单输入框样式混乱 - 不同页面输入框样式差异大，有的有边框有的无边框，背景色也不一致（rgba(255,255,255,0.1) vs #fff） - 建议统一使用style.css中定义的.input-field类
- [ ] [UI] 页面标题样式不统一 - 有的页面使用.page-title（36px），有的使用.section-title（32px），对齐方式也不同（居中vs左对齐） - 建议建立统一的标题层级系统
- [ ] [UI] 响应式断点不统一 - 各页面使用不同的断点值（576px/768px/992px混用） - 建议统一响应式断点为576px/768px/992px/1200px
- [ ] [UI] 移动端字体大小未调整 - 移动端仍然使用桌面端字体大小，如hero-title在移动端仍为40px（应缩小至28px左右） - 建议建立响应式字体系统
- [ ] [UI] 触摸目标过小 - 部分按钮和链接在移动端触摸区域过小，不符合44px最小触摸区域规范 - 建议确保所有可点击元素至少44x44px
- [ ] [UI] 八字排盘结果表格在移动端显示拥挤 - Bazi.vue中的排盘表格在移动端字体过小（10px-12px），藏干信息难以辨认 - 建议优化移动端表格布局，考虑横向滚动或卡片式布局
- [ ] [UI] 加载状态样式不统一 - 各页面加载动画样式不一致（太极图、简单spinner、Element Plus加载组件混用） - 建议统一加载组件
- [ ] [UI] 积分提示样式在各页面重复定义 - points-hint样式在Bazi.vue、Tarot.vue等多个页面重复定义，维护困难 - 建议提取为公共组件PointsHint
- [ ] [UI] 阴影效果不一致 - 各组件阴影深浅不一（box-shadow值各不相同），style.css定义了标准阴影但各页面组件使用自定义阴影 - 建议统一使用CSS变量定义阴影
- [ ] [UI] 颜色变量使用不规范 - 硬编码颜色值与CSS变量混用，如五行颜色直接写死（#ffd700、#228b22等） - 建议将所有颜色提取为CSS变量
- [ ] [UI] 塔罗牌卡片在移动端显示过小 - Tarot.vue中的塔罗卡片在移动端宽度仅120px，难以看清内容 - 建议优化为更大的卡片或横向滑动布局
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域在移动端显示不佳 - Hehun.vue中的bazi-compare在移动端flex-direction: column后，中间的分隔符（💕）旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中的score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 输入框焦点状态不明显 - 输入框focus状态样式不够突出，用户体验不佳 - 建议增强焦点状态的边框颜色和阴影
- [ ] [UI] 字体大小层级不清晰 - 标题和正文字体大小差异不够明显，如hero-title 56px与hero-subtitle 20px差距过大 - 建议建立统一的字体层级系统
- [ ] [UI] 缺少页面过渡动画 - 页面切换时没有过渡效果，体验较生硬 - 建议添加Vue页面过渡动画
- [ ] [UI] 首页Hero区域背景渐变与白色主题不协调 - Home.vue的hero区域使用radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%)，在白色主题下效果不明显 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 功能卡片图标大小不统一 - 首页feature-card中的图标大小不一致（48px），与其他页面图标不协调 - 建议统一图标尺寸规范
- [ ] [UI] 页脚链接间距在移动端过小 - 移动端footer-links的gap为20px，在较小屏幕上显得拥挤 - 建议增加间距或改为垂直排列
- [ ] [UI] 浮动陪伴组件位置在部分页面可能遮挡内容 - floating-companion固定在右下角，在某些页面可能遮挡重要按钮 - 建议增加智能避让或可调节位置功能
- [x] [2026-03-16] 前端SEOStats.vue缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端AiAnalysis.php类型检查缺失 - backend/app/controller/AiAnalysis.php第49行 - $request->param('bazi')期望数组但可能返回字符串，导致后续处理错误 - 建议添加类型检查：if (!is_array($baziData)) { return json(['code' => 400, 'message' => '八字数据格式错误']); }
- [ ] [2026-03-16] Bazi.vue未使用变量和函数 - frontend/src/views/Bazi.vue第950行、1068-1084行 - yearlyTrendData变量声明后从未使用，getYearlyTrendData函数定义后从未调用 - 建议删除未使用的代码
- [ ] [2026-03-16] Bazi.vue潜在空值访问 - frontend/src/views/Bazi.vue第1357行 - analyzeBaziAi调用时aiAbortController.value可能为null - 建议使用可选链：aiAbortController.value?.signal
- [ ] [2026-03-16] 后端AiAnalysis.php类型检查缺失 - backend/app/controller/AiAnalysis.php第49行 - $request->param('bazi')期望数组但可能返回字符串，导致后续处理错误 - 建议添加类型检查：if (!is_array($baziData)) { return json(['code' => 400, 'message' => '八字数据格式错误']); }
- [ ] [2026-03-16] Bazi.vue未使用变量和函数 - frontend/src/views/Bazi.vue第950行、1068-1084行 - yearlyTrendData变量声明后从未使用，getYearlyTrendData函数定义后从未调用 - 建议删除未使用的代码
- [ ] [2026-03-16] 后端Admin.php权限检查返回格式不一致 - backend/app/controller/Admin.php第89,147,201行 - 权限检查使用json()而非$this->error()，与其他方法不一致 - 建议统一使用$this->error('无权限', 403)保持一致性
- [ ] [2026-03-16] 后端Content.php模型类未导入 - backend/app/controller/Content.php多处 - 使用\app\model\Page等全局命名空间，未导入模型类 - 建议添加相应的use语句导入模型类
- [ ] [2026-03-16] 后端Auth.php模型类导入不一致 - backend/app/controller/Auth.php第59,118行 - 使用\app\model\PointsRecord全局命名空间，但文件顶部已使用use导入其他模型 - 建议统一添加use app\model\PointsRecord;
- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 前端AlmanacManage.vue API调用缺失 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - submitForm和generateMonth函数中只有模拟延迟，没有实际API调用 - 建议添加真实的API保存和生成调用
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，filteredList计算属性没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [UI] Home.vue页面样式与白色主题不一致 - 首页各区块仍使用深色背景（rgba(0,0,0,0.2)、rgba(255,255,255,0.05)等），与style.css中定义的白色主题严重冲突 - 建议统一改为白色/浅色背景，使用CSS变量如var(--bg-card)、var(--bg-secondary)
- [ ] [UI] 所有功能页面（Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue）使用深色背景与白色主题冲突 - 页面背景使用rgba(0,0,0,0.2)、rgba(255,255,255,0.05)等深色样式，与style.css中--bg-primary: #ffffff定义不符 - 建议将所有页面背景统一为白色主题配色
- [ ] [UI] 文字颜色在白色主题下不可读 - 多处使用color: #fff、color: rgba(255,255,255,0.8)等白色文字，在白色背景下完全不可见 - 建议改为使用var(--text-primary)、var(--text-secondary)等CSS变量
- [ ] [UI] EmptyState组件配色与主题不匹配 - EmptyState.vue使用深色边框色（#d9d9d9、#f0f0f0）和深色文字色（#1a1a1a、#666），与整体白色主题不协调 - 建议更新为使用主题CSS变量

### 🟡 中优先级（体验问题）

- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 前端AlmanacManage.vue API调用缺失 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - submitForm和generateMonth函数中只有模拟延迟，没有实际API调用 - 建议添加真实的API保存和生成调用
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实际调用API加载数据 - 建议实现真实的API调用和分页逻辑
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [2026-03-16] 前端Config.vue未使用loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有在页面加载时使用 - 建议在onMounted中使用loading状态
- [ ] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用，onMounted和onUnmounted为空 - 建议实现图表初始化逻辑或删除未使用的代码
- [ ] [UI] 按钮样式不统一 - 各页面按钮圆角不一致（12px/20px/25px/30px混用），有的使用渐变有的使用纯色 - 建议统一使用style.css中定义的.btn-primary（25px圆角）
- [ ] [UI] 卡片圆角不统一 - 各页面卡片圆角不一致（12px/15px/16px/20px混用） - 建议统一为16px或20px
- [ ] [UI] 表单输入框样式混乱 - 不同页面输入框样式差异大，有的有边框有的无边框，背景色也不一致（rgba(255,255,255,0.1) vs #fff） - 建议统一使用style.css中定义的.input-field类
- [ ] [UI] 页面标题样式不统一 - 有的页面使用.page-title（36px），有的使用.section-title（32px），对齐方式也不同（居中vs左对齐） - 建议建立统一的标题层级系统
- [ ] [UI] 响应式断点不统一 - 各页面使用不同的断点值（576px/768px/992px混用） - 建议统一响应式断点为576px/768px/992px/1200px
- [ ] [UI] 移动端字体大小未调整 - 移动端仍然使用桌面端字体大小，如hero-title在移动端仍为40px（应缩小至28px左右） - 建议建立响应式字体系统
- [ ] [UI] 触摸目标过小 - 部分按钮和链接在移动端触摸区域过小，不符合44px最小触摸区域规范 - 建议确保所有可点击元素至少44x44px
- [ ] [UI] 八字排盘结果表格在移动端显示拥挤 - Bazi.vue中的排盘表格在移动端字体过小（10px-12px），藏干信息难以辨认 - 建议优化移动端表格布局，考虑横向滚动或卡片式布局
- [ ] [UI] 图标系统混乱 - 大量使用emoji作为图标（💎、☯、🎴等），没有统一图标库，在不同平台显示效果不一致 - 建议引入@element-plus/icons-vue统一图标
- [ ] [UI] 加载状态样式不统一 - 各页面加载动画样式不一致（太极图、简单spinner、Element Plus加载组件混用） - 建议统一加载组件
- [ ] [UI] 积分提示样式在各页面重复定义 - points-hint样式在Bazi.vue、Tarot.vue等多个页面重复定义，维护困难 - 建议提取为公共组件PointsHint
- [ ] [UI] 阴影效果不一致 - 各组件阴影深浅不一（box-shadow值各不相同），style.css定义了标准阴影但各页面组件使用自定义阴影 - 建议统一使用CSS变量定义阴影
- [ ] [UI] 颜色变量使用不规范 - 硬编码颜色值与CSS变量混用，如五行颜色直接写死（#ffd700、#228b22等） - 建议将所有颜色提取为CSS变量
- [ ] [UI] 首页Hero区域文字颜色问题 - Home.vue第344、351、387、394、456、461、516、524、571行使用白色文字（color: #fff, rgba(255,255,255,0.7)等），与白色主题冲突 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能卡片背景色与主题不匹配 - Home.vue第587-591行feature-card使用深色背景（rgba(255,255,255,0.05)），与白色主题不协调 - 建议改为使用var(--bg-card)
- [ ] [UI] 移动端导航菜单关闭按钮过小 - App.vue第520行mobile-nav-close按钮padding仅5px，不符合44px最小触摸区域规范 - 建议增大点击区域至44x44px
- [ ] [UI] 浮动陪伴组件关闭按钮过小 - App.vue第815-823行close-btn宽度仅28px，不符合44px最小触摸区域规范 - 建议增大至44px
- [ ] [2026-03-16] 前端缺少Pinia状态管理 - frontend/src/stores目录不存在 - 虽然main.js中引入了Pinia，但没有创建stores目录和用户状态管理 - 建议创建stores目录，添加userStore管理用户角色和权限
- [ ] [2026-03-16] 前端API调用参数错误 - frontend/src/views/admin/Config.vue第404行 - updateFeature调用时参数传递可能存在问题，需要检查admin.js中函数定义 - 建议核对API定义和调用处的参数顺序
- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、shichen等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实际调用API - 建议实现真实的API调用
- [ ] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-386行 - pieChart和trendChart被定义但没有实际使用，图表初始化代码缺失 - 建议完成图表初始化
- [ ] [2026-03-16] 后端AdminAuth中间件logOperation方法未完整实现 - backend/app/middleware/AdminAuth.php第53-68行 - 方法构建好日志数据但未执行记录操作，代码被注释 - 建议实现日志记录逻辑：Db::name('admin_operation_log')->insert($data)
- [ ] [2026-03-16] 后端AdminAuthService缺少无效adminId校验 - backend/app/service/AdminAuthService.php第30-41行 - checkPermission方法没有处理$adminId为0或负数的情况 - 建议添加if ($adminId <= 0) { return false; }
- [ ] [2026-03-16] 后端返回码格式不统一 - backend/app/controller/Admin.php多处 - 混合使用code=200和code=0表示成功 - 建议统一使用$this->success()和$this->error()方法保持一致性
- [ ] [2026-03-16] 后端Admin.php权限检查返回格式不一致 - backend/app/controller/Admin.php第89,147,201行 - 权限检查使用$this->checkPermission()但返回格式与其他方法不一致（使用json()而非$this->error()） - 建议统一使用$this->error('无权限', 403)保持一致性
- [ ] [2026-03-16] 后端Content.php模型类未导入 - backend/app/controller/Content.php多处 - 使用\app\model\Page等全局命名空间，未导入模型类 - 建议添加相应的use语句导入模型类
- [ ] [2026-03-16] 后端Auth.php模型类导入不一致 - backend/app/controller/Auth.php第59,118行 - 使用\app\model\PointsRecord全局命名空间，但文件顶部已使用use导入其他模型 - 建议统一添加use app\model\PointsRecord;
- [ ] [2026-03-16] 后端Auth.php邀请码限制逻辑不完整 - backend/app/controller/Auth.php第283-357行 - 尝试次数超过10次后仅记录日志但不阻止操作 - 建议超过限制直接返回错误并阻止继续尝试
- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 前端AlmanacManage.vue API调用缺失 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - submitForm和generateMonth函数中只有模拟延迟，没有实际API调用 - 建议添加真实的API保存和生成调用
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，filteredList计算属性没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [2026-03-16] 前端Config.vue未使用loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有在页面加载时使用 - 建议在onMounted中使用loading状态
- [ ] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用，onMounted和onUnmounted为空 - 建议实现图表初始化逻辑或删除未使用的代码
- [ ] [UI] 按钮样式不统一 - 各页面按钮样式不一致，有的使用渐变背景（linear-gradient(135deg, #e94560, #ff6b6b)），有的使用纯色，圆角也不统一（12px/25px/30px） - 建议统一使用style.css中定义的.btn-primary和.btn-secondary类
- [ ] [UI] 卡片圆角不统一 - 各页面卡片圆角不一致（12px/15px/16px/20px） - 建议统一使用16px或20px圆角
- [ ] [UI] 表单输入框样式不统一 - 不同页面输入框样式差异大，有的有边框，有的无边框，背景色也不一致 - 建议统一使用style.css中定义的.input-field类
- [ ] [UI] 页面标题样式不统一 - 有的页面使用.page-title（36px），有的使用.section-title（32px），对齐方式也不同 - 建议统一标题样式规范
- [ ] [UI] 移动端导航菜单关闭按钮过小 - 移动端菜单的关闭按钮（✕）在44px以下，不符合触摸区域最小44px的规范 - 建议增大点击区域至44x44px
- [ ] [UI] 八字排盘结果表格在移动端显示拥挤 - Bazi.vue中的排盘表格在移动端字体过小（10px-12px），可读性差 - 建议优化移动端表格布局，考虑横向滚动或卡片式布局
- [ ] [UI] 加载状态样式不统一 - 各页面加载动画样式不一致，有的是太极图，有的是简单spinner - 建议统一加载组件
- [ ] [UI] 积分提示在各页面重复定义 - points-hint样式在多个页面重复定义，维护困难 - 建议提取为公共组件
- [ ] [UI] 响应式断点不统一 - 各页面使用不同的断点值（576px/768px/992px混用） - 建议统一响应式断点为576px/768px/992px/1200px
- [ ] [UI] 移动端字体大小未调整 - 移动端仍然使用桌面端字体大小，如hero-title在移动端仍为40px - 建议移动端标题缩小至28px左右
- [ ] [UI] 触摸目标过小 - 部分按钮和链接在移动端触摸区域过小，如塔罗牌点击区域 - 建议确保触摸目标至少44x44px
- [ ] [UI] 卡片间距不一致 - 各页面卡片padding和margin不统一（24px/30px/40px混用） - 建议统一为padding: 24px; margin-bottom: 24px
- [ ] [UI] 表单布局不统一 - 各页面表单输入框样式差异大，有的使用Element Plus组件，有的使用原生元素 - 建议统一使用Element Plus表单组件

### 🔴 高优先级（功能性问题）

- [ ] [UI] 主题系统严重不一致 - style.css定义白色主题（--bg-primary: #ffffff），但所有页面使用深色背景（rgba(0,0,0,0.2)、rgba(255,255,255,0.05)） - 建议统一主题方向：要么改为深色主题，要么将所有页面改为白色主题
- [ ] [UI] 文字颜色与背景冲突 - 多处使用白色文字（color: #fff、rgba(255,255,255,0.8)），在白色背景下完全不可读 - 建议根据主题统一文字颜色
- [ ] [UI] 登录页与整体主题严重不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与其他页面白色主题定义冲突 - 建议统一登录页主题风格

### 🟡 中优先级（体验问题）

- [ ] [UI] 按钮样式不统一 - 各页面按钮圆角不一致（12px/20px/25px/30px混用），有的使用渐变有的使用纯色 - 建议统一使用style.css中定义的.btn-primary（25px圆角）
- [ ] [UI] 卡片圆角不统一 - 各页面卡片圆角不一致（12px/15px/16px/20px混用） - 建议统一为16px或20px
- [ ] [UI] 表单输入框样式混乱 - 不同页面输入框样式差异大，有的有边框有的无边框，背景色也不一致（rgba(255,255,255,0.1) vs #fff） - 建议统一使用style.css中定义的.input-field类
- [ ] [UI] 页面标题样式不统一 - 有的页面使用.page-title（36px），有的使用.section-title（32px），对齐方式也不同（居中vs左对齐） - 建议建立统一的标题层级系统
- [ ] [UI] 响应式断点不统一 - 各页面使用不同的断点值（576px/768px/992px混用） - 建议统一响应式断点为576px/768px/992px/1200px
- [ ] [UI] 移动端字体大小未调整 - 移动端仍然使用桌面端字体大小，如hero-title在移动端仍为40px（应缩小至28px左右） - 建议建立响应式字体系统
- [ ] [UI] 触摸目标过小 - 部分按钮和链接在移动端触摸区域过小，不符合44px最小触摸区域规范 - 建议确保所有可点击元素至少44x44px
- [ ] [UI] 八字排盘结果表格在移动端显示拥挤 - Bazi.vue中的排盘表格在移动端字体过小（10px-12px），藏干信息难以辨认 - 建议优化移动端表格布局，考虑横向滚动或卡片式布局
- [ ] [UI] 图标系统混乱 - 大量使用emoji作为图标（💎、☯、🎴等），没有统一图标库，在不同平台显示效果不一致 - 建议引入@element-plus/icons-vue统一图标
- [ ] [UI] 加载状态样式不统一 - 各页面加载动画样式不一致（太极图、简单spinner、Element Plus加载组件混用） - 建议统一加载组件
- [ ] [UI] 积分提示样式在各页面重复定义 - points-hint样式在Bazi.vue、Tarot.vue等多个页面重复定义，维护困难 - 建议提取为公共组件PointsHint
- [ ] [UI] 阴影效果不一致 - 各组件阴影深浅不一（box-shadow值各不相同），style.css定义了标准阴影但各页面组件使用自定义阴影 - 建议统一使用CSS变量定义阴影
- [ ] [UI] 颜色变量使用不规范 - 硬编码颜色值与CSS变量混用，如五行颜色直接写死（#ffd700、#228b22等） - 建议将所有颜色提取为CSS变量

### 🟢 低优先级（优化问题）

- [ ] [UI] 首页Hero区域背景渐变与白色主题不协调 - Home.vue的hero区域使用radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%)，在白色主题下效果不明显 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 功能卡片图标大小不统一 - 首页feature-card中的图标大小不一致（48px），与其他页面图标不协调 - 建议统一图标尺寸规范
- [ ] [UI] 页脚链接间距在移动端过小 - 移动端footer-links的gap为20px，在较小屏幕上显得拥挤 - 建议增加间距或改为垂直排列
- [ ] [UI] 浮动陪伴组件位置在部分页面可能遮挡内容 - floating-companion固定在右下角，在某些页面可能遮挡重要按钮 - 建议增加智能避让或可调节位置功能
- [ ] [UI] 塔罗牌卡片在移动端显示过小 - Tarot.vue中的塔罗卡片在移动端宽度仅120px，难以看清内容 - 建议优化为更大的卡片或横向滑动布局
- [ ] [UI] 六爻卦象线条样式可以更加美观 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域在移动端显示不佳 - Hehun.vue中的bazi-compare在移动端flex-direction: column后，中间的分隔符（💕）旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中的score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 缺少页面过渡动画 - 页面切换时没有过渡效果，体验较生硬 - 建议添加Vue页面过渡动画
- [ ] [UI] 输入框焦点状态不明显 - 输入框focus状态样式不够突出，用户体验不佳 - 建议增强焦点状态的边框颜色和阴影
- [ ] [UI] 字体大小层级不清晰 - 标题和正文字体大小差异不够明显，如hero-title 56px与hero-subtitle 20px差距过大 - 建议建立统一的字体层级系统
- [ ] [2026-03-16] 前端SEOStats.vue未使用的导入和变量 - frontend/src/views/admin/SEOStats.vue第256行、第385-386行 - onUnmounted被导入但未使用，pieChart和trendChart定义但未使用 - 建议清理未使用的代码
- [ ] [2026-03-16] 前端Config.vue未使用的loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有使用 - 建议删除或使用该变量
- [x] [2026-03-16] 前端SEOStats.vue缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue - 搜索框输入没有防抖处理，可能导致频繁触发筛选 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue、SEOManage.vue - 上传组件只有on-success回调，没有on-error处理 - 建议添加错误处理回调
- [ ] [2026-03-16] 后端AiAnalysis.php返回码不一致 - backend/app/controller/AiAnalysis.php第89-97行 - 使用code=0表示成功，与其他控制器使用code=200不一致 - 建议统一返回码格式
- [ ] [2026-03-16] 后端Vip.php返回格式不一致 - backend/app/controller/Vip.php第163-166行 - 直接使用json返回，没有使用$this->success() - 建议统一使用BaseController的方法
- [ ] [2026-03-16] 前端Bazi.vue未使用变量yearlyTrendData - frontend/src/views/Bazi.vue第950行 - yearlyTrendData变量被声明并赋值，但在组件中从未被使用 - 建议删除此变量或在模板中使用
- [ ] [2026-03-16] 后端AdminAuth中间件日志记录敏感信息 - backend/app/middleware/AdminAuth.php第63行 - 记录请求参数可能包含敏感信息（如密码） - 建议过滤敏感字段后再记录
- [ ] [2026-03-16] 后端Auth.php邀请码尝试次数限制逻辑问题 - backend/app/controller/Auth.php第287-296行 - 达到10次后仅记录日志但不阻止，且Cache键未定义过期时间 - 建议超过限制直接返回错误并设置合理的过期时间
- [ ] [2026-03-16] 后端Vip.php返回格式不一致 - backend/app/controller/Vip.php第28-40行、96-100行 - 错误返回使用HTTP状态码，成功返回code为0，与其他控制器使用code=200不一致 - 建议统一返回码格式
- [ ] [2026-03-16] 后端Vip.php分页参数未验证 - backend/app/controller/Vip.php第155-156行 - page和limit参数未验证最小值和最大值 - 建议限制limit最大值为100，确保page >= 1
- [ ] [2026-03-16] 后端AdminAuthService缺少异常处理 - backend/app/service/AdminAuthService.php第83-219行 - 模型查询和缓存操作未处理异常 - 建议添加try-catch块和日志记录
- [ ] [2026-03-16] 后端Vip.php返回格式不一致 - backend/app/controller/Vip.php第28-40行、96-100行 - 错误返回使用HTTP状态码，成功返回code为0，与其他控制器使用code=200不一致 - 建议统一返回码格式
- [ ] [2026-03-16] 后端Auth.php邀请码尝试次数限制逻辑问题 - backend/app/controller/Auth.php第287-296行 - 达到10次后仅记录日志但不阻止操作，且Cache键未定义过期时间 - 建议超过限制直接返回错误并设置合理的过期时间
- [ ] [2026-03-16] 后端AdminAuth中间件日志记录敏感信息 - backend/app/middleware/AdminAuth.php第63行 - 记录请求参数可能包含敏感信息（如密码） - 建议过滤敏感字段后再记录
- [ ] [2026-03-16] 后端Content.php模型类未导入 - backend/app/controller/Content.php第29,78,80等行 - 使用\app\model\Page等全局命名空间调用，未使用use导入 - 建议统一添加use语句导入模型类
- [ ] [2026-03-16] 后端AiAnalysis.php返回码不一致 - backend/app/controller/AiAnalysis.php第89-97行 - 使用code=0表示成功，与其他控制器使用code=200不一致 - 建议统一返回码格式
- [ ] [UI] 首页Hero区域背景渐变与白色主题不协调 - Home.vue的hero区域使用radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%)，在白色主题下效果不明显 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 功能卡片图标大小不统一 - 首页feature-card中的图标大小不一致（48px），与其他页面图标不协调 - 建议统一图标尺寸规范
- [ ] [UI] 页脚链接间距在移动端过小 - 移动端footer-links的gap为20px，在较小屏幕上显得拥挤 - 建议增加间距或改为垂直排列
- [ ] [UI] 浮动陪伴组件位置在部分页面可能遮挡内容 - floating-companion固定在右下角，在某些页面可能遮挡重要按钮 - 建议增加智能避让或可调节位置功能
- [ ] [UI] 塔罗牌卡片在移动端显示过小 - Tarot.vue中的塔罗卡片在移动端宽度仅120px，难以看清内容 - 建议优化为更大的卡片或横向滑动布局
- [ ] [UI] 六爻卦象线条样式可以更加美观 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域在移动端显示不佳 - Hehun.vue中的bazi-compare在移动端flex-direction: column后，中间的分隔符（💕）旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中的score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 登录页面背景与整体主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与其他页面白色主题不一致 - 建议改为白色主题配色
- [ ] [UI] 缺少页面过渡动画 - 页面切换时没有过渡效果，体验较生硬 - 建议添加Vue页面过渡动画
- [ ] [UI] 图标使用不一致 - 各页面大量使用emoji作为图标（💎、☯、🎴等），没有统一的图标系统 - 建议引入图标库如@element-plus/icons-vue
- [ ] [UI] 阴影效果不一致 - 各组件阴影深浅不一，style.css定义了标准阴影但各页面组件使用自定义阴影 - 建议统一使用CSS变量定义阴影
- [ ] [UI] 颜色变量使用不规范 - 硬编码颜色值与CSS变量混用，如五行颜色直接写死 - 建议将所有颜色提取为CSS变量
- [ ] [UI] 输入框焦点状态不明显 - 输入框focus状态样式不够突出，用户体验不佳 - 建议增强焦点状态的边框颜色和阴影
- [ ] [UI] 字体大小层级不清晰 - 标题和正文字体大小差异不够明显，如hero-title 56px与hero-subtitle 20px差距过大 - 建议建立统一的字体层级系统
- [ ] [UI] 输入框焦点状态不明显 - 输入框focus状态样式不够突出，用户体验不佳 - 建议增强焦点状态的边框颜色和阴影
- [ ] [UI] 首页Hero区域背景渐变与白色主题不协调 - Home.vue的hero区域使用radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%)，在白色主题下效果不明显 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 功能卡片图标大小不统一 - 首页feature-card中的图标大小不一致（48px），与其他页面图标不协调 - 建议统一图标尺寸规范
- [ ] [UI] 页脚链接间距在移动端过小 - 移动端footer-links的gap为20px，在较小屏幕上显得拥挤 - 建议增加间距或改为垂直排列
- [ ] [UI] 浮动陪伴组件位置在部分页面可能遮挡内容 - floating-companion固定在右下角，在某些页面可能遮挡重要按钮 - 建议增加智能避让或可调节位置功能
- [ ] [UI] 塔罗牌卡片在移动端显示过小 - Tarot.vue中的塔罗卡片在移动端宽度仅120px，难以看清内容 - 建议优化为更大的卡片或横向滑动布局
- [ ] [UI] 六爻卦象线条样式可以更加美观 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域在移动端显示不佳 - Hehun.vue中的bazi-compare在移动端flex-direction: column后，中间的分隔符（💕）旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中的score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 缺少页面过渡动画 - 页面切换时没有过渡效果，体验较生硬 - 建议添加Vue页面过渡动画
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 卡片hover效果不一致 - 各页面卡片hover效果差异大，有的上移有的放大有的变色 - 建议统一hover动画效果
- [ ] [UI] 表单标签样式不统一 - 各页面表单标签字体大小、颜色、间距不统一 - 建议建立统一的表单标签规范
- [ ] [UI] 页面最大宽度限制不一致 - 有的页面使用container（1200px），有的没有限制 - 建议统一内容区域最大宽度

---

## 代码逻辑问题（本次检查新增）

### 🔴 高优先级（功能性问题）

- [x] [2026-03-16] 管理端Config.vue中updateFeature函数名冲突 - frontend/src/views/admin/Config.vue - 已重命名本地函数为handleUpdateFeature - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php - 第17行JWT密钥硬编码，应从配置文件读取
- [ ] [2026-03-16] 后端Auth控制器缺少Cache类导入 - backend/app/controller/Auth.php - 第288行使用Cache但未导入think\facade\Cache

### 🟡 中优先级（体验问题）

- [ ] [2026-03-16] 前端AlmanacManage.vue黄历数据API未实现 - frontend/src/views/admin/AlmanacManage.vue - 黄历数据加载和保存使用模拟数据，需要接入真实API
- [ ] [2026-03-16] 前端SEOManage.vue站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现
- [ ] [2026-03-16] 后端Admin控制器返回码格式不统一 - backend/app/controller/Admin.php - 部分接口返回code=200，部分返回code=0，应统一
- [ ] [2026-03-16] 后端缺少AdminAuthService实现检查 - backend/app/service/AdminAuthService.php - 需要确认权限检查逻辑是否完整

### 🟢 低优先级（优化问题）

- [ ] [2026-03-16] 前端Login.vue用户协议和隐私政策功能未实现 - frontend/src/views/Login.vue - showAgreement和showPrivacy方法仅显示提示
- [ ] [2026-03-16] 前端Bazi.vue中isCurrentDaYun方法使用固定年龄 - frontend/src/views/Bazi.vue - 第1254行currentAge固定为30，应根据出生日期计算
- [ ] [2026-03-16] 后端AdminAuth中间件logOperation方法未完整实现 - backend/app/middleware/AdminAuth.php - 第53-68行日志记录为注释状态

## 已完成项目

- [x] [2026-03-16] 前端Bazi.vue中AI解盘相关变量未定义 - frontend/src/views/Bazi.vue - 已添加aiLoadingTime、aiAbortController、aiLoadingTimer的ref定义 - 修复时间: 2026-03-16

---
*最后更新: 2026-03-16 - 待办处理执行器*
