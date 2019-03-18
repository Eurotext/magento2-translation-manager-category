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

class CategoryItemGetMapper
{
    /**
     * @var CategoryScopeConfigReader
     */
    private $categoryScopeConfig;

    public function __construct(CategoryScopeConfigReader $categoryScopeConfig)
    {
        $this->categoryScopeConfig = $categoryScopeConfig;
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

        return $category;
    }
}