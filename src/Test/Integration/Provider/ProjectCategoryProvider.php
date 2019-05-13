<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Provider;

use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Repository\ProjectCategoryRepository;
use Magento\TestFramework\Helper\Bootstrap;

class ProjectCategoryProvider
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var ProjectCategoryRepository */
    private $projectCategoryRepository;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->projectCategoryRepository = $this->objectManager->get(ProjectCategoryRepository::class);
    }

    /**
     *
     * @param int $projectId
     * @param int $categoryId
     * @param string $status
     *
     * @return ProjectCategoryInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createProjectCategory(
        int $projectId,
        int $categoryId,
        string $status = ProjectCategoryInterface::STATUS_NEW
    ): ProjectCategoryInterface {
        /** @var ProjectCategoryInterface $object */
        $object = $this->objectManager->create(ProjectCategoryInterface::class);
        $object->setProjectId($projectId);
        $object->setEntityId($categoryId);
        $object->setStatus($status);

        return $this->projectCategoryRepository->save($object);
    }
}
