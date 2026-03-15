# 太初命理网站 - 功能缺失与逻辑漏洞报告

**报告生成时间**: 2026-03-15  
**检查范围**: 前端 + 后端完整代码库  
**严重程度**: 🔴 严重  🟡 中等  🟢 轻微

---

## 一、功能缺失（重要）

### 🔴 1. 六爻占卜功能完全缺失

**状态**: ❌ 未实现  
**影响**: 用户无法使用六爻占卜功能

**详情**:
- 项目中没有任何六爻相关的代码文件
- 没有六爻的数据库表结构
- 没有六爻的后端API
- 前端页面和路由完全缺失

**建议**:
1. 创建六爻数据库表（`liuyao_records`）
2. 实现后端六爻控制器（`Liuyao.php`）
3. 创建前端六爻页面（`Liuyao.vue`）
4. 添加前端路由 `/liuyao`
5. 在首页和导航添加入口

---

### 🔴 2. 八字合婚功能前端缺失

**状态**: ⚠️ 后端已实现，前端缺失  
**影响**: 用户无法使用合婚功能

**详情**:
- ✅ 后端API已完整实现 (`backend/app/controller/Hehun.php`)
- ✅ 数据库表已创建 (`hehun_records`)
- ✅ API路由已配置 (`/api/hehun/*`)
- ❌ 前端没有合婚页面
- ❌ 前端路由没有 `/hehun`
- ❌ 首页没有合婚入口
- ❌ 导航栏没有合婚选项

**后端已实现的功能**:
```
GET  /api/hehun/pricing      - 获取定价配置
POST /api/hehun/calculate    - 合婚计算（需要登录）
GET  /api/hehun/history      - 合婚历史（需要登录）
POST /api/hehun/export       - 导出报告（需要登录）
```

**建议**:
1. 创建前端合婚页面 `Hehun.vue`
2. 添加前端路由 `/hehun`
3. 在首页添加合婚快捷入口
4. 在导航栏添加合婚链接

---

### 🟡 3. 取名建议功能前端缺失

**状态**: ⚠️ 后端已实现，前端缺失  

**后端路由**:
```
POST /api/qiming/suggest    - 取名建议
GET  /api/qiming/history    - 取名历史
```

**缺失**: 前端页面和入口

---

### 🟡 4. 吉日查询功能前端缺失

**状态**: ⚠️ 后端已实现，前端缺失  

**后端路由**:
```
POST /api/jiri/query        - 吉日查询
```

**缺失**: 前端页面和入口

---

## 二、逻辑漏洞（安全相关）

### 🔴 1. 积分扣除竞态条件漏洞

**位置**: `backend/app/controller/Paipan.php`, `Tarot.php`, `Hehun.php` 等  
**严重程度**: 高  
**风险**: 用户可能通过并发请求重复获取服务只扣一次积分

**问题代码** (Paipan.php 第 71-87 行):
```php
// 检查用户积分
$userModel = \app\model\User::find($user['sub']);
if (!$userModel) {
    return $this->error('用户不存在', 404);
}

// ... 其他逻辑 ...

// 非首次排盘需要检查积分
if (!$isFirstBazi && $userModel->points < self::BAZI_POINTS_COST) {
    return $this->error('积分不足，请先充值', 403);
}
```

**漏洞分析**:
1. 检查积分和扣除积分之间有时间窗口
2. 并发请求可能同时通过检查，但只扣除一次积分
3. 没有使用数据库行锁保护积分扣除操作

**修复建议**:
```php
// 使用数据库事务和行锁
Db::startTrans();
try {
    // 使用行锁查询用户
    $userModel = \app\model\User::where('id', $user['sub'])->lock(true)->find();
    
    if ($userModel->points < $cost) {
        Db::rollback();
        return $this->error('积分不足');
    }
    
    // 扣除积分
    $userModel->points -= $cost;
    $userModel->save();
    
    // 记录积分流水
    PointsRecord::create([...]);
    
    Db::commit();
} catch (\Exception $e) {
    Db::rollback();
    return $this->error('操作失败');
}
```

---

### 🟡 2. 限流中间件可以绕过

**位置**: `backend/app/middleware/RateLimit.php`  
**严重程度**: 中  
**风险**: 用户可以绕过请求频率限制

**问题**:
1. 限流基于缓存，重启后重置
2. 用户可以通过更换IP绕过（使用代理）
3. 缓存键生成方式可能被预测

**当前实现**:
```php
protected function getClientId($request): string
{
    if ($request->user && isset($request->user['sub'])) {
        return 'user_' . $request->user['sub'];
    }
    $ip = $request->ip();
    return 'ip_' . md5($ip);
}
```

**建议**:
1. 对于登录接口，同时限制手机号
2. 添加图形验证码防止自动化攻击
3. 将限流记录持久化到数据库或Redis

---

### 🟡 3. 手机号登录缺少图形验证码

