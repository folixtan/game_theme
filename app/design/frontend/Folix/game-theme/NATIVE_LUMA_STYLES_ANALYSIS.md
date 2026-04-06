# 原生Luma移动端样式分析

## 一、Navigation（导航）样式分析

### 1.1 移动端导航核心机制

**文件位置**：`theme-frontend-blank/web/css/source/_navigation.less`

**核心样式**：

```less
// 1. 汉堡菜单按钮
.nav-toggle {
    .lib-icon-font(
        @icon-menu,
        @_icon-font-size: 28px,
        @_icon-font-color: @header-icons-color,
        @_icon-font-color-hover: @header-icons-color-hover
    );
    cursor: pointer;
    display: block;
    left: 15px;
    position: absolute;
    top: 15px;
    z-index: 14;
}

// 2. 导航侧边栏（默认隐藏）
.nav-sections {
    -webkit-overflow-scrolling: touch;
    transition: left .3s;
    height: 100%;
    left: calc(-1 * (100% - 54px));  // 默认隐藏在左侧
    overflow: auto;
    position: fixed;
    top: 0;
    width: calc(100% - 54px);  // 宽度为屏幕宽度减去54px
}

// 3. 导航打开状态
.nav-open .nav-sections {
    left: 0;  // 显示导航
    z-index: 99;
    box-shadow: 0 0 5px 0 rgba(50, 50, 50, .75);
}

// 4. 遮罩效果
.nav-open .nav-toggle:after {
    background: rgba(0, 0, 0, 0.7);
    content: '';
    display: block;
    height: 100%;
    position: fixed;
    right: 0;
    top: 0;
    width: 100%;
    z-index: 1;
}
```

**关键机制**：
- ✅ 使用 `position: fixed` 实现侧边栏
- ✅ 使用 `left` 属性控制显示/隐藏
- ✅ 保留54px的空间（用于显示关闭按钮）
- ✅ 使用伪元素 `::after` 创建遮罩
- ✅ 使用 `transition` 实现动画效果

---

### 1.2 标签页导航

**原生实现**：

```less
// 标签页标题
.nav-sections-item-title {
    background: darken(@navigation__background, 5%);
    border: solid darken(@navigation__background, 10%);
    border-width: 0 0 1px 1px;
    box-sizing: border-box;
    float: left;
    height: 71px;
    padding-top: 24px;
    text-align: center;
    width: 33.33%;  // 三个标签页，各占33.33%

    &.active {
        background: transparent;
        border-bottom: 0;
    }
}

// 标签页内容
.nav-sections-item-content {
    box-sizing: border-box;
    float: right;
    margin-left: -100%;
    margin-top: 71px;
    width: 100%;
    padding: @indent__m 0;
}
```

**关键机制**：
- ✅ 使用 `float: left` 实现标签页标题
- ✅ 使用 `width: 33.33%` 实现三等分
- ✅ 使用 `margin-top: 71px` 为标签页标题留出空间
- ✅ 使用 `.active` 类控制当前激活状态

---

### 1.3 菜单项样式

**原生实现**：

```less
.navigation {
    padding: 0;

    .parent {
        .level-top {
            // 展开图标
            .lib-icon-font(
                @_icon-font-content: @icon-down,
                @_icon-font-size: 42px,
                @_icon-font-position: after,
                @_icon-font-display: block
            );
            position: relative;

            &:after {
                position: absolute;
                right: 7px;
                top: -8px;
            }

            // 激活状态
            &.ui-state-active {
                .lib-icon-font-symbol(
                    @_icon-font-content: @icon-up,
                    @_icon-font-position: after
                );
            }
        }
    }
}
```

**关键机制**：
- ✅ 使用 `.lib-icon-font()` mixin添加图标
- ✅ 使用 `@icon-down` 和 `@icon-up` 控制展开/折叠
- ✅ 使用 `.ui-state-active` 类控制状态（由jQuery UI Menu控制）

---

## 二、Top Bar和Main Bar样式

### 2.1 Top Bar样式

**原生实现**：

```less
.panel.header {
    .links,
    .switcher {
        display: none;  // 移动端隐藏
    }
}

// 桌面端
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    .panel.header {
        .links,
        .switcher {
            display: inline-block;  // 桌面端显示
        }
    }
}
```

**关键机制**：
- ✅ 移动端默认隐藏Top Bar的链接和切换器
- ✅ 桌面端通过媒体查询显示

