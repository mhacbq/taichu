# 太初命理网站 - 待办修复列表

> 自动生成时间: 2026-03-16
> 本文件由自动化检查任务维护

## 自动化任务说明

已设置9个自动化任务（**分工优化版**）：

### 🔍 检查类任务（发现问题）
| 任务名称 | 频率 | 说明 | 角色 |
|---------|------|------|------|
| 网站逻辑检查任务 | 每30分钟 | 检查前端/后端逻辑问题 | 👨‍💻 代码审查专家 |
| UI设计检查官 | 每30分钟 | 检查UI设计问题 | 🎨 产品经理/UI设计师 |
| 运营人员后台检查 | 每30分钟 | 使用后台管理系统并记录问题 | 👔 运营人员 |
| 占卜爱好者体验检查 | 每30分钟 | 体验占卜功能并记录准确性问题 | 🔮 占卜爱好者 |

### 🔧 修复类任务（解决问题）- **分工优化**
| 任务名称 | 频率 | 每次修复 | 负责领域 | Git提交 |
|---------|------|---------|---------|---------|
| **后端修复专家** | 每15分钟 | **5个后端问题** | PHP后端安全/API/数据库 | ✅ 自动提交 |
| **前端修复专家** | 每15分钟 | **5个前端问题** | Vue组件/功能逻辑/代码规范 | ✅ 自动提交 |
| **UI修复专家** | 每15分钟 | **5个UI问题** | 主题统一/样式/响应式/图标 | ✅ 自动提交 |
| 待办处理执行器 | 每30分钟 | 5个问题（综合） | 跨领域问题 | - |
| 前端开发修复任务 | 每20分钟 | 3-5个前端问题 | 前端专项 | ✅ 自动提交 |

## 代码逻辑检查报告 - 2026-03-16 第十八轮

### 本次检查重点
- 检查范围：前端Vue项目关键文件、后端PHP控制器
- 检查维度：语法错误、类型错误、API调用、返回格式统一性、未使用代码
- 发现问题：发现8个新问题，需要添加到待处理列表

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [ ] [2026-03-16 17:30] 后端Admin.php返回格式不统一 - backend/app/controller/Admin.php第129、268行 - 混用json()和$this->success()/$this->error()两种返回方式 - 建议统一使用BaseController的方法
- [ ] [2026-03-16 17:30] 后端Content.php返回格式不统一 - backend/app/controller/Content.php全部返回 - 只使用json()返回，没有使用继承的$this->success()/$this->error()方法 - 建议统一返回格式
- [ ] [2026-03-16 17:30] 后端AiAnalysis.php返回格式不统一 - backend/app/controller/AiAnalysis.php第48、384行 - 混用json()和$this->success()/$this->error()两种返回方式 - 建议统一使用BaseController的方法

#### 🟡 中优先级（体验问题）
- [ ] [2026-03-16 17:30] 后端Vip.php未使用的导入 - backend/app/controller/Vip.php第7行 - UserVip模型已导入但未使用 - 建议删除未使用的导入
- [ ] [2026-03-16 17:30] 后端Paipan.php重复变量定义 - backend/app/controller/Paipan.php第58、64行 - $mode变量被重复定义 - 建议删除重复的定义
- [ ] [2026-03-16 17:30] 后端Paipan.php未使用的本地方法 - backend/app/controller/Paipan.php第214-238行 - generateSimpleInterpretation方法定义了但未使用 - 建议删除未使用的本地方法
- [ ] [2026-03-16 17:30] 前端Tarot.vue未使用参数 - frontend/src/views/Tarot.vue第565-568行 - showCardDetail函数中index参数传入但未使用 - 建议移除未使用的参数或添加_前缀
- [ ] [2026-03-16 17:30] 前端App.vue未使用的导入 - frontend/src/App.vue第167行 - HomeFilled图标导入但未使用 - 建议移除未使用的导入

### 已修复/已不存在的问题
1. **前端Bazi.vue result对象空值检查** - 已使用可选链操作符?.进行保护
2. **前端Bazi.vue aiAbortController空值检查** - 已使用可选链操作符?.进行保护
3. **前端Bazi.vue 定时器清理** - 已在finally块中统一清理
4. **后端AdminAuth中间件JWT密钥** - 已从环境变量读取，未硬编码
5. **后端AdminAuth中间件日志敏感信息** - 已正确过滤敏感字段
6. **后端AdminAuthService无效adminId校验** - 已添加多处校验
7. **后端AiAnalysis.php cURL SSL验证** - 已正确启用SSL验证

---

## UI设计检查报告 - 2026-03-16 第十七轮

### 本次检查重点
- 检查范围：前端Vue项目全部视图页面和组件
- 检查维度：整体视觉风格、首页设计、功能页面、交互体验、移动端适配
- 发现问题：核心主题不一致问题仍然是最高优先级，需要尽快决策

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [x] [UI] 主题方向决策 - Home.vue - 已将首页各区域背景和文字颜色改为CSS变量，适配白色主题 - 修复时间: 2026-03-16
- [x] [UI] 导航栏与页面内容区视觉割裂 - App.vue - 已统一为白色导航栏，移动端菜单也使用白色主题 - 修复时间: 2026-03-16
- [x] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue - 已将白色文字改为使用var(--text-primary)和var(--text-secondary) - 修复时间: 2026-03-16
- [x] [UI] 登录页Logo仍使用emoji图标 - Login.vue - 已将☯emoji替换为Element Plus的YinYang图标 - 修复时间: 2026-03-16
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [ ] [UI] 各功能页面大量使用白色文字 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue/Help.vue/Recharge.vue等页面多处使用color: #fff，与白色主题冲突 - 建议统一使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 后台管理页面深色背景与白色主题不协调 - admin/*.vue页面使用rgba(0,0,0,0.2)深色背景，与style.css定义的白色主题冲突 - 建议统一后台主题风格
- [ ] [UI] 六爻页面标题使用emoji图标 - Liuyao.vue第7行使用☯emoji作为标题图标 - 建议替换为Element Plus图标YinYang
- [ ] [UI] 合婚页面标题使用emoji图标 - Hehun.vue第7行使用💕emoji作为标题图标 - 建议替换为Element Plus图标如Link或Heart

#### 🟡 中优先级（体验问题）
- [x] [UI] 导航栏大量使用emoji图标 - App.vue - 已替换为Element Plus图标（YinYang, Diamond, User, SwitchButton, HomeFilled, Calendar, Magic, Star, Sunrise, Collection, Present, Close） - 修复时间: 2026-03-16
- [x] [UI] 首页大量使用emoji图标 - Home.vue - 已替换为Element Plus图标（Sunrise, Sunny, Moon, Diamond, Cherry, Calendar, MagicStick, Star, Aim, Present, Switch, Link） - 修复时间: 2026-03-16
- [x] [UI] 八字排盘页面使用emoji图标 - Bazi.vue - 已将💝、💎、🌱、🔮、❓、🎁、⚡等emoji替换为Element Plus图标(HeartFilled, Diamond, Magic, QuestionFilled, Present, Lightning) - 修复时间: 2026-03-16
- [x] [UI] 塔罗占卜页面使用emoji图标 - Tarot.vue - 已将💎、🎴、🔮、💭、💼、💕、🌱、🤔、👥、💾、📤、🔄等emoji替换为Element Plus图标(Diamond, Magic, ChatDotRound, Briefcase, StarFilled, UserFilled, QuestionFilled, Document, Download, RefreshRight) - 修复时间: 2026-03-16
- [x] [UI] 六爻占卜页面使用emoji图标 - Liuyao.vue - 已将☯、🔄、💾、🗑等emoji替换为Element Plus图标(YinYang, RefreshRight, Download, Delete, Magic) - 修复时间: 2026-03-16
- [ ] [UI] 每日运势页面使用emoji图标 - Daily.vue使用🔮、❓、📿等emoji - 建议统一使用图标库
- [x] [UI] 移动端导航关闭按钮触摸区域过小 - App.vue - 已增大padding至12px，确保最小点击区域44x44px - 修复时间: 2026-03-16
- [x] [UI] 浮动陪伴组件关闭按钮触摸区域过小 - App.vue - 已增大宽高至44px，符合最小触摸区域规范 - 修复时间: 2026-03-16
- [x] [UI] 登录页输入框使用emoji图标 - Login.vue - 已替换为Element Plus图标（Phone, Lock, Lightbulb） - 修复时间: 2026-03-16
- [ ] [UI] 浮动陪伴组件关闭按钮尺寸仍偏小 - App.vue第853-871行close-btn宽度仅28px，建议增大至44px以符合WCAG 2.1标准
- [ ] [UI] 合婚页面八字对比区域使用emoji分隔符 - Hehun.vue第41行使用💕emoji作为分隔符 - 建议改为Element Plus图标或CSS装饰

#### 🟢 低优先级（美观问题）
- [ ] [UI] 首页Hero区域背景渐变在白色主题下效果不明显 - Home.vue第297行使用radial-gradient，在白色背景下效果微弱 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域分隔符突兀 - Hehun.vue中bazi-divider使用💕emoji，在移动端旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 首页feature-card深色背景与白色主题不协调 - Home.vue第587-591行使用rgba(255,255,255,0.05)深色背景 - 建议改为var(--bg-card)浅色背景
- [ ] [UI] 首页about区域深色背景与白色主题不协调 - Home.vue第632行使用rgba(0,0,0,0.2)深色背景 - 建议改为var(--bg-secondary)浅色背景
- [ ] [UI] 塔罗牌使用emoji表示 - Tarot.vue第109行使用emoji表示塔罗牌 - 建议添加真实塔罗牌图片或使用SVG图标

---

## UI设计检查报告 - 2026-03-16 第十五轮

#### 🟡 中优先级（体验问题）
- [x] [UI] 导航栏大量使用emoji图标 - App.vue - 已替换为Element Plus图标（YinYang, Diamond, User, SwitchButton, HomeFilled, Calendar, Magic, Star, Sunrise, Collection, Present, Close） - 修复时间: 2026-03-16
- [x] [UI] 首页大量使用emoji图标 - Home.vue - 已替换为Element Plus图标（Sunrise, Sunny, Moon, Diamond, Cherry, Calendar, MagicStick, Star, Aim, Present, Switch, Link） - 修复时间: 2026-03-16
- [ ] [UI] 八字排盘页面使用emoji图标 - Bazi.vue使用💝、💎、🌱、🔮、❓、🎁、⚡等emoji - 建议统一使用图标库
- [ ] [UI] 塔罗占卜页面使用emoji图标 - Tarot.vue使用💎、🎴、🔮、💭、💼、💕、🌱、🤔、👥、💾、📤、🔄等emoji - 建议统一使用图标库
- [ ] [UI] 六爻占卜页面使用emoji图标 - Liuyao.vue使用☯、🔄、💾、🗑等emoji - 建议统一使用图标库
- [ ] [UI] 合婚页面使用emoji图标 - Hehun.vue使用💕、👨、🔓、🤖、💝、🔄、📄等emoji - 建议统一使用图标库
- [x] [UI] 移动端导航关闭按钮触摸区域过小 - App.vue - 已增大padding至12px，确保最小点击区域44x44px - 修复时间: 2026-03-16
- [x] [UI] 浮动陪伴组件关闭按钮触摸区域过小 - App.vue - 已增大宽高至44px，符合最小触摸区域规范 - 修复时间: 2026-03-16
- [x] [UI] 登录页输入框使用emoji图标 - Login.vue - 已替换为Element Plus图标（Phone, Lock, Lightbulb） - 修复时间: 2026-03-16

#### 🟢 低优先级（美观问题）
- [ ] [UI] 首页Hero区域背景渐变在白色主题下效果不明显 - Home.vue第297行使用radial-gradient，在白色背景下效果微弱 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域分隔符突兀 - Hehun.vue中bazi-divider使用💕emoji，在移动端旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 首页feature-card深色背景与白色主题不协调 - Home.vue第587-591行使用rgba(255,255,255,0.05)深色背景 - 建议改为var(--bg-card)浅色背景
- [ ] [UI] 首页about区域深色背景与白色主题不协调 - Home.vue第632行使用rgba(0,0,0,0.2)深色背景 - 建议改为var(--bg-secondary)浅色背景

