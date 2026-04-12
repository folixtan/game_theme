#!/bin/bash
# Magento Theme Deployment Script
# Folix Game Theme - Mobile Header Fix

echo "========================================="
echo "Folix Game Theme - Deployment Script"
echo "Mobile Navigation & Search Fix"
echo "========================================="
echo ""

# 清除缓存
echo "Step 1: Clearing cache..."
php bin/magento cache:clean
php bin/magento cache:flush

# 清除静态文件
echo ""
echo "Step 2: Clearing static files..."
rm -rf pub/static/frontend/Folix/*
rm -rf var/view_preprocessed/pub/static/frontend/Folix/*

# 清除 RequireJS 缓存
echo ""
echo "Step 3: Clearing RequireJS cache..."
rm -rf pub/static/_requirejs/*
rm -rf var/view_preprocessed/pub/static/_requirejs/*

# 重新部署静态文件
echo ""
echo "Step 4: Deploying static content..."
php bin/magento setup:static-content:deploy --theme=Folix/game-theme -f

# 清除缓存
echo ""
echo "Step 5: Clearing cache again..."
php bin/magento cache:clean
php bin/magento cache:flush

echo ""
echo "========================================="
echo "Deployment completed!"
echo "========================================="
echo ""
echo "✓ Layout structure fixed (navigation.sections stays in place)"
echo "✓ Mobile navigation sidebar enabled"
echo "✓ Mobile search interaction added (click to expand)"
echo ""
echo "Please test in mobile view:"
echo "1. Open Chrome DevTools (F12)"
echo "2. Click 'Toggle device toolbar' (Ctrl+Shift+M)"
echo "3. Select 'iPhone 12 Pro' or resize to 375px width"
echo "4. Hard refresh the page (Ctrl+Shift+R)"
echo ""
echo "Expected results:"
echo "✓ Hamburger menu should toggle navigation sidebar"
echo "✓ Navigation sidebar should contain menu items"
echo "✓ Search icon should expand search box when clicked"
echo "✓ Logo should be centered"
echo "✓ Cart and login buttons should be visible"
echo ""
