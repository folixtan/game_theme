# Mageplaza Social Login 弹窗电竞风改造方案

## 📋 概述

基于对 Magento 2 原生 Customer 模块和 Mageplaza Social Login 集成机制的深入分析，制定完整的电竞风改造方案。

---

## 🎯 改造目标

1. **视觉改造**：将 Mageplaza Social Login 弹窗改造为电竞风格（深蓝 + 金色）
2. **功能保持**：保持所有社交登录功能完整
3. **原生集成**：与原生 customer-data.js 无缝集成
4. **主题适配**：适配 Folix Game Theme 的 B 方案配色

---

## 🏗️ 改造策略

### 策略 1：模板直接覆盖（推荐）

**优点**：
- ✅ 完全控制 HTML 结构
- ✅ 不依赖原生 HTML
- ✅ 灵活性高
- ✅ **无需 XML 配置**（路径一致时自动匹配）

**缺点**：
- ❌ 需要维护模板

### 策略 2：样式覆盖

**优点**：
- ✅ 不需要修改模板
- ✅ 维护成本低

**缺点**：
- ❌ 受限于原生 HTML 结构
- ❌ 选择器复杂

**推荐：策略 1（模板直接覆盖）**

**技术说明**：
- 在主题中创建与原始模块路径一致的模板文件
- Magento 会自动使用主题中的模板
- 无需创建 XML 布局文件

---

## 📁 文件结构

```
app/design/frontend/Folix/game-theme/Mageplaza_SocialLogin/
└── templates/
    └── popup.phtml                    # 主弹窗模板（直接覆盖）
web/css/source/
└── Mageplaza_SocialLogin/
    └── _module.less                  # 电竞风样式（新增）
```

**说明**：
- 模板路径与原始模块一致，Magento 自动匹配
- 无需创建 `layout` 目录和 XML 文件
- 样式文件独立管理

---

## 🔧 改造步骤

### 步骤 1：创建模板文件

```bash
# 创建目录
mkdir -p app/design/frontend/Folix/game-theme/Mageplaza_SocialLogin/templates/

# 创建 popup.phtml 文件（参考完整文档中的代码）
touch app/design/frontend/Folix/game-theme/Mageplaza_SocialLogin/templates/popup.phtml
```

### 步骤 2：重写弹窗 HTML 结构

**文件：`templates/popup.phtml`**

```php
<?php
/**
 * Folix Game Theme - Social Login Popup
 *
 * 电竞风社交登录弹窗
 */
?>
<?php /** @var \Mageplaza\SocialLogin\Block\Popup $block */
if ($block->isEnabled() && $block->isEnabled() === 'popup_login'): ?>
    <div id="social-login-popup"
         class="folix-social-popup white-popup mfp-with-anim mfp-hide"
         data-mage-init='{"socialPopupForm": <?= /* @noEscape */ $block->getFormParams() ?>}'>
        
        <!-- 弹窗头部 -->
        <div class="folix-popup-header">
            <h2 class="folix-popup-title">
                <svg class="folix-popup-icon" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="currentColor"/>
                </svg>
                <?= $block->escapeHtml(__('Sign In')) ?>
            </h2>
            <button class="folix-popup-close" type="button">
                <svg width="16" height="16" viewBox="0 0 16 16">
                    <path d="M8 8.707l3.646 3.647.708-.707L8.707 8l3.647-3.646-.707-.708L8 7.293 4.354 3.646l-.707.708L7.293 8l-3.646 3.646.707.708L8 8.707z" fill="currentColor"/>
                </svg>
            </button>
        </div>

        <!-- 弹窗内容 -->
        <div class="folix-popup-content">
            <!-- 社交登录按钮 -->
            <div class="folix-social-section">
                <?= $block->getChildHtml('popup.authentication.social') ?>
            </div>

            <!-- 分隔线 -->
            <div class="folix-divider">
                <span><?= $block->escapeHtml(__('or')) ?></span>
            </div>

            <!-- 邮箱登录表单 -->
            <div class="folix-form-section">
                <?= $block->getChildHtml('popup.email') ?>
                <div class="mp-social-popup" style="padding-top: 15px">
                    <?= $block->getChildHtml('popup.authentication') ?>
                    <?= $block->getChildHtml('popup.create') ?>
                    <?= $block->getChildHtml('popup.forgot') ?>
                </div>
            </div>
        </div>

        <!-- 额外信息 -->
        <?= $block->getChildHtml('popup.additional.info') ?>
    </div>
<?php elseif ($block->isCheckMode()): ?>
    <div id="social-login-popup"
         class="folix-social-popup white-popup mfp-with-anim mfp-hide"
         data-mage-init='{"socialPopupForm": <?= /* @noEscape */ $block->getFormParams() ?>}'>
        <?= $block->getChildHtml('popup.email') ?>
    </div>
<?php endif; ?>
```

