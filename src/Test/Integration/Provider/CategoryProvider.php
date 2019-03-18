<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Provider;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\ObjectManagerInterface;

class CategoryProvider
{
    /** @var CategoryRepositoryInterface */
    private static $categoryRepository;

    /** @var ObjectManagerInterface */
    private static $objectManager;

    private static function _construct()
    {
        self::$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        self::$categoryRepository = self::$objectManager->get(CategoryRepositoryInterface::class);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public static function createCategories(): array
    {
        self::_construct();

        /** @var CategoryInterface $category */
        $category = self::$objectManager->create(CategoryInterface::class);

        $category->setName(__METHOD__ . \date('YmdHis'));
        $category->setParentId(1);
        $category->setPath('1/100');
        $category->setLevel(1);
        $category->setIsActive(true);
        $category->setPosition(1);
        /** @var Category $category */
        $category->setData('description', 'some description for a category');

        $result = self::$categoryRepository->save($category);

        return [(int)$result->getId()];
    }
}
