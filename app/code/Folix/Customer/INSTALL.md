# Folix_Customer 模块 - 快速安装指南

## 📋 功能概述

本模块为游戏充值平台定制Magento账户页面，包含：
1. ✅ Header精简（移除导航，只保留Logo）
2. ✅ Dashboard卡片式布局（基于v3.html设计）
3. ✅ **高性能统计系统**（用户维度统计表 + Observer + Cron）

## 🚀 安装步骤

### 1. 启用模块

```bash
cd /var/www/html/game/game

# 启用模块
bin/magento module:enable Folix_Customer

# 升级数据库（会创建folix_customer_stats表）
bin/magento setup:upgrade

# 编译DI（生产模式）
bin/magento setup:di:compile

# 清理缓存
bin/magento cache:clean

# 部署静态内容（生产模式）
bin/magento setup:static-content:deploy -f
```

### 2. 验证安装

```bash
# 检查模块是否启用
bin/magento module:status Folix_Customer

# 检查数据库表是否创建
mysql -u root -p -e "USE magento; SHOW TABLES LIKE 'folix_customer_stats';"

# 检查Cron任务是否配置
bin/magento cron:run --group=default
```

## 📊 统计数据系统说明

### 架构设计

```
订单完成事件
    ↓
Observer (实时更新)
    ↓
统计表 (主键查询 < 1ms)
    ↓
Dashboard显示
```

### 数据库表

**表名**: `folix_customer_stats`

**字段**:
- `customer_id` (PK, FK → customer_entity)
- `total_orders` (总订单数)
- `total_spent` (总消费金额)
- `active_keys` (活跃卡密数)
- `last_order_at` (最后订单时间)
- `updated_at` (更新时间)

### 数据更新机制

#### 1. **实时更新（Observer）**
- 监听事件：`sales_order_save_after`
- 触发条件：订单状态变为 `complete`
- 更新方式：重新计算COUNT/SUM并更新统计表

#### 2. **定时校准（Cron）**
- 执行时间：每天凌晨2:00
- 作用：重新计算所有用户数据
- 防止：Observer遗漏导致的数据不一致

### 性能优化

| 指标 | 优化前 | 优化后 |
|------|--------|--------|
| 查询方式 | COUNT/SUM全表扫描 | 主键查询 |
| 响应时间 | 50-200ms | < 1ms |
| 数据库压力 | 高 | 极低 |

## 🔧 配置说明

### Cron任务配置

文件：`etc/crontab.xml`

```xml
<job name="folix_customer_recalculate_stats" 
     instance="Folix\Customer\Cron\RecalculateCustomerStats" 
     method="execute">
    <schedule>0 2 * * *</schedule> <!-- 每天凌晨2点 -->
</job>
```

**自定义执行时间**：
- 每6小时：`0 */6 * * *`
- 每周日凌晨3点：`0 3 * * 0`

### 事件监听配置

文件：`etc/events.xml`

```xml
<event name="sales_order_save_after">
    <observer name="folix_customer_update_stats" 
              instance="Folix\Customer\Observer\UpdateCustomerStats"/>
</event>
```

**监听其他事件**：
- 订单取消：`sales_order_cancel_after`
- 订单退款：`sales_order_refund_after`

## 📝 开发指南

### 添加新的统计字段

1. **更新db_schema.xml**：
```xml
<column xsi:type="int" name="new_stat_field" 
        unsigned="true" nullable="false" default="0" 
        comment="New Stat Field"/>
```

2. **更新ResourceModel**：
```php
// 在recalculateStats()方法中添加计算逻辑
$newValue = ...;
$stats['new_stat_field'] = $newValue;
```

3. **更新Block**：
```php
public function getNewStat(): int
{
    $stats = $this->getStats();
    return (int)($stats['new_stat_field'] ?? 0);
}
```

4. **更新模板**：
```php
<div class="stat-number"><?= $block->escapeHtml($block->getNewStat()) ?></div>
```

### 手动触发统计更新

```php
// 在任何地方调用
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$repository = $objectManager->get(\Folix\Customer\Api\CustomerStatsRepositoryInterface::class);
$repository->recalculate($customerId);
```

## 🐛 故障排查

### 问题1：统计数据为0

**可能原因**：
- 订单未完成（状态不是`complete`）
- Observer未执行
- 数据库表未创建

**排查步骤**：
```bash
# 1. 检查订单状态
mysql -e "SELECT entity_id, status FROM sales_order WHERE customer_id = YOUR_CUSTOMER_ID LIMIT 5;"

# 2. 检查Observer日志
tail -f var/log/system.log | grep "Customer stats updated"

# 3. 手动触发更新
bin/magento dev:query-log:enable
# 然后访问Dashboard页面
```

### 问题2：Cron任务未执行

**可能原因**：
- Magento Cron未配置
- Cron组配置错误

**排查步骤**：
```bash
# 1. 检查Cron配置
crontab -l

# 2. 手动执行Cron
bin/magento cron:run --group=default

# 3. 检查Cron日志
tail -f var/log/cron.log
```

### 问题3：统计表数据不一致

**解决方案**：
```bash
# 手动执行校准任务
php bin/magento cron:run --group=default

# 或直接在数据库中重新计算
mysql -e "
UPDATE folix_customer_stats fcs
INNER JOIN (
    SELECT customer_id, COUNT(*) as total_orders, SUM(grand_total) as total_spent
    FROM sales_order
    WHERE status IN ('complete', 'processing', 'pending')
    GROUP BY customer_id
) so ON fcs.customer_id = so.customer_id
SET fcs.total_orders = so.total_orders,
    fcs.total_spent = so.total_spent;
"
```

## 📈 监控建议

### 1. 数据库表大小

```sql
-- 检查统计表大小
SELECT COUNT(*) as total_customers, 
       SUM(total_orders) as total_orders_all,
       SUM(total_spent) as total_revenue
FROM folix_customer_stats;
```

### 2. 性能监控

```bash
# 检查慢查询
tail -f var/log/db-debug.log | grep "slow_query"

# 检查Block渲染时间
# 在Stats Block中添加：
$this->_logger->debug('Stats block render time: ' . (microtime(true) - $startTime));
```

### 3. Cron执行监控

```bash
# 检查Cron执行历史
mysql -e "
SELECT job_code, status, created_at, executed_at, finished_at
FROM cron_schedule
WHERE job_code LIKE 'folix_customer%'
ORDER BY created_at DESC
LIMIT 10;
"
```

## 🎯 下一步优化建议

1. **添加Redis缓存**（可选）：
   - 缓存统计表查询结果
   - 进一步降低数据库查询

2. **添加API接口**：
   - REST API获取用户统计
   - 移动端App使用

3. **统计图表**：
   - 消费趋势折线图
   - 订单状态饼图

4. **通知系统**：
   - 订单完成后推送通知
   - 统计异常告警

## 📞 技术支持

如有问题，请检查：
1. `var/log/system.log` - 系统日志
2. `var/log/cron.log` - Cron日志
3. `var/log/db-debug.log` - 数据库查询日志

---

**模块版本**: 1.1.0  
**最后更新**: 2024-04-21  
**兼容版本**: Magento 2.4+, PHP 8.1+
