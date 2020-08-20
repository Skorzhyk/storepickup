<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Amasty\StorePickupAddon\Model\Config\Source\Delivery;
use Magento\Quote\Model\Quote\Item;

/**
 * Class is responsible for splitting quote based on items' store pickup value.
 */
class QuoteProcessor
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(CheckoutSession $checkoutSession, CartRepositoryInterface $quoteRepository)
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Split quotes by items' store pickup value.
     *
     * @param CartInterface $quote
     * @return array
     */
    public function splitQuotes(CartInterface $quote): array
    {
        $groups = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $isDeliverySpecified = false;

            $productOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
            if (array_key_exists('options', $productOptions) && $productOptions['options'] > 0) {
                foreach ($productOptions['options'] as $option) {
                    if ($option['label'] == Delivery::DELIVERY_OPTION_LABEL) {
                        $groups[$option['value']][] = $item;
                        $isDeliverySpecified = true;
                        break;
                    }
                }
            }

            if ($isDeliverySpecified === false) {
                $groups[Delivery::SHIPPING][] = $item;
            }
        }

        return $groups;
    }

    /**
     * Create separate quote for items' group.
     *
     * @param CartInterface $quote
     * @param CartInterface $separateQuote
     * @param array $quoteItems
     * @param $paymentMethod
     * @return CartInterface
     */
    public function createSeparateQuote(
        CartInterface $quote,
        CartInterface $separateQuote,
        array $quoteItems,
        $paymentMethod
    ): CartInterface {
        $this->setAddresses($quote, $separateQuote);
        $this->setCustomerData($quote, $separateQuote);
        $this->setQuoteItems($quoteItems, $separateQuote);

        $this->quoteRepository->save($separateQuote);

        $this->collectTotals($separateQuote, $quoteItems);
        $this->setPaymentMethod($quote, $separateQuote, $paymentMethod);

        return $separateQuote;
    }

    /**
     * Get quote item delivery type.
     *
     * @param Item $item
     * @return int
     */
    public function getItemDelivery(Item $item): int
    {
        $productOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
        if (array_key_exists('options', $productOptions) && $productOptions['options'] > 0) {
            foreach ($productOptions['options'] as $option) {
                if ($option['label'] == Delivery::DELIVERY_OPTION_LABEL) {
                    return (int)$option['value'];
                }
            }
        }

        return Delivery::SHIPPING;
    }

    /**
     * Update checkout session according to the last placed order.
     *
     * @param $separateQuote
     * @param $order
     * @param $orderIds
     * @return QuoteProcessor
     */
    public function updateSession($separateQuote, $order, $orderIds): QuoteProcessor
    {
        $this->checkoutSession->setLastQuoteId($separateQuote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($separateQuote->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
        $this->checkoutSession->setOrderIds($orderIds);

        return $this;
    }

    /**
     * Set quote items.
     *
     * @param array $items
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function setQuoteItems(array $items, CartInterface $separateQuote): QuoteProcessor
    {
        foreach ($items as $item) {
            $item->setId(null);
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $child->setId(null);
                }
            }
            $separateQuote->addItem($item);
        }

        return $this;
    }

    /**
     * Set addresses from original quote to the separated.
     *
     * @param CartInterface $quote
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function setAddresses(CartInterface $quote, CartInterface $separateQuote): QuoteProcessor
    {
        $shippingAddressData = $quote->getShippingAddress()->getData();
        unset($shippingAddressData['id']);
        unset($shippingAddressData['quote_id']);

        $billingAddressData = $quote->getBillingAddress()->getData();
        unset($billingAddressData['id']);
        unset($billingAddressData['quote_id']);

        $separateQuote->getShippingAddress()->setData($shippingAddressData);
        $separateQuote->getBillingAddress()->setData($billingAddressData);

        return $this;
    }

    /**
     * Pass customer data to the separate quote.
     *
     * @param CartInterface $quote
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function setCustomerData(CartInterface $quote, CartInterface $separateQuote): QuoteProcessor
    {
        $separateQuote->setStoreId($quote->getStoreId());
        $separateQuote->setCustomer($quote->getCustomer());
        $separateQuote->setCustomerIsGuest($quote->getCustomerIsGuest());

        if ($quote->getCheckoutMethod() === CartManagementInterface::METHOD_GUEST) {
            $separateQuote->unsetData('customer_id');
            $separateQuote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            $separateQuote->setCustomerIsGuest(true);
            $separateQuote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        return $this;
    }

    /**
     * Collect separate quote totals.
     *
     * @param CartInterface $quote
     * @param array $items
     * @return QuoteProcessor
     */
    private function collectTotals(CartInterface $quote, array $items): QuoteProcessor
    {
        $tax = 0.0;
        $discount = 0.0;
        $finalPrice = 0.0;

        foreach ($items as $item) {
            $tax += $item->getData('tax_amount');
            $discount += $item->getData('discount_amount');

            $finalPrice += ($item->getPrice() * $item->getQty());
        }

        $shipping = $this->shippingAmount($quote);

        foreach ($quote->getAllAddresses() as $address) {
            $grandTotal = (($finalPrice + $shipping + $tax) - $discount);

            $address->setBaseSubtotal($finalPrice);
            $address->setSubtotal($finalPrice);
            $address->setDiscountAmount($discount);
            $address->setTaxAmount($tax);
            $address->setBaseTaxAmount($tax);
            $address->setBaseGrandTotal($grandTotal);
            $address->setGrandTotal($grandTotal);
        }

        return $this;
    }

    private function shippingAmount($quote)
    {
        $total = 0.0;

        if ($quote->hasVirtualItems() === true) {
            return $total;
        }
        $shippingTotal = $quote->getShippingAddress()->getShippingAmount();

        $total = $shippingTotal;

        return $total;
    }

    private function setPaymentMethod(
        CartInterface $quote,
        CartInterface $separateQuote,
        $paymentMethod
    ): QuoteProcessor {
        $separateQuote->getPayment()->setMethod($quote->getPayment()->getMethod());

        if ($paymentMethod) {
            $separateQuote->getPayment()->setQuote($separateQuote);
            $separateQuote->getPayment()->importData($paymentMethod->getData());
        }
        return $this;
    }
}
