<?php
declare(strict_types=1);

namespace Eurotext\TranslationManagerCategory\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectEntityInterface;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Model\ProjectCategory;
use Eurotext\TranslationManagerCategory\Model\ProjectCategoryFactory;
use Eurotext\TranslationManagerCategory\Model\ResourceModel\ProjectCategoryCollectionFactory;
use Eurotext\TranslationManagerCategory\Model\ResourceModel\ProjectCategoryResource;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProjectCategoryRepository implements ProjectCategoryRepositoryInterface
{
    /**
     * @var ProjectCategoryFactory
     */
    protected $projectCategoryFactory;

    /**
     * @var ProjectCategoryResource
     */
    private $categoryResource;

    /**
     * @var ProjectCategoryCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    public function __construct(
        ProjectCategoryResource $categoryResource,
        ProjectCategoryFactory $projectFactory,
        ProjectCategoryCollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->projectCategoryFactory = $projectFactory;
        $this->categoryResource = $categoryResource;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return ProjectEntityInterface
     * @throws CouldNotSaveException
     */
    public function save(ProjectEntityInterface $object): ProjectEntityInterface
    {
        try {
            /** @var ProjectCategory $object */
            $this->categoryResource->save($object);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param int $id
     *
     * @return ProjectEntityInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ProjectEntityInterface
    {
        /** @var ProjectCategory $object */
        $object = $this->projectCategoryFactory->create();
        $this->categoryResource->load($object, $id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Project with id "%1" does not exist.', $id));
        }

        return $object;
    }

    /**
     * @param ProjectEntityInterface $object
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ProjectEntityInterface $object): bool
    {
        try {
            /** @var ProjectCategory $object */
            $this->categoryResource->delete($object);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        $object = $this->getById($id);

        return $this->delete($object);
    }

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC';
                $collection->addOrder($sortOrder->getField(), $direction);
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }

        /** @var \Magento\Framework\Api\SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
