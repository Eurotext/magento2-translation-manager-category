<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Setup;

use Eurotext\TranslationManagerCategory\Setup\Service\CreateProjectCategorySchema;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var CreateProjectCategorySchema
     */
    private $createProjectCategorySchema;

    public function __construct(
        CreateProjectCategorySchema $createProjectCategorySchema
    ) {
        $this->createProjectCategorySchema = $createProjectCategorySchema;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createProjectCategorySchema->execute($setup);
    }
}
