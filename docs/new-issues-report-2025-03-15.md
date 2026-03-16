# 太初命理系统新问题发现报告

**报告日期**: 2025-03-15  
**检查范围**: 全站功能、API接口、数据库、安全性  
**发现问题**: 12个

---

## 一、严重问题 (3个)

### ISSUE-001: 任务系统路由与控制器不匹配 🔴

**问题描述**: 
路由文件 `route/app.php` 中定义了 `PointsTask` 控制器，但实际创建的是 `Task` 控制器。

**路由定义**:
```php
Route::group('tasks', function () {
    Route::get('list', 'PointsTask/list');      // 路由指向 PointsTask
    Route::post('complete', 'PointsTask/complete');
    Route::get('my-tasks', 'PointsTask/myTasks');
});
```

**实际文件**: `backend/app/controller/Task.php`

**影响**: 任务系统API返回404错误

**修复建议**:
1. 将控制器重命名为 `PointsTask.php`，或
2. 修改路由指向 `Task` 控制器

```php
// 修复后的路由
Route::group('tasks', function () {
    Route::get('list', 'Task/getTasks');
    Route::post('claim', 'Task/claimReward');
    Route::post('checkin', 'Task/checkin');
    Route::get('checkin-history', 'Task/getCheckinHistory');
});
```

---

### ISSUE-002: 取名建议(Qiming)控制器缺失 🔴

**问题描述**:
前端API调用 `/api/qiming/suggest`，路由已定义，但控制器文件不存在。

**路由定义**:
```php
Route::group('qiming', function () {
    Route::post('suggest', 'Qiming/suggest');
    Route::get('history', 'Qiming/history');
});
```

**缺失文件**: `backend/app/controller/Qiming.php`

**影响**: 取名功能无法使用

**建议**: 需要创建 Qiming 控制器或移除相关路由和前端代码

---

### ISSUE-003: 吉日查询(Jiri)控制器缺失 🔴

**问题描述**:
前端API调用 `/api/jiri/query`，路由已定义，但控制器文件不存在。

**路由定义**:
```php
Route::group('jiri', function () {
    Route::post('query', 'Jiri/query');
});
```

**缺失文件**: `backend/app/controller/Jiri.php`

**影响**: 吉日查询功能无法使用

**建议**: 需要创建 Jiri 控制器或移除相关路由和前端代码

---

## 二、中等问题 (4个)

### ISSUE-004: 微信登录扫码功能为模拟实现 🟠

**问题描述**:
`frontend/src/views/Login.vue` 中的微信扫码登录只是模拟，没有真正调用微信OAuth API。

**问题代码**:
```javascript
// 开始微信扫码
const startWechatScan = () => {
  wechatScanning.value = true
  // 模拟扫码过程
  setTimeout(() => {
    handleWechatLogin()
  }, 2000)
}
```

**影响**: 用户无法真正使用微信扫码登录

**修复建议**:
1. 集成微信官方OAuth登录流程
2. 或使用微信开放平台SDK
3. 生成真实的带参数的二维码

---

### ISSUE-005: 排盘历史使用前端分页 🟠

**问题描述**:
`frontend/src/views/Profile.vue` 中的排盘历史采用前端分页，当数据量大时性能差。

**问题代码**:
```javascript
// 前端分页
const start = (baziCurrentPage.value - 1) * baziPageSize.value
const end = start + baziPageSize.value
baziHistory.value = allData.slice(start, end)
```

**影响**: 数据量大时页面卡顿，用户体验差

**修复建议**:
1. 后端API支持分页参数
2. 前端按需加载数据

```javascript
// 建议修改为后端分页
const loadBaziHistory = async () => {
  const baziRes = await getBaziHistory({
    page: baziCurrentPage.value,
    pageSize: baziPageSize.value
  })
}
```

---

### ISSUE-006: 后台管理数据为静态数据 🟠

**问题描述**:
`admin/src/views/dashboard/index.vue` 中的统计数据是写死的静态数据。

**问题代码**:
```javascript
const statistics = ref([
  { title: '总用户数', value: 12580, trend: 12.5, color: '#409eff', icon: 'UserFilled' },
  { title: '今日新增', value: 128, trend: 8.2, color: '#67c23a', icon: 'User' },
  // ... 静态数据
])
```

