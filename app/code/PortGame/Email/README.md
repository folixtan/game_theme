# PortGame Email Module

## 简介

这是一个精简且支持后台配置的邮件发送模块，**完全遵循 Magento 原生设计模式**。

## 功能特点

✅ **原生模式**：完全按照 Magento Sales 模块的邮件架构设计  
✅ **精简设计**：直接使用 Magento Framework 的 TransportBuilder  
✅ **后台配置**：支持在后台配置邮件模板、发件人身份、启用/禁用  
✅ **职责分离**：Template Container + SenderBuilder 分离设计  
✅ **轻量级**：不依赖 Sales 模块的 Sender 类  

## 目录结构

```
app/code/PortGame/Email/
├── Model/
│   ├── Container/
│   │   └── Template.php        # 模板容器（存储变量、选项、模板 ID）
│   ├── SenderBuilder.php       # 发送器构建器（构建并发送邮件）
│   └── EmailConfig.php         # 配置读取类
├── etc/
│   ├── module.xml              # 模块配置
│   ├── di.xml                  # 依赖注入配置
│   ├── email_templates.xml     # 邮件模板注册
│   ├── acl.xml                 # 访问控制列表
│   └── adminhtml/
│       └── system.xml          # 后台系统配置
├── view/frontend/email/
│   ├── thirdparty_card.html    # 卡密订单邮件模板
│   └── thirdparty_direct.html  # 直充订单邮件模板
├── composer.json               # Composer 配置
├── registration.php            # 模块注册
└── README.md                   # 使用文档
```

## 安装步骤

1. **启用模块**
```bash
php bin/magento module:enable PortGame_Email
php bin/magento setup:upgrade
php bin/magento cache:clean
```

2. **配置邮件模板**
- 登录 Magento 后台
- 进入 `Stores > Configuration > General > PortGame Email Settings`
- 配置邮件模板、发件人身份、启用状态

## 使用方法

### 架构设计（遵循 Magento 原生模式）

```
业务模块 → Template Container → SenderBuilder → TransportBuilder → 发送邮件
```

**核心组件**（参考 Magento Sales 模块）：

