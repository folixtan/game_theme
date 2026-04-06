# 产品详情页开发示例

## 需求描述

为 Folix Game Theme 定制产品详情页，实现以下功能：

1. **布局调整**：改为两栏布局（左图右信息）
2. **添加游戏特色徽章**：在产品名称下方显示 NEW/HOT/SALE 徽章
3. **重新设计"加入购物车"按钮**：渐变背景，游戏风格
4. **添加社交分享按钮**：在产品信息区域
5. **自定义产品标签页**：游戏详情、系统要求、用户评价

---

## 第1步：定位原文件

### 1.1 布局文件

```bash
# 产品详情页布局文件
vendor/magento/module-catalog/view/frontend/layout/catalog_product_view.xml
```

### 1.2 关键模板文件

```
vendor/magento/module-catalog/view/frontend/templates/
├── product/
│   └── view/
│       ├── addtocart.phtml     # 加入购物车按钮
│       ├── attribute.phtml     # 产品属性显示
│       ├── form.phtml          # 产品表单容器
│       ├── gallery.phtml       # 产品图片画廊
│       └── details.phtml       # 产品详情标签页
```

### 1.3 原生布局结构

```
┌─────────────────────────────────────────────────────────────────┐
│                         content                                  │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐  ┌──────────────────────────────────────┐│
│  │ product.info.media│  │ product.info.main                    ││
│  │   (图片画廊)       │  │   ├── product.info.price             ││
│  │                   │  │   │   ├── product.info.stock.sku     ││
│  │                   │  │   │   └── product.price.final        ││
│  │                   │  │   ├── product.info (表单)            ││
│  │                   │  │   │   └── product.info.addtocart     ││
│  │                   │  │   └── product.info.extrahint         ││
│  └──────────────────┘  └──────────────────────────────────────┘│
│                                                                  │
│  ┌──────────────────────────────────────────────────────────────┐│
│  │ product.info.details (标签页)                                ││
│  │   ├── product.info.description (Details)                     ││
│  │   └── product.attributes (More Information)                  ││
│  └──────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

---

## 第2步：创建主题文件结构

```bash
# 创建目录结构
app/design/frontend/Folix/game-theme/
├── Magento_Catalog/
│   ├── layout/
│   │   └── catalog_product_view.xml     # 产品详情页布局
│   ├── templates/
│   │   └── product/
│   │       └── view/
│   │           ├── addtocart.phtml      # 覆盖加入购物车
│   │           ├── badge.phtml          # 新增徽章组件
│   │           └── share.phtml          # 新增分享组件
│   └── web/
│       └── css/
│           └── source/
│               ├── _extend.less         # 模块样式入口
│               └── extend/
│                   └── _product-view.less
└── Magento_Theme/
    └── layout/
        └── default.xml                  # 全局布局（如需要）
```

---

## 第3步：实施修改

### 3.1 布局文件修改

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/layout/catalog_product_view.xml`

```xml
<?xml version="1.0"?>
<page layout="1column" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <!-- 
        ============================================
        第1个需求：添加游戏徽章
        在产品名称下方添加 NEW/HOT/SALE 徽章
        ============================================
        -->
        <referenceContainer name="product.info.main">
            <!-- 在价格之前添加徽章 -->
            <block class="Magento\Framework\View\Element\Template" 
                   name="product.info.badge" 
                   template="Magento_Catalog::product/view/badge.phtml" 
                   before="product.info.price">
                <arguments>
                    <argument name="badge_view_model" xsi:type="object">
                        Folix\GameTheme\ViewModel\Product\Badge
                    </argument>
                </arguments>
            </block>
        </referenceContainer>
        
        <!-- 
        ============================================
        第2个需求：添加社交分享按钮
        在产品信息区域添加分享组件
        ============================================
        -->
        <referenceContainer name="product.info.social">
            <block class="Magento\Framework\View\Element\Template" 
                   name="product.info.share" 
                   template="Magento_Catalog::product/view/share.phtml" 
                   after="product.info.addto">
                <arguments>
                    <argument name="share_view_model" xsi:type="object">
                        Folix\GameTheme\ViewModel\Product\Share
                    </argument>
                </arguments>
            </block>
        </referenceContainer>
        
        <!-- 
        ============================================
        第3个需求：添加自定义标签页
        在 Details 标签页后添加游戏相关标签
        ============================================
        -->
        <referenceBlock name="product.info.details">
            <!-- 游戏特色标签页 -->
            <block class="Magento\Catalog\Block\Product\View\Description" 
                   name="product.info.game_features" 
                   as="game_features" 
                   template="Magento_Catalog::product/view/attribute.phtml" 
                   group="detailed_info">
                <arguments>
                    <argument name="at_call" xsi:type="string">getGameFeatures</argument>
                    <argument name="at_code" xsi:type="string">game_features</argument>
                    <argument name="css_class" xsi:type="string">game-features</argument>
                    <argument name="at_label" xsi:type="string">none</argument>
                    <argument name="title" translate="true" xsi:type="string">Game Features</argument>
                    <argument name="sort_order" xsi:type="string">15</argument>
                </arguments>
            </block>
            
            <!-- 系统要求标签页 -->
            <block class="Magento\Catalog\Block\Product\View\Description" 
                   name="product.info.system_requirements" 
                   as="system_requirements" 
                   template="Magento_Catalog::product/view/attribute.phtml" 
                   group="detailed_info">
                <arguments>
                    <argument name="at_call" xsi:type="string">getSystemRequirements</argument>
                    <argument name="at_code" xsi:type="string">system_requirements</argument>
                    <argument name="css_class" xsi:type="string">system-requirements</argument>
                    <argument name="at_label" xsi:type="string">none</argument>
                    <argument name="title" translate="true" xsi:type="string">System Requirements</argument>
                    <argument name="sort_order" xsi:type="string">25</argument>
                </arguments>
            </block>
        </referenceBlock>
        
        <!-- 
        ============================================
        第4个需求：修改加入购物车按钮模板
        ============================================
        -->
        <referenceBlock name="product.info.addtocart" 
                        template="Magento_Catalog::product/view/addtocart.phtml"/>
    </body>
</page>
```