---

## UI设计检查报告 - 2026-03-16 第十四轮

### 本次检查重点
- 检查范围：前端Vue项目全部视图页面和组件
- 检查维度：整体视觉风格、首页设计、功能页面、交互体验、移动端适配
- 发现问题：核心主题不一致问题仍然是最高优先级，需要尽快决策

### 历史检查发现的问题（已归档）

#### 🔴 高优先级（功能性问题）
- [ ] [UI] 主题方向决策 - 这是最核心的设计问题，影响整个网站的视觉一致性 - 建议：考虑到命理玄学的行业属性，建议统一为深色主题（神秘、专业感），或全面改为白色主题（清新、现代感）
- [ ] [UI] 导航栏与页面内容区视觉割裂 - App.vue使用白色导航栏，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue多处使用白色文字（color: #fff, rgba(255,255,255,0.7)等），在浅色背景下不可见 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [ ] [UI] 各功能页面大量使用白色文字 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue/Help.vue/Recharge.vue等页面多处使用color: #fff，与白色主题冲突 - 建议统一使用var(--text-primary)和var(--text-secondary)

#### 🟡 中优先级（体验问题）
- [x] [UI] 导航栏大量使用emoji图标 - App.vue - 已替换为Element Plus图标（YinYang, Diamond, User, SwitchButton, HomeFilled, Calendar, Magic, Star, Sunrise, Collection, Present, Close） - 修复时间: 2026-03-16
- [ ] [UI] 首页大量使用emoji图标 - Home.vue使用🌅、☀️、🌙、💎、🌸、🎁、✨、🔮、📅、🎴、💡、☯、💕、🌟、🎯、👩、👨、👦等emoji - 建议统一使用图标库
- [ ] [UI] 八字排盘页面使用emoji图标 - Bazi.vue使用💝、💎、🌱、🔮、❓、🎁、⚡等emoji - 建议统一使用图标库
- [ ] [UI] 塔罗占卜页面使用emoji图标 - Tarot.vue使用💎、🎴、🔮、💭、💼、💕、🌱、🤔、👥、💾、📤、🔄等emoji - 建议统一使用图标库
- [ ] [UI] 六爻占卜页面使用emoji图标 - Liuyao.vue使用☯、🔄、💾、🗑等emoji - 建议统一使用图标库
- [ ] [UI] 合婚页面使用emoji图标 - Hehun.vue使用💕、👨、🔓、🤖、💝、🔄、📄等emoji - 建议统一使用图标库
- [x] [UI] 移动端导航关闭按钮触摸区域过小 - App.vue - 已增大padding至12px，确保最小点击区域44x44px - 修复时间: 2026-03-16
- [x] [UI] 浮动陪伴组件关闭按钮触摸区域过小 - App.vue - 已增大宽高至44px，符合最小触摸区域规范 - 修复时间: 2026-03-16
- [ ] [UI] 移动端导航菜单仍使用emoji图标 - App.vue第64-79行mobile-nav-link使用🏠、📅、🎴、☯、💕、🌟等emoji - 建议替换为Element Plus图标

#### 🟢 低优先级（美观问题）
- [ ] [UI] 首页Hero区域背景渐变在白色主题下效果不明显 - Home.vue第297行使用radial-gradient，在白色背景下效果微弱 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域分隔符突兀 - Hehun.vue中bazi-divider使用💕emoji，在移动端旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 首页feature-card深色背景与白色主题不协调 - Home.vue第587-591行使用rgba(255,255,255,0.05)深色背景 - 建议改为var(--bg-card)浅色背景
- [ ] [UI] 首页about区域深色背景与白色主题不协调 - Home.vue第632行使用rgba(0,0,0,0.2)深色背景 - 建议改为var(--bg-secondary)浅色背景

---

## UI设计检查报告 - 2026-03-16 第十一轮

### 本次检查重点
- 检查范围：前端Vue项目全部视图页面和组件
- 检查维度：整体视觉风格、首页设计、功能页面、交互体验、移动端适配
- 发现问题：核心主题不一致问题仍然存在，需要决策

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [ ] [UI] 主题方向决策 - 这是最核心的设计问题，影响整个网站的视觉一致性 - 建议：考虑到命理玄学的行业属性，建议统一为深色主题（神秘、专业感），或全面改为白色主题（清新、现代感）
- [ ] [UI] 导航栏与页面内容区视觉割裂 - App.vue使用白色导航栏，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue多处使用白色文字（color: #fff, rgba(255,255,255,0.7)等），在浅色背景下不可见 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [ ] [UI] 八字排盘加载状态深色背景与白色主题冲突 - Bazi.vue第1440行loading-state使用深色背景（rgba(0,0,0,0.3)），与白色主题不协调 - 建议改为浅色背景或使用主题CSS变量
- [ ] [UI] 各功能页面大量使用白色文字 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue/Help.vue/Recharge.vue等页面多处使用color: #fff，与白色主题冲突 - 建议统一使用var(--text-primary)和var(--text-secondary)

#### 🟡 中优先级（体验问题）
- [ ] [UI] 导航栏大量使用emoji图标 - App.vue使用☯、💎、👤、🚪、🏠、📅、🎴、💕、🌟、🌸、💝等emoji作为图标 - 建议引入@element-plus/icons-vue统一图标系统
- [ ] [UI] 首页大量使用emoji图标 - Home.vue使用🌅、☀️、🌙、💎、🌸、🎁、✨、🔮、📅、🎴、💡、☯、💕、🌟、🎯、👩、👨、👦等emoji - 建议统一使用图标库
- [ ] [UI] 八字排盘页面使用emoji图标 - Bazi.vue使用💝、💎、🌱、🔮、❓、🎁、⚡等emoji - 建议统一使用图标库
- [ ] [UI] 塔罗占卜页面使用emoji图标 - Tarot.vue使用💎、🎴、🔮、💭、💼、💕、🌱、🤔、👥、💾、📤、🔄等emoji - 建议统一使用图标库
- [ ] [UI] 六爻占卜页面使用emoji图标 - Liuyao.vue使用☯、🔄、💾、🗑等emoji - 建议统一使用图标库
- [ ] [UI] 合婚页面使用emoji图标 - Hehun.vue使用💕、👨、🔓、🤖、💝、🔄、📄等emoji - 建议统一使用图标库
- [ ] [UI] 移动端导航关闭按钮触摸区域过小 - App.vue第60行mobile-nav-close按钮padding仅5px，实际点击区域不足44px - 建议增大至44x44px
- [ ] [UI] 浮动陪伴组件关闭按钮触摸区域过小 - App.vue第138行close-btn宽度仅28px，不符合44px最小触摸区域规范 - 建议增大至44px
- [ ] [UI] 按钮圆角不统一 - 各页面按钮圆角不一致（12px/20px/25px/30px混用） - 建议统一使用style.css中定义的25px圆角
- [ ] [UI] 卡片hover效果不一致 - Home.vue中feature-card使用transform: translateY(-10px)，而其他页面卡片使用不同的hover效果 - 建议统一hover动画效果

#### 🟢 低优先级（美观问题）
- [ ] [UI] 首页Hero区域背景渐变在白色主题下效果不明显 - Home.vue第297行使用radial-gradient，在白色背景下效果微弱 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域分隔符突兀 - Hehun.vue中bazi-divider使用💕emoji，在移动端旋转90度显得突兀 - 建议优化移动端布局
- [ ] [UI] 功能卡片图标大小不统一 - 首页feature-card中的图标大小不一致（48px），与其他页面图标不协调 - 建议统一图标尺寸规范

---

## UI设计检查报告 - 2026-03-16 第十轮

### 本次检查重点
- 检查范围：前端Vue项目全部视图页面和组件
- 检查维度：整体视觉风格、首页设计、功能页面、交互体验、移动端适配
- 发现问题：核心主题不一致问题仍然存在，需要决策

### 历史检查发现的问题（已归档）

#### 🔴 高优先级（功能性问题）
- [ ] [UI] 主题方向决策 - 这是最核心的设计问题，影响整个网站的视觉一致性 - 建议：考虑到命理玄学的行业属性，建议统一为深色主题（神秘、专业感），或全面改为白色主题（清新、现代感）
- [ ] [UI] 导航栏与页面内容区视觉割裂 - App.vue使用白色导航栏，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue多处使用白色文字（color: #fff, rgba(255,255,255,0.7)等），在浅色背景下不可见 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格

#### 🟡 中优先级（体验问题）
- [ ] [UI] 导航栏大量使用emoji图标 - App.vue使用☯、💎、👤、🚪、🏠、📅、🎴、💕、🌟、🌸、💝等emoji作为图标 - 建议引入@element-plus/icons-vue统一图标系统
- [ ] [UI] 首页大量使用emoji图标 - Home.vue使用🌅、☀️、🌙、💎、🌸、🎁、✨、🔮、📅、🎴、💡、☯、💕、🌟、🎯、👩、👨、👦等emoji - 建议统一使用图标库
- [ ] [UI] 八字排盘页面使用emoji图标 - Bazi.vue使用💝、💎、🌱、🔮、❓、🎁、⚡等emoji - 建议统一使用图标库
- [ ] [UI] 塔罗占卜页面使用emoji图标 - Tarot.vue使用💎、🎴、🔮、💭、💼、💕、🌱、🤔、👥、💾、📤、🔄等emoji - 建议统一使用图标库
- [ ] [UI] 六爻占卜页面使用emoji图标 - Liuyao.vue使用☯、🔄、💾、🗑等emoji - 建议统一使用图标库
- [ ] [UI] 合婚页面使用emoji图标 - Hehun.vue使用💕、👨、🔓、🤖、💝、🔄、📄等emoji - 建议统一使用图标库
- [ ] [UI] 移动端导航关闭按钮触摸区域过小 - App.vue第60行mobile-nav-close按钮padding仅5px，实际点击区域不足44px - 建议增大至44x44px
- [ ] [UI] 浮动陪伴组件关闭按钮触摸区域过小 - App.vue第138行close-btn宽度仅28px，不符合44px最小触摸区域规范 - 建议增大至44px

#### 🟢 低优先级（美观问题）
- [ ] [UI] 首页Hero区域背景渐变在白色主题下效果不明显 - Home.vue第297行使用radial-gradient，在白色背景下效果微弱 - 建议使用更明显的浅色渐变或装饰性SVG背景
- [ ] [UI] 功能卡片hover效果不一致 - Home.vue第596-600行feature-card使用transform: translateY(-10px)，而其他页面卡片使用不同的hover效果 - 建议统一hover动画效果
- [ ] [UI] 页面内容区缺少统一背景色 - 各页面内容区背景色不一致，有的透明有的深色，与白色主题不协调 - 建议统一添加浅色背景
- [ ] [UI] 八字排盘加载状态深色背景 - Bazi.vue第1440行loading-state使用深色背景（rgba(0,0,0,0.3)），与白色主题冲突 - 建议改为浅色背景
- [ ] [UI] 每日运势页面评分圆圈在移动端过大 - Daily.vue中score-circle在移动端120x120px可能占用过多空间 - 建议响应式缩小至80x80px
- [ ] [UI] 六爻卦象线条样式单调 - Liuyao.vue中的yao-line使用简单线条，视觉效果较单调 - 建议增加更精致的卦象图形设计
- [ ] [UI] 合婚页面八字对比区域分隔符突兀 - Hehun.vue中bazi-divider使用💕emoji，在移动端旋转90度显得突兀 - 建议优化移动端布局

---

## 代码逻辑检查报告 - 2026-03-16 第十五轮

