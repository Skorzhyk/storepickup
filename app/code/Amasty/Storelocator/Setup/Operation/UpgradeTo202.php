<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup\Operation;

use Amasty\Storelocator\Setup\InstallSchema;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeTo202
 */
class UpgradeTo202
{
    /**
     * @param SchemaSetupInterface $setup
     */
    public function addCanonicalUrl(SchemaSetupInterface $setup)
    {
        $locationTable = $setup->getTable(InstallSchema::LOCATION_TABLE_NAME);
        $setup->getConnection()->addColumn(
            $locationTable,
            'canonical_url',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'length'   => 255,
                'comment'  => 'Canonical Url'
            ]
        );
    }
}