### 3.2 模板文件：徽章组件

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/templates/product/view/badge.phtml`

```php
<?php
/**
 * Folix Game Theme - Product Badge
 * 
 * 显示游戏徽章: NEW / HOT / SALE / DIRECT
 */

/** @var $block \Magento\Framework\View\Element\Template */
/** @var $viewModel \Folix\GameTheme\ViewModel\Product\Badge */

$viewModel = $block->getBadgeViewModel();
$product = $viewModel->getProduct();
$badges = $viewModel->getBadges($product);
?>

<?php if (!empty($badges)): ?>
<div class="product-badges" data-role="product-badges">
    <?php foreach ($badges as $badge): ?>
        <span class="game-badge game-badge--<?= $block->escapeHtmlAttr($badge['type']) ?>">
            <?= $block->escapeHtml($badge['label']) ?>
        </span>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

### 3.3 模板文件：覆盖加入购物车按钮

> ⚠️ **重要**：覆盖 = 复制原生内容 + 添加新功能，不是只写新内容！

**原生模板位置**：`vendor/magento/module-catalog/view/frontend/templates/product/view/addtocart.phtml`

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/templates/product/view/addtocart.phtml`

```php
<?php
/**
 * Folix Game Theme - Add to Cart Button
 * 
 * 覆盖说明：
 * 1. 复制原生内容（保留原生功能）
 * 2. 添加数量增减按钮（新功能）
 * 3. 修改按钮样式（新样式）
 * 
 * 原生模板：vendor/magento/module-catalog/view/frontend/templates/product/view/addtocart.phtml
 */

/** @var $block \Magento\Catalog\Block\Product\View */
?>
<?php 
// ============ 原生代码开始（必须保留）============
$_product = $block->getProduct(); 
$buttonTitle = __('Add to Cart'); 
?>
<?php if ($_product->isSaleable()): ?>
<div class="box-tocart">
    <div class="fieldset">
        <?php if ($block->shouldRenderQuantity()): ?>
        <div class="field qty">
            <label class="label" for="qty">
                <span><?= $block->escapeHtml(__('Qty')) ?></span>
            </label>
            <div class="control">
                <?php 
                // ============ 新增：数量增减按钮 ============
                ?>
                <button type="button" class="qty-btn qty-minus" data-action="decrease">
                    <span>−</span>
                </button>
                
                <?php 
                // ============ 原生代码（必须保留）============
                ?>
                <input type="number"
                       name="qty"
                       id="qty"
                       min="0"
                       value="<?= $block->getProductDefaultQty() * 1 ?>"
                       title="<?= $block->escapeHtmlAttr(__('Qty')) ?>"
                       class="input-text qty"
                       data-validate="<?= $block->escapeHtml(json_encode($block->getQuantityValidators())) ?>"
                />
                
                <?php 
                // ============ 新增：数量增减按钮 ============
                ?>
                <button type="button" class="qty-btn qty-plus" data-action="increase">
                    <span>+</span>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <button type="submit"
                    title="<?= $block->escapeHtmlAttr($buttonTitle) ?>"
                    class="action primary tocart folix-btn-gradient"
                    id="product-addtocart-button"
                    disabled>
                <?php 
                // ============ 新增：按钮图标 ============
                ?>
                <span class="btn-icon">🛒</span>
                <?php 
                // ============ 原生代码 ============
                ?>
                <span><?= $block->escapeHtml($buttonTitle) ?></span>
            </button>
            <?= $block->getChildHtml('', true) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
