<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_StorePickupWithLocator
 */


namespace Amasty\StorePickupWithLocator\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\Setup;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class ConnectionResolver for support several databases
 */
class ConnectionResolver
{
    /**
     * Retrieve Connection
     *
     * @param SchemaSetupInterface $setup
     * @param string $connectionName
     * @return AdapterInterface
     */
    public function getConnection(SchemaSetupInterface $setup, $connectionName)
    {
        if ($setup instanceof Setup) {
            return $setup->getConnection($connectionName);
        }
        return $setup->getConnection();
    }
}
