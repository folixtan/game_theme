# Folix Game Theme - 开发总结

## 项目概述
基于Magento 2.4.8-p4官方规范开发的游戏电商主题，继承自Magento/luma，采用蓝橙渐变色方案（蓝#4A90E2 + 橙#FF6B35）。

## 继承架构
```
Magento/blank (基础)
    └── Magento/luma (父主题)
        └── Folix/game-theme (本主题)
```

## 核心特性

### 1. 渐变色方案
- **主蓝色**: `#4A90E2`
- **主橙色**: `#FF6B35`
- **深蓝背景**: `#1E293B`
- **紫色强调**: `#7C3AED`
- **金色高亮**: `#F59E0B`

### 2. UI组件
- **渐变按钮**: 蓝到橙的渐变，悬停时更鲜艳
- **徽章系统**: NEW/HOT/SALE徽章，带有闪烁动画
- **产品卡片**: 悬停上浮+阴影效果
- **跑马灯**: 自动滚动的公告栏

### 3. 主题样式架构

#### CSS文件结构规范 ⭐

**所有样式文件统一放到 `web/css/source/` 下，按模块分目录：**

```
web/css/source/
├── _extend.less         # 入口文件，引入所有样式
├── _variables.less      # 变量定义
├── _theme.less          # 覆盖父主题变量
├── _global.less         # 全局基础样式
├── _buttons.less        # 按钮样式
├── _abstracts.less      # 抽象类
│
├── Magento_Theme/       # Theme 模块样式
│   ├── _header.less
│   ├── _footer.less
│   ├── _navigation.less
│   └── _login-dropdown.less
│
├── Magento_Catalog/     # Catalog 模块样式
│   ├── _catalog.less         # 产品列表
│   └── _product-view.less    # 产品详情
│
├── Magento_Cms/         # CMS 模块样式
│   ├── _slider.less
│   └── _components.less
│
├── Magento_Customer/    # Customer 模块样式
│   └── _customer.less
│
├── Magento_LayeredNavigation/
│   └── _layered-navigation.less
│
└── Magento_Review/
    └── _review.less
```

**模块目录下仅保留 `_module.less` 用于覆盖父主题变量，其他样式全部放到 `web/css/source/Magento_XXX/` 下。**

#### _extend.less 示例
```less
// 全局样式
@import '_global.less';
@import '_buttons.less';
@import '_abstracts.less';

// 模块样式
@import 'Magento_Theme/_header.less';
@import 'Magento_Theme/_footer.less';
@import 'Magento_Catalog/_product-view.less';
// ... 其他模块
```

#### 媒体查询写法 ⭐
```less
// 通用样式（移动优先）
& when (@media-common = true) {
    .selector {
        // 移动端样式
    }
}

// 媒体查询 - 必须在 & when 块外部，与它平级
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .selector {
        // 桌面端样式
    }
}

.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    .selector {
        // 移动端样式
    }
}
```

#### 开发原则
1. **样式统一放 `web/css/source/`**：按模块分目录，不在模块目录下放样式
2. **入口文件只做 import**：`_extend.less` 只引入其他文件，不写样式
3. **扩展优先**: 使用`_extend.less`和`<referenceContainer>`
4. **覆盖次之**: 使用`_theme.less`覆盖变量

### 4. 布局修改策略

#### 头部布局（default.xml）
```xml
<!-- 扩展而非覆盖 -->
<referenceContainer name="header.container">
    <referenceContainer name="header.panel">
        <!-- 添加跑马灯 -->
        <block class="Magento\Framework\View\Element\Template" 
               name="header.marquee" 
               template="Magento_Theme::html/header/marquee.phtml" 
               after="top.links"/>
    </referenceContainer>
</referenceContainer>
```

#### 尾部布局（default.xml）
```xml
<!-- 新增Footer栏目块 -->
<referenceContainer name="footer-container">
    <container name="footer.top" htmlTag="div" htmlClass="footer-top">
        <block class="Magento\Framework\View\Element\Template" 
               name="footer.columns" 
               template="Magento_Theme::html/footer/columns.phtml"/>
    </container>
</referenceContainer>
```

## 文件清单

### 布局文件
- `Magento_Theme/layout/default.xml` - 主布局配置

### 模板文件
- `Magento_Theme/templates/html/header/marquee.phtml` - 跑马灯公告
- `Magento_Theme/templates/html/footer/columns.phtml` - Footer栏目
- `Magento_Theme/templates/html/footer/copyright.phtml` - Copyright区域

### 样式文件
- `web/css/source/_theme.less` - 变量覆盖
- `web/css/source/_variables.less` - 新变量定义
- `web/css/source/_extend.less` - 扩展入口
- `web/css/source/extends/_pages.less` - 页面样式
- `web/css/source/extends/_modules.less` - 模块样式
- `web/css/source/extends/_components.less` - 组件样式
- `web/css/source/extends/_product.less` - 产品样式

### JavaScript文件
- `web/js/marquee.js` - 跑马灯组件
- `web/js/theme.js` - 主题初始化

### 静态资源
- `media/preview.svg` - 主题预览图

## 颜色规范

### 渐变组合
| 名称 | 起始色 | 结束色 | 用途 |
|------|--------|--------|------|
| 主渐变 | `#4A90E2` | `#FF6B35` | 按钮、徽章、Hero |
| 深蓝渐变 | `#1E293B` | `#0F172A` | 头部Top Bar、Footer |
| 紫色渐变 | `#7C3AED` | `#9333EA` | Copyright、特殊强调 |

