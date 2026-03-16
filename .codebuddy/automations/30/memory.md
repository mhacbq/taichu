# 网站逻辑检查任务 - 执行历史

## 2026-03-17 16:30 执行记录 (第25轮)

### 检查范围
1. 前端逻辑检查 (frontend/src目录)
2. 管理端逻辑检查 (frontend/src/views/admin目录)
3. 后台逻辑检查 (backend/app目录)

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
1. **前端API响应码判断不一致** - Recharge.vue/Profile.vue/Bazi.vue多处使用`res.code === 0`，但后端大部分接口返回`code=200`
2. **后端返回格式不统一** - Config.php/SiteContent.php/Upload.php/AiPrompt.php/AdminAuth.php混用`json()`和`$this->success()/$this->error()`
3. **前端管理端页面API调用均为模拟实现** - SEOManage.vue/SEOStats.vue/ShenshaManage.vue/KnowledgeManage.vue使用硬编码数据
4. **前端SEOStats.vue图表初始化代码缺失** - pieChart和trendChart ref被引用但未初始化

#### 🟡 中优先级（体验问题）
1. **后端Upload.php部分返回使用json()** - 第51、251、260行使用`json()`返回
2. **后端AiPrompt.php部分返回使用json()** - 第73、234、248行使用`json()`返回
3. **前端管理端分页逻辑不完整** - 分页组件存在但数据未按分页切片
4. **前端AlmanacManage.vue响应码判断使用双条件** - 使用`res.code === 200 || res.code === 0`

#### 🟢 低优先级（优化问题）
1. **前端路由缺少错误边界处理** - 路由懒加载组件没有错误边界处理
2. **前端管理端页面缺少真实API对接** - 需要实现后端API接口

### 已修复/已不存在的问题
1. 后端Admin.php缺少Db类导入 - 已修复
2. 后端Upload.php文件上传安全风险 - 已修复
3. 后端Upload.php目录遍历风险 - 已修复
4. 后端SmsService.php cURL SSL验证 - 已修复
5. 后端Liuyao.php事务处理 - 已修复
6. 后端Liuyao.php日辰参数验证 - 已修复

### 待处理统计
- 高优先级: 4个新问题
- 中优先级: 4个新问题
- 低优先级: 2个新问题

---

## 2026-03-16 21:00 执行记录

### 检查范围
1. 前端逻辑检查 (frontend/src目录)
2. 管理端逻辑检查 (frontend/src/views/admin目录)
3. 后台逻辑检查 (backend/app目录)

### 本次检查发现的新问题

#### 🔴 高优先级（功能性问题）
1. **后端返回格式不统一** - SiteContent.php/AiPrompt.php/Upload.php/AdminAuth.php直接使用json()返回，与其他使用success()/error()的控制器不一致
2. **后端AdminAuth.php硬编码管理员凭据** - 第28行硬编码账号密码，存在严重安全隐患
3. **后端AdminAuth.php JWT密钥硬编码** - 第17行JWT密钥硬编码，应从环境变量读取
4. **后端Upload.php文件上传安全风险** - 使用getOriginalExtension()获取扩展名，可能被伪造
5. **后端Upload.php目录遍历风险** - 删除文件时未验证路径
6. **后端Payment.php cURL禁用SSL验证** - SSL_VERIFYPEER和SSL_VERIFYHOST设为false
7. **后端SmsService.php cURL禁用SSL验证** - SSL_VERIFYPEER设为false
8. **后端Hehun.php直接实例化控制器** - 不符合MVC规范
9. **后端Paipan.php变量语法错误** - 第1353行变量缺少$符号
10. **前端Hehun.vue v-html XSS风险** - 第103、109行使用v-html渲染服务器内容

#### 🟡 中优先级（体验问题）
1. **前端管理端页面使用模拟数据** - KnowledgeManage.vue/SEOManage.vue/SEOStats.vue/ShenshaManage.vue使用硬编码模拟数据
2. **前端分页逻辑不完整** - 多个管理端页面分页组件存在但数据未按分页切片
3. **后端缺少导入语句** - AdminAuth.php使用Db类但未导入
4. **前端空值检查缺失** - Bazi.vue/Tarot.vue/Liuyao.vue多处深层嵌套属性访问缺少空值检查

#### 🟢 低优先级（优化问题）
1. **前端硬编码值** - 积分值、分页大小等硬编码在代码中
2. **后端测试代码** - SmsService.php测试模式验证码硬编码为123456

### 已修复/已不存在的问题
1. **前端Tarot.vue未使用变量** - 已不存在
2. **后端Admin.php统计逻辑错误** - 已修复
3. **后端Content.php SQL注入防护** - 已添加preg_replace过滤

### 待处理统计
- 高优先级: 10个新问题
- 中优先级: 4个新问题
- 低优先级: 2个新问题

---

[历史记录已归档...]
