<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Amasty\Storelocator\Block\Adminhtml\Location\Edit\Form\Status;
use Amasty\Storelocator\Model\Location;
use Amasty\Storelocator\Model\ResourceModel\Location\Collection;
use Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Store;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\StorePickupWithLocator\CustomerData\LocationData;

/**
 * Provide Data For CustomerData Section
 *
 */
class LocationProvider extends LocationData
{
    const SECTION_NAME = 'amasty-storepickup-data';

    const NO_AVAILABLE_LOCATIONS = 'amasty_storepickup_no_locations';

    /**
     * @var CollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TimeHandler
     */
    private $timeHandler;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Quote|null
     */
    private $quote = null;

    public function __construct(
        ConfigProvider $configProvider,
        TimeHandler $timeHandler,
        CheckoutSession $checkoutSession,
        RegionCollectionFactory $regionCollectionFactory,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        CollectionFactory $locationCollectionFactory
    ) {
        $this->configProvider = $configProvider;
        $this->timeHandler = $timeHandler;
        $this->checkoutSession = $checkoutSession;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->locationCollectionFactory = $locationCollectionFactory;
    }

    /**
     * @return array
     */
    public function getLocationCollection()
    {
        $locationCollection = $this->prepareCollection();

        $locationArray = $locationCollection->getLocationData();
        $template = $this->configProvider->getStoreTemplate();
        $regions = [];
        /** @var array $locationData */
        foreach ($locationArray as $key => &$locationData) {
            if (!empty($locationData['schedule_array']) && is_array($locationData['schedule_array'])) {
                $locationData['handled_time'] = $this->timeHandler->execute($locationData['schedule_array']);
            } else {
                $locationData['handled_time'] =
                    $this->timeHandler->generate(TimeHandler::START_TIME, TimeHandler::END_TIME);
            }

            if (empty($locationData['handled_time'])) {
                unset($locationArray[$key]);
                continue;
            }

            $state = $locationData['state'];
            $locationData['region']['region'] = $state;
            if (is_numeric($state)) {
                $regions[] = $state;
            }

            /** @var Location $location */
            $location = $locationCollection->getItemById($locationData['id']);
            $location->setTemplatesHtml();
            $locationData['details'] = $location->getPopupHtml();
            $locationData['current_timezone_time'] = $this->timeHandler->getDateTimestamp();
            $locationData['value'] = $locationData['id'];
            $locationData['label'] = $locationData['name'];
        }

        if (!empty($regions)) {
            $this->loadRegionDataForLocations($regions, $locationArray);
        }

        if (empty($locationArray)) {
            $this->checkoutSession->setStepData('checkout', self::NO_AVAILABLE_LOCATIONS, true);
        } else {
            $this->checkoutSession->setStepData('checkout', self::NO_AVAILABLE_LOCATIONS, false);
        }

        return array_values($locationArray);
    }

    /**
     * load region details and attach it to location data
     *
     * @param int[] $regions - region ids
     * @param array $locationArray
     */
    private function loadRegionDataForLocations($regions, &$locationArray)
    {
        /** @var \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection */
        $regionCollection = $this->regionCollectionFactory->create();
        $regionCollection->addFieldToFilter('main_table.region_id', ['in' => $regions])
            ->removeAllFieldsFromSelect()
            ->addFieldToSelect(['region_id', 'country_id', 'code', 'default_name'])
            ->load();

        foreach ($locationArray as &$locationData) {
            if (is_numeric($locationData['state'])) {
                /** @var \Magento\Directory\Model\Region $regionModel */
                $regionModel = $regionCollection->getItemById($locationData['state']);
                if ($regionModel === null) {
                    continue;
                }
                $locationData['region'] = [
                    'region' => $regionModel->getName(),
                    'region_id' => $regionModel->getId(),
                    'region_code' => $regionModel->getCode()
                ];
            }
        }
    }

    /**
     * @return Collection
     */
    private function prepareCollection()
    {
        $this->request->setPostValue(['sortByDistance' => true]);
        $storeId = $this->storeManager->getStore()->getId();
        /** @var Collection $locationCollection */
        $locationCollection = $this->locationCollectionFactory->create();
        $locationCollection->addFilterByStores([Store::DEFAULT_STORE_ID, $storeId]);
        $locationCollection->addDistance($locationCollection->getSelect());
        $locationCollection->addFieldToFilter('status', Status::ENABLED);

        if ($this->configProvider->isCheckProductAvailability()) {
            $locationCollection->filterLocationsByProduct($this->getQuoteProductIds(), $storeId);
        }

        return $locationCollection;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if ($this->quote === null) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * @return array|int
     */
    public function getQuoteProductIds()
    {
        $ids = [];
        if (($this->checkoutSession->getQuoteId() || $this->checkoutSession->hasQuote())
            && $this->getQuote()->hasItems()
        ) {
            $quote = $this->getQuote();
            /** @var Item $item */
            foreach ($quote->getAllItems() as $item) {
                $ids[] = $item->getProductId();
            }
        }

        return $ids;
    }
}
