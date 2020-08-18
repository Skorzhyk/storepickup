<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Plugin\Checkout\Model;

use Amasty\StorePickupWithLocator\Model\Carrier\Shipping;
use Amasty\StorePickupWithLocator\Model\ConfigProvider;
use Amasty\StorePickupWithLocator\Model\DateTimeValidator;
use Amasty\StorePickupWithLocator\Model\Quote;
use Amasty\StorePickupWithLocator\Model\QuoteRepository;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\ShippingAddressManagementInterface;

/**
 * Class ShippingInformationManagementPlugin for save store pickup data
 */
class ShippingInformationManagementPlugin
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var DateTimeValidator
     */
    private $validator;

    /**
     * @var ShippingAddressManagementInterface
     */
    private $shippingAddressManagement;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        QuoteRepository $quoteRepository,
        DateTimeValidator $validator,
        ShippingAddressManagementInterface $shippingAddressManagement,
        ConfigProvider $configProvider
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->validator = $validator;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->configProvider = $configProvider;
    }

    /**
     * Validate pickup data
     *
     * @param ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return null
     * @throws InputException
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if ($addressInformation->getShippingCarrierCode() !== Shipping::SHIPPING_METHOD_CODE) {
            return null;
        }

        $storeValue = (int)$addressInformation->getExtensionAttributes()->getAmPickupStore();

        if (!$storeValue) {
            throw new InputException(__('Store ID is not specified. Please, choose a store for pickup.'));
        }

        if ($this->configProvider->isPickupDateEnabled()) {
            $dateValue = (string)$addressInformation->getExtensionAttributes()->getAmPickupDate();
            $timeFrom = $timeTo = null;

            if ($timeValue = (string)$addressInformation->getExtensionAttributes()->getAmPickupTime()) {
                list($timeFrom, $timeTo) = explode('|', $timeValue);
            }

            if (!$this->validator->isValidDate($cartId, $storeValue, $dateValue, $timeFrom, $timeTo)) {
                throw new InputException(__('Store Pickup Date/Time is not valid.'));
            }
        }

        return null;
    }

    /**
     * Save pickup data
     *
     * @param ShippingInformationManagement $subject
     * @param \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails
     * @param string|int $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function afterSaveAddressInformation(
        ShippingInformationManagement $subject,
        $paymentDetails,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if ($addressInformation->getShippingCarrierCode() !== Shipping::SHIPPING_METHOD_CODE) {
            return $paymentDetails;
        }

        $addressId = $this->shippingAddressManagement->get($cartId)->getId();

        /** @var Quote $quoteEntity */
        $quoteEntity = $this->quoteRepository->getByAddressId($addressId);
        $quoteEntity->setAddressId($addressId)
            ->setQuoteId($cartId)
            ->setStoreId((int)$addressInformation->getExtensionAttributes()->getAmPickupStore())
            ->setDate(null)
            ->setTimeFrom(null)
            ->setTimeTo(null);

        if ($date = (string)$addressInformation->getExtensionAttributes()->getAmPickupDate()) {
            $quoteEntity->setDate($date);
        }

        if ($timeValue = (string)$addressInformation->getExtensionAttributes()->getAmPickupTime()) {
            list($timeFrom, $timeTo) = explode('|', $timeValue);
            $quoteEntity->setTimeFrom($timeFrom)->setTimeTo($timeTo);
        }

        $this->quoteRepository->save($quoteEntity);

        return $paymentDetails;
    }
}
