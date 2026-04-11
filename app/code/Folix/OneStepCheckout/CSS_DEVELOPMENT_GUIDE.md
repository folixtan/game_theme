# OneStepCheckout CSS 开发规范

## 📐 文件结构

```
app/code/Folix/OneStepCheckout/view/frontend/web/css/source/
├── _module.less          # ✅ 必需 - 模块级样式入口
└── components/           # 可选 - 组件级样式拆分
    ├── _login-notice.less
    └── _xxx.less
```

**重要**: 
- `_module.less` 会被Magento自动加载
- **无需**在 `default.xml` 中手动引入CSS
- 组件样式可以在 `_module.less` 中 `@import`

---

## 🎯 核心规范

### **1. 移动优先 (Mobile First)**

```less
// ✅ 正确写法
& when (@media-common = true) {
    // 移动端基础样式
    .component {
        padding: @folix-space-4;
        font-size: 14px;
    }
}

// 平板及以上
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .component {
        padding: @folix-space-6;
        font-size: 16px;
    }
}

// 桌面及以上
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__l) {
    .component {
        padding: @folix-space-8;
        font-size: 18px;
    }
}

// ❌ 错误写法 - 不要使用 max-width
@media (max-width: 767px) {
    .component { ... }
}
```

---

### **2. 使用主题变量**

#### **颜色变量**
```less
// ✅ 正确
.color-primary { color: @folix-primary; }           // #4A90E2
.color-accent { color: @folix-accent; }             // #FF6B35
.bg-panel { background: @folix-bg-panel; }          // #F1F5F9
.text-main { color: @folix-text-primary; }          // #1E293B
.text-sub { color: @folix-text-secondary; }         // #475569

// ❌ 错误 - 禁止硬编码
.color-primary { color: #4A90E2; }
.bg-panel { background: #F1F5F9; }
```

#### **间距变量**
```less
// ✅ 正确
.padding-sm { padding: @folix-space-2; }   // 8px
.padding-md { padding: @folix-space-4; }   // 16px
.padding-lg { padding: @folix-space-6; }   // 24px
.margin-xl { margin: @folix-space-8; }     // 32px

// ❌ 错误
.padding-sm { padding: 8px; }
.margin-xl { margin: 32px; }
```

#### **圆角变量**
```less
// ✅ 正确
.radius-sm { border-radius: @folix-radius-sm; }   // 4px
.radius-md { border-radius: @folix-radius-md; }   // 8px
.radius-lg { border-radius: @folix-radius-lg; }   // 12px

// ❌ 错误
.radius-md { border-radius: 8px; }
```

#### **阴影变量**
```less
// ✅ 正确
.shadow-card { box-shadow: @folix-shadow; }
.shadow-hover { box-shadow: @folix-shadow-md; }
.shadow-primary { box-shadow: @folix-shadow-primary; }

// ❌ 错误
.shadow-card { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
```

#### **过渡动画**
```less
// ✅ 正确
.transition-fast { transition: @folix-transition-fast; }   // 0.15s
.transition-normal { transition: @folix-transition; }      // 0.2s
.transition-smooth { transition: @folix-transition-smooth; } // 0.3s cubic-bezier

// ❌ 错误
.transition { transition: all 0.3s ease; }
```

---

### **3. 完整示例**

```less
/**
 * Login Notice Component
 */
& when (@media-common = true) {
    
    .folix-login-notice {
        // 使用主题变量
        margin: @folix-space-6 0;
        padding: @folix-space-6;
        background: linear-gradient(135deg, lighten(@folix-bg-panel, 2%) 0%, @folix-bg-panel 100%);
        border-radius: @folix-radius-lg;
        border-left: 4px solid @folix-primary;
        
        .notice-content {
            display: flex;
            align-items: center;
            gap: @folix-space-6;
            
            .notice-icon {
                flex-shrink: 0;
                color: @folix-primary;
            }
            
            .notice-text {
                flex: 1;
                
                h3 {
                    margin: 0 0 @folix-space-2 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: @folix-text-primary;
                }
                
                p {
                    margin: 0 0 @folix-space-1 0;
                    font-size: 14px;
                    color: @folix-text-secondary;
                    line-height: 1.5;
                    
                    &.notice-hint {
                        font-size: 12px;
                        color: @folix-accent;
                        font-style: italic;
                    }
                }
            }
            
            .action.primary {
                flex-shrink: 0;
                padding: @folix-space-4 @folix-space-8;
                background-color: @folix-primary;
                border: none;
                border-radius: @folix-radius-md;
                color: #FFFFFF;  // 白色可以硬编码
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all @folix-transition-smooth;
                
                &:hover {
                    background-color: @folix-primary-dark;
                    transform: translateY(-2px);
                    box-shadow: @folix-shadow-primary;
                }
                
                &:active {
                    transform: translateY(0);
                }
            }
        }
    }
}

// 平板端调整
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .folix-login-notice {
        margin: @folix-space-8 0;
        padding: @folix-space-8;
    }
}
```

