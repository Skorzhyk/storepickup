<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Quote|null
     */
    private $quote = null;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'amastyStorePickupConfig' => [
                'websiteId' => $this->getQuote()->getStore()->getWebsiteId(),
                'storeId' => $this->getQuote()->getStore()->getStoreId()
            ]
        ];
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    private function getQuote()
    {
        if ($this->quote === null) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }
}
