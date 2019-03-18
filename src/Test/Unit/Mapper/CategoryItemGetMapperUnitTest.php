<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Unit\Mapper;

use Eurotext\RestApiClient\Response\Project\ItemGetResponse;
use Eurotext\TranslationManagerCategory\Mapper\CategoryItemGetMapper;
use Eurotext\TranslationManagerProduct\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Api\Data\CategoryInterface;

class CategoryItemGetMapperUnitTest extends UnitTestAbstract
{

    /** @var CategoryItemGetMapper */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->sut = $this->objectManager->getObject(
            CategoryItemGetMapper::class,
            [
            ]
        );
    }

    public function testMap()
    {
        $label = 'some-frontend-label';
        $optionValue = '111';
        $optionLabel = 'Option Label 1';

        /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryInterface $category */
        $category = $this->createMock(CategoryInterface::class);

        // Execute test
        $itemGetResponse = new ItemGetResponse();
        $itemGetResponse->setData(
            [
                '__meta' => [],
                'label' => $label,
                'options' => [
                    $optionValue => $optionLabel,
                    '222' => 'Option no longer exists in Magento Database',
                ],
            ]
        );

        $categoryResult = $this->sut->map($itemGetResponse, $category);

        // ASSERT
        $this->assertInstanceOf(CategoryInterface::class, $categoryResult);
        $this->assertEquals($category, $categoryResult);
    }
}
