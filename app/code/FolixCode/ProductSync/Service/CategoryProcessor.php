<?php
declare(strict_types=1);

namespace FolixCode\ProductSync\Service;
use Magento\Catalog\Model\CategoryRepository;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
/**
 * @api
 * @since 100.0.2
 */
class CategoryProcessor
{
    /**
     * Delimiter in category path.
     */
    public const DELIMITER_CATEGORY = '/';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Categories id to object cache.
     *
     * @var array
     */
    protected $categoriesCache = [];

    /**
     * Instance of catalog category factory.
     *
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Failed categories during creation
     *
     * @var array
     * @since 100.1.0
     */
    protected $failedCategories = [];

    /** @var \Magento\Catalog\Model\CategoryRepository   */
    protected $categoryRepository;

    private $storeManager;

    const ROOT_PATH ='Default Category';

    private $resourceConnection;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        CategoryRepository $categoryRepository
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->initCategories();
    }

    /**
     * Initialize categories
     *
     * @return $this
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $collection = $this->categoryColFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
            $collection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            foreach ($collection as $category) {
                $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                $pathSize = count($structure);

                $this->categoriesCache[$category->getId()] = $category;
                if ($pathSize > 1) {
                    $path = [];
                    for ($i = 1; $i < $pathSize; $i++) {
                        $name = $collection->getItemById((int)$structure[$i])->getName();
                        $path[] = $name !== null ? $this->quoteDelimiter($name) : '';
                    }
                    /** @var string $index */
                    $index = $this->standardizeString(
                        implode(self::DELIMITER_CATEGORY, $path)
                    );
                    $this->categories[$index] = $category->getId();
                }
            }
        }
        return $this;
    }

    /**
     * Creates a category.
     *
     * @param string $name
     * @param int $parentId
     * @return int
     */
    protected function createCategory($name, $parentId,array $attributes = [])
    {
        $this->storeManager->setCurrentStore(0);
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryFactory->create();
        if (!($parentCategory = $this->getCategoryById($parentId))) {
            $parentCategory = $this->categoryFactory->create()->load($parentId);
        }
       // var_dump($parentCategory->getPath(),$parentCategory->getId());exit;
        $category->setPath($parentCategory->getPath());
        $category->setParentId($parentId);
        $category->setName($this->unquoteDelimiter($name));
        $category->setIsActive(true);
        $url_key = isset($attributes['url_key']) ?: $this->generateUrlKey($name);
        $category->setUrlKey($url_key);
        if(isset($attributes['url_key'])) {
         
            unset($attributes['url_key']);
       }

        if(isset($attributes['id']) && is_numeric($attributes['id'])) {
             $category->setData('vendor_id',$attributes['id']);
        }

       

       // var_dump($category->getDefaultAttributeSetId());exit;

        $category->setIncludeInMenu(true);
        $category->setAttributeSetId($category->getDefaultAttributeSetId());
        $category->setStoreId(Store::DEFAULT_STORE_ID);
        $category = $this->categoryRepository->save($category);
        //更改path
    //   var_dump($category->getPath());exit;
        $this->resourceConnection->getConnection()->update(
            $this->resourceConnection->getTableName('catalog_category_entity'),
            ['path' => $category->getPath().self::DELIMITER_CATEGORY.$category->getId()],
            ['entity_id = ?' => $category->getId()]
        );;
        $this->categoriesCache[$category->getId()] = $category;
        return $category->getId();
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
     * @param array $attributes
     * @return int
     */
    public function upsertCategory($categoryPath,array $attributes = [])
    {
        //$this->initCategories();

        /** @var string $index */
        $index = $categoryPath !== null ? $this->standardizeString($categoryPath) : '';

        if (!isset($this->categories[$index])) {
            $pathParts = preg_split('~(?<!\\\)' . preg_quote(self::DELIMITER_CATEGORY, '~') . '~', $categoryPath);
            // $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
              $parentId = 2;
            $path = '';

            foreach ($pathParts as $pathPart) {
                $path .= $this->standardizeString($pathPart);
                if (!isset($this->categories[$path])) {
                 
                    $this->categories[$path] = $this->createCategory($pathPart, $parentId,$attributes[$pathPart]);
                }
                $parentId = $this->categories[$path];
                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $this->categories[$index];
    }

    /**
     * Returns IDs of categories by string path creating nonexistent ones.
     *
     * @param string $categoriesString
     * @param string $categoriesSeparator
     * @return array
     */
    public function upsertCategories($categoriesString, $categoriesSeparator)
    {
        $categoriesIds = [];
        $categories = $categoriesString !== null ? explode($categoriesSeparator, $categoriesString) : [];

        foreach ($categories as $category) {
            try {
                $categoriesIds[] = $this->upsertCategory($category);
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                $this->addFailedCategory($category, $e);
            }
        }

        return $categoriesIds;
    }

    /**
     * Add failed category
     *
     * @param string $category
     * @param \Magento\Framework\Exception\AlreadyExistsException $exception
     *
     * @return $this
     */
    private function addFailedCategory($category, $exception)
    {
        $this->failedCategories[] =
            [
                'category' => $category,
                'exception' => $exception,
            ];
        return $this;
    }

    /**
     * Return failed categories
     *
     * @return array
     * @since 100.1.0
     */
    public function getFailedCategories()
    {
        return $this->failedCategories;
    }

    /**
     * Resets failed categories' array
     *
     * @return $this
     * @since 100.2.0
     */
    public function clearFailedCategories()
    {
        $this->failedCategories = [];
        return $this;
    }

    /**
     * Get category by Id
     *
     * @param int $categoryId
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCategoryById($categoryId)
    {
        return $this->categoriesCache[$categoryId] ?? null;
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
     * 构建分类路径
     *
     * @param array $categoryData
     * @return string
     */
    public function buildCategoryPath(array $categoryData): string
    {
        // 如果有 parent_path，使用它 + 当前分类名
        if (!empty($categoryData['parent_path'])) {
            $parentPath = rtrim($categoryData['parent_path'], '/');
            $categoryName = $categoryData['name'] ?? 'Unknown';
            return $parentPath . '/' . $categoryName;
        }

        // 否则直接使用分类名作为单层路径
        return self::ROOT_PATH.'/'.$categoryData['name'] ?? 'Unknown';
    }

    

    public function cleanCache(): void
    {
        $this->categoriesCache = [];
    }
}
