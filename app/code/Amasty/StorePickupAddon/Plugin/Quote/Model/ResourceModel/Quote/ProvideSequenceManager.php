<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Quote\Model\ResourceModel\Quote;

use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Amasty\StorePickupAddon\Model\SalesSequence\Order\Manager as OrderSequenceManager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class is responsible for using custom sequence manager.
 */
class ProvideSequenceManager
{
    /** @var OrderSequenceManager */
    private $orderSequenceManager;

    /**
     * @param OrderSequenceManager $orderSequenceManager
     */
    public function __construct(OrderSequenceManager $orderSequenceManager)
    {
        $this->orderSequenceManager = $orderSequenceManager;
    }

    /**
     * Set current quote to sequence manager
     *
     * @param QuoteResource $subject
     * @param callable $proceed
     * @param CartInterface $quote
     * @return string
     * @throws LocalizedException
     */
    public function aroundGetReservedOrderId(QuoteResource $subject, callable $proceed, CartInterface $quote)
    {
        $this->orderSequenceManager
            ->setQuote($quote)
            ->setMeta(Order::ENTITY, $quote->getStoreId());

        return $this->orderSequenceManager->getNextValue();
    }
}
