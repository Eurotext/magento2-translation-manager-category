<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Test\Unit\Mapper;

use Eurotext\RestApiClient\Request\Project\ItemPostRequest;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ScopeConfigReaderInterface;
use Eurotext\TranslationManagerCategory\Mapper\CategoryItemPostMapper;
use Eurotext\TranslationManagerCategory\ScopeConfig\CategoryScopeConfigReader;
use Eurotext\TranslationManagerCategory\Test\Unit\UnitTestAbstract;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\AttributeInterface;

class CategoryItemPostMapperUnitTest extends UnitTestAbstract
{
    /** @var CategoryScopeConfigReader|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryScopeConfig;

    /** @var ScopeConfigReaderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeConfig;

    /** @var CategoryItemPostMapper */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scopeConfig = $this->createMock(ScopeConfigReaderInterface::class);
        $this->categoryScopeConfig = $this->createMock(CategoryScopeConfigReader::class);

        $this->sut = $this->objectManager->getObject(
            CategoryItemPostMapper::class,
            [
                'scopeConfigReader' => $this->scopeConfig,
                'categoryScopeConfig' => $this->categoryScopeConfig,
            ]
        );
    }

    public function testMap()
    {
        $projectId = 123;
        $categoryId = 32123;

        $storeViewSrc = 1;
        $storeViewDst = 2;
        $langSrc = 'de_DE';
        $langDst = 'en_US';

        $nameValue = 'category-name';
        $descValue = 'This is some description';
        $shortDescValue = 'really short description';

        $attrCodeName = 'name';
        $attrCodeDesc = 'description';
        $attrCodeShortDesc = 'short_description';
        $attributesEnabled = [$attrCodeName, $attrCodeDesc, $attrCodeShortDesc];

        // Mock Category with Custom Attributes
        $descAttribute = $this->createMock(AttributeInterface::class);
        $descAttribute->expects($this->once())->method('getValue')->willReturn($descValue);

        $shortDescAttribute = $this->createMock(AttributeInterface::class);
        $shortDescAttribute->expects($this->once())->method('getValue')->willReturn($shortDescValue);

        /** @var CategoryInterface|\PHPUnit_Framework_MockObject_MockObject $category */
        $category = $this->createMock(CategoryInterface::class);
        $category->expects($this->any())->method('getId')->willReturn($categoryId);
        $category->expects($this->once())->method('getName')->willReturn($nameValue);
        $category->expects($this->exactly(3))
                 ->method('getCustomAttribute')
                 ->willReturnOnConsecutiveCalls(null, $descAttribute, $shortDescAttribute);

        // Mock Project
        /** @var ProjectInterface|\PHPUnit_Framework_MockObject_MockObject $project */
        $project = $this->createMock(ProjectInterface::class);
        $project->expects($this->once())->method('getExtId')->willReturn($projectId);
        $project->expects($this->once())->method('getStoreviewSrc')->willReturn($storeViewSrc);
        $project->expects($this->once())->method('getStoreviewDst')->willReturn($storeViewDst);

        // Mock ScopeConfig
        $this->scopeConfig->expects($this->exactly(2))
                          ->method('getLocaleForStore')
                          ->willReturnOnConsecutiveCalls($langSrc, $langDst);

        // Mock CategoryScopeConfig
        $this->categoryScopeConfig->expects($this->once())
                                  ->method('getAttributesEnabled')->willReturn($attributesEnabled);

        // Execute test
        $request = $this->sut->map($category, $project);

        // ASSERT
        $this->assertInstanceOf(ItemPostRequest::class, $request);

        $this->assertEquals($projectId, $request->getProjectId());
        $this->assertEquals($langSrc, $request->getSource());
        $this->assertEquals($langDst, $request->getTarget());
        $this->assertEquals(CategoryItemPostMapper::ENTITY_TYPE, $request->getTextType());

        $itemData = $request->getData();

        $meta = $itemData->getMeta();
        $this->assertEquals($categoryId, $meta['item_id']);
        $this->assertEquals($categoryId, $meta['entity_id']);
        $this->assertEquals(CategoryItemPostMapper::ENTITY_TYPE, $meta['entity_type']);

        $data = $itemData->getData();
        $this->assertEquals($nameValue, $data[$attrCodeName]);
        $this->assertEquals($descValue, $data[$attrCodeDesc]);
        $this->assertEquals($shortDescValue, $data[$attrCodeShortDesc]);
    }
}
