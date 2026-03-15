# 数据库表名参考文档

## 表前缀规则

项目使用 ThinkPHP 的数据库配置，默认表前缀为 `tc_`。

**重要说明**：
- 如果模型中明确定义了 `$table` 或 `$name` 属性，ThinkPHP **不会**自动添加前缀
- 因此，模型中定义的表名必须与数据库中实际表名完全一致

## 表名对照表

### 带 tc_ 前缀的表（业务核心表）

| 表名 | 模型 | 说明 |
|------|------|------|
| tc_user | User | 用户表 |
| tc_bazi_record | BaziRecord | 八字排盘记录 |
| tc_tarot_record | TarotRecord | 塔罗占卜记录 |
| tc_daily_fortune | DailyFortune | 每日运势记录 |
| tc_points_record | PointsRecord | 积分变动记录 |
| tc_points_exchange | PointsExchange | 积分兑换记录 |
| tc_points_product | PointsProduct | 积分商品表 |
| tc_invite_record | InviteRecord | 邀请记录 |
| tc_recharge_order | RechargeOrder | 充值订单 |
| tc_admin_log | AdminLog | 管理员操作日志 |
| tc_admin_role | AdminRole | 管理员角色 |
| tc_admin_permission | AdminPermission | 管理员权限 |
| tc_admin_user_role | AdminUserRole | 管理员角色关联 |
| tc_admin_role_permission | AdminRolePermission | 角色权限关联 |

### 不带 tc_ 前缀的表（系统配置表）

| 表名 | 模型 | 说明 |
|------|------|------|
| system_config | SystemConfig | 系统配置 |
| ai_prompts | AiPrompt | AI提示词 |
| payment_configs | PaymentConfig | 支付配置 |
| sms_codes | SmsCode | 短信验证码记录 |
| sms_configs | SmsConfig | 短信配置 |
| hehun_records | HehunRecord | 八字合婚记录 |
| pages | Page | CMS页面 |
| page_versions | PageVersion | 页面版本 |
| page_drafts | PageDraft | 页面草稿 |
| upload_files | UploadFile | 上传文件 |
| tarot_cards | TarotCard | 塔罗牌数据 |
| tarot_spreads | TarotSpread | 塔罗牌阵 |
| faqs | Faq | 常见问题 |
| daily_fortune_templates | DailyFortuneTemplate | 每日运势模板 |
| testimonials | Testimonial | 用户评价 |
| site_contents | SiteContent | 网站内容 |
| question_templates | QuestionTemplate | 问题模板 |
| feedback | Feedback | 用户反馈 |
| checkin_record | CheckinRecord | 签到记录 |

## 模型配置说明

### 带前缀的模型示例
```php
class User extends Model
{
    protected $table = 'tc_user';  // 实际表名为 tc_user
}
```

### 不带前缀的模型示例
```php
class AiPrompt extends Model
{
    protected $table = 'ai_prompts';  // 实际表名为 ai_prompts（不带前缀）
}
```

## 新建表的建议

1. **业务数据表**（用户、订单、记录等）：使用 `tc_` 前缀
2. **系统配置表**（配置、模板、内容管理等）：不使用前缀

## 数据库迁移文件命名规范

- 带 tc_ 前缀的表：`20250316_create_tc_xxxx_table.sql`
- 不带前缀的表：`20250316_create_xxxx_table.sql`
