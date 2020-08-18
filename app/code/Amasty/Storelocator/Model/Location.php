<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model;

use Amasty\Storelocator\Api\ReviewRepositoryInterface;
use Amasty\Storelocator\Helper\Data;
use Amasty\Storelocator\Ui\DataProvider\Form\ScheduleDataProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rule\Model\Condition\Sql\Builder;
use Magento\Store\Model\Store;

/**
 * Class Location
 *
 * Define location and actions with it
 */
class Location extends \Magento\Rule\Model\AbstractModel
{
    const CACHE_TAG = 'amlocator_location';
    const EVENT_PREFIX = 'amasty_storelocator_location';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = self::EVENT_PREFIX;

    /**
     * Store rule actions model
     *
     * @var \Magento\Rule\Model\Action\Collection
     */
    protected $_actions;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Product\CombineFactory
     */
    protected $condProdCombineF;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Product\Combine
     */
    protected $combineProduct;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Amasty\Base\Model\Serializer
     */
    protected $serializer;

    /**
     * @var \Amasty\Storelocator\Model\Rule\Condition\Product\CombineFactory
     */
    protected $locatorCondition;

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var array
     */
    public $dayNames;

    /**
     * @var ReviewRepositoryInterface
     */
    private $reviewRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    private $filterProvider;

    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $productIds;