1. **Template Container** ([Container/Template.php](file:///var/www/html/game/game/app/code/PortGame/Email/Model/Container/Template.php))
   - 存储模板变量（`$vars`）
   - 存储模板选项（`$options`）
   - 存储模板 ID（`$id`）
   - 实现 [ResetAfterRequestInterface](file:///var/www/html/game/game/vendor/magento/framework/ObjectManager/ResetAfterRequestInterface.php)（请求后重置状态）

2. **SenderBuilder** ([SenderBuilder.php](file:///var/www/html/game/game/app/code/PortGame/Email/Model/SenderBuilder.php))
   - 从 Template Container 获取数据
   - 调用 TransportBuilder 构建邮件
   - 发送邮件

3. **EmailConfig** ([EmailConfig.php](file:///var/www/html/game/game/app/code/PortGame/Email/Model/EmailConfig.php))
   - 读取后台配置
   - 提供模板 ID、发件人身份、商店信息

### 在其他模块中使用

```php
use PortGame\Email\Model\Container\Template;
use PortGame\Email\Model\SenderBuilder;

class YourClass
{
    protected $templateContainer;
    protected $senderBuilder;
    
    public function __construct(
        Template $templateContainer,
        SenderBuilder $senderBuilder
    ) {
        $this->templateContainer = $templateContainer;
        $this->senderBuilder = $senderBuilder;
    }
    
    // 发送卡密订单邮件
    public function sendCardEmail($order, $cardData)
    {
        // 1. 准备模板变量
        $templateVars = [
            'customer_name' => $order->getCustomerName(),
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'card_number' => $cardData['card_number'],
            'password' => $cardData['password'],
            'expiry_date' => $cardData['expiry_date'],
        ];
        
        // 2. 设置模板选项
        $templateOptions = [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $order->getStoreId(),
        ];
        
        // 3. 配置 Template Container
        $this->templateContainer->setTemplateVars($templateVars);
        $this->templateContainer->setTemplateOptions($templateOptions);
        
        // 4. 发送邮件
        $this->senderBuilder->send(
            'thirdparty_card',          // 模板类型（对应 system.xml 配置）
            $order->getCustomerEmail(), // 收件人邮箱
            $order->getCustomerName()   // 收件人姓名
        );
    }
    
    // 发送直充订单邮件
    public function sendDirectEmail($order, $topupData)
    {
        // 1. 准备模板变量
        $templateVars = [
            'customer_name' => $order->getCustomerName(),
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'account' => $topupData['account'],
            'recharge_status' => $topupData['status'],
            'recharge_result' => $topupData['result'],
        ];
        
        // 2. 设置模板选项
        $templateOptions = [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $order->getStoreId(),
        ];
        
        // 3. 配置 Template Container
        $this->templateContainer->setTemplateVars($templateVars);
        $this->templateContainer->setTemplateOptions($templateOptions);
        
        // 4. 发送邮件
        $this->senderBuilder->send(
            'thirdparty_direct',        // 模板类型
            $order->getCustomerEmail(),
            $order->getCustomerName()
        );
    }
}
```

## 配置说明

### 后台配置路径

`Stores > Configuration > General > PortGame Email Settings`

- **General Configuration**
  - Enable Third Party Order Email：全局启用/禁用
  
- **Third Party Card Order**
  - Email Template：选择卡密订单邮件模板
  - Send From：选择发件人身份
  - Enable：启用/禁用卡密订单邮件
  
- **Third Party Direct Top-up**
  - Email Template：选择直充订单邮件模板
  - Send From：选择发件人身份
  - Enable：启用/禁用直充订单邮件

## 扩展指南

### 添加新的邮件类型

1. **email_templates.xml**：添加模板配置
2. **system.xml**：添加后台配置
3. **EmailConfig**：添加配置路径常量
4. **创建模板文件**：`view/frontend/email/xxx.html`
5. **调用方式**：`$this->senderBuilder->send('your_type', $email, $name)`

## 技术架构

### 核心组件

1. **Template Container**：模板变量容器
   - 实现 `ResetAfterRequestInterface`（请求后自动重置状态）
   - 存储模板变量、选项和 ID
   - 完全遵循 Magento Sales 模块的原生设计

2. **SenderBuilder**：发送器构建器
   - 从 Template Container 获取数据
   - 调用 TransportBuilder 构建邮件
   - 发送邮件

3. **EmailConfig**：配置读取类
   - 读取后台配置
   - 支持全局和类型级别的启用/禁用
   - 获取模板 ID、发件人身份、商店信息

4. **邮件模板**：使用 Magento 标准模板语法
   - 支持变量替换
   - 支持条件判断
   - 包含头尾模板

### 设计优势

✅ **完全遵循原生模式**：基于 Magento Sales 模块的邮件架构  
✅ **职责清晰**：Template Container 存储数据，SenderBuilder 发送邮件  
✅ **状态管理**：实现 ResetAfterRequestInterface，自动清理状态  
✅ **高灵活性**：业务模块可以自定义任何数据结构  
✅ **易于测试**：组件可独立测试  
✅ **可复用性强**：其他模块可以直接引用  
✅ **后台可配置**：无需修改代码即可调整邮件设置  

### 原生参考

本模块的设计完全参考以下 Magento 原生文件：
- [vendor/magento/module-sales/Model/Order/Email/Container/Template.php](file:///var/www/html/game/game/vendor/magento/module-sales/Model/Order/Email/Container/Template.php)
- [vendor/magento/module-sales/Model/Order/Email/SenderBuilder.php](file:///var/www/html/game/game/vendor/magento/module-sales/Model/Order/Email/SenderBuilder.php)
- [vendor/magento/module-sales/Model/Order/Email/Sender.php](file:///var/www/html/game/game/vendor/magento/module-sales/Model/Order/Email/Sender.php)
- [vendor/magento/framework/Mail/TemplateInterface.php](file:///var/www/html/game/game/vendor/magento/framework/Mail/TemplateInterface.php)
- [vendor/magento/framework/ObjectManager/ResetAfterRequestInterface.php](file:///var/www/html/game/game/vendor/magento/framework/ObjectManager/ResetAfterRequestInterface.php)