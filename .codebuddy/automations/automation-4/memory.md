# 待办处理执行器 - 执行记录

## 2026-03-16 执行记录（第十五次）

### 本次处理任务
- **任务**: 修复后端SQL注入安全风险
- **优先级**: 高优先级（安全性问题）

### 修复内容
**问题**: 多个后端控制器中存在SQL注入风险，keyword/phone等参数直接拼接到SQL查询中。

**修复的文件**:

1. **backend/app/controller/Admin.php - exportUsers方法** (第819-826行)
   - 添加了 `preg_replace('/[%_\\\\]/', '', $keyword)` 过滤特殊字符
   - 改用 `whereLike('nickname|phone', '%' . $keyword . '%')` 参数绑定查询
   - 防止SQL注入攻击

2. **backend/app/controller/AdminSms.php - getRecords方法** (第165-167行)
   - 添加了 `preg_replace('/[%_\\\\]/', '', $phone)` 过滤特殊字符
   - 改用 `whereLike('phone', '%' . $phone . '%')` 参数绑定查询
   - 防止SQL注入攻击

3. **backend/app/controller/SiteContent.php - getContentList方法** (第93-95行)
   - 添加了 `preg_replace('/[%_\\\\]/', '', $key)` 过滤特殊字符
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
$phone = preg_replace('/[%_\\\\]/', '', $phone);
$query->whereLike('phone', '%' . $phone . '%');

$keyword = preg_replace('/[%_\\\\]/', '', $keyword);
$query->whereLike('nickname|phone', '%' . $keyword . '%');
```

### 验证结果
- 代码语法正确，无lint错误
- 使用ThinkPHP的whereLike方法进行参数绑定，防止SQL注入
- 统一使用preg_replace过滤SQL特殊字符（%, _, \\）
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

## 2026-03-16 执行记录（第十四次）

### 本次处理任务
- **任务**: 修复后端Admin.php返回格式不统一问题
- **优先级**: 高优先级（代码规范问题）

### 修复内容
**问题**: backend/app/controller/Admin.php中混用json()和$this->success()/$this->error()两种返回方式，导致API返回格式不统一。

**修复的文件**: backend/app/controller/Admin.php

**修改的方法** (共9个方法):
1. **baziRecords()** - 获取八字记录列表
2. **deleteBaziRecord()** - 删除八字记录  
3. **pointsRecords()** - 获取积分记录
4. **adjustPoints()** - 调整用户积分
5. **feedbackList()** - 获取反馈列表
6. **replyFeedback()** - 回复反馈
7. **getSettings()** - 获取系统设置
8. **saveSettings()** - 保存系统设置
9. **operationLogs()** - 获取操作日志列表

**修改前**:
```php
return json([
    'code' => 200,
    'data' => $data
]);
return json(['code' => 500, 'message' => '错误信息']);
```

**修改后**:
```php
return $this->success($data, '获取成功');
return $this->error('错误信息', 500);
```

### 验证结果
- 代码语法正确，无lint错误
- 所有API返回格式现在统一使用BaseController的方法
- 与dashboard()、users()等方法保持一致

### 待办状态更新
- 已将待办项从"待处理项目"移到"已完成项目"
  - 后端Admin.php返回格式不统一
- 更新了TODO.md文件

### 剩余待处理任务（高优先级）
代码逻辑问题（高优先级）:
1. [高] 后端Content.php返回格式不统一
2. [高] 后端AiAnalysis.php返回格式不统一
3. [高] 后端Admin.php SQL注入风险
4. [高] 后端AiAnalysis.php缺少输入长度限制
5. [高] 前端管理端页面API调用缺失

UI问题:
- 18+个待处理的UI样式统一问题

---

## 历史执行记录

[... 保留之前的执行记录 ...]
