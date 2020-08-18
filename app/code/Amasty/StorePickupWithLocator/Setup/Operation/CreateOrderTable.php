<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Setup\Operation;

use Amasty\StorePickupWithLocator\Api\Data\OrderInterface;
use Amasty\StorePickupWithLocator\Model\ResourceModel\Order;
use Amasty\StorePickupWithLocator\Setup\ConnectionResolver;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class CreateOrderTable for Store Pickup
 */
class CreateOrderTable
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
        $table = $setup->getTable(Order::TABLE);

        return $table = $this->connectionResolver->getConnection($setup, 'sales')
            ->newTable($table)
            ->addColumn(
                OrderInterface::ID,
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                OrderInterface::ORDER_ID,
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Order Id'
            )
            ->addColumn(
                OrderInterface::STORE_ID,
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            )
            ->addColumn(
                OrderInterface::DATE,
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Date'
            )
            ->addColumn(
                OrderInterface::TIME_FROM,
                Table::TYPE_INTEGER,
                10,
                ['nullable' => true],
                'Time From'
            )
            ->addColumn(
                OrderInterface::TIME_TO,
                Table::TYPE_INTEGER,
                10,
                ['nullable' => true],
                'Time To'
            )
            ->addForeignKey(
                $setup->getFkName(
                    Order::TABLE,
                    'order_id',
                    'sales_order',
                    'entity_id'
                ),
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Amasty Order Table');
    }
}
