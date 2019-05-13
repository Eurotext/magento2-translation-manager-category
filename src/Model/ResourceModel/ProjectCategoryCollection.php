<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerCategory\Model\ResourceModel;

use Eurotext\TranslationManagerCategory\Model\ProjectCategory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class ProjectCategoryCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ProjectCategory::class, ProjectCategoryResource::class);
    }
}