### 本次检查重点
- 检查范围：前端Vue项目关键文件、后端PHP控制器、中间件
- 检查维度：语法错误、类型错误、API调用、权限控制、安全问题
- 发现问题：发现8个新问题，其中4个高优先级问题需要尽快修复

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [x] [2026-03-16 15:00] ~~后端Auth.php微信登录仍使用模拟逻辑~~ - **已删除**: 根据需求，微信登录功能已移除，仅保留短信登录
- [x] [2026-03-16 15:00] 后端Content.php SQL注入风险 - backend/app/controller/Content.php第368-371行 - 已修复：改用ThinkPHP参数绑定语法 `$query->where('title|page_id', 'like', '%' . $keyword . '%')` - 修复时间: 2026-03-16
- [x] [2026-03-16 15:00] 后端AdminAuthService缺少无效adminId校验 - backend/app/service/AdminAuthService.php第31-42行 - 已修复：在getAdminPermissions和clearPermissionCache方法中添加`if ($adminId <= 0) { return false; }`校验 - 修复时间: 2026-03-16
- [ ] [2026-03-16 15:00] 前端Bazi.vue aiAbortController空值检查缺失 - frontend/src/views/Bazi.vue第1299-1300行、第1330行 - 访问aiAbortController.value.signal时没有做空值检查，可能导致运行时错误 - 建议使用可选链：aiAbortController.value?.signal

#### 🟡 中优先级（体验问题）
- [x] [2026-03-16 15:00] 后端AiAnalysis.php未使用的常量 - backend/app/controller/AiAnalysis.php第19-22行 - 已修复：移除未使用的CacheService导入以及ENABLE_CACHE和CACHE_TTL常量 - 修复时间: 2026-03-16
- [ ] [2026-03-16 15:00] 后端Vip.php使用emoji作为图标 - backend/app/controller/Vip.php第54-82行 - 返回的权益列表使用emoji图标（✨、📊、💎、💕、🎯、🎁），可能在某些系统或数据库编码环境下显示异常 - 建议改为使用图标库或SVG图标
- [ ] [2026-03-16 15:00] 后端Admin.php权限检查返回格式不统一 - backend/app/controller/Admin.php第89-91行、第147-149行、第201-203行 - dashboard和users方法使用$this->error()，userDetail方法使用json()，返回格式不一致 - 建议统一使用$this->error()方法
- [ ] [2026-03-16 15:00] 前端Bazi.vue多处潜在空值访问 - frontend/src/views/Bazi.vue第220-262行等 - result对象多层属性访问存在空值风险 - 建议使用可选链操作符?.或添加v-if判断

### 已修复/已不存在的问题
- [x] 前端Tarot.vue缺少computed导入 - 已修复：computed已正确从vue导入
- [x] 后端Auth.php邀请码暴力枚举防护 - 已修复：防护逻辑完整
- [x] 后端AiAnalysis.php cURL缺少SSL验证 - 已修复：已添加SSL验证配置
- [x] 前端Bazi.vue getYearlyTrend函数导入未使用 - 已不存在：代码已更新
- [x] 前端Config.vue loading变量未使用 - 已不存在：代码使用saving对象
- [x] 后端Admin.php feedbackList缺少权限检查 - 已修复：已添加权限检查
- [x] 后端AiAnalysis.php返回码不一致 - 已修复：已统一使用BaseController方法
- [x] 后端Admin.php返回码格式不统一 - 已修复：已统一使用$this->error()
- [x] 后端AdminAuthService异常处理不完整 - 已修复：已添加日志记录
- [x] 前端router/index.js未使用的导入 - 已使用：generateWebsiteSchema已在第315行使用

---

## 代码逻辑检查报告 - 2026-03-16 第十三轮

### 本次检查重点
- 检查范围：前端Vue项目关键文件、后端PHP控制器、中间件
- 检查维度：语法错误、类型错误、API调用、权限控制、安全问题
- 发现问题：发现15个新问题，其中3个高优先级问题需要尽快修复

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [x] [2026-03-16 14:00] 前端Tarot.vue缺少computed导入 - frontend/src/views/Tarot.vue第172行 - 已添加import { ref, onMounted, computed } from 'vue' - 修复时间: 2026-03-16
- [x] [2026-03-16 14:30] 后端AiAnalysis.php cURL缺少SSL验证 - backend/app/controller/AiAnalysis.php第276-294行 - 已添加CURLOPT_SSL_VERIFYPEER和CURLOPT_SSL_VERIFYHOST配置，启用SSL验证防止中间人攻击 - 修复时间: 2026-03-16
- [x] [2026-03-16 14:00] 前端App.vue localStorage解析缺少异常处理 - frontend/src/App.vue第220-221行 - JSON.parse(userInfo)可能抛出异常导致页面崩溃 - 已添加try-catch包裹和错误处理 - 修复时间: 2026-03-16

#### 🟡 中优先级（体验问题）
- [x] [2026-03-16 14:30] 后端Auth.php PointsRecord模型使用全局命名空间 - backend/app/controller/Auth.php第60,119,331,345行 - 已添加use app\model\PointsRecord;导入语句，并替换所有\app\model\PointsRecord为直接使用导入的类 - 修复时间: 2026-03-16
- [x] [2026-03-16 14:30] 后端Admin.php使用全局命名空间调用DailyFortune - backend/app/controller/Admin.php第99-100行 - 已添加use app\model\DailyFortune;导入语句，并替换所有\app\model\DailyFortune为直接使用导入的类 - 修复时间: 2026-03-16
- [x] [2026-03-16 14:30] 后端AiAnalysis.php返回码格式不一致 - backend/app/controller/AiAnalysis.php第54-55行、第103-106行 - 已统一使用BaseController的success()和error()方法替换所有json()返回 - 修复时间: 2026-03-16
- [ ] [2026-03-16 14:00] 后端AiAnalysis.php未使用的常量 - backend/app/controller/AiAnalysis.php第19-22行 - ENABLE_CACHE和CACHE_TTL定义但未使用 - 建议实现缓存逻辑或移除未使用的常量
- [ ] [2026-03-16 14:00] 前端router/index.js未使用的导入 - frontend/src/router/index.js第2行 - generateWebsiteSchema导入但未使用 - 建议删除未使用的导入
- [x] [2026-03-16 14:00] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue - 已添加宜、忌、干支、煞、值日等字段的验证规则 - 修复时间: 2026-03-16
- [ ] [2026-03-16 14:00] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue - pieChart和trendChart变量定义但未使用 - 建议实现图表初始化或删除未使用的代码

#### 🟢 低优先级（优化问题）
- [ ] [2026-03-16 14:00] 前端Tarot.vue selectedCardIndex变量未使用 - frontend/src/views/Tarot.vue第242行 - 变量定义后从未读取 - 建议删除该变量
- [ ] [2026-03-16 14:00] 前端Config.vue loading变量未使用 - frontend/src/views/admin/Config.vue - loading变量定义了但没有使用 - 建议删除或使用该变量
- [ ] [2026-03-16 14:00] 后端Vip.php使用emoji作为图标 - backend/app/controller/Vip.php第54-82行 - 返回的权益列表使用emoji图标 - 建议改为使用图标库或SVG图标
- [ ] [2026-03-16 14:00] 后端AdminAuthService缓存键前缀可能冲突 - backend/app/service/AdminAuthService.php第21行 - 使用admin:permissions:前缀可能与其他系统冲突 - 建议添加应用特定前缀如taichu:admin:permissions:

---

## 代码逻辑检查报告 - 2026-03-16 第17轮

### 本次检查重点
- 检查范围：前端Vue项目关键文件、后端PHP控制器、中间件
- 检查维度：语法错误、类型错误、API调用、权限控制、安全问题
- 发现问题：发现12个新问题，其中5个高优先级问题需要尽快修复

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [x] [2026-03-16 17:00] 后端Vip.php缺少用户认证中间件 - backend/app/controller/Vip.php第21行 - 已添加protected $middleware = [\app\middleware\Auth::class]中间件配置 - 修复时间: 2026-03-16
- [ ] [2026-03-16 17:00] 后端Paipan.php调用未定义服务方法 - backend/app/controller/Paipan.php第70-71、74行 - 调用interpretationService和fortuneAnalysisService的方法，但这些服务类未导入或可能不存在 - 建议确认服务类存在并添加正确的use导入语句
- [x] [2026-03-16 17:00] 后端AiAnalysis.php流式请求缺少SSL验证 - backend/app/controller/AiAnalysis.php第355-358行 - 已添加CURLOPT_SSL_VERIFYPEER和CURLOPT_SSL_VERIFYHOST配置，启用SSL验证防止中间人攻击 - 修复时间: 2026-03-16
- [ ] [2026-03-16 17:00] 后端Admin.php SQL注入风险 - backend/app/controller/Admin.php第163-170行 - 用户名和手机号搜索使用字符串拼接，虽然有preg_replace过滤但仍存在风险 - 建议使用参数绑定替代字符串拼接
- [x] [2026-03-16 17:00] 前端Bazi.vue藏干访问缺少可选链 - frontend/src/views/Bazi.vue第275-291行 - 已统一使用可选链和默认值：result.bazi?.month?.canggan || [] - 修复时间: 2026-03-16

#### 🟡 中优先级（体验问题）
- [x] [2026-03-16 17:00] 后端Admin.php API返回格式不一致 - backend/app/controller/Admin.php第129、184、224行等 - 已统一使用$this->error()和$this->success()替代json()返回 - 修复时间: 2026-03-16
- [x] [2026-03-16 17:00] 后端Admin.php updateUserStatus缺少输入验证 - backend/app/controller/Admin.php第245行 - 已添加in_array验证确保status只能是0/1/2 - 修复时间: 2026-03-16
- [x] [2026-03-16 17:00] 后端Vip.php使用emoji作为图标 - backend/app/controller/Vip.php第54-82行 - 已将emoji图标替换为图标库名称(star/document/diamond/heart/service/gift) - 修复时间: 2026-03-16
- [ ] [2026-03-16 17:00] 后端AiAnalysis.php缺少输入长度限制 - backend/app/controller/AiAnalysis.php第49-51、109-111行 - baziData和customPrompt没有长度限制 - 建议添加长度验证
- [x] [2026-03-16 17:00] 前端App.vue未使用的图标导入 - frontend/src/App.vue第167-185行 - 已删除未使用的图标导入(Home、Timer、Reading、Link、SunriseIcon) - 修复时间: 2026-03-16
- [x] [2026-03-16 17:00] 前端Tarot.vue selectedCardIndex变量未使用 - frontend/src/views/Tarot.vue第242行 - 已删除该变量定义和showCardDetail函数中的赋值 - 修复时间: 2026-03-16
- [ ] [2026-03-16 17:00] 前端管理端页面API调用缺失 - admin/AlmanacManage.vue/KnowledgeManage.vue/SEOManage.vue - submitForm、generateMonth等函数只有模拟延迟没有实际API调用 - 建议实现真实的API接口

---

## 代码逻辑检查报告 - 2026-03-16 第16轮

### 本次检查重点
- 检查范围：前端Vue项目关键文件、后端PHP控制器、中间件
- 检查维度：语法错误、类型错误、API调用、权限控制、安全问题
- 发现问题：发现7个新问题，其中3个高优先级问题需要尽快修复

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
- [ ] [2026-03-16 16:30] 前端Bazi.vue result对象多层属性访问空值风险 - frontend/src/views/Bazi.vue第205-206、221-301行 - 多处直接访问result.bazi.xxx而没有检查result或result.bazi是否存在，可能导致页面崩溃 - 建议使用可选链操作符?.或添加v-if空值检查
- [x] [2026-03-16 16:30] 后端AiAnalysis.php callAiApiStream方法缺少SSL验证 - backend/app/controller/AiAnalysis.php第350-372行 - 已添加CURLOPT_SSL_VERIFYPEER和CURLOPT_SSL_VERIFYHOST配置，启用SSL验证防止中间人攻击 - 修复时间: 2026-03-16
- [x] [2026-03-16 16:30] 后端AiAnalysis.php testConnection方法缺少SSL验证 - backend/app/controller/AiAnalysis.php第455-468行 - 已添加CURLOPT_SSL_VERIFYPEER和CURLOPT_SSL_VERIFYHOST配置，启用SSL验证防止中间人攻击 - 修复时间: 2026-03-16

