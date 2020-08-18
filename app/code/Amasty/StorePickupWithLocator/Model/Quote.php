<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Amasty\StorePickupWithLocator\Api\Data\QuoteInterface;
use Amasty\StorePickupWithLocator\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Framework\Model\AbstractModel;

class Quote extends AbstractModel implements QuoteInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(QuoteResourceModel::class);
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
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
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
    public function getAddressId()
    {
        return $this->getData(self::ADDRESS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAddressId($addressId)
    {
        return $this->setData(self::ADDRESS_ID, $addressId);
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
