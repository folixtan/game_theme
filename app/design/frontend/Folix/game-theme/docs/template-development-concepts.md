# Adobe Commerce 模板开发概念

> 来源: https://developer.adobe.com/commerce/frontend-core/guide/templates/override

## 一、模板基础概念

### 1. 模板是如何初始化的

模板在**布局文件**中初始化，每个布局 block 都有关联的模板。

模板通过 `<block>` 指令的 `template` 属性指定：

```xml
<block class="Magento\Catalog\Block\Category\View" 
       name="category.image" 
       template="Magento_Catalog::category/image.phtml">
```

**解析**：
- `category.image` block 由 `Magento_Catalog` 模块的 `category/image.phtml` 模板渲染
- 模板位置: `app/code/Magento/Catalog/view/frontend/templates/category/image.phtml`

### 2. 模板也可以在 PHP Block 类中指定

```xml
<block class="Magento\Review\Block\View" name="review_view" ifconfig="catalog/review/active"/>
```

在 Block 类中：

```php
// app/code/Magento/Review/Block/View.php
protected $_template = 'Magento_Review::view.phtml';
```

## 二、模板位置

| 类型 | 位置 |
|------|------|
| **模块模板** | `<module_dir>/view/frontend/templates/<path_to_templates>` |
| **主题模板** | `<theme_dir>/<Namespace>_<Module>/templates/<path_to_templates>` |

**示例**：
- `app/code/Magento/Catalog/view/frontend/templates/product/widget/new/content/new_grid.phtml`
- `app/design/frontend/Folix/game-theme/Magento_Catalog/templates/product/view.phtml`

## 三、模板覆盖规则（重要！）

### 核心概念：覆盖 = 同名文件替换

```
⚠️ 重要：模板覆盖是"同名文件替换"，不是"合并"！
```

当你创建同名模板文件后：
- ✅ 主题模板**完全替代**原生模板
- ❌ 原生模板**不再被使用**
- ❌ 不会自动合并两个文件的内容

### 覆盖的正确姿势

```
┌─────────────────────────────────────────────────────────────────┐
│  错误理解：覆盖 = 在原生基础上添加                                │
│  ❌ 只写新增的内容，期望自动合并                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  正确理解：覆盖 = 用新文件替换旧文件                              │
│  ✅ 必须复制原生内容，然后在基础上修改                            │
└─────────────────────────────────────────────────────────────────┘
```

### 实例说明

**需求**：在 addtocart.phtml 添加一个自定义按钮

```
❌ 错误做法：只写新增内容
┌──────────────────────────────────────┐
│ app/design/.../addtocart.phtml       │
│ ─────────────────────────────────────│
│ <button class="my-btn">自定义按钮</button> │
│                                      │
│ 结果：原生加入购物车功能丢失！       │
└──────────────────────────────────────┘

✅ 正确做法：复制原生内容 + 添加新内容
┌──────────────────────────────────────┐
│ app/design/.../addtocart.phtml       │
│ ─────────────────────────────────────│
│ <?php                                │
│ // 复制原生内容（保留原生功能）       │
/** @var $block \Magento\Catalog\Block\Product\View */
$_product = $block->getProduct();
$buttonTitle = __('Add to Cart');
// ... 原生代码 ...
│ ?>                                   │
│                                      │
│ <!-- 新增自定义按钮 -->              │
│ <button class="my-btn">自定义按钮</button> │
└──────────────────────────────────────┘
```

### 优先级规则

对于同名模板文件：

1. **主题模板覆盖模块模板**
2. **子主题模板覆盖父主题模板**

```
优先级: 子主题 > 父主题 > 模块

最终使用: 优先级最高的同名文件（其他同名文件被忽略）
```

**示例**：
```
模块模板: vendor/magento/module-catalog/view/frontend/templates/product/view.phtml
父主题:   vendor/magento/theme-frontend-luma/Magento_Catalog/templates/product/view.phtml
子主题:   app/design/frontend/Folix/game-theme/Magento_Catalog/templates/product/view.phtml
                                                                     
最终使用: Folix/game-theme 版本 (优先级最高)
```

## 四、根模板

`<Magento_Theme_module_dir>/view/base/templates/root.phtml` 是所有店铺页面的根模板。

**特点**：
- 包含 `doctype` 声明
- 贡献所有页面的 `<head>` 和 `<body>` 部分
- 可以像其他模板一样被主题覆盖

## 五、在模板中获取布局参数

布局文件中设置的参数值，在模板中通过 block 的方法访问：

**布局文件**：
```xml
<argument name="store_name" xsi:type="string">ExampleCorp</argument>
```

**模板中获取**：
```php
// 获取参数值
$block->getData('store_name')
$block->getStoreName()

// 检查参数是否存在
$block->hasData('store_name')
$block->hasStoreName()
```

### 重要原则

> **模板必须不在其代码中实例化新对象。所有对象必须从 Block 对象传递。**
> 
> 这样，模板保持无状态，其唯一职责是显示从 Block 对象接收的数据。

## 六、PHP 短标签

