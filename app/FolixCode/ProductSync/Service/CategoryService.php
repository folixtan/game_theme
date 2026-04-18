<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;

use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\Store;

/**
 * 分类管理服务 - 继承 Magento 官方 CategoryProcessor
 * 
 * 优势：
 * 1. 复用官方的分类处理逻辑（经过充分测试）
 * 2. 减少代码量，降低维护成本
 * 3. 自动跟随 Magento 升级
 */
class CategoryService extends CategoryProcessor
{
    private ?LoggerInterface $logger = null;
    private bool $isInitialized = false;

    const ROOT_PATH ='Default Category';

    private $currentId = null;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($categoryColFactory, $categoryFactory);
        $this->logger = $logger;
    }

    /**
     * 重写 initCategories，改为懒加载
     * 避免在构造函数中就加载所有分类
     *
     * @return $this
     */
    protected function initCategories()
    {
        if ($this->isInitialized) {
            return $this;
        }

        try {
            parent::initCategories();
            $this->isInitialized = true;
            
            // ✅ 安全检查：确保 logger 已初始化
            if ($this->logger) {
                $this->logger->info('Category cache initialized', [
                    'total_categories' => count($this->categoriesCache),
                    'path_mappings' => count($this->categories)
                ]);
            }
        } catch (\Exception $e) {
            // ✅ 安全检查：确保 logger 已初始化
            if ($this->logger) {
                $this->logger->error('Failed to initialize category cache', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this;
    }

    /**
     * array $categoryData [
     *    'id' => '1',
     *     'name' => 'Games', or games/coins
     * ]
     */
    public function buildCategory(array $categoryData) {
         $path  = $this->buildCategoryPath($categoryData);
         $this->currentId = $categoryData['id'] ?:null ;
         $categoryId  = $this->upsertCategoryByPath($path);
    }

    
    /**
     * Creates a category.
     *
     * @param string $name
     * @param int $parentId
     * @return int
     */
    protected function createCategory($name, $parentId)
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryFactory->create();
        if (!($parentCategory = $this->getCategoryById($parentId))) {
            $parentCategory = $this->categoryFactory->create()->load($parentId);
        }
        $category->setPath($parentCategory->getPath());
        $category->setParentId($parentId);
        if($this->currentId) $category->setId($this->currentId);

        $category->setUrlKey($this->generateUrlKey($name));

        $category->setName($this->unquoteDelimiter($name));
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setAttributeSetId($category->getDefaultAttributeSetId());
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category->save();
        $this->categoriesCache[$category->getId()] = $category;
        return $category->getId();
    }
    /**
     * 根据名称路径查找或创建分类（公开方法）
     * 支持多级路径，如 "Games/Coins/Premium"
     *
     * @param string $categoryPath 分类路径，如 "Games/Coins"
     * @return int 分类ID
     */
    public function upsertCategoryByPath(string $categoryPath): int
    {
        $this->initCategories();
        
        try {
            // 调用父类的 protected 方法
            return $this->upsertCategory($categoryPath);
        } catch (\Exception $e) {
            // ✅ 安全检查：确保 logger 已初始化
            if ($this->logger) {
                $this->logger->error('Failed to upsert category', [
                    'path' => $categoryPath,
                    'error' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    /**
     * 根据 ID 获取分类（公开方法）
     *
     * @param int|string $categoryId
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCategoryById($categoryId): ?\Magento\Catalog\Model\Category
    {
        $this->initCategories();
        return parent::getCategoryById($categoryId);
    }


    
    /**
     * 构建分类路径
     *
     * @param array $categoryData
     * @return string
     */
    private function buildCategoryPath(array $categoryData): string
    {
        // 如果有 parent_path，使用它 + 当前分类名
        if (!empty($categoryData['parent_path'])) {
            $parentPath = rtrim($categoryData['parent_path'], '/');
            $categoryName = $categoryData['name'];
            return $parentPath . '/' . $categoryName;
        }

        // 否则直接使用分类名作为单层路径
        return static::ROOT_PATH.'/'.$categoryData['name'];
    }

    /**
     * 生成 URL Key
     * 
     * 如果 url_key 不存在，使用 name + 随机字符串生成
     * 随机字符串长度 3-5 位，降低重复概率
     *
     * @param string $name 分类名称
     * @return string
     */
    private function generateUrlKey(string $name): string
    {
        
        // 1. 去掉所有中文、 emoji、特殊符号
        $str = preg_replace('/[\x{4e00}-\x{9fa5}]/u', '', $name);

        // 2. 只保留英文数字，转小写
        $str = strtolower($str);
        $str = preg_replace('/[^a-z]+/', '-', $str);
        $str = trim($str, '-');

       
       return 'category-' .$str. substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
      
    }
 
    
    /**
     * Returns ID of category by string path creating nonexistent ones.
     *
     * @param string $categoryPath
     * @return int
     */
    protected function upsertCategory($categoryPath)
    {
        /** @var string $index */
        $index = $categoryPath !== null ? $this->standardizeString($categoryPath) : '';

        if (!isset($this->categories[$index])) {
            $pathParts = preg_split('~(?<!\\\)' . preg_quote(self::DELIMITER_CATEGORY, '~') . '~', $categoryPath);
            $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
            $path = '';

            foreach ($pathParts as $pathPart) {
                $path .= $this->standardizeString($pathPart);
                if (!isset($this->categories[$path])) {
                    $this->categories[$path] = $this->createCategory($pathPart, $parentId);
                }
                $parentId = $this->categories[$path];
                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $this->categories[$index];
    }

    
    /**
     * Standardize a string.
     * For now it performs only a lowercase action, this method is here to include more complex checks in the future
     * if needed.
     *
     * @param string $string
     * @return string
     */
    private function standardizeString($string)
    {
        return mb_strtolower($string);
    }

    /**
     * Quoting delimiter character in string.
     *
     * @param string $string
     * @return string
     */
    private function quoteDelimiter($string)
    {
        return str_replace(self::DELIMITER_CATEGORY, '\\' . self::DELIMITER_CATEGORY, $string);
    }
    
    /**
     * Remove quoting delimiter in string.
     *
     * @param string $string
     * @return string
     */
    private function unquoteDelimiter($string)
    {
        return str_replace('\\' . self::DELIMITER_CATEGORY, self::DELIMITER_CATEGORY, $string);
    }

    /**
     * 清除缓存（用于测试或强制刷新）
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->categories = [];
        $this->categoriesCache = [];
        $this->failedCategories = [];
        $this->isInitialized = false;
    }
}