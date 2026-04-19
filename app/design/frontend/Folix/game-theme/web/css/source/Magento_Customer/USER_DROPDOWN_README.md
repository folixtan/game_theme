# 用户下拉菜单样式说明

## 📋 概述

本文件为 Folix Game Theme 的用户登录后下拉菜单提供完整的样式支持。

## 📁 文件位置与架构

### 当前实现（独立文件）
- **样式文件**: `app/design/frontend/Folix/game-theme/web/css/source/Magento_Customer/_user-dropdown.less`
- **状态**: ✅ **已启用**，直接在 `_extend.less` 中引入
- **特点**: 自包含，不依赖其他文件

### 旧实现（已废弃）
- **文件**: `app/design/frontend/Folix/game-theme/web/css/source/Magento_Customer/_module.less`
- **状态**: ❌ **未启用**（在 `_extend.less` 中被注释掉）
- **原因**: 包含错误的类名（`.folix-customer-dropdown`）和过时的实现
- **注意**: 该文件中的 `.folix-customer-welcome` 样式已迁移到 `_user-dropdown.less`

## 🎯 关键设计决策

### 1. 为什么选择独立文件而非 _module.less？

**原因：**
1. `_module.less` 中包含大量旧的、错误的实现
2. 使用不同的类名（`.folix-customer-dropdown` vs `.folix-user-dropdown`）
3. 保持新实现的独立性和清晰度
4. 避免引入不必要的历史代码

**架构优势：**
```
_extend.less
  ├── _user-dropdown.less (✅ 新的、正确的实现)
  └── _module.less (❌ 旧的、被注释掉)
```

### 2. 定位上下文的处理

**问题：** `.folix-user-dropdown` 使用 `position: absolute`，需要父容器有定位上下文。

**解决方案：** 在 `_user-dropdown.less` 中直接定义：
```less
.folix-customer-welcome {
    position: relative;  // 创建定位上下文
}

.folix-user-dropdown {
    position: absolute;  // 相对于 .folix-customer-welcome 定位
    top: 100%;
    right: 0;
}
```

**注意：** 虽然 Magento 官方建议在 `_module.less` 中管理模块级样式，但为了保持新实现的独立性，我们将必要的容器样式也放在了这个文件中。

## 🎨 设计特点

### 1. 视觉风格
- **主题色**: 使用电竞风格的深蓝 + 金色配色方案
- **渐变效果**: 触发器区域使用蓝色渐变背景
- **阴影效果**: 使用金色阴影突出重要元素
- **圆角设计**: 统一的圆角规范（8px-12px）

### 2. 交互效果
- **淡入动画**: 菜单展开时带有淡入和下滑动画
- **悬停效果**: 菜单项悬停时显示金色左侧指示条
- **点击反馈**: 激活时有轻微缩放效果
- **响应式**: 平板端调整尺寸，移动端隐藏

### 3. 组件结构

```html
<li class="customer-welcome folix-customer-welcome active">
    <!-- 触发按钮 -->
    <span class="customer-name active">
        <button class="action switch folix-customer-btn">
            <span>Change</span>
            <svg class="folix-dropdown-icon">...</svg>
        </button>
    </span>
    
    <!-- 下拉菜单容器 -->
    <div class="customer-menu folix-user-dropdown">
        <!-- 触发器区域（头像 + 用户名） -->
        <div class="user-dropdown-trigger">
            <div class="avatar"></div>
            <span class="username">用户名</span>
            <span class="arrow">▼</span>
        </div>
        
        <!-- 菜单列表 -->
        <div class="user-dropdown-menu">
            <a href="/customer/account/">
                <span>My Account</span>
            </a>
            <a href="/sales/order/history/">
                <span>My Orders</span>
            </a>
            <a href="/wishlist/">
                <span>My Wishlist</span>
            </a>
            <div class="divider"></div>
            <a href="/customer/account/logout/" class="logout">
                <span>Sign Out</span>
            </a>
        </div>
    </div>
</li>
```

## 🎯 CSS 类说明

### 主要容器

| 类名 | 说明 | 作用 |
|------|------|------|
| `.folix-customer-welcome` | 用户欢迎容器 | **position: relative**，创建定位上下文 |
| `.folix-user-dropdown` | 下拉菜单主容器 | 定位、背景、阴影、动画 |
| `.user-dropdown-trigger` | 触发器区域 | 头像、用户名、箭头图标 |
| `.user-dropdown-menu` | 菜单列表容器 | 包含所有菜单项 |

