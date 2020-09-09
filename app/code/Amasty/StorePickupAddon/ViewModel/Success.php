<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Registry;

class Success implements ArgumentInterface
{
    private const SINGLE_ORDER_ID = 0;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var Registry */
    private $registry;

    private $orderIds = null;

    /**
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Registry $registry
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Registry $registry
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
    }

    /**
     * Get split orders' IDs.
     *
     * @return array|null
     */
    public function getOrderIds(): ?array
    {
        if ($this->orderIds === null) {
            $orderIds = $this->checkoutSession->getOrderIds();
            if ($orderIds !== null) {
                $this->orderIds = $orderIds;
            } else {
                $this->orderIds = $orderIds = [$this->checkoutSession->getLastRealOrderId()];
            }

            $this->checkoutSession->setOrderIds(null);
        }

        return $this->orderIds;
    }

    /**
     * Set current order as next split order.
     *
     * @param int $orderId
     * @return OrderInterface
     */
    public function setCurrentOrder(int $orderId): OrderInterface
    {
        if ($orderId != self::SINGLE_ORDER_ID) {
            $order = $this->orderRepository->get($orderId);

            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->registry->unregister('current_order');
            $this->registry->register('current_order', $order);

            return $order;
        }

        return $this->checkoutSession->getLastRealOrder();
    }
}
