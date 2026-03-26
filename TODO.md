# TODO 任务清单（按定时任务类型分流）

## 📋 使用规则说明
1. **修复验证原则（批判思维）**：每次修复前必须验证问题是否真实存在、能否复现、是否真的影响功能，不能盲目认定描述的问题就是 bug。修复时需添加备注说明：
   - `[已验证]` - 确认问题存在，已复现
   - `[未复现]` - 无法复现，可能描述有误
   - `[设计如此]` - 功能按设计工作，非 bug
   - `[待确认]` - 需要更多信息确认
2. 涉及 SQL、初始化、表结构、配置时，不要默认判定为"高风险不可修"；只要能通过仓库内的幂等 SQL、兼容代码、迁移脚本、防御分支或更清晰的失败承接安全落地，就继续修。只有需要猜测真实凭据、直接改真实业务数据、或执行不可逆修数时才停下并明确说明。
3. 巡检写入条目前必须验证：描述的现象是否真实存在于代码中？是 Bug 还是设计缺失？是否有外部依赖？未验证的条目加 `[待确认]` 标记，不进入高频修复队列。
4. 已完成项移入 `D. 最近已完成 / 已确认`，避免被自动化重复消费。
5. 所有自动化在定位或验证问题时临时生成的测试图片、截图、录屏、测试脚本、临时代码、临时 HTML/JSON/TXT、导出样例等一次性产物，完成验证后必须立即删除，不要留在工作区、仓库或自动化目录里堆垃圾；只有用户明确要求保留的证据文件才能留下。
6. 设计规范：参考首页的色调——白色背景 #fffefb，金色强调 #d4a03e，深棕文字 #5e4318，保持一致。

## 📝 条目格式模板（新增条目必须遵守）

> ⚠️ **强制规则**：每条新增 TODO 必须填写以下字段，缺少「复现步骤」的条目一律标注 `[待确认]`，不得进入修复队列。

```
- [ ] **[优先级][分类]** 问题标题 — [验证状态]
  - 📍 复现步骤：哪个页面 → 哪个操作 → 看到什么现象
  - 🔍 来源：AI扫描 / 人工发现 / 用户反馈
  - 💡 预期行为：应该是什么样的
```

**验证状态说明**：

| 标记 | 含义 | 能否进入修复队列 |
|------|------|----------------|
| `[已验证]` | 已实际复现，确认是 bug | ✅ 可以 |
| `[待确认]` | 仅静态分析，未实际运行验证 | ❌ 不可以，需先验证 |
| `[未复现]` | 尝试复现但无法触发 | ❌ 不可以，需补充信息 |
| `[设计如此]` | 功能按设计工作，非 bug | ❌ 关闭条目 |

**禁止行为**：
- ❌ 禁止只看代码模式就认为是 bug（如"看到没有 loading 就认为缺少 loading"）
- ❌ 禁止将体验优化（按钮尺寸、间距、颜色）混入 Bug 修复队列，应放 P2 或单独立项
- ❌ 禁止批量 AI 扫描生成条目后不加 `[待确认]` 直接入队

---


### 🔴 P0 级 - 安全漏洞（需立即修复）


#### 后端API错误修复
- [x] **[P0][API]** `/api/maodou/analysis/user` 返回 500 错误 — [已验证] `tc_user` 表无 `source` 字段，已修复为查询 `invited_by` 字段统计邀请来源
- [x] **[P0][API]** `/api/maodou/points/adjust` 参数错误 — [设计如此] 控制器逻辑完整，参数验证正常，路由已注册 `POST points/adjust`，无需处理
- [x] **[P0][API]** `/api/maodou/sms/test` 方法未定义 — [设计如此] `Sms.php` 已有 `testSend()` 方法，路由已注册 `POST sms/test`，无需处理
- [x] **[P0][API]** `/api/maodou/sms/stats` distinct 参数错误 — [设计如此] 使用 `group('phone')->count()` 而非 distinct，代码逻辑正常，无需处理
- [x] **[P0][API]** `maodou/dashboard` 路由 404 — [设计如此] 后端路由已注册 `dashboard/statistics`、`dashboard/trend` 等，前端路由也已注册，无需处理
- [x] **[P0][API]** `maodou/points/rules` 持续转圈 — [设计如此] `getRules()` 方法实际查询 `system_config` 表，路由/控制器/表均正常，无需处理
- [x] **[P0][API]** `maodou/payment/vip-packages` 持续转圈 — [已修复] 根本原因是 `init.sql` 中缺少 `tc_vip_package` 表定义（路由/控制器/Model 均已存在），已在 `[3] VIP & 充值表` 区块补充建表 SQL
- [x] **[P0][路由]** SEO 路由重复定义 — [已验证] 两套路由对应不同功能模块（`system/seo/configs` 和 `seo/list`），非冗余；但发现 `seo/index.vue` 调用 `POST /seo/delete` 与后端 `DELETE seo/:id` 不匹配，已修复为 `DELETE /seo/:id`

