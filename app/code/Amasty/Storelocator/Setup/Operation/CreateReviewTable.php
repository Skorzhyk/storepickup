<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup\Operation;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Amasty\Storelocator\Api\Data\ReviewInterface;
use Amasty\Storelocator\Setup\InstallSchema;

/**
 * Class CreateReviewTable
 */
class CreateReviewTable
{
    const TABLE_NAME = 'amasty_amlocator_review';

    /**
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
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
        $customerTable = $setup->getTable('customer_entity');
        $locationsTable = $setup->getTable(InstallSchema::LOCATION_TABLE_NAME);

        return $setup->getConnection()
            ->newTable(
                $table
            )->setComment(
                'Amasty Storelocator reviews table'
            )->addColumn(
                ReviewInterface::ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                ReviewInterface::LOCATION_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Location Id'
            )->addColumn(
                ReviewInterface::CUSTOMER_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Customer Id'
            )->addColumn(
                ReviewInterface::REVIEW_TEXT,
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false
                ],
                'Text'
            )->addColumn(
                ReviewInterface::RATING,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Rating'
            )->addColumn(
                ReviewInterface::PLACED_AT,
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default'  => Table::TIMESTAMP_INIT
                ],
                'Placed Date'
            )->addColumn(
                ReviewInterface::PUBLISHED_AT,
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                ],
                'Approved Date'
            )->addColumn(
                ReviewInterface::STATUS,
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false
                ],
                'Status'
            )->addForeignKey(
                $setup->getFkName(
                    $table,
                    ReviewInterface::CUSTOMER_ID,
                    $customerTable,
                    'entity_id'
                ),
                ReviewInterface::CUSTOMER_ID,
                $customerTable,
                'entity_id'
            )->addForeignKey(
                $setup->getFkName(
                    $table,
                    ReviewInterface::LOCATION_ID,
                    $locationsTable,
                    'id'
                ),
                ReviewInterface::LOCATION_ID,
                $locationsTable,
                'id',
                Table::ACTION_CASCADE
            );
    }
}
