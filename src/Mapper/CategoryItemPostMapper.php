<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerCategory\Mapper;

use Eurotext\RestApiClient\Request\Data\Project\ItemData;
use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Eurotext\TranslationManagerCategory\ScopeConfig\CategoryScopeConfigReader;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */
class CategoryItemPostMapper
{
    const ENTITY_TYPE = 'specialized-text'; // using specialized-text since there is no distinct type for category

    /**
     * @var ScopeConfigReaderInterface
     */
    private $scopeConfig;

    /**
     * @var CategoryScopeConfigReader
     */
    private $categoryScopeConfig;

    public function __construct(
        ScopeConfigReaderInterface $scopeConfigReader,
        CategoryScopeConfigReader $categoryScopeConfig
    ) {
        $this->scopeConfig = $scopeConfigReader;
        $this->categoryScopeConfig = $categoryScopeConfig;
    }

    public function map(CategoryInterface $category, ProjectInterface $project): ItemPostRequest
    {
        $languageSrc = $this->scopeConfig->getLocaleForStore($project->getStoreviewSrc());
        $languageDest = $this->scopeConfig->getLocaleForStore($project->getStoreviewDst());

        $attributesEnabled = $this->categoryScopeConfig->getAttributesEnabled();

        $data = [
            'name' => $category->getName(),
        ];

        foreach ($attributesEnabled as $attributeCode) {
            $customAttribute = $category->getCustomAttribute($attributeCode);

            if ($customAttribute === null) {
                continue;
            }

            $data[$attributeCode] = $customAttribute->getValue();
        }

        $meta = [
            'item_id' => $category->getId(),
            'entity_id' => $category->getId(),
            'entity_type' => self::ENTITY_TYPE,
        ];

        $itemData = new ItemData($data, $meta);

        $itemRequest = new ItemPostRequest(
            $project->getExtId(),
            $languageSrc,
            $languageDest,
            self::ENTITY_TYPE,
            '',
            $itemData
        );

        return $itemRequest;
    }
}