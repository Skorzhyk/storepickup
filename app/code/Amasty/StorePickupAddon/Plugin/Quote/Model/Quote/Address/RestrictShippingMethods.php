<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Quote\Model\Quote\Address;

use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Magento\Quote\Model\Quote\Address;
use Amasty\StorePickupWithLocator\Model\Carrier\Shipping;

/**
 * Class is responsible for restrict shipping methods.
 */
class RestrictShippingMethods
{
    /** @var QuoteProcessor */
    private $quoteProcessor;

    /**
     * @param QuoteProcessor $quoteProcessor
     */
    public function __construct(QuoteProcessor $quoteProcessor)
    {
        $this->quoteProcessor = $quoteProcessor;
    }

    /**
     * Disable pickup shipping method if quote has shipping delivery.
     *
     * @param Address $subject
     * @param array $rates
     * @return array
     */
    public function afterGetGroupedAllShippingRates(Address $subject, array $rates)
    {
        $quote = $subject->getQuote();

        foreach ($rates as $carrier => $rate) {
            if ($carrier == Shipping::SHIPPING_METHOD_CODE && !$this->quoteProcessor->onlyPickup($quote)) {
                unset($rates[$carrier]);
            }
        }

        return $rates;
    }
}
