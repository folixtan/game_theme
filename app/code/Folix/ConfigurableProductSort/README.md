# Folix_ConfigurableProductSort

## 概述
此模块为 Magento 2 的配置产品（Configurable Product）提供按价格升序排序的功能。当用户在产品详情页查看配置选项时，**同一属性下的选项**（如颜色：红色、蓝色、绿色）将按它们对应的**最低子产品价格**从低到高排列。

## 功能特性
- ✅ 自动对配置产品的属性选项按最低价格升序排序
- ✅ 使用 Plugin 方式扩展，不修改核心代码
- ✅ 兼容传统下拉框和 Swatches 两种展示方式
- ✅ 性能优化，避免不必要的数据库查询

## 示例说明

### 排序前（原始顺序）
**颜色属性：**
```php
'options' => [
    ['id' => 6, 'label' => 'Red', 'products' => [101, 102]],   // 最低价 $15
    ['id' => 7, 'label' => 'Blue', 'products' => [103, 104]],  // 最低价 $10
    ['id' => 8, 'label' => 'Green', 'products' => [105, 106]]  // 最低价 $12
]
```

### 排序后（按价格升序）
**颜色属性：**
```php
'options' => [
    ['id' => 7, 'label' => 'Blue', 'products' => [103, 104]],  // 最低价 $10 ✓
    ['id' => 8, 'label' => 'Green', 'products' => [105, 106]], // 最低价 $12 ✓
    ['id' => 6, 'label' => 'Red', 'products' => [101, 102]]    // 最低价 $15 ✓
]
```

## 技术实现

### 插件机制
- **目标类**: `Magento\ConfigurableProduct\Model\ConfigurableAttributeData`
- **插件方法**: `afterGetAttributesData` (After Plugin)
- **拦截点**: public 方法 `getAttributesData()`
- **排序依据**: 每个选项关联的子产品中的**最低最终价格**

### 为什么使用 After Plugin？
- `getAttributeOptionsData()` 是 **protected** 方法，无法直接通过 Plugin 拦截
- `getAttributesData()` 是 **public** 方法，可以在其返回结果后处理数据
- After Plugin 在原生方法执行后获取返回值，然后对其中的 `options` 数组进行排序

### 工作流程
1. 用户访问配置产品详情页
2. Magento 调用 `ConfigurableAttributeData::getAttributesData()` 获取配置数据
3. 原生方法遍历所有属性，调用 protected 的 `getAttributeOptionsData()` 构建数据结构
4. **插件在返回后拦截**：获取完整的 attributes 数组
5. **对每个属性的 options 数组排序**：计算每个选项的最低子产品价格，按价格升序重排
6. 返回排序后的数据给前端 JavaScript

### 核心逻辑
```php
public function afterGetAttributesData(
    ConfigurableAttributeData $subject,
    array $result,
    Product $product,
    array $options = []
): array {
    // 遍历每个属性
    foreach ($result['attributes'] as $attributeId => &$attributeData) {
        // 对该属性的 options 数组排序
        if (!empty($attributeData['options'])) {
            $attributeData['options'] = $this->sortOptionsByMinPrice($attributeData['options']);
        }
    }
    return $result;
}
```

## 安装说明

### 1. 启用模块
```bash
php bin/magento module:enable Folix_ConfigurableProductSort
php bin/magento setup:upgrade
```

### 2. 清理缓存
```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

### 3. 编译依赖（生产环境）
```bash
php bin/magento setup:di:compile
```

## 验证方法

### 前端验证
1. 访问任意配置产品详情页（有多个选项的属性）
2. 查看属性选项列表（如下拉框或色块）
3. 确认选项按最低价格从低到高排列

### 调试技巧
在浏览器控制台查看配置数据：
```javascript
console.log(window.spConfig.attributes);
// 查看 attributes[属性ID].options 数组的顺序
```

## 注意事项

### 性能考虑
- 插件会在每次加载配置产品时执行
- 对于有大量选项的配置产品，会查询多个子产品价格
- 建议配合 Redis 缓存使用以优化性能

### 兼容性
- ✅ Magento 2.3.x
- ✅ Magento 2.4.x
- ✅ 与传统下拉框兼容
- ✅ 与 Swatches 模块兼容

### 排序规则
- **有产品的选项**：按最低价格升序排列
- **无产品的选项**：排在最后（价格为 PHP_FLOAT_MAX）
- **价格相同**：保持原始顺序（稳定排序）

## 故障排除

### 问题：排序未生效
**解决方案**：
1. 确认模块已启用：`php bin/magento module:status Folix_ConfigurableProductSort`
2. 清理缓存：`php bin/magento cache:clean`
3. 检查 di.xml 配置是否正确
4. 查看浏览器控制台的 spConfig 数据

### 问题：性能下降
**解决方案**：
1. 启用 Redis 缓存
2. 检查是否有其他插件冲突
3. 考虑优化 ProductRepository 的缓存策略

## 开发者信息

### 模块结构
```
app/code/Folix/ConfigurableProductSort/
├── registration.php              # 模块注册文件
├── README.md                     # 使用文档
├── etc/
│   ├── module.xml                # 模块配置
│   └── di.xml                    # 依赖注入配置
└── Plugin/
    └── ConfigurableAttributeDataPlugin.php  # 核心插件类
```

### 关键方法说明

#### afterGetAttributesData
在原生 `getAttributesData` 方法执行后拦截，对返回数据中的每个属性的 options 数组进行排序。

#### sortOptionsByMinPrice
计算每个选项的最低产品价格，然后按价格升序重排整个 options 数组。

#### getMinimumPriceForOption
遍历选项关联的所有产品ID，找出最低的最终价格。

### 扩展开发
如需自定义排序逻辑，可以：
1. 创建新的插件类继承 `ConfigurableAttributeDataPlugin`
2. 重写 `sortOptionsByMinPrice()` 方法实现自定义排序规则
3. 在 di.xml 中替换插件配置

## 许可证
Copyright © Folix. All rights reserved.
