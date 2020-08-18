<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Block;

class Pager extends \Amasty\Storelocator\Block\Pager
{
    /**
     * Return correct URL for StorePickupWithLocator ajax request
     *
     * @param array $params
     * @return string
     */
    public function getPagerUrl($params = [])
    {
        if ($query = $this->getRequest()->getParam('query')) {
            $params['query'] = $query;
        }

        return $this->_urlBuilder->getUrl('amstorepickup/map/update', $params);
    }
}
