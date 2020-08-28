<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Quote\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item\OptionFactory;

/**
 * Class is responsible for creating orders based on items' store pickup value.
 */
class SetDeliveryOption
{
    private $serializer;

    private $optionFactory;

    public function __construct(Json $serializer, OptionFactory $optionFactory)
    {
        $this->serializer = $serializer;
        $this->optionFactory = $optionFactory;
    }

    public function afterAddProduct(CartInterface $quote, Item $item)
    {
        $product = $item->getProduct();
        $deliveryAttribute = $product->getCustomAttribute(Delivery::ATTRIBUTE_KEY);
        $delivery = Delivery::SHIPPING;
        if ($deliveryAttribute !== null) {
            $deliveryAttributeValue = $deliveryAttribute->getValue();
            $deliveryOptions = explode(',', $deliveryAttributeValue);
            $delivery = count($deliveryOptions) > 0 ? $deliveryOptions[0] : Delivery::SHIPPING;
        }

        $additionalOptions = $item->getOptionByCode('additional_options');
        if ($additionalOptions === null) {
            $additionalOptions = [];
        }
        $additionalOptions[] = [
            Delivery::ATTRIBUTE_KEY => [
                'label' => Delivery::OPTION_LABEL,
                'value' => (int)$delivery
            ]
        ];

        $deliveryOption = $this->optionFactory->create();
        $deliveryOption
            ->setProductId($product->getId())
            ->setCode('additional_options')
            ->setValue($this->serializer->serialize($additionalOptions));

        $item->addOption($deliveryOption);
    }
}
