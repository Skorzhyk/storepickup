<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Setup\Operation;

use Amasty\StorePickupWithLocator\Api\Data\QuoteInterface;
use Amasty\StorePickupWithLocator\Model\ResourceModel\Quote;
use Amasty\StorePickupWithLocator\Setup\ConnectionResolver;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class CreateQuoteTable for Store Pickup
 */
class CreateQuoteTable
{
    /**
     * @var ConnectionResolver
     */
    private $connectionResolver;

    public function __construct(ConnectionResolver $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    /**
     * @param SchemaSetupInterface $setup
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
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function createTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(Quote::TABLE);

        return $table = $this->connectionResolver->getConnection($setup, 'sales')
            ->newTable($table)
            ->addColumn(
                QuoteInterface::ID,
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                QuoteInterface::ADDRESS_ID,
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Address Id'
            )
            ->addColumn(
                QuoteInterface::QUOTE_ID,
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Quote Id'
            )
            ->addColumn(
                QuoteInterface::STORE_ID,
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            )
            ->addColumn(
                QuoteInterface::DATE,
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Date'
            )
            ->addColumn(
                QuoteInterface::TIME_FROM,
                Table::TYPE_INTEGER,
                10,
                ['nullable' => true],
                'Time From'
            )
            ->addColumn(
                QuoteInterface::TIME_TO,
                Table::TYPE_INTEGER,
                10,
                ['nullable' => true],
                'Time To'
            )
            ->addForeignKey(
                $setup->getFkName(
                    Quote::TABLE,
                    'address_id',
                    'quote_address',
                    'address_id'
                ),
                'address_id',
                $setup->getTable('quote_address'),
                'address_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Amasty Quote Table');
    }
}