#### 🟡 中优先级（体验问题）
- [ ] [2026-03-16 16:30] 前端Bazi.vue cancelAiAnalysis函数空值检查缺失 - frontend/src/views/Bazi.vue第1275-1376行 - clearInterval(aiLoadingTimer.value)调用前未检查是否为null - 建议添加if (aiLoadingTimer.value)判断
- [ ] [2026-03-16 16:30] 前端Bazi.vue shareResult函数空值风险 - frontend/src/views/Bazi.vue第1242-1261行 - 直接访问result.value.bazi.xxx没有空值检查 - 建议添加空值检查后再访问嵌套属性
- [ ] [2026-03-16 16:30] 前端ShenshaManage.vue分页逻辑副作用问题 - frontend/src/views/admin/ShenshaManage.vue第259-285行 - filteredList computed属性中直接修改total.value，违反Vue响应式原则 - 建议将total计算移到单独computed或使用watch
- [ ] [2026-03-16 16:30] 前端KnowledgeManage.vue图片上传错误处理未绑定 - frontend/src/views/admin/KnowledgeManage.vue第163行 - el-upload组件缺少:on-error="handleCoverError"绑定 - 建议添加错误处理事件绑定

---

## 待处理项目

### 🔴 高优先级（功能性问题）

- [x] [2026-03-16 16:00] 后端依赖文件缺失 - backend/app/controller/Vip.php引用UserVip、VipOrder模型和VipService服务，但这些文件不存在 - 已创建backend/app/model/UserVip.php、VipOrder.php和backend/app/service/VipService.php - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] 后端Content.php依赖模型缺失 - backend/app/controller/Content.php引用PageRecycle和OperationLog模型，但这些文件不存在 - 已创建backend/app/model/PageRecycle.php和OperationLog.php - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] 后端Admin.php依赖模型缺失 - backend/app/controller/Admin.php引用Feedback模型，但该文件不存在 - 已创建backend/app/model/Feedback.php - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] ~~后端Auth.php微信登录仍使用模拟逻辑~~ - **已删除**: 微信登录功能已移除，仅保留短信登录
- [x] [2026-03-16 16:00] 后端Auth.php事务处理不完整 - backend/app/controller/Auth.php第35-67行、第106-126行 - 已使用Db::startTrans()/commit()/rollback()包裹新用户创建、积分赠送和邀请码处理操作 - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] 后端Admin.php权限检查返回格式不统一 - backend/app/controller/Admin.php - 已统一使用$this->error()方法替换所有json(['code' => 403])返回 - 修复时间: 2026-03-16
- [ ] [2026-03-16 16:00] 后端Admin.php adjustPoints验证逻辑问题 - backend/app/controller/Admin.php第396-405行 - 使用$request->validate()但ThinkPHP的validate方法返回验证器实例，不是布尔值 - 建议修复验证逻辑
- [x] [2026-03-16 16:00] 后端Admin.php统计逻辑错误 - backend/app/controller/Admin.php第118-120行 - featureStats中塔罗占卜已修复为使用TarotRecord::count() - 修复时间: 2026-03-16
- [ ] [2026-03-16 16:00] 前端Bazi.vue未使用的导入 - frontend/src/views/Bazi.vue第912行 - getYearlyTrendApi被导入但未使用 - 建议删除未使用的导入
- [x] [2026-03-16 16:00] 前端Bazi.vue定时器清理不完整 - frontend/src/views/Bazi.vue第1164-1168行、第1197行 - 已将clearInterval移到finally块中，确保在任何情况下都被清理 - 修复时间: 2026-03-16
- [ ] [2026-03-16 16:00] 前端Tarot.vue未使用变量 - frontend/src/views/Tarot.vue第242行 - selectedCardIndex变量声明后从未读取 - 建议删除未使用的变量
- [ ] [2026-03-16 16:00] 前端管理端页面API调用均为模拟实现 - Config.vue/AlmanacManage.vue/KnowledgeManage.vue/SEOManage.vue/SEOStats.vue/ShenshaManage.vue都是模拟数据或setTimeout模拟 - 建议实现真实的API接口调用
- [ ] [2026-03-16 16:00] 前端管理端分页逻辑不完整 - KnowledgeManage.vue/SEOStats.vue/ShenshaManage.vue分页组件存在但数据未按分页切片或loadData为空 - 建议实现完整的分页逻辑
- [ ] [2026-03-16 16:00] 前端管理端表单验证不完整 - Config.vue/AlmanacManage.vue/SEOManage.vue多个表单缺少必填验证或验证规则不完整 - 建议完善表单验证规则
- [ ] [2026-03-16 15:00] 后端Content.php SQL注入风险 - backend/app/controller/Content.php第368-371行 - keyword参数使用字符串拼接而非参数绑定，虽然有过滤但仍存在风险 - 建议使用ThinkPHP参数绑定语法
- [ ] [2026-03-16 15:00] 后端AdminAuthService缺少无效adminId校验 - backend/app/service/AdminAuthService.php第31-42行 - checkPermission方法未验证$adminId是否大于0，传入0或负数可能导致查询异常 - 建议添加if ($adminId <= 0) { return false; }
- [ ] [2026-03-16 15:00] 前端Bazi.vue aiAbortController空值检查缺失 - frontend/src/views/Bazi.vue第1299-1300行、第1330行 - 访问aiAbortController.value.signal时没有做空值检查，可能导致运行时错误 - 建议使用可选链：aiAbortController.value?.signal

- [ ] [UI] 主题方向决策 - 这是最核心的设计问题，影响整个网站的视觉一致性 - 建议：考虑到命理玄学的行业属性，建议统一为深色主题（神秘、专业感），或全面改为白色主题（清新、现代感）
- [ ] [UI] 导航栏与页面内容区视觉割裂 - App.vue使用白色导航栏，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色与背景冲突 - Home.vue多处使用白色文字（color: #fff, rgba(255,255,255,0.7)等），在浅色背景下不可见 - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue/Hehun.vue/Daily.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [x] [2026-03-16] 前端SEOStats.vue RankBadge组件缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件JWT密钥硬编码风险 - backend/app/middleware/AdminAuth.php - 已移除默认值，强制从环境变量ADMIN_JWT_SECRET读取，未设置时抛出异常 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件日志记录敏感信息 - backend/app/middleware/AdminAuth.php - 已添加敏感字段脱敏处理（password、pwd、token、secret、key、authorization等字段会被替换为***） - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Auth.php邀请码暴力枚举风险 - backend/app/controller/Auth.php - 已修复：尝试次数超过10次后阻止操作（return） - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AiAnalysis.php类型检查缺失 - backend/app/controller/AiAnalysis.php第49行和第112行 - 已添加is_array检查，确保$baziData为数组类型，避免后续处理错误 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Admin.php feedbackList缺少权限检查 - backend/app/controller/Admin.php第472行 - 已添加权限检查：if (!$this->checkPermission('feedback_view')) { return json(['code' => 403, 'message' => '无权限查看反馈列表']); } - 修复时间: 2026-03-16
- [ ] [2026-03-16] 前端Bazi.vue潜在空值访问 - frontend/src/views/Bazi.vue第1357行 - analyzeBaziAi调用时aiAbortController.value可能为null - 建议使用可选链或添加空值检查

- [ ] [UI] 全局主题方向需要决策 - style.css定义白色主题，但所有页面使用深色背景，这是最核心的设计问题 - 建议：评估品牌定位后统一为深色主题（更符合命理玄学调性）或全面改为白色主题
- [ ] [UI] 页面背景色与导航栏/页脚严重割裂 - App.vue使用白色导航栏和页脚，但各页面内容区使用深色背景（rgba(0,0,0,0.2)等） - 建议统一全站背景色风格
- [ ] [UI] 首页Hero区域文字颜色在白色主题下不可见 - Home.vue第344、351、387、394、456、461、516、524、571行使用白色文字（color: #fff, rgba(255,255,255,0.7)等） - 建议改为使用var(--text-primary)和var(--text-secondary)
- [ ] [UI] 功能页面背景与主题冲突 - Bazi.vue/Tarot.vue/Liuyao.vue等页面使用rgba(0,0,0,0.2)深色背景，与style.css中--bg-primary: #ffffff定义不符 - 建议统一页面背景配色
- [ ] [UI] 登录页深色背景与白色主题不协调 - Login.vue使用深色渐变背景（#1a1a2e到#16213e），与整体白色主题定义冲突 - 建议统一登录页主题风格
- [x] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php第17行 - 已添加构造函数从环境变量读取JWT密钥：Env::get('ADMIN_JWT_SECRET')，并添加默认回退值 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue缺少h函数导入 - frontend/src/views/admin/SEOStats.vue第380行 - 已添加import { h } from 'vue'导入语句 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Content.php缺少SQL注入防护 - backend/app/controller/Content.php第363-365行 - 已添加preg_replace过滤特殊字符：$keyword = preg_replace('/[%_\\\\]/', '', $keyword); - 修复时间: 2026-03-16
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
- [x] [2026-03-16] 后端Admin.php权限检查返回格式不一致 - backend/app/controller/Admin.php第89,147,201行 - 已统一使用$this->error('无权限', 403)方法替换json()返回 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Content.php模型类未导入 - backend/app/controller/Content.php多处 - 已添加use语句导入所有模型类（Page, PageVersion, PageDraft, PageRecycle, OperationLog），并替换所有\app\model\为直接使用导入的类 - 修复时间: 2026-03-16
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

