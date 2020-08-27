<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Amasty\StorePickupAddon\Model\ShippingProvider;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Amasty\StorePickupWithLocator\Model\Carrier\Shipping;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class CollectPickupShipping implements ObserverInterface
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var QuoteProcessor */
    private $quoteProcessor;

    /** @var ShippingProvider */
    private $shippingProvider;

    private $quoteRepository;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param QuoteProcessor $quoteProcessor
     * @param ShippingProvider $shippingProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteProcessor $quoteProcessor,
        ShippingProvider $shippingProvider,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteProcessor = $quoteProcessor;
        $this->shippingProvider = $shippingProvider;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        try {
            $quote = $this->checkoutSession->getQuote();

            $shippingAddress = $quote->getShippingAddress();

            $isShipping = false;
            foreach ($quote->getAllVisibleItems() as $item) {
                $delivery = $this->quoteProcessor->getItemDelivery($item);
                if ($delivery == Delivery::SHIPPING) {
                    $isShipping = true;
                }
            }

            if (
                $isShipping === false
                && isset($delivery)
                && $quote->getShippingAddress()->getShippingMethod() != Shipping::SHIPPING_NAME
            ) {
                $this->shippingProvider->collectPickupInformation($quote, $delivery);
            } elseif ($isShipping && $quote->getShippingAddress()->getShippingMethod() == Shipping::SHIPPING_NAME) {
                $this->shippingProvider->collectPickupInformation($quote);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function isAddressRefreshed(Address $address): bool
    {
        return $address->get
    }
}
