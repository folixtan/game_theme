#!/usr/bin/env python3
"""
搜索 Magento 2 头部 customer 菜单和登录模态框的实现
"""

from coze_coding_dev_sdk import SearchClient
from coze_coding_utils.runtime_ctx.context import new_context

ctx = new_context(method="search.magento.customer.modal")
client = SearchClient(ctx=ctx)

# 搜索 Magento 2 头部 customer 菜单和登录模态框
response = client.web_search_with_summary(
    query='Magento 2 customer dropdown menu header modal popup login customer.phtml header.phtml top.links authenticationPopup',
    count=10
)

if response.summary:
    print("=" * 80)
    print("AI SUMMARY:")
    print("=" * 80)
    print(response.summary)
    print()

if response.web_items:
    print("=" * 80)
    print(f"SEARCH RESULTS ({len(response.web_items)} items):")
    print("=" * 80)
    
    for i, item in enumerate(response.web_items, 1):
        print(f"\n{i}. {item.title}")
        print(f"   Source: {item.site_name}")
        print(f"   URL: {item.url}")
        print(f"   Snippet: {item.snippet[:300]}...")