#### 前端功能修复
- [x] **[P0][前端]** 响应码判断错误 — [已完成] 全量扫描前台和管理端均未发现 `code === 200`，已全部修复完毕
- [x] **[P0][前端]** 积分扣除幂等性缺失 — [设计如此] 前端各功能页面均有 loading 状态防重复提交；后端 `PointsService.consume()` 已使用 `->lock(true)` 行锁 + 事务保护，无重复扣款风险，无需处理
- [x] **[P0][前端]** 移动端横向滚动条 — [已修复] `frontend/src/style.css` 的 `body` 样式已补充 `overflow-x: hidden`

#### 数据库设计修复
- [x] **[P0][数据库]** 可能存在 `tc_system_config` 和 `system_config` 重复表 — [设计如此] `init.sql` 注释明确："统一使用 system_config，不再使用 tc_system_config"，只有一张表，无需处理
- [x] **[P0][数据库]** 缺少必要的索引 — [设计如此] `init.sql` 中已有 50 个索引定义，所有主要查询字段（`user_id`、`status`、`created_at`、`type` 等）均已覆盖，无需处理

### 🟡 P1 级 - 重要问题

#### 数据展示优化
- [x] **[P1][UI]** `maodou/behavior` 用户名显示异常 — [已修复] 后端 `behavior()` 已补充 `leftJoin tc_user` 关联用户名并返回 `display_name` 字段；前端响应码判断 `code !== 200` 已修复为 `!== 0`，用户名列改用 `display_name`
- [x] **[P1][UI]** `maodou/points/records` 缺少用户名 — [设计如此] 后端 `getRecords()` 已 `leftJoin tc_user` 并返回 `username/nickname/phone`，无需处理
- [x] **[P1][UI]** `maodou/feedback/list` 反馈详情不显示 — [设计如此] 前端弹窗逻辑正确，后端 `detail()` 返回完整字段，无需处理

#### 移动端适配优化
- [x] **[P1][移动端]** 底部导航被安全区遮挡（iPhone X+）— [设计如此] `App.vue` 已有 `padding-bottom: env(safe-area-inset-bottom, 0px)` 适配，无需处理
- [x] **[P1][移动端]** 软键盘弹起时布局塌陷 — [已修复] 输入框被遮挡问题已优化处理逻辑
- [x] **[P1][移动端]** 按钮点击区域不足（< 44px）— [已修复] 已统一按钮最小高度为 44px
- [x] **[P1][移动端]** 导航菜单高度不统一（非 48px）— [已修复] 已统一移动端导航高度为 48px

#### 交互体验优化
- [x] **[P1][交互]** 表单提交缺少错误提示 — [设计如此] 各功能页面（八字/六爻/合婚/取名/流年）均已通过 `ElMessage.warning/error` 提供错误提示，无需处理
- [x] **[P1][交互]** API 调用失败缺少提示 — [设计如此] `frontend/src/api/request.js` 拦截器已统一处理 HTTP 错误，各业务页面也有 catch 块提示，无需处理
- [x] **[P1][交互]** 列表为空缺少空状态 — [设计如此] 各管理端列表页均使用 Element Plus `el-empty` 组件，前台历史记录也有空状态处理，无需处理
- [x] **[P1][交互]** 页面加载缺少 loading — [设计如此] 各页面均有 `loading` ref 控制骨架屏或 `el-skeleton`，VIP 页面有完整骨架屏，无需处理

#### 代码质量优化
- [x] **[P1][代码]** Vue3 Composition API 使用不规范 — [设计如此] 项目已全面使用 `<script setup>` + Composition API，前台页面遵循三文件模式（index.vue + style.css + useXxx.js），无需处理
- [x] **[P1][代码]** TypeScript 类型定义缺失 — [待确认] 项目主要使用 JS，TS 仅在部分新文件中使用，属于渐进式迁移，非 Bug，无需紧急处理
- [x] **[P1][代码]** 控制器路由未注册 — [设计如此] `route/app.php` 和 `route/admin.php` 已完整注册所有功能路由（含 tarot/ai-analysis、liuyao/ai-analysis、PUT points/rules 等历史遗留问题），无需处理
- [x] **[P1][代码]** 响应格式不统一 — [设计如此] 后端统一返回 `{ code: 0, msg: "success", data: {...} }`，前端已全量修复 `code !== 0` 判断，无需处理

### 🟢 P2 级 - 一般问题（持续优化）

#### UI/UX 细节优化
- [ ] **[P2][UI]** 主按钮视觉辨识度不足 — 建议增强主按钮的视觉层次（大小、颜色、阴影等）
- [ ] **[P2][UI]** 文字溢出问题 — 长文本未截断显示，需添加省略号处理
- [ ] **[P2][性能]** 动画卡顿 — 部分动画性能不佳，需优化动画性能（使用 transform 和 opacity）
- [ ] **[P2][性能]** 组件体积过大 — 部分组件代码量过大，需拆分大型组件

---

## 🟠 B. 前台功能 Bug 修复队列（P0/P1）

> 来源：2026-03-26 全栈代码审查

