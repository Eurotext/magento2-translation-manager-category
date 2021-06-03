<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Sender;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\TranslationManager\Service\Project\CreateProjectServiceInterface;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerCategory\Api\ProjectCategoryRepositoryInterface;
use Eurotext\TranslationManagerCategory\Repository\ProjectCategoryRepository;
use Eurotext\TranslationManagerCategory\Sender\CategorySender;
use Eurotext\TranslationManagerCategory\Test\Integration\Provider\CategoryProvider;
use Eurotext\TranslationManagerCategory\Test\Integration\Provider\ProjectCategoryProvider;

class CategorySenderTest extends IntegrationTestAbstract
{
    private static $categories;

    /** @var ProjectCategoryRepositoryInterface */
    private $projectEntityRepository;

    /** @var CategorySender */
    private $sut;

    /** @var ProjectCategoryProvider */
    private $projectEntityProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var CreateProjectServiceInterface */
    private $createProject;

    protected function setUp(): void
    {
        parent::setUp();

        $configBuiler = new ConfigurationMockBuilder($this);
        $config = $configBuiler->buildConfiguration();

        $itemApi = new ItemV1Api($config);

        $this->sut = $this->objectManager->create(CategorySender::class, ['itemApi' => $itemApi]);

        $projectApi = new ProjectV1Api($config);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class, ['projectApi' => $projectApi]
        );

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
        $this->projectEntityProvider = $this->objectManager->get(ProjectCategoryProvider::class);
        $this->projectEntityRepository = $this->objectManager->get(ProjectCategoryRepository::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldSendProjectEntities()
    {
        $entityId = array_shift(self::$categories);
        $projectName = __CLASS__ . '-category-sender';

        $project = $this->projectProvider->createProject($projectName);

        $projectEntity = $this->projectEntityProvider->createProjectCategory($project->getId(), $entityId);

        $resultProject = $this->createProject->execute($project);
        $this->assertTrue($resultProject);

        $result = $this->sut->send($project);

        $this->assertTrue($result);

        $projectEntity = $this->projectEntityRepository->getById($projectEntity->getId());

        $extId = $projectEntity->getExtId();

        $this->assertGreaterThan(0, $extId, 'The ext_id should be the one from Eurotext');

    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public static function loadFixture()
    {
        self::$categories = CategoryProvider::createCategories();
    }
}
