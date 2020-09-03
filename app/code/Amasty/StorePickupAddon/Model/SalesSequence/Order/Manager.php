<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Model\SalesSequence\Order;

use Magento\SalesSequence\Model\SequenceFactory;
use Magento\SalesSequence\Model\ResourceModel\Meta as ResourceSequenceMeta;
use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\Quote\Api\Data\CartInterface;
use Amasty\Storelocator\Model\LocationFactory;
use Amasty\Storelocator\Model\ResourceModel\Location as LocationResource;
use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Amasty\Storelocator\Model\ResourceModel\Location\Collection as LocationCollection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Core sequence Manager rewrite. Class is responsible for providing order prefix for Pickup orders.
 */
class Manager
{
    /** @var ResourceSequenceMeta */
    private $resourceSequenceMeta;

    /** @var SequenceFactory */
    private $sequenceFactory;

    /** @var QuoteProcessor */
    private $quoteProcessor;

    /** @var LocationCollection */
    private $locationCollection;

    private $quote = null;

    /**
     * @param ResourceSequenceMeta $resourceSequenceMeta
     * @param SequenceFactory $sequenceFactory
     * @param QuoteProcessor $quoteProcessor
     * @param LocationCollection $locationCollection
     */
    public function __construct(
        ResourceSequenceMeta $resourceSequenceMeta,
        SequenceFactory $sequenceFactory,
        QuoteProcessor $quoteProcessor,
        LocationCollection $locationCollection
    ) {
        $this->resourceSequenceMeta = $resourceSequenceMeta;
        $this->sequenceFactory = $sequenceFactory;
        $this->quoteProcessor = $quoteProcessor;
        $this->locationCollection = $locationCollection;
    }

    /**
     * Returns sequence for given entityType and store.
     *
     * @param $entityType
     * @param $storeId
     * @return SequenceInterface
     * @throws LocalizedException
     */
    public function getSequence($entityType, $storeId)
    {
        $meta = $this->resourceSequenceMeta->loadByEntityTypeAndStore(
            $entityType,
            $storeId
        );

        $delivery = $this->quoteProcessor->getQuoteDelivery($this->quote);
        if ($delivery != Delivery::SHIPPING) {
            $locations = $this->locationCollection->getLocationData();
            foreach ($locations as $location) {
                if ($location->getId() == $delivery) {
                    $x = 11;
//                    if ($location->getOrderPrefix() !== null) {
//                        $meta->setPrefix($location->getOrderPrefix());
//                    }
                }
            }
        }

        return $this->sequenceFactory->create(['meta' => $meta]);
    }

    /**
     * Set current quote.
     *
     * @param CartInterface $quote
     * @return void
     */
    public function setQuote(CartInterface $quote): void
    {
        $this->quote = $quote;
    }
}
