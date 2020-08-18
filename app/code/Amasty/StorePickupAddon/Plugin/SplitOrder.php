<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Event\ManagerInterface;
use Amasty\StorePickupAddon\Model\QuoteProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

/**
 * Class is responsible for creating orders based on items' store pickup value.
 */
class SplitOrder
{
    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var QuoteFactory */
    private $quoteFactory;

    /** @var ManagerInterface */
    private $eventManager;

    /**
     * @var QuoteProcessor
     */
    private $quoteProcessor;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param ManagerInterface $eventManager
     * @param QuoteProcessor $quoteProcessor
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        ManagerInterface $eventManager,
        QuoteProcessor $quoteProcessor
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->eventManager = $eventManager;
        $this->quoteProcessor = $quoteProcessor;
    }

    /**
     * Split order by attribute.
     *
     * @param QuoteManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param null $paymentMethod
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundPlaceOrder(QuoteManagement $subject, callable $proceed, $cartId, $paymentMethod = null)
    {
        $currentQuote = $this->quoteRepository->getActive($cartId);

        $itemGroups = $this->quoteProcessor->splitQuotes($currentQuote);
        if (count($itemGroups) <= 1) {
            return $result = $proceed($cartId, $paymentMethod);
        }

        $orders = [];
        $orderIds = [];
        foreach ($itemGroups as $quoteItems) {
            $quote = $this->quoteProcessor->createSeparateQuote(
                $currentQuote,
                $this->quoteFactory->create(),
                $quoteItems,
                $paymentMethod
            );

            try {
                $order = $this->placeOrder($currentQuote, $subject);
                $orders[] = $order;
                $orderIds[$order->getEntityId()] = $order->getIncrementId();

                if ($order === null) {
                    throw new LocalizedException(__('Please try to place the order again.'));
                }

                $this->quoteProcessor->updateSession($quote, $order, $orderIds);
            } catch (\Exception $e) {

            }
        }

        $currentQuote->setIsActive(false);

        $this->eventManager->dispatch(
            'checkout_submit_all_after',
            ['orders' => $orders, 'quote' => $currentQuote]
        );

        return $currentQuote->getId();
    }

    /**
     * Save quote.
     *
     * @param CartInterface $quote
     * @return void
     */
    private function saveQuote($quote): void
    {
        $this->quoteRepository->save($quote);
    }

    /**
     * Place an order.
     *
     * @param CartInterface $quote
     * @param QuoteManagement $quoteManagement
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function placeOrder(CartInterface $quote, QuoteManagement $quoteManagement): OrderInterface
    {
        $this->eventManager->dispatch(
            'checkout_submit_before',
            ['quote' => $quote]
        );

        $this->saveQuote($quote);

        /** @var Quote $quote */
        return $quoteManagement->submit($quote);
    }
}
