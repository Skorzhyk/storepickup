<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model;

use Magento\Catalog\Api\Data\ProductInterface;

class LocationProductValidator
{
    /**
     * @param Location $location
     * @param ProductInterface $product
     *
     * @return bool
     */
    public function isValid(
        Location $location,
        ProductInterface $product
    ) {
        $product->setStoreId($location->getStoreId());

        return $location->getProductConditions()->validate($product);
    }
}
