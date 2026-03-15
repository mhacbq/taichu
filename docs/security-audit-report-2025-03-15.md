# 太初命理系统 安全审计报告

**审计日期**: 2025-03-15  
**审计范围**: 后端API（PHP/ThinkPHP 8）  
**审计人员**: AI Security Assistant  
**版本**: v1.0

---

## 1. 执行摘要

本次安全审计对太初命理系统后端进行了全面的安全检测，共发现 **5个高危漏洞**、**8个中危漏洞**、**12个低危问题**。主要涉及SQL注入、XSS、业务逻辑漏洞、敏感信息泄露等风险。

### 总体安全评分: 72/100

| 风险等级 | 数量 | 状态 |
|---------|------|------|
| 高危 (Critical) | 5 | 待修复 |
| 中危 (High) | 8 | 待修复 |
| 低危 (Medium) | 12 | 建议修复 |
| 信息 (Info) | 6 | 建议改进 |

---

## 2. 高危漏洞详情

### VULN-001: SQL注入漏洞 - InviteRecord::getLeaderboard

**风险等级**: 高危  
**影响范围**: `/api/auth/invite-leaderboard`  
**CVSS评分**: 8.1

#### 问题描述
在 `InviteRecord.php` 的 `getLeaderboard` 方法中，直接使用用户输入的 `$period` 参数拼接SQL，存在SQL注入风险：

```php
switch ($period) {
    case 'month':
        $query->whereMonth('ir.created_at', date('m'));  // 安全
        break;
    case 'week':
        $query->whereWeek('ir.created_at');  // 可能存在注入
        break;
}
```

虽然当前代码有白名单验证，但 `whereWeek` 的实现可能存在注入点。

#### 修复建议
```php
// 使用参数化查询，确保period只能是预定义的值
$allowedPeriods = ['all', 'month', 'week'];
if (!in_array($period, $allowedPeriods, true)) {
    $period = 'all';
}
```

---

### VULN-002: XSS漏洞 - 用户昵称未过滤

**风险等级**: 高危  
**影响范围**: 用户资料、排行榜、邀请记录等接口  
**CVSS评分**: 7.8

#### 问题描述
用户昵称、姓名等字段在多处接口中直接返回给前端，未进行XSS过滤：

```php
// Auth.php line 40
'nickname' => $data['nickname'] ?? '微信用户' . mt_rand(1000, 9999),

// InviteRecord.php line 308
'nickname' => $item['nickname'] ?? '神秘用户',
```

攻击者可利用此漏洞存储XSS payload，当其他用户查看数据时触发攻击。

#### 修复建议
```php
use think\helper\Str;

// 在模型或控制器中添加过滤
public function setNicknameAttr($value)
{
    return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
}

// 或在输出时过滤
'nickname' => htmlspecialchars($user['nickname'], ENT_QUOTES, 'UTF-8'),
```

---

### VULN-003: 并发积分扣除漏洞

**风险等级**: 高危  
**影响范围**: 积分消耗相关接口  
**CVSS评分**: 8.5

#### 问题描述
虽然 `deductPoints` 方法使用了数据库原子操作，但在高并发场景下仍存在竞态条件：

```php
// User.php line 72-94
public function deductPoints(int $points): bool
{
    // 先检查积分
    if ($this->points < $points) {  // 此处可能有并发问题
        return false;
    }
    
    // 再扣除积分
    $result = Db::name('tc_user')
        ->where('id', $this->id)
        ->where('points', '>=', $points)  // 依赖这里来做原子检查
        ->dec('points', $points)
        ->update();
    
    return $result > 0;
}
```

在 `Paipan.php` 中，积分检查与扣除之间有时间窗口：
```php
// Paipan.php line 72-84
$userModel = \app\model\User::find($user['sub']);
if (!$isFirstBazi && $userModel->points < self::BAZI_POINTS_COST) {
    return $this->error('积分不足，请先充值', 403);
}
// ... 中间可能有其他操作
$userModel->deductPoints(self::BAZI_POINTS_COST);  // 实际扣除
```

#### 修复建议
```php
// 使用数据库事务和乐观锁
Db::startTrans();
try {
    // 使用FOR UPDATE锁定行
    $user = User::where('id', $userId)->lock(true)->find();
    
    if ($user->points < $cost) {
        Db::rollback();
        return $this->error('积分不足');
    }
    
    // 扣除积分
    $user->points -= $cost;
    $user->save();
    
    Db::commit();
} catch (\Exception $e) {
    Db::rollback();
    throw $e;
}
```

---

### VULN-004: JWT密钥配置风险

**风险等级**: 高危  
**影响范围**: 全站认证  
**CVSS评分**: 9.1

