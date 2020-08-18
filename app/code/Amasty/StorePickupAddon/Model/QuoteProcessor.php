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

/**
 * Class is responsible for splitting quote based on items' store pickup value.
 */
class QuoteProcessor
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
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
            $product = $item->getProduct();

            $product->load($product->getId());
            $pickupOption = $product->getColor();
            $groups[$pickupOption][] = $item;
        }

        return $groups;
    }

    /**
     * Create separate quote for items' group.
     *
     * @param CartInterface $currentQuote
     * @param CartInterface $separateQuote
     * @param array $quoteItems
     * @param $paymentMethod
     * @return CartInterface
     */
    public function createSeparateQuote(
        CartInterface $currentQuote,
        CartInterface $separateQuote,
        array $quoteItems,
        $paymentMethod
    ): CartInterface {
        $this->copyAddresses($currentQuote, $separateQuote);
        $this->copyCustomerData($currentQuote, $separateQuote);
        $this->copyQuoteItems($quoteItems, $separateQuote);

        $this->collectTotals($separateQuote);
        $this->setPaymentMethod($currentQuote, $separateQuote, $paymentMethod);

        return $separateQuote;
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
     * Copy quote items.
     *
     * @param array $items
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function copyQuoteItems(array $items, CartInterface $separateQuote): QuoteProcessor
    {
        foreach ($items as $item) {
            $item->setId(null);
            $separateQuote->addItem($item);
        }

        return $this;
    }

    /**
     * Copy addresses from original quote to the separated.
     *
     * @param CartInterface $currentQuote
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function copyAddresses(CartInterface $currentQuote, CartInterface $separateQuote): QuoteProcessor
    {
        $shippingAddressData = $currentQuote->getShippingAddress()->getData();
        unset($shippingAddressData['id']);
        unset($shippingAddressData['quote_id']);

        $billingAddressData = $currentQuote->getBillingAddress()->getData();
        unset($billingAddressData['id']);
        unset($billingAddressData['quote_id']);

        $separateQuote->getShippingAddress()->setData($shippingAddressData);
        $separateQuote->getBillingAddress()->setData($billingAddressData);

        return $this;
    }

    /**
     * Pass customer data to the separate quote.
     *
     * @param CartInterface $currentQuote
     * @param CartInterface $separateQuote
     * @return QuoteProcessor
     */
    private function copyCustomerData(CartInterface $currentQuote, CartInterface $separateQuote): QuoteProcessor
    {
        $separateQuote->setStoreId($currentQuote->getStoreId());
        $separateQuote->setCustomer($currentQuote->getCustomer());
        $separateQuote->setCustomerIsGuest($currentQuote->getCustomerIsGuest());

        if ($currentQuote->getCheckoutMethod() === CartManagementInterface::METHOD_GUEST) {
            $separateQuote->unsetData('customer_id');
            $separateQuote->setCustomerEmail($currentQuote->getBillingAddress()->getEmail());
            $separateQuote->setCustomerIsGuest(true);
            $separateQuote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        return $this;
    }

    /**
     * Collect separate quote totals.
     *
     * @param CartInterface $quote
     * @return $this
     */
    private function collectTotals(CartInterface $quote): QuoteProcessor
    {
        $tax = 0.0;
        $discount = 0.0;
        $finalPrice = 0.0;

        foreach ($quote->getItems() as $item) {
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
        CartInterface $currentQuote,
        CartInterface $separateQuote,
        $paymentMethod
    ): QuoteProcessor {
        $separateQuote->getPayment()->setMethod($currentQuote->getPayment()->getMethod());

        if ($paymentMethod) {
            $separateQuote->getPayment()->setQuote($separateQuote);
            $separateQuote->getPayment()->importData($paymentMethod->getData());
        }
        return $this;
    }
}
