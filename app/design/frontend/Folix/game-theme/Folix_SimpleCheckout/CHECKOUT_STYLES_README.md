# Folix Simple Checkout - 样式文件组织说明

## 📁 文件结构

```
Folix_SimpleCheckout/web/css/source/
└── _checkout-custom.less    # 独立的Checkout页面样式文件
```

## 🎯 Magento标准LESS语法规范

### 核心结构

```less
// ========================================
// 1. 变量定义
// ========================================
@folix-primary: #4a90e2;
@indent-base: 16px;

// ========================================
// 2. 公共样式 - 使用 & when (@media-common = true)
// ========================================
& when (@media-common = true) {
    // 所有屏幕尺寸通用的样式
    .checkout-container { ... }
    .opc-wrapper { ... }
}

// ========================================
// 3. 移动端样式 - 媒体查询
// ========================================
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 屏幕宽度 <= 768px 的样式
}

// ========================================
// 4. 桌面端样式 - 媒体查询
// ========================================
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    // 屏幕宽度 >= 768px 的样式
}
```

## 🔑 关键概念解析

### 1. `& when (@media-common = true)` 的作用

**这是什么？**
- Magento LESS编译系统的全局变量
- 用于标记"公共样式"（非媒体查询内的样式）

**为什么需要它？**
```less
// ❌ 错误写法：可能导致样式重复输出
.checkout-container {
    padding: 16px;
}

// ✅ 正确写法：确保只输出一次
& when (@media-common = true) {
    .checkout-container {
        padding: 16px;
    }
}
```

**工作原理：**
1. Magento的LESS编译器在处理主题时，会设置 `@media-common` 变量
2. 当 `@media-common = true` 时，包裹在其中的样式会被编译输出
3. 这确保了公共样式不会被多次编译（特别是在多主题继承场景中）

### 2. `.media-width()` Mixin

**这是Magento标准的响应式媒体查询方式**

#### 移动端查询
```less
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 当屏幕宽度 <= @screen__m (默认768px) 时生效
    .checkout-container {
        flex-direction: column; // 单列布局
    }
}
```

#### 桌面端查询
```less
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    // 当屏幕宽度 >= @screen__m (默认768px) 时生效
    .checkout-container {
        display: flex; // 多列布局
    }
}
```

**优势：**
- ✅ 自动处理浏览器前缀
- ✅ 与主题断点系统集成
- ✅ 支持多断点扩展（`@screen__s`, `@screen__m`, `@screen__l`, `@screen__xl`）
- ✅ 避免媒体查询冲突

## ✨ 文件组织结构

### 📋 完整结构示例

```less
/**
 * 文件头部注释
 */

// ========================================
// Part 1: 变量定义
// ========================================
@folix-primary: #4a90e2;
@indent-base: 16px;
// ... 其他变量

// ========================================
// Part 2: 公共样式（所有屏幕尺寸）
// ========================================
& when (@media-common = true) {
    
    // 全局容器
    .checkout-container { ... }
    
    // OPC Wrapper
    .opc-wrapper { ... }
    
    // 登录表单
    .form-login { ... }
    
    // 商品摘要
    .block.items-in-cart { ... }
    
    // 支付方式
    .payment-method { ... }
    
    // 侧边栏
    .sidebar-folix-content .opc-sidebar { ... }
    
    // ... 更多组件
    
} // end & when (@media-common = true)

// ========================================
// Part 3: 移动端样式 (≤768px)
// ========================================
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    body {
        padding: 20px 10px;
    }
    
    .checkout-container {
        flex-direction: column;
    }
    
    // 移动端特定调整
    .product-image-wrapper img {
        width: 60px;
        height: 60px;
    }
    
    // 固定底部侧边栏
    .sidebar-folix-content {
        position: fixed;
        bottom: 0;
        // ...
    }
}

// ========================================
// Part 4: 桌面端样式 (≥768px)
// ========================================
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .checkout-container {
        display: flex;
        gap: 32px;
    }
    
    // Sticky定位侧边栏
    .sidebar-folix-content .opc-sidebar {
        position: sticky;
        top: 20px;
    }
}
```

