<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Block\Adminhtml\Sales\Order\Create\Shipping;

use Amasty\StorePickupWithLocator\Model\ConfigProvider;
use Amasty\StorePickupWithLocator\Model\TimeHandler;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\View\Element\Template;
use Amasty\StorePickupWithLocator\CustomerData\LocationData;

class Form extends Template
{
    /**
     * Path To Render Template
     */
    const TEMPLATE_PATH = 'Amasty_StorePickupWithLocator::sales/order/create/form.phtml';

    /**
     * @var CollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var TimeHandler
     */
    private $timeHandler;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Template\Context $context,
        LocationData $locationCollectionFactory,
        FormFactory $formFactory,
        TimeHandler $timeHandler,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->_template = self::TEMPLATE_PATH;
        $this->formFactory = $formFactory;
        $this->timeHandler = $timeHandler;
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getOptionsForStores()
    {
        $locationCollection = $this->locationCollectionFactory->getSectionData();
        $locationsData = ['' => __('Please select a store.')];
        foreach ($locationCollection['stores'] as $location) {
            $locationsData[$location['id']] = $location['name'];
        }

        return $locationsData;
    }

    /**
     * @return array
     */
    public function getIntervals()
    {
        $timeIntervalOptions = $this->timeHandler->generate(TimeHandler::START_TIME, TimeHandler::END_TIME);
        $validDataForOptions = ['' => __('Please select time interval.')];
        foreach ($timeIntervalOptions as $interval) {
            $validDataForOptions[$interval['value']] = $interval['label'];
        }

        return $validDataForOptions;
    }

    /**
     * @return \Magento\Framework\Data\Form\Element\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormElements()
    {
        $form = $this->formFactory->create();
        $form->setHtmlIdPrefix('ampickup');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Store Pickup With Locator Data'),
                'class' => 'ampickup-order-fieldset'
            ]
        );

        $locationOptions = $this->getOptionsForStores();
        $fieldset->addField(
            'location_id',
            'select',
            [
                'label' => __('Select Store To Collect Your Order'),
                'name' => 'ampickup[location_id]',
                'required' => false,
                'options' => $locationOptions,
                'class' => 'ampickup-field'
            ]
        );

        if ($this->configProvider->isPickupDateEnabled()) {
            $dateFormat = $this->timeHandler->getFormatDate();
            $fieldset->addField(
                'date',
                \Magento\Framework\Data\Form\Element\Date::class,
                [
                    'label' => __('Pickup Date'),
                    'name' => 'ampickup[date]',
                    'input_format' => $dateFormat,
                    'format' => $dateFormat,
                    'required' => false,
                    'date_format' => $dateFormat,
                    'class' => 'ampickup-field'
                ]
            );
            $intervalOptions = $this->getIntervals();
            $fieldset->addField(
                'interval_id',
                'select',
                [
                    'label' => __('Pickup Time'),
                    'name' => 'ampickup[tinterval_id]',
                    'required' => false,
                    'options' => $intervalOptions,
                    'class' => 'ampickup-field'
                ]
            );
        }

        return $form->getElements();
    }
}