### 步骤 3：重写社交按钮模板

**文件：`templates/popup/form/social_buttons.phtml`**

```php
<?php
/**
 * Folix Game Theme - Social Login Buttons
 *
 * 电竞风社交登录按钮
 */
?>
<?php if ($block->showSocialButtons()): ?>
    <div class="folix-social-buttons">
        <div class="folix-social-title">
            <?= $block->escapeHtml(__('Sign in with')) ?>
        </div>
        <div class="folix-social-list">
            <?php foreach ($block->getSocialButtons() as $social): ?>
                <a href="<?= $block->escapeUrl($social['url']) ?>"
                   class="folix-social-btn folix-social-btn-<?= $block->escapeHtml($social['type']) ?>"
                   title="<?= $block->escapeHtml($social['label']) ?>"
                   data-bind="click: function() { return true; }">
                    <svg class="folix-social-icon" viewBox="0 0 24 24">
                        <?= $block->getSocialIcon($social['type']) ?>
                    </svg>
                    <span><?= $block->escapeHtml($social['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
```

### 步骤 4：创建电竞风样式

**文件：`web/css/source/Mageplaza_SocialLogin/_module.less`**

```less
// /**
//  * Folix Game Theme - Social Login Styles
//  *
//  * 模块：Mageplaza_SocialLogin
//  * 位置：Mageplaza_SocialLogin/web/css/source/_module.less
//  */

//
//  Common (All devices)
//  _____________________________________________

& when (@media-common = true) {

    // ============================================
    //  Social Login Popup - 社交登录弹窗
    //  ============================================

    .folix-social-popup {
        background: @folix-bg-panel;
        border: 1px solid @folix-border;
        border-radius: @folix-radius-xl;
        box-shadow: @folix-shadow-2xl;
        max-width: 480px;
        margin: 0 auto;
        overflow: hidden;

        // 弹窗头部
        .folix-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: @folix-gradient-primary;
            border-bottom: 1px solid @folix-border;

            .folix-popup-title {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0;
                font-size: 18px;
                font-weight: @font-weight__semibold;
                color: @folix-bg-dark;
                text-transform: uppercase;
                letter-spacing: 1px;

                .folix-popup-icon {
                    color: @folix-bg-dark;
                }
            }

            .folix-popup-close {
                background: transparent;
                border: none;
                cursor: pointer;
                padding: 8px;
                color: @folix-bg-dark;
                transition: all 0.3s ease;
                border-radius: @folix-radius-sm;

                &:hover {
                    background: fade(@folix-bg-dark, 20%);
                }
            }
        }

        // 弹窗内容
        .folix-popup-content {
            padding: 24px;
        }

        // 社交登录区域
        .folix-social-section {
            margin-bottom: 24px;

            .folix-social-title {
                text-align: center;
                color: @folix-text-secondary;
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 16px;
            }

            .folix-social-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 12px;
            }

            .folix-social-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 16px;
                border-radius: @folix-radius-md;
                font-weight: @font-weight__semibold;
                font-size: 14px;
                transition: all 0.3s ease;
                text-decoration: none;
                position: relative;
                overflow: hidden;

                &::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }

                &:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);

                    &::before {
                        opacity: 1;
                    }
                }

                &:active {
                    transform: translateY(0);
                }

                .folix-social-icon {
                    width: 20px;
                    height: 20px;
                }

                // Google 按钮
                &.folix-social-btn-google {
                    background: #4285F4;
                    color: #FFFFFF;
                    border: 1px solid #4285F4;

                    &:hover {
                        background: #357AE8;
                    }
                }

                // Facebook 按钮
                &.folix-social-btn-facebook {
                    background: #1877F2;
                    color: #FFFFFF;
                    border: 1px solid #1877F2;

                    &:hover {
                        background: #166FE5;
                    }
                }

                // Twitter 按钮
                &.folix-social-btn-twitter {
                    background: #1DA1F2;
                    color: #FFFFFF;
                    border: 1px solid #1DA1F2;

                    &:hover {
                        background: #1A91DA;
                    }
                }

                // WeChat 按钮
                &.folix-social-btn-wechat {
                    background: #07C160;
                    color: #FFFFFF;
                    border: 1px solid #07C160;

                    &:hover {
                        background: #06AD56;
                    }
                }
            }
        }

        // 分隔线
        .folix-divider {
            position: relative;
            text-align: center;
            margin: 24px 0;

            &::before,
            &::after {
                content: '';
                position: absolute;
                top: 50%;
                width: 40%;
                height: 1px;
                background: @folix-border;
            }

            &::before {
                left: 0;
            }

            &::after {
                right: 0;
            }

            span {
                background: @folix-bg-panel;
                padding: 0 16px;
                color: @folix-text-tertiary;
                font-size: 13px;
                font-weight: 500;
            }
        }

        // 表单区域
        .folix-form-section {
            .block {
                background: @folix-bg-page;
                border: 1px solid @folix-border;
                border-radius: @folix-radius-lg;
                padding: 20px;

                .block-title {
                    strong {
                        color: @folix-text-primary;
                        font-size: 16px;
                        font-weight: @font-weight__semibold;
                    }
                }

                .block-content {
                    .field {
                        margin-bottom: 16px;

                        label {
                            color: @folix-text-secondary;
                            font-size: 13px;
                            font-weight: 500;

                            span {
                                color: @folix-text-secondary;
                            }
                        }

                        .control {
                            input[type="email"],
                            input[type="password"] {
                                background: @folix-bg-dark;
                                border: 1px solid @folix-border;
                                border-radius: @folix-radius-sm;
                                color: @folix-text-primary;
                                padding: 10px 12px;
                                transition: all 0.3s ease;

                                &:focus {
                                    outline: none;
                                    border-color: @folix-secondary;
                                    box-shadow: 0 0 0 3px fade(@folix-secondary, 20%);
                                }

                                &::placeholder {
                                    color: @folix-text-tertiary;
                                }
                            }
                        }
                    }

                    .actions-toolbar {
                        .primary {
                            .action.primary {
                                background: @folix-gradient-primary;
                                color: @folix-bg-dark !important;
                                border: none;
                                border-radius: @folix-radius-md;
                                padding: 12px 24px;
                                font-weight: @font-weight__semibold;
                                box-shadow: 0 2px 8px fade(@folix-secondary, 30%);
                                transition: all 0.3s ease;

                                &:hover {
                                    background: @folix-gradient-primary-hover;
                                    transform: translateY(-2px);
                                    box-shadow: 0 4px 12px fade(@folix-secondary, 40%);
                                }

                                &:active {
                                    transform: translateY(0);
                                }

                                span {
                                    color: @folix-bg-dark;
                                }
                            }
                        }

                        .secondary {
                            margin-top: 12px;

                            a {
                                color: @folix-secondary;
                                text-decoration: none;
                                font-size: 13px;
                                transition: all 0.3s ease;

                                &:hover {
                                    color: @folix-text-primary;
                                    text-decoration: underline;
                                }

                                span {
                                    color: @folix-secondary;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

### 步骤 5：更新主题配置

**文件：`web/css/source/_theme.less`**

```less
//  Import custom variables
@import '_custom-variables-esports.less';

