<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Unit\Seeder;

use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Model\ProjectCategoryFactory;
use Eurotext\TranslationManagerCategory\Seeder\CategorySeeder;
use Eurotext\TranslationManagerCategory\Setup\ProjectCategorySchema;
use Eurotext\TranslationManagerCategory\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Psr\Log\LoggerInterface;

class CategorySeederUnitTest extends UnitTestAbstract
{
    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ProjectCategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectCategoryRepository;

    /** @var ProjectCategoryFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $projectCategoryFactory;

    /** @var CategoryListInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryRepository;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var ProjectMockBuilder */
    private $projectBuilder;

    /** @var CategorySeeder */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->projectBuilder = new ProjectMockBuilder($this);

        $this->categoryRepository = $this->createMock(CategoryListInterface::class);
        $this->projectCategoryFactory = $this->createMock(ProjectCategoryFactory::class);
        $this->projectCategoryRepository = $this->createMock(ProjectCategoryRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            CategorySeeder::class, [
                'categoryRepository' => $this->categoryRepository,
                'projectCategoryFactory' => $this->projectCategoryFactory,
                'projectCategoryRepository' => $this->projectCategoryRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'logger' => $this->logger,
            ]
        );
    }

    public function testItShouldSeedCategorys()
    {
        $projectId = 33;
        $totalCount = 1;
        $entityId = 11;
        $pEntityCount = 0;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectCategorySchema::ENTITY_ID, $entityId],
            [ProjectCategorySchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Category
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);

        $categoryResult = $this->createMock(CategorySearchResultsInterface::class);
        $categoryResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $categoryResult->expects($this->once())->method('getItems')->willReturn([$category]);

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($categoryResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectCategoryRepository->expects($this->once())->method('save');

        // New project entity
        $pCategory = $this->createMock(ProjectCategoryInterface::class);
        $pCategory->expects($this->once())->method('setProjectId')->with($projectId);
        $pCategory->expects($this->once())->method('setEntityId')->with($entityId);
        $pCategory->expects($this->once())->method('setStatus')->with(ProjectCategoryInterface::STATUS_NEW);

        $this->projectCategoryFactory->expects($this->once())->method('create')->willReturn($pCategory);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldSkipSeedingIfCategoryIsSeededAlready()
    {
        $projectId = 33;
        $totalCount = 1;
        $entityId = 11;
        $pEntityCount = 1;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectCategorySchema::ENTITY_ID, $entityId],
            [ProjectCategorySchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Category
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);

        $categoryResult = $this->createMock(CategorySearchResultsInterface::class);
        $categoryResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $categoryResult->expects($this->once())->method('getItems')->willReturn([$category]);

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($categoryResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectCategoryRepository->expects($this->never())->method('save');

        $this->projectCategoryFactory->expects($this->never())->method('create');

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldSkipRootCategory()
    {
        $projectId = 33;
        $totalCount = 1;
        $entityId = 1;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectCategorySchema::ENTITY_ID, $entityId],
            [ProjectCategorySchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Category
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);

        $categoryResult = $this->createMock(CategorySearchResultsInterface::class);
        $categoryResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $categoryResult->expects($this->once())->method('getItems')->willReturn([$category]);

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($categoryResult);

        // Search existing project entity
        $this->projectCategoryRepository->expects($this->never())->method('getList');
        $this->projectCategoryRepository->expects($this->never())->method('save');

        $this->projectCategoryFactory->expects($this->never())->method('create');

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->never())->method('getId');

        // TEST
        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionsWhileSaving()
    {
        $projectId = 33;
        $totalCount = 1;
        $entityId = 11;
        $pEntityCount = 0;

        $this->searchCriteriaBuilder->method('addFilter')->withConsecutive(
            [ProjectCategorySchema::ENTITY_ID, $entityId],
            [ProjectCategorySchema::PROJECT_ID, $projectId]
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Category
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);

        $categoryResult = $this->createMock(CategorySearchResultsInterface::class);
        $categoryResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $categoryResult->expects($this->once())->method('getItems')->willReturn([$category]);

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($categoryResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectCategoryRepository->expects($this->once())->method('save')->willThrowException(new \Exception);

        // New project entity
        $pCategory = $this->createMock(ProjectCategoryInterface::class);
        $pCategory->expects($this->once())->method('setProjectId')->with($projectId);
        $pCategory->expects($this->once())->method('setEntityId')->with($entityId);
        $pCategory->expects($this->once())->method('setStatus')->with(ProjectCategoryInterface::STATUS_NEW);

        $this->projectCategoryFactory->expects($this->once())->method('create')->willReturn($pCategory);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        // TEST
        $result = $this->sut->seed($project);

        $this->assertFalse($result);
    }

    public function testItShouldSkipSeedingIfNoCategorysAreFound()
    {
        $entityTotalCount = 0;

        $searchCriteria = new SearchCriteria();

        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(CategorySearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($entityTotalCount);
        $searchResult->expects($this->never())->method('getItems');

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

    public function testItShouldAddEntitiesFilter()
    {
        $entityId = 11;
        $entities = [$entityId];
        $entityTotalCount = 0;

        $searchCriteria = new SearchCriteria();

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
                                    ->withConsecutive(['entity_id', $entities, 'in'])->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(CategorySearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($entityTotalCount);
        $searchResult->expects($this->never())->method('getItems');

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $project = $this->projectBuilder->buildProjectMock();

        $result = $this->sut->seed($project, $entities);

        $this->assertTrue($result);
    }

    public function testItShouldLogEntitiesNotFound()
    {
        $projectId = 33;
        $totalCount = 1;
        $entityId = 11;
        $entityIdNotFound = 1111;
        $pEntityCount = 0;

        $entities = [$entityId, $entityIdNotFound];

        $this->searchCriteriaBuilder->method('addFilter')
                                    ->withConsecutive(
                                        ['entity_id', $entities, 'in'],
                                        [ProjectCategorySchema::ENTITY_ID, $entityId],
                                        [ProjectCategorySchema::PROJECT_ID, $projectId]
                                    )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
                                    ->method('create')
                                    ->willReturnOnConsecutiveCalls(new SearchCriteria(), new SearchCriteria());

        // Category
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);

        $categoryResult = $this->createMock(CategorySearchResultsInterface::class);
        $categoryResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $categoryResult->expects($this->once())->method('getItems')->willReturn([$category]);

        $this->categoryRepository->expects($this->once())->method('getList')->willReturn($categoryResult);

        // Search existing project entity
        $projectEntityResult = $this->createMock(SearchResultsInterface::class);
        $projectEntityResult->expects($this->once())->method('getTotalCount')->willReturn($pEntityCount);

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($projectEntityResult);
        $this->projectCategoryRepository->expects($this->once())->method('save');

        // New project entity
        $pCategory = $this->createMock(ProjectCategoryInterface::class);
        $pCategory->expects($this->once())->method('setProjectId')->with($projectId);
        $pCategory->expects($this->once())->method('setEntityId')->with($entityId);
        $pCategory->expects($this->once())->method('setStatus')->with(ProjectCategoryInterface::STATUS_NEW);

        $this->projectCategoryFactory->expects($this->once())->method('create')->willReturn($pCategory);

        // project mock
        $project = $this->projectBuilder->buildProjectMock();
        $project->expects($this->once())->method('getId')->willReturn($projectId);

        $this->logger->expects($this->once())->method('error');

        // TEST
        $result = $this->sut->seed($project, $entities);

        $this->assertTrue($result);
    }

}