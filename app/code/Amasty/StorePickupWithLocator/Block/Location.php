<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Block;

use Magento\Framework\View\Element\AbstractBlock;

class Location extends \Amasty\Storelocator\Block\Location
{
    protected $_template = 'Amasty_StorePickupWithLocator::map/center.phtml';

    /**
     * Instance of pager block
     *
     * @var \Magento\Catalog\Block\Product\Widget\Html\Pager
     */
    private $pager;

    /**
     * @return string
     */
    public function getLeftBlockHtml()
    {
        $html = $this->setTemplate('Amasty_StorePickupWithLocator::map/left.phtml')->toHtml();

        return $html;
    }

    /**
     * @return string
     */
    public function getJsonLocations()
    {
        return $this->jsonEncoder->encode($this->getLocations());
    }

    /**
     * @param array $locationArray
     *
     * @return array
     */
    public function getLocations($locationArray = ['items' => []])
    {
        if ($this->getLayout()->getBlock('pickup_here_button')) {
            $pickupButton = $this->getLayout()->getBlock('pickup_here_button')->toHtml();

            foreach ($this->getLocationCollection()->getLocationData() as $location) {
                $location['popup_html'] .= str_replace('idForLocation', $location['id'], $pickupButton);
                $locationArray['items'][] = $location;
            }

            $locationArray['totalRecords'] = count($locationArray['items']);
            $locationArray['block'] = $this->getLeftBlockHtml();

            if ($storeListId = $this->getAmlocatorStoreList()) {
                $locationArray['storeListId'] = $storeListId;
            }

            /** @var \Magento\Store\Model\StoreManager $store */
            $store = $this->_storeManager->getStore(true)->getId();
            $locationArray['currentStoreId'] = $store;
        }

        return $locationArray;
    }

    protected function _prepareLayout()
    {
        if ($this->getNameInLayout() && strpos($this->getNameInLayout(), 'link') === false
            && strpos($this->getNameInLayout(), 'jsinit') === false
        ) {
            if ($title = $this->configProvider->getMetaTitle()) {
                $this->pageConfig->getTitle()->set($title);
            }

            if ($description = $this->configProvider->getMetaDescription()) {
                $this->pageConfig->setDescription($description);
            }

            $this->getPagerHtml();

            if ($this->pager) {
                if (!$this->pager->isFirstPage()) {
                    $this->addPrevNext(
                        $this->getUrl('amstorepickup/map/update', ['p' => $this->pager->getCurrentPage() - 1]),
                        ['rel' => 'prev']
                    );
                } elseif ($this->pager->getCurrentPage() < $this->pager->getLastPageNum()) {
                    $this->addPrevNext(
                        $this->getUrl('amstorepickup/map/update', ['p' => $this->pager->getCurrentPage() + 1]),
                        ['rel' => 'next']
                    );
                }
            }
        }

        return AbstractBlock::_prepareLayout();
    }

    /**
     * Add prev/next pages
     *
     * @param string $url
     * @param array $attributes
     *
     */
    private function addPrevNext($url, $attributes)
    {
        $this->pageConfig->addRemotePageAsset(
            $url,
            'link_rel',
            ['attributes' => $attributes]
        );
    }

    /**
     * Return Pager for locator page
     *
     * @return string
     */
    public function getPagerHtml()
    {
        if ($this->getLayout()->getBlock('amasty.chooseOnMap.pager')) {
            $this->pager = $this->getLayout()->getBlock('amasty.chooseOnMap.pager');

            return $this->pager->toHtml();
        }

        if (!$this->pager) {
            $this->pager = $this->getLayout()->createBlock(
                \Amasty\StorePickupWithLocator\Block\Pager::class,
                'amasty.chooseOnMap.pager'
            );

            if ($this->pager) {
                $this->pager->setUseContainer(
                    false
                )->setShowPerPage(
                    false
                )->setShowAmounts(
                    false
                )->setFrameLength(
                    $this->_scopeConfig->getValue(
                        'design/pagination/pagination_frame',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->setJump(
                    $this->_scopeConfig->getValue(
                        'design/pagination/pagination_frame_skip',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->setLimit(
                    $this->configProvider->getPaginationLimit()
                )->setCollection(
                    $this->getLocationCollection()
                );

                return $this->pager->toHtml();
            }
        }

        return '';
    }
}
