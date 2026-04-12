#!/usr/bin/env python3
"""
搜索 Magento 2 原生 customer 模块的 HTML 结构
"""

from coze_coding_dev_sdk import SearchClient
from coze_coding_utils.runtime_ctx.context import new_context
import json

ctx = new_context(method="search.magento.customer")
client = SearchClient(ctx=ctx)

# 搜索 Magento 2 customer 模块的 HTML 结构和模板
response = client.web_search_with_summary(
    query="Magento 2 customer module HTML structure login button create account header customer menu template phtml",
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
        print(f"   Snippet: {item.snippet[:200]}...")
