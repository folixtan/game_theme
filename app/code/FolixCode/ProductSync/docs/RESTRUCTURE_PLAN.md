# Magento 分类重组方案

## 📋 一、现状分析

### 当前问题
- **859 个分类**全部平级（直接挂在 Default Category 下）
- 菜单过长，用户体验差
- 分类结构混乱，不利于 SEO 和导航

### 分类统计（按产品属性）
| 类型 | 数量 | 说明 |
|------|------|------|
| 卡密类 (charge_type=3) | 462 个 | 礼品卡、游戏卡、会员卡等 |
| 直充类 (charge_type=4) | 297 个 | 游戏充值、代充等 |
| 服务类 | 3 个 | 加速器、直播、陪玩等 |
| 空分类 | 100 个 | 无关联产品，可隐藏 |

---

## 🏗️ 二、目标结构（6 层架构）

```
Level 1: Root Catalog (ID: 1) - 系统根目录（隐藏）
Level 2: Default Category (ID: 2) - 后台默认分类（隐藏）

Level 3: 顶级分类（前端主菜单，3个）
  ├── Game Cards & Keys (卡密类，462个)
  ├── Game Top-Up (直充类，297个)
  └── Game Services (服务类，3个)

Level 4: 业务类型分组（前端二级菜单）
  卡密类下：
    ├── Gift Cards (礼品卡)
    ├── Game Cards (游戏卡)
    ├── Membership (会员卡)
    ├── Prepaid Cards (预付卡)
    └── Digital Codes (兑换码)
  
  直充类下：
    ├── Mobile Games (手游直充)
    ├── PC Games (PC游戏直充)
    ├── Console Games (主机游戏直充)
    └── Game Currency (游戏货币)

Level 5: 品牌/平台分组（前端三级菜单）
  例如 Mobile Games 下：
    ├── PUBG Mobile (20个分类)
    ├── Genshin Impact (15个分类)
    ├── Free Fire (18个分类)
    └── Others (剩余按字母分组)

Level 6: 真实分类（前端不显示，包含实际产品）
  例如 PUBG Mobile 下：
    ├── 60 UC
    ├── 325 UC
    ├── 660 UC
    └── 1800 UC
```

---

## 🎯 三、分组规则设计

### 3.1 Level 3（顶级分类）- 按产品属性
**判断依据**: 分类下产品的 `game_charge_type` 属性

```php
if (charge_type == 3) → Game Cards & Keys (卡密类)
if (charge_type == 4) → Game Top-Up (直充类)
其他 → Game Services (服务类)
```

### 3.2 Level 4（业务类型）- 按业务属性
**卡密类分组规则**:

| Level 4 分组 | 关键词匹配规则 | 预计数量 |
|-------------|---------------|---------|
| Gift Cards | 包含 "Amazon", "Apple", "Google Play", "Gift Card" | ~180 |
| Game Cards | 包含 "Steam", "PlayStation", "Xbox", "Nintendo" | ~120 |
| Membership | 包含 "Netflix", "Spotify", "Disney", "会员" | ~60 |
| Prepaid Cards | 包含 "Visa", "Mastercard", "Prepaid" | ~40 |
| Others | 剩余分类 | ~62 |

**直充类分组规则**:

| Level 4 分组 | 关键词匹配规则 | 预计数量 |
|-------------|---------------|---------|
| Mobile Games | 包含 "Mobile", "手游", 或知名手游名称 | ~120 |
| PC Games | 包含 "Steam", "Epic", "PC", "Origin" | ~80 |
| Console Games | 包含 "PSN", "Xbox", "Nintendo Switch" | ~60 |
| Game Currency | 包含 "Currency", "Coins", "Diamonds", "UC" | ~37 |

### 3.3 Level 5（品牌/平台）- 按品牌名称

**提取规则**:
```php
// 从分类名称中提取品牌名
"7-Eleven Card(HK)" → "7-Eleven"
"PUBG Mobile 60 UC" → "PUBG Mobile"
"Genshin Impact 60 Primogems" → "Genshin Impact"
"Steam Wallet 20 USD" → "Steam Wallet"
```

**分组策略**:
- **出现 ≥ 5 次**的品牌 → 独立 Level 5 分组
- **出现 < 5 次**的品牌 → 归入 "Others"，按首字母再分成 A-F, G-L, M-Z 等

**示例**:
```
Level 4: Mobile Games (120个)
  └── Level 5: PUBG Mobile (20个)
       └── Level 6: 60 UC, 325 UC, 660 UC...
  └── Level 5: Genshin Impact (15个)
  └── Level 5: Free Fire (18个)
  └── Level 5: A-F (22个，其他品牌按字母)
  └── Level 5: G-L (20个)
  └── Level 5: M-Z (25个)
```

### 3.4 Level 6（真实分类）- 最终归集
- 所有现有的 859 个分类最终都移动到这里
- 不再需要进一步分组
- `include_in_menu = 0`（前端不显示）

---

## 📊 四、实施步骤

### Step 1: 分析分类归属
```php
// 1. 查询每个分类下产品的 game_charge_type 属性
// 2. 统计该分类下最多的 charge_type
// 3. 根据最多的类型确定归属（卡密 or 直充）
// 4. 从分类名称提取品牌/平台名称
```

**输出**: 分类归属映射表
```
[
  'category_id' => 5,
  'name' => 'PUBG Mobile 60 UC',
  'level_3' => 'Game Top-Up',
  'level_4' => 'Mobile Games',
  'level_5' => 'PUBG Mobile',
  'charge_type' => 4
]
```

