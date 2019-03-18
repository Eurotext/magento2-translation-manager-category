<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\RestApiClient\Response\Project\ItemPostResponse;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Mapper\CategoryItemPostMapper;
use Eurotext\TranslationManagerCategory\Sender\CategorySender;
use Eurotext\TranslationManagerCategory\Test\Unit\UnitTestAbstract;
use GuzzleHttp\Exception\RequestException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CategorySenderUnitTest extends UnitTestAbstract
{
    /** @var CategorySender */
    private $sut;

    /** @var ProjectCategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $projectCategoryRepository;

    /** @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $searchCriteriaBuilder;

    /** @var CategoryRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryRepository;

    /** @var CategoryItemPostMapper|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryItemPostMapper;

    /** @var ItemV1Api|\PHPUnit_Framework_MockObject_MockObject */
    private $itemApi;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->itemApi = $this->createMock(ItemV1Api::class);

        $this->projectCategoryRepository = $this->createMock(ProjectCategoryRepositoryInterface::class);

        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);

        $this->categoryItemPostMapper = $this->createMock(CategoryItemPostMapper::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = $this->objectManager->getObject(
            CategorySender::class,
            [
                'itemApi' => $this->itemApi,
                'projectCategoryRepository' => $this->projectCategoryRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'categoryRepository' => $this->categoryRepository,
                'itemPostMapper' => $this->categoryItemPostMapper,
                'logger' => $this->logger,
            ]
        );

    }

    public function testItShouldSendProjectEntities()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $categoryId = 11;

        $projectEntity = $this->createMock(ProjectCategoryInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($categoryId);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectCategoryInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $category = $this->createMock(CategoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')
                                 ->with($categoryId)->willReturn($category);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->categoryItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectCategoryRepository->expects($this->once())->method('save')->with($projectEntity);

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldNoSendIfEntityHasExtId()
    {
        $extIdSaved = 12345;

        $projectEntity = $this->createMock(ProjectCategoryInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->categoryRepository->expects($this->never())->method('get');
        $this->categoryItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectCategoryRepository->expects($this->never())->method('save');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionIfCategoryIsNotFound()
    {
        $extIdSaved = 0;
        $categoryId = 11;

        $projectEntity = $this->createMock(ProjectCategoryInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($categoryId);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $this->categoryRepository->expects($this->once())->method('get')
                                 ->with($categoryId)
                                 ->willThrowException(new NoSuchEntityException());

        $this->categoryItemPostMapper->expects($this->never())->method('map');
        $this->itemApi->expects($this->never())->method('post');
        $this->projectCategoryRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionFromTheApi()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $categoryId = 23;

        $projectEntity = $this->createMock(ProjectCategoryInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($categoryId);
        $projectEntity->expects($this->never())->method('setExtId');
        $projectEntity->expects($this->never())->method('setStatus');

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $category = $this->createMock(CategoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')
                                 ->with($categoryId)
                                 ->willReturn($category);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->categoryItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->never())->method('getId');
        $exception = $this->createMock(RequestException::class);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willThrowException($exception);

        $this->projectCategoryRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }

    public function testItShouldCatchExceptionWhileSavingTheCategory()
    {
        $extIdSaved = 0;
        $extIdNew = 12345;
        $categoryId = 222;

        $projectEntity = $this->createMock(ProjectCategoryInterface::class);
        $projectEntity->expects($this->once())->method('getExtId')->willReturn($extIdSaved);
        $projectEntity->expects($this->once())->method('getEntityId')->willReturn($categoryId);
        $projectEntity->expects($this->once())->method('setExtId')->with($extIdNew);
        $projectEntity->expects($this->once())->method('setStatus')->with(ProjectCategoryInterface::STATUS_EXPORTED);

        $searchResultItems = [$projectEntity];

        $searchResult = $this->createMock(SearchResultsInterface::class);
        $searchResult->expects($this->once())->method('getItems')->willReturn($searchResultItems);
        $this->projectCategoryRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $category = $this->createMock(CategoryInterface::class);
        $this->categoryRepository->expects($this->once())->method('get')
                                 ->with($categoryId)
                                 ->willReturn($category);

        $itemRequest = $this->createMock(ItemPostRequest::class);
        $this->categoryItemPostMapper->expects($this->once())->method('map')->willReturn($itemRequest);

        $itemPostResponse = $this->createMock(ItemPostResponse::class);
        $itemPostResponse->expects($this->once())->method('getId')->willReturn($extIdNew);
        $this->itemApi->expects($this->once())->method('post')->with($itemRequest)->willReturn($itemPostResponse);

        $this->projectCategoryRepository->expects($this->once())->method('save')
                                        ->with($projectEntity)->willThrowException(new \Exception());

        $this->logger->expects($this->once())->method('error');

        $project = $this->createMock(ProjectInterface::class);
        $project->method('getId')->willReturn(123);
        /** @var ProjectInterface $project */

        $result = $this->sut->send($project);

        $this->assertFalse($result);
    }
}
