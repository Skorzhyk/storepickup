<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Setup;

use Amasty\StorePickupWithLocator\Setup\Operation\CreateOrderTable;
use Amasty\StorePickupWithLocator\Setup\Operation\CreateQuoteTable;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema executed setup scripts
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var CreateQuoteTable
     */
    private $createQuoteTable;

    /**
     * @var CreateOrderTable
     */
    private $createOrderTable;

    public function __construct(CreateQuoteTable $createQuoteTable, CreateOrderTable $createOrderTable)
    {
        $this->createQuoteTable = $createQuoteTable;
        $this->createOrderTable = $createOrderTable;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->createQuoteTable->execute($installer);
        $this->createOrderTable->execute($installer);

        $installer->endSetup();
    }
}
