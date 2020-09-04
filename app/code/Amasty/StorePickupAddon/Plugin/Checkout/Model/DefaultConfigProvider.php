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

use Amasty\StorePickupAddon\Model\QuoteProcessor;

/**
 * Class is responsible for providing additional information to checkout.
 */
class DefaultConfigProvider
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var QuoteProcessor */
    private $quoteProcessor;

    public function __construct(CheckoutSession $checkoutSession, QuoteProcessor $quoteProcessor)
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteProcessor = $quoteProcessor;
    }

    /**
     * Provide pickup state information.
     *
     * @param CoreProvider $subject
     * @param $config
     * @return mixed
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

        // Remove in phase 3 (use custom options).
        $itemsConfig = $config['totalsData']['items'];
        $items = $quote->getItemsCollection()->getItems();
        foreach ($itemsConfig as $key => $itemConfig) {
            $item = $items[$itemConfig['item_id']];
            $itemsConfig[$key]['delivery'] = $this->quoteProcessor->getItemDelivery($item);
        }
        $config['totalsData']['items'] = $itemsConfig;

        return $config;
    }
}