## 📊 响应式策略对比

| 特性 | 公共样式 | 移动端 (≤768px) | 桌面端 (≥768px) |
|------|---------|----------------|----------------|
| **语法** | `& when (@media-common = true)` | `.media-width(... 'max' ...)` | `.media-width(... 'min' ...)` |
| **布局** | Flex基础结构 | 单列垂直 | 双列Flex |
| **侧边栏** | Sticky定位（默认） | Fixed底部面板 | Sticky定位 |
| **商品图** | 80x80px | 60x60px | 80x80px |
| **按钮** | 标准高度 | 最小50px | 标准高度 |
| **Input字体** | 15px | 16px（防iOS缩放） | 15px |

## 🔧 使用方法

### 方式1：在主题中引入（推荐）

在主题的 `web/css/source/_extend.less` 中添加：

```less
@import 'Folix_SimpleCheckout/css/source/_checkout-custom';
```

### 方式2：在模块XML布局中加载

在 `checkout_index_index.xml` 的 `<head>` 部分添加：

```xml
<head>
    <css src="Folix_SimpleCheckout::css/checkout-custom.css"/>
</head>
```

### 方式3：合并到现有Checkout样式

将文件内容复制到：
```
app/design/frontend/Folix/game-theme/web/css/source/Magento_Checkout/_checkout.less
```

## ⚠️ 注意事项

### 1. **编译流程**
修改LESS后，必须执行以下命令重新生成静态资源：

```bash
cd /var/www/html/game/game
bin/magento cache:clean
bin/magento cache:flush
bin/magento setup:static-content:deploy -f en_US zh_Hans_CN
```

### 2. **为什么使用 `& when (@media-common = true)`？**

**场景演示：**
```less
// 假设你有两个主题继承关系：
// Parent Theme -> Child Theme

// ❌ 不使用 @media-common
.checkout-container {
    padding: 16px;
}
// 可能被编译两次（父主题 + 子主题），导致CSS冗余

// ✅ 使用 @media-common
& when (@media-common = true) {
    .checkout-container {
        padding: 16px;
    }
}
// Magento编译器确保只输出一次
```

### 3. **变量命名规范**
- 模块级变量使用 `@folix-*` 前缀
- 避免与主题全局变量冲突
- 如需使用主题变量，取消顶部的 `@import` 注释

### 4. **调试技巧**

**检查LESS编译结果：**
```bash
# 查看编译后的CSS文件
cat pub/static/frontend/Folix/game-theme/en_US/Folix_SimpleCheckout/css/checkout-custom.css

# 搜索特定的选择器
grep -n "checkout-container" pub/static/frontend/Folix/game-theme/en_US/Folix_SimpleCheckout/css/checkout-custom.css
```

**验证媒体查询：**
```javascript
// 在浏览器控制台执行
window.innerWidth  // 查看当前窗口宽度
getComputedStyle(document.querySelector('.checkout-container')).display
```

## 📚 参考资源

- [Magento 2 LESS官方文档](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/css-topics/css-preprocess.html)
- [Magento 2响应式设计指南](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/responsive-web-design/rwd.html)
- [LESS语言特性](http://lesscss.org/)
- [Magento UI Library](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/css-topics/css-themes.html)

## 📝 版本历史

- **v1.1.0** (2026-04-26)
  - 添加 `& when (@media-common = true)` 包裹公共样式
  - 符合Magento标准LESS语法规范
  - 确保样式在多主题继承场景中不重复输出

- **v1.0.0** (2026-04-26)
  - 初始版本
  - 按照Magento标准规范组织样式
  - 分离公共、移动端、桌面端样式

---

**作者**: Lingma (灵码)  
**最后更新**: 2026-04-26
