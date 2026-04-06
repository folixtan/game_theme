# Magento 模板开发流程完整指南

## 核心概念：覆盖 = 同名文件替换

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│   ⚠️  模板覆盖是"替换"，不是"合并"！                            │
│                                                                 │
│   创建同名模板后：                                               │
│   ✅ 主题模板 完全替代 原生模板                                  │
│   ❌ 原生模板 不再被使用                                        │
│   ❌ 不会自动合并内容                                           │
│                                                                 │
│   所以：必须复制原生内容，然后在基础上修改                        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 覆盖的正确步骤

```
第1步：找到原生模板
       vendor/magento/module-xxx/view/frontend/templates/xxx.phtml
                    ↓
第2步：复制全部内容
       复制原生的所有代码，不要遗漏任何内容
                    ↓
第3步：放到主题目录
       app/design/frontend/Folix/game-theme/Module/templates/xxx.phtml
                    ↓
第4步：在复制的基础上修改
       添加你需要的新功能/新样式
                    ↓
第5步：测试验证
       确保原生功能正常 + 新功能正常
```

### 实例对比

```
❌ 错误做法（原生功能丢失）:
─────────────────────────────────────────────
文件: app/design/.../addtocart.phtml
─────────────────────────────────────────────
<button class="my-btn">我的按钮</button>
─────────────────────────────────────────────
结果: 加入购物车功能完全丢失！

✅ 正确做法（保留原生 + 添加新功能）:
─────────────────────────────────────────────
文件: app/design/.../addtocart.phtml
─────────────────────────────────────────────
<?php
// 复制原生代码（完整保留）
$_product = $block->getProduct();
$buttonTitle = __('Add to Cart');
// ... 所有原生代码 ...
?>
<!-- 新增你的功能 -->
<button class="my-btn">我的按钮</button>
─────────────────────────────────────────────
结果: 原生功能正常 + 新功能正常
```

---

## 一、模板开发五步法

```
┌─────────────────────────────────────────────────────────────────┐
│                    第1步：需求分析                               │
│  - 确定要修改什么（布局？模板？样式？）                          │
│  - 确定修改范围（全局？特定页面？特定模块？）                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    第2步：定位原文件                             │
│  - 使用浏览器调试工具查看 HTML 结构                              │
│  - 使用 Magento 的 Template Hints 功能                          │
│  - 查找布局文件和模板文件                                        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    第3步：创建主题文件                           │
│  - 按照约定创建目录结构                                          │
│  - 复制原文件到主题目录（保持相同路径）                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    第4步：实施修改                               │
│  - 布局文件：使用 referenceContainer/referenceBlock 扩展        │
│  - 模板文件：只修改差异部分                                      │
│  - 样式文件：使用模块化组织                                      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    第5步：测试验证                               │
│  - 清除缓存                                                      │
│  - 部署静态文件                                                  │
│  - 多端测试（桌面、移动）                                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 二、文件定位方法

### 方法1：使用 Template Hints（推荐）

```bash
# 后台开启 Template Hints
Stores → Configuration → Advanced → Developer → Debug → Template Path Hints = Yes

# 仅对特定IP显示（生产环境安全）
Stores → Configuration → Advanced → Developer → Developer Client Restrictions
```

开启后，前台页面会显示每个模板的路径。

### 方法2：浏览器调试 + 文件搜索

```bash
# 1. 浏览器查看 HTML 结构，找到特征 class 或 ID
# 2. 在项目中搜索

# 搜索布局文件中的 block name
grep -r "name=\"product.info\"" vendor/magento/

# 搜索模板文件中的特征内容
grep -r "product-info-main" vendor/magento/
```

### 方法3：查看 Block 类

```php
// 布局文件中的 block class 指向对应的 Block 类
<block class="Magento\Catalog\Block\Product\View" ...>

