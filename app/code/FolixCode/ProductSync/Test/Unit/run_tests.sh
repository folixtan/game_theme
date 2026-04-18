#!/bin/bash

# CategoryImportConsumer 单元测试运行脚本

echo "=========================================="
echo "CategoryImportConsumer 单元测试"
echo "=========================================="
echo ""

# 切换到项目根目录
cd /var/www/html/game/game

# 运行测试
echo "正在运行测试..."
echo ""

vendor/bin/phpunit \
    app/code/FolixCode/ProductSync/Test/Unit/Model/MessageQueue/Consumer/CategoryImportConsumerTest.php \
    --colors=always \
    --testdox

echo ""
echo "=========================================="
echo "测试完成"
echo "=========================================="
