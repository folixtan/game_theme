# Folix Game Theme - 文件检查清单

## ✅ 核心配置文件
- [x] `theme.xml` - 主题配置
- [x] `etc/view.xml` - 视图配置
- [x] `composer.json` - Composer 配置
- [x] `registration.php` - 主题注册

## ✅ 布局文件 (Layout XML)
- [x] `Magento_Theme/layout/default.xml` - 默认布局
- [x] `Magento_Catalog/layout/catalog_category_view.xml` - 分类页布局
- [x] `Magento_Catalog/layout/catalog_product_view.xml` - 产品页布局
- [x] `Magento_Customer/layout/customer_account.xml` - 客户账户布局

## ✅ 模板文件 (Templates)
### Header
- [x] `Magento_Theme/templates/html/header/top-bar.phtml`
- [x] `Magento_Theme/templates/html/header/main-bar.phtml`
- [x] `Magento_Theme/templates/html/header/marquee.phtml`
- [x] `Magento_Theme/templates/html/header/links-top-left.phtml`
- [x] `Magento_Theme/templates/html/header/app-link.phtml`
- [x] `Magento_Theme/templates/html/header/login-button.phtml`
- [x] `Magento_Theme/templates/html/header/search.phtml`

### Footer
- [x] `Magento_Theme/templates/html/footer/copyright.phtml`
- [x] `Magento_Theme/templates/html/footer/links.phtml`
- [x] `Magento_Theme/templates/html/footer/marquee.phtml`

### Components
- [x] `Magento_Theme/templates/html/slider/hero.phtml` - Hero Slider
- [x] `Magento_Theme/templates/html/modal/login.phtml` - 登录弹窗
- [x] `Magento_Theme/templates/html/home/promo-banner.phtml` - 促销横幅

### Catalog
- [x] `Magento_Catalog/templates/product/list/home-products.phtml` - 首页产品列表

## ✅ JavaScript 文件
**位置：`Magento_Theme/web/js/`**

- [x] `mobile-header.js` - 移动端头部交互（全局加载）
- [x] `marquee.js` - 跑马灯组件（模板引用）
- [x] `slider.js` - 滑块组件（模板引用）
- [x] `modal.js` - 弹窗组件（模板引用）

## ✅ RequireJS 配置
- [x] `Magento_Theme/requirejs-config.js` - RequireJS 配置（正确位置）
- ❌ ~~`requirejs-config.js`~~ - 根目录配置（已删除，避免冲突）

## ✅ 样式文件 (Less)
### 核心文件
- [x] `web/css/source/_variables.less` - 变量定义
- [x] `web/css/source/_theme.less` - 主题样式
- [x] `web/css/source/_extend.less` - 扩展样式入口

### 模块化样式
- [x] `web/css/source/extends/_global.less` - 全局样式
- [x] `web/css/source/extends/_header.less` - 头部样式
- [x] `web/css/source/extends/_footer.less` - 底部样式
- [x] `web/css/source/extends/_navigation.less` - 导航样式
- [x] `web/css/source/extends/_buttons.less` - 按钮样式
- [x] `web/css/source/extends/_products.less` - 产品样式
- [x] `web/css/source/extends/_components.less` - 组件样式
- [x] `web/css/source/extends/_pages.less` - 页面样式
- [x] `web/css/source/extends/_abstracts.less` - 抽象类

### 模块样式
- [x] `Magento_Theme/web/css/source/_module.less` - Magento_Theme 模块样式
- [x] `Magento_Catalog/web/css/source/_module.less` - Magento_Catalog 模块样式

## ✅ 路径说明

### JavaScript 文件路径
- **正确**：`Magento_Theme/web/js/xxx.js`
- **模板引用**：`Magento_Theme/js/xxx`（不需要 `.js` 后缀）

### RequireJS 加载方式
1. **全局加载**（`deps`）：页面加载时立即执行
   - `mobile-header.js` - 需要立即初始化移动端交互

2. **按需加载**（`data-mage-init`）：模板中引用时才加载
   - `marquee.js` - 由模板 `marquee.phtml` 引用
   - `slider.js` - 由模板 `hero.phtml` 引用
   - `modal.js` - 由模板 `login.phtml` 引用

## 🚀 部署检查

部署前请确认：
1. ✅ 所有 JS 文件在 `Magento_Theme/web/js/` 目录下
2. ✅ 只有一个 `requirejs-config.js` 在 `Magento_Theme/` 目录下
3. ✅ 清除 RequireJS 缓存
4. ✅ 重新编译静态文件

```bash
# 清除缓存
php bin/magento cache:clean
rm -rf pub/static/frontend/Folix/*
rm -rf var/view_preprocessed/pub/static/frontend/Folix/*
rm -rf pub/static/_requirejs/*
rm -rf var/view_preprocessed/pub/static/_requirejs/*

# 重新部署
php bin/magento setup:static-content:deploy --theme=Folix/game-theme -f
php bin/magento cache:flush
```
