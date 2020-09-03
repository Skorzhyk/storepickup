<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Block\Sales\Order;

use Magento\Sales\Block\Order\Totals as CoreBlock;

/**
 * Core Totals block layout rewrite.
 */
class Totals extends CoreBlock
{
    /**
     * @inheritDoc
     */
    protected function _beforeToHtml()
    {
        if ($this->_coreRegistry->registry('current_order')) {
            $this->setOrder($this->_coreRegistry->registry('current_order'));
        }
        return parent::_beforeToHtml();
    }
}
