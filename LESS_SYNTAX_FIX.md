# Less 编译错误修复记录

## 🐛 问题历史

### 问题 1：缺少闭合括号（已修复）

**错误信息**：
```
closing `}` in _module.less on line 462, column 30
```

**修复内容**：
- Magento_Customer/_module.less: 添加了一个闭合括号 `}`（后续发现是多余的，已删除）
- Mageplaza_SocialLogin/_module.less: 添加了一个闭合括号 `}`（后续发现是多余的，已删除）

**最终修复**：两个文件都不需要额外的闭合括号，因为 `@keyframes` 可以在 `& when` 块内部定义。

---

### 问题 2：缺少 @media-common 变量定义（已修复）

**错误信息**：
```
variable @media-common is undefined in file /var/www/html/game/game/var/view_preprocessed/pub/static/frontend/Folix/game-t
```

**根本原因**：
- 两个 `_module.less` 文件使用了 `& when (@media-common = true)` 条件
- 但 `@media-common` 变量没有被定义
- 该变量通常从父主题 Luma 的 `_theme.less` 或 `_lib.less` 继承
- 由于编译时变量作用域问题，需要在模块文件中显式定义

**修复内容**：

#### 修复 1：Magento_Customer/_module.less

在文件开头添加：
```less
//
//  Media Common Variable
//  _____________________________________________

@media-common: true;
```

#### 修复 2：Mageplaza_SocialLogin/_module.less

在文件开头添加：
```less
//
//  Media Common Variable
//  _____________________________________________

@media-common: true;
```

**变更位置**：
- `Magento_Customer/_module.less`: 第 17-20 行（新增）
- `Mageplaza_SocialLogin/_module.less`: 第 12-15 行（新增）

---

## ✅ 最终修复状态

| 问题 | 状态 | 说明 |
|------|------|------|
| 闭合括号错误 | ✅ 已修复 | 移除了多余的闭合括号 |
| @media-common 未定义 | ✅ 已修复 | 在两个文件开头添加了变量定义 |

---

## 📚 技术说明

### @media-common 变量

**作用**：
- 控制样式是否在所有设备上应用
- 在 Magento 主题开发中用于条件编译

**常见值**：
- `true`: 在所有设备上应用
- `false`: 仅在特定设备上应用

**使用方式**：
```less
@media-common: true;

& when (@media-common = true) {
    // 这里的样式会在所有设备上应用
}
```

### 模块文件的正确结构

```less
// 1. 文件头部注释
/**
 * Folix Game Theme - Module Styles
 */

// 2. 定义 @media-common 变量
@media-common: true;

// 3. Common 样式块
& when (@media-common = true) {
    // 所有设备通用的样式
    // 可以包含 @keyframes 动画定义
}

// 4. Mobile 样式块（可选）
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 移动端特定样式
}
```

---

## 🔍 问题排查经验

### 1. 变量未定义错误

**症状**：
```
variable @xxx is undefined in file ...
```

**排查步骤**：
1. 检查该变量是否在使用前已定义
2. 检查变量是否在正确的文件作用域内
3. 对于继承的主题变量，检查是否需要显式导入或定义

**解决方案**：
- 在文件开头显式定义变量
- 或者在 `_theme.less` 中导入父主题的库文件

### 2. 括号匹配错误

**症状**：
```
missing opening `{` in _module.less
closing `}` in _module.less
```

**排查步骤**：
1. 使用编辑器的括号匹配功能检查
2. 统计开括号和闭括号的数量
3. 检查嵌套层级是否正确

**解决方案**：
- 使用 Lint 工具自动检查
- 遵循一致的代码缩进

---

## 📝 最佳实践

### 1. 变量管理

**原则**：
- 模块文件尽量自包含，不依赖外部变量
- 必要的变量在文件开头显式定义

**示例**：
```less
// 定义必要的变量
@media-common: true;
@screen__m: 768px;

// 使用变量
& when (@media-common = true) {
    ...
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

---

**修复完成时间**：2025-01-XX
**修复人**：AI Assistant
**状态**：✅ 所有问题已修复并验证
