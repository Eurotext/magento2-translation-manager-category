<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\RestApiClient\Request\Project\ItemGetRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntityRetrieverInterface;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Mapper\CategoryItemGetMapper;
use Eurotext\TranslationManagerCategory\Setup\ProjectCategorySchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class CategoryRetriever implements EntityRetrieverInterface
{
    /**
     * @var ProjectCategoryRepositoryInterface
     */
    private $projectCategoryRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ItemV1ApiInterface
     */
    private $itemApi;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CategoryItemGetMapper
     */
    private $categoryItemGetMapper;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectCategoryRepositoryInterface $projectCategoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CategoryRepositoryInterface $categoryRepository,
        CategoryItemGetMapper $categoryItemGetMapper,
        LoggerInterface $logger
    ) {
        $this->projectCategoryRepository = $projectCategoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->itemApi = $itemApi;
        $this->categoryRepository = $categoryRepository;
        $this->categoryItemGetMapper = $categoryItemGetMapper;
        $this->logger = $logger;
    }

    public function retrieve(ProjectInterface $project): bool
    {
        $result = true;

        $projectId = $project->getId();
        $projectExtId = $project->getExtId();
        $storeId = $project->getStoreviewDst();

        $this->logger->info(sprintf('retrieve project categories project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::EXT_ID, 0, 'gt');
        $this->searchCriteriaBuilder->addFilter(
            ProjectCategorySchema::STATUS, ProjectCategoryInterface::STATUS_EXPORTED
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectCategoryRepository->getList($searchCriteria);

        $projectCategorys = $searchResult->getItems();

        foreach ($projectCategorys as $projectCategory) {
            $lastError = '';

            /** @var $projectCategory ProjectCategoryInterface */
            $itemExtId = $projectCategory->getExtId();
            $categoryId = $projectCategory->getEntityId();

            try {
                $category = $this->categoryRepository->get($categoryId, $storeId);

                $itemRequest = new ItemGetRequest($projectExtId, $itemExtId);

                $itemGetResponse = $this->itemApi->get($itemRequest);

                $this->categoryItemGetMapper->map($itemGetResponse, $category);

                $this->categoryRepository->save($category);

                $status = ProjectCategoryInterface::STATUS_IMPORTED;

                $this->logger->info(sprintf('category id:%d, ext-id:%d => success', $categoryId, $itemExtId));
            } catch (GuzzleException $e) {
                $status = ProjectCategoryInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('category id:%d => %s', $categoryId, $lastError));
                $result = false;
            } catch (\Exception $e) {
                $status = ProjectCategoryInterface::STATUS_ERROR;
                $lastError = $e->getMessage();
                $this->logger->error(sprintf('category id:%d => %s', $categoryId, $lastError));
                $result = false;
            }

            $projectCategory->setStatus($status);
            $projectCategory->setLastError($lastError);
            $this->projectCategoryRepository->save($projectCategory);
        }

        return $result;
    }

}