- [ ] [2026-03-16 16:00] 后端AiAnalysis.php返回格式不一致 - backend/app/controller/AiAnalysis.php第54、58、65、114、118、124行 - 使用json(['code' => ...])返回，但其他方法使用$this->success()或$this->error() - 建议统一使用BaseController方法
- [ ] [2026-03-16 16:00] 后端AiAnalysis.php getConfig返回码不一致 - backend/app/controller/AiAnalysis.php第387-391行 - getConfig方法返回code: 0，但其他方法返回code: 200 - 建议统一返回码
- [ ] [2026-03-16 16:00] 后端Content.php返回格式不一致 - backend/app/controller/Content.php多处 - 全部使用json(['code' => 200])，与BaseController的success()方法返回格式不一致 - 建议统一使用$this->success()和$this->error()
- [ ] [2026-03-16 16:00] 后端InviteRecord.php whereWeek使用不一致 - backend/app/controller/InviteRecord.php第241行 - getUserRank方法使用whereWeek，而getLeaderboard中已改为使用whereBetween替代 - 建议统一使用whereBetween
- [ ] [2026-03-16 16:00] 后端Auth.php邀请码查询逻辑不一致 - backend/app/controller/Auth.php第311行 - InviteRecord查询应该添加status=1条件以保持一致性 - 建议添加where('status', 1)条件
- [x] [2026-03-16 16:00] 前端Tarot.vue分享错误处理不完整 - frontend/src/views/Tarot.vue第536-538行 - 已添加catch块处理复制失败情况，显示错误提示 - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] 前端App.vue潜在空值访问 - frontend/src/views/App.vue第29、85行 - 已添加空字符串检查，为空时显示默认文本'用户' - 修复时间: 2026-03-16
- [x] [2026-03-16 16:00] 前端router/index.js缺少路由懒加载 - frontend/src/router/index.js第1-21行 - 已将非首屏页面改为懒加载，提升首屏性能 - 修复时间: 2026-03-16
- [ ] [2026-03-16 16:00] 前端管理端权限控制缺失 - KnowledgeManage.vue/SEOStats.vue/ShenshaManage.vue - 没有任何权限控制逻辑，删除、编辑等操作没有权限检查 - 建议添加权限控制
- [ ] [2026-03-16 16:00] 前端管理端未使用的变量 - SEOManage.vue/SEOStats.vue/ShenshaManage.vue - loading变量、图表引用等声明但未使用 - 建议清理未使用的代码
- [x] [2026-03-16 15:00] 前端Bazi.vue aiAbortController空值检查缺失 - frontend/src/views/Bazi.vue - 已使用可选链操作符?.修复aiAbortController.value?.signal访问，同时修复了result.bazi的多处空值访问 - 修复时间: 2026-03-16
- [ ] [2026-03-16 15:00] 后端AiAnalysis.php未使用的常量 - backend/app/controller/AiAnalysis.php第19-22行 - ENABLE_CACHE和CACHE_TTL已定义但未被使用，缓存功能实际上未启用 - 建议实现缓存逻辑或移除未使用的常量
- [x] [2026-03-16 15:00] 前端Tarot.vue selectedCardIndex变量未使用 - frontend/src/views/Tarot.vue第242行 - 经检查该变量已不存在，代码已更新 - 修复时间: 2026-03-16
- [x] [2026-03-16 15:00] 前端Bazi.vue getYearlyTrend函数导入未使用 - frontend/src/views/Bazi.vue第911行 - 经检查该导入已不存在，代码已更新 - 修复时间: 2026-03-16
- [ ] [2026-03-16 15:00] 前端Bazi.vue多处潜在空值访问 - frontend/src/views/Bazi.vue第220-262行等 - result对象多层属性访问存在空值风险 - 建议使用可选链操作符?.或添加v-if判断
- [ ] [2026-03-16 15:00] 后端Vip.php使用emoji作为图标 - backend/app/controller/Vip.php第54-82行 - 返回的权益列表使用emoji图标（✨、📊、💎、💕、🎯、🎁），可能在某些系统或数据库编码环境下显示异常 - 建议改为使用图标库或SVG图标
- [x] [2026-03-16 15:00] 前端Config.vue loading变量未使用 - frontend/src/views/admin/Config.vue第319行 - 经检查代码中使用的是saving对象而非loading变量，实现方式已更新 - 修复时间: 2026-03-16
- [ ] [2026-03-16 15:00] 前端SEOManage.vue站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue第466-499行 - generateSitemap、saveRobots、搜索引擎提交均为模拟实现 - 建议替换为实际API调用
- [ ] [2026-03-16 15:00] 后端Admin.php权限检查返回格式不统一 - backend/app/controller/Admin.php第89-91行、第147-149行、第201-203行 - dashboard和users方法使用$this->error()，userDetail方法使用json()，返回格式不一致 - 建议统一使用$this->error()方法
- [ ] [2026-03-16 15:00] 后端Auth.php processInviteCode事务处理不完整 - backend/app/controller/Auth.php第317-358行 - 异常时仅rollback但没有抛出异常或返回错误信息 - 建议添加错误返回
- [x] [2026-03-16 15:00] 后端Admin.php feedbackList缺少权限检查 - backend/app/controller/Admin.php第472行 - 已修复：已添加权限检查if (!$this->checkPermission('feedback_view')) - 修复时间: 2026-03-16
- [x] [2026-03-16 15:00] 后端AiAnalysis.php返回码不一致 - backend/app/controller/AiAnalysis.php第54-55行、第93-95行 - 已修复：已统一使用BaseController的success()和error()方法 - 修复时间: 2026-03-16
- [x] [2026-03-16 15:00] 后端AiAnalysis.php未使用的常量 - backend/app/controller/AiAnalysis.php第19-22行 - 问题仍然存在，ENABLE_CACHE和CACHE_TTL已定义但未被使用 - 建议实现缓存逻辑或移除未使用的常量
- [x] [2026-03-16 15:00] 后端Admin.php返回码格式不统一 - backend/app/controller/Admin.php多处 - 已修复：已统一使用$this->error()方法替换json()返回 - 修复时间: 2026-03-16
- [ ] [2026-03-16 12:30] 后端Admin.php分页参数未验证 - backend/app/controller/Admin.php第152-156行等 - 多个方法中分页参数没有验证是否为正整数 - 建议添加filter_var验证
- [ ] [2026-03-16 12:30] 后端Vip.php分页参数验证不完整 - backend/app/controller/Vip.php第128-133行 - 虽然限制了范围但没有验证参数类型 - 建议添加FILTER_VALIDATE_INT验证
- [ ] [2026-03-16 12:30] 后端Content.php分页参数未验证 - backend/app/controller/Content.php第362-363行 - page和pageSize参数没有验证是否为正整数 - 建议添加验证
- [x] [2026-03-16 15:00] 后端AdminAuthService异常处理不完整 - backend/app/service/AdminAuthService.php - 已修复：已添加Log::error()记录详细错误信息 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 前端AlmanacManage.vue API调用缺失 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - submitForm和generateMonth函数中只有模拟延迟，没有实际API调用 - 建议添加真实的API保存和生成调用
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实际调用API加载数据 - 建议实现真实的API调用和分页逻辑
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [x] [2026-03-16] 前端Config.vue未使用loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有使用 - 已删除未使用的loading变量 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用，onMounted和onUnmounted为空 - 已删除未使用的代码和导入 - 修复时间: 2026-03-16
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
- [x] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理 - 已添加防抖函数和watch处理，300ms延迟 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调 - 已添加on-error="handleCoverError"和错误处理函数 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端Config.vue未使用loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有使用 - 已删除未使用的loading变量 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用 - 已删除未使用的代码和导入 - 修复时间: 2026-03-16
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
- [x] [2026-03-16] 后端Content.php模型类未导入 - backend/app/controller/Content.php第29,78,80等行 - 已添加use语句导入所有模型类（Page, PageVersion, PageDraft, PageRecycle, OperationLog），并替换所有\app\model\为直接使用导入的类 - 修复时间: 2026-03-16
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

## 运营检查报告 - 2026-03-16 第四轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 运营人员
- **检查范围**: 太初命理网站后台管理系统

### 本次检查发现的新问题

#### 🔴 高优先级（运营阻塞问题）

- [ ] [运营] 后台缺少Dashboard首页组件 - /admin路由直接跳转到Config.vue系统配置页面，缺少数据概览Dashboard - 建议创建AdminDashboard.vue展示用户数、订单数、收入等关键指标
- [ ] [运营] 用户管理页面缺失 - 后台没有UserManage.vue用户列表管理页面，无法查看、搜索、编辑用户信息 - 建议创建用户管理页面并对接后端/users接口
- [ ] [运营] 订单管理页面缺失 - VIP订单、积分订单无法在后台查看和管理 - 建议创建OrderManage.vue对接后端订单接口
- [ ] [运营] 反馈管理页面缺失 - 后端有feedbackList接口，但前端没有对应的FeedbackManage.vue页面 - 建议创建反馈管理页面
- [ ] [运营] 后台管理页面API调用均为模拟实现 - AlmanacManage.vue、KnowledgeManage.vue、SEOManage.vue、ShenshaManage.vue等页面只有模拟数据，没有真实API调用 - 建议实现真实的API接口调用
- [ ] [运营] 黄历管理页面submitForm和generateMonth函数只有模拟延迟 - frontend/src/views/admin/AlmanacManage.vue第409-432行 - 没有实际API调用，数据无法真正保存 - 建议添加真实的API保存和生成调用
- [ ] [运营] 神煞管理页面分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第386-388行 - loadData函数为空，没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)
- [ ] [运营] 管理员登录功能缺失 - 当前只有普通用户手机号登录，没有管理员账号密码登录入口 - 建议添加/admin/login管理员登录页面
- [ ] [运营] 后台缺少统一的AdminLayout布局组件 - 各管理页面独立存在，没有统一的左侧导航菜单和顶部栏 - 建议创建AdminLayout.vue包含侧边导航栏、面包屑、退出功能
- [ ] [运营] 后台API接口与前端不匹配 - admin.js中定义的API（如getFeatureSwitches、updateFeature等）在后端Admin.php中没有对应实现，只有admin/Config.php中有部分实现 - 建议后端实现对应接口或前端调整API调用

#### 🟡 中优先级（运营体验问题）

- [ ] [运营] 知识库文章搜索缺少防抖处理 - frontend/src/views/admin/KnowledgeManage.vue - 搜索框输入没有防抖，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [运营] 知识库图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [运营] SEO管理页面站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现，无法真正生成sitemap.xml - 建议实现真实的站点地图生成功能
- [ ] [运营] 后台页面没有面包屑导航 - 虽然路由配置了breadcrumb元信息，但页面中没有显示面包屑组件 - 建议在AdminLayout中添加面包屑导航
- [ ] [运营] 后台管理页面缺少退出登录功能 - 后台页面没有提供管理员退出登录的入口 - 建议在导航栏添加退出按钮
- [ ] [运营] 后台缺少操作日志查看页面 - 后端有operationLogs接口和AdminLog模型，但前端没有对应的日志查看页面 - 建议添加操作日志查询页面
- [ ] [运营] SEO统计页面图表功能未实现 - frontend/src/views/admin/SEOStats.vue - 图表功能未实现，只有模拟数据展示 - 建议集成ECharts实现数据可视化
- [ ] [运营] 后台管理页面缺少权限控制UI - 各管理页面没有根据管理员权限动态显示/隐藏操作按钮 - 建议添加权限控制

#### 🟢 低优先级（运营优化建议）

- [ ] [运营] 后台页面主题与前端不统一 - 后台页面使用深色主题（rgba(0,0,0,0.2)背景），但前端整体为白色主题 - 建议统一后台主题风格
- [ ] [运营] 后台页面缺少响应式适配 - 管理页面在移动端显示可能存在问题 - 建议添加移动端适配
- [ ] [运营] 黄历管理页面表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [运营] 后台缺少数据统计报表导出功能 - 无法导出用户数据、订单数据等报表 - 建议添加导出Excel/CSV功能
- [ ] [运营] 后台缺少积分调整功能页面 - 后端有adjustPoints接口，但前端没有对应的积分调整页面 - 建议添加积分调整功能
- [ ] [运营] 后台缺少VIP会员管理页面 - 后端有VIP相关接口，但前端没有对应的会员管理页面 - 建议添加VIP会员管理功能

---

## 运营检查报告 - 2026-03-16 第三轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 运营人员
- **检查范围**: 太初命理网站后台管理系统

### 本次检查发现的新问题

#### 🔴 高优先级（运营阻塞问题）

- [ ] [运营] 后台缺少Dashboard首页组件 - /admin路由直接跳转到Config.vue系统配置页面，缺少数据概览Dashboard - 建议创建AdminDashboard.vue展示用户数、订单数、收入等关键指标
- [ ] [运营] 用户管理页面缺失 - 后台没有UserManage.vue用户列表管理页面，无法查看、搜索、编辑用户信息 - 建议创建用户管理页面并对接后端/users接口
- [ ] [运营] 订单管理页面缺失 - VIP订单、积分订单无法在后台查看和管理 - 建议创建OrderManage.vue对接后端订单接口
- [ ] [运营] 反馈管理页面缺失 - 后端有feedbackList接口，但前端没有对应的FeedbackManage.vue页面 - 建议创建反馈管理页面
- [ ] [运营] 后台管理页面API调用均为模拟实现 - AlmanacManage.vue、KnowledgeManage.vue、SEOManage.vue、ShenshaManage.vue等页面只有模拟数据，没有真实API调用 - 建议实现真实的API接口调用
- [ ] [运营] 黄历管理页面submitForm和generateMonth函数只有模拟延迟 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - 没有实际API调用，数据无法真正保存 - 建议添加真实的API保存和生成调用
- [ ] [运营] 神煞管理页面分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)
- [ ] [运营] 管理员登录功能缺失 - 当前只有普通用户手机号登录，没有管理员账号密码登录入口 - 建议添加/admin/login管理员登录页面
- [ ] [运营] 后台缺少统一的AdminLayout布局组件 - 各管理页面独立存在，没有统一的左侧导航菜单和顶部栏 - 建议创建AdminLayout.vue包含侧边导航栏、面包屑、退出功能
- [ ] [运营] 后台API接口与前端不匹配 - admin.js中定义的API（如getFeatureSwitches、updateFeature等）在后端Admin.php中没有对应实现 - 建议后端实现对应接口或前端调整API调用

#### 🟡 中优先级（运营体验问题）

