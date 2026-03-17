# Automation Memory - UI Fixes (2026-03-17)

## Latest Run
- **Task**: Fourth round of UI consistency, accessibility, and mobile usability fixes.
- **Status**: Completed (5 UI issues resolved).
- **Date**: 2026-03-17

## Major Changes
1. **首页统计图标稳定渲染（Home.vue）**
   - 将统计卡片图标从字符串动态组件改为稳定的图标组件引用。
   - 新增 `statIconMap` 与 `resolveStatIcon()`，兼容接口返回的字符串图标名，避免统计区整组图标丢失。
2. **返回按钮白色主题对比度修复（BackButton.vue）**
   - 按钮改为使用 Element Plus `ArrowLeft` 图标。
   - 统一为浅色容器、深色文字、hover/focus/active 状态，并补齐 44px 触控尺寸。
3. **塔罗问题引导区移动端降密（Tarot.vue）**
   - 小屏下将 `.topic-tabs` 调整为 2 列、`.template-list` 调整为单列。
   - 补齐话题/模板项最小触控高度，并将牌面详情弹窗宽度改为 `min(92vw, 500px)`。
4. **六爻历史删除入口可发现性提升（Liuyao.vue）**
   - 删除按钮从 hover 才可见改为默认可见的次级危险操作按钮。
   - 新增“删除”文案，触屏端无需悬停即可识别操作入口。
5. **减少动态效果支持（Home.vue / Liuyao.vue / TarotCard.vue）**
   - 为首页欢迎区、统计卡片、六爻卦象动效、塔罗牌持续旋转/脉冲补充 `prefers-reduced-motion: reduce` 降级分支。
   - 在用户系统偏好减少动态效果时关闭非必要持续动画与位移变换。

## TODO Maintenance
- 已从 `TODO.md` 删除以下 5 个已完成 UI 问题：
  - 首页统计卡片图标依赖字符串动态组件
  - 共享返回按钮白色主题对比度偏弱
  - 塔罗问题引导区移动端过密
  - 六爻历史删除按钮仅 hover 可见
  - 持续动画未响应 reduced-motion

## Validation
- `read_lints`：`frontend/src/views/Home.vue`、`frontend/src/components/BackButton.vue`、`frontend/src/views/Tarot.vue`、`frontend/src/components/TarotCard.vue`、`frontend/src/views/Liuyao.vue` 均为 **0 diagnostics**。
- `npm run build`：**通过**。
- 当前仍有构建体积告警：`element-plus` 与 `SEOStats` chunk 超过 500 kB，但不阻塞本轮 UI 修复提交。

## Git Commit
- Planned: `fix-ui-accessibility-mobile-20260317`

## Previous Run
- Previous commit: `fix-ui-multiple-issues-20260317-1700`
- Focus: Global CSS variables, Bazi result visualization, Tarot interaction feedback, Liuyao hexagram refinement, page transition polish.