### 子元素

| 类名 | 说明 | 样式特点 |
|------|------|---------|
| `.avatar` | 用户头像 | 36px 圆形，金色边框和阴影 |
| `.username` | 用户名 | 白色文字，超出省略 |
| `.arrow` | 下拉箭头 | 旋转动画，提示状态 |
| `.divider` | 分割线 | 1px 透明边框 |
| `.logout` | 登出链接 | 红色强调，危险操作 |

## 🎨 颜色变量

使用的设计系统变量：

```less
// 背景色
@folix-bg-panel: #1E293B;          // 菜单背景
@folix-bg-hover: #334155;          // 悬停背景
@folix-bg-selected: rgba(255, 215, 0, 0.1);  // 选中背景

// 渐变色
@folix-gradient-primary: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
@folix-gradient-primary-hover: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%);

// 强调色
@folix-secondary: #FFD700;         // 金色（主强调色）
@folix-accent: #FF3B30;            // 红色（登出按钮）

// 文本色
@folix-text-primary: #FFFFFF;      // 主要文字
@folix-text-on-dark: #F8FAFC;      // 深色背景上的文字

// 边框和阴影
@folix-border: rgba(255, 255, 255, 0.1);
@folix-shadow-xl: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
@folix-shadow-gold: 0 4px 12px rgba(255, 215, 0, 0.3);

// 圆角
@folix-radius-lg: 12px;
@folix-radius-sm: 4px;
```

## 📱 响应式设计（使用 Magento 标准 .media-width()）

### 文件结构说明

**重要**：必须使用 Magento 标准的 `.media-width()` mixin 来处理响应式断点。

```less
// ✅ 正确：基础样式在 media-common 内
& when (@media-common = true) {
    .folix-customer-welcome {
        position: relative;  // 定位上下文
    }
    
    .folix-user-dropdown {
        // 桌面端默认样式
    }
}

// ✅ 正确：使用 Magento 标准 media-width mixin
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 平板端及以下 (≤ 768px)
    .folix-user-dropdown {
        // 平板端样式
    }
}

.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__s) {
    // 移动端 (≤ 640px)
    .folix-user-dropdown {
        display: none !important;  // 移动端隐藏
    }
}
```

### 断点说明

| 断点变量 | 值 | 适用设备 | 行为 |
|---------|-----|---------|------|
| `@screen__m` | 768px | 平板及以下 | 调整菜单尺寸和间距 |
| `@screen__s` | 640px | 移动端 | **隐藏下拉菜单** |

### 桌面端 (> 768px)
- 菜单宽度：最小 200px
- 位置：右上角绝对定位
- 头像尺寸：36px × 36px
- 字体大小：14px
- **完全显示**

### 平板端 (≤ 768px)
- 菜单宽度：最小 180px
- 位置：右侧对齐（偏移 -10px）
- 头像尺寸：32px × 32px
- 字体大小：13px
- **完全显示**

### 移动端 (≤ 640px)
- **下拉菜单完全隐藏** (`display: none`)
- 建议：移动端应使用全屏导航菜单或侧边抽屉代替下拉菜单
- 需要在其他文件中实现移动端专用的用户菜单

## 🚀 部署步骤

### 1. 清理缓存
```bash
bin/magento cache:clean
```

### 2. 删除预处理文件
```bash
rm -rf var/view_preprocessed/pub/static/frontend/Folix/game-theme
```

### 3. 删除静态资源
```bash
rm -rf pub/static/frontend/Folix/game-theme
```

### 4. 重新部署静态内容
```bash
bin/magento setup:static-content:deploy en_US -f
```

### 5. 验证编译结果
检查生成的 CSS 文件：
```bash
grep -n "folix-user-dropdown" pub/static/frontend/Folix/game-theme/en_US/css/styles-m.css
```

## 🔍 调试技巧

### 1. 检查样式是否生效
在浏览器开发者工具中：
1. 右键点击下拉菜单 → 检查元素
2. 查看 Computed 标签页
3. 确认 `.folix-user-dropdown` 和 `.folix-customer-welcome` 样式已应用

