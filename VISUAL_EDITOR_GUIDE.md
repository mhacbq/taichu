# 可视化编辑器使用指南

## 概述

太初后台管理系统现已集成**"可见即可改"**的可视化编辑器，让管理员可以直接在前端页面上点击编辑内容，无需进入后台即可修改网站所有内容。

---

## 功能特性

### 1. 可视化编辑组件

| 组件 | 功能 | 使用场景 |
|------|------|----------|
| `EditableText` | 文本编辑 | 标题、描述、正文等 |
| `EditableImage` | 图片编辑 | Logo、Banner、配图等 |
| `EditableSelect` | 选择器编辑 | 状态、分类、标签等 |
| `EditableSwitch` | 开关编辑 | 功能开关、显示/隐藏等 |
| `EditableColor` | 颜色编辑 | 主题色、背景色等 |

### 2. 内容块系统

| 块类型 | 说明 |
|--------|------|
| 文本块 | 支持单行/多行/富文本 |
| 图片块 | 支持上传/图库/URL |
| 轮播块 | 图片轮播组件 |
| 卡片块 | 带标题的内容卡片 |
| 列表块 | 可编辑列表项 |
| 统计块 | 数据展示卡片 |
| 图表块 | ECharts图表 |

### 3. 页面布局编辑器

- 拖拽排序内容块
- 响应式布局预览（桌面/平板/手机）
- 实时保存与草稿功能
- 模板系统
- 导出/导入JSON

### 4. 版本控制

- 自动保存历史版本
- 版本对比
- 一键回滚
- 版本描述管理

---

## 使用方式

### 在页面中使用可视化编辑器

```vue
<template>
  <div class="page">
    <!-- 可编辑标题 -->
    <h1>
      <EditableText
        v-model="pageTitle"
        :save-api="saveTitle"
        save-key="title"
      />
    </h1>
    
    <!-- 可编辑图片 -->
    <EditableImage
      v-model="bannerImage"
      width="100%"
      height="300px"
      :save-api="saveImage"
    />
    
    <!-- 页面布局编辑器 -->
    <PageLayout
      v-model="blocks"
      :is-editing="true"
      page-id="home"
      @save="handleSave"
    />
  </div>
</template>
```

### 权限控制

```vue
<!-- 只有管理员可见编辑功能 -->
<div v-edit>
  <EditableText v-model="content" />
</div>

<!-- 预览模式（禁用编辑） -->
<div v-preview="true">
  <ContentBlock :block="block" :can-edit="false" />
</div>
```

---

## 后端API

### 页面管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/content/pages` | GET | 获取页面列表 |
| `/api/content/page/:id` | GET | 获取页面内容 |
| `/api/content/page/:id` | POST | 保存页面 |
| `/api/content/page/:id` | DELETE | 删除页面 |

### 版本管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/content/page/:id/versions` | GET | 获取版本历史 |
| `/api/content/version/:id/restore` | POST | 恢复版本 |
| `/api/content/version/:id/preview` | GET | 预览版本 |

### 草稿管理

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/content/page/:id/autosave` | POST | 自动保存草稿 |
| `/api/content/page/:id/draft` | GET | 获取草稿 |

---

## 数据库表结构

### pages 表
- `page_id` - 页面唯一标识
- `title` - 页面标题
- `content` - 页面内容（JSON）
- `settings` - 页面设置（JSON）
- `version` - 当前版本号
- `status` - 状态（published/draft/hidden）

### page_versions 表
- `page_id` - 页面标识
- `content` - 内容快照
- `version` - 版本号
- `author_id` - 作者ID
- `description` - 版本描述

### page_drafts 表
- `page_id` - 页面标识
- `admin_id` - 管理员ID
- `content` - 草稿内容
- `auto_save` - 是否自动保存

---

## 管理后台

### 页面管理
- 访问路径：`/content/pages`
- 功能：新建/编辑/删除/导出页面

### 页面编辑器
- 访问路径：`/editor/page/:id`
- 功能：可视化编辑页面内容

### 编辑模式
1. **编辑模式** - 添加/删除/拖拽内容块
2. **预览模式** - 预览实际效果
3. **代码模式** - 查看/编辑JSON和Vue模板

---

## 最佳实践

1. **定期保存** - 虽然支持自动保存，但重要修改建议手动保存
2. **使用版本** - 重大修改前先手动保存一个版本
3. **预览确认** - 发布前在预览模式检查各设备显示效果
4. **权限控制** - 合理分配编辑权限，避免多人同时编辑

---

## 注意事项

1. 编辑前请确保有相应权限
2. 自动保存为草稿，不会覆盖已发布内容
3. 版本历史保留最近50个版本
4. 导出/导入功能支持JSON格式