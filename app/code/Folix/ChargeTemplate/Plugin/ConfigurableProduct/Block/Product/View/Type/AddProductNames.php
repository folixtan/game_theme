<?php
/**
 * Copyright © Folix, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Folix\ChargeTemplate\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for adding product names to jsonConfig.
 */
class AddProductNames
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param Json $jsonSerializer
     */
    public function __construct(
        Json $jsonSerializer
    ) {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Add product names to jsonConfig.
     *
     * @param Subject $configurable
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Subject $configurable, string $result): string
    {
        $jsonConfig = $this->jsonSerializer->unserialize($result);

        $jsonConfig['productNames'] = $this->getProductNames($configurable);

        return $this->jsonSerializer->serialize($jsonConfig);
    }

    /**
     * Get product names by product ID.
     *
     * @param Subject $configurable
     * @return array
     */
    private function getProductNames(Subject $configurable): array
    {
        $names = [];
        foreach ($configurable->getAllowProducts() as $product) {
            $names[$product->getId()] = $product->getName();
        }

        return $names;
    }
}
