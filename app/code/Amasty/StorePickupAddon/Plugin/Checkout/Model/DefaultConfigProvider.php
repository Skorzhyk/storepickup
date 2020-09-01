<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Checkout\Model;

use Magento\Checkout\Model\DefaultConfigProvider as CoreProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class is responsible for providing additional information to checkout.
 */
class DefaultConfigProvider
{
    private $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Provide pickup state information.
     *
     * @param CoreProvider $subject
     * @param $config
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGetConfig(CoreProvider $subject, $config)
    {
        $quote = $this->checkoutSession->getQuote();

        // Remove in phase 3 (use custom options).
        $config['quoteData']['only_pickup'] = (int)$quote->getOnlyPickup();
        if ($quote->getIsPickupDataCleared() !== null) {
            $config['quoteData']['is_pickup_data_cleared'] = (int)$quote->getIsPickupDataCleared();
        }
        // Remove in phase 3 (use custom options).
        if ($quote->getDelivery() !== null) {
            $config['quoteData']['delivery'] = (int)$quote->getDelivery();
        }

        return $config;
    }
}
