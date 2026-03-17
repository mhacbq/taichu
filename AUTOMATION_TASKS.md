# 太初命理网站 - 自动化任务管理总览

> 📅 创建时间：2026-03-16  
> 🔄 最后更新：2026-03-17  
> 📊 当前状态：任务架构已优化，5小时清零计划执行中

---

## 🎛️ 快速操作 (Quick Commands)

### ✅ 启动/暂停所有任务
```bash
# 启动全部9个任务
workbuddy automation active 15 15-2 ui-15 30-2 20 automation automation-2 30 ui-30 30-3 30-4

# 暂停全部11个任务
workbuddy automation pause 15 15-2 ui-15 30-2 20 automation automation-2 30 ui-30 30-3 30-4
```

### 🛠️ 分组操作
```bash
# 🚀 紧急修复模式：暂停检查，全速修复
workbuddy automation pause 30 ui-30 30-3 30-4
workbuddy automation active 15 15-2 ui-15 30-2 20 automation automation-2

# 🔍 检查模式：只发现问题，不自动修复
workbuddy automation pause 15 15-2 ui-15 30-2 20 automation automation-2
workbuddy automation active 30 ui-30 30-3 30-4
```

---

## 📊 任务总览

### 🔧 修复类任务 (ID: 15, 15-2, ui-15, 30-2, 20, automation, automation-2)
| 任务名称 | 频率 | 每次修复 | 负责领域 | 状态 |
|---------|------|---------|---------|------|
| **后端修复专家** | 15 min | 5个 | PHP安全/API/数据库 | ACTIVE |
| **前端修复专家** | 15 min | 5个 | Vue逻辑/代码规范 | ACTIVE |
| **UI修复专家** | 15 min | 5个 | 主题/样式/图标替换 | ACTIVE |
| **命理算法专家** | 60 min | 3-5个 | Bazi/Liuyao/Tarot逻辑精度 | ACTIVE |
| **系统重构专家** | 60 min | 3-5个 | Lint修复/日志优化/重构 | ACTIVE |
| 待办处理执行器 | 30 min | 5个 | 跨领域综合修复 | ACTIVE |
| 前端开发修复 | 20 min | 3-5个 | 前端专项修复 | ACTIVE |


### 🔍 检查类任务 (ID: 30, ui-30, 30-3, 30-4)
| 任务名称 | 频率 | 角色 | 检查范围 | 状态 |
|---------|------|------|---------|------|
| 网站逻辑检查 | 30 min | 代码审计 | 全站逻辑/安全性 | ACTIVE |
| UI设计检查 | 30 min | UI/UX | 视觉一致性/适配 | ACTIVE |
| 运营后台检查 | 30 min | 运营官 | 管理后台功能 | ACTIVE |
| 占卜体验检查 | 30 min | 命理专家 | 算法准确性/专业性 | ACTIVE |

---

## 📈 效率统计
- **修复能力**：~82 个问题 / 小时
- **日处理量**：~1440 个问题 / 天
- **目标**：5 小时内清空历史积压 Bug

---

## 🔗 相关文档
- [TODO.md](./TODO.md) - 当前实时待办清单
- [AUTOMATION_OPTIMIZATION.md](./AUTOMATION_OPTIMIZATION.md) - 详细优化方案与分工原理

---

**最后更新**：2026-03-17  
**适用项目**：taichu-unified  
