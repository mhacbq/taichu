# 太初命理网站 - 花纹质感设计方案

## 方案对比

| 方案 | 特点 | 性能 | 效果 | 适用场景 |
|------|------|------|------|----------|
| **方案1：CSS云纹** | 纯CSS代码实现 | ⭐⭐⭐⭐⭐ 优 | 柔和渐进 | 全局背景 |
| **方案2：SVG图案** | 矢量图形，可缩放 | ⭐⭐⭐⭐ 良 | 清晰精致 | 局部装饰 |
| **方案3：边框线条** | 经典中式装饰 | ⭐⭐⭐⭐⭐ 优 | 古典雅致 | Footer/分隔线 |

---

## 方案1：CSS云纹背景（已实施）

**位置**：`App.vue` 全局样式

**效果**：
- 多层径向渐变模拟云纹散布
- 45°斜纹底纹增加层次
- 金色透明度极低，不干扰内容阅读

**使用**：已自动生效，无需额外配置

```css
/* 可在组件中自定义覆盖 */
.custom-section {
  background-image:
    radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
}
```

---

## 方案2：SVG图案（可选用）

**文件**：
- `/public/patterns/cloud-texture.svg` - 云纹背景
- `/public/patterns/wave-decor.svg` - 波纹装饰

**用法**：

```vue
<template>
  <!-- 云纹背景 -->
  <div class="cloud-bg-section">
    内容区域
  </div>

  <!-- 波纹装饰 -->
  <div class="wave-decor"></div>
</template>

<style scoped>
.cloud-bg-section {
  background-image: url('/patterns/cloud-texture.svg');
  background-repeat: repeat;
  background-size: 200px 200px;
  background-blend-mode: overlay;
}

.wave-decor {
  height: 20px;
  background-image: url('/patterns/wave-decor.svg');
  background-repeat: repeat-x;
  background-size: 200px 100%;
}
```

**适用组件**：
- VIP充值页面（提升高级感）
- 八字分析结果页（增强仪式感）
- 首页轮播图背景

---

## 方案3：边框线条（已实施Footer）

**位置**：`App.vue` `.footer` 样式

**效果**：
- 双重虚线装饰顶部
- 金色+透明度渐变
- 中式传统纹样风格

**复用方式**：

```vue
<style scoped>
/* 任何需要中式边框的组件 */
.section-divider {
  position: relative;
  padding-top: 30px;
}

.section-divider::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: repeating-linear-gradient(
    90deg,
    #D4AF37,
    #D4AF37 20px,
    transparent 20px,
    transparent 40px
  );
}

.section-divider::after {
  content: '';
  position: absolute;
  top: 4px;
  left: 0;
  right: 0;
  height: 2px;
  background: repeating-linear-gradient(
    90deg,
    transparent,
    transparent 10px,
    rgba(212, 175, 55, 0.5) 10px,
    rgba(212, 175, 55, 0.5) 12px
  );
}
</style>
```

---

## 色彩规范

所有花纹使用同一金色系，符合品牌规范：

```css
--primary-color: #D4AF37      /* 主金色 */
--primary-light: #F0D060      /* 亮金色 */
--primary-light-20: rgba(212, 175, 55, 0.2)
--primary-rgb: 212, 175, 55
```

---

## 自定义调整

### 调整透明度
修改 `rgba(212, 175, 55, X)` 中的 X 值（0-1）

### 调整密度
```css
/* 斜纹密度 */
repeating-linear-gradient(
  45deg,
  transparent,
  transparent 35px,  /* 增大=稀疏 */
  rgba(212, 175, 55, 0.01) 35px,
  rgba(212, 175, 55, 0.01) 36px
)
```

### 局部禁用
```css
.no-pattern {
  background-image: none !important;
  background-color: #FFFFFF;
}
```

---

## 示例：组合使用

```vue
<template>
  <div class="styled-card">
    <div class="card-header">标题</div>
    <div class="card-content">内容</div>
  </div>
</template>

<style scoped>
.styled-card {
  background:
    url('/patterns/cloud-texture.svg'),
    linear-gradient(135deg, #FFFFFF 0%, #FAFAFA 100%);
  background-blend-mode: overlay;
  border: 1px solid rgba(212, 175, 55, 0.2);
  position: relative;
}

.card-header {
  border-bottom: 1px solid rgba(212, 175, 55, 0.15);
  position: relative;
}

.card-header::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 60px;
  height: 2px;
  background: #D4AF37;
}
</style>
```
