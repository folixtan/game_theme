# Folix Game Theme

A modern gaming e-commerce theme for Magento 2.4.8, extending the Luma theme.

## ✨ Features

- **Modern Design**: Gradient color scheme with blue and orange accents
- **Gaming Style**: Game badges, product cards, and gaming-specific UI
- **Responsive**: Fully responsive for all devices
- **Performance**: Optimized CSS using Magento UI Library
- **Standards Compliant**: Follows Magento 2 theming best practices

## 📦 Installation

### Via Composer
```bash
composer require folix/theme-frontend-game
```

### Manual Installation
1. Copy theme to `app/design/frontend/Folix/game-theme/`
2. Run Magento setup:
```bash
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## ⚙️ Configuration

1. Go to **Admin > Content > Design > Configuration**
2. Select your store view
3. Set **Default Theme** to "Folix Game Theme"
4. Save configuration

## 🎨 Theme Structure

```
Folix/game-theme/
├── web/css/source/
│   ├── _theme.less          # 覆盖 Luma 变量
│   ├── _variables.less       # 自定义变量
│   ├── _extends.less         # 引入模块化样式文件
│   └── extends/
│       ├── _global.less      # 全局样式：body、html
│       ├── _header.less      # 头部样式
│       ├── _footer.less      # 底部样式
│       ├── _navigation.less  # 导航样式
│       ├── _buttons.less     # 按钮样式
│       ├── _products.less    # 产品样式
│       └── _abstracts.less   # 抽象类（需要 &:extend）
├── Magento_Theme/
│   ├── web/css/source/_module.less
│   └── layout/default.xml
├── Magento_Catalog/
│   └── web/css/source/_module.less
├── etc/view.xml
├── theme.xml
└── registration.php
```

## 🔑 Key Concepts

### `_extends.less` 机制

1. **通过 `@import (reference)` 引入**
   - 直接选择器（如 `.page-header`）会正常输出
   - 抽象类（如 `.abs-*`）需要 `&:extend()` 才会输出

2. **父子主题合并**
   - 子主题的 `_extends.less` 会与父主题合并
   - 同选择器会覆盖父主题样式

3. **模块化组织**
   - 可以创建任意 Less 文件
   - 在 `_extends.less` 中 `@import` 引入即可

### 两种 CSS 编写方案

| 方案 | 文件位置 | 适用场景 |
|------|----------|----------|
| **方案一** | `_extends.less` + 模块文件 | 全局样式、轻量覆盖 |
| **方案二** | `Magento_*/_module.less` | 模块大改动（需复制父主题变量） |

### `_extends.less` vs `_module.less`

| 特性 | `_extends.less` | `_module.less` |
|------|-----------------|----------------|
| 继承方式 | 合并父主题 | 完全覆盖父主题 |
| 变量要求 | 无需复制 | 必须复制父主题变量 |
| 适用场景 | 全局样式、小改动 | 模块重构、大改动 |
| 文件位置 | `web/css/source/` | `Magento_*/web/css/source/` |

## 🎨 Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Primary Blue | #4A90E2 | Buttons, links, highlights |
| Secondary Orange | #FF6B35 | CTAs, accents, prices |
| Accent Purple | #6C5CE7 | Gradients |
| Dark Background | #1E293B | Header, footer |

## 🔧 Customization

### 添加新的样式模块

1. 创建文件 `web/css/source/extends/_custom.less`
2. 在 `_extends.less` 中引入：
```less
@import 'extends/_custom.less';
```

### 使用抽象类

```less
// 在任何 Less 文件中
.my-custom-button {
    &:extend(.abs-folix-button all);
}
```

### 覆盖变量

在 `_theme.less` 中覆盖父主题变量：
```less
@theme__color__primary: #YourColor;
```

## 📱 Responsive Breakpoints

- **Desktop**: ≥ 768px (`@screen__m`)
- **Mobile**: < 768px

## 📚 Requirements

- Magento 2.4.8+
- PHP 8.1+
- Composer

## 📄 License

OSL-3.0, AFL-3.0

---

**Version**: 2.0.0  
**Compatibility**: Magento 2.4.8
