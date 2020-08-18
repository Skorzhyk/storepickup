<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model;

use Amasty\Storelocator\Model\ResourceModel\Gallery\Collection as GalleryCollection;

class BaseImageLocation
{
    /**
     * @var GalleryCollection
     */
    private $gallery;

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    public function __construct(
        GalleryCollection $gallery,
        ImageProcessor $imageProcessor
    ) {
        $this->gallery = $gallery;
        $this->imageProcessor = $imageProcessor;
    }

    /**
     * @param int $locationId
     *
     * @return string
     */
    public function getMainImageUrl($locationId)
    {
        $baseImage = $this->gallery->getBaseLocationImage($locationId)->getData('image_name');

        return $baseImage ? $this->imageProcessor->getImageUrl(
            [ImageProcessor::AMLOCATOR_GALLERY_MEDIA_PATH, $locationId, $baseImage]
        ) : $baseImage;
    }
}
