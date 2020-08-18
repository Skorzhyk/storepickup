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
 * Class CreateGalleryTable
 */
class CreateGalleryTable
{
    const TABLE_NAME = 'amasty_amlocator_gallery';

    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->createTable(
            $this->createTable($setup)
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     *
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function createTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(self::TABLE_NAME);
        $locationTable = $setup->getTable(InstallSchema::LOCATION_TABLE_NAME);

        return $setup->getConnection()
            ->newTable($table)
            ->setComment('Table for images gallery of each location')
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )
            ->addColumn(
                'location_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Location Id'
            )->addColumn(
                'image_name',
                Table::TYPE_TEXT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Image Name'
            )->addColumn(
                'is_base',
                Table::TYPE_BOOLEAN,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Base Image Flag'
            )->addForeignKey(
                $setup->getFkName(
                    $table,
                    'location_id',
                    $locationTable,
                    'id'
                ),
                'location_id',
                $locationTable,
                'id',
                Table::ACTION_CASCADE
            );
    }
}
