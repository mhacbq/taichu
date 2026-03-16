# 定时任务快速操作指南

> 💡 本文档提供一键操作命令，方便快速管理所有定时任务

---

## 📋 任务ID速查表

### 🔧 修复类任务（5个）
```
后端修复专家      → ID: 15
前端修复专家      → ID: 15-2
UI修复专家        → ID: ui-15
待办处理执行器    → ID: 30-2
前端开发修复任务  → ID: 20
```

### 🔍 检查类任务（4个）
```
网站逻辑检查任务      → ID: 30
UI设计检查官          → ID: ui-30
运营人员后台检查      → ID: 30-3
占卜爱好者体验检查    → ID: 30-4
```

---

## 🎛️ 快速操作

### ✅ 启动所有任务
```bash
# 启动全部9个任务
workbuddy automation active 15
workbuddy automation active 15-2
workbuddy automation active ui-15
workbuddy automation active 30-2
workbuddy automation active 20
workbuddy automation active 30
workbuddy automation active ui-30
workbuddy automation active 30-3
workbuddy automation active 30-4
```

### ⏸️ 暂停所有任务
```bash
# 暂停全部9个任务
workbuddy automation pause 15
workbuddy automation pause 15-2
workbuddy automation pause ui-15
workbuddy automation pause 30-2
workbuddy automation pause 20
workbuddy automation pause 30
workbuddy automation pause ui-30
workbuddy automation pause 30-3
workbuddy automation pause 30-4
```

---

## 🎯 分组操作

### 仅启动修复任务（快速清理Bug）
```bash
# 只启动5个修复任务
workbuddy automation active 15      # 后端修复专家
workbuddy automation active 15-2    # 前端修复专家
workbuddy automation active ui-15   # UI修复专家
workbuddy automation active 30-2    # 待办处理执行器
workbuddy automation active 20      # 前端开发修复任务
```

### 仅启动检查任务（发现问题）
```bash
# 只启动4个检查任务
workbuddy automation active 30      # 网站逻辑检查
workbuddy automation active ui-30   # UI设计检查
workbuddy automation active 30-3    # 运营后台检查
workbuddy automation active 30-4    # 占卜体验检查
```

### 仅暂停修复任务（暂停清理，继续检查）
```bash
# 暂停修复，保留检查
workbuddy automation pause 15
workbuddy automation pause 15-2
workbuddy automation pause ui-15
workbuddy automation pause 30-2
workbuddy automation pause 20
```

### 仅暂停检查任务（暂停检查，继续修复）
```bash
# 暂停检查，专注修复
workbuddy automation pause 30
workbuddy automation pause ui-30
workbuddy automation pause 30-3
workbuddy automation pause 30-4
```

---

## 🔧 单任务操作

### 后端修复专家
```bash
# 启动
workbuddy automation active 15

# 暂停
workbuddy automation pause 15

# 查看状态
workbuddy automation status 15
```

### 前端修复专家
```bash
# 启动
workbuddy automation active 15-2

# 暂停
workbuddy automation pause 15-2

# 查看状态
workbuddy automation status 15-2
```

### UI修复专家
```bash
# 启动
workbuddy automation active ui-15

# 暂停
workbuddy automation pause ui-15

# 查看状态
workbuddy automation status ui-15
```

---

## 📊 查看任务状态

### 查看所有任务
```bash
workbuddy automation list
```

### 查看运行中的任务
```bash
workbuddy automation list --status active
```

### 查看已暂停的任务
```bash
workbuddy automation list --status paused
```

---

## ⚡ 常用场景

### 场景1：紧急修复模式（全力修复Bug）
```bash
# 暂停所有检查任务，只保留修复任务
workbuddy automation pause 30
workbuddy automation pause ui-30
workbuddy automation pause 30-3
workbuddy automation pause 30-4

# 确保所有修复任务运行
workbuddy automation active 15
workbuddy automation active 15-2
workbuddy automation active ui-15
workbuddy automation active 30-2
workbuddy automation active 20

echo "✅ 已进入紧急修复模式，全力清理Bug中..."
```

