<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorelocatorIndexer
 */


namespace Amasty\StorelocatorIndexer\Model\Indexer\Location;

use Amasty\Storelocator\Helper\Data;
use Amasty\Storelocator\Model\Location;
use Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Amasty\StorelocatorIndexer\Model\Indexer\AbstractIndexBuilder;
use Amasty\StorelocatorIndexer\Model\ResourceModel\LocationProductIndex;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class IndexBuilder extends AbstractIndexBuilder
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Customer[]
     */
    protected $loadedCustomers;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Data
     */
    protected $storelocatorHelper;

    /**
     * @var LocationProductIndex
     */
    protected $locationProduct;

    public function __construct(
        LocationCollectionFactory $locationCollectionFactory,
        LoggerInterface $logger,
        Data $storelocatorHelper,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepository $productRepository,
        LocationProductIndex $locationProduct,
        $batchSize = 1000
    ) {
        $this->storelocatorHelper = $storelocatorHelper;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->locationProduct = $locationProduct;
        parent::__construct($locationCollectionFactory, $logger, $productCollectionFactory, $batchSize);
    }

    /**
     * @param array $ids
     *
     * @throws \Exception
     */
    protected function doReindexByIds($ids)
    {
        $this->locationProduct->deleteByIds($ids);
        $locationCollection = $this->getAllLocations();
        $locationCollection->addFieldToFilter('id', ['in' => $ids])->addFieldToFilter('status', 1);

        /** @var Location $location */
        foreach ($locationCollection->getItems() as $location) {
            $productIds = $location->getMatchingProductIds();
            $this->updateLocationIndex($location, $productIds);
        }
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function doReindexFull()
    {
        $this->locationProduct->clearIndex();
        $locations = $this->getAllLocations()->getItems();

        /** @var Location $location */
        foreach ($locations as $k => &$location) {
            $this->updateLocationProductsIndex($location);
            unset($locations[$k]);
        }
    }

    /**
     * @param Location $location
     *
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function updateLocationProductsIndex(Location $location)
    {
        $rows = [];
        $count = 0;
        $locationId = $location->getId();

        if (!$location->getStatus()) {
            return $this;
        }

        foreach ($location->getMatchingProductIds() as $productId => $stores) {
            foreach ($stores as $storeId => $value) {
                $rows[] = [
                    LocationProductIndex::LOCATION_ID => $locationId,
                    LocationProductIndex::PRODUCT_ID => $productId,
                    LocationProductIndex::STORE_ID => $storeId
                ];

                if (++$count == $this->batchSize) {
                    $this->locationProduct->insertData($rows);
                    $rows = [];
                    $count = 0;
                }
            }
        }

        if (!empty($rows)) {
            $this->locationProduct->insertData($rows);
        }

        return $this;
    }
}
