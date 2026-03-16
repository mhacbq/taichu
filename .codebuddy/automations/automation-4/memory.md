# 待办处理执行器 - 执行记录

## 2026-03-17 执行记录（第十九次）

### 本次处理任务
- **任务**: 修复管理端API密钥明文显示风险
- **优先级**: 高优先级（安全问题）

### 修复内容
**问题**: admin/src/views/system/settings.vue第101-109行 - AI配置的API密钥从API获取并显示在输入框中，存在安全风险。

**修复的文件**:
1. **admin/src/views/system/settings.vue**
   - 添加`isApiKeyMasked`和`apiKeyPlaceholder`响应式变量
   - 加载配置时检测密钥是否为脱敏格式（包含****）
   - 如果是脱敏格式，清空表单中的api_key并显示占位符提示
   - 保存时如果api_key为空且之前是脱敏状态，则不传递该字段
   - 改进用户提示：区分"密钥已配置"和"密钥将以加密形式存储"

2. **backend/app/controller/AiAnalysis.php**
   - 改进`saveConfig`方法，处理api_key未提供、为空或为掩码格式的情况
   - 如果api_key未设置或为空，保留原配置值
   - 如果api_key是掩码格式（包含****），保留原配置值

**修改前**:
```javascript
// 前端：直接显示从API获取的密钥（可能是脱敏后的）
<el-input v-model="aiForm.api_key" type="password" show-password />

// 后端：只处理掩码格式
if (strpos($config['api_key'], '****') !== false) {
    $config['api_key'] = $oldConfig['api_key'] ?? '';
}
```

**修改后**:
```javascript
// 前端：检测脱敏格式，清空表单值，显示占位符
if (res.data.api_key && res.data.api_key.includes('****')) {
  isApiKeyMasked.value = true
  apiKeyPlaceholder.value = res.data.api_key
  res.data.api_key = ''  // 清空，避免提交脱敏值
}

// 保存时如果为空且之前是脱敏状态，不传递该字段
if (!submitData.api_key && isApiKeyMasked.value) {
  delete submitData.api_key
}

// 后端：处理多种情况
if (!isset($config['api_key']) || empty($config['api_key'])) {
    $config['api_key'] = $oldConfig['api_key'] ?? '';
} elseif (strpos($config['api_key'], '****') !== false) {
    $config['api_key'] = $oldConfig['api_key'] ?? '';
}
```

### 验证结果
- 前端代码语法正确
- 后端代码语法正确
- 密钥脱敏显示逻辑完善
- 保存时不会意外覆盖原密钥
- 用户体验改善：清晰的提示信息

### 待办状态更新
- 已将管理端API密钥明文显示风险从"待处理项目"移到"已完成项目"
- 更新了TODO.md文件

---

## 历史执行记录

[... 保留之前的执行记录 ...]