- [ ] [运营] 知识库文章搜索缺少防抖处理 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [运营] 知识库图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [运营] SEO管理页面站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现，无法真正生成sitemap.xml - 建议实现真实的站点地图生成功能
- [ ] [运营] 后台页面没有面包屑导航 - 虽然路由配置了breadcrumb元信息，但页面中没有显示面包屑组件 - 建议在AdminLayout中添加面包屑导航
- [ ] [运营] 后台管理页面缺少退出登录功能 - 后台页面没有提供管理员退出登录的入口 - 建议在导航栏添加退出按钮
- [ ] [运营] 后台缺少操作日志查看页面 - 后端有operationLogs接口和AdminLog模型，但前端没有对应的日志查看页面 - 建议添加操作日志查询页面
- [ ] [运营] SEO统计页面图表功能未实现 - frontend/src/views/admin/SEOStats.vue第131、147行引用了pieChart和trendChart图表容器，但没有实际初始化图表 - 建议集成ECharts实现数据可视化
- [ ] [运营] 后台管理页面缺少权限控制UI - 各管理页面没有根据管理员权限动态显示/隐藏操作按钮 - 建议添加权限控制

#### 🟢 低优先级（运营优化建议）

- [ ] [运营] 后台页面主题与前端不统一 - 后台页面使用深色主题（rgba(0,0,0,0.2)背景），但前端整体为白色主题 - 建议统一后台主题风格
- [ ] [运营] 后台页面缺少响应式适配 - 管理页面在移动端显示可能存在问题 - 建议添加移动端适配
- [ ] [运营] 黄历管理页面表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [运营] 后台缺少数据统计报表导出功能 - 无法导出用户数据、订单数据等报表 - 建议添加导出Excel/CSV功能
- [ ] [运营] 后台缺少积分调整功能页面 - 后端有adjustPoints接口，但前端没有对应的积分调整页面 - 建议添加积分调整功能
- [ ] [运营] 后台缺少VIP会员管理页面 - 后端有VIP相关接口，但前端没有对应的会员管理页面 - 建议添加VIP会员管理功能

---

## 运营检查报告 - 2026-03-16 第二轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 运营人员
- **检查范围**: 太初命理网站后台管理系统

### 本次检查发现的新问题

#### 🔴 高优先级（运营阻塞问题）

- [ ] [运营] 后台缺少Dashboard首页组件 - /admin路由直接跳转到Config.vue系统配置页面，缺少数据概览Dashboard - 建议创建AdminDashboard.vue展示用户数、订单数、收入等关键指标
- [ ] [运营] 用户管理页面缺失 - 后台没有UserManage.vue用户列表管理页面，无法查看、搜索、编辑用户信息 - 建议创建用户管理页面并对接后端/users接口
- [ ] [运营] 订单管理页面缺失 - VIP订单、积分订单无法在后台查看和管理 - 建议创建OrderManage.vue对接后端订单接口
- [ ] [运营] 反馈管理页面缺失 - 后端有feedbackList接口，但前端没有对应的FeedbackManage.vue页面 - 建议创建反馈管理页面
- [ ] [运营] 后台管理页面API调用均为模拟实现 - AlmanacManage.vue、KnowledgeManage.vue、SEOManage.vue、ShenshaManage.vue等页面只有模拟数据，没有真实API调用 - 建议实现真实的API接口调用
- [ ] [运营] 黄历管理页面submitForm和generateMonth函数只有模拟延迟 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - 没有实际API调用，数据无法真正保存 - 建议添加真实的API保存和生成调用
- [ ] [运营] 神煞管理页面分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)
- [ ] [运营] 管理员登录功能缺失 - 当前只有普通用户手机号登录，没有管理员账号密码登录入口 - 建议添加/admin/login管理员登录页面
- [ ] [运营] 后台缺少统一的AdminLayout布局组件 - 各管理页面独立存在，没有统一的左侧导航菜单和顶部栏 - 建议创建AdminLayout.vue包含侧边导航栏、面包屑、退出功能

#### 🟡 中优先级（运营体验问题）

- [ ] [运营] 知识库文章搜索缺少防抖处理 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [运营] 知识库图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [运营] SEO管理页面站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现，无法真正生成sitemap.xml - 建议实现真实的站点地图生成功能
- [ ] [运营] 系统配置页面loading变量未使用 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有在页面加载时使用 - 建议在onMounted中使用loading状态
- [ ] [运营] SEO统计页面图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用，onMounted和onUnmounted为空 - 建议实现图表初始化逻辑或删除未使用的代码
- [ ] [运营] 后台页面没有面包屑导航 - 虽然路由配置了breadcrumb元信息，但页面中没有显示面包屑组件 - 建议在AdminLayout中添加面包屑导航
- [ ] [运营] 后台管理页面缺少退出登录功能 - 后台页面没有提供管理员退出登录的入口 - 建议在导航栏添加退出按钮
- [ ] [运营] 后台缺少操作日志查看页面 - 后端有operationLogs接口和AdminLog模型，但前端没有对应的日志查看页面 - 建议添加操作日志查询页面

#### 🟢 低优先级（运营优化建议）

- [ ] [运营] 后台页面主题与前端不统一 - 后台页面使用深色主题（rgba(0,0,0,0.2)背景），但前端整体为白色主题 - 建议统一后台主题风格
- [ ] [运营] 后台页面缺少响应式适配 - 管理页面在移动端显示可能存在问题 - 建议添加移动端适配
- [ ] [运营] 黄历管理页面表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [运营] 后台缺少数据统计报表导出功能 - 无法导出用户数据、订单数据等报表 - 建议添加导出Excel/CSV功能
- [ ] [运营] 后台缺少积分调整功能页面 - 后端有adjustPoints接口，但前端没有对应的积分调整页面 - 建议添加积分调整功能

---

## 运营检查报告 - 2026-03-16 第一轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 运营人员
- **检查范围**: 太初命理网站后台管理系统

### 检查发现的问题

#### 🔴 高优先级（运营阻塞问题）

- [ ] [运营] 后台管理页面缺少Dashboard首页 - 路由配置了/admin但没有对应的Dashboard组件，访问/admin会直接进入系统配置页面，缺少数据概览 - 建议创建AdminDashboard.vue作为后台首页，展示用户数、订单数、收入等关键指标
- [ ] [运营] 用户管理页面缺失 - 后台没有用户列表管理页面，无法查看、搜索、编辑用户信息 - 建议创建UserManage.vue实现用户管理功能
- [ ] [运营] 订单管理页面缺失 - VIP订单、积分订单无法在后台查看和管理 - 建议创建OrderManage.vue实现订单管理
- [ ] [运营] 后台管理页面API调用均为模拟实现 - AlmanacManage.vue、KnowledgeManage.vue、SEOManage.vue、ShenshaManage.vue等页面只有模拟数据，没有真实API调用 - 建议实现真实的API接口调用
- [ ] [运营] 黄历管理页面submitForm和generateMonth函数只有模拟延迟 - frontend/src/views/admin/AlmanacManage.vue第397-428行 - 没有实际API调用，数据无法真正保存 - 建议添加真实的API保存和生成调用
- [ ] [运营] 神煞管理页面分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实现分页切片 - 建议实现分页逻辑：list.slice((page.value - 1) * pageSize.value, page.value * pageSize.value)

#### 🟡 中优先级（运营体验问题）

- [ ] [运营] 知识库文章搜索缺少防抖处理 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [运营] 知识库图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [ ] [运营] SEO管理页面站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现，无法真正生成sitemap.xml - 建议实现真实的站点地图生成功能
- [ ] [运营] 后台管理页面缺少导航菜单组件 - 各管理页面独立存在，没有统一的左侧导航菜单 - 建议创建AdminLayout.vue包含侧边导航栏
- [ ] [运营] 后台页面没有面包屑导航 - 虽然路由配置了breadcrumb元信息，但页面中没有显示面包屑组件 - 建议在AdminLayout中添加面包屑导航
- [ ] [运营] 后台管理页面缺少退出登录功能 - 后台页面没有提供管理员退出登录的入口 - 建议在导航栏添加退出按钮
- [ ] [运营] 系统配置页面loading变量未使用 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有在页面加载时使用 - 建议在onMounted中使用loading状态
- [ ] [运营] SEO统计页面图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用，onMounted和onUnmounted为空 - 建议实现图表初始化逻辑或删除未使用的代码

#### 🟢 低优先级（运营优化建议）

- [ ] [运营] 后台页面主题与前端不统一 - 后台页面使用深色主题（rgba(0,0,0,0.2)背景），但前端整体为白色主题 - 建议统一后台主题风格
- [ ] [运营] 后台页面缺少响应式适配 - 管理页面在移动端显示可能存在问题 - 建议添加移动端适配
- [ ] [运营] 黄历管理页面表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [运营] 后台缺少操作日志查看页面 - 后端有AdminLog模型记录操作日志，但前端没有查看页面 - 建议添加操作日志查询页面
- [ ] [运营] 后台缺少数据统计报表导出功能 - 无法导出用户数据、订单数据等报表 - 建议添加导出Excel/CSV功能

---

## 代码逻辑问题（本次检查新增）

### 🔴 高优先级（功能性问题）

