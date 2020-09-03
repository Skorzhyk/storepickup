<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Amasty\Storelocator\Model\ResourceModel\Location\Collection as LocationCollection;

class Delivery extends AbstractSource
{
    public const SHIPPING = 0;

    public const ATTRIBUTE_KEY = 'delivery';

    public const OPTION_LABEL = 'Delivery';

    public const ORDER_PREFIX = 'order_prefix';

    private const SHIPPING_LABEL = 'Shipping';

    /** @var LocationCollection */
    private $locationCollection;

    public function __construct(LocationCollection $locationCollection)
    {
        $this->locationCollection = $locationCollection;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $this->_options = [];
        $this->_options[] = ['label' => self::SHIPPING_LABEL, 'value' => self::SHIPPING];

        $this->locationCollection
            ->addFieldToSelect('id')
            ->addFieldToSelect('name');

        if ($this->locationCollection->count() > 0) {
            foreach ($this->locationCollection->getItems() as $location) {
                $this->_options[] = ['label' => $location->getName(), 'value' => $location->getId()];
            }
        }

        return $this->_options;
    }
}
