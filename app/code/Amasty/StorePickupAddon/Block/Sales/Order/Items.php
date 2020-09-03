<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Block\Sales\Order;

use Magento\Sales\Block\Order\Items as CoreItems;

/**
 * Core Items block layout rewrite.
 */
class Items extends CoreItems
{
    /**
     * @inheritDoc
     */
    protected function _beforeToHtml()
    {
        $this->_prepareLayout();
        return parent::_beforeToHtml();
    }
}
