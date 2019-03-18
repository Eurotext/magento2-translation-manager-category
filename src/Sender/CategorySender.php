<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1ApiInterface;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySenderInterface;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Mapper\CategoryItemPostMapper;
use Eurotext\TranslationManagerCategory\Setup\ProjectCategorySchema;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CategorySender implements EntitySenderInterface
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
     * @var CategoryItemPostMapper
     */
    private $itemPostMapper;

    public function __construct(
        ItemV1ApiInterface $itemApi,
        ProjectCategoryRepositoryInterface $projectCategoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CategoryRepositoryInterface $categoryRepository,
        CategoryItemPostMapper $itemPostMapper,
        LoggerInterface $logger
    ) {
        $this->projectCategoryRepository = $projectCategoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->itemApi = $itemApi;
        $this->categoryRepository = $categoryRepository;
        $this->itemPostMapper = $itemPostMapper;
        $this->logger = $logger;
    }

    public function send(ProjectInterface $project): bool
    {
        $result = true;

        $projectId = $project->getId();

        $this->logger->info(sprintf('send project categories project-id:%d', $projectId));

        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::PROJECT_ID, $projectId);
        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::EXT_ID, 0);
        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::STATUS, ProjectCategoryInterface::STATUS_NEW);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->projectCategoryRepository->getList($searchCriteria);

        /** @var $projectCategorys ProjectCategoryInterface[] */
        $projectCategorys = $searchResult->getItems();

        foreach ($projectCategorys as $projectCategory) {
            $isEntitySent = $this->sendEntity($project, $projectCategory);

            $result = $isEntitySent ? $result : false;
        }

        return $result;
    }

    private function sendEntity(ProjectInterface $project, ProjectCategoryInterface $projectCategory): bool
    {
        $result = true;

        // Skip already transferred categorys
        if ($projectCategory->getExtId() > 0) {
            return true;
        }

        $categoryId  = $projectCategory->getEntityId();

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('category %s => %s', $categoryId, $message));

            return false;
        }

        $itemRequest = $this->itemPostMapper->map($category, $project);

        try {
            $response = $this->itemApi->post($itemRequest);

            // save project_category ext_id
            $extId = $response->getId();
            $projectCategory->setExtId($extId);
            $projectCategory->setStatus(ProjectCategoryInterface::STATUS_EXPORTED);

            $this->projectCategoryRepository->save($projectCategory);

            $this->logger->info(sprintf('category %s, ext-id:%s => success', $categoryId, $extId));
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('category %s => %s', $categoryId, $message));
            $result = false;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->logger->error(sprintf('category %s => %s', $categoryId, $message));
            $result = false;
        }

        return $result;
    }
}
