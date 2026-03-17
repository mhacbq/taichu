# 太初项目统一版 - 2026-03-17 状态概览

## 最近更新

### 命理算法修复（本轮自动化执行，2026-03-17）
- 本轮集中修复了 **5 个 `[占卜]` 逻辑/算法问题**，覆盖 **节气定月、起运顺逆、旬空展示、五行权重底盘、合婚生肖索引、每日运势黄历** 六条关键链路。
- 核心改动集中在：`backend/app/service/BaziCalculationService.php`、`backend/app/service/BaziInterpretationService.php`、`backend/app/controller/Paipan.php`、`backend/app/controller/Hehun.php`、`backend/app/model/DailyFortune.php`、`TODO.md`。

#### 本轮完成项
1. **节气定月逻辑纠偏**
   - `BaziCalculationService::getLunarMonth()` 改为按 **大雪→小寒→立春→惊蛰……** 的顺序判月，不再逆序误落丑月。
   - 理论依据：子平法以“节”定月，子月起大雪、丑月起小寒、寅月起立春。
2. **起运顺逆恢复按男女阴阳判定**
   - `Paipan.php` 现已把前端性别透传到 `calculateBazi()`；服务层同时兼容 `male/female` 与 `男/女`。
   - 理论依据：阳男阴女顺、阴男阳女逆，性别参数不能在控制层丢失。
3. **旬空与五行权重底盘补齐**
   - 八字结果新增顶层 `xunkong` / `xunkong_list`，并把各柱旬空改为可直接展示的文本。
   - 新增 `wuxing_stats` 加权统计：天干显气 + 地支藏干分气，并突出月令权重，解释层改为消费浮点权重而非截断整数。
   - 理论依据：旬空须随日柱同出；五行旺衰应兼看透干、藏干与月令司令，不宜只做机械计数。
4. **合婚免费预览 500 修复**
   - 八字四柱现补齐 `gan_index` / `zhi_index` / `number` 等元数据；`Hehun::analyzeYearPillar()` 也增加地支索引回退。
   - 直接修复生肖配对读取 `zhi_index` 崩溃的问题。
5. **每日运势黄历年号改回真实农历**
   - `DailyFortune` 已改为调用 `LunarService::solarToLunar()` 生成 `丙午年 正月廿九` 这类真实农历字符串，并会自动修正今日已存在的错误记录。
   - 理论依据：黄历底盘至少应以真实农历年、月、日为基础，不能使用随机干支模拟。

#### 验证情况
- 已对新增/修改的 PHP 文件执行 IDE/LSP 诊断：**无新增语法错误**。
- 已人工复核关键逻辑：
  - 月柱改按节令边界推进；
  - 起运性别参数已贯通前后；
  - 八字结果已包含旬空与五行权重统计；
  - 合婚生肖分析对新旧数据都可回退；
  - 每日运势黄历字符串改由统一农历服务生成。
- 环境限制：当前机器仍未检测到 `php` CLI，暂未执行命令行回归脚本。

### 后台运营巡检（本轮自动化巡检）
- 已实际启动独立后台前端并验证 `http://localhost:3001/login` 可访问，然后结合真实接口请求、数据库查询与后台代码交叉核验，完成管理员登录、Dashboard、用户、内容、订单、系统配置链路的运营巡检。
- 已将本轮 **6 项新增且不重复的运营阻塞问题** 写入 `TODO.md` 的 **《第十九轮后台运营检查报告》**。
- 本轮未修改业务代码，主要输出为运营问题清单、运行态证据和修复优先级。

#### 关键发现
- **登录页可访问，但无法完成登录**：后台前端代理仍指向 `localhost:8000`，而实际本地后端运行在 `8080`；直连 `POST /api/admin/auth/login` 时又因查询不存在的 `taichu.admin` 表返回 500。
- **登录后权限验证链路未就绪**：`GET /api/admin/auth/info` 实测报错 `ADMIN_JWT_SECRET environment variable is not set`，受保护接口无法正常进入。
- **Dashboard/导航存在整体失效风险**：`admin/src/router/index.js` 仅注册 `constantRoutes`，业务 `asyncRoutes` 未注入 router，登录成功后也无法稳定落到首页及各模块页面。
- **内容与订单模块存在成片路径错配**：黄历 CRUD、支付/VIP 订单、站点内容/FAQ/评价/SEO 的前端请求路径与后端路由不一致，运营核心操作链路当前不可用。

