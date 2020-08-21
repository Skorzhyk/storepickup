<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupAddon
 */
declare(strict_types=1);

namespace Amasty\StorePickupAddon\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Amasty\StorePickupWithLocator\Model\Carrier\Shipping;
use Magento\Checkout\Api\Data\ShippingInformationExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class is responsible for collecting shipping address information based on quote delivery type.
 */
class ShippingProvider
{
    private const PICKUP_STORE = 'am_pickup_store';

    /** @var ShippingInformationManagementInterface */
    private $shippingInformationManagement;

    /** @var ShippingInformationInterfaceFactory */
    private $shippingInformationFactory;

    /** @var ShippingInformationExtensionFactory */
    private $shippingInformationExtensionFactory;

    /** @var CartExtensionFactory */
    private $cartExtensionFactory;

    /** @var ShippingAssignmentFactory */
    private $shippingAssignmentFactory;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    public function __construct(
        ShippingInformationManagementInterface $shippingInformationManagement,
        ShippingInformationInterfaceFactory $shippingInformationFactory,
        ShippingInformationExtensionFactory $shippingInformationExtensionFactory,
        CartExtensionFactory $cartExtensionFactory,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->shippingInformationExtensionFactory = $shippingInformationExtensionFactory;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingAssignmentFactory = $shippingAssignmentFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Set store pickup shipping method to the separate quote.
     *
     * @param CartInterface $quote
     * @param int $deliveryLocation
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function collectPickupInformation(CartInterface $quote, int $deliveryLocation): CartInterface
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }
        $shippingAssignment = $this->shippingAssignmentFactory->create();
        $shippingAssignment->setItems($quote->getAllVisibleItems());
        $cartExtension->setShippingAssignments([$shippingAssignment]);

        $shippingInformationExtension = $this->shippingInformationExtensionFactory->create();
        $shippingInformationExtension->setData(self::PICKUP_STORE, $deliveryLocation);

        $shippingInformation = $this->shippingInformationFactory->create();
        $shippingInformation
            ->setShippingAddress($quote->getShippingAddress())
            ->setBillingAddress($quote->getBillingAddress())
            ->setShippingCarrierCode(Shipping::SHIPPING_METHOD_CODE)
            ->setShippingMethodCode(Shipping::SHIPPING_METHOD_CODE)
            ->setExtensionAttributes($shippingInformationExtension);

        $this->shippingInformationManagement->saveAddressInformation($quote->getId(), $shippingInformation);

        return $this->quoteRepository->getActive($quote->getId());
    }
}
