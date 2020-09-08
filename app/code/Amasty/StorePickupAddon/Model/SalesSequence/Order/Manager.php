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
use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\SalesSequence\Model\Sequence;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Core sequence Manager rewrite. Class is responsible for providing order prefix for Pickup orders.
 */
class Manager
{
    private const SHIPPING_ORDER_PREFIX = 'SHP';

    /** @var ResourceSequenceMeta */
    private $resourceSequenceMeta;

    /** @var SequenceFactory */
    private $sequenceFactory;

    /** @var QuoteProcessor */
    private $quoteProcessor;

    /** @var LocationCollectionFactory */
    private $locationCollectionFactory;

    /** @var QuoteCollectionFactory */
    private $quoteCollectionFactory;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var AdapterInterface */
    private $resourceConnection;

    private $quote = null;

    private $childrenQuoteCollection = null;

    private $meta;

    /**
     * @param ResourceSequenceMeta $resourceSequenceMeta
     * @param SequenceFactory $sequenceFactory
     * @param QuoteProcessor $quoteProcessor
     * @param LocationCollectionFactory $locationCollectionFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceSequenceMeta $resourceSequenceMeta,
        SequenceFactory $sequenceFactory,
        QuoteProcessor $quoteProcessor,
        LocationCollectionFactory $locationCollectionFactory,
        QuoteCollectionFactory $quoteCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->resourceSequenceMeta = $resourceSequenceMeta;
        $this->sequenceFactory = $sequenceFactory;
        $this->quoteProcessor = $quoteProcessor;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->resourceConnection = $resourceConnection->getConnection();
    }

    /**
     * Get increment ID for next order (with considering split orders).
     *
     * @return string
     */
    public function getNextValue(): string
    {
        $orderGroup = $this->getOrderGroup();

        if ($orderGroup !== null) {
            $orderGroupSize = $orderGroup->getSize();

            $this->meta->getActiveProfile()->setSuffix('-' . ($orderGroupSize + 1));

            if ($orderGroupSize > 0) {
                $lastIncrementIdQuery = 'SELECT MAX(sequence_value) FROM ' . $this->meta->getSequenceTable();
                $groupIncrementId = (int)$this->resourceConnection->fetchOne($lastIncrementIdQuery);

                return sprintf(
                    Sequence::DEFAULT_PATTERN,
                    $this->meta->getActiveProfile()->getPrefix(),
                    $groupIncrementId,
                    $this->meta->getActiveProfile()->getSuffix()
                );
            }
        }

        return $this->getSequence()->getNextValue();
    }

    /**
     * Set current quote.
     *
     * @param CartInterface $quote
     * @return Manager
     */
    public function setQuote(CartInterface $quote): Manager
    {
        $this->quote = $quote;

        $parentQuoteId = $this->quote->getParentQuoteId();
        if ($parentQuoteId !== null) {
            $childrenQuoteCollection = $this->quoteCollectionFactory->create();
            $childrenQuoteCollection->addFieldToFilter(QuoteProcessor::PARENT_QUOTE_ID, ['eq' => $parentQuoteId]);

            $this->childrenQuoteCollection = $childrenQuoteCollection;
        }

        return $this;
    }

    /**
     * @param $entityType
     * @param $storeId
     * @return Manager
     * @throws LocalizedException
     */
    public function setMeta($entityType, $storeId): Manager
    {
        $this->meta = $this->resourceSequenceMeta->loadByEntityTypeAndStore(
            $entityType,
            $storeId
        );

        $delivery = $this->quoteProcessor->getQuoteDelivery($this->quote);
        if ($delivery != Delivery::SHIPPING) {
            $locations = $this->locationCollectionFactory->create()->getLocationData();
            foreach ($locations as $location) {
                if ((int)$location['id'] == $delivery) {
                    if (
                        array_key_exists('attributes', $location)
                        && array_key_exists(Delivery::ORDER_PREFIX, $location['attributes'])
                    ) {
                        $prefix = $location['attributes'][Delivery::ORDER_PREFIX]['value'];
                        if ($prefix !== null && $prefix != '') {
                            $this->meta->getActiveProfile()->setPrefix($prefix . '-');
                        }
                    }

                    break;
                }
            }
        } else {
            $this->meta->getActiveProfile()->setPrefix(self::SHIPPING_ORDER_PREFIX . '-');
        }

        return $this;
    }

    /**
     * Returns sequence for given entityType and store.
     *
     * @return SequenceInterface
     */
    private function getSequence()
    {
        return $this->sequenceFactory->create(['meta' => $this->meta]);
    }

    /**
     * Returns first order ID from current order group.
     *
     * @return OrderCollection|null
     */
    private function getOrderGroup(): ?OrderCollection
    {
        if ($this->childrenQuoteCollection !== null) {
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToFilter('quote_id', ['in' => $this->childrenQuoteCollection->getAllIds()]);

            return $orderCollection;
        }

        return null;
    }
}