    /**
     * Valid product ids for location
     *
     * @var array
     */
    protected $validProductIds;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Iterator
     */
    protected $resourceIterator;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var Builder
     */
    private $sqlBuilder;

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var ConfigHtmlConverter
     */
    private $configHtmlConverter;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\Base\Model\Serializer $serializer,
        \Magento\SalesRule\Model\Rule\Condition\Product\CombineFactory $_condProdCombineF,
        \Magento\CatalogRule\Model\Rule\Condition\CombineFactory $locatorConditionFactory,
        ImageProcessor $imageProcessor,
        ConfigProvider $configProvider,
        Data $dataHelper,
        ReviewRepositoryInterface $reviewRepository,
        CustomerRepositoryInterface $customerRepository,
        Escaper $escaper,
        ConfigHtmlConverter $configHtmlConverter,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Model\ResourceModel\Iterator $resourceIterator,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Builder $sqlBuilder,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->combineProduct = $_condProdCombineF->create();
        $this->locatorCondition = $locatorConditionFactory->create();
        $this->imageProcessor = $imageProcessor;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            null,
            null,
            $data
        );
        $this->configProvider = $configProvider;
        $this->dataHelper = $dataHelper;
        $this->dayNames = $this->dataHelper->getDaysNames();
        $this->reviewRepository = $reviewRepository;
        $this->customerRepository = $customerRepository;
        $this->escaper = $escaper;
        $this->filterProvider = $filterProvider;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceIterator = $resourceIterator;
        $this->productFactory = $productFactory;
        $this->sqlBuilder = $sqlBuilder;
        $this->configHtmlConverter = $configHtmlConverter;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Storelocator\Model\ResourceModel\Location::class);
    }

    /**
     * Get array of product ids which are matched by location
     * Initializing by Indexer. Stored in Index
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMatchingProductIds()
    {
        if ($this->productIds === null) {
            $this->productIds = [];
            $this->setCollectedAttributes([]);
            /** @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
            $productCollection = $this->productCollectionFactory->create();
            if (!empty($this->getStoreIds())) {
                $productCollection->addWebsiteFilter($this->getWebsiteIds());
                $storeIds = array_values($this->getStoreIds());
            } else {
                $storeIds = [Store::DEFAULT_STORE_ID];
            }
            if (!$this->getProductConditions()->getActions()) {
                $productIds = $productCollection->getAllIds();
                foreach ($productIds as $productId) {
                    foreach ($storeIds as $storeId) {
                        $this->productIds[$productId][$storeId] = true;
                    }
                }

                return $this->productIds;
            }

            $conditions = $this->getProductConditions();
            $conditions->collectValidatedAttributes($productCollection);
            $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);

            foreach ($productCollection->getAllIds() as $productId) {
                foreach ($storeIds as $storeId) {
                    $this->productIds[$productId][$storeId] = true;
                }
            }
        }

        return $this->productIds;
    }

    /**
     * @return \Magento\Rule\Model\Condition\Combine
     */
    public function getProductConditions()
    {
        $conditionsObject = $this->getActions();
        $conditions = $conditionsObject->getConditions();
        $productCondition = [];
        foreach ($conditions as $condition) {
            if ($condition['form_name'] == 'catalog_rule_form') {
                $productCondition[] = $condition;
            }
        }
        $conditionsObject->setConditions($productCondition);

        return $conditionsObject;
    }

    /**
     * @param $product
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isValidForLocation($product)
    {
        $productId = $product->getId();
        foreach ($product->getStoreIds() as $storeId) {
            $product->setStoreId($storeId);
            if (!$this->getProductConditions()->getActions() || $this->getProductConditions()->validate($product)) {
                $this->validProductIds[$productId][$storeId] = true;
            }
        }

        return $this->validProductIds;
    }

    /**
     * Get location associated store Ids
     * Note: Location can be for All Store View (sore_ids = array(0 => '0'))
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getStoreIds()
    {
        $storesArray = explode(',', $this->_getData('stores'));

        return array_filter($storesArray);
    }

    /**
     * Get location associated website Ids
     * Note: Location can be for All Store View (sore_ids = array(0))
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWebsiteIds()
    {
        if (!$this->hasWebsiteIds()) {
            $stores = $this->getStoreIds();
            $websiteIds = [];
            foreach ($stores as $storeId) {
                $websiteIds[] = $this->storeManager->getStore($storeId)->getWebsiteId();
            }
            $this->setData('website_ids', array_unique($websiteIds));
        }

        return $this->_getData('website_ids');
    }

    public function getConditionsInstance()
    {
        return $this->combineProduct;
    }

    public function getActionsInstance()
    {
        return $this->locatorCondition;
    }

    /**
     * @return string
     */
    public function getMarkerMediaUrl()
    {
        if ($this->getMarkerImg()) {
            return $this->imageProcessor->getImageUrl(
                [ImageProcessor::AMLOCATOR_MEDIA_PATH, $this->getId(), $this->getMarkerImg()]
            );
        }
    }

    /**
     * Getting working time for location
     *
     * @param string $dayName
     *
     * @return array
     */
    public function getWorkingTime($dayName)
    {
        $scheduleArray = $this->getDaySchedule($dayName);
        $periods = [];
        if (array_shift($scheduleArray) == 0) {
            return [$this->getDayName($dayName) => $this->configProvider->getClosedText()];
        }

        $periods[$this->getDayName($dayName)] = $this->getFromToTime(
            $scheduleArray[ScheduleDataProvider::OPEN_TIME],
            $scheduleArray[ScheduleDataProvider::CLOSE_TIME]
        );

        // not show similar from/to times for break
        if ($scheduleArray[ScheduleDataProvider::START_BREAK_TIME]
            != $scheduleArray[ScheduleDataProvider::END_BREAK_TIME]
        ) {
            $periods[$this->configProvider->getBreakText()] = $this->getFromToTime(
                $scheduleArray[ScheduleDataProvider::START_BREAK_TIME],
                $scheduleArray[ScheduleDataProvider::END_BREAK_TIME]
            );
        }

        return $periods;
    }

    /**
     * @return string
     */
    public function getWorkingTimeToday()
    {
        // getting current day
        $currentDate = $this->_localeDate->date();
        $currentDay = strtolower($currentDate->format('l'));
        $todaySchedule = $this->getDaySchedule($currentDay);

        if (array_shift($todaySchedule) == 0) {
            return $this->configProvider->getClosedText();
        }

        return $this->getFromToTime(
            $todaySchedule[ScheduleDataProvider::OPEN_TIME],
            $todaySchedule[ScheduleDataProvider::CLOSE_TIME]
        );
    }

    /**
     * @param string $dayName
     *
     * @return array
     */
    public function getDaySchedule($dayName)
    {
        $schedule = $this->getSchedule();

        if (array_key_exists($dayName, $schedule)) {
            $scheduleKey = strtolower($this->dayNames[$dayName]->getText());
        } else {
            // getting day of the week for compatibility with old module versions
            $scheduleKey = date("N", strtotime($dayName));
        }

        return $schedule[$scheduleKey];
    }

    /**
     * @param string $dayName
     *
     * @return string
     */
    public function getDayName($dayName)
    {
        if (array_key_exists($dayName, $this->dayNames)) {
            $dayName = $this->dayNames[$dayName]->getText();
        } else {
            $dayName = date('l', strtotime("Sunday + $dayName days"));
        }

        return $dayName;
    }

    /**
     * Getting from/to time
     *
     * @param array $from
     * @param array $to
     *
     * @return string
     */
    public function getFromToTime($from, $to)
    {
        $from = implode(':', $from);
        $to = implode(':', $to);
        $needConvertTime = $this->configProvider->getConvertTime();
        if ($needConvertTime) {
            $from = date("g:i a", strtotime($from));
            $to = date("g:i a", strtotime($to));
        }

        return implode(' - ', [$from, $to]);
    }

    private function getSchedule()
    {
        if ($this->getScheduleString()) {
            return $this->serializer->unserialize($this->getScheduleString());
        }
    }

    /**
     * @return array|bool
     */
    public function getLocationReviews()
    {
        $locationId = $this->getId();

        $reviews = $this->reviewRepository->getApprovedByLocationId($locationId);
        $result = [];

        if ($reviews) {
            /** @var \Amasty\Storelocator\Model\Review $review */
            foreach ($reviews as $review) {
                try {
                    $customer = $this->customerRepository->getById($review->getCustomerId());
                    $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
                } catch (NoSuchEntityException $e) {
                    $customerName = 'Anonymus';
                    continue;
                }
                array_push(
                    $result,
                    [
                        'name'         => $customerName,
                        'review'       => $review->getReviewText(),
                        'rating'       => $review->getRating(),
                        'published_at' => $review->getPublishedAt()
                    ]
                );
            }

            return $result;
        } else {
            return false;
        }
    }

    /**
     * @return bool|int
     */
    public function getLocationAverageRating()
    {
        $locationId = $this->getId();

        $reviews = $this->reviewRepository->getApprovedByLocationId($locationId);
        $rating = 0;
        $count = 0;

        if ($reviews) {
            /** @var \Amasty\Storelocator\Model\Review $review */
            foreach ($reviews as $review) {
                $rating += (int)$review->getRating();
                $count++;
            }

            return $rating / $count;
        } else {
            return false;
        }
    }

    /**
     * return string
     */
    public function getDateFormat()
    {
        $this->_localeDate->getDateFormat();
    }

    /**
     * Set templates html
     */
    public function setTemplatesHtml()
    {
        $this->getResource()->setAttributesData($this);

        $this->configHtmlConverter->setHtml($this);
    }

    /**
     * Get full description for location page
     *
     * @return string
     */
    public function getLocationDescription()
    {
        return $this->filterProvider->getPageFilter()->filter($this->getDescription());
    }

    /**
     * Retrieve rule actions model
     *
     * @return \Magento\Rule\Model\Action\Collection
     */
    public function getActions()
    {
        if (!$this->_actions) {
            $this->_resetActions();
        }

        // Load rule actions if it is applicable
        if ($this->hasActionsSerialized()) {
            $actions = $this->getActionsSerialized();
            if (!empty($actions)) {
                $actions = $this->serializer->unserialize($actions);
                if (is_array($actions) && !empty($actions)) {
                    $this->_actions->loadArray($actions);
                }
            }
            $this->unsActionsSerialized();
        }

        return $this->_actions;
    }

    public function activate()
    {
        $this->setStatus(1);
        $this->save();

        return $this;
    }

    public function inactivate()
    {
        $this->setStatus(0);
        $this->save();

        return $this;
    }

    /**
     * Set flags for saving new location
     */
    public function setModelFlags()
    {
        $this->getResource()->setResourceFlags();
    }
}