---

### 2.2 Main Bar样式

**原生实现**：

```less
// Logo
.logo {
    float: left;
    margin: 0 0 25px 15px;
    max-width: 50%;
    z-index: 5;
}

// 搜索框
.block-search {
    .label {
        display: none;  // 桌面端隐藏label
    }
    
    // 移动端
    @media (max-width: @screen__m) {
        .label {
            display: block;  // 移动端显示label（作为搜索图标）
        }
    }
}

// 购物车
.minicart-wrapper {
    float: right;
    margin: 0 10px 0 0;
}
```

---

## 三、关键变量

### 3.1 导航相关变量

```less
@active-nav-indent: 54px;  // 导航保留空间

@navigation__background: @color-gray94;  // 导航背景色
@navigation-level0-item__active__color: @primary__color;  // 激活菜单项颜色

@submenu__padding-left: 15px;  // 子菜单左内边距
```

### 3.2 图标变量

```less
@icon-menu: '\e608';  // 汉堡菜单图标
@icon-down: '\e607';  // 向下箭头
@icon-up: '\e618';    // 向上箭头
```

---

## 四、可以复用的Mixin

### 4.1 .lib-icon-font()

**用途**：添加图标字体

**参数**：
- `@_icon-font-content`: 图标内容
- `@_icon-font-size`: 图标大小
- `@_icon-font-position`: 图标位置（before/after）
- `@_icon-font-display`: 显示方式

**示例**：
```less
.nav-toggle {
    .lib-icon-font(
        @icon-menu,
        @_icon-font-size: 28px,
        @_icon-font-color: @header-icons-color
    );
}
```

---

### 4.2 .lib-main-navigation()

**用途**：主导航样式（移动端）

**特点**：
- 自动处理菜单项样式
- 自动处理子菜单展开/折叠
- 遵循Magento规范

---

## 五、原生机制的优缺点

### 5.1 优点

✅ **成熟的交互机制**：
- 使用jQuery UI Menu控制菜单展开/折叠
- 使用CSS transition实现动画
- 使用遮罩防止误操作

✅ **标签页导航清晰**：
- Menu、Account、Settings三个标签页
- 自动切换机制
- 移动端优化

✅ **响应式设计完善**：
- 使用媒体查询控制显示/隐藏
- 移动端和桌面端分离

---

### 5.2 缺点

❌ **标签页导航不够现代**：
- 不符合现代移动端体验（如seagm.com）
- 占用过多垂直空间

❌ **样式定制困难**：
- 标签页标题固定三等分
- 样式耦合度高

---

## 六、我们的修改策略

### 6.1 保留原生机制

✅ **保留**：
- `.nav-toggle` 汉堡菜单按钮机制
- `.nav-sections` 侧边栏滑动机制
- 遮罩效果
- jQuery UI Menu控制菜单展开/折叠

### 6.2 修改部分

⚠️ **修改**：
- 移除标签页导航（Menu/Account/Settings）
- 改为单一菜单列表
- 优化Top Bar显示

### 6.3 新增部分

➕ **新增**：
- Top Bar在移动端显示（汉堡菜单 + 品牌名 + 语言/货币 + 登录）
- Main Bar重构（汉堡菜单 + 搜索 + 用户/登录）
- 菜单项active状态样式（使用@folix-secondary颜色）

---

## 七、总结

### 7.1 原生样式精华

1. ✅ **侧边栏导航机制** - 使用fixed定位和left控制
2. ✅ **汉堡菜单按钮** - 使用.lib-icon-font() mixin
3. ✅ **遮罩效果** - 使用伪元素实现
4. ✅ **响应式设计** - 使用媒体查询

### 7.2 我们要做的事

1. ✅ 复用原生导航机制（fixed + left）
2. ✅ 移除标签页导航
3. ✅ 添加Top Bar移动端样式
4. ✅ 重构Main Bar移动端样式
5. ✅ 使用@folix变量系统

### 7.3 核心原则

**最小化修改，最大化复用**：
- ✅ 复用原生的侧边栏机制
- ✅ 复用原生的遮罩效果
- ✅ 复用原生的菜单展开/折叠逻辑
- ✅ 只修改必要的样式部分

---

## 八、下一步

1. ✅ 查看当前项目已实现的样式
2. ✅ 找出样式差异
3. ✅ 设计修改方案
4. ✅ 编写样式代码（在原生基础上修改）
