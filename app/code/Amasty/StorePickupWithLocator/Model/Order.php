<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Amasty\StorePickupWithLocator\Api\Data\OrderInterface;
use Amasty\StorePickupWithLocator\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Framework\Model\AbstractModel;

class Order extends AbstractModel implements OrderInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(OrderResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($entityId)
    {
        return $this->setData(self::ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getDate()
    {
        return $this->getData(self::DATE);
    }

    /**
     * @inheritDoc
     */
    public function setDate($date)
    {
        return $this->setData(self::DATE, $date);
    }

    /**
     * @inheritDoc
     */
    public function getTimeFrom()
    {
        return $this->getData(self::TIME_FROM);
    }

    /**
     * @inheritDoc
     */
    public function setTimeFrom($timeFrom)
    {
        return $this->setData(self::TIME_FROM, $timeFrom);
    }

    /**
     * @inheritDoc
     */
    public function getTimeTo()
    {
        return $this->getData(self::TIME_TO);
    }

    /**
     * @inheritDoc
     */
    public function setTimeTo($timeTo)
    {
        return $this->setData(self::TIME_TO, $timeTo);
    }
}
