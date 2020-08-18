<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Model;

use Amasty\StorePickupWithLocator\Api\LocationPickupValuesInterface;
use Amasty\StorePickupWithLocator\Api\QuoteRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\ShippingAddressManagementInterface;

class LocationPickupValues implements LocationPickupValuesInterface
{
    /**
     * @var QuoteRepositoryInterface
     */
    private $amQuoteRepository;

    /**
     * @var ShippingAddressManagementInterface
     */
    private $shippingAddressManagement;

    /**
     * @var DateTimeValidator
     */
    private $validator;

    public function __construct(
        QuoteRepositoryInterface $amQuoteRepository,
        ShippingAddressManagementInterface $shippingAddressManagement,
        DateTimeValidator $validator
    ) {
        $this->amQuoteRepository = $amQuoteRepository;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->validator = $validator;
    }

    /**
     * @param int $cartId
     * @param int $locationId
     * @param string|null $date
     * @param string|null $timePeriod
     *
     * @return bool|mixed
     * @throws InputException
     */
    public function saveSelectedPickupValues(
        $cartId,
        $locationId,
        $date = null,
        $timePeriod = null
    ) {
        $timeFrom = $timeTo = null;

        if ($date) {
            if ($timePeriod) {
                list($timeFrom, $timeTo) = explode('|', $timePeriod);
            }

            if (!$this->validator->isValidDate($cartId, $locationId, $date, $timeFrom, $timeTo)) {
                throw new InputException(__('Store Pickup Date/Time is not valid.'));
            }
        }

        $this->processSavePickupData($cartId, $locationId, $date, $timeFrom, $timeTo);

        return true;
    }

    /**
     * @param int $cartId
     * @param int $locationId
     * @param string $date
     * @param string $timeFrom
     * @param string $timeTo
     */
    private function processSavePickupData($cartId, $locationId, $date, $timeFrom, $timeTo)
    {
        $addressEntity = $this->shippingAddressManagement->get($cartId);
        $addressId = $addressEntity->getId();

        $quoteEntity = $this->amQuoteRepository->getByAddressId($addressId);

        $quoteEntity->setAddressId($addressId)
            ->setStoreId($locationId)
            ->setQuoteId($cartId)
            ->setDate($date)
            ->setTimeFrom($timeFrom)
            ->setTimeTo($timeTo);

        $this->amQuoteRepository->save($quoteEntity);
    }
}
