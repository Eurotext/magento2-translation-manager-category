<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerCategory\Model;

use Eurotext\TranslationManager\Model\AbstractProjectEntity;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Model\ResourceModel\ProjectCategoryCollection;
use Eurotext\TranslationManagerCategory\Model\ResourceModel\ProjectCategoryResource;

class ProjectCategory extends AbstractProjectEntity implements ProjectCategoryInterface
{
    const CACHE_TAG = 'eurotext_project_category';

    protected function _construct()
    {
        $this->_init(ProjectCategoryResource::class);
        $this->_setResourceModel(ProjectCategoryResource::class, ProjectCategoryCollection::class);
    }

    protected function getCacheTag(): string
    {
        return self::CACHE_TAG;
    }
}
