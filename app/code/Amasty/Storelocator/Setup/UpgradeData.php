<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Upgrade Data script
 */
class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $connection = $setup->getConnection();
            /* this data will be prepared, parsed and replace condition class */
            $relationsSelect = $connection->select()->from(
                $setup->getTable('amasty_amlocator_location')
            );
            $ruleRelationsDataSet = $connection->fetchAll($relationsSelect);
            foreach ($ruleRelationsDataSet as $locationRow) {
                $this->updateActionData($setup, $locationRow);
            }
        }

        $setup->endSetup();
    }

    /**
     * Update condition class in actions_serialized column
     *
     * @param ModuleDataSetupInterface $setup
     * @param                          $locationRow
     */
    public function updateActionData(ModuleDataSetupInterface $setup, $locationRow)
    {
        $oldConditionClass = 'Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition';
        $newConditionClass = 'Magento\\\\CatalogRule\\\\Model\\\\Rule\\\\Condition';
        $modifiedData =
            str_replace($oldConditionClass, $newConditionClass, $locationRow['actions_serialized']);
        $connection = $setup->getConnection();
        $connection->update(
            $setup->getTable('amasty_amlocator_location'),
            ['actions_serialized' => $modifiedData],
            $connection->quoteInto('id = ?', $locationRow['id'])
        );
    }
}
