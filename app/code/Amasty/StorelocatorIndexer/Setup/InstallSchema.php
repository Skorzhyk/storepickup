<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorelocatorIndexer
 */


namespace Amasty\StorelocatorIndexer\Setup;

use Amasty\StorelocatorIndexer\Model\ResourceModel\LocationProductIndex;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->createLocationsIndexTable($setup);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function createLocationsIndexTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(LocationProductIndex::TABLE_NAME))
            ->addColumn(
                LocationProductIndex::LOCATION_ID,
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Location Id'
            )
            ->addColumn(
                LocationProductIndex::PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Product ID'
            )
            ->addColumn(
                LocationProductIndex::STORE_ID,
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Store ID'
            )
            ->addIndex(
                $setup->getIdxName(
                    LocationProductIndex::TABLE_NAME,
                    [
                        LocationProductIndex::LOCATION_ID,
                        LocationProductIndex::PRODUCT_ID,
                        LocationProductIndex::STORE_ID
                    ]
                ),
                [
                    LocationProductIndex::LOCATION_ID,
                    LocationProductIndex::PRODUCT_ID,
                    LocationProductIndex::STORE_ID
                ]
            )
            ->setComment('Amasty Index Locations Table');

        $setup->getConnection()->createTable($table);
    }
}
