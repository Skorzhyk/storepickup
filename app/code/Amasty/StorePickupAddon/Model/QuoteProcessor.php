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
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class is responsible for splitting quote based on items' store pickup value.
 */
class QuoteProcessor
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var ShippingProvider  */
    private $shippingProvider;

    /** @var CartInterface */
    private $originalQuote;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param ShippingProvider $shippingProvider
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        ShippingProvider $shippingProvider
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->shippingProvider = $shippingProvider;
    }

    /**
     * Split quotes by items' store pickup value.
     *
     * @param CartInterface $quote
     * @return array
     */
    public function splitQuotes(CartInterface $quote): array
    {
        $this->originalQuote = $quote;

        $groups = [];
        foreach ($this->originalQuote->getAllVisibleItems() as $item) {
            $groups[$this->getItemDelivery($item)][] = $item;
        }

        return $groups;
    }

    /**
     * Create separate quote for items' group.
     *
     * @param CartInterface $quote
     * @param array $quoteItems
     * @param $paymentMethod
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function createSeparateQuote(
        CartInterface $quote,
        array $quoteItems,
        $paymentMethod
    ): CartInterface {
        $this
            ->setAddresses($quote)
            ->setCustomerData($quote)
            ->setQuoteItems($quoteItems, $quote);

        $quote = $this->setShippingInformation($quote);

        $this
            ->collectTotals($quote, $quoteItems)
            ->setPaymentMethod($quote, $paymentMethod);

        return $quote;
    }

    /**
     * Check if quote has only pickup delivery.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function onlyPickup(?CartInterface $quote): bool
    {
        if ($quote != null) {
            $onlyPickup = true;
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($this->getItemDelivery($item) == Delivery::SHIPPING) {
                    $onlyPickup = false;
                }
            }

            return $onlyPickup;
        }

        return false;
    }

    /**
     * Get quote delivery type.
     *
     * @param CartInterface $quote
     * @return int
     */
    public function getQuoteDelivery(CartInterface $quote): int
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            return $this->getItemDelivery($item);
        }

        return Delivery::SHIPPING;
    }

    /**
     * Get quote item delivery type.
     *
     * @param Item $item
     * @return int
     */
    public function getItemDelivery(Item $item): int
    {
//        $productOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
//        if (array_key_exists('options', $productOptions) && $productOptions['options'] > 0) {
//            foreach ($productOptions['options'] as $option) {
//                if ($option['label'] == Delivery::OPTION_LABEL) {
//                    return (int)$option['value'];
//                }
//            }
//        }

//        return Delivery::SHIPPING;

        // Update in phase 3 (use custom options).
        $product = $item->getProduct();

        $product->load($product->getId());
        $delivery = $product->getDelivery() !== null ? (int)$product->getDelivery() : Delivery::SHIPPING;
//        $delivery = Delivery::SHIPPING;
//        if ($deliveryAttribute !== null) {
//            $deliveryAttributeValue = $deliveryAttribute->getValue();
//            $deliveryOptions = explode(',', $deliveryAttributeValue);
//            $delivery = count($deliveryOptions) > 0 ? $deliveryOptions[0] : Delivery::SHIPPING;
//        }

        return $delivery;
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
     * Set addresses from original quote to the separated.
     *
     * @param CartInterface $quote
     * @return QuoteProcessor
     */
    private function setAddresses(CartInterface $quote): QuoteProcessor
    {
        $shippingAddressData = $this->originalQuote->getShippingAddress()->getData();
        $shippingAddressData['collect_shipping_rates'] = true;
        unset($shippingAddressData['id']);
        unset($shippingAddressData['quote_id']);

        $billingAddressData = $this->originalQuote->getBillingAddress()->getData();
        unset($billingAddressData['id']);
        unset($billingAddressData['quote_id']);

        $quote->getShippingAddress()->setData($shippingAddressData);
        $quote->getBillingAddress()->setData($billingAddressData);

        return $this;
    }

    /**
     * Pass customer data to the separate quote.
     *
     * @param CartInterface $quote
     * @return QuoteProcessor
     */
    private function setCustomerData(CartInterface $quote): QuoteProcessor
    {
        $quote->setStoreId($this->originalQuote->getStoreId());
        $quote->setCustomer($this->originalQuote->getCustomer());
        $quote->setCustomerIsGuest($this->originalQuote->getCustomerIsGuest());

        if ($this->originalQuote->getCheckoutMethod() === CartManagementInterface::METHOD_GUEST) {
            $quote->unsetData('customer_id');
            $quote->setCustomerEmail($this->originalQuote->getBillingAddress()->getEmail());
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        return $this;
    }

    /**
     * Set quote items.
     *
     * @param array $items
     * @param CartInterface $quote
     * @return QuoteProcessor
     */
    private function setQuoteItems(array $items, CartInterface $quote): QuoteProcessor
    {
        foreach ($items as $item) {
            $item->setId(null);
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $child->setId(null);
                }
            }
            $quote->addItem($item);
        }

        return $this;
    }

    /**
     * Set separate quote shipping information.
     *
     * @param CartInterface $quote
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    private function setShippingInformation(CartInterface $quote): CartInterface
    {
        $this->quoteRepository->save($quote);

        $delivery = $this->getQuoteDelivery($quote);
        if ($delivery != Delivery::SHIPPING) {
            $quote = $this->shippingProvider->collectPickupInformation($quote, $delivery);
        }

        return $quote;
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
        $baseTax = 0.0;
        $tax = 0.0;
        $baseDiscount = 0.0;
        $discount = 0.0;
        $baseSubtotal = 0.0;
        $subtotal = 0.0;

        /** @var Item $item $item */
        foreach ($items as $item) {
            $baseTax += $item->getBaseTaxAmount();
            $tax += $item->getTaxAmount();

            $baseDiscount += $item->getBaseDiscountAmount();
            $discount += $item->getDiscountAmount();

            $baseSubtotal += $item->getBaseRowTotal();
            $subtotal += $item->getRowTotal();
        }

        $baseShippingAmount = $quote->getShippingAddress()->getBaseShippingAmount();
        $shippingAmount = $quote->getShippingAddress()->getShippingAmount();

        foreach ($quote->getAllAddresses() as $address) {
            $baseGrandTotal = ($baseSubtotal + $baseTax - $baseDiscount) + $baseShippingAmount;
            $grandTotal = ($subtotal + $tax - $discount) + $shippingAmount;

            $address->setBaseSubtotal($baseSubtotal);
            $address->setSubtotal($subtotal);

            $address->setBaseDiscountAmount($baseDiscount);
            $address->setDiscountAmount($discount);

            $address->setBaseTaxAmount($baseTax);
            $address->setTaxAmount($tax);

            $address->setBaseGrandTotal($baseGrandTotal);
            $address->setGrandTotal($grandTotal);
        }

        return $this;
    }

    /**
     * Set separate quote payment method.
     *
     * @param CartInterface $quote
     * @param $paymentMethod
     * @return QuoteProcessor
     */
    private function setPaymentMethod(CartInterface $quote, $paymentMethod): QuoteProcessor
    {
        $quote->getPayment()->setMethod($this->originalQuote->getPayment()->getMethod());

        if ($paymentMethod) {
            $quote->getPayment()->setQuote($quote);
            $quote->getPayment()->importData($paymentMethod->getData());
        }

        return $this;
    }
}
