<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup\Operation;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class CreateScheduleTable
 */
class CreateScheduleTable
{
    const TABLE_NAME = 'amasty_amlocator_schedule';

    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $this->createScheduleTable($setup);
    }

    /**
     * create amasty_amlocator_schedule table
     *
     * @param SchemaSetupInterface $setup
     */
    private function createScheduleTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(self::TABLE_NAME))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Schedule Id'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false],
                'Schedule Name'
            )->addColumn(
                'schedule',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Schedule'
            );
        $setup->getConnection()->createTable($table);
    }
}
