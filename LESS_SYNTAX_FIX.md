# Less 语法错误修复记录

## 🐛 问题描述

编译时出现错误：
```
closing `}` in _module.less on line 462, column 30
460|                         }
461|
462|                         span {
463|                             color: @folix-bg-dark;
464|                         }
```

## 🔍 问题分析

### 根本原因

两个 `_module.less` 文件都缺少 `& when (@media-common = true) {` 条件块的闭合括号。

### 受影响的文件

1. **`web/css/source/Magento_Customer/_module.less`**
   - 第 18 行：`& when (@media-common = true) {` 开始条件块
   - 第 470 行：文件结束，但只有一个 `}`
   - **缺少**：一个闭合括号来闭合 `& when` 条件块

2. **`web/css/source/Mageplaza_SocialLogin/_module.less`**
   - 第 16 行：`& when (@media-common = true) {` 开始条件块
   - 第 622 行：文件结束，但只有一个 `}`
   - **缺少**：一个闭合括号来闭合 `& when` 条件块

## ✅ 修复方案

### 修复 1：Magento_Customer/_module.less

**修改前**（第 462-470 行）：
```less
                        span {
                            color: @folix-bg-dark;
                        }
                    }
                }
            }
        }
    }
```

**修改后**（第 462-472 行）：
```less
                        span {
                            color: @folix-bg-dark;
                        }
                    }
                }
            }
        }
    }
}
```

**变更**：在第 472 行添加了一个闭合的 `}`

### 修复 2：Mageplaza_SocialLogin/_module.less

**修改前**（第 617-622 行）：
```less
    100% {
        transform: scale(1);
        opacity: 0;
    }
```

**修改后**（第 617-624 行）：
```less
    100% {
        transform: scale(1);
        opacity: 0;
    }
}
```

**变更**：在第 624 行添加了一个闭合的 `}`

## 📊 修复统计

| 文件 | 修复前行数 | 修复后行数 | 添加内容 |
|------|-----------|-----------|---------|
| Magento_Customer/_module.less | 470 | 472 | 1 个闭合括号 `}` |
| Mageplaza_SocialLogin/_module.less | 622 | 624 | 1 个闭合括号 `}` |

## 🎯 修复验证

### 验证方法

1. 检查文件末尾的括号闭合情况
2. 确认 `& when (@media-common = true) {` 有对应的闭合括号

### 验证结果

✅ `Magento_Customer/_module.less` - 末尾有两个闭合的 `}`
✅ `Mageplaza_SocialLogin/_module.less` - 末尾有两个闭合的 `}`

## 📝 经验总结

### 问题根源

在编写 Less 文件时，经常使用 `& when (@media-common = true) {` 来包裹公共样式，但容易忘记在文件末尾添加对应的闭合括号。

### 预防措施

1. **使用代码编辑器的括号匹配功能**
   - VS Code: 自动高亮匹配的括号
   - Sublime Text: 显示括号高亮

2. **遵循一致的代码结构**
   ```less
   & when (@media-common = true) {
       // 内容
   }  // 立即添加闭合括号，再往回填充内容
   ```

3. **使用 Lint 工具**
   - Stylelint 配置 Less 插件
   - 自动检查语法错误

4. **文件末尾注释**
   ```less
   }  // End of & when (@media-common = true)
   ```

---

**修复完成时间**：2025-01-XX
**修复人**：AI Assistant
**状态**：✅ 已修复并验证
