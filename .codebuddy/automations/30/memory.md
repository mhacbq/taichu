# 网站逻辑检查任务执行记录

## 执行时间
2026-03-19 14:00 (本轮检查)

## 检查范围
基于 TODO.md B.30 章节关注点：新接口契约不一致、异常分支误扣费、初始化 SQL / 启动脚本冲突、落库或历史写入断裂。

## 本轮新增证据

### 1. 系统设置保存后缓存未清除
- 位置：`backend/app/controller/Admin.php` 第1285-1310行 `saveSettings()` 方法
- 根因：方法直接使用 `Db::table($configTable)` 更新数据库，而**未调用** `ConfigService::clearCache()`
- 对比：`ConfigService::set()` 方法（第50-60行）在更新成功后会自动清除缓存
- 影响：回读时 `ConfigService::get()` 命中 1 小时缓存，返回旧值
- 状态：**已闭环**（根因证据明确）

### 2. 合婚免费预览历史不闭环
- 位置：`backend/app/controller/Hehun.php` 第375-405行
- 根因：免费层（tier=free）直接返回结果，**未调用** `HehunRecord::createCompatible()` 保存记录
- 对比：付费层（tier=premium）在第498-516行会调用 `createCompatible` 保存记录
- 影响：免费预览后用户无法在历史记录中回看结果
- 状态：**已闭环**（代码逻辑确认）

### 3. 大运K线图异常分支扣费
- 位置：`backend/app/service/DayunFortuneService.php` 第100-188行
- 状态：代码层面**已修复**（先计算再扣费，异常时不扣费）
- TODO.md D 部分记录该问题已修复

## 确认的阻塞项（沿用）
1. `tc_daily_fortune` 缺 `lunar_date` 字段（表定义 vs 迁移文件/代码不一致）
2. `tc_bazi_record` 缺 `location` 字段（表定义 vs `Paipan.php` 使用不一致）

## 结论
- 本轮找到 2 个新的代码层面证据：系统设置缓存问题、合婚免费历史不闭环
- 大运K线图问题在代码层面已修复
- 阻塞项仍为数据库表结构缺失问题
