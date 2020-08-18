<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\CustomerData;

use Amasty\StorePickupWithLocator\Model\LocationProvider;
use Amasty\StorePickupWithLocator\Model\ConfigProvider;
use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * LocationData section
 */
class LocationData implements SectionSourceInterface
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LocationProvider
     */
    private $locationProvider;

    public function __construct(
        ConfigProvider $configProvider,
        LocationProvider $locationProvider
    ) {
        $this->configProvider = $configProvider;
        $this->locationProvider = $locationProvider;
    }

    /**
     * @inheritDoc
     */
    public function getSectionData()
    {
        if ($this->isStorePickupEnabled()) {
            return [
                'stores' => $this->locationProvider->getLocationCollection(),
                'website_id' => $this->locationProvider->getQuote()->getStore()->getWebsiteId(),
                'store_id' => $this->locationProvider->getQuote()->getStore()->getId()
            ];
        }

        return ['stores' => []];
    }

    /**
     * @return bool
     */
    private function isStorePickupEnabled()
    {
        return $this->configProvider->isStorePickupEnabled();
    }
}
