<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Uninstall
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $tablesToDrop = [
            InstallSchema::LOCATION_TABLE_NAME,
            Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_TABLE_NAME,
            Operation\CreateAttributeTables::LOCATION_ATTRIBUTE_OPTION_TABLE_NAME,
            Operation\CreateGalleryTable::TABLE_NAME,
            Operation\CreateReviewTable::TABLE_NAME,
            Operation\CreateScheduleTable::TABLE_NAME,
            Operation\CreateAttributeTables::LOCATION_STORE_ATTRIBUTE_TABLE_NAME
        ];
        foreach ($tablesToDrop as $table) {
            $installer->getConnection()->dropTable(
                $installer->getTable($table)
            );
        }

        $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->delete(
            \Amasty\Storelocator\Model\ImageProcessor::AMLOCATOR_MEDIA_PATH
        );

        $installer->endSetup();
    }
}
