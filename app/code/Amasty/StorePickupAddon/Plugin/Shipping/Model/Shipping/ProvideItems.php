<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Shipping\Model\Shipping;

use Magento\Shipping\Model\Shipping;
use Magento\Quote\Model\Quote\Item;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Amasty\StorePickupAddon\Model\QuoteProcessor;

class ProvideItems
{
    private const SUBTOTAL = 'subtotal';

    private const SUBTOTAL_WITH_DISCOUNT = 'subtotal_with_discount';

    private const QUANTITY = 'qty';

    /** @var QuoteProcessor */
    private $quoteProcessor;

    public function __construct(QuoteProcessor $quoteProcessor)
    {
        $this->quoteProcessor = $quoteProcessor;
    }

    public function beforeCollectRates(Shipping $subject, $request)
    {
        $shippingItems = [];

        foreach ($request->getAllItems() as $item) {
            if ($this->quoteProcessor->getItemDelivery($item) == Delivery::SHIPPING) {
                $shippingItems[] = $item;
            }
        }

        $requestUpdatedData = $this->collectItemsData($shippingItems);

        $request->setPackageValue($requestUpdatedData[self::SUBTOTAL]);
        $request->setPackageValueWithDiscount($requestUpdatedData[self::SUBTOTAL_WITH_DISCOUNT]);
        $request->setPackageQty($requestUpdatedData[self::QUANTITY]);
        $request->setAllItems($shippingItems);

        return [$request];
    }

    private function collectItemsData(array $shippingItems): array
    {
        $subtotal = 0;
        $subtotalWithDiscount = 0;
        $qty = 0;
        /** @var Item $item $item */
        foreach ($shippingItems as $item) {
            $subtotal += $item->getBaseRowTotal();
            $subtotalWithDiscount += $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
            $qty += $item->getQty();
        }

        return [
            self::SUBTOTAL => $subtotal,
            self::SUBTOTAL_WITH_DISCOUNT => $subtotalWithDiscount,
            self::QUANTITY => $qty
        ];
    }
}
