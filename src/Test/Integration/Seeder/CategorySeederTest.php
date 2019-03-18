<?php
declare(strict_types=1);
/**
 * @copyright see LICENSE.txt
 *
 * @see LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Integration\Seeder;

use Eurotext\TranslationManager\Test\Integration\IntegrationTestAbstract;
use Eurotext\TranslationManager\Test\Integration\Provider\ProjectProvider;
use Eurotext\TranslationManagerCategory\Seeder\CategorySeeder;

class CategorySeederTest extends IntegrationTestAbstract
{
    /** @var CategorySeeder */
    protected $sut;

    /** @var ProjectProvider */
    private $projectProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->sut = $this->objectManager->create(CategorySeeder::class);

        $this->projectProvider = $this->objectManager->get(ProjectProvider::class);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testItShouldSeedProjectProducts()
    {
        $name = __CLASS__ . '-category-seeder';

        $project = $this->projectProvider->createProject($name);

        $result = $this->sut->seed($project);

        $this->assertTrue($result);
    }

}
