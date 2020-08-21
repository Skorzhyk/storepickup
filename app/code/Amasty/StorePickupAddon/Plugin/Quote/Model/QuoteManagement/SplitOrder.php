<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Plugin\Quote\Model\QuoteManagement;

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
use Psr\Log\LoggerInterface;

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

    /** @var QuoteProcessor */
    private $quoteProcessor;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param ManagerInterface $eventManager
     * @param QuoteProcessor $quoteProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        ManagerInterface $eventManager,
        QuoteProcessor $quoteProcessor,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->eventManager = $eventManager;
        $this->quoteProcessor = $quoteProcessor;
        $this->logger = $logger;
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
                $this->quoteFactory->create(),
                $quoteItems,
                $paymentMethod
            );

            try {
                $order = $this->placeOrder($quote, $subject);
                $orders[] = $order;
                $orderIds[$order->getEntityId()] = $order->getIncrementId();

                if ($order === null) {
                    throw new LocalizedException(__('Please try to place the order again.'));
                }

                $this->quoteProcessor->updateSession($quote, $order, $orderIds);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
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

        $this->quoteRepository->save($quote);

        /** @var Quote $quote */
        return $quoteManagement->submit($quote);
    }
}
