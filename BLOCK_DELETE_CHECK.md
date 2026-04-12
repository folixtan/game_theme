# Block 删除检查

## 检查结果

### 1. skip_to_content
- **位置**：`header.panel` Container 中
- **路径**：`module-theme/view/frontend/layout/default.xml:39`
- **功能**：无障碍功能，跳转到主要内容
- **删除**：✅ 可以删除（demo 中不需要）

### 2. currency
- **位置**：`store.settings` Block 中
- **路径**：`module-theme/view/frontend/layout/default.xml:95`
- **功能**：货币切换
- **删除**：✅ 可以删除（demo 中不需要）

### 3. report.bugs
- **位置**：`footer` Container 中
- **路径**：`module-theme/view/frontend/layout/default.xml:132`
- **功能**：报告错误
- **删除**：✅ 可以删除（Luma 主题也删除了）

### 4. store_switcher（❌ 不应该删除）
- **位置**：`footer` Container 中
- **路径**：`module-theme/view/frontend/layout/default.xml:125`
- **功能**：店铺切换
- **Luma 处理**：使用 `<move>` 移动位置，不删除
- **删除**：❌ 不应该删除（只是移动位置）

## 正确的删除方式

### ❌ 错误做法
```xml
<referenceBlock name="store_switcher" remove="true" />
```

### ✅ 正确做法
```xml
<!-- 不删除，保留在 footer 中 -->
<!-- 或者如果需要移动位置： -->
<move element="store_switcher" destination="footer" after="footer_links" />
```

## 总结

删除 Block 之前应该：
1. ✅ 检查 Block 是否存在（grep 搜索）
2. ✅ 检查 Block 在哪个 Container 中
3. ✅ 检查 Luma 主题如何处理
4. ✅ 确认 demo 是否需要这个功能
5. ✅ 如果只是移动位置，使用 `<move>` 而不是 `remove="true"`
