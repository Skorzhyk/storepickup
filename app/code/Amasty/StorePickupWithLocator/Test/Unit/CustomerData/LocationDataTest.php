<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Test\Unit\CustomerData;

use Amasty\StorePickupWithLocator\CustomerData\LocationData;
use Amasty\StorePickupWithLocator\Test\Unit\Traits;
use Amasty\StorePickupWithLocator\Model\ConfigProvider;
use Amasty\StorePickupWithLocator\Model\LocationProvider;
use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use PHPUnit\Framework\TestCase;

/**
 * Class LocationDataTest
 *
 * @see LocationData
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class LocationDataTest extends TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    const STORE_ID = 0;

    const WEBSITE_ID = 0;

    /**
     * @var LocationData
     */
    private $model;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LocationProvider
     */
    private $locationProvider;


    public function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);;
        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->locationProvider = $this->createMock(LocationProvider::class);

        $this->model = $this->getObjectManager()->getObject(
            LocationData::class,
            [
                'configProvider' => $this->configProvider,
                'checkoutSession' => $this->checkoutSession,
                'locationProvider' => $this->locationProvider
            ]
        );
    }

    /**
     * @covers       LocationData::getSectionData
     * @dataProvider testGetSectionDataDataProvider
     */
    public function testGetSectionData($expected)
    {
        $this->configProvider->expects($this->any())->method('isStorePickupEnabled')
            ->willReturn(true);

        $returnedStores = [
            [
                'id' => '2'
            ]
        ];

        $this->locationProvider->expects($this->any())->method('getLocationCollection')->willReturn($returnedStores);

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->locationProvider->expects($this->any())->method('getQuote')->willReturn($quoteMock);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($store);

        $store->expects($this->any())->method('getWebsiteId')->willReturn(self::WEBSITE_ID);
        $store->expects($this->any())->method('getId')->willReturn(self::STORE_ID);

        $this->assertEquals($expected, $this->model->getSectionData());
    }

    public function testGetSectionDataDataProvider()
    {
        return
            [
                [
                    [
                        'stores' => [
                            [
                                'id' => '2',
                            ]
                        ],
                        'website_id' => self::WEBSITE_ID,
                        'store_id' => self::STORE_ID
                    ]
                ]
            ];
    }
}
