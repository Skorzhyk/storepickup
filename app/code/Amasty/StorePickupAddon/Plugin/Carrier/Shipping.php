<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Carrier;

use Amasty\StorePickupWithLocator\Model\Carrier\Shipping as PickupCarrier;
use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class is responsible for providing additional information to checkout.
 */
class Shipping
{
    /** @var QuoteProcessor */
    private $quoteProcessor;

    /** @var CheckoutSession */
    private $checkoutSession;

    public function __construct(QuoteProcessor $quoteProcessor, CheckoutSession $checkoutSession)
    {
        $this->quoteProcessor = $quoteProcessor;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Disable pickup method if quote has shipping delivery.
     *
     * @param PickupCarrier $subject
     * @param $result
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterIsActive(PickupCarrier $subject, $result)
    {
        $quote = $this->checkoutSession->getQuote();

        return $result ? $this->quoteProcessor->onlyPickup($quote) : $result;
    }
}
