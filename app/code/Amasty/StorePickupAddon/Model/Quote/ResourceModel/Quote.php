<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Model\Quote\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Quote\Model\ResourceModel\Quote as CoreQuoteResource;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\SalesSequence\Model\Manager;
use Amasty\StorePickupAddon\Model\SalesSequence\Order\Manager as OrderSequenceManager;
use Magento\Sales\Model\Order;

/**
 * Core Quote resource model rewrite. Class is responsible for providing current quote to order sequence manager.
 */
class Quote extends CoreQuoteResource
{
    /** @var OrderSequenceManager */
    private $orderSequenceManager;

    /**
     * @param Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param Manager $sequenceManager
     * @param OrderSequenceManager $orderSequenceManager
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        Manager $sequenceManager,
        OrderSequenceManager $orderSequenceManager,
        $connectionName = null
    ) {
        $this->orderSequenceManager = $orderSequenceManager;
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $sequenceManager, $connectionName);
    }

    public function getReservedOrderId($quote)
    {
        $this->orderSequenceManager->setQuote($quote);

        return $this->orderSequenceManager->getSequence(
            Order::ENTITY,
            $quote->getStoreId()
        )->getNextValue();
    }
}