**位置**: `backend/app/controller/Auth.php`  
**严重程度**: 中  
**风险**: 暴力破解手机号验证码

**问题**:
1. 只有短信频率限制（每分钟1条）
2. 验证码只有6位数字，可被暴力破解
3. 测试环境使用固定验证码 `123456`

**建议**:
1. 添加图形验证码
2. 增加验证码错误次数限制
3. 生产环境移除测试验证码

---

### 🟢 4. JWT Token 没有黑名单机制

**位置**: `backend/app/middleware/Auth.php`  
**严重程度**: 低  
**风险**: 用户退出后Token仍然有效

**问题**:
- Token 过期前无法强制失效
- 用户退出登录后，之前颁发的Token仍然可以使用
- 用户修改密码后，旧Token仍然有效

**建议**:
1. 添加Token黑名单（Redis存储已失效的Token）
2. 或在Token中加入签发时间，用户数据中添加密码修改时间，进行比较

---

### 🟢 5. 部分接口没有认证但返回敏感信息

**位置**: 多个公开接口  
**严重程度**: 低

**需要检查的接口**:
- `/api/stats/home` - 是否返回敏感统计数据
- `/api/config/client` - 配置信息是否敏感
- `/api/tarot/share` - 分享内容是否越权

---

## 三、功能逻辑问题

### 🟡 1. 首页功能入口不完整

**当前首页显示的功能**:
| 功能 | 状态 | 入口位置 |
|------|------|----------|
| 八字排盘 | ✅ | Hero区域 + QuickActions |
| 塔罗占卜 | ✅ | Hero区域 + QuickActions |
| 每日运势 | ✅ | Hero区域 + QuickActions |
| 八字合婚 | ❌ | **缺失** |
| 六爻占卜 | ❌ | **缺失** |
| 取名建议 | ❌ | **缺失** |
| 吉日查询 | ❌ | **缺失** |

**建议**: 在首页添加所有功能的入口

---

### 🟡 2. QuickActions 组件功能不全

**文件**: `frontend/src/components/QuickActions.vue`

**当前只有4个快捷入口**:
1. 八字排盘
2. 塔罗占卜
3. 每日运势
4. 个人中心

**建议添加**:
- 八字合婚
- 六爻占卜（实现后）
- 取名建议
- 吉日查询

---

### 🟢 3. 路由守卫缺少权限细化

**位置**: `frontend/src/router/index.js`

**问题**:
- 只有登录/未登录判断
- 没有VIP权限判断
- 没有功能开关判断

**建议**:
```javascript
// 添加权限元信息
{
  path: '/hehun',
  meta: {
    requiresAuth: true,
    requiresFeature: 'hehun_enabled',  // 功能开关
    minVipLevel: 0  // VIP等级要求
  }
}
```

---

## 四、前端技术问题

### 🟢 1. 没有统一的错误处理

**问题**:
- API错误处理分散在各个组件
- 没有全局错误边界
- 网络错误没有统一处理

**建议**:
1. 创建全局错误处理组件
2. 在axios拦截器中统一处理错误

---

### 🟢 2. 前端路由缺少懒加载

**位置**: `frontend/src/router/index.js`

**问题**:
```javascript
// 当前方式 - 一次性加载所有组件
import Home from '../views/Home.vue'
import Bazi from '../views/Bazi.vue'
// ...
```

**建议**:
```javascript
// 使用懒加载
const Home = () => import('../views/Home.vue')
const Bazi = () => import('../views/Bazi.vue')
```

---

## 五、数据库与后端问题

### 🟢 1. 部分字段没有索引

**需要检查的表**:
- `bazi_records` - user_id, created_at
- `tarot_records` - user_id, created_at
- `points_records` - user_id, type, created_at
- `hehun_records` - user_id, created_at

**建议**: 添加合适的索引优化查询

---

### 🟢 2. 定时任务日志记录不完善

**位置**: `backend/app/command/DailyTask.php`

**问题**:
- 定时任务执行结果没有详细日志
- 失败时没有告警机制

**建议**:
1. 添加详细的执行日志
2. 任务失败时发送通知（邮件/企业微信）

---

## 六、修复优先级建议

### 第一优先级（立即修复）
1. 🔴 积分扣除竞态条件漏洞
2. 🔴 实现六爻占卜功能
3. 🔴 实现合婚前端页面

### 第二优先级（本周修复）
4. 🟡 添加图形验证码
5. 🟡 完善首页功能入口
6. 🟡 实现取名建议前端
7. 🟡 实现吉日查询前端

### 第三优先级（本月修复）
8. 🟢 添加JWT黑名单机制
9. 🟢 优化前端路由懒加载
10. 🟢 完善数据库索引

---

## 七、已修复问题

✅ **CORS跨域问题** - 已修复  
✅ **前端代理配置** - 已修复  
✅ **部署文档** - 已添加  
✅ **定时任务** - 已添加

---

**报告生成人**: WorkBuddy AI  
**下次检查时间**: 建议修复后重新扫描
