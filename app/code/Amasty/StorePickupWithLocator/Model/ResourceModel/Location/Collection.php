<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model\ResourceModel\Location;

use Amasty\Base\Model\Serializer;
use Amasty\Geoip\Model\Geolocation;
use Amasty\Storelocator\Helper\Data;
use Amasty\Storelocator\Model\ConfigProvider;
use Amasty\Storelocator\Model\LocationProductValidator;
use Amasty\StorePickupWithLocator\Model\LocationProvider;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use \Amasty\StorePickupWithLocator\Model\ConfigProvider as LocatorConfigProvider;

/**
 * Validation locations by schedule
 */
class Collection extends \Amasty\Storelocator\Model\ResourceModel\Location\Collection
{
    /**
     * @var LocationProvider
     */
    private $locationData;

    /**
     * @var ConfigProvider
     */
    private $locatorConfigProvider;

    /**
     * @var \Amasty\StorePickupWithLocator\Model\ConfigProvider
     */
    private $storePickupConfigProvider;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        Registry $registry,
        ScopeConfigInterface $scope,
        Address $address,
        Request $httpRequest,
        Data $dataHelper,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Serializer $serializer,
        Geolocation $geolocation,
        ConfigProvider $configProvider,
        LocationProductValidator $locationProductValidator,
        LocationProvider $locationData,
        LocatorConfigProvider $storePickupConfigProvider,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $request,
            $registry,
            $scope,
            $httpRequest,
            $productRepository,
            $categoryRepository,
            $serializer,
            $geolocation,
            $configProvider,
            $locationProductValidator,
            $connection,
            $resource
        );
        $this->locationData = $locationData;
        $this->locatorConfigProvider = $configProvider;
        $this->storePickupConfigProvider = $storePickupConfigProvider;
    }

    protected function _renderFiltersBefore()
    {
        $this->addFieldToFilter(
            ['schedule_table.schedule', 'main_table.schedule'],
            [
                   [ // conditions for field schedule_table.schedule
                          ['like' => '%_status":"1"%']
                   ],
                   ['in' => null], // condition for field main_table.schedule
            ]
        );
        parent::_renderFiltersBefore();
    }

    /**
     * Apply filters to locations collection
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function applyDefaultFilters()
    {
        $store = $this->storeManager->getStore(true)->getId();
        $attributesFromRequest = [];

        $pageNumber = (int)$this->request->getParam('p');
        $select = $this->getSelect();

        if (!$this->storeManager->isSingleStoreMode()) {
            $this->addFilterByStores([Store::DEFAULT_STORE_ID, $store]);
        }

        $select->where('main_table.status = 1');
        $this->addDistance($select);

        $params = $this->request->getParams();

        if (isset($params['attributes'])) {
            $attributesFromRequest = $this->prepareRequestParams($params['attributes']);
        }
        $this->applyAttributeFilters($attributesFromRequest);

        $productIds = $this->locationData->getQuoteProductIds();

        if ($this->storePickupConfigProvider->isCheckProductAvailability()) {
            $this->filterLocationsByProduct($productIds, $store);
        }

        $this->setCurPage($pageNumber);
        $this->setPageSize($this->locatorConfigProvider->getPaginationLimit());
    }
}