### 场景2：检查模式（只检查不修复）
```bash
# 暂停所有修复任务，只保留检查任务
workbuddy automation pause 15
workbuddy automation pause 15-2
workbuddy automation pause ui-15
workbuddy automation pause 30-2
workbuddy automation pause 20

# 确保所有检查任务运行
workbuddy automation active 30
workbuddy automation active ui-30
workbuddy automation active 30-3
workbuddy automation active 30-4

echo "✅ 已进入检查模式，只发现问题不修复..."
```

### 场景3：夜间模式（低频率运行）
```bash
# 暂停高频修复任务，只保留检查任务和低频修复
workbuddy automation pause 15
workbuddy automation pause 15-2
workbuddy automation pause ui-15

# 保留低频率任务
workbuddy automation active 30-2    # 每30分钟
workbuddy automation active 20      # 每20分钟
workbuddy automation active 30      # 每30分钟
workbuddy automation active ui-30
workbuddy automation active 30-3
workbuddy automation active 30-4

echo "✅ 已进入夜间模式，降低资源占用..."
```

### 场景4：全速模式（所有任务全速运行）
```bash
# 启动所有任务
workbuddy automation active 15
workbuddy automation active 15-2
workbuddy automation active ui-15
workbuddy automation active 30-2
workbuddy automation active 20
workbuddy automation active 30
workbuddy automation active ui-30
workbuddy automation active 30-3
workbuddy automation active 30-4

echo "🚀 已进入全速模式，9个任务全速运行！"
echo "预计每小时修复 ~82 个问题"
```

### 场景5：完全停止（暂停所有任务）
```bash
# 暂停所有任务
workbuddy automation pause 15
workbuddy automation pause 15-2
workbuddy automation pause ui-15
workbuddy automation pause 30-2
workbuddy automation pause 20
workbuddy automation pause 30
workbuddy automation pause ui-30
workbuddy automation pause 30-3
workbuddy automation pause 30-4

echo "⏸️ 所有任务已暂停"
```

---

## 📈 监控命令

### 查看修复进度
```bash
# 统计TODO.md中的待办项数量
cd c:/Users/v_boqchen/WorkBuddy/Claw/taichu-unified
grep -c "^- \[ \]" TODO.md
echo "个问题待处理"
```

### 查看已完成数量
```bash
cd c:/Users/v_boqchen/WorkBuddy/Claw/taichu-unified
grep -c "^- \[x\]" TODO.md
echo "个问题已修复"
```

### 查看Git提交记录
```bash
cd c:/Users/v_boqchen/WorkBuddy/Claw/taichu-unified
git log --oneline --since="1 day ago" | grep -E "(fix-backend|fix-frontend|fix-ui)"
```

---

## 🔄 自动化脚本

### Windows PowerShell 脚本

创建文件 `task-manager.ps1`：

```powershell
# 任务管理脚本
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "repair", "check", "status")]
    [string]$Action
)

switch ($Action) {
    "start" {
        Write-Host "🚀 启动所有任务..."
        # 启动所有任务的命令
    }
    "stop" {
        Write-Host "⏸️ 暂停所有任务..."
        # 暂停所有任务的命令
    }
    "repair" {
        Write-Host "🔧 仅启动修复任务..."
        # 仅启动修复任务
    }
    "check" {
        Write-Host "🔍 仅启动检查任务..."
        # 仅启动检查任务
    }
    "status" {
        Write-Host "📊 查看任务状态..."
        # 查看任务状态
    }
}
```

### 使用方法
```powershell
# 启动所有任务
.\task-manager.ps1 -Action start

# 暂停所有任务
.\task-manager.ps1 -Action stop

# 仅修复模式
.\task-manager.ps1 -Action repair

# 仅检查模式
.\task-manager.ps1 -Action check

# 查看状态
.\task-manager.ps1 -Action status
```

---

## 📞 帮助

如需帮助，请查看：
- `AUTOMATION_TASKS.md` - 完整任务清单
- `AUTOMATION_OPTIMIZATION.md` - 优化方案详情
- `TODO.md` - 当前Bug清单

---

**最后更新**：2026-03-16  
**适用项目**：taichu-unified