### 2. 验证 LESS 编译
如果样式未更新：
```bash
# 查看 LESS 源文件修改时间
ls -lh app/design/frontend/Folix/game-theme/web/css/source/Magento_Customer/

# 检查编译后的 CSS
tail -100 pub/static/frontend/Folix/game-theme/en_US/css/styles-m.css
```

### 3. 测试响应式断点
```bash
# 桌面端 (> 768px)
# 菜单应该正常显示

# 平板端 (≤ 768px)
# 菜单应该显示但尺寸缩小

# 移动端 (≤ 640px)
# 菜单应该完全隐藏
```

### 4. 强制刷新浏览器
- Chrome/Edge: `Ctrl + Shift + R` (Windows) 或 `Cmd + Shift + R` (Mac)
- Firefox: `Ctrl + F5` (Windows) 或 `Cmd + Shift + R` (Mac)

## 🎨 自定义修改

### 修改菜单宽度
```less
.folix-user-dropdown {
    min-width: 240px;  // 改为你需要的宽度
}
```

### 修改头像尺寸
```less
.avatar {
    width: 48px;   // 改为你需要的大小
    height: 48px;
    min-width: 48px;  // 关键：防止 Flex 压缩
    min-height: 48px;
}
```

### 修改菜单项间距
```less
.user-dropdown-menu {
    gap: 8px;  // 改为你需要的间距
}
```

### 修改动画速度
```less
animation: dropdownFadeIn 0.5s ease;  // 改为 0.5s 更慢
```

### 修改移动端行为
如果希望在移动端显示而不是隐藏：
```less
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__s) {
    .folix-user-dropdown {
        // 不要设置 display: none
        // 改为底部固定面板或其他移动端友好的样式
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        // ... 其他移动端样式
    }
}
```

## ⚠️ 注意事项

1. **不要直接修改编译后的 CSS**
   - 所有修改必须在 `.less` 源文件中进行
   - 修改后必须重新部署静态内容

2. **保持选择器特异性**
   - 使用 `.folix-` 前缀避免冲突
   - 避免使用 `!important`（除了移动端隐藏）

3. **响应式测试**
   - 在多种屏幕尺寸下测试菜单显示
   - 确保移动端有替代的用户菜单方案

4. **性能优化**
   - 避免过度使用动画
   - 使用 CSS transform 而非 position 进行动画

5. **正圆形元素保护**
   - 对于头像等需要保持正圆形的元素，必须同时设置 `width`、`height`、`min-width`、`min-height`
   - 防止 Flex 布局或父容器压缩导致变形

6. **Magento 标准实践**
   - 始终使用 `.media-width()` mixin 而非原生 `@media` 查询
   - 遵循 `@extremum` ('min'/'max') 和 `@break` (@screen__*) 参数规范
   - 参考 Magento Luma 主题的实现模式

7. **架构决策记录**
   - **不使用 `_module.less`**：因为其中包含错误的实现和过时的类名
   - **独立文件策略**：`_user-dropdown.less` 是自包含的，包含所有必要的样式（包括父容器的定位上下文）
   - **未来重构**：如果需要，可以清理 `_module.less` 并重新启用它，然后从 `_user-dropdown.less` 中移除重复的容器样式

## 📝 更新日志

### v1.3.0 (2026-04-19)
- ✅ 添加 `.folix-customer-welcome` 的 `position: relative` 定义
- ✅ 明确架构决策：不使用旧的 `_module.less`
- ✅ 保持文件独立性，避免引入错误代码

### v1.2.0 (2026-04-19)
- ✅ 改用 Magento 标准的 `.media-width()` mixin
- ✅ 移动端隐藏下拉菜单（≤ 640px）
- ✅ 添加详细的断点说明和移动端替代方案建议
- ✅ 符合 Magento 官方响应式开发规范

### v1.1.0 (2026-04-19)
- ✅ 修正响应式样式位置（移到 @media-common 外部）
- ✅ 添加头像 min-width/min-height 防止变形
- ✅ 遵循 Magento LESS 编译规范

### v1.0.0 (2026-04-19)
- ✅ 初始版本发布
- ✅ 支持桌面端、平板端、移动端
- ✅ 完整的动画效果
- ✅ 遵循 Folix 电竞主题设计规范

---

**维护者**: FolixCode Team  
**最后更新**: 2026-04-19  
**架构状态**: 独立文件实现，不依赖 `_module.less`