### Step 2: 创建层级结构
```php
// 创建 Level 3（3个顶级分类）
// 创建 Level 4（9个业务类型分组）
// 创建 Level 5（根据实际品牌数量动态创建）
```

### Step 3: 移动真实分类
```php
// 遍历 859 个分类
// 根据映射表找到对应的 Level 5 父分类
// 移动分类到 Level 6
```

### Step 4: 设置可见性
```php
// Level 3-5: include_in_menu = 1（前端显示）
// Level 6: include_in_menu = 0（前端隐藏）
```

### Step 5: 验证与清理
```php
// 1. 重建 URL Rewrites
// 2. 重建分类索引
// 3. 清理缓存
// 4. 验证分类树完整性
```

---

##  五、技术实现

### 5.1 CLI 命令
```bash
# 1. 预览模式（生成报告，不修改数据）
php bin/magento folixcode:restructure:categories --dry-run

# 2. 执行模式（实际操作）
php bin/magento folixcode:restructure:categories --execute

# 3. 验证模式（检查分类树完整性）
php bin/magento folixcode:restructure:categories --verify
```

### 5.2 核心文件
```
app/code/FolixCode/ProductSync/Console/Command/RestructureCategoriesCommand.php
```

### 5.3 关键方法
```php
// 1. 分析分类归属
analyzeCategory归属(categoryId): array

// 2. 创建层级分类
createLevel3Categories(): array
createLevel4Categories(level3Ids): array
createLevel5Categories(level4Ids): array

// 3. 移动真实分类
moveRealCategoriesToLevel6(level5Ids): array

// 4. 设置可见性
setCategoryVisibility(categoryId, level): void
```

---

## ️ 六、风险控制

### 6.1 风险点
| 风险 | 影响 | 应对措施 |
|------|------|---------|
| 分类移动失败 | 部分分类未正确归类 | 记录日志，支持回滚 |
| URL Key 冲突 | 分类 URL 重复 | 自动生成唯一 URL Key |
| Path 字段错误 | 分类树结构损坏 | 使用 save() 而非 move()，确保 path 正确 |
| 索引重建失败 | 前端分类显示异常 | 重建前先验证分类树完整性 |

### 6.2 回滚方案
```bash
# 1. 恢复数据库备份
mysql -u root -p database < backup.sql

# 2. 或使用命令回滚（如果支持）
php bin/magento folixcode:restructure:categories --rollback
```

### 6.3 验证检查点
```php
// 执行后验证：
// 1. 所有分类的 parent_id 是否正确
// 2. 所有分类的 path 字段是否无循环引用
// 3. Level 3-6 的层级是否正确
// 4. 索引重建是否成功
// 5. 前端菜单是否正常显示
```

---

##  七、预期效果

### 前端菜单结构
```
Game Cards & Keys ▼
  ├── Gift Cards ▼
  │    ├── Amazon Cards
  │    ├── Apple Cards
  │    └── Google Play
  ├── Game Cards ▼
  │    ├── Steam
  │    ├── PlayStation
  │    └── Xbox
  └── Membership ▼
       ├── Netflix
       └── Spotify

Game Top-Up ▼
  ├── Mobile Games ▼
  │    ├── PUBG Mobile
  │    ├── Genshin Impact
  │    └── Free Fire
  ├── PC Games ▼
  │    ├── Steam Wallet
  │    └── Epic Games
  └── Console Games ▼
       ├── PSN
       └── Xbox Live

Game Services ▼
  ├── Accelerator
  └── Companion
```

### 性能提升
- ✅ 菜单加载速度提升（从 859 个节点减少到约 50 个节点）
- ✅ 用户体验改善（清晰的分类结构）
- ✅ SEO 优化（语义化的 URL 结构）

---

## 🚀 八、执行计划

### 第一阶段：数据准备（1-2天）
- [ ] 运行分析脚本，生成分类归属映射表
- [ ] 人工复核未分类项（约 100 个）
- [ ] 确认分组规则

### 第二阶段：代码开发（2-3天）
- [ ] 编写分类重组 CLI 命令
- [ ] 实现 dry-run 预览功能
- [ ] 添加验证和回滚机制

### 第三阶段：测试验证（1-2天）
- [ ] 在测试环境执行 dry-run
- [ ] 验证分类树完整性
- [ ] 测试前端菜单显示

### 第四阶段：生产执行（1天）
- [ ] 备份数据库
- [ ] 执行分类重组
- [ ] 重建索引和缓存
- [ ] 验证生产环境

---

## 📝 九、需要确认的事项

### 9.1 分组规则
- [ ] Level 4 的分组名称是否合适？
- [ ] Level 5 的品牌提取规则是否准确？
- [ ] "Others" 分组是否按首字母分？

### 9.2 可见性设置
- [ ] Level 3-5 是否都在菜单显示？
- [ ] Level 6 是否全部隐藏？

### 9.3 异常处理
- [ ] 无法分类的项如何处理？（归入 Others）
- [ ] 空分类是否隐藏或删除？

---

## ✅ 十、方案确认

**请确认以下事项后，我将开始编写代码：**

1. ✅ 6层架构是否符合您的需求？
2. ✅ Level 4 的分组规则是否合理？
3. ✅ Level 5 按品牌/平台分组是否可行？
4. ✅ 是否有其他特殊要求？

确认后，我将：
1. 先编写分析脚本，生成详细的分类归属报告
2. 您审阅报告并确认分组结果
3. 最后编写完整的重组 CLI 命令并执行