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
use Magento\Catalog\Model\Category;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryItemGetMapperUnitTest extends UnitTestAbstract
{
    /** @var CategoryItemGetMapper */
    private $sut;

    /** @var CategoryUrlPathGenerator|MockObject */
    private $categoryUrlPathGenerator;

    protected function setUp()
    {
        parent::setUp();

        $this->categoryUrlPathGenerator = $this->createMock(CategoryUrlPathGenerator::class);

        $this->sut = $this->objectManager->getObject(
            CategoryItemGetMapper::class,
            [
                'categoryUrlPathGenerator' => $this->categoryUrlPathGenerator,
            ]
        );
    }

    public function testMap()
    {
        $label = 'some-frontend-label';

        $this->categoryUrlPathGenerator->expects($this->once())
                                       ->method('getUrlKey')->willReturn('some-url-key');
        $this->categoryUrlPathGenerator->expects($this->once())
                                       ->method('getUrlPath')->willReturn('some-url-path');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Category $category */
        $category = $this->createMock(Category::class);

        // Execute test
        $itemGetResponse = new ItemGetResponse();
        $itemGetResponse->setData(
            [
                '__meta' => [],
                'name' => $label,
            ]
        );

        $categoryResult = $this->sut->map($itemGetResponse, $category);

        // ASSERT
        $this->assertInstanceOf(CategoryInterface::class, $categoryResult);
        $this->assertEquals($category, $categoryResult);
    }
}
