<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Repository;

use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerCategory\Repository\ProjectCategoryRepository;
use Eurotext\TranslationManagerCategory\Test\Integration\Provider\ProjectCategoryProvider;
use Magento\Framework\Exception\NoSuchEntityException;

class ProjectCategoryRepositoryTest extends IntegrationTestAbstract
{
    /** @var ProjectCategoryRepository */
    protected $sut;

    /** @var ProjectCategoryProvider */
    protected $projectCategoryProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->sut = $this->objectManager->get(ProjectCategoryRepository::class);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
        $this->projectCategoryProvider = $this->objectManager->get(ProjectCategoryProvider::class);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testItShouldCreateAProjectCategoryAndGetItById()
    {
        $categoryId = 1;
        $name = __CLASS__ . '-test-getById';
        $project = $this->projectProvider->createProject($name);
        $projectId = $project->getId();

        $projectCategory = $this->projectCategoryProvider->createProjectCategory($projectId, $categoryId);

        $id = $projectCategory->getId();

        $this->assertTrue($id > 0);

        $projectRead = $this->sut->getById($id);

        $this->assertSame($id, $projectRead->getId());
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testItShouldDeleteProjectCategorys()
    {
        $categoryId = 1;
        $name = __CLASS__ . '-test-delete';
        $project = $this->projectProvider->createProject($name);
        $projectId = $project->getId();

        $projectCategory = $this->projectCategoryProvider->createProjectCategory($projectId, $categoryId);

        $id = $projectCategory->getId();

        $result = $this->sut->deleteById($id);

        $this->assertTrue($result);

        try {
            $projectRead = $this->sut->getById($id);
        } catch (NoSuchEntityException $e) {
            $projectRead = null;
        }

        $this->assertNull($projectRead);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldReturnAListOfProjectCategorys()
    {
        $categoryIds = [1, 2, 3];

        $name = __CLASS__ . '-test-list';
        $project = $this->projectProvider->createProject($name);
        $projectId = $project->getId();

        $projectCategorys = [];
        foreach ($categoryIds as $categoryId) {
            $projectCategory = $this->projectCategoryProvider->createProjectCategory($projectId, $categoryId);

            $projectCategorys[$categoryId] = $projectCategory;
        }

        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->objectManager->get(\Magento\Framework\Api\SearchCriteria::class);

        $searchResults = $this->sut->getList($searchCriteria);

        $items = $searchResults->getItems();
        $this->assertTrue(count($items) >= count($projectCategorys));
    }

}
