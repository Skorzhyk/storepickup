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
use Amasty\Storelocator\Model\LocationFactory;
use Amasty\Storelocator\Model\ResourceModel\Location as LocationResource;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * Class is responsible for collecting shipping address information based on quote delivery type.
 */
class ShippingProvider
{
    private const PICKUP_STORE = 'am_pickup_store';

    private const COUNTRY_CODE_PATH = 'general/country/default';

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

    private $locationFactory;

    private $locationResource;

    private $addressFactory;

    private $storeManager;

    public function __construct(
        ShippingInformationManagementInterface $shippingInformationManagement,
        ShippingInformationInterfaceFactory $shippingInformationFactory,
        ShippingInformationExtensionFactory $shippingInformationExtensionFactory,
        CartExtensionFactory $cartExtensionFactory,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        CartRepositoryInterface $quoteRepository,
        LocationFactory $locationFactory,
        LocationResource $locationResource,
        AddressFactory $addressFactory,
        StoreManagerInterface $storeManager

    ) {
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->shippingInformationExtensionFactory = $shippingInformationExtensionFactory;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingAssignmentFactory = $shippingAssignmentFactory;
        $this->quoteRepository = $quoteRepository;
        $this->locationFactory = $locationFactory;
        $this->locationResource = $locationResource;
        $this->addressFactory = $addressFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Set/remove store pickup shipping information (address, method) to the quote.
     *
     * @param CartInterface $quote
     * @param int $deliveryLocation
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function collectPickupInformation(
        CartInterface $quote,
        int $deliveryLocation = null
    ): CartInterface {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }
        $shippingAssignment->setItems($quote->getAllVisibleItems());
        $cartExtension->setShippingAssignments([$shippingAssignment]);

        $shippingInformation = $this->shippingInformationFactory->create();
        if ($deliveryLocation !== null) {
            $shippingInformationExtension = $this->shippingInformationExtensionFactory->create();
            $shippingInformationExtension->setData(self::PICKUP_STORE, $deliveryLocation);

            $this->setShippingAddress($quote->getShippingAddress(), $deliveryLocation);
            $shippingInformation
                ->setShippingAddress($quote->getShippingAddress())
                ->setBillingAddress($quote->getBillingAddress())
                ->setShippingCarrierCode(Shipping::SHIPPING_METHOD_CODE)
                ->setShippingMethodCode(Shipping::SHIPPING_METHOD_CODE)
                ->setExtensionAttributes($shippingInformationExtension);

            $this->shippingInformationManagement->saveAddressInformation($quote->getId(), $shippingInformation);

            return $this->quoteRepository->getActive($quote->getId());
        } else {
            $this->refreshShippingInformation($quote);
            $this->quoteRepository->save($quote);

            return $quote;
        }
    }

    /**
     * Set shipping address as selected pickup location address.
     *
     * @param Address $address
     * @param int $deliveryLocation
     * @return Address
     */
    private function setShippingAddress(Address $address, int $deliveryLocation): Address
    {
        $location = $this->locationFactory->create();
        $this->locationResource->load($location, $deliveryLocation);

        $address
            ->setFirstname('-')
            ->setLastname('-')
            ->setCountryId($location->getCountry())
            ->setRegion($location->getStateName())
            ->setRegionId($location->getState())
            ->setStreet($location->getAddress())
            ->setCity($location->getCity())
            ->setPostcode($location->getZip())
            ->setTelephone($location->getPhone());

        return $address;
    }

    /**
     * Remove shipping information from the quote.
     *
     * @param CartInterface $quote
     * @return CartInterface
     */
    private function refreshShippingInformation(CartInterface $quote): CartInterface
    {
        $quote->getShippingAddress()
            ->setFirstname(null)
            ->setLastname(null)
            ->setRegion(null)
            ->setRegionId(null)
            ->setStreet(null)
            ->setCity(null)
            ->setPostcode(null)
            ->setTelephone(null)
            ->setShippingMethod('')
            ->setShippingDescription('');

        $quote->getExtensionAttributes()->setShippingAssignments(null);

        return $quote;
    }
}