**影响**: 后台管理无法反映真实业务数据

**修复建议**:
1. 后端创建统计API
2. 前端调用真实数据

---

### ISSUE-007: 缺少支付回调路由配置 🟠

**问题描述**:
创建了 Alipay 控制器，但路由中没有配置支付宝回调接口。

**当前路由**:
```php
// 支付回调（不需要认证）
Route::post('api/payment/notify', 'Payment/notify');
```

**缺失**: 支付宝回调路由

**修复建议**:
```php
// 支付宝回调
Route::post('api/alipay/notify', 'Alipay/notify');
Route::get('api/alipay/return', 'Alipay/return');
```

---

## 三、体验问题 (3个)

### ISSUE-008: 塔罗历史存储在本地 🟡

**问题描述**:
塔罗占卜历史存储在 localStorage，换设备或清除缓存后数据丢失。

**问题代码**:
```javascript
// 加载本地塔罗历史
const loadTarotHistory = () => {
  const saved = JSON.parse(localStorage.getItem('tarot_saved') || '[]')
  tarotHistory.value = saved.slice(0, 10)
}
```

**修复建议**:
1. 后端创建塔罗历史表
2. 历史记录存储在服务器

---

### ISSUE-009: 缺少加载状态管理 🟡

**问题描述**:
多个页面同时发起请求时，没有统一的加载状态管理，可能导致多次显示loading。

**影响**: 用户体验不一致

**修复建议**:
1. 使用全局loading状态管理
2. 或使用请求拦截器统一处理

---

### ISSUE-010: 缺少错误边界处理 🟡

**问题描述**:
前端缺少错误边界(Error Boundary)，当组件报错时整个页面崩溃。

**修复建议**:
1. 创建 Vue 错误边界组件
2. 使用 try-catch 包裹关键逻辑

---

## 四、安全问题 (2个)

### ISSUE-011: 游客模式可直接访问首页 🟢

**问题描述**:
游客模式没有严格限制功能，某些需要登录的功能可能可以通过URL直接访问。

**修复建议**:
1. 前端路由守卫加强验证
2. 后端API统一检查登录状态

---

### ISSUE-012: 缺少请求频率限制 🟢

**问题描述**:
虽然已经配置了 RateLimit 中间件，但某些敏感接口（如发送验证码）可能需要更严格的限制。

**修复建议**:
1. 对敏感接口单独配置限流规则
2. 增加图形验证码防止滥用

---

## 五、问题汇总

| 等级 | 数量 | 问题 |
|------|------|------|
| 🔴 严重 | 3 | 路由不匹配、控制器缺失 |
| 🟠 中等 | 4 | 模拟登录、前端分页、静态数据、路由缺失 |
| 🟡 体验 | 3 | 本地存储、状态管理、错误处理 |
| 🟢 安全 | 2 | 游客模式、频率限制 |

---

## 六、修复优先级建议

### 第一优先级（本周内）
1. ISSUE-001: 修复任务系统路由
2. ISSUE-007: 添加支付宝回调路由

### 第二优先级（两周内）
3. ISSUE-004: 实现真实微信登录
4. ISSUE-005: 后端支持分页
5. ISSUE-006: 后台管理接入真实数据

### 第三优先级（月内）
6. ISSUE-002/003: 创建Qiming和Jiri控制器
7. ISSUE-008: 塔罗历史服务端存储
8. ISSUE-009/010/011/012: 体验和安全优化

---

## 七、新增控制器需求

### 需要创建的控制器

1. **PointsTask.php** (或修改Task.php)
   - 用于支持任务系统路由

2. **Qiming.php** (可选)
   - 取名建议功能
   - 如果暂时不需要，可移除路由

3. **Jiri.php** (可选)
   - 吉日查询功能
   - 如果暂时不需要，可移除路由

---

## 八、数据库表创建建议

### 塔罗历史表
```sql
CREATE TABLE tc_tarot_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question TEXT,
    cards JSON NOT NULL,
    interpretation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

---

报告生成完毕，建议优先处理严重问题，确保核心功能正常运行。