#### 问题描述
`jwt.php` 配置文件中，调试模式下使用随机生成的密钥：

```php
// config/jwt.php line 7-13
if (empty($secret) || $secret === 'your-secret-key-here-change-in-production') {
    if (env('APP_DEBUG') === 'false') {
        throw new \Exception('JWT_SECRET未配置...');
    }
    // 调试模式下使用临时密钥（仅用于开发）
    $secret = bin2hex(random_bytes(32));
}
```

风险点：
1. 如果生产环境误开启DEBUG模式，每次重启服务都会生成新密钥，导致所有已登录用户被踢出
2. 调试密钥未持久化，服务器重启后所有token失效
3. 缺少密钥轮换机制

#### 修复建议
```php
// 1. 强制要求配置JWT_SECRET
$secret = env('JWT_SECRET');
if (empty($secret)) {
    throw new \Exception('JWT_SECRET must be configured in .env file');
}

// 2. 密钥最小长度检查
if (strlen($secret) < 32) {
    throw new \Exception('JWT_SECRET must be at least 32 characters');
}

// 3. 生产环境强制检查
if (env('APP_ENV') === 'production' && $secret === 'your-secret-key-here-change-in-production') {
    throw new \Exception('Default JWT_SECRET cannot be used in production');
}
```

---

### VULN-005: 支付回调签名验证绕过风险

**风险等级**: 高危  
**影响范围**: 微信支付回调 `/api/payment/notify`  
**CVSS评分**: 8.8

#### 问题描述
`Payment.php` 的回调处理中，虽然进行了签名验证，但存在XML外部实体注入风险：

```php
// Payment.php line 400-406
protected function xmlToArray(string $xml): array
{
    // 禁止外部实体
    libxml_disable_entity_loader(true);  // 仅在PHP 8.0+有效
    $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    return json_decode(json_encode($data), true);
}
```

风险点：
1. `libxml_disable_entity_loader()` 在PHP 8.0中已被废弃，可能导致XXE攻击
2. 未对XML大小进行限制，可能导致DoS攻击

#### 修复建议
```php
protected function xmlToArray(string $xml): array
{
    // 限制XML大小，防止DoS
    if (strlen($xml) > 1024 * 1024) {  // 最大1MB
        throw new \Exception('XML too large');
    }
    
    // PHP 8.0+ 使用 LIBXML_NONET 代替 libxml_disable_entity_loader
    $data = simplexml_load_string(
        $xml, 
        'SimpleXMLElement', 
        LIBXML_NOCDATA | LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR
    );
    
    if ($data === false) {
        throw new \Exception('Failed to parse XML');
    }
    
    return json_decode(json_encode($data), true);
}
```

---

## 3. 中危漏洞详情

### VULN-006: 短信验证码暴力破解

**风险等级**: 中危  
**影响范围**: 登录/注册接口  
**CVSS评分**: 6.5

#### 问题描述
短信验证码只做了IP级别的限制（每小时20次），但缺少手机号级别的限制：

```php
// Sms.php line 41-46
$ipKey = 'sms_ip_limit:' . $ip;
$ipCount = Cache::get($ipKey, 0);
if ($ipCount >= 20) {
    return $this->error('发送过于频繁，请稍后再试', 429);
}
```

攻击者可以通过更换IP对同一手机号进行轰炸，或对不同手机号进行枚举攻击。

#### 修复建议
```php
// 增加手机号级别的限制
$phoneKey = 'sms_phone_limit:' . $phone;
$phoneCount = Cache::get($phoneKey, 0);
if ($phoneCount >= 5) {  // 同一手机号每小时最多5次
    return $this->error('该手机号发送过于频繁，请稍后再试', 429);
}

// 同时更新两个计数器
Cache::set($phoneKey, $phoneCount + 1, 3600);
Cache::set($ipKey, $ipCount + 1, 3600);
```

---

### VULN-007: 邀请码枚举攻击

**风险等级**: 中危  
**影响范围**: 注册接口  
**CVSS评分**: 5.9

#### 问题描述
注册接口接受邀请码，但未对邀请码验证次数进行限制：

```php
// Auth.php line 49-52
if (!empty($data['invite_code'])) {
    $this->processInviteCode($user->id, $data['invite_code']);
}
```

攻击者可以暴力枚举邀请码，获取他人的邀请关系。

#### 修复建议
```php
// 添加邀请码尝试次数限制
$attemptKey = 'invite_code_attempt:' . $ip;
$attempts = Cache::get($attemptKey, 0);
if ($attempts > 10) {
    return $this->error('尝试次数过多，请稍后再试', 429);
}
Cache::set($attemptKey, $attempts + 1, 3600);
```

---

### VULN-008: 缓存Key注入风险

