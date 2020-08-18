<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup\Operation;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Amasty\Storelocator\Setup\InstallSchema;

/**
 * Class CreateAttributeTables
 */
class CreateAttributeTables
{
    const LOCATION_ATTRIBUTE_TABLE_NAME = 'amasty_amlocator_attribute';

    const LOCATION_ATTRIBUTE_OPTION_TABLE_NAME = 'amasty_amlocator_attribute_option';

    const LOCATION_STORE_ATTRIBUTE_TABLE_NAME = 'amasty_amlocator_store_attribute';

    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $this->createAttributeTable($setup);
        $this->createAttributeOptionTable($setup);
        $this->createAttributeValueTable($setup);
    }

    /**
     * create amasty_amlocator_attribute table
     *
     * @param SchemaSetupInterface $setup
     */
    private function createAttributeTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::LOCATION_ATTRIBUTE_TABLE_NAME))
            ->addColumn(
                'attribute_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Attribute Id'
            )->addColumn(
                'frontend_label',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false],
                'Default Label'
            )
            ->addColumn(
                'attribute_code',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Code'
            )
            ->addColumn(
                'frontend_input',
                Table::TYPE_TEXT,
                50,
                ['unsigned' => true, 'nullable' => false],
                'Frontend Input'
            )
            ->addColumn(
                'is_required',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => true],
                'Is Required'
            )
            ->addColumn(
                'label_serialized',
                Table::TYPE_TEXT,
                '64k',
                ['unsigned' => true, 'nullable' => true],
                'Attribute Labels by store'
            );
        $setup->getConnection()->createTable($table);
    }

    /**
     * create amasty_amlocator_attribute_option table
     *
     * @param SchemaSetupInterface $setup
     */
    private function createAttributeOptionTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME))
            ->addColumn(
                'value_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Id'
            )
            ->addColumn(
                'options_serialized',
                Table::TYPE_TEXT,
                '64k',
                ['unsigned' => true, 'nullable' => true],
                'Value And Store'
            )
            ->addColumn(
                'is_default',
                Table::TYPE_TEXT,
                '64k',
                ['unsigned' => true, 'nullable' => true],
                'This is Default Option'
            )
            ->addColumn(
                'sort_order',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Sort order'
            )
            ->addIndex(
                $setup->getIdxName(self::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME, ['attribute_id']),
                ['attribute_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME,
                    'attribute_id',
                    self::LOCATION_ATTRIBUTE_TABLE_NAME,
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable(CreateAttributeTables::LOCATION_ATTRIBUTE_TABLE_NAME),
                'attribute_id',
                Table::ACTION_CASCADE
            );
        $setup->getConnection()->createTable($table);
    }

    /**
     * create amasty_amlocator_store_attribute table
     *
     * @param SchemaSetupInterface $setup
     */
    private function createAttributeValueTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::LOCATION_STORE_ATTRIBUTE_TABLE_NAME))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )
            ->addColumn(
                'attribute_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Id'
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store Location Id'
            )
            ->addColumn(
                'value',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Value'
            )
            ->addIndex(
                $setup->getIdxName(self::LOCATION_STORE_ATTRIBUTE_TABLE_NAME, ['attribute_id']),
                ['attribute_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::LOCATION_STORE_ATTRIBUTE_TABLE_NAME,
                    'attribute_id',
                    self::LOCATION_ATTRIBUTE_TABLE_NAME,
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable(self::LOCATION_ATTRIBUTE_TABLE_NAME),
                'attribute_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    self::LOCATION_STORE_ATTRIBUTE_TABLE_NAME,
                    'store_id',
                    InstallSchema::LOCATION_TABLE_NAME,
                    'id'
                ),
                'store_id',
                $setup->getTable(InstallSchema::LOCATION_TABLE_NAME),
                'id',
                Table::ACTION_CASCADE
            )
            ->addIndex(
                $setup->getIdxName(
                    self::LOCATION_STORE_ATTRIBUTE_TABLE_NAME,
                    ['attribute_id', 'store_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['attribute_id', 'store_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );
        $setup->getConnection()->createTable($table);
    }
}
