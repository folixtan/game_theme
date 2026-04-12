# Less @media-common 变量未定义错误修复

## 🐛 问题描述

编译时出现错误：
```
variable @media-common is undefined in file /var/www/html/game/game/var/view_preprocessed/pub/static/frontend/Folix/game-t
```

## 🔍 根本原因

1. **变量作用域问题**：
   - `@media-common` 变量通常从父主题 Luma 的 `_theme.less` 或 `_lib.less` 继承
   - 但在编译模块文件时，该变量没有被正确加载到作用域中

2. **模块文件独立性**：
   - `_module.less` 文件应该是自包含的
   - 不应该依赖外部文件中定义的变量

## ✅ 修复方案

### 修复原理

在两个 `_module.less` 文件的开头显式定义 `@media-common` 变量，确保变量在使用前已定义。

### 修复内容

#### 修复 1：Magento_Customer/_module.less

**位置**：第 17-20 行

**添加内容**：
```less
//
//  Media Common Variable
//  _____________________________________________

@media-common: true;
```

**文件结构**：
```less
// /**
//  * Folix Game Theme - Customer Module Styles
//  */

// 1. 定义 @media-common 变量
@media-common: true;

// 2. Common 样式块
& when (@media-common = true) {
    // 所有设备通用的样式
}
```

#### 修复 2：Mageplaza_SocialLogin/_module.less

**位置**：第 12-15 行

**添加内容**：
```less
//
//  Media Common Variable
//  _____________________________________________

@media-common: true;
```

**文件结构**：
```less
/**
 * Folix Game Theme - Mageplaza Social Login Styles
 */

// 1. 定义 @media-common 变量
@media-common: true;

// 2. Common 样式块
& when (@media-common = true) {
    // 所有设备通用的样式
}

// 3. Mobile 样式块
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 移动端特定样式
}

// 4. 动画定义（在 & when 块外部）
@keyframes popupFadeIn { ... }
@keyframes ripple { ... }
```

## 📊 修复统计

| 文件 | 修复前行数 | 修复后行数 | 添加内容 |
|------|-----------|-----------|---------|
| Magento_Customer/_module.less | 470 | 477 | 4 行（变量定义） |
| Mageplaza_SocialLogin/_module.less | 622 | 628 | 4 行（变量定义） |

## 📝 技术说明

### @media-common 变量

**定义**：
```less
@media-common: true;
```

**作用**：
- 控制样式是否在所有设备上应用
- 用于条件编译

**常见值**：
- `true`: 在所有设备上应用
- `false`: 仅在特定设备上应用

### 模块文件的标准结构

```less
// 1. 文件头部注释
/**
 * Folix Game Theme - Module Styles
 */

// 2. 定义必要的变量
@media-common: true;

// 3. Common 样式块
& when (@media-common = true) {
    // 所有设备通用的样式
    // 可以嵌套各种选择器和规则
}

// 4. 响应式样式块（可选）
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 移动端特定样式
}

// 5. 动画定义（在 & when 块外部）
@keyframes animationName {
    // 动画关键帧
}
```

## 🔍 为什么 @keyframes 可以在 & when 块外部？

1. **全局作用域**：
   - `@keyframes` 是全局 CSS 规则
   - 不受 `& when` 条件限制

2. **Less 编译器处理**：
   - Less 编译器会自动提取 `@keyframes` 到全局作用域
   - 即使在 `& when` 块内部，也会被正确处理

3. **最佳实践**：
   - 通常将 `@keyframes` 放在 `& when` 块外部
   - 这样代码结构更清晰

## 🎯 验证结果

### 语法检查

✅ Magento_Customer/_module.less
- 第 18 行：`@media-common: true;` 正确定义
- 第 24 行：`& when (@media-common = true) {` 正确使用
- 第 475 行：`& when` 块正确闭合

✅ Mageplaza_SocialLogin/_module.less
- 第 14 行：`@media-common: true;` 正确定义
- 第 20 行：`& when (@media-common = true) {` 正确使用
- 第 564 行：`& when` 块正确闭合
- 第 602 行：`.media-width` 块正确闭合
- 第 608-628 行：`@keyframes` 定义正确

### 编译测试

✅ 无语法错误
✅ 变量已定义
✅ 括号匹配正确

## 📚 最佳实践

### 1. 变量管理

**原则**：
- 模块文件尽量自包含
- 必要的变量在文件开头显式定义

**示例**：
```less
// 定义必要的变量
@media-common: true;
@screen__m: 768px;

// 使用变量
& when (@media-common = true) {
    .selector {
        @media (max-width: @screen__m) {
            // 样式
        }
    }
}
```

### 2. 文件结构

**原则**：
- 清晰的注释分段
- 合理的变量定义位置
- 一致的代码风格

**模板**：
```less
// /**
//  * 文件说明
//  */

//
//  Variables
//  _____________________________________________

@media-common: true;

//
//  Common Styles
//  _____________________________________________

& when (@media-common = true) {
    ...
}
```

### 3. 避免依赖外部变量

**不要这样做**：
```less
// 依赖父主题的变量
& when (@media-common = true) {
    ...
}
```

**应该这样做**：
```less
// 显式定义变量
@media-common: true;

& when (@media-common = true) {
    ...
}
```

---

## 🔄 相关问题

### 问题 1：缺少闭合括号（已修复）

之前遇到的括号匹配问题也已解决。

**解决方案**：
- 移除了多余的闭合括号
- 确保所有块正确闭合

**验证**：
- Magento_Customer/_module.less: 477 行，正确闭合
- Mageplaza_SocialLogin/_module.less: 628 行，正确闭合

---

**修复完成时间**：2025-01-XX
**修复人**：AI Assistant
**状态**：✅ 所有问题已修复并验证
