# 待办处理执行器 - 执行记录

## 2026-03-16 执行记录（第十六次）

### 本次处理任务
- **任务**: 修复后端Admin.php统计逻辑错误
- **优先级**: 高优先级（功能性问题）

### 修复内容
**问题**: backend/app/controller/Admin.php第317行userDetail()方法中塔罗占卜统计错误地使用了DailyFortune模型，应该使用TarotRecord模型。

**修复的文件**: backend/app/controller/Admin.php

**修改前**:
```php
// 添加统计数据
$user['bazi_count'] = BaziRecord::where('user_id', $id)->count();
$user['tarot_count'] = DailyFortune::where('user_id', $id)->count();  // 错误：统计的是每日运势
```

**修改后**:
```php
// 添加统计数据
$user['bazi_count'] = BaziRecord::where('user_id', $id)->count();
$user['tarot_count'] = TarotRecord::where('user_id', $id)->count();  // 正确：统计塔罗占卜记录
```

### 验证结果
- 代码语法正确，无lint错误
- TarotRecord模型已正确导入
- 修复后用户详情页的塔罗占卜统计将准确显示用户的塔罗占卜次数

### 待办状态更新
- 已将待办项从"待处理项目"移到"已完成项目"
  - 后端Admin.php统计逻辑错误（userDetail方法）
- 更新了TODO.md文件

### 剩余待处理任务（高优先级）
代码逻辑问题（高优先级）:
1. [高] 后端Admin.php缺少Db类导入（第903行、第939行）
2. [高] 后端AdminAuth.php硬编码管理员凭据
3. [高] 后端AdminAuth.php JWT密钥硬编码
4. [高] 前端Bazi.vue isCurrentDaYun函数硬编码年龄
5. [高] 前端Tarot.vue API错误处理不完整

UI问题:
- 18+个待处理的UI样式统一问题

---

## 2026-03-16 执行记录（第十五次）

### 本次处理任务
- **任务**: 修复后端SQL注入安全风险
- **优先级**: 高优先级（安全性问题）

### 修复内容
**问题**: 多个后端控制器中存在SQL注入风险，keyword/phone等参数直接拼接到SQL查询中。

**修复的文件**:

1. **backend/app/controller/Admin.php - exportUsers方法** (第819-826行)
   - 添加了 `preg_replace('/[%_\\]/', '', $keyword)` 过滤特殊字符
   - 改用 `whereLike('nickname|phone', '%' . $keyword . '%')` 参数绑定查询
   - 防止SQL注入攻击

2. **backend/app/controller/AdminSms.php - getRecords方法** (第165-167行)
   - 添加了 `preg_replace('/[%_\\]/', '', $phone)` 过滤特殊字符
   - 改用 `whereLike('phone', '%' . $phone . '%')` 参数绑定查询
   - 防止SQL注入攻击

3. **backend/app/controller/SiteContent.php - getContentList方法** (第93-95行)
   - 添加了 `preg_replace('/[%_\\]/', '', $key)` 过滤特殊字符
   - 改用 `whereLike('key', '%' . $key . '%')` 参数绑定查询
   - 防止SQL注入攻击

**修改前**:
```php
// 存在SQL注入风险
$query->where('phone', 'like', "%{$phone}%");
$query->where(function ($q) use ($keyword) {
    $q->where('nickname', 'like', "%{$keyword}%")
      ->whereOr('phone', 'like', "%{$keyword}%");
});
```

**修改后**:
```php
// 安全的参数绑定查询
$phone = preg_replace('/[%_\\]/', '', $phone);
$query->whereLike('phone', '%' . $phone . '%');

$keyword = preg_replace('/[%_\\]/', '', $keyword);
$query->whereLike('nickname|phone', '%' . $keyword . '%');
```

### 验证结果
- 代码语法正确，无lint错误
- 使用ThinkPHP的whereLike方法进行参数绑定，防止SQL注入
- 统一使用preg_replace过滤SQL特殊字符（%, _, \）
- 与其他已修复的查询（如Content.php、Upload.php）保持一致

### 待办状态更新
- 已将3个相关待办项从"待处理项目"移到"已完成项目"
  - 后端Admin.php users方法SQL注入风险
  - 后端AdminSms.php SQL注入风险
  - 后端SiteContent.php SQL注入风险
- 更新了TODO.md文件

### 剩余待处理任务（高优先级）
代码逻辑问题（高优先级）:
1. [高] 后端Hehun.php buildReportHtml方法XSS安全风险
2. [高] 后端Liuyao.php qiGua方法缺少事务处理
3. [高] 前端Hehun.vue JSON解析缺少错误处理
4. [高] 后端AiAnalysis.php缺少输入长度限制

UI问题:
- 18+个待处理的UI样式统一问题

---

## 历史执行记录

[... 保留之前的执行记录 ...]
