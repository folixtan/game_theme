# PortGame Light Theme

## 简介

PortGame 浅色简约主题，采用 Apple/Stripe 风格的现代设计。

### 特点

- 🎨 **简约风格**：白色背景 + 蓝色主色 + 橙色强调
- ⚡ **一键换肤**：与深色主题共享相同的变量名结构
- 📱 **响应式设计**：完美适配移动端和桌面端
- 🚀 **高性能**：优化的 CSS 结构和轻量化阴影效果

### 适用场景

- ✅ 通用电商平台
- ✅ 数字产品销售
- ✅ SaaS 平台
- ✅ 企业官网

---

## 文件结构

```
game-theme-light/
├── composer.json                    # Composer 配置
├── registration.php                 # 主题注册文件
├── theme.xml                        # 主题配置文件
├── media/
│   └── preview-light.jpg           # 主题预览图（待添加）
└── web/
    └── css/
        └── source/
            ├── _custom-variables-light.less  # 浅色变量定义
            └── _theme.less                    # 主题变量覆盖
```

---

## 配色方案

### 主色系

| 变量名 | 值 | 用途 |
|--------|-----|------|
| `@folix-primary` | `#FFFFFF` | 主背景 |
| `@folix-secondary` | `#3B82F6` | 品牌蓝（主按钮） |
| `@folix-accent` | `#F97316` | 活力橙（强调色） |
| `@folix-green` | `#10B981` | 清新绿（成功状态） |

### 文字色

| 变量名 | 值 | 用途 |
|--------|-----|------|
| `@folix-text-primary` | `#1F2937` | 主文字 |
| `@folix-text-secondary` | `#4B5563` | 次要文字 |
| `@folix-text-tertiary` | `#6B7280` | 辅助文字 |

### 背景色

| 变量名 | 值 | 用途 |
|--------|-----|------|
| `@folix-bg-page` | `#F9FAFB` | 页面背景 |
| `@folix-bg-panel` | `#FFFFFF` | 面板背景 |
| `@folix-bg-card` | `#FFFFFF` | 卡片背景 |

---

## 安装与启用

### 1. 安装主题

主题文件已放置在正确位置，运行以下命令：

```bash
bin/magento setup:upgrade
bin/magento setup:static-content:deploy -f
bin/magento cache:clean
```

### 2. 启用主题

在 Magento 后台：

1. 进入 **Content > Design > Configuration**
2. 编辑当前 Store View
3. 在 **Applied Theme** 中选择 **PortGame Light Theme**
4. 保存配置

### 3. 切换回深色主题

只需在后台将主题切换回 **Folix Game Theme** 即可。

---

## 与深色主题的区别

| 特性 | 深色主题（Esports） | 浅色主题（Light） |
|------|-------------------|------------------|
| **主背景** | `#0A1628` 深蓝 | `#F9FAFB` 浅灰 |
| **卡片背景** | `#1E293B` 深灰 | `#FFFFFF` 白色 |
| **主文字** | `#FFFFFF` 白色 | `#1F2937` 深灰 |
| **品牌色** | `#FFD700` 金色 | `#3B82F6` 蓝色 |
| **强调色** | `#FF3B30` 电竞红 | `#F97316` 活力橙 |
| **按钮主色** | 金色渐变 | 蓝色渐变 |
| **边框** | 透明发光 | 浅灰实线 |
| **阴影** | 强发光效果 | 轻微阴影 |
| **风格** | 酷炫、电竞、沉浸 | 清新、专业、高效 |
| **适用场景** | 游戏充值、虚拟商品 | 通用电商、数字产品 |

---

## 一键换肤原理

两个主题使用**完全相同的变量名**（`@folix-*`），只是赋予不同的值：

```less
// 深色主题（game-theme/web/css/source/_custom-variables-esports.less）
@folix-bg-page: #0A1628;
@folix-text-primary: #FFFFFF;
@folix-secondary: #FFD700;

// 浅色主题（game-theme-light/web/css/source/_custom-variables-light.less）
@folix-bg-page: #F9FAFB;
@folix-text-primary: #1F2937;
@folix-secondary: #3B82F6;
```

在 `_theme.less` 中，两个主题的结构完全相同，只是引入的变量文件不同：

```less
// 深色主题
@import '_custom-variables-esports.less';

// 浅色主题
@import '_custom-variables-light.less';
```

这样在后台切换主题时，所有页面元素自动应用新主题的配色，无需修改任何业务代码。

---

## 自定义

如需调整配色，只需修改 `_custom-variables-light.less` 文件中的变量值即可。

### 示例：修改品牌色

```less
// 将品牌色从蓝色改为紫色
@folix-secondary: #8B5CF6;
@folix-secondary-dark: #7C3AED;
@folix-secondary-light: #A78BFA;
```

修改后运行：

```bash
bin/magento setup:static-content:deploy -f
bin/magento cache:clean
```

---

## 技术支持

如有问题，请联系开发团队。

---

**版本**: 1.0.0  
**最后更新**: 2026-05-01
