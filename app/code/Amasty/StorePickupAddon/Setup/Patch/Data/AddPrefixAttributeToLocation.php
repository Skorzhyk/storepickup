<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Amasty\Storelocator\Model\AttributeFactory;
use Amasty\Storelocator\Model\ResourceModel\Attribute;

class AddPrefixAttributeToLocation implements DataPatchInterface
{
    /** @var Attribute */
    private $attributeResource;

    /** @var AttributeFactory */
    private $attributeFactory;

    /**
     * @param Attribute $attributeResource
     * @param AttributeFactory $attributeFactory
     */
    public function __construct(Attribute $attributeResource, AttributeFactory $attributeFactory)
    {
        $this->attributeResource = $attributeResource;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $attributeData = [
            'attribute_code' => 'order_prefix',
            'frontend_label' => 'Order Prefix',
            'frontend_input' => 'text'
        ];

        $attribute = $this->attributeFactory->create();
        $attribute->setData($attributeData);
        $this->attributeResource->save($attribute);
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
