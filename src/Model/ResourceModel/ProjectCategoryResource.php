<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerCategory\Model\ResourceModel;

use Eurotext\TranslationManagerCategory\Setup\ProjectCategorySchema;

class ProjectCategoryResource extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(ProjectCategorySchema::TABLE_NAME, ProjectCategorySchema::ID);
    }
}
