<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Seeder;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\EntitySeederInterface;
use Eurotext\TranslationManagerCategory\Api\Data\ProjectCategoryInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Model\ProjectCategoryFactory;
use Eurotext\TranslationManagerCategory\Setup\ProjectCategorySchema;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * CategorySeeder
 */
class CategorySeeder implements EntitySeederInterface
{
    /**
     * @var CategoryListInterface
     */
    private $categoryRepository;

    /**
     * @var ProjectCategoryFactory
     */
    private $projectCategoryFactory;

    /**
     * @var ProjectCategoryRepositoryInterface
     */
    private $projectCategoryRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CategoryListInterface $categoryRepository,
        ProjectCategoryFactory $projectCategoryFactory,
        ProjectCategoryRepositoryInterface $projectCategoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->projectCategoryFactory = $projectCategoryFactory;
        $this->projectCategoryRepository = $projectCategoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    public function seed(ProjectInterface $project, array $entities = []): bool
    {
        $result = true;

        // get category collection
        if (count($entities) > 0) {
            $this->searchCriteriaBuilder->addFilter('entity_id', $entities, 'in');
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->categoryRepository->getList($searchCriteria);

        if ($searchResult->getTotalCount() === 0) {
            // no categorys found, matching the criteria
            $this->logger->warning('no matching categorys found');

            return $result;
        }

        $entitiesNotFound = array_flip($entities);

        // create project category configurations
        $categorys = $searchResult->getItems();

        foreach ($categorys as $category) {
            // Found entity, so remove it from not found list
            unset($entitiesNotFound[$category->getId()]);

            // Ignore category "Root Catalog", it is invisible and cannot be translated
            if ((int)$category->getId() === 1) {
                continue;
            }

            /** @var $category CategoryInterface */
            $isSeeded = $this->seedEntity($project, $category);

            $result = !$isSeeded ? false : $result;
        }

        // Log entites that where not found
        if (count($entitiesNotFound) > 0) {
            foreach ($entitiesNotFound as $sku => $value) {
                $this->logger->error(sprintf('category-id "%s" not found', $sku));
            }

        }

        return $result;
    }

    private function seedEntity(ProjectInterface $project, CategoryInterface $category): bool
    {
        $projectId = $project->getId();
        $categoryId = (int)$category->getId();

        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::ENTITY_ID, $categoryId);
        $this->searchCriteriaBuilder->addFilter(ProjectCategorySchema::PROJECT_ID, $projectId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->projectCategoryRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() >= 1) {
            // category has already been added to project
            $this->logger->info(sprintf('skipping category "%s"(%d) already added', $categoryId, $categoryId));

            return true;
        }

        /** @var ProjectCategoryInterface $projectCategory */
        $projectCategory = $this->projectCategoryFactory->create();
        $projectCategory->setProjectId($projectId);
        $projectCategory->setEntityId($categoryId);
        $projectCategory->setStatus(ProjectCategoryInterface::STATUS_NEW);

        try {
            $this->projectCategoryRepository->save($projectCategory);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
