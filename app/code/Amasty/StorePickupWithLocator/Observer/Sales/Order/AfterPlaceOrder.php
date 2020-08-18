<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Observer\Sales\Order;

use Amasty\StorePickupWithLocator\Api\Data\OrderInterface;
use Amasty\StorePickupWithLocator\Api\Data\QuoteInterface;
use Amasty\StorePickupWithLocator\Model\OrderFactory;
use Amasty\StorePickupWithLocator\Model\OrderRepository;
use Amasty\StorePickupWithLocator\Model\Quote;
use Amasty\StorePickupWithLocator\Model\QuoteRepository;
use Amasty\StorePickupWithLocator\Model\Carrier\Shipping;
use Amasty\StorePickupWithLocator\Model\Sales\AddressResolver;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AfterPlaceOrder for move date information from quote to order, 'sales_model_service_quote_submit_success' event
 */
class AfterPlaceOrder implements ObserverInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AddressResolver
     */
    private $orderAddressResolver;

    public function __construct(
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AddressResolver $orderAddressResolver
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderAddressResolver = $orderAddressResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        if (!$order = $observer->getEvent()->getOrder()) {
            return $this;
        }

        if ($order->getShippingMethod() !== Shipping::SHIPPING_NAME) {
            return $this;
        }

        $this->searchCriteriaBuilder->addFilter(QuoteInterface::QUOTE_ID, $order->getQuoteId());
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $quoteList = $this->quoteRepository->getList($searchCriteria);

        /** @var Quote $quote */
        foreach ($quoteList->getItems() as $quote) {
            $locationId = $quote->getStoreId();

            $data = [
                'order_id' => $order->getId(),
                'location_id' => $quote->getStoreId(),
                'date' => $quote->getDate(),
                'time_from' => $quote->getTimeFrom(),
                'time_to' => $quote->getTimeTo()
            ];
            $this->orderRepository->setAndSaveOrderData($data);
            $this->orderAddressResolver->setShippingInformation($order, $locationId);
        }

        return $this;
    }
}
