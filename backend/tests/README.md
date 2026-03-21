# API测试快速开始

## 快速运行测试

### 方式1：使用启动脚本（推荐）

```bash
# 进入后端目录
cd /data/workspace/taichu/backend

# 启动后端服务（新终端窗口）
php -S localhost:8000 -t public

# 在另一个终端运行测试
./run_api_test.sh

# 或指定测试地址
./run_api_test.sh http://localhost:8000
```

### 方式2：直接运行PHP脚本

```bash
# 启动后端服务
php -S localhost:8000 -t public

# 运行测试
php tests/test_api.php

# 或指定URL
php tests/test_api.php http://localhost:8000
```

### 方式3：使用ThinkPHP命令

```bash
# 启动服务
php think run

# 运行测试
php tests/test_api.php http://localhost:8080
```

## 测试覆盖范围

测试脚本覆盖了 **90+ 个API接口**，包括：

- ✅ 健康检查、统计、配置等基础接口
- ✅ 用户认证、登录、注册等身份相关接口
- ✅ 八字排盘、运势分析、塔罗占卜等核心功能接口
- ✅ 积分系统、VIP会员、支付等业务接口
- ✅ 用户反馈、通知、分享等辅助功能接口
- ✅ AI分析、合婚、六爻、取名、吉日查询等特色功能接口

## 测试结果

测试完成后会显示：

1. **实时输出**：每个接口的测试结果
2. **统计报告**：通过率、失败数量
3. **JSON报告**：详细测试数据（自动保存）

## 查看测试报告

```bash
# 查看最新的测试报告
cat api_test_report_*.json | tail -1 | jq

# 或列出所有报告
ls -la api_test_report_*.json
```

## 注意事项

1. 需要先启动后端服务
2. 部分接口可能因数据不足而失败（正常现象）
3. 建议在测试数据库中运行，避免污染生产数据
4. 测试数据不会自动清理

## 预期结果

- ✅ **公开接口**（健康检查、配置、套餐列表等）：应该全部通过
- ⚠️ **需要认证的接口**：取决于登录是否成功
- ⚠️ **需要积分的接口**：取决于用户积分余额
- ⚠️ **操作特定记录的接口**：取决于测试数据是否存在
