#!/bin/bash

# ============================================================================
# Folix SimpleCheckout - 部署脚本
# ============================================================================

echo "=========================================="
echo "Folix SimpleCheckout Module Deployment"
echo "=========================================="
echo ""

# 进入Magento根目录
cd /var/www/html/game/game

# 1. 启用模块
echo "[1/5] Enabling module..."
php bin/magento module:enable Folix_SimpleCheckout

# 2. 升级数据库
echo "[2/5] Upgrading database..."
php bin/magento setup:upgrade

# 3. 清理缓存
echo "[3/5] Cleaning cache..."
php bin/magento cache:clean

# 4. 编译依赖(生产模式需要)
echo "[4/5] Compiling dependencies..."
php bin/magento setup:di:compile

# 5. 部署静态资源
echo "[5/5] Deploying static content..."
php bin/magento setup:static-content:deploy -f zh_Hans_CN en_US

echo ""
echo "=========================================="
echo "Deployment completed successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit: http://your-domain.com/checkout"
echo "2. Test login functionality (PC & Mobile)"
echo "3. Test place order flow"
echo "4. Check responsive design"
echo ""