模板中可以使用 PHP 短标签：

```php
<?= $block->getAdjustmentsHtml() ?>
// 等同于
<?php echo $block->getAdjustmentsHtml(); ?>
```

## 七、本地化（翻译）

支持翻译的内容必须用 `__()` 包裹：

```php
<span><?= $escaper->escapeHtml(__('Back to Product Reviews')) ?></span>
```

---

## 八、常见布局定制任务

### 1. 设置页面布局

```xml
<!-- 改变 Advanced Search 页面布局 -->
<page layout="2columns-left" xmlns:xsi="...">
    ...
</page>
```

### 2. 引入静态资源（JS, CSS, 字体）

```xml
<page xmlns:xsi="...">
  <head>
    <!-- 本地 CSS -->
    <css src="css/my-styles.css" />
    <css src="Namespace_Module::css/custom-styles.css" />
    
    <!-- 本地 JS -->
    <script src="Magento_Catalog::js/sample1.js" />
    <script async="" src="Magento_Catalog::js/sample1.js" />
    <script defer="" src="Magento_Catalog::js/sample1.js" />
    
    <!-- 外部资源 -->
    <css src="https://example.com/style.css" src_type="url" />
    <script src="https://example.com/script.js" src_type="url" />
  </head>
</page>
```

### 3. 移除静态资源

```xml
<page xmlns:xsi="...">
   <head>
    <!-- 移除 CSS -->
    <remove src="css/styles-m.css" />
    <remove src="Namespace_ModuleName::css/styles.css" />
    
    <!-- 移除 JS -->
    <remove src="my-js.js" />
    <remove src="Magento_Catalog::js/sample1.js" />
   </head>
</page>
```

### 4. 创建容器

```xml
<container name="some.container" 
           as="someContainer" 
           label="Some Container" 
           htmlTag="div" 
           htmlClass="some-container" />
```

### 5. 引用容器（更新容器）

```xml
<!-- 添加内容到容器 -->
<referenceContainer name="header.panel">
  <block class="Magento\Framework\View\Element\Html\Links" name="header.links">
    <arguments>
      <argument name="css_class" xsi:type="string">header links</argument>
    </arguments>
  </block>
</referenceContainer>

<!-- 添加 CSS 类 -->
<referenceContainer name="page.wrapper" htmlClass="my-new-class second-class"/>

<!-- 添加 ID -->
<referenceContainer name="page.wrapper" htmlId="MyWrapper"/>

<!-- 移除容器 -->
<referenceContainer name="product.info.stock.sku" remove="true"/>
```

### 6. 创建 Block

```xml
<block class="Magento\Catalog\Block\Product\View\Description" 
       name="product.info.sku" 
       template="Magento_Catalog::product/view/attribute.phtml" 
       after="product.info.type">
  <arguments>
    <argument name="at_call" xsi:type="string">getSku</argument>
    <argument name="at_label" xsi:type="string">none</argument>
    <argument name="title" translate="true" xsi:type="string">SKU</argument>
    <argument name="add_attribute" xsi:type="string">itemprop="sku"</argument>
  </arguments>
</block>
```

### 7. 引用 Block（更新 Block）

```xml
<!-- 移除 Block -->
<referenceBlock name="block.name" remove="true"/>
```

---

## 九、Folix 主题应用示例

### 正确的模板覆盖

```
要覆盖: vendor/magento/module-customer/view/frontend/templates/account/dashboard/info.phtml

正确位置: app/design/frontend/Folix/game-theme/Magento_Customer/templates/account/dashboard/info.phtml
```

### 正确的布局扩展

```
要扩展: vendor/magento/module-customer/view/frontend/layout/customer_account.xml

正确位置: app/design/frontend/Folix/game-theme/Magento_Customer/layout/customer_account.xml
```

### 错误做法（不要这样做）

```
❌ 直接修改模块文件
❌ 在主题根目录创建布局文件
❌ 重复定义已存在的 block
```

---

---

## 十、模板定制步骤（Walkthrough）

> 来源: https://developer.adobe.com/commerce/frontend-core/guide/templates/walkthrough

### 定制现有模板的步骤

1. **定位模板** - 使用浏览器调试工具找到要修改的页面/block 关联的模板
2. **复制模板** - 按照模板存储约定复制到主题文件夹
3. **修改模板** - 进行必要的更改

### 添加新模板的步骤

1. **创建模板** - 在主题目录中按照约定创建模板文件
2. **关联布局** - 在布局文件中将模板分配给 block

### 实战示例：在评论表单添加消息

**需求**：在产品评论表单添加鼓励文字

**步骤**：

1. **定位原模板**：
   ```
   <Magento_Review_module_dir>/view/frontend/templates/form.phtml
   ```

2. **复制到主题**：
   ```
   app/design/frontend/ExampleCorp/orange/Magento_Review/templates/form.phtml
   ```

3. **修改模板**：
   ```php
   <!-- 在 <form> 之前添加 -->
   <div class="review-notice">
       <p>Share your experience! Your review helps other customers make better decisions.</p>
   </div>
   
   <form action="..." method="post">
       ...
   </form>
   ```