### 文字颜色
- 主标题: `#1E293B`
- 副标题: `#475569`
- 链接: `#4A90E2`
- 链接悬停: `#FF6B35`
- 反白文字: `#FFFFFF`

## 响应式断点

遵循Magento UI Library断点：
- `@screen__xxs`: 320px
- `@screen__xs`: 480px
- `@screen__s`: 640px
- `@screen__m`: 768px
- `@screen__l`: 1024px
- `@screen__xl`: 1440px

## 测试建议

### 验证项
1. ✓ 头部布局和样式与原型一致
2. ✓ 尾部布局和样式与原型一致
3. ✓ 产品卡片悬停效果
4. ✓ 徽章渐变和动画
5. ✓ 跑马灯滚动功能
6. ✓ 响应式布局（移动端）

### 测试命令
```bash
# 部署主题
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy --theme=Folix/game-theme

# 清除缓存
php bin/magento cache:clean

# 编译Less
php bin/magento dev:source-theme:deploy
```

## 扩展建议

### 新增模块样式
1. 创建 `web/css/source/extends/_new_module.less`
2. 在 `_extend.less` 中导入：`@import 'extends/_new_module.less';`

### 新增布局修改
1. 在 `Magento_Theme/layout/` 创建新XML文件
2. 使用 `<referenceContainer>` 扩展而非 `<container>` 覆盖

### 新增模板文件
1. 在 `Magento_Theme/templates/` 创建新PHTML文件
2. 使用Magento原生选择器结构

## 开发规范提醒

1. **选择器优先级**: 使用原生选择器（如`.page-header`），避免自定义选择器
2. **变量使用**: 使用Magento UI Library函数（如`.lib-css()`）
3. **文件组织**: 遵循扩展优先原则
4. **注释规范**: 每个文件顶部添加功能说明和位置信息

---

## 头部布局详细说明

### Magento布局继承机制（重要！）

Magento主题的布局文件是通过**继承机制**工作的，子主题的`default.xml`会与父主题的`default.xml`**合并**，而非覆盖。

#### 四种操作方式

| 操作 | 语法 | 说明 |
|------|------|------|
| **新增容器** | `<container name="xxx" ...>` | 在 `referenceContainer` 内新建容器 |
| **新增Block** | `<block class="..." name="xxx" ...>` | 在容器内添加新Block |
| **移动元素** | `<move element="xxx" destination="yyy" />` | 将原生Block移到新位置 |
| **删除元素** | `<referenceBlock name="xxx" remove="true" />` | 移除不需要的Block |

#### 示例：Folix头部布局

```xml
<!-- 1. 新增容器 -->
<referenceContainer name="header.panel">
    <container name="folix.top.left" htmlTag="div" htmlClass="header-wrapper-links-top" before="-" />
    <container name="header-marquee" htmlTag="div" htmlClass="header-marquee" after="folix.top.left" />
    <container name="header-wrapper-right" htmlTag="div" htmlClass="header-wrapper-right" after="header-marquee" />
</referenceContainer>

<!-- 2. 删除不需要的元素 -->
<referenceBlock name="header.links" remove="true" />
<referenceBlock name="skip_to_content" remove="true" />

<!-- 3. 移动原生元素 -->
<move element="store_language" destination="header-wrapper-right" />
<move element="navigation.sections" destination="header-wrapper" after="logo" />
<move element="top.search" destination="header-wrapper" after="navigation.sections" />
<move element="minicart" destination="header-wrapper" after="top.search" />

<!-- 4. 新增Block -->
<referenceContainer name="folix.top.left">
    <block class="..." name="folix.game.top.links" template="..." />
</referenceContainer>
```

### Folix头部结构

**Top Bar (`header.panel`)**：
```
folix.top.left (.header-wrapper-links-top)
    └── 辅助链接（新闻、奖励、支持）
header-marquee (.header-marquee)
    └── 跑马灯公告
header-wrapper-right (.header-wrapper-right)
    ├── APP下载链接
    └── 语言切换 (移动自 store_language)
```

**Main Bar (`header-wrapper`)**：
```
logo [原生]
navigation.sections [移动到此处]
top.search [移动到此处]
folix.login.button [新增]
minicart [移动到此处]
```

### 响应式设计

**桌面端 (>= 768px)**：
- Top Bar：完整显示所有元素
- Main Bar：Logo | 导航 | 搜索 | 登录 | 购物车
- 头部sticky定位

**移动端 (< 768px)**：
- Top Bar：仅显示跑马灯
- Main Bar：导航按钮 | Logo | 登录/购物车 | 搜索框(全宽)

### 关键选择器

| 选择器 | 用途 |
|--------|------|
| `.page-header` | 整个头部容器 |
| `.panel.wrapper` | Top Bar外层 |
| `.panel.header` | Top Bar内容区 |
| `.header.content` | Main Bar内容区 |
| `.header-wrapper-links-top` | 左侧辅助链接容器 |
| `.header-marquee` | 跑马灯容器 |
| `.header-wrapper-right` | 右侧容器 |

### 验证方法

```bash
# 部署主题
php bin/magento setup:static-content:deploy --theme=Folix/game-theme
php bin/magento cache:clean
```

检查项：
- [ ] Top Bar背景为深蓝渐变
- [ ] Main Bar底部有橙色边框
- [ ] 所有元素正确显示
- [ ] 移动端响应式布局正常

---

**开发时间**: 2024年
**Magento版本**: 2.4.8-p4
**主题版本**: 1.0.0