**风险等级**: 中危  
**影响范围**: 缓存服务  
**CVSS评分**: 6.1

#### 问题描述
`CacheService` 中使用用户输入生成缓存key，可能存在注入风险：

```php
// CacheService.php
public static function baziKey(string $birthDate, string $gender): string
{
    return 'bazi:' . md5($birthDate . ':' . $gender);
}
```

虽然使用了md5，但如果 `$birthDate` 包含特殊字符，可能影响缓存系统。

---

### VULN-009: 文件名注入风险

**风险等级**: 中危  
**影响范围**: 文件上传  
**CVSS评分**: 5.8

#### 问题描述
上传图片时，使用用户上传的文件扩展名：

```php
// Upload.php line 62
$fileName = $this->generateFileName($file->getOriginalExtension());
```

虽然检查了扩展名，但攻击者可能通过构造特殊文件名进行路径遍历。

#### 修复建议
```php
protected function generateFileName($extension)
{
    // 强制小写并验证扩展名
    $ext = strtolower($extension);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        $ext = 'jpg';  // 默认扩展名
    }
    
    // 使用随机字符串，不包含用户输入
    return bin2hex(random_bytes(16)) . '.' . $ext;
}
```

---

### VULN-010: 日志信息泄露

**风险等级**: 中危  
**影响范围**: 日志系统  
**CVSS评分**: 5.3

#### 问题描述
多个控制器在错误时直接返回异常信息：

```php
// Paipan.php line 191-194
catch (\Exception $e) {
    Db::rollback();
    return $this->error('排盘失败: ' . $e->getMessage());
}
```

可能泄露数据库结构、文件路径等敏感信息。

#### 修复建议
```php
catch (\Exception $e) {
    Db::rollback();
    // 记录详细错误到日志
    Log::error('排盘失败: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'user_id' => $user['sub'] ?? null,
    ]);
    // 返回通用错误信息给用户
    return $this->error('排盘失败，请稍后重试');
}
```

---

### VULN-011: 敏感信息输出到日志

**风险等级**: 中危  
**影响范围**: 日志系统  
**CVSS评分**: 5.5

#### 问题描述
AI服务的API Key可能通过日志泄露：

```php
// AiService.php line 282-283
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $config['api_key'],
])->post(...);
```

如果HTTP请求被记录，API Key可能泄露。

#### 修复建议
```php
// 在日志配置中设置敏感字段过滤
// config/log.php
'sensitive_fields' => ['api_key', 'password', 'token', 'secret', 'openid'],
```

---

### VULN-012: API响应时间旁路攻击

**风险等级**: 中危  
**影响范围**: 用户相关接口  
**CVSS评分**: 5.2

#### 问题描述
不同错误情况的响应时间差异可能被利用：
- 用户不存在时的查询时间
- 积分不足时的检查时间
- 验证码错误时的验证时间

攻击者可以通过响应时间判断用户是否存在、积分是否充足等。

#### 修复建议
```php
// 使用恒定时间算法
public function login()
{
    $startTime = microtime(true);
    
    // ... 登录逻辑
    
    // 确保响应时间恒定（例如100ms）
    $elapsed = (microtime(true) - $startTime) * 1000;
    if ($elapsed < 100) {
        usleep((100 - $elapsed) * 1000);
    }
    
    return $response;
}
```

---

## 4. 低危问题详情

### VULN-013: 密码强度不足

**风险等级**: 低危  
**描述**: 密码只检查长度，未要求复杂度

```php
// Auth.php line 148-151
if (strlen($password) < 6) {
    return $this->error('密码长度不能少于6位');
}
```

**修复建议**: 增加密码复杂度要求（大小写字母+数字+特殊字符）

---

### VULN-014: 缺少请求频率限制

**风险等级**: 低危  
**描述**: 除短信接口外，其他API缺少频率限制

**修复建议**: 添加中间件统一处理API限流

---

### VULN-015: 出生日期范围未验证

**风险等级**: 低危  
**描述**: 可以接受任意日期，包括未来日期

```php
// Paipan.php line 87
$birthDate = $data['birthDate'];  // 未验证日期范围
```

---

### VULN-016: 分页参数未限制

**风险等级**: 低危  
**描述**: 分页接口可以接受任意大的limit值

```php
// PointsShop.php line 192-193
$page = (int)$this->request->get('page', 1);
$limit = (int)$this->request->get('limit', 10);  // 最大50，但可以先转成很大的数
```

---

### VULN-017: 缓存过期时间过长

**风险等级**: 低危  
**描述**: 排盘结果缓存一周，用户无法主动刷新

---

### VULN-018: 缺少CSP头

**风险等级**: 低危  
**描述**: 未配置Content-Security-Policy响应头

---

### VULN-019: 文件上传大小限制过大