// ============ 原生代码（必须保留）============
?>
<script type="text/x-magento-init">
{
    "#product_addtocart_form": {
        "Magento_Catalog/js/validate-product": {}
    }
}
</script>

<?php 
// ============ 新增：数量控制初始化 ============
?>
<script type="text/x-magento-init">
{
    ".box-tocart": {
        "Magento_Catalog/js/qty-controls": {}
    }
}
</script>
```

### 覆盖开发的关键原则

```
┌─────────────────────────────────────────────────────────────────┐
│  1. 找到原生模板：vendor/magento/.../xxx.phtml                   │
│  2. 复制全部内容：不要遗漏任何原生代码                            │
│  3. 在复制的基础上修改：添加/修改你需要的部分                     │
│  4. 测试验证：确保原生功能正常 + 新功能正常                       │
└─────────────────────────────────────────────────────────────────┘
```

### 常见错误

```
❌ 错误1：只写新增内容，原生功能丢失
❌ 错误2：复制时遗漏原生代码
❌ 错误3：修改时删除了重要的原生 class/data 属性
❌ 错误4：没有保留原生的 JavaScript 初始化

✅ 正确：完整复制 + 谨慎修改 + 充分测试
```

### 3.4 模板文件：社交分享

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/templates/product/view/share.phtml`

```php
<?php
/**
 * Folix Game Theme - Social Share
 * 
 * 社交分享按钮组件
 */

/** @var $block \Magento\Framework\View\Element\Template */

$productName = $block->getProduct()->getName();
$productUrl = $block->getProduct()->getProductUrl();
?>

<div class="product-social-share">
    <span class="share-label"><?= $block->escapeHtml(__('Share:')) ?></span>
    
    <div class="share-buttons">
        <!-- Facebook -->
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $block->escapeUrl($productUrl) ?>"
           class="share-btn share-btn--facebook"
           target="_blank"
           rel="noopener"
           title="<?= $block->escapeHtmlAttr(__('Share on Facebook')) ?>">
            <svg class="icon icon-facebook"><!-- Facebook Icon --></svg>
        </a>
        
        <!-- Twitter -->
        <a href="https://twitter.com/intent/tweet?url=<?= $block->escapeUrl($productUrl) ?>&text=<?= $block->escapeUrl($productName) ?>"
           class="share-btn share-btn--twitter"
           target="_blank"
           rel="noopener"
           title="<?= $block->escapeHtmlAttr(__('Share on Twitter')) ?>">
            <svg class="icon icon-twitter"><!-- Twitter Icon --></svg>
        </a>
        
        <!-- Pinterest -->
        <a href="https://pinterest.com/pin/create/button/?url=<?= $block->escapeUrl($productUrl) ?>"
           class="share-btn share-btn--pinterest"
           target="_blank"
           rel="noopener"
           title="<?= $block->escapeHtmlAttr(__('Share on Pinterest')) ?>">
            <svg class="icon icon-pinterest"><!-- Pinterest Icon --></svg>
        </a>
        
        <!-- Copy Link -->
        <button type="button"
                class="share-btn share-btn--copy"
                data-url="<?= $block->escapeHtmlAttr($productUrl) ?>"
                title="<?= $block->escapeHtmlAttr(__('Copy Link')) ?>">
            <svg class="icon icon-link"><!-- Link Icon --></svg>
        </button>
    </div>
</div>
```

---

## 第4步：样式文件

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/web/css/source/extend/_product-view.less`

```less
// /**
//  * Folix Game Theme - Product View Styles
//  */

