# 太初管理后台系统 - 功能清单

## 📦 技术栈

- **前端**: Vue 3 + Element Plus + Pinia + Vue Router
- **后端**: ThinkPHP 8 + JWT认证
- **图表**: ECharts
- **富文本**: WangEditor

## 🚀 功能模块

### 1. 仪表盘 (Dashboard)
- [x] 数据统计卡片（用户总数/新增/八字/塔罗）
- [x] 用户增长趋势图
- [x] 功能使用分布饼图
- [x] 实时数据监控
- [x] 待处理反馈列表

### 2. 用户管理 (User Management)
- [x] 用户列表/搜索/筛选
- [x] 用户详情查看（基本信息/使用统计/活动记录）
- [x] 用户状态管理（启用/禁用）
- [x] 积分调整功能
- [x] 用户导出
- [x] 行为日志

### 3. 内容管理 (Content Management)
- [x] 八字记录管理（列表/查看/删除）
- [x] 塔罗占卜记录管理
- [x] 每日运势管理（CRUD）

### 4. 积分管理 (Points Management)
- [x] 积分记录查询
- [x] 积分规则设置
- [x] 手动积分调整

### 5. 反馈管理 (Feedback Management)
- [x] 反馈列表/搜索
- [x] 反馈分类管理
- [x] 反馈回复功能
- [x] 状态管理（待处理/处理中/已解决/已关闭）

### 6. 反作弊系统 (Anti-Cheat)
- [x] 风险事件查看（频繁请求/异常设备/IP异常/行为异常）
- [x] 风险等级分类（高危/中危/低危）
- [x] 风险事件处理（忽略/警告/封禁）
- [x] 风险规则配置
- [x] 设备指纹管理

### 7. 系统设置 (System Settings)
- [x] 基础配置（网站名称/Logo/描述）
- [x] 积分配置（注册赠送/签到/功能消耗）
- [x] 功能开关（注册/每日运势/反馈）
- [x] 敏感词管理（添加/删除/批量导入/检测测试）
- [x] 系统公告管理
- [x] 管理员账号管理

### 8. 日志管理 (Log Management)
- [x] 操作日志（记录所有后台操作）
- [x] 登录日志
- [x] API调用日志
- [x] 日志导出/清空

### 9. 任务调度 (Task Scheduler)
- [x] 定时任务列表
- [x] Cron表达式配置
- [x] 任务启停控制
- [x] 手动执行任务
- [x] 执行日志查看
- [x] 脚本管理

## 📁 文件结构

```
taichu-unified/admin/
├── src/
│   ├── api/                    # API接口
│   │   ├── request.js          # Axios请求封装
│   │   ├── user.js             # 用户相关API
│   │   ├── dashboard.js        # 仪表盘API
│   │   ├── content.js          # 内容管理API
│   │   ├── points.js           # 积分管理API
│   │   ├── feedback.js         # 反馈管理API
│   │   ├── anticheat.js        # 反作弊API
│   │   ├── system.js           # 系统设置API
│   │   ├── log.js              # 日志管理API
│   │   └── task.js             # 任务调度API
│   ├── components/             # 公共组件
│   ├── layout/                 # 布局组件
│   │   ├── index.vue           # 主布局
│   │   ├── components/
│   │   │   ├── Sidebar/        # 侧边栏
│   │   │   ├── Navbar.vue      # 顶部导航
│   │   │   ├── Breadcrumb.vue  # 面包屑
│   │   │   ├── TagsView/       # 标签页
│   │   │   └── AppMain.vue     # 主内容区
│   ├── router/                 # 路由配置
│   ├── stores/                 # Pinia状态管理
│   ├── styles/                 # 全局样式
│   ├── utils/                  # 工具函数
│   ├── views/                  # 页面视图
│   │   ├── login/              # 登录页
│   │   ├── dashboard/          # 仪表盘
│   │   ├── user/               # 用户管理
│   │   ├── content/            # 内容管理
│   │   ├── points/             # 积分管理
│   │   ├── feedback/           # 反馈管理
│   │   ├── anticheat/          # 反作弊
│   │   ├── system/             # 系统设置
│   │   ├── log/                # 日志管理
│   │   ├── task/               # 任务调度
│   │   └── error/              # 错误页
│   ├── App.vue
│   └── main.js
├── package.json
├── vite.config.js
└── index.html
```

## 🔐 权限控制

- JWT Token认证
- 路由守卫验证
- 操作权限检查
- 管理员角色区分

## 📊 数据可视化

- ECharts图表集成
- 实时数据展示
- 趋势分析
- 分布统计

## 🔧 系统特性

- 响应式布局
- 多标签页支持
- 页面缓存
- 主题切换
- 国际化支持（预留）
- 操作日志记录
- 数据导出功能

## 🚀 快速验证 / 启动

### 1. 先做构建校验（推荐）

```bash
cd taichu-unified/admin
npm install
npm run build
```

构建成功后会生成 `admin/dist/index.html`。如果后台已经挂在某个站点根地址，请访问“站点根地址 + `/login`”打开登录页。

### 2. 需要热更新时再启动 dev server

```powershell
Set-Location "C:\Users\v_boqchen\WorkBuddy\Claw\taichu-unified\admin"
npm install
$env:VITE_PROXY_TARGET = 'http://localhost:8080'
npm run dev
```

访问终端打印出来的 dev URL 即可（常见是 `http://localhost:3001`，但不要把 3001 当成固定后台站点地址）。

### 3. 登录口径

- 登录页前端路由：`/login`
- 登录接口：`POST /api/admin/auth/login`
- 若数据库使用了 `database/20260317_create_admin_users_table.sql` 的初始化数据，可用默认账号 `admin / admin123`
- 若当前环境已有自己的管理员数据，请直接使用真实账号，不要机械假设默认密码一定可用

## 📡 API接口

后台API使用独立前缀 `/api/admin/*`，与前端API分离，确保安全性。