---

## 🎨 常用主题变量速查

### **品牌色**
```less
@folix-primary: #4A90E2;          // 主色 - 蓝色
@folix-secondary: #FF6B35;        // 强调色 - 橙色
@folix-accent: #6C5CE7;           // 搭配色 - 紫色
```

### **文字色**
```less
@folix-text-primary: #1E293B;     // 主文字
@folix-text-secondary: #475569;   // 次文字
@folix-text-tertiary: #64748B;    // 三级文字
```

### **背景色**
```less
@folix-bg-page: #F8FAFC;          // 页面背景
@folix-bg-panel: #F1F5F9;         // 面板背景
@folix-bg-card: #FFFFFF;          // 卡片背景
```

### **间距 (Spacing)**
```less
@folix-space-1: 4px;
@folix-space-2: 8px;
@folix-space-3: 12px;
@folix-space-4: 16px;
@folix-space-5: 20px;
@folix-space-6: 24px;
@folix-space-8: 32px;
@folix-space-10: 40px;
@folix-space-12: 48px;
```

### **圆角 (Radius)**
```less
@folix-radius-sm: 4px;
@folix-radius: 6px;
@folix-radius-md: 8px;
@folix-radius-lg: 12px;
@folix-radius-xl: 16px;
```

### **阴影 (Shadow)**
```less
@folix-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
@folix-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
@folix-shadow-md: 0 4px 14px rgba(74, 144, 226, 0.12);
@folix-shadow-primary: 0 4px 14px rgba(74, 144, 226, 0.25);
```

### **过渡 (Transition)**
```less
@folix-transition-fast: 0.15s ease;
@folix-transition: 0.2s ease;
@folix-transition-smooth: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
```

### **断点 (Breakpoints)**
```less
@screen__xs: 480px;
@screen__s: 640px;
@screen__m: 768px;    // 平板
@screen__l: 1024px;   // 桌面
@screen__xl: 1280px;
@screen__xxl: 1536px;
```

---

## ⚠️ 常见错误

### **❌ 错误1: 硬编码颜色和间距**
```less
// 错误
.button {
    padding: 16px 32px;
    background: #4A90E2;
    border-radius: 8px;
}

// 正确
.button {
    padding: @folix-space-4 @folix-space-8;
    background: @folix-primary;
    border-radius: @folix-radius-md;
}
```

### **❌ 错误2: 使用 max-width 媒体查询**
```less
// 错误
@media (max-width: 767px) {
    .component { padding: 8px; }
}

// 正确 - 移动优先
& when (@media-common = true) {
    .component { padding: @folix-space-2; }  // 移动端默认
}

.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .component { padding: @folix-space-4; }  // 平板及以上
}
```

### **❌ 错误3: 忘记 @media-common 包裹**
```less
// 错误
.component {
    padding: @folix-space-4;
}

// 正确
& when (@media-common = true) {
    .component {
        padding: @folix-space-4;
    }
}
```

### **❌ 错误4: 在 default.xml 中手动引入CSS**
```xml
<!-- 错误 - 不需要 -->
<head>
    <css src="Folix_OneStepCheckout::css/styles-m.css"/>
</head>

<!-- 正确 - _module.less 会自动加载 -->
<head>
    <!-- 空或注释说明即可 -->
</head>
```

---

## 🔧 调试技巧

### **查看编译后的CSS**
```bash
# 编译后的文件位置
pub/static/frontend/Folix/game-theme/zh_Hans_CN/Folix_OneStepCheckout/css/styles-m.css
pub/static/frontend/Folix/game-theme/zh_Hans_CN/Folix_OneStepCheckout/css/styles-l.css
```

### **检查变量是否定义**
```bash
# 搜索变量定义
grep -r "@folix-primary" app/design/frontend/Folix/game-theme/web/css/source/_variables.less
```

### **强制重新编译**
```bash
# 清理静态文件
rm -rf pub/static/frontend/Folix/game-theme/*

# 重新部署
php bin/magento setup:static-content:deploy -f

# 清理缓存
php bin/magento cache:clean
```

---

## 📚 参考资料

- [主题变量定义](_variables.less)
- [Magento LESS 文档](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/css-topics/css-preprocess.html)
- [移动优先设计](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/responsive-web-design/rwd-mobile-first.html)

---

**最后更新**: 2026-04-07  
**版本**: v1.0