& when (@media-common = true) {
    
    //
    //  Product Badges - 游戏徽章
    //  _____________________________________________
    
    .product-badges {
        display: flex;
        gap: @folix-space-2;
        margin-bottom: @indent__base;
    }
    
    .game-badge {
        display: inline-flex;
        align-items: center;
        padding: @folix-space-1 @folix-space-3;
        border-radius: @folix-radius-full;
        font-size: 12px;
        font-weight: @font-weight__bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        
        &--new {
            background: @folix-badge-new-bg;
            color: white;
        }
        
        &--hot {
            background: @folix-badge-hot-bg;
            color: white;
        }
        
        &--sale {
            background: @folix-badge-sale-bg;
            color: white;
        }
        
        &--direct {
            background: @folix-gradient-primary;
            color: white;
        }
    }
    
    //
    //  Add to Cart Button - 游戏风格按钮
    //  _____________________________________________
    
    .box-tocart {
        .fieldset {
            display: flex;
            align-items: flex-end;
            gap: @indent__base;
        }
        
        .field.qty {
            margin-bottom: 0;
            
            .label {
                display: block;
                margin-bottom: @folix-space-2;
                color: @folix-text-secondary;
                font-size: 13px;
            }
            
            .control {
                display: flex;
                align-items: center;
                border: 2px solid @folix-border;
                border-radius: @folix-radius;
                overflow: hidden;
            }
        }
        
        .qty-btn {
            width: 44px;
            height: 44px;
            background: @folix-bg-panel;
            border: none;
            color: @folix-text-primary;
            font-size: 18px;
            cursor: pointer;
            transition: @folix-transition;
            
            &:hover {
                background: @folix-bg-hover;
            }
        }
        
        .input-text.qty {
            width: 60px;
            height: 44px;
            text-align: center;
            border: none;
            font-size: 16px;
            font-weight: @font-weight__semibold;
            
            &:focus {
                outline: none;
            }
        }
        
        .actions {
            flex: 1;
        }
        
        .folix-btn-gradient {
            width: 100%;
            padding: 14px 32px;
            background: @folix-gradient-primary;
            border: none;
            border-radius: @folix-radius;
            color: white;
            font-size: 16px;
            font-weight: @font-weight__bold;
            cursor: pointer;
            transition: @folix-transition-smooth;
            box-shadow: @folix-shadow-primary;
            
            &:hover:not([disabled]) {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(74, 144, 226, 0.4);
            }
            
            .btn-icon {
                margin-right: @folix-space-2;
            }
        }
    }
    
    //
    //  Social Share - 社交分享
    //  _____________________________________________
    
    .product-social-share {
        display: flex;
        align-items: center;
        gap: @folix-space-3;
        padding: @indent__base 0;
        border-top: 1px solid @folix-border-light;
        border-bottom: 1px solid @folix-border-light;
        margin-top: @indent__base;
        
        .share-label {
            color: @folix-text-secondary;
            font-size: 14px;
        }
        
        .share-buttons {
            display: flex;
            gap: @folix-space-2;
        }
        
        .share-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: @folix-radius-full;
            background: @folix-bg-panel;
            border: none;
            cursor: pointer;
            transition: @folix-transition;
            color: @folix-text-secondary;
            
            &:hover {
                background: @folix-primary;
                color: white;
            }
            
            &--facebook:hover {
                background: #1877F2;
            }
            
            &--twitter:hover {
                background: #1DA1F2;
            }
            
            &--pinterest:hover {
                background: #E60023;
            }
        }
    }
}

//
//  Mobile (< 768px)
//  _____________________________________________

.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    .box-tocart {
        .fieldset {
            flex-direction: column;
            align-items: stretch;
        }
        
        .field.qty {
            width: 100%;
            
            .control {
                justify-content: center;
            }
        }
    }
}
```

### 样式入口文件

**文件**: `app/design/frontend/Folix/game-theme/Magento_Catalog/web/css/source/_extend.less`

```less
// /**
//  * Folix Game Theme - Magento_Catalog Module Styles
//  */

// 引入全局变量
@import '../../../web/css/source/_variables.less';

// 引入模块样式
@import 'extend/_product-view.less';
```

---

## 第5步：测试验证

### 5.1 部署命令

```bash
# 清除缓存
php bin/magento cache:clean

# 部署静态文件
php bin/magento setup:static-content:deploy --theme=Folix/game-theme

# 或在开发模式下自动部署
php bin/magento deploy:mode:set developer
```

### 5.2 验证清单

```
✅ 产品详情页布局是否正确
✅ 游戏徽章是否显示（根据产品属性）
✅ 加入购物车按钮样式是否正确
✅ 数量增减按钮是否工作
✅ 社交分享按钮是否显示
✅ 自定义标签页是否显示
✅ 移动端响应式是否正常
```

### 5.3 调试方法

```bash
# 开启 Template Hints
Admin → Stores → Configuration → Advanced → Developer → Debug
→ Template Path Hints = Yes (for your IP)

# 检查布局是否加载
grep -r "product.info.badge" var/log/

# 检查模板是否被使用
# 打开产品详情页，查看 HTML 注释中的模板路径
```

---

## 总结：开发流程回顾

| 步骤 | 内容 | 文件 |
|------|------|------|
| **1. 需求分析** | 确定要修改的功能 | 无 |
| **2. 定位原文件** | 查找布局和模板位置 | `vendor/magento/module-catalog/...` |
| **3. 创建主题文件** | 按约定创建目录结构 | `app/design/frontend/Folix/...` |
| **4. 布局修改** | 使用 referenceContainer 添加 block | `layout/catalog_product_view.xml` |
| **5. 模板修改** | 创建/覆盖模板文件 | `templates/product/view/*.phtml` |
| **6. 样式修改** | 编写模块化 Less | `web/css/source/extend/_product-view.less` |
| **7. 测试验证** | 清缓存、部署、测试 | CLI + 浏览器 |