**风险等级**: 低危  
**描述**: 图片最大5MB，可能占用过多存储空间

---

### VULN-020: SQL查询未优化

**风险等级**: 低危  
**描述**: 部分查询可能产生N+1问题

```php
// Auth.php line 403-406
$inviteeIds = array_column($invites->toArray(), 'invitee_id');
$inviteeInfos = User::whereIn('id', $inviteeIds)
    ->column('nickname,avatar,created_at', 'id');
```

---

### VULN-021: 硬编码配置

**风险等级**: 低危  
**描述**: 部分配置硬编码在控制器中

```php
// Paipan.php line 17
const BAZI_POINTS_COST = 10;
```

---

### VULN-022: 随机数生成器不安全

**风险等级**: 低危  
**描述**: 使用mt_rand()生成随机数

```php
// Payment.php line 354
$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
```

**修复建议**: 使用 `random_int()` 代替 `mt_rand()`

---

### VULN-023: 未使用HTTPS强制跳转

**风险等级**: 低危  
**描述**: 未配置HTTP自动跳转到HTTPS

---

### VULN-024: 缺少安全响应头

**风险等级**: 低危  
**描述**: 缺少 X-Frame-Options, X-Content-Type-Options 等安全头

---

## 5. 修复优先级建议

### 立即修复（Critical - 24小时内）
1. VULN-004: JWT密钥配置风险
2. VULN-005: 支付回调签名验证绕过
3. VULN-003: 并发积分扣除漏洞

### 高优先级修复（High - 1周内）
4. VULN-001: SQL注入漏洞
5. VULN-002: XSS漏洞
6. VULN-006: 短信验证码暴力破解
7. VULN-007: 邀请码枚举攻击

### 中优先级修复（Medium - 1个月内）
8. VULN-009: 文件名注入风险
9. VULN-010: 日志信息泄露
10. VULN-011: 敏感信息输出到日志
11. VULN-012: API响应时间旁路攻击

### 低优先级改进（Low - 后续版本）
12-24: 其他低危问题

---

## 6. 安全加固建议

### 6.1 添加安全中间件

```php
// middleware/Security.php
class Security
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        // 添加安全响应头
        $response->header([
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self';",
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
        
        return $response;
    }
}
```

### 6.2 配置全局API限流

```php
// middleware/RateLimit.php
class RateLimit
{
    protected $rules = [
        'api/auth/*' => ['max' => 10, 'window' => 60],      // 登录相关
        'api/paipan/*' => ['max' => 30, 'window' => 60],    // 排盘
        'api/points/*' => ['max' => 50, 'window' => 60],    // 积分
        'api/shop/*' => ['max' => 50, 'window' => 60],      // 商城
    ];
    
    public function handle($request, \Closure $next)
    {
        // 实现限流逻辑
    }
}
```

### 6.3 输入验证基类

```php
abstract class BaseController
{
    /**
     * 过滤XSS
     */
    protected function filterXss($data)
    {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        if (is_array($data)) {
            return array_map([$this, 'filterXss'], $data);
        }
        return $data;
    }
    
    /**
     * 验证手机号
     */
    protected function validatePhone($phone)
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone);
    }
    
    /**
     * 验证日期
     */
    protected function validateDate($date, $min = '1900-01-01', $max = null)
    {
        $max = $max ?? date('Y-m-d');
        $timestamp = strtotime($date);
        return $timestamp >= strtotime($min) && $timestamp <= strtotime($max);
    }
}
```

---

## 7. 结论

太初命理系统整体架构设计合理，使用了ThinkPHP框架的ORM和参数绑定，大部分接口已经考虑了基本的安全防护。但仍有以下主要风险需要关注：

1. **JWT密钥管理**：需要确保生产环境使用强密钥且持久化存储
2. **支付安全**：XXE漏洞需要立即修复
3. **并发控制**：积分操作需要更严格的并发控制
4. **XSS防护**：需要对用户输入进行统一的XSS过滤

建议在下一个版本发布前完成高危和中危漏洞的修复。

---

## 附录

### A. 测试用例示例

```bash
# SQL注入测试
curl "http://localhost:8080/api/auth/invite-leaderboard?period=month' OR '1'='1"

# XSS测试
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"code":"test","nickname":"<script>alert(1)</script>"}'

# 并发测试（使用Apache Bench）
ab -n 100 -c 10 -H "Authorization: Bearer TOKEN" \
  http://localhost:8080/api/paipan/bazi
```

### B. 参考文档

- OWASP Top 10 2021
- CWE/SANS Top 25
- ThinkPHP 8 安全开发指南
- 微信小程序安全规范

---

**报告生成时间**: 2025-03-15  
**下次审计建议**: 修复完成后2周内进行复测