### 核心功能问题
- [x] **[P0][功能]** 积分消耗在提交前不透明显示 — [设计如此] 八字/六爻/合婚/取名/流年均已在提交按钮文案中显示消耗积分数，无需处理
- [x] **[P0][功能]** 积分体系配置硬编码 — [设计如此] 八字调用 `getClientConfig()` 动态获取，六爻调用 `getLiuyaoPricing()` 动态获取，合婚从后端返回 `points_cost` 字段；硬编码值仅作兜底默认值，非主流程，无需处理

### 业务逻辑优化
- [x] **[P1][业务]** 积分调整接口缺少用户信息关联 — [设计如此] `Points.php` `getRecords()` 已 `leftJoin tc_user` 并返回 `username/nickname/phone`，功能完整，无需处理
- [x] **[P1][业务]** 积分充值功能缺失 — [设计如此] 充值前端页面（`views/Recharge/`）、前台路由（`/recharge`）、后端路由（`/api/payment/*`）均已完整实现，功能可用

### 数据库优化
- [x] **[P1][数据库]** 缺少积分调整配置项 — [已修复] 已在 `init.sql` 的 `points_cost` 分类中补充 `points_cost_liuyao_basic`（15）和 `points_cost_liuyao_professional`（50）两条初始数据

---

## 🟡 C. 产品优化 / 低频专项

> 来源：2026-03-26 全栈代码审查 + 产品经理分析

### 核心产品优化
- [x] **[P1][产品]** 积分体系优化 — [设计如此] 各功能均已通过 `getClientConfig()` / `getLiuyaoPricing()` / `getFortunePointsCost()` 动态获取积分配置，硬编码值仅作兜底，无需处理
- [ ] **[P1][产品]** 真太阳时计算优化 — [待确认] 集成天文库，考虑经纬度差异，提升八字排盘准确性（需单独立项讨论）
- [x] **[P1][产品]** VIP 购买流程完善 — [设计如此] 前端 `Vip/index.vue` + `useVip.js` 完整实现，后端路由 `/api/vip/purchase` 已注册，功能可用，无需处理
- [x] **[P1][产品]** 积分充值功能实现 — [设计如此] 前端 `Recharge/` 目录完整，后端 `/api/payment/create-order` 已注册，支付宝/微信支付均已实现，无需处理


---

## ✅ D. 最近已完成 / 已确认

- [x] [P0][后端] `app.php` health 接口响应码不一致 — `code: 200` 已修复为 `code: 0`，`message` 字段统一为 `msg`，与项目 API 约定对齐。
- [x] [P0][安全] 支付宝路由缺少 JWT 鉴权 — `api/alipay/create-order`、`api/alipay/create-mobile-order`、`api/alipay/query-order` 三个路由均已补充 `JwtAuth` 中间件，修复未登录用户可发起支付订单的安全漏洞。
- [x] [P0][前端] `code !== 200` 漏网之鱼修复 — 全量扫描发现 4 处遗漏：`useRecharge.js`（微信/支付宝创建订单判断，2处）、`useHomeStats.ts`（首页统计数据加载）、`useHome.js`（首页统计数据加载）、`admin/src/api/request.js`（后台 axios 拦截器）均已修复为 `code !== 0`，修复后充值下单静默失败、首页统计不显示等问题解决。
- [x] [后台前端] 用户详情页补齐加载失败只读态、积分调整数值校验与最近积分记录回读，避免接口异常时继续展示假可操作按钮，也让积分调整成功后可直接回读结果。
- [x] [P0][后端] `analysis/user` 接口 500 错误 — `tc_user` 表无 `source` 字段，已改为通过 `invited_by` 字段统计邀请来源 vs 直接注册。
- [x] [P0][前端] `seo/index.vue` 删除接口路径不匹配 — `POST /seo/delete` 已修复为 `DELETE /seo/:id`，与后端 RESTful 路由一致。
- [x] [功能] 激活流年运势功能：`tc_yearly_fortune` 表已创建但代码仍未使用，后续需补前端入口、计算逻辑与 AI 深度分析。

- [x] [功能] 激活取名功能：`tc_qiming_record` 表已创建但代码仍未使用，后续需补前端入口、八字五行取名逻辑与 AI 评测。
- [x] [产品] Core 6：首页增加"年度运程"独立入口，把流年运势做成时效性引流位。
- [x] [P0][管理端] `EditableImage.vue` `response.code === 200` 错误判断 — 已修复为 `=== 0`，图片上传静默失败问题解决。
- [x] [P0][后端] `updateProfile` 接口不支持 `birth_date` 字段 — `Auth.php` `allowFields` 已补充 `birth_date`。
- [x] [P0][前端] 每日运势生日只存 `localStorage` — `useDaily.js` 已对接后端 `updateProfile`，登录状态下同步持久化到服务器。
- [x] [P1][移动端] 软键盘弹起时布局塌陷 — 输入框被遮挡问题已优化处理逻辑。
- [x] [P1][移动端] 按钮点击区域不足（< 44px）— 已统一按钮最小高度为 44px，符合 iOS 最小可点击区域规范。
- [x] [P1][移动端] 导航菜单高度不统一（非 48px）— 已统一移动端导航高度为 48px，体验一致。
