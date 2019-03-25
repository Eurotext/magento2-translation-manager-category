<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Eurotext\TranslationManagerCategory\ScopeConfig\CategoryScopeConfigReader;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;

class CategoryItemGetMapper
{
    /**
     * @var CategoryScopeConfigReader
     */
    private $categoryScopeConfig;

    /**
     * @var CategoryUrlPathGenerator
     */
    private $categoryUrlPathGenerator;

    public function __construct(
        CategoryScopeConfigReader $categoryScopeConfig,
        CategoryUrlPathGenerator $categoryUrlPathGenerator
    ) {
        $this->categoryScopeConfig = $categoryScopeConfig;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
    }

    public function map(ItemGetResponse $itemGetResponse, CategoryInterface $category): CategoryInterface
    {
        $item = $itemGetResponse->getItemData();

        $category->setName($item->getDataValue('name'));

        $attributesEnabled = $this->categoryScopeConfig->getAttributesEnabled();
        foreach ($attributesEnabled as $attributeCode) {
            $customAttribute = $category->getCustomAttribute($attributeCode);

            if ($customAttribute === null) {
                // missing custom attribute, maybe due to not being set at the global category,
                // or the attribute has been removed but still is configured in the system.xml
                continue;
            }

            $newValue = $item->getDataValue($attributeCode);

            if (empty($newValue)) {
                // If there is no translated value do not set it
                continue;
            }

            $customAttribute->setValue($newValue);
        }

        /** @var $category Category */
        $this->triggerAutomaticUrlGenerate($category);

        return $category;
    }

    private function triggerAutomaticUrlGenerate(Category $category)
    {
        // Unset url-key so it gets automatically generated during save
        // Unset Customer Attribute, if data is empty custom attribute is fallback value
        $this->setDataAndCustomAttribute($category, 'url_key');
        $urlKey = $this->categoryUrlPathGenerator->getUrlKey($category);
        $this->setDataAndCustomAttribute($category, 'url_key', $urlKey);

        // Unset url-path as well otherwise it does not work
        // Unset Customer Attribute, if data is empty custom attribute is fallback value
        $this->setDataAndCustomAttribute($category, 'url_path');
        $urlPath = $this->categoryUrlPathGenerator->getUrlPath($category);
        $this->setDataAndCustomAttribute($category, 'url_path', $urlPath);
    }

    private function setDataAndCustomAttribute(Category $category, string $key, string $value = null)
    {
        $category->setData($key);

        $urlKeyAttribute = $category->getCustomAttribute($key);
        if ($urlKeyAttribute !== null) {
            $urlKeyAttribute->setValue($value);
        }
    }
}