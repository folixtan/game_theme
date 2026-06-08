<?php
/**
 * Copyright © Folix. All rights reserved.
 */

namespace Folix\ConfigurableProductSort\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;

/**
 * Plugin to sort configurable product attribute options by minimum final price (ascending)
 */
class ConfigurableAttributeDataPlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * After plugin for getAttributesData method
     * Sorts each attribute's options by their minimum product final price in ascending order
     *
     * @param ConfigurableAttributeData $subject
     * @param array $result
     * @return array
     */
    public function afterGetAttributesData(
        ConfigurableAttributeData $subject,
        array $result
    ): array {
        // Sort options for each attribute
        if (!empty($result['attributes']) && is_array($result['attributes'])) {
            foreach ($result['attributes'] as $attributeId => &$attributeData) {
                if (!empty($attributeData['options']) && is_array($attributeData['options'])) {
                    $attributeData['options'] = $this->sortOptionsByMinPrice($attributeData['options']);
                }
            }
        }

        return $result;
    }

    /**
     * Sort options by their minimum product final price
     *
     * @param array $options
     * @return array
     */
    private function sortOptionsByMinPrice(array $options): array
    {
        if (empty($options)) {
            return $options;
        }

        // Calculate minimum price for each option
        $optionPrices = [];
        foreach ($options as $key => $option) {
            $minPrice = $this->getMinimumPriceForOption($option['products'] ?? []);
            $optionPrices[$key] = $minPrice;
        }

        // Sort options by minimum price (ascending)
        asort($optionPrices);

        // Rebuild sorted options array
        $sortedOptions = [];
        foreach ($optionPrices as $key => $price) {
            $sortedOptions[] = $options[$key];
        }

        return $sortedOptions;
    }

    /**
     * Get minimum final price for an option's products
     *
     * @param array $productIds
     * @return float
     */
    private function getMinimumPriceForOption(array $productIds): float
    {
        if (empty($productIds)) {
            return PHP_FLOAT_MAX; // Options with no products go to the end
        }

        $minPrice = PHP_FLOAT_MAX;

        foreach ($productIds as $productId) {
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId);
                $finalPrice = (float) $product->getFinalPrice();
                
                if ($finalPrice < $minPrice) {
                    $minPrice = $finalPrice;
                }
            } catch (\Exception $e) {
                // If product cannot be loaded, skip it
                continue;
            }
        }

        // If no valid prices found, use max value to push to end
        return $minPrice === PHP_FLOAT_MAX ? PHP_FLOAT_MAX : $minPrice;
    }
}
