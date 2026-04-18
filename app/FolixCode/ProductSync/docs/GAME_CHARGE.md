# 游戏充值项目文档

## 项目概述

这是一个游戏充值项目，支持两种充值方式：

1. **直充** - 直接充值到游戏账户
2. **卡密** - 购买充值卡密

## 产品属性配置

### 1. 充值类型属性

**属性代码**：`game_charge_type`

**属性类型**：Select（下拉选择）

**可选值**：
- `direct` - 直充
- `card` - 卡密

**属性设置**：
- 必填：是
- 全局作用域：是
- 可搜索：是
- 可筛选：是
- 前台可见：是

### 2. 产品类型

**产品类型**：`virtual`（虚拟产品）

**特性**：
- 需要库存管理
- 不需要配送
- 自动设置为重量为0

## API数据格式

### 商品列表数据示例

```json
{
  "id": "1001",
  "name": "王者荣耀100点券",
  "price": "10.00",
  "description": "王者荣耀游戏点券充值",
  "short_description": "100点券",
  "status": 1,
  "charge_type": "direct",
  "category_ids": [10, 15]
}
```

### 字段说明

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | string | 是 | 外部产品ID |
| name | string | 是 | 产品名称 |
| price | float | 是 | 产品价格 |
| description | string | 否 | 产品描述 |
| short_description | string | 否 | 产品简短描述 |
| status | int | 否 | 产品状态（1=启用，0=禁用） |
| charge_type | string | 是 | 充值类型（direct/card） |
| category_ids | array | 否 | 分类ID数组 |

## 安装和配置

### 1. 运行安装脚本

```bash
# 执行Data Patch以添加自定义属性
php bin/magento setup:upgrade

# 清除缓存
php bin/magento cache:clean
php bin/magento cache:flush

# 编译DI
php bin/magento setup:di:compile
```

### 2. 验证属性已创建

登录Magento后台，进入：
`Stores > Attributes > Product`

检查以下属性是否存在：
- `game_charge_type` - 充值类型

### 3. 创建属性集（可选）

如果需要为游戏充值产品创建独立的属性集：

1. 进入 `Stores > Attributes > Attribute Set`
2. 点击 `Add Attribute Set`
3. 输入属性集名称：`Game Charge Products`
4. 基于 `Default` 属性集
5. 将 `game_charge_type` 添加到属性集的 `Game Settings` 组

## 产品导入流程

### 直充产品导入

```json
{
  "id": "1001",
  "name": "王者荣耀100点券",
  "price": "10.00",
  "charge_type": "direct",
  "description": "直充到指定游戏账户",
  "category_ids": [10]
}
```

导入后产品特性：
- 产品类型：虚拟产品
- 充值类型：直充
- 不需要库存
- 不需要配送

### 卡密产品导入

```json
{
  "id": "2001",
  "name": "王者荣耀100点券卡密",
  "price": "10.00",
  "charge_type": "card",
  "description": "购买充值卡密，自行充值",
  "category_ids": [10]
}
```

导入后产品特性：
- 产品类型：虚拟产品
- 充值类型：卡密
- 不需要库存
- 不需要配送

## 常见问题

### 1. 如何区分直充和卡密？

在后台编辑产品时，在 `Game Settings` 组中可以看到 `充值类型` 字段，可以选择：
- **直充** - 用户下单后直接充值到指定游戏账户
- **卡密** - 用户下单后获得卡密，自行充值

### 2. 如何批量修改充值类型？

在后台产品列表中：
1. 选择要修改的产品
2. 选择 `Actions > Update Attributes`
3. 在 `充值类型` 字段中选择新值
4. 点击 `Save`

### 3. 虚拟产品的订单流程

1. 用户下单购买虚拟产品
2. 订单状态为 `Pending`
3. 支付成功后，订单状态变更为 `Processing`
4. 直充产品：系统自动充值
5. 卡密产品：生成卡密并发送给用户
6. 订单完成

### 4. 如何管理库存？

虚拟产品默认不管理库存：
- `manage_stock` = 0
- `is_in_stock` = 1

如果需要限制卡密库存，可以：
1. 在产品编辑页面启用库存管理
2. 设置初始库存数量
3. 根据卡密发放情况调整库存

## 扩展功能

### 1. 添加游戏账户字段

如果需要用户填写游戏账户信息，可以：

1. 创建自定义客户属性
2. 在结账页面添加表单字段
3. 根据充值类型显示不同字段

### 2. 卡密管理

为卡密产品实现卡密管理：
1. 创建卡密数据库表
2. 开发卡密管理后台
3. 订单生成时分配卡密
4. 在订单详情中显示卡密

### 3. 充值接口对接

对接游戏充值商API：
1. 开发充值接口客户端
2. 实现自动充值逻辑
3. 处理充值结果回调
4. 记录充值日志

## 技术实现

### 属性模型

- **Backend Model**: `FolixCode\ProductSync\Model\Product\Attribute\Backend\ChargeType`
- **Frontend Model**: `FolixCode\ProductSync\Model\Product\Attribute\Frontend\ChargeType`
- **Source Model**: `FolixCode\ProductSync\Model\Product\Attribute\Source\ChargeType`

### 助手类

`FolixCode\ProductSync\Helper\ChargeType`

提供充值类型相关的工具方法：
- `getAllTypes()` - 获取所有类型
- `getTypeLabel()` - 获取类型标签
- `isValidType()` - 验证类型
- `isDirect()` - 判断是否为直充
- `isCard()` - 判断是否为卡密

### 导入服务

`FolixCode\ProductSync\Service\ProductImporter`

处理产品导入逻辑：
- 自动设置为虚拟产品类型
- 设置充值类型属性
- 配置库存管理为不启用

## 测试

### 测试步骤

1. 运行安装脚本创建属性
2. 手动创建一个测试产品
3. 设置充值类型为 "直充"
4. 检查产品前台显示
5. 测试下单流程
6. 验证订单处理

### 测试数据

直充产品：
```bash
php bin/magento folixcode:sync:products --type=products --limit=1
```

卡密产品：
手动创建或通过API导入包含 `"charge_type": "card"` 的数据

## 相关文件

- `Setup/Patch/Data/AddProductAttributes.php` - 属性安装脚本
- `Model/Product/Attribute/Source/ChargeType.php` - 充值类型源模型
- `Service/ProductImporter.php` - 产品导入服务
- `Helper/ChargeType.php` - 充值类型助手类
