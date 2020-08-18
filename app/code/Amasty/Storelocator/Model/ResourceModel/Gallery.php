<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model\ResourceModel;

use Amasty\Storelocator\Setup\Operation\CreateGalleryTable;
use Amasty\Storelocator\Model\ImageProcessor;
use Magento\Directory\Model\Region;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Gallery
 */
class Gallery extends AbstractDb
{
    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    /**
     * @var Region
     */
    private $region;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $ioFile;

    public function __construct(
        ImageProcessor $imageProcessor,
        Region $region,
        Filesystem $filesystem,
        File $ioFile,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->imageProcessor = $imageProcessor;
        $this->region = $region;
        $this->filesystem = $filesystem;
        $this->ioFile = $ioFile;
    }

    public function _construct()
    {
        $this->_init(CreateGalleryTable::TABLE_NAME, 'id');
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $data = $object->getData();

        if (isset($data['image_name']) && $object->isObjectNew()) {
            $this->imageProcessor->processImage(
                $data['image_name'],
                ImageProcessor::GALLERY_IMAGE_TYPE,
                $object->getLocationId(),
                $object->getLocationIsNew()
            );
        }
    }

    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $data = $object->getData();

        if (isset($data['image_name'])) {
            $this->imageProcessor->deleteImage($data['image_name']);
        }
    }
}
