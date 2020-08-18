<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorelocatorIndexer
 */


namespace Amasty\StorelocatorIndexer\Plugin\Model;

use Amasty\Storelocator\Model\Location;
use Amasty\Storelocator\Model\LocationProductValidator;
use Amasty\StorelocatorIndexer\Model\ResourceModel\LocationProductIndex;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

class LocationProductValidatorPlugin
{
    /**
     * @var LocationProductIndex
     */
    private $locationProductIndex;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        LocationProductIndex $locationProductIndex,
        StoreManagerInterface $storeManager
    ) {
        $this->locationProductIndex = $locationProductIndex;
        $this->storeManager = $storeManager;
    }

    public function aroundIsValid(
        LocationProductValidator $subject,
        \Closure $proceed,
        Location $location,
        Product $product
    ) {
        if ($valid = $this->locationProductIndex->validateLocation(
            $location->getId(),
            $product->getId(),
            $this->storeManager->getStore()->getId()
        )) {
            return true;
        }

        return false;
    }
}