- [x] [2026-03-16] 管理端Config.vue中updateFeature函数名冲突 - frontend/src/views/admin/Config.vue - 已重命名本地函数为handleUpdateFeature - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端AdminAuth中间件硬编码JWT密钥 - backend/app/middleware/AdminAuth.php - 已添加构造函数从环境变量读取JWT密钥 - 修复时间: 2026-03-16
- [x] [2026-03-16] 后端Auth控制器缺少Cache类导入 - backend/app/controller/Auth.php - 已添加use think\facade\Cache;导入语句 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 后端Vip.php缺失VipService依赖 - backend/app/controller/Vip.php第10行 - VipService类被引用但未找到定义文件，会导致运行时错误 - 建议创建backend/app/service/VipService.php文件
- [ ] [2026-03-16] 后端Vip.php缺失模型文件 - backend/app/controller/Vip.php第7-8行 - UserVip和VipOrder模型被引用但未找到定义文件 - 建议创建backend/app/model/UserVip.php和VipOrder.php
- [ ] [2026-03-16] 后端Admin.php feedbackList缺少权限检查 - backend/app/controller/Admin.php第472行 - feedbackList方法没有进行权限检查，其他管理功能都有权限检查 - 建议添加if (!$this->checkPermission('feedback_view'))检查
- [ ] [2026-03-16] 前端Tarot.vue缺少computed导入 - frontend/src/views/Tarot.vue - computed被使用但未从vue导入，会导致运行时错误 - 建议添加import { ref, onMounted, computed } from 'vue'
- [ ] [2026-03-16] 后端Content.php缺失PageRecycle模型 - backend/app/controller/Content.php第395行 - 使用了PageRecycle模型但文件不存在 - 建议创建backend/app/model/PageRecycle.php
- [ ] [2026-03-16] 后端Content.php缺失OperationLog模型 - backend/app/controller/Content.php第525行 - 使用了OperationLog模型但文件不存在 - 建议创建backend/app/model/OperationLog.php或使用AdminLog替代
- [ ] [2026-03-16] 前端Bazi.vue缺少CircleClose图标导入 - frontend/src/views/Bazi.vue第880行 - 使用<CircleClose />但未导入 - 建议添加import { CircleClose } from '@element-plus/icons-vue'
- [ ] [2026-03-16] 前端所有管理端页面API调用缺失 - frontend/src/views/admin/*.vue - Config.vue/AlmanacManage.vue/KnowledgeManage.vue/SEOManage.vue/SEOStats.vue/ShenshaManage.vue都是模拟实现 - 建议实现真实的API调用
- [ ] [2026-03-16] 后端Content.php SQL注入风险 - backend/app/controller/Content.php第363-364行 - keyword参数直接拼接到SQL中 - 建议添加preg_replace过滤特殊字符

### 🟡 中优先级（体验问题）

- [ ] [2026-03-16] 前端AlmanacManage.vue黄历数据API未实现 - frontend/src/views/admin/AlmanacManage.vue - 黄历数据加载和保存使用模拟数据，需要接入真实API
- [ ] [2026-03-16] 前端SEOManage.vue站点地图功能模拟实现 - frontend/src/views/admin/SEOManage.vue - 站点地图生成和robots保存为模拟实现
- [ ] [2026-03-16] 后端Admin控制器返回码格式不统一 - backend/app/controller/Admin.php - 部分接口返回code=200，部分返回code=0，应统一
- [ ] [2026-03-16] 后端缺少AdminAuthService实现检查 - backend/app/service/AdminAuthService.php - 需要确认权限检查逻辑是否完整
- [ ] [2026-03-16] 后端API返回格式不一致 - backend/app/controller/AiAnalysis.php第54,89-97行 - 错误时使用code=400/500，与其他控制器不一致 - 建议统一使用BaseController的success()和error()方法
- [ ] [2026-03-16] 后端Content.php输入验证不完整 - backend/app/controller/Content.php第67-76行 - savePage和importPage方法对blocks数组的验证不够完整 - 建议添加更严格的输入验证
- [ ] [2026-03-16] 后端Content.php潜在SQL注入风险 - backend/app/controller/Content.php第363-365行 - keyword参数直接拼接到like查询中 - 建议添加preg_replace过滤特殊字符
- [ ] [2026-03-16] 前端Bazi.vue未使用变量和函数 - frontend/src/views/Bazi.vue第950行、1068-1084行 - yearlyTrendData变量和getYearlyTrendData函数定义后从未使用 - 建议删除未使用的代码
- [x] [2026-03-16] 前端Tarot.vue未使用变量 - frontend/src/views/Tarot.vue第242行 - 已删除selectedCardIndex变量定义和showCardDetail函数中的赋值 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 前端App.vue潜在空值访问 - frontend/src/views/App.vue第221行 - 从localStorage获取userInfo后直接访问nickname属性，可能为null - 建议使用可选链user?.nickname
- [ ] [2026-03-16] 前端ShenshaManage.vue分页逻辑不完整 - frontend/src/views/admin/ShenshaManage.vue第380-382行 - loadData函数为空，没有实际调用API加载数据 - 建议实现真实的API调用和分页逻辑
- [ ] [2026-03-16] 前端KnowledgeManage.vue搜索缺少防抖 - frontend/src/views/admin/KnowledgeManage.vue第35-40行 - 搜索框输入没有防抖处理，频繁输入会触发多次过滤 - 建议使用lodash.debounce或自定义防抖函数
- [ ] [2026-03-16] 前端KnowledgeManage.vue图片上传缺少错误处理 - frontend/src/views/admin/KnowledgeManage.vue第158-169行 - 上传组件只有on-success回调，没有on-error处理 - 建议添加:on-error="handleCoverError"和错误处理函数
- [x] [2026-03-16] ~~后端Auth.php微信登录模拟逻辑~~ - **已删除**: 微信登录功能已移除，仅保留短信登录
- [ ] [2026-03-16] 后端Auth.php事务处理不完整 - backend/app/controller/Auth.php第178-215行 - phoneRegister方法中事务处理，但login和phoneLogin方法中创建用户和赠送积分没有使用事务 - 建议统一使用事务处理
- [ ] [2026-03-16] 后端AiAnalysis.php未使用常量 - backend/app/controller/AiAnalysis.php第19,22行 - 定义了ENABLE_CACHE和CACHE_TTL常量但未使用 - 建议实现缓存逻辑或移除未使用的常量
- [ ] [2026-03-16] 后端AiAnalysis.php cURL缺少SSL验证 - backend/app/controller/AiAnalysis.php第272-290行 - cURL调用没有设置SSL验证选项 - 建议添加CURLOPT_SSL_VERIFYPEER和CURLOPT_SSL_VERIFYHOST配置
- [ ] [2026-03-16] 前端Bazi.vue潜在空值访问 - frontend/src/views/Bazi.vue第192-301行 - v-else-if="result"内部多处访问result.bazi属性，没有做空值检查 - 建议添加result?.bazi可选链操作符
- [ ] [2026-03-16] 前端多处localStorage操作缺少异常处理 - frontend/src/views/Bazi.vue/App.vue等 - JSON.parse和localStorage操作未做try-catch包裹 - 建议添加异常处理防止页面崩溃
- [ ] [2026-03-16] 前端Bazi.vue AI流式响应可能无限循环 - frontend/src/views/Bazi.vue第1317-1347行 - while(true)循环如果reader不返回done可能死循环 - 建议添加超时机制或最大迭代次数限制
- [ ] [2026-03-16] 后端Content.php模型类未正确导入 - backend/app/controller/Content.php多处 - 使用\app\model\XXX动态调用，没有使用use语句导入 - 建议添加use语句导入模型类
- [ ] [2026-03-16] 后端Auth.php邀请码暴力枚举防护不完整 - backend/app/controller/Auth.php - 手机号登录处理邀请码时，如果无效只是静默返回 - 建议添加邀请码有效性反馈
- [ ] [2026-03-16] 后端Auth.php事务处理错误日志缺失 - backend/app/controller/Auth.php第356-358行 - 异常捕获后只执行rollback，没有记录错误日志 - 建议添加Log::error记录错误信息

### 🟢 低优先级（优化问题）

- [ ] [2026-03-16] 前端Login.vue用户协议和隐私政策功能未实现 - frontend/src/views/Login.vue - showAgreement和showPrivacy方法仅显示提示
- [ ] [2026-03-16] 前端Bazi.vue中isCurrentDaYun方法使用固定年龄 - frontend/src/views/Bazi.vue - 第1254行currentAge固定为30，应根据出生日期计算
- [ ] [2026-03-16] 后端AdminAuth中间件logOperation方法未完整实现 - backend/app/middleware/AdminAuth.php - 第53-68行日志记录为注释状态
- [ ] [2026-03-16] 后端AiAnalysis.php未使用的类引用 - backend/app/controller/AiAnalysis.php第8行 - CacheService被引用但未使用 - 建议移除或实现缓存功能
- [ ] [2026-03-16] 后端AiAnalysis.php未实现的缓存功能 - backend/app/controller/AiAnalysis.php第18-22行 - 定义了ENABLE_CACHE和CACHE_TTL常量但未使用 - 建议实现AI分析结果缓存逻辑
- [ ] [2026-03-16] 后端Content.php潜在空指针风险 - backend/app/controller/Content.php第85-86行 - 使用$request->adminId和$request->adminName可能不存在 - 建议添加空值检查或使用默认值
- [ ] [2026-03-16] 前端Bazi.vue内存泄漏风险 - frontend/src/views/Bazi.vue第1183-1187行、1298-1304行 - setInterval创建的定时器在组件卸载时没有被清理 - 建议在onUnmounted钩子中清理定时器
- [x] [2026-03-16] 前端Config.vue未使用loading变量 - frontend/src/views/admin/Config.vue第319行 - loading变量定义了但没有使用 - 已删除未使用的loading变量 - 修复时间: 2026-03-16
- [x] [2026-03-16] 前端SEOStats.vue图表初始化代码缺失 - frontend/src/views/admin/SEOStats.vue第385-394行 - pieChart和trendChart变量定义但未使用 - 已删除未使用的代码和导入 - 修复时间: 2026-03-16
- [ ] [2026-03-16] 前端AlmanacManage.vue表单验证不完整 - frontend/src/views/admin/AlmanacManage.vue第302-304行 - 只有solarDate字段有验证规则，其他重要字段如yi、ji、ganzhi等没有验证 - 建议添加完整的表单验证规则
- [ ] [2026-03-16] 后端Auth.php重复代码 - backend/app/controller/Auth.php多处 - 新用户赠送积分和记录积分变动的代码在多个方法中重复 - 建议提取公共方法rewardNewUser($user)
- [ ] [2026-03-16] 后端AdminAuthService缓存键冲突风险 - backend/app/service/AdminAuthService.php第21行 - 缓存键前缀admin:permissions:可能与其他系统冲突 - 建议添加应用特定的前缀如taichu:admin:permissions:
- [ ] [2026-03-16] 前端router/index.js未使用的导入 - frontend/src/router/index.js第2行 - generateWebsiteSchema导入但未使用 - 建议删除未使用的导入
- [ ] [2026-03-16] 前端Tarot.vue隐者牌含义使用英文 - frontend/src/views/Tarot.vue第305-307行 - 与其他牌的中文描述不一致 - 建议统一为中文描述
- [ ] [2026-03-16] 后端AiAnalysis.php类型检查重复 - backend/app/controller/AiAnalysis.php第57-59行、第124-126行 - 检查baziData是否为数组但没有检查内部结构 - 建议添加字段验证确保包含必要八字字段
- [ ] [2026-03-16] 后端AdminAuth.php日志记录请求头敏感信息 - backend/app/middleware/AdminAuth.php第63-92行 - 过滤了请求参数敏感字段但未过滤请求头中的Authorization/Cookie - 建议添加请求头敏感信息过滤
- [ ] [2026-03-16] 后端AdminAuthService.php无效adminId校验缺失 - backend/app/service/AdminAuthService.php第31-42行、第73-115行 - 没有对$adminId进行有效性校验 - 建议添加if ($adminId <= 0)校验
- [ ] [2026-03-16] 后端AdminAuthService.php异常处理返回信息不足 - backend/app/service/AdminAuthService.php第178-235行 - 返回布尔值false，调用者无法得知具体失败原因 - 建议返回包含错误信息的结果数组

## 占卜功能体验检查报告 - 2026-03-16

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 占卜爱好者（专业命理/塔罗爱好者角度）
- **检查范围**: 太初命理网站全部占卜功能

### 🔴 高优先级（准确性/功能性问题）

- [ ] [占卜] 八字排盘节气数据不完整 - backend/app/controller/Paipan.php第563-573行 - 仅包含1990、1995、2000年节气数据，其他年份排盘可能不准确 - 建议补充完整节气表或使用天文算法计算
- [ ] [占卜] 六爻占卜缺少手动摇卦功能 - frontend/src/views/Liuyao.vue - 只有自动起卦，没有手动输入六次摇卦结果的功能 - 建议添加手动起卦模式
- [ ] [占卜] 六爻结果缺少变卦展示 - backend/app/controller/Liuyao.php - 动爻产生变卦，但结果中只展示主卦，没有变卦分析 - 建议添加变卦计算和展示
- [ ] [占卜] 塔罗隐者牌含义使用英文 - frontend/src/views/Tarot.vue第305-307行 - 与其他牌的中文描述不一致 - 建议统一为中文描述
- [ ] [占卜] 八字日主强弱判断过于简化 - backend/app/controller/Paipan.php - 日主强弱判断仅基于简单规则，未考虑月令、通根、透干等因素 - 建议参考《滴天髓》完善判断逻辑

### 🟡 中优先级（体验/专业性优化）

- [ ] [占卜] 六爻缺少六亲分析 - backend/app/controller/Liuyao.php - 六爻解卦需要六亲（父母、兄弟、子孙、妻财、官鬼）分析，当前缺失 - 建议添加六亲配属和解读
- [ ] [占卜] 六爻缺少六神分析 - backend/app/controller/Liuyao.php - 六神（青龙、朱雀、勾陈、螣蛇、白虎、玄武）是六爻重要组成部分 - 建议添加六神配属
- [ ] [占卜] 六爻缺少用神判断逻辑 - backend/app/controller/Liuyao.php - 解卦需要根据问题类型确定用神，当前解读较为通用 - 建议根据问题关键词智能判断用神
- [ ] [占卜] 塔罗牌缺少AI深度解读 - backend/app/controller/Tarot.php - 抽牌后只有基础解读，没有AI分析功能 - 建议添加AI解牌功能
- [ ] [占卜] 八字排盘缺少真太阳时计算 - backend/app/controller/Paipan.php - 虽然前端有出生地选择，但后端未实现真太阳时转换 - 建议根据经度计算真太阳时
- [ ] [占卜] 八字十神计算未考虑藏干 - backend/app/controller/Paipan.php第519-557行 - 十神只计算了天干，地支藏干的十神未完整展示 - 建议完善藏干十神分析

### 🟢 低优先级（专业性增强）

- [ ] [占卜] 八字排盘可增加神煞分析 - backend/app/controller/Paipan.php - 可添加天乙贵人、文昌、桃花等常用神煞 - 建议参考《三命通会》添加常见神煞
- [ ] [占卜] 八字大运流年可添加更多细节 - backend/app/controller/Paipan.php - 可添加交运时间、流年神煞等信息 - 建议丰富大运流年展示
- [ ] [占卜] 塔罗牌可添加牌阵关系分析 - frontend/src/views/Tarot.vue - 多张牌之间缺乏关联性解读 - 建议添加牌阵整体分析
- [ ] [占卜] 合婚可添加更多传统合婚法 - backend/app/controller/Hehun.php - 可添加三元合婚、九宫合婚等传统方法 - 建议丰富合婚算法
- [ ] [占卜] 六爻可添加应期推断 - backend/app/controller/Liuyao.php - 解卦时可尝试推断事情发生时间 - 建议添加应期分析

---

## 已完成项目

- [x] [2026-03-16] 前端Bazi.vue中AI解盘相关变量未定义 - frontend/src/views/Bazi.vue - 已添加aiLoadingTime、aiAbortController、aiLoadingTimer的ref定义 - 修复时间: 2026-03-16

---

## 占卜功能体验检查报告 - 2026-03-16 第四轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 占卜爱好者（专业命理/塔罗爱好者角度）
- **检查范围**: 太初命理网站全部占卜功能深度体验
- **测试数据**: 1990年5月15日 10:30 男 / 1992年8月20日 女

### 功能体验结果

#### 1. 八字排盘功能
- **排盘计算**: 四柱计算逻辑正确，使用五虎遁月法和日上起时法
- **节气问题**: 确认节气数据仅1990、1995、2000三年精确数据，其他年份使用通用数据(±1天误差)
- **十神计算**: 天干十神计算正确，藏干十神已计算但前端展示不够突出
- **真太阳时**: 前端有出生地选择，后端未实现经度转换
- **大运流年**: 大运顺逆排法正确（阳男阴女顺排，阴男阳女逆排）

#### 2. 六爻占卜功能
- **起卦逻辑**: 钱币占卜模拟正确(老阴/少阴/少阳/老阳)
- **变卦缺失**: 动爻产生变卦但未展示变卦卦象
- **六亲六神**: 完全缺失，解卦不够专业
- **手动起卦**: 仅支持自动起卦，无手动输入六次摇卦结果功能
- **用神判断**: 未根据问题类型自动判断用神

#### 3. 塔罗牌占卜功能
- **牌阵设计**: 单张/三张/凯尔特十字牌阵合理
- **隐者牌问题**: 确认第305-307行使用英文描述（upright/reversed/advice）
- **AI解读**: 仅有基础解读，无AI深度分析功能
- **牌阵关系**: 多张牌之间缺乏关联性解读
- **元素分析**: 牌阵元素分布分析缺失

#### 4. 合婚配对功能
- **算法全面性**: 包含生肖/日主/五行/纳音/地支合冲配对
- **分层付费**: 设计合理(免费预览+80积分详细报告)
- **传统方法**: 缺少三元合婚、九宫合婚、紫微合婚等传统方法
- **八字对比**: 双方八字并排展示清晰

#### 5. 每日运势功能
- **个性化**: 基于八字日主计算，使用十神关系（比劫/印绶/食伤/官杀/财星）
- **黄历信息**: 宜忌展示完整
- **运势关联**: 与日主五行关系判断准确
- **幸运信息**: 提供幸运色和幸运方位

### 本次检查发现的新问题

#### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 六爻变卦计算完全缺失 - backend/app/controller/Liuyao.php第262-326行 - 动爻产生变卦但未计算和展示变卦卦象，六爻解卦不完整 - 建议添加变卦计算逻辑：老阳变阴、老阴变阳
- [ ] [占卜] 六爻六亲六神分析缺失 - backend/app/controller/Liuyao.php - 六爻解卦缺少六亲(父母/兄弟/子孙/妻财/官鬼)和六神(青龙/朱雀/勾陈/螣蛇/白虎/玄武)配属 - 建议添加专业六爻分析
- [ ] [占卜] 六爻用神判断逻辑缺失 - backend/app/controller/Liuyao.php第331-360行 - 未根据问题类型自动判断用神，解读过于通用 - 建议添加关键词识别用神逻辑
- [ ] [占卜] 八字节气数据覆盖年份不足 - backend/app/controller/Paipan.php第563-573行 - 仅1990/1995/2000三年精确数据，其他年份使用通用数据可能导致月柱计算错误(±1天误差) - 建议补充1900-2050年完整节气表
- [ ] [占卜] 八字真太阳时未实现 - backend/app/controller/Paipan.php - 前端选择出生地后，后端未根据经度计算真太阳时 - 建议添加真太阳时转换算法

#### 🟡 中优先级（体验问题）
- [ ] [占卜] 六爻缺少手动起卦模式 - frontend/src/views/Liuyao.vue - 仅支持自动随机起卦，无法手动输入六次摇卦结果 - 建议添加手动起卦界面，支持用户输入每次摇卦结果
- [ ] [占卜] 塔罗牌阵关系分析缺失 - frontend/src/views/Tarot.vue - 多张牌之间缺乏关联性解读，仅单张牌义堆砌 - 建议添加牌阵整体分析和牌与牌之间的关系解读
- [ ] [占卜] 塔罗缺少元素分析 - frontend/src/views/Tarot.vue - 牌阵中元素(火/水/风/土)分布未分析 - 建议添加元素分布统计和解读
- [ ] [占卜] 八字藏干十神展示不突出 - frontend/src/views/Bazi.vue - 虽有藏干十神计算，但前端展示不够突出，藏干信息难以辨认 - 建议优化藏干十神展示方式
- [ ] [占卜] 合婚缺少传统合婚法 - backend/app/controller/Hehun.php - 可添加三元合婚、九宫合婚、紫微合婚等传统方法 - 建议丰富合婚算法
- [ ] [占卜] 塔罗缺少AI深度解读 - backend/app/controller/Tarot.php - 仅有基础牌义，无AI解牌功能 - 建议添加AI解牌接口

#### 🟢 低优先级（专业性优化）
- [ ] [占卜] 六爻可添加应期推断 - backend/app/controller/Liuyao.php - 解卦时可尝试推断事情发生时间 - 建议添加应期分析
- [ ] [占卜] 六爻可添加伏神分析 - backend/app/controller/Liuyao.php - 可添加伏神、飞神分析 - 建议参考《增删卜易》完善
- [ ] [占卜] 塔罗可添加牌面图案 - frontend/src/views/Tarot.vue - 当前仅使用emoji表示，可添加真实塔罗牌图片 - 建议引入韦特塔罗牌图片资源
- [ ] [占卜] 八字可增加神煞分析 - backend/app/controller/Paipan.php - 可添加天乙贵人、文昌、桃花、驿马等常用神煞 - 建议参考《三命通会》
- [ ] [占卜] 八字大运流年可添加更多细节 - backend/app/controller/Paipan.php - 缺少交运时间、流年神煞等信息 - 建议丰富大运流年展示
- [ ] [占卜] 八字日主强弱判断过于简化 - backend/app/controller/Paipan.php - 日主强弱判断仅基于简单规则，未考虑月令、通根、透干等因素 - 建议参考《滴天髓》完善判断逻辑

---

## 占卜功能体验检查报告 - 2026-03-16 第二轮

### 检查概览
- **检查时间**: 2026-03-16
- **检查人员**: 占卜爱好者（专业命理/塔罗爱好者角度）
- **检查范围**: 太初命理网站全部占卜功能深度体验
- **测试生辰**: 1990年5月15日 10:30 男 / 1992年8月20日 女

### 功能体验结果

#### 1. 八字排盘功能
- **排盘计算**: 四柱计算逻辑基本正确，使用五虎遁月法和日上起时法
- **节气问题**: 确认节气数据仅1990、1995、2000三年，其他年份使用通用数据(±1天误差)
- **十神计算**: 天干十神计算正确，但藏干十神展示不完整
- **真太阳时**: 前端有出生地选择，后端未实现经度转换

#### 2. 六爻占卜功能
- **起卦逻辑**: 钱币占卜模拟正确(老阴/少阴/少阳/老阳)
- **变卦缺失**: 动爻产生变卦但未展示变卦卦象
- **六亲六神**: 完全缺失，解卦不够专业
- **手动起卦**: 仅支持自动起卦，无手动输入功能

#### 3. 塔罗牌占卜功能
- **牌阵设计**: 单张/三张/凯尔特十字牌阵合理
- **隐者牌问题**: 确认第305-307行使用英文描述
- **AI解读**: 仅有基础解读，无AI深度分析
- **牌阵关系**: 多张牌缺乏关联性解读

#### 4. 合婚配对功能
- **算法全面性**: 包含生肖/日主/五行/纳音配对
- **分层付费**: 设计合理(免费预览+付费详细报告)
- **传统方法**: 缺少三元合婚、九宫合婚等

#### 5. 每日运势功能
- **个性化**: 基于八字日主计算，逻辑正确
- **黄历信息**: 宜忌展示完整
- **运势关联**: 与日主五行关系判断准确

### 本次检查发现的新问题

#### 🔴 高优先级（逻辑错误/准确性问题）
- [ ] [占卜] 节气数据覆盖年份不足 - backend/app/controller/Paipan.php第563-573行 - 仅1990/1995/2000三年精确数据，其他年份使用通用数据可能导致月柱计算错误(±1天误差) - 建议补充1900-2050年完整节气表
- [ ] [占卜] 六爻变卦计算缺失 - backend/app/controller/Liuyao.php第262-326行 - 有动爻时未计算和展示变卦，六爻解卦不完整 - 建议添加变卦计算逻辑
- [x] [占卜] 塔罗隐者牌英文描述 - frontend/src/views/Tarot.vue第305-307行 - 已翻译为中文：内省沉思/孤立自闭/花时间反思 - 修复时间: 2026-03-16
- [ ] [占卜] 八字排盘真太阳时未实现 - backend/app/controller/Paipan.php - 前端选择出生地后，后端未根据经度计算真太阳时 - 建议添加真太阳时转换算法

#### 🟡 中优先级（体验问题）
- [ ] [占卜] 六爻缺少手动起卦模式 - frontend/src/views/Liuyao.vue - 仅支持自动随机起卦，无法手动输入六次摇卦结果 - 建议添加手动起卦界面
- [ ] [占卜] 六爻六亲六神分析缺失 - backend/app/controller/Liuyao.php - 解卦缺少六亲(父母/兄弟/子孙/妻财/官鬼)和六神(青龙/朱雀等)配属 - 建议添加专业六爻分析
- [ ] [占卜] 六爻用神判断逻辑缺失 - backend/app/controller/Liuyao.php - 未根据问题类型自动判断用神 - 建议添加关键词识别用神逻辑
- [ ] [占卜] 塔罗缺少AI深度解读 - backend/app/controller/Tarot.php - 仅有基础牌义，无AI解牌功能 - 建议添加AI解牌接口
- [ ] [占卜] 八字藏干十神展示不完整 - backend/app/controller/Paipan.php第819-829行 - 虽有藏干十神计算，但前端展示不够突出 - 建议优化藏干十神展示

#### 🟢 低优先级（专业性优化）
- [ ] [占卜] 八字可增加神煞分析 - backend/app/controller/Paipan.php - 可添加天乙贵人、文昌、桃花、驿马等常用神煞 - 建议参考《三命通会》
- [ ] [占卜] 八字大运流年细节不足 - backend/app/controller/Paipan.php - 缺少交运时间、流年神煞等信息 - 建议丰富大运流年展示
- [ ] [占卜] 塔罗牌阵关系分析缺失 - frontend/src/views/Tarot.vue - 多张牌之间缺乏关联性解读 - 建议添加牌阵整体分析
- [ ] [占卜] 合婚可增加传统合婚法 - backend/app/controller/Hehun.php - 可添加三元合婚、九宫合婚、紫微合婚等 - 建议丰富合婚算法
- [ ] [占卜] 六爻应期推断缺失 - backend/app/controller/Liuyao.php - 解卦时可尝试推断事情发生时间 - 建议添加应期分析

---
*最后更新: 2026-03-16 - 占卜爱好者体验检查第二轮*