#### 运行态证据
- 登录页访问：`curl http://localhost:3001/login` 返回 `HTTP 200`。
- 登录接口报错：`POST http://localhost:8080/api/admin/auth/login` 返回 `SQLSTATE[42S02] ... Table 'taichu.admin' doesn't exist`。
- 鉴权接口报错：`GET http://localhost:8080/api/admin/auth/info` 返回 `ADMIN_JWT_SECRET environment variable is not set`。
- 数据库核验：本地 `taichu` 库中不存在 `admin` / `tc_admin` 登录表，但存在 `tc_admin_role`、`tc_admin_user_role` 等角色关联表。

#### 验证说明
- 已验证：后台登录页可访问；后端健康检查 `http://localhost:8080/api/health` 返回 `200`；Vite 管理端端口 `3001` 可启动。
- 已验证失败：账号密码登录、登录后鉴权、Dashboard 落地、黄历管理、支付/VIP 订单、站点内容与 SEO 管理链路。
- 环境备注：本轮为完成巡检临时安装了 `admin` 端依赖并启动了本地 dev 服务；尝试在收尾阶段关闭 3001 监听进程时被系统拒绝，因此当前本机可能仍有后台 dev 服务在运行。

### 占卜功能实测（本轮自动化巡检）

- 以“资深爱好者 + 代码交叉核验”的方式，实际体验并复核了 **八字排盘、六爻占卜、塔罗占卜、合婚配对、每日运势** 五条链路。
- 已将本轮新增且确认不重复的问题写入 `TODO.md` 的 **《占卜爱好者深度体验检查报告 - 第二十轮》**。
- 本轮未改动业务代码，主要产出为问题清单、运行态证据与后续修复优先级。

#### 关键发现
- **八字排盘**：测试生辰 `1990-05-15 10:30 男` 实测月柱落到 `己丑`，结合 `BaziCalculationService::getLunarMonth()` 代码可确认节气定月逻辑倒序，导致多数日期会错误落到丑月。
- **六爻占卜**：页面加载与提交都命中错误路由，后端返回“方法不存在：`Liuyao->getPricing()` / `Liuyao->divination()`”，当前核心起卦链路不可用。
- **塔罗占卜**：单张、三张、凯尔特十字牌阵都能出牌，但凯尔特十字关系分析仍偏线性串牌，结论模板化较重。
- **合婚配对**：免费预览提交即 500，错误页明确提示 `Hehun.php line 563` 读取不存在的 `zhi_index`。
- **每日运势**：接口本身返回 200，但前端字段映射错位，且 `2026-03-17` 返回的 `lunarDate` 为“甲子年 1月16日”，与真实黄历不符。

#### 运行态证据
- 八字结果截图：
  ![八字排盘结果](c:/Users/v_boqchen/AppData/Roaming/WorkBuddy/User/globalStorage/tencent-cloud.coding-copilot/brain/c9535e71645542debcc135a35034ee1c/bazi-result.png)
- 每日运势异常截图：
  ![每日运势异常](c:/Users/v_boqchen/AppData/Roaming/WorkBuddy/User/globalStorage/tencent-cloud.coding-copilot/brain/c9535e71645542debcc135a35034ee1c/daily-error.png)

### 命理算法修复（本轮）

- **八字历法内核统一**：修复 `backend/app/controller/Paipan.php` 的旧版节气与日柱分叉逻辑。
  - `getJieQiDate()` 补齐 **20 世纪/21 世纪寿星公式常数** 与特殊年修正。
  - `calculateDayPillar()`、`calculateBazi()` 改为统一委托 `BaziCalculationService`，避免控制器继续使用旧基准日算法。
- **六爻伏神链路补全**：修复 `backend/app/controller/Liuyao.php` 与 `backend/app/service/LiuyaoService.php`。
  - 起卦时不再默认“甲日”，会自动推算当日干支，校验 `ri_gan/ri_zhi`。
  - 用神不现时会按卦宫首卦回退伏神，并补出伏神地支、宿主爻、旬空状态。