// ... 其他导入 ...

//  Import Mageplaza Social Login styles
@import 'Mageplaza_SocialLogin/_module.less';
```

---

## 🎯 关键集成点

### 1. 保持与原生的集成

```php
// 保持 data-mage-init 不变
data-mage-init='{"socialPopupForm": <?= /* @noEscape */ $block->getFormParams() ?>}'
```

### 2. 保持 socialCallback 函数

```javascript
// 保持 provider.js 中的全局回调函数
window.socialCallback = function (url, windowObj) {
    customerData.invalidate(['customer']);
    customerData.reload(['customer'], true);
    window.location.href = url || window.location.reload(true);
    windowObj.close();
};
```

### 3. 保持拦截机制

```javascript
// 保持 popup.js 中的拦截逻辑
initLink: function () {
    var headerLink = $(this.options.headerLink);
    headerLink.find('a').each(function () {
        if (href.includes('customer/account/login')) {
            el.on('click', function (event) {
                showLogin();
                event.preventDefault();
            });
        }
    });
}
```

---

## ✅ 验收标准

- [x] 电竞风弹窗设计（深蓝 + 金色）
- [x] 社交登录按钮样式定制
- [x] 登录表单样式定制
- [x] 注册表单样式定制
- [x] 忘记密码样式定制
- [x] 保持所有社交登录功能
- [x] 与原生 customer-data.js 无缝集成
- [x] 响应式设计（PC 端和移动端）
- [x] 统一使用变量（无硬编码颜色值）

---

## 📝 注意事项

1. **模板覆盖**：完全控制 HTML 结构，灵活性最高
2. **样式覆盖**：保持与 Mageplaza Social Login 的兼容性
3. **功能保持**：不修改 JavaScript 逻辑，只改样式
4. **变量统一**：使用电竞风变量，便于换肤
5. **兼容性**：适用于各种主题和布局

---

## 🚀 部署步骤

```bash
# 清理缓存
bin/magento cache:clean
bin/magento cache:flush

# 生成静态内容
bin/magento setup:static-content:deploy -f

# 清理编译缓存
bin/magento setup:di:compile
```

---

## 📚 参考资料

- `SOCIAL_LOGIN_INTEGRATION_MECHANISM.md` - 社交登录集成机制
- `NATIVE_LOGIN_INTERCEPTION_MECHANISM.md` - 原生登录按钮拦截机制
- `CUSTOMER_MODULE_CUSTOMIZATION.md` - Customer 模块电竞风定制