### 重要提醒

如果添加新的 `.html` 模板后修改它，更改不会立即生效，需要：

```bash
# 清除静态文件
rm -rf pub/static/frontend/*
rm -rf var/view_preprocessed/*

# 或者使用 Grunt
grunt clean:<theme_name>
```

---

## 十一、总结

| 操作 | 方法 | 文件位置 |
|------|------|---------|
| 覆盖模板 | 同名文件覆盖 | `<theme>/<Module>/templates/` |
| 扩展布局 | 使用 `<referenceContainer>` / `<referenceBlock>` | `<theme>/<Module>/layout/` |
| 新增 Block | 使用 `<block>` | 布局文件中 |
| 移除元素 | `remove="true"` | 布局文件中 |
| 移动元素 | `<move element="..." destination="..."/>` | 布局文件中 |

**核心原则**：
1. **扩展优先** - 使用 `<referenceContainer>` 扩展而非覆盖
2. **主题覆盖** - 在主题目录中创建同名文件
3. **不修改核心** - 永远不要直接修改 `vendor/` 目录

---

## 十二、实战示例：修改迷你购物车布局

> 来源: https://developer.adobe.com/commerce/frontend-core/guide/templates/sample

### 需求

Blank 主题中，迷你购物车的产品列表在 **"Go to Checkout"** 按钮下方：

```
┌─────────────────────────────┐
│  Go to Checkout             │
├─────────────────────────────┤
│  Product 1                  │
│  Product 2                  │
│  Product 3                  │
└─────────────────────────────┘
```

ExampleCorp 想要把产品列表移到按钮上方：

```
┌─────────────────────────────┐
│  Product 1                  │
│  Product 2                  │
│  Product 3                  │
├─────────────────────────────┤
│  Go to Checkout             │
└─────────────────────────────┘
```

### 解决步骤

#### 1. 定位模板

迷你购物车模板位置：
```
vendor/magento/module-checkout/view/frontend/web/template/minicart/content.html
```

#### 2. 复制到主题

```
app/design/frontend/ExampleCorp/orange/Magento_Checkout/web/template/minicart/content.html
```

#### 3. 修改模板顺序

**原始代码结构**：
```html
<!-- ko foreach: getCartItems() -->
    <!-- 产品列表 -->
<!-- /ko -->

<div class="actions">
    <div class="primary">
        <!-- Go to Checkout 按钮 -->
    </div>
</div>
```

**修改后代码结构**（交换顺序）：
```html
<div class="actions">
    <div class="primary">
        <!-- Go to Checkout 按钮 -->
    </div>
</div>

<!-- ko foreach: getCartItems() -->
    <!-- 产品列表 -->
<!-- /ko -->
```

### 关键学习点

| 要点 | 说明 |
|------|------|
| **模板类型** | HTML 模板（Knockout JS）也遵循同样的覆盖规则 |
| **路径约定** | `web/template/` 目录存放 Knockout 模板 |
| **修改方式** | 直接复制原模板，按需修改 HTML 结构 |
| **生效方式** | 主题模板自动覆盖模块模板 |

### 注意事项

修改 Knockout 模板（`.html`）后需要清除缓存：

```bash
rm -rf pub/static/frontend/*
rm -rf var/view_preprocessed/*
```

---

## 十三、模板类型对比

| 模板类型 | 文件扩展名 | 用途 | 位置 |
|---------|----------|------|------|
| **PHTML 模板** | `.phtml` | 服务端渲染，PHP 模板 | `templates/` 目录 |
| **HTML 模板** | `.html` | Knockout JS 客户端模板 | `web/template/` 目录 |

### PHTML 模板特点

```php
<?php
// 可以使用 PHP 代码
$product = $block->getProduct();
?>
<div class="product-name">
    <?= $escaper->escapeHtml($product->getName()) ?>
</div>
```

### HTML 模板特点（Knockout）

```html
<!-- 使用 Knockout 绑定 -->
<div class="product-name" data-bind="text: product().name"></div>

<!-- ko foreach: items -->
    <div data-bind="text: name"></div>
<!-- /ko -->
```

---

## 十四、Folix 主题实战建议

### 覆盖模板的正确姿势

```bash
# 1. 找到原模板位置
vendor/magento/module-theme/view/frontend/templates/html/header.phtml

# 2. 复制到主题目录（保持相同路径）
app/design/frontend/Folix/game-theme/Magento_Theme/templates/html/header.phtml

# 3. 修改模板内容
# 只修改需要改的部分，不要重写整个文件
```

### 扩展布局的正确姿势

```xml
<!-- app/design/frontend/Folix/game-theme/Magento_Theme/layout/default.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <!-- 使用 referenceContainer 扩展，不要覆盖 -->
        <referenceContainer name="header.panel">
            <block class="Magento\Framework\View\Element\Template" 
                   name="custom.header.notice" 
                   template="Magento_Theme::html/header/notice.phtml"/>
        </referenceContainer>
    </body>
</page>
```
