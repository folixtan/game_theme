# top.links 位置分析

## 原生 Magento 2 Theme 模块（module-theme）

### 第22-28行（在 body 直接子级）

```xml
<referenceBlock name="top.links">
    <block class="Magento\Theme\Block\Html\Header" name="header" as="header" before="-">
        <arguments>
            <argument name="show_part" xsi:type="string">welcome</argument>
        </arguments>
    </block>
</referenceBlock>
```

- 这是一个 `<referenceBlock>`，引用了一个已经存在的 `top.links`
- 给它添加一个子块 `header`（用于显示欢迎消息）

### 第50行（在 header.panel 容器中）

```xml
<container name="header.panel" label="Page Header Panel" htmlTag="div" htmlClass="panel header">
    <block class="Magento\Framework\View\Element\Template" name="skip_to_content" ... />
    <block class="Magento\Store\Block\Switcher" name="store_language" ... />
    <block class="Magento\Customer\Block\Account\Navigation" name="top.links">
        <arguments>
            <argument name="css_class" xsi:type="string">header links</argument>
        </arguments>
    </block>
</container>
```

- 这里**定义**了 `top.links` 这个 block
- 它在 `header.panel` 容器中

## 结论

✅ `top.links` 原本就在 `header.panel`（Top Bar）中，不需要移动！

## 理解

1. **定义位置**：`top.links` 在第50行定义，在 `header.panel` 容器中
2. **修改位置**：第22-28行的 `<referenceBlock>` 是给 `top.links` 添加子块
3. **Customer 模块**：通过 `<referenceBlock name="top.links">` 添加登录/注册链接

所以 `top.links` 一直在 Top Bar 中，不在 content 中！
