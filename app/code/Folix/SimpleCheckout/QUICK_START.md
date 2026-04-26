# 🚀 Folix SimpleCheckout 快速启动指南

## 📋 模块已创建完成!

所有核心文件已就绪,现在可以部署和测试了。

---

## ⚡ 快速部署(3步完成)

### **方法1: 使用部署脚本(推荐)**

```bash
cd /var/www/html/game/game/app/code/Folix/SimpleCheckout
chmod +x deploy.sh
./deploy.sh
```

### **方法2: 手动执行命令**

```bash
cd /var/www/html/game/game

# 1. 启用模块
php bin/magento module:enable Folix_SimpleCheckout

# 2. 升级数据库
php bin/magento setup:upgrade

# 3. 清理缓存
php bin/magento cache:clean

# 4. 编译依赖(生产模式需要)
php bin/magento setup:di:compile

# 5. 部署静态资源
php bin/magento setup:static-content:deploy -f zh_Hans_CN en_US
```

---

## ✅ 部署后验证清单

### **1. 检查模块是否启用**
```bash
php bin/magento module:status | grep SimpleCheckout
```
应该显示: `Folix_SimpleCheckout`

### **2. 访问Checkout页面**
```
http://your-domain.com/checkout
```

### **3. 功能测试**

#### **PC端 (≥ 768px)**
- [ ] Shipping-step已隐藏
- [ ] ProgressBar已隐藏
- [ ] Email输入框正常显示
- [ ] Login Notice卡片显示在Email下方
- [ ] 点击"立即登录"触发Mageplaza弹窗
- [ ] Cart Items正常显示
- [ ] Payment Methods正常显示
- [ ] Sidebar在右上角sticky定位
- [ ] Place Order按钮可以点击

#### **移动端 (< 768px)**
- [ ] Login Notice卡片隐藏
- [ ] 简洁登录链接显示在Email下方
- [ ] 点击"立即登录"触发Mageplaza弹窗
- [ ] Sidebar固定在底部
- [ ] Sidebar顶部有渐变阴影
- [ ] 主内容有320px底部间距
- [ ] Place Order按钮始终可见

### **4. 登录功能测试**
- [ ] PC端点击登录 → Mageplaza弹窗显示
- [ ] 移动端点击登录 → 同一个Mageplaza弹窗显示
- [ ] Google登录按钮可用
- [ ] Facebook登录按钮可用
- [ ] 登录成功后页面刷新,用户状态更新

### **5. 下单流程测试**
- [ ] 选择支付方式
- [ ] 点击Place Order按钮
- [ ] 显示加载动画
- [ ] 订单提交成功
- [ ] 跳转到成功页面

---

## 🔧 常见问题排查

### **问题1: 模块未生效**
```bash
# 解决: 强制重新部署
php bin/magento cache:flush
rm -rf var/view_preprocessed/*
rm -rf pub/static/frontend/*
php bin/magento setup:static-content:deploy -f zh_Hans_CN en_US
```

### **问题2: CSS样式未加载**
```bash
# 解决: 检查LESS编译
ls -la pub/static/frontend/Folix/game-theme/*/css/source/_module.less

# 如果文件不存在,重新部署
php bin/magento setup:static-content:deploy -f zh_Hans_CN en_US
```

### **问题3: JS组件报错**
```bash
# 解决: 清除requirejs缓存
rm -rf var/view_preprocessed/pub/static/frontend/**/requirejs-config.js
php bin/magento cache:clean
```

### **问题4: Mageplaza弹窗不显示**
```bash
# 检查Mageplaza模块是否启用
php bin/magento module:status | grep SocialLogin

# 如果未启用,启用它
php bin/magento module:enable Mageplaza_SocialLogin
php bin/magento setup:upgrade
```

---

## 📁 模块文件结构

```
app/code/Folix/SimpleCheckout/
├── registration.php                          ✅ 模块注册
├── etc/
│   └── module.xml                            ✅ 模块配置
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── checkout_index_index.xml      ✅ 布局配置
│       └── web/
│           ├── css/
│           │   └── source/
│           │       └── _module.less          ✅ CSS样式
│           ├── js/
│           │   └── view/
│           │       ├── payment/
│           │       │   └── login-notice.js   ✅ 登录组件
│           │       └── place-order-button.js ✅ 下单按钮组件
│           └── template/
│               ├── payment/
│               │   └── login-notice.html     ✅ 登录模板
│               └── place-order-button.html   ✅ 下单按钮模板
├── demo_simple_checkout.html                 ✅ Demo页面
├── DEMO_README.md                            ✅ Demo说明
├── README.md                                 ✅ 模块说明
└── deploy.sh                                 ✅ 部署脚本
```

---

## 🎯 核心功能说明

### **1. 布局配置 (checkout_index_index.xml)**
- ✅ 参考SalesRule标准写法
- ✅ **Place Order Button放在 `sidebar.summary.itemsAfter`** (与OneStepCheckout一致)
- ✅ Mageplaza Social Login集成
- ✅ Login Notice挂载到`before-login-form`
- ✅ 不破坏原生结构,保持兼容性

### **2. 登录组件 (login-notice.js + login-notice.html)**
- ✅ PC端显示完整卡片(图标+标题+描述+按钮)
- ✅ 移动端显示简洁链接(文本+边框按钮)
- ✅ 两者都调用同一个`triggerLogin()`方法
- ✅ 触发Mageplaza Social Login弹窗
- ✅ 降级方案:弹窗不存在时跳转登录页

### **3. 下单按钮 (place-order-button.js + place-order-button.html)**
- ✅ 统一下单入口,隐藏各支付方式内的按钮
- ✅ 检查是否选择支付方式
- ✅ 调用原生`placeOrderAction`
- ✅ 显示加载动画和错误提示
- ✅ SSL安全提示

### **4. CSS样式 (_module.less)**
- ✅ 隐藏shipping-step和progressBar
- ✅ 隐藏支付方式内的Place Order按钮
- ✅ PC端Sidebar sticky定位
- ✅ 移动端Sidebar fixed定位(底部)
- ✅ 响应式媒体查询
- ✅ 使用主题变量(@folix-*)

---

## 📝 下一步优化建议

### **短期优化**
1. **充值信息显示** - 确保ChargeTemplate模块的additional_data正确传递到Cart Item
2. **表单验证** - 添加Email格式验证
3. **错误处理** - 优化Mageplaza弹窗加载失败的提示

### **中期优化**
1. **性能优化** - 懒加载非关键JS组件
2. **无障碍访问** - 添加ARIA标签
3. **国际化** - 完善i18n翻译

### **长期优化**
1. **A/B测试** - 对比OneStepCheckout和SimpleCheckout转化率
2. **数据分析** - 追踪用户结账漏斗
3. **个性化** - 根据用户历史推荐支付方式

---

## 🆘 需要帮助?

如果遇到任何问题:

1. **查看日志**
```bash
tail -f var/log/system.log
tail -f var/log/exception.log
```

2. **检查浏览器控制台**
- F12打开开发者工具
- 查看Console是否有JS错误
- 查看Network是否有404资源

3. **参考文档**
- `DEMO_README.md` - Demo详细说明
- `README.md` - 模块架构说明
- `CHECKOUT_DEVELOPMENT_GUIDE.md` - Magento Checkout开发指南

---

**准备好了吗?运行部署脚本开始测试吧!** 🚀

```bash
./deploy.sh
```
