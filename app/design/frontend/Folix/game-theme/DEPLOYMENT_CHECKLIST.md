# Folix Game Theme - 部署检查清单

## 📋 部署前检查

### 1. 文件检查
- [x] `_extend.less` 存在于 `web/css/source/` 目录
- [x] `_extend.less` 正确引入了 `extends/_header.less`
- [x] `extends/_header.less` 包含自定义样式
- [x] `_theme.less` 和 `_variables.less` 配置正确

### 2. 样式内容验证
- [x] Top Bar padding: `padding: 22px 20px;`
- [x] 导航悬停渐变: `linear-gradient(135deg, #EA580C, #C2410C)`
- [x] 搜索框焦点外发光: `:focus-within` 伪类
- [x] 自定义类: `.header-links-top`, `.header-links-left`

## 🚀 部署步骤

### 方式 1: 使用部署脚本（推荐）
```bash
cd /path/to/magento
chmod +x app/design/frontend/Folix/game-theme/deploy.sh
./app/design/frontend/Folix/game-theme/deploy.sh
```

### 方式 2: 手动部署
```bash
cd /path/to/magento

# 1. 清除缓存
php bin/magento cache:clean

# 2. 清除静态文件
rm -rf pub/static/frontend/Folix/game-theme/*
rm -rf var/view_preprocessed/pub/static/frontend/Folix/game-theme/*

# 3. 重新编译
php bin/magento setup:static-content:deploy --theme=Folix/game-theme

# 4. 生产模式需要编译 DI
php bin/magento setup:di:compile
```

## ✅ 部署后验证

### 1. 检查编译后的文件
打开编译后的 CSS 文件，搜索以下关键词：
```
pub/static/frontend/Folix/game-theme/en_US/css/styles-l.css
```

搜索关键词：
- `header-links-top` - 应该找到自定义类
- `padding: 22px 20px` - 应该找到 Top Bar 样式
- `linear-gradient(135deg, #EA580C, #C2410C)` - 应该找到渐变
- `focus-within` - 应该找到搜索框焦点样式

### 2. 浏览器验证
打开浏览器开发者工具（F12）：

1. **Network 标签**
   - 刷新页面（Ctrl+Shift+R）
   - 检查 `styles-l.css` 和 `styles-m.css` 是否有新版本号
   - 确认文件大小是否变化

2. **Elements 标签**
   - 检查 `.panel.header` 元素
   - 查看 Computed 样式中的 padding 是否为 `22px 20px`
   
3. **Console 标签**
   - 检查是否有 CSS 加载错误
   - 检查是否有 404 错误

### 3. 视觉验证

#### Top Bar（辅助导航栏）
- [ ] 高度约 60px（通过 padding 控制）
- [ ] 深蓝渐变背景 `#1E293B → #0F172A`
- [ ] 左侧链接清晰可见
- [ ] 右侧 APP 下载链接和语言切换正常

#### Main Bar（主导航栏）
- [ ] 白色背景
- [ ] 底部橙色边框 3px
- [ ] 导航项悬停有渐变背景（橙色渐变）
- [ ] 二级菜单不与一级导航重叠

#### 搜索框
- [ ] 点击时显示蓝色外发光
- [ ] 搜索按钮可点击
- [ ] 搜索按钮悬停有渐变背景 + 上移效果

## 🐛 故障排除

### 问题 1: 样式没有生效
**解决方案：**
```bash
# 清除所有缓存
php bin/magento cache:flush
rm -rf var/cache/*
rm -rf var/page_cache/*

# 清除浏览器缓存
Ctrl+Shift+R (Windows) 或 Cmd+Shift+R (Mac)

# 检查文件权限
chmod -R 775 pub/static/
chmod -R 775 var/
```

### 问题 2: CSS 文件版本号没变
**解决方案：**
```bash
# 强制重新编译
rm -rf pub/static/frontend/Folix/game-theme/*
php bin/magento setup:static-content:deploy --theme=Folix/game-theme -f
```

### 问题 3: 编译错误
**解决方案：**
```bash
# 检查 LESS 语法错误
php bin/magento dev:source-theme:deploy Folix/game-theme

# 查看错误日志
tail -f var/log/system.log
tail -f var/log/exception.log
```

### 问题 4: 部分样式生效，部分不生效
**解决方案：**
- 检查 CSS 优先级，可能需要使用 `!important`
- 检查是否有其他 CSS 文件覆盖了样式
- 使用浏览器开发者工具检查样式来源

## 📊 性能优化

### 生产环境建议
```bash
# 设置为生产模式
php bin/magento deploy:mode:set production

# 合并 CSS
php bin/magento config:set dev/css/merge_css_files 1

# 压缩 CSS
php bin/magento config:set dev/css/minify_files 1

# 启用缓存
php bin/magento cache:enable
```

## 📝 注意事项

1. **开发模式 vs 生产模式**
   - 开发模式：每次修改 LESS 文件后，刷新页面会自动编译
   - 生产模式：必须手动运行部署命令

2. **版本号**
   - Magento 使用版本号管理静态文件缓存
   - 每次部署后，版本号会更新
   - 如果版本号不变，说明没有重新编译

3. **权限问题**
   - 确保 `pub/static/` 和 `var/` 目录有写权限
   - 文件所有者应该是 web 服务器用户（如 www-data）

## 🎯 快速检查命令

```bash
# 一键检查样式是否正确
grep -r "padding: 22px 20px" pub/static/frontend/Folix/game-theme/*/css/

# 检查主题是否注册
php bin/magento dev:query:theme:show

# 检查文件修改时间
ls -lh pub/static/frontend/Folix/game-theme/en_US/css/styles-l.css
```

---

**如有问题，请查看日志：**
- `var/log/system.log`
- `var/log/exception.log`
- `var/log/debug.log` (开发模式)
