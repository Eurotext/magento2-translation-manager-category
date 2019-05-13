<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Retriever;

use Eurotext\RestApiClient\Api\Project\ItemV1Api;
use Eurotext\RestApiClient\Api\ProjectV1Api;
use Eurotext\RestApiClient\Request\ProjectTranslateRequest;
use Eurotext\TranslationManager\Service\Project\CreateProjectServiceInterface;
use Eurotext\TranslationManager\Test\Builder\ConfigurationMockBuilder;
use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerCategory\Model\ProjectCategory;
use Eurotext\TranslationManagerCategory\Repository\ProjectCategoryRepository;
use Eurotext\TranslationManagerCategory\Retriever\CategoryRetriever;
use Eurotext\TranslationManagerCategory\Sender\CategorySender;
use Eurotext\TranslationManagerCategory\Test\Integration\Provider\ProjectCategoryProvider;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

class CategoryRetrieverTest extends IntegrationTestAbstract
{
    /** @var ProjectCategoryRepository */
    private $projectCategoryRepository;

    /** @var CategoryRetriever */
    private $sut;

    /** @var ProjectCategoryProvider */
    private $projectCategoryProvider;

    /** @var ProjectProvider */
    private $projectProvider;

    /** @var CategorySender */
    private $categorySender;

    /** @var CreateProjectServiceInterface */
    private $createProject;

    /** @var ProjectV1Api */
    private $projectApi;

    protected function setUp()
    {
        parent::setUp();

        $config = (new ConfigurationMockBuilder($this))->buildConfiguration();

        $itemApi = new ItemV1Api($config);
        $this->projectApi = new ProjectV1Api($config);

        $this->sut = $this->objectManager->create(CategoryRetriever::class, ['itemApi' => $itemApi]);

        $this->createProject = $this->objectManager->create(
            CreateProjectServiceInterface::class, ['projectApi' => $this->projectApi]
        );

        $this->categorySender = $this->objectManager->create(CategorySender::class, ['itemApi' => $itemApi]);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
        $this->projectCategoryProvider = $this->objectManager->get(ProjectCategoryProvider::class);
        $this->projectCategoryRepository = $this->objectManager->get(ProjectCategoryRepository::class);
    }

    /**
     * @magentoDataFixture loadFixture
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testItShouldRetrieveProjectCategorys()
    {
        $categoryId = 10;
        $name = __CLASS__ . '-category-retriever';

        $project = $this->projectProvider->createProject($name);

        $projectCategory1 = $this->projectCategoryProvider->createProjectCategory($project->getId(), $categoryId);
        $projectCategoryId = $projectCategory1->getId();

        // Create project at Eurotext
        $resultProjectCreate = $this->createProject->execute($project);
        $this->assertTrue($resultProjectCreate);

        // Send Project Categorys to Eurotext
        $resultSend = $this->categorySender->send($project);
        $this->assertTrue($resultSend);

        // trigger translation progress
        $this->projectApi->translate(new ProjectTranslateRequest($project->getExtId()));

        try {
            // Set The area code otherwise image resizing will fail
            /** @var State $appState */
            $appState = $this->objectManager->get(State::class);
            $appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
        }

        // Retrieve Project from Eurotext
        $result = $this->sut->retrieve($project);

        $this->assertTrue($result);

        $projectCategory = $this->projectCategoryRepository->getById($projectCategoryId);
        $this->assertGreaterThan(0, $projectCategory->getExtId());
        $this->assertEquals(ProjectCategory::STATUS_IMPORTED, $projectCategory->getStatus());
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../_fixtures/provide_categories.php';
    }
}
