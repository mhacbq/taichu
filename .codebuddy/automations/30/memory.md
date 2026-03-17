# 网站逻辑检查任务 - 执行历史

## 2026-03-17 执行记录 (第32轮)

### 检查范围
1. 前端逻辑检查 (frontend/src目录)
2. 管理端逻辑检查 (admin/src/views目录)
3. 后台逻辑检查 (backend/app/controller目录)

### 本次检查发现的新问题

#### 🔴 高优先级（功能性/安全问题）
1. **前端API响应码判断不一致** - Profile.vue使用`code === 200`，其他文件使用`code === 0`
2. **前端XSS过滤不够完善** - Hehun.vue使用正则表达式过滤，建议使用DOMPurify
3. **前端内存泄漏风险** - Bazi.vue定时器在组件卸载时未清理
4. **后端Auth.php缺少Log类导入** - 使用Log::error()但未导入Log类
5. **后端AdminAuth中间件返回码不一致** - 认证失败返回code=200但HTTP状态码为401
6. **管理端缺失13个视图文件** - 路由配置存在但对应视图文件不存在

#### 🟡 中优先级（体验/代码质量问题）
1. **前端未使用的导入和变量** - Home.vue和Bazi.vue存在未使用代码
2. **前端流式响应错误处理不完善** - AI解盘流式响应解析错误被静默忽略
3. **前端缺少全局状态管理** - 积分状态从localStorage读取可能不是最新
4. **后端Cors中间件域名配置** - 包含大量本地开发地址
5. **后端RateLimit中间件属性检查** - 检查$request->user时未判断属性是否存在
6. **后端Payment.php回调异常日志不完善** - 支付回调异常时未记录详细错误
7. **管理端API路径前缀不一致** - 不同API文件使用不同路径前缀
8. **管理端角色/字典管理使用模拟数据** - role.vue和dict.vue使用硬编码数据

#### 🟢 低优先级（优化问题）
1. **后端orderRaw潜在SQL注入风险** - TarotCard.php和DailyFortuneTemplate.php使用orderRaw
2. **后端HttpsEnforce环境变量判断** - 使用=== 'false'可能不准确
3. **管理端权限指令未使用** - 定义了v-permission指令但视图文件中未使用
4. **管理端响应拦截器错误处理不完善** - 只处理code !== 200，未处理403/500等

### 待处理统计
- 高优先级: 6个新问题
- 中优先级: 8个新问题
- 低优先级: 4个新问题

---

## 2026-03-17 23:30 执行记录 (第29轮)

### 检查范围
1. 前端逻辑检查 (frontend/src目录)
2. 管理端逻辑检查 (admin/src/views目录)
3. 后台逻辑检查 (backend/app/controller目录)

### 本次检查发现的新问题

#### 🔴 高优先级（功能性/安全问题）
1. **后端API响应格式不统一** - Config.php/SiteContent.php混用`json(['code' => 0])`和`$this->success()`
2. **后端Admin.php获取用户信息方式错误** - 使用`$this->request->user`而非`$this->request->adminUser`
3. **后端Upload.php文件扩展名验证不一致** - 使用`getOriginalExtension()`而非`getExtension()`
4. **后端异常信息泄露风险** - Auth.php/SiteContent.php直接返回`$e->getMessage()`
5. **管理端响应码判断不统一** - 多个页面使用`res.code === 0`，应统一为200
6. **管理端路由权限控制不完整** - /sms/feedback/anticheat等模块未配置roles权限
7. **管理端存在大量模拟数据** - role.vue/dict.vue/user/detail.vue使用硬编码数据

#### 🟡 中优先级（体验/代码质量问题）
1. **后端AiAnalysis.php直接输出流式响应** - 使用`echo`和`exit`直接输出
2. **后端SiteContent.php缺少输入验证** - updatePageContent未验证page参数格式
3. **管理端API路径前缀不一致** - 不同文件使用不同前缀格式
4. **管理端页面状态管理问题** - updatePageStatus函数注释掉了API调用
5. **管理端敏感操作缺少权限验证** - 用户状态切换等操作没有权限控制

#### 🟢 低优先级（优化问题）
1. **后端代码重复** - 分页参数验证和权限检查逻辑重复
2. **后端魔法数字** - 密码长度、分页大小使用硬编码
3. **管理端权限指令依赖问题** - roles初始值为空数组

### 已修复/已不存在的问题
1. 前端Tarot.vue缺少导入 - 已修复
2. 前端Liuyao.vue缺少组件导入 - 已修复
3. 后端Hehun.php变量未定义错误 - 已修复
4. 后端Hehun.php XSS安全风险 - 已修复
5. 后端Cors中间件允许任意来源 - 已修复

### 待处理统计
- 高优先级: 7个新问题
- 中优先级: 5个新问题
- 低优先级: 3个新问题

---

## 2026-03-17 22:00 执行记录 (第27轮)

### 检查范围
1. 前端逻辑检查 (frontend/src目录)
2. 管理端逻辑检查 (admin/src/views目录)
3. 后台逻辑检查 (backend/app/controller目录)

### 本次检查发现的新问题

#### 🔴 高优先级（功能性/安全问题）
1. **前端API响应码判断不一致** - Recharge.vue/Profile.vue/Bazi.vue/useSiteContent.js多处使用`res.code === 0`，但后端大部分接口返回`code=200`
2. **后端返回格式不统一** - Config.php/Upload.php/AiPrompt.php/SiteContent.php混用`json()`和`$this->success()/$this->error()`，code值不一致（0或200）
3. **后端异常信息泄露风险** - Upload.php/SiteContent.php多处使用`json(['code' => 500, 'message' => $e->getMessage()])`直接返回异常消息
4. **前端管理端页面API调用均为模拟实现** - SEOManage.vue/SEOStats.vue/ShenshaManage.vue/KnowledgeManage.vue使用硬编码数据
5. **前端SEOStats.vue图表初始化代码缺失** - pieChart和trendChart ref被引用但未初始化，ECharts未引入

#### 🟡 中优先级（体验/代码质量问题）
1. **后台管理页面响应码判断混乱** - dashboard/index.vue/site-content/ai/prompts.vue使用`res.code === 0`，与request.js期望的`code=200`不一致
2. **后端Upload.php部分返回使用json()** - 第51、251、260行使用`json()`返回
3. **后端AiPrompt.php部分返回使用json()** - 第73行使用`json()`返回

#### 🟢 低优先级（优化问题）
1. **前端AlmanacManage.vue响应码判断使用双条件** - 使用`res.code === 200 || res.code === 0`
2. **前端路由缺少错误边界处理** - 路由懒加载组件没有错误边界处理
3. **前端管理端页面缺少真实API对接** - 需要实现后端API接口

### 已修复/已不存在的问题
1. 后端AdminAuth.php硬编码用户ID - 已使用`$admin['id']`
2. 后端Paipan.php/Tarot.php异常处理 - 已正确记录日志并返回通用错误消息

### 待处理统计
- 高优先级: 5个新问题
- 中优先级: 5个新问题
- 低优先级: 3个新问题

---

[历史记录已归档...]