- **八字强弱评分深化**：修复 `backend/app/service/BaziCalculationService.php` 与 `backend/app/service/BaziInterpretationService.php`。
  - 在原“月令/藏干/透干”基础上，新增 **六冲、六合、三合局** 对日主力量的加减分。
  - 喜用神文案直接复用核心强弱评分结果，避免解读层与排盘层口径漂移。
- **塔罗元素术语回正**：修复 `backend/app/controller/Tarot.php` 的元素互动文案，回归 `Elemental Dignities / Friendly / Enemy / Neutral` 术语，不再使用“五行化”表达。
- **TODO 清理**：已从 `TODO.md` 删除本轮完成的 5 项 `[占卜]` 条目：
  1. 20世纪节气计算常数缺失
  2. 日柱计算算法不统一
  3. 缺失“伏神”系统
  4. 塔罗元素互动术语中式化
  5. 强弱分析未计入地支冲合

## 本轮涉及文件
- `backend/app/controller/Paipan.php`
- `backend/app/controller/Liuyao.php`
- `backend/app/controller/Tarot.php`
- `backend/app/service/BaziCalculationService.php`
- `backend/app/service/BaziInterpretationService.php`
- `backend/app/service/LiuyaoService.php`
- `TODO.md`

## 验证情况
- 已对上述变更文件执行 **IDE/LSP 诊断检查**：未发现新增语法或静态错误。
- 已人工复核关键差异点：
  - `Paipan` 不再保留旧版日柱计算主链路。
  - `Liuyao` 已把日辰上下文传入用神判断。
  - `BaziInterpretationService` 已改为消费 `BaziCalculationService::analyzeStrength()` 的结果。
- **运行态验证说明**：当前环境未找到可直接调用的 `php` CLI，因此未执行命令行级的 PHP 回归脚本；后续如补齐本机 PHP 路径，建议追加一轮样例盘校验。

## 当前仍待处理的占卜项
- [ ] 命卦计算忽略立春划分
- [ ] 塔罗逆位牌义支持不均
- [ ] 六爻接口路由与控制器方法完全失配

## 前端待办修复（本轮自动化执行，2026-03-17）

- 本轮聚焦 `TODO.md` 中的前端/Vue 待办，完成 **5 个前端问题修复**，覆盖登录验证码、每日运势渲染、后台用户运营、站点内容分页四条链路。
- 同时顺手处理了两个会阻塞独立后台构建验证的前端基础问题：补装 `admin` 端缺失的 `vuedraggable` 依赖，并在 `admin/src/utils/format.js` 中补齐 `formatDateTime` 导出。

### 本轮完成项
1. **登录验证码接口纠正**
   - 文件：`frontend/src/api/index.js`
   - 将发送验证码接口从错误的 `/auth/send-sms` 改为后端实际存在的 `/sms/send-code`。
2. **每日运势页面响应映射修复**
   - 文件：`frontend/src/views/Daily.vue`
   - 改为直接消费后端平铺返回的 `date / lunarDate / overallScore / aspects / details / personalized` 字段，恢复页面主内容渲染。
3. **用户详情页补齐手动调积分**
   - 文件：`admin/src/views/user/detail.vue`
   - 新增“手动调积分”按钮、调账弹窗，并接入 `/points/adjust` 接口，提交成功后自动刷新用户详情。
4. **用户列表改进版恢复批量启用/禁用**
   - 文件：`admin/src/views/user/list-improved.vue`
   - 补齐批量状态更新逻辑，支持批量启用/禁用选中用户。
5. **站点内容管理补齐分页**
   - 文件：`admin/src/views/site-content/content-manager.vue`
   - 新增分页条、翻页与 pageSize 事件处理，并对接后端 `current/pageSize` 分页参数；删除最后一条记录时会自动回退页码。

### TODO 清理
- 已从 `TODO.md` 删除以下 5 条已完成待办：
  1. 用户详情页积分手动调整功能缺失
  2. 用户管理列表批量启用/禁用功能失效
  3. 站点内容管理模块缺失分页功能
  4. 每日运势前端取数字段与后端响应结构不一致
  5. 登录验证码仍走错误接口导致占卜前置流程受阻

### 验证结果
- `npm run build --prefix frontend`：通过。
- `npm run build --prefix admin`：通过。
- 备注：构建过程中存在 Sass legacy-js-api 与大体积 chunk 警告，但不影响本轮功能编译通过。

