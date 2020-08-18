<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Amasty\StorePickupWithLocator\Api\GuestLocationPickupValuesInterface;
use Amasty\StorePickupWithLocator\Api\LocationPickupValuesInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestLocationPickupValues implements GuestLocationPickupValuesInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var LocationPickupValuesInterface
     */
    private $locationPickupValues;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LocationPickupValuesInterface $locationPickupValues
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->locationPickupValues = $locationPickupValues;
    }

    /**
     * @param string $cartId
     * @param int $locationId
     * @param string|null $date
     * @param string|null $timePeriod
     *
     * @return mixed
     */
    public function saveSelectedPickupValues(
        $cartId,
        $locationId,
        $date = null,
        $timePeriod = null
    ) {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->locationPickupValues->saveSelectedPickupValues(
            $quoteIdMask->getQuoteId(),
            $locationId,
            $date,
            $timePeriod
        );
    }
}
