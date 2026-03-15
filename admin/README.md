# 太初管理后台系统 (Taichu Admin)

基于 Vue 3 + Element Plus 的现代化后台管理系统

## 技术栈

- **前端框架**: Vue 3 + Composition API
- **UI组件库**: Element Plus
- **状态管理**: Pinia
- **路由管理**: Vue Router 4
- **HTTP客户端**: Axios
- **图表库**: ECharts
- **富文本编辑**: WangEditor

## 功能模块

### 1. 仪表盘 (Dashboard)
- 数据概览统计
- 用户增长趋势
- 功能使用分析
- 实时数据监控

### 2. 用户管理 (User Management)
- 用户列表/搜索/筛选
- 用户详情查看
- 用户状态管理(启用/禁用)
- 用户行为日志
- 用户标签管理

### 3. 内容管理 (Content Management)
- 八字记录管理
- 塔罗占卜记录
- 每日运势管理
- 内容审核

### 4. 积分管理 (Points Management)
- 积分记录查询
- 积分调整(手动增减)
- 积分规则设置
- 积分统计报表

### 5. 反馈管理 (Feedback Management)
- 用户反馈列表
- 反馈分类管理
- 反馈处理状态
- 反馈统计分析

### 6. 反作弊系统 (Anti-Cheat)
- 风险事件查看
- 风险规则配置
- 设备指纹管理
- 风险用户标记

### 7. 系统设置 (System Settings)
- 基础配置
- 敏感词管理
- 邮件/短信模板
- 系统公告

### 8. 日志管理 (Log Management)
- 操作日志
- 登录日志
- 异常日志
- API调用日志

### 9. 任务调度 (Task Scheduler)
- 定时任务列表
- 任务执行日志
- 脚本管理

## 目录结构

```
admin/
├── public/                 # 静态资源
├── src/
│   ├── api/               # API接口
│   │   ├── user.js
│   │   ├── content.js
│   │   ├── points.js
│   │   ├── feedback.js
│   │   ├── system.js
│   │   └── log.js
│   ├── assets/            # 资源文件
│   ├── components/        # 公共组件
│   │   ├── CommonTable/   # 通用表格
│   │   ├── SearchForm/    # 搜索表单
│   │   ├── StatCard/      # 统计卡片
│   │   └── Chart/         # 图表组件
│   ├── directives/        # 自定义指令
│   ├── layout/            # 布局组件
│   │   ├── index.vue      # 主布局
│   │   ├── Sidebar/       # 侧边栏
│   │   ├── Navbar/        # 顶部导航
│   │   └── AppMain/       # 主内容区
│   ├── router/            # 路由配置
│   ├── stores/            # Pinia状态管理
│   ├── styles/            # 全局样式
│   ├── utils/             # 工具函数
│   ├── views/             # 页面视图
│   │   ├── dashboard/     # 仪表盘
│   │   ├── user/          # 用户管理
│   │   ├── content/       # 内容管理
│   │   ├── points/        # 积分管理
│   │   ├── feedback/      # 反馈管理
│   │   ├── anticheat/     # 反作弊
│   │   ├── system/        # 系统设置
│   │   ├── log/           # 日志管理
│   │   └── task/          # 任务调度
│   ├── App.vue
│   └── main.js
├── package.json
├── vite.config.js
└── README.md
```

## 快速开始

```bash
# 安装依赖
npm install

# 开发模式
npm run dev

# 构建生产环境
npm run build
```

## 后端API对接

管理系统使用独立的后端API前缀 `/api/admin/*`
