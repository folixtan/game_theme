# Folix 主题开发规则和边界

## 一、核心开发流程（必须严格遵守）

### 1. 四步开发法

```
第1步：查看原生代码
  ↓
第2步：查看已实现功能
  ↓
第3步：找出差异
  ↓
第4步：基于原生添加/修改
```

### 2. 禁止行为

- ❌ **禁止重复写代码**：已经实现的样式、结构、功能，不要重复写
- ❌ **禁止完全重写**：只修改差异部分
- ❌ **禁止滥用 !important**：只在真正需要覆盖原生样式时使用
- ❌ **禁止猜测**：不确定时要先查看已实现的内容

---

## 二、开发前检查清单

### 1. 查看已实现内容

**布局文件**：
- [ ] 检查 `default.xml` 中是否已有 block/container
- [ ] 检查 block 的 name 是否重复
- [ ] 检查 container 的位置

**模板文件**：
- [ ] 检查是否已有对应的 .phtml 文件
- [ ] 检查模板中的变量和方法

**CSS 文件**：
- [ ] 检查 `_header.less` 中是否已有样式
- [ ] 检查 CSS 变量是否已定义（`_variables.less`）
- [ ] 检查选择器优先级

### 2. 差异分析

**新增功能**：
- 描述需要添加什么
- 说明为什么不能复用已有代码

**修改功能**：
- 描述需要修改什么
- 说明已有代码的哪些部分可以保留

---

## 三、CSS 开发规范

### 1. 变量使用

**正确做法**：
```less
// 使用已定义的变量
color: @folix-text-primary;
background: @folix-bg-page;
border-color: @folix-border-light;
```

**错误做法**：
```less
// 硬编码颜色
color: #1E293B;
background: #F8FAFC;
border-color: #E2E8F0;
```

### 2. 选择器优先级

**正确做法**：
```less
// 使用更具体的选择器提高优先级
.page-header .panel.wrapper {
    display: block;
}
```

**错误做法**：
```less
// 滥用 !important
.panel.wrapper {
    display: block !important;
}
```

### 3. 响应式断点

**移动端样式**：
```less
.media-width(@extremum, @break) when (@extremum = 'max') and (@break = @screen__m) {
    // 移动端样式
}
```

**PC端样式**：
```less
.media-width(@extremum, @break) when (@extremum = 'min') and (@break = @screen__m) {
    // PC端样式
}
```

---

## 四、布局文件规范

### 1. Block 命名

**正确做法**：
```xml
<block name="catalog.topnav.desktop" />
<block name="catalog.topnav.mobile" />
```

**错误做法**：
```xml
<block name="catalog.topnav" /> <!-- name 重复 -->
```

### 2. Container 使用

**正确做法**：
```xml
<referenceContainer name="page.top">  <!-- 正确 -->
    <container name="mobile.nav.container">
        ...
    </container>
</referenceContainer>
```

**错误做法**：
```xml
<referenceContainer name="page.top.container">  <!-- 错误的容器名 -->
    ...
</referenceContainer>
```

---

## 五、开发模板

### 1. 添加新功能模板

```markdown
## 第1步：查看原生代码
- 文件：[列出相关文件]
- 已有内容：[描述已实现的内容]

## 第2步：查看已实现功能
- 布局：[检查 default.xml]
- 模板：[检查 .phtml 文件]
- 样式：[检查 .less 文件]

## 第3步：找出差异
- 缺少：[列出缺少的功能]
- 需要修改：[列出需要修改的部分]

## 第4步：基于原生添加/修改
- 添加：[只添加缺少的部分]
- 修改：[只修改必要的差异]
```

### 2. 修复问题模板

```markdown
## 问题分析
- 现象：[描述问题]
- 原因：[分析原因]

## 查看已实现
- 文件：[相关文件]
- 样式：[相关CSS]

## 修复方案
- 修改：[只修改问题部分]
- 不动：[保持已有代码]
```

---

## 六、本次开发总结

### 移动端布局开发

**需求确认**：
1. ✅ Top Bar：左侧汉堡图标，点击展开/收起 header-links-left
2. ✅ Main Bar：左汉堡 | 中搜索 | 右购物车+登录
3. ✅ 侧边栏：Logo + 关闭按钮 + 主菜单

**已实现内容**（不重复写）：
- 主菜单结构（`catalog.topnav.mobile`）
- 侧边栏容器（`mobile-nav-container`）
- 白色背景
- 深灰色文字
- 橙色高亮激活

**本次添加**：
- Logo 和关闭按钮（`mobile-nav-header.phtml`）
- Top Bar 展开功能（待实现）

**Git 提交**：
- ef4b4e29f - feat(mobile): 添加侧边栏 Logo 和关闭按钮

---

## 七、重要提醒

### 1. 每次对话开始前

- [ ] 先查看已实现的内容
- [ ] 画图确认需求
- [ ] 分析差异
- [ ] 只修改差异部分

### 2. 避免的错误

- ❌ 不要猜测，要查看代码
- ❌ 不要重复，要复用
- ❌ 不要重写，要修改
- ❌ 不要编造，要确认

### 3. 提交信息规范

```
<type>(<scope>): <subject>

完成内容：
1. 文件名 - 修改内容
2. 遵循开发流程
3. 不重复已有代码
```

**Type**：
- feat: 新功能
- fix: 修复
- refactor: 重构
- docs: 文档

---

## 八、参考文档

- `COMPRESSED_SUMMARY.md` - 项目总览
- `MOBILE_HEADER_PROTOTYPE.md` - 移动端原型设计
- `NATIVE_LUMA_STYLES_ANALYSIS.md` - Luma 原生样式分析
- `web/css/source/_variables.less` - CSS 变量定义

---

**最后更新**：2024-01-XX
**版本**：1.0
