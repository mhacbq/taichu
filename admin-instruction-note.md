# 两套管理端整合方案

## 整合时间
2026-03-20

## 分析结果

### frontend/src/views/admin 下的文件清单（共18个）
1. AlmanacManage.vue → admin/src/views/content/almanac.vue（已有）
2. AnticheatManage.vue → admin/src/views/anticheat/events.vue（已有）
3. Config.vue → admin/src/views/system/settings.vue（已有）
4. ContentRecords.vue → 需要新增到 admin/src/views/content/
5. Dashboard.vue → admin/src/views/dashboard/index.vue（已有）
6. FeedbackManage.vue → admin/src/views/feedback/list.vue（已有）
7. KnowledgeManage.vue → admin/src/views/site-content/knowledge.vue（已有）
8. OrderManage.vue → admin/src/views/payment/orders.vue（已有）
9. PackageManage.vue → admin/src/views/payment/vip-packages.vue（已有）
10. PointsRecords.vue → admin/src/views/points/records.vue（已有）
11. PointsRules.vue → admin/src/views/points/rules.vue（已有）
12. SEOManage.vue → admin/src/views/site-content/seo.vue（已有）
13. SEOStats.vue → 需要新增到 admin/src/views/site-content/
14. ShenshaManage.vue → admin/src/views/content/shensha.vue（已有）
15. SystemLogs.vue → admin/src/views/log/api.vue, operation.vue（已有）
16. SystemTools.vue → admin/src/views/system/notice.vue, sensitive.vue（已有）
17. TaskManage.vue → admin/src/views/task/list.vue（已有）
18. UserManage.vue → admin/src/views/user/list.vue（已有）

## 整合策略

### 1. 功能对比与保留策略
对于已有对应文件的，进行功能对比：
- 如果 admin 下的文件功能更完善，保留 admin 的
- 如果 frontend/src/views/admin 下的文件有独特功能，将其合并到 admin 对应文件中

### 2. 新增文件
- ContentRecords.vue → 复制到 admin/src/views/content/records.vue
- SEOStats.vue → 复制到 admin/src/views/site-content/seo-stats.vue

### 3. 废弃目录
整合完成后，删除 frontend/src/views/admin 目录

## 执行步骤
1. 对比每个重复文件的功能差异
2. 将 admin 缺失的文件（ContentRecords.vue, SEOStats.vue）复制过去
3. 合并有差异的功能
4. 更新路由配置
5. 删除 frontend/src/views/admin 目录
6. 提交到 Git