// Block 类中可能定义了默认模板
protected $_template = 'Magento_Catalog::product/view.phtml';
```

---

## 三、文件路径速查表

| 文件类型 | 模块位置 | 主题位置 |
|---------|---------|---------|
| **布局 XML** | `<module>/view/frontend/layout/` | `<theme>/<Module>/layout/` |
| **PHTML 模板** | `<module>/view/frontend/templates/` | `<theme>/<Module>/templates/` |
| **Knockout 模板** | `<module>/view/frontend/web/template/` | `<theme>/<Module>/web/template/` |
| **CSS/Less** | `<module>/view/frontend/web/css/` | `<theme>/<Module>/web/css/` |
| **JavaScript** | `<module>/view/frontend/web/js/` | `<theme>/<Module>/web/js/` |

### Folix 主题路径示例

```
app/design/frontend/Folix/game-theme/
├── Magento_Theme/
│   ├── layout/
│   │   └── default.xml              # 全局布局
│   └── templates/
│       └── html/
│           └── header.phtml         # 覆盖头部模板
│
├── Magento_Catalog/
│   ├── layout/
│   │   └── catalog_product_view.xml # 产品详情页布局
│   └── templates/
│       └── product/
│           └── view.phtml           # 覆盖产品视图模板
│
└── web/css/source/
    ├── _variables.less              # 全局变量
    ├── _extend.less                 # 全局样式入口
    └── _theme.less                  # 覆盖父主题变量
```

---

## 四、布局操作速查

| 操作 | 语法 | 说明 |
|------|------|------|
| **引用容器** | `<referenceContainer name="xxx">` | 向已有容器添加内容 |
| **引用 Block** | `<referenceBlock name="xxx">` | 修改已有 Block |
| **创建容器** | `<container name="xxx">` | 创建新容器 |
| **创建 Block** | `<block class="..." name="xxx">` | 创建新 Block |
| **移动元素** | `<move element="xxx" destination="yyy"/>` | 移动 Block/容器 |
| **移除元素** | `remove="true"` | 移除 Block/容器 |
| **修改模板** | `template="Module::path/to.phtml"` | 更换模板 |

---

## 五、缓存与部署命令

```bash
# 清除所有缓存
php bin/magento cache:clean
php bin/magento cache:flush

# 清除特定缓存
php bin/magento cache:clean layout block_html full_page

# 部署静态文件
php bin/magento setup:static-content:deploy --theme=Folix/game-theme

# 开发模式（自动生成静态文件）
php bin/magento deploy:mode:set developer

# 清除静态文件（手动）
rm -rf pub/static/frontend/Folix/game-theme/*
rm -rf var/view_preprocessed/*
```

---

## 六、常见错误与解决

| 错误 | 原因 | 解决方案 |
|------|------|---------|
| 布局修改不生效 | 缓存未清除 | `cache:clean layout` |
| 模板修改不生效 | 文件路径错误 | 检查路径是否与原文件匹配 |
| Block 重复定义 | name 属性重复 | 使用不同的 name 或使用 referenceBlock |
| 样式不生效 | CSS 未编译 | `setup:static-content:deploy` |
| JS 报错 | RequireJS 配置错误 | 检查 `requirejs-config.js` |

---

## 七、开发规范

### 命名规范

```xml
<!-- Block 命名：模块前缀 + 功能 -->
<block name="catalog.product.view" />      ✅
<block name="my_product_view" />           ❌

<!-- 容器命名：功能 + container -->
<container name="product.info.container" /> ✅
<container name="myContainer" />            ❌
```

### 修改原则

```
1. 扩展优先 - 使用 referenceContainer/referenceBlock
2. 只改差异 - 不重复已有代码
3. 保留原样 - 不删除原生 class 和 data 属性
4. 注释清晰 - 标注修改原因和日期
```

---

## 八、调试技巧

### 1. 查看当前加载的布局

```php
// 在控制器或 Block 中
$layout = $this->getLayout();
$handles = $layout->getUpdate()->getHandles();
var_dump($handles);
```

### 2. 查看 Block 是否存在

```php
// 在模板中
if ($block->getChildBlock('product.info')) {
    // Block 存在
}
```

### 3. 输出 Block 名称

```php
// 在模板中
echo $block->getNameInLayout();
```

### 4. 查看所有 Block

```php
// 在模板中
foreach ($block->getLayout()->getAllBlocks() as $block) {
    echo $block->getNameInLayout() . "\n";
}
```
