# 系统配置管理功能 - 部署说明

## 功能概述

本次更新将支付配置、AI服务配置、推送服务配置、短信服务配置统一整合到数据库中，通过管理端进行统一管理，替换原有的硬编码配置方式。

## 1. 数据库迁移

### 执行步骤

在数据库服务器上执行以下SQL脚本：

```bash
mysql -u root -p taichu < database/20260320_create_system_configs_table.sql
```

### 表结构说明

新建表：`tc_system_configs`

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键ID |
| config_key | VARCHAR(100) | 配置键名 |
| config_value | TEXT | 配置值 |
| config_group | VARCHAR(50) | 配置分组 |
| config_type | VARCHAR(20) | 配置类型 |
| description | VARCHAR(255) | 配置描述 |
| is_sensitive | TINYINT | 是否敏感信息 |
| is_enabled | TINYINT | 是否启用 |
| sort_order | INT | 排序 |
| created_at | TIMESTAMP | 创建时间 |
| updated_at | TIMESTAMP | 更新时间 |

### 配置分组

- `payment`: 支付配置（微信支付、支付宝）
- `ai`: AI服务配置
- `push`: 推送服务配置
- `sms`: 短信服务配置

## 2. 后端更新

### 新增文件

1. **模型**: `backend/app/model/SystemConfig.php`
   - 统一配置读取接口
   - 支持敏感信息加密/解密
   - 兼容旧代码的支付配置获取

2. **控制器**: `backend/app/controller/admin/SystemConfig.php`
   - 配置列表查看
   - 配置保存（支持敏感信息处理）
   - 支付和AI配置测试
   - 配置导出功能

### 路由更新

已在 `backend/route/admin.php` 中新增以下路由：

- `GET /api/admin/system-config` - 获取配置列表
- `POST /api/admin/system-config/save` - 保存配置
- `POST /api/admin/system-config/test-payment` - 测试支付配置
- `POST /api/admin/system-config/test-ai` - 测试AI配置
- `GET /api/admin/system-config/export` - 导出配置

### 代码兼容性

为保证平滑迁移，新系统提供了兼容旧代码的方法：

```php
// 旧代码继续可用，自动从数据库读取
$config = PaymentConfig::getPaymentConfig();

// 新代码推荐方式
$config = SystemConfig::getGroupConfig('payment');
```

## 3. 管理端更新

### 新增文件

1. **API模块**: `admin/src/api/systemConfig.js`
   - 配置相关API调用方法
   - 配置字段定义和常量

2. **页面组件**: `admin/src/views/system/systemConfig.vue`
   - 支付配置管理（微信、支付宝）
   - AI服务配置管理
   - 推送服务配置管理
   - 短信服务配置管理
   - 配置测试功能
   - 配置导出功能

### 路由更新

已在 `admin/src/router/index.js` 中新增路由：

- 路径: `/system/system-config`
- 名称: `SystemConfig`
- 权限: `admin`

### 访问入口

管理端登录后：
1. 进入 `系统设置` 菜单
2. 点击 `系统配置` 子菜单

## 4. 配置项说明

### 支付配置

#### 微信支付
- 商户号
- 应用ID
- API密钥（敏感）
- 支付证书（敏感）
- 支付私钥（敏感）
- 回调通知URL
- 启用状态

#### 支付宝
- 应用ID
- 应用私钥（敏感）
- 支付宝公钥（敏感）
- 异步通知URL
- 同步跳转URL
- 启用状态

### AI服务配置

- API密钥（敏感）
- API地址
- 模型名称
- 最大Token数
- 请求超时时间
- 流式输出开关
- 思维链开关
- AI解盘消耗积分
- 八字分析开关
- 塔罗分析开关
- 启用状态

### 推送服务配置

支持多种推送服务：
- 极光推送（AppKey、MasterSecret）
- FCM（服务器密钥）
- 自定义Webhook（URL、Bearer Token）

### 短信服务配置

- 测试模式开关
- 测试验证码（测试模式专用）
- 腾讯云SecretId（敏感）
- 腾讯云SecretKey（敏感）
- 腾讯云SDKAppId（敏感）
- 短信签名
- 短信模板ID
- 启用状态

## 5. 安全特性

### 敏感信息加密

所有标记为敏感的配置项（API密钥、私钥、证书等）在存储时会自动使用AES-256加密，读取时自动解密，数据库中不存储明文。

### 权限控制

- 只有 `admin` 角色可以修改系统配置
- 配置测试功能需要管理员权限
- 配置导出功能需要管理员权限

### 操作审计

所有配置修改都会记录到操作日志中，包含：
- 修改时间
- 操作人
- 修改的配置项
- 修改前后的值（敏感信息脱敏）

## 6. 迁移建议

### 旧配置迁移

如果系统中已有支付配置，需要将旧配置迁移到新系统：

```sql
-- 示例：从PaymentConfig表迁移
INSERT INTO tc_system_configs (config_key, config_value, config_group, config_type, description, is_sensitive, is_enabled)
SELECT
    'wechat_mch_id',
    mch_id,
    'payment',
    'string',
    '微信支付商户号',
    0,
    1
FROM tc_payment_configs
WHERE config_type = 'wechat';
```

### 测试验证

迁移后请按以下步骤测试：

1. **管理端测试**
   - [ ] 能否正常访问系统配置页面
   - [ ] 能否保存配置（非敏感项）
   - [ ] 敏感信息能否正常加密/解密
   - [ ] 配置测试功能是否正常

2. **功能测试**
   - [ ] 支付功能是否正常
   - [ ] AI服务是否正常
   - [ ] 推送功能是否正常
   - [ ] 短信功能是否正常

3. **兼容性测试**
   - [ ] 旧代码是否仍能正常工作
   - [ ] 是否有遗漏的配置项

## 7. 回滚方案

如果需要回滚，请按以下步骤操作：

1. 停止应用服务
2. 删除 `tc_system_configs` 表
3. 恢复旧配置文件（`.env` 或配置文件）
4. 重启应用服务

## 8. 常见问题

### Q: 为什么有些配置项无法编辑？
A: 可能是权限不足，请确保登录账号具有 `admin` 角色。

### Q: 敏感信息加密后如何查看？
A: 在管理端查看时，敏感信息会自动解密显示；但在数据库中是加密存储的。

### Q: 配置测试失败怎么办？
A: 检查以下几点：
   - 配置值是否正确填写
   - 网络连接是否正常
   - 第三方服务是否正常运行
   - 查看后端日志获取详细错误信息

### Q: 如何批量导出/导入配置？
A: 使用配置导出功能可以导出JSON格式的配置文件，暂不支持直接导入，需要联系开发人员。

## 9. 联系方式

如有问题，请联系开发团队。
