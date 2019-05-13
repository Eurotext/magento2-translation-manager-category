<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Test\Builder\ProjectMockBuilder;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Repository\ProjectCategoryRepository;
use Eurotext\TranslationManagerCategory\Retriever\CategoryRetriever;
use Eurotext\TranslationManagerCategory\Test\Builder\ProjectCategoryMockBuilder;
use Eurotext\TranslationManagerCategory\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\TransferException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;

class CategoryRetrieverUnitTest extends UnitTestAbstract
{
    /** @var CategoryRetriever */
    private $sut;

    /** @var ProjectMockBuilder */
    private $projectMockBuilder;

    /** @var ProjectCategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $projectCategoryRepository;

    /** @var SearchResultsInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $searchResults;

    /** @var CategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var ItemV1ApiInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    protected function setUp()
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1ApiInterface::class);

        $this->projectCategoryRepository = $this->createMock(ProjectCategoryRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->searchResults = $this->createMock(SearchResultsInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);

        $this->projectMockBuilder = new ProjectMockBuilder($this);

        $this->sut = $this->objectManager->getObject(
            CategoryRetriever::class,
            [
                'itemApi' => $this->itemApi,
                'projectCategoryRepository' => $this->projectCategoryRepository,
                'categoryRepository' => $this->categoryRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    public function testItShouldRetrieveProjectCategories()
    {
        $categoryId = 1;
        $storeId = 3;
        $status = ProjectCategoryInterface::STATUS_IMPORTED;
        $lastError = '';

        $project = $this->projectMockBuilder->buildProjectMock();
        $project->method('getStoreviewDst')->willReturn($storeId);

        $projectCategory = $this->createMock(ProjectCategoryInterface::class);
        $projectCategory->expects($this->once())->method('setStatus')->with($status);
        $projectCategory->expects($this->once())->method('setLastError')->with($lastError);
        $projectCategory->expects($this->once())->method('getEntityId')->willReturn($categoryId);
        $projectCategory->expects($this->once())->method('getExtId')->willReturn(2423);

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectCategoryRepository->expects($this->once())->method('save')->with($projectCategory);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectCategory]);

        $category = $this->createMock(CategoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')
                                 ->with($categoryId, $storeId)->willReturn($category);

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);
    }

    public function testItShouldSetLastErrorForGuzzleException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new TransferException($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    public function testItShouldSetLastErrorForException()
    {
        $lastError = 'The Message from the exception that occured';
        $apiException = new \Exception($lastError);

        $this->runTestExceptionsAreHandledCorrectly($apiException);
    }

    private function runTestExceptionsAreHandledCorrectly(\Exception $apiException)
    {
        $status = ProjectCategoryInterface::STATUS_ERROR;

        $project = $this->projectMockBuilder->buildProjectMock();

        $projectCategory = $this->createMock(ProjectCategoryInterface::class);
        $projectCategory->expects($this->once())->method('setStatus')->with($status);
        $projectCategory->expects($this->once())->method('setLastError')->with($apiException->getMessage());

        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($this->searchResults);
        $this->projectCategoryRepository->expects($this->once())->method('save')->with($projectCategory);

        $this->searchResults->expects($this->once())->method('getItems')->willReturn([$projectCategory]);

        $category = $this->createMock(CategoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')->willReturn($category);

        $this->itemApi->method('get')->willThrowException($apiException);

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertFalse($result);
    }
}
