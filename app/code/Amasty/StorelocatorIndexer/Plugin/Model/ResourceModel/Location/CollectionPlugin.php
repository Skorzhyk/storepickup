<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorelocatorIndexer
 */


namespace Amasty\StorelocatorIndexer\Plugin\Model\ResourceModel\Location;

use Amasty\Storelocator\Model\ResourceModel\Location\Collection;
use Amasty\StorelocatorIndexer\Model\ResourceModel\LocationProductIndex;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class CollectionPlugin
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

    public function aroundFilterLocationsByProduct(
        Collection $subject,
        \Closure $proceed,
        $productId,
        $storeIds
    ) {
        $locationIds = [];
        $fields = $this->locationProductIndex->getLocationsByProduct($productId, [Store::DEFAULT_STORE_ID, $storeIds]);
        foreach ($fields as $field) {
            $locationIds[] = $field[LocationProductIndex::LOCATION_ID];
        }
        $subject->addFieldToFilter('main_table.id', ['in' => $locationIds]);
    }

    public function aroundFilterLocationsByCategory(
        Collection $subject,
        \Closure $proceed,
        $categoryId,
        $storeIds
    ) {
        $locationIds = [];
        $fields = $this->locationProductIndex->getLocationsByCategory(
            $categoryId,
            [Store::DEFAULT_STORE_ID, $storeIds]
        );
        foreach ($fields as $field) {
            $locationIds[] = $field[LocationProductIndex::LOCATION_ID];
        }
        $subject->addFieldToFilter('main_table.id', ['in' => $locationIds]);
    }
}
