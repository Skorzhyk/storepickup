<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */


namespace Amasty\Storelocator\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Amasty\Storelocator\Setup\Operation\CreateReviewTable;

/**
 * Class Review
 */
class Review extends AbstractDb
{
    public function _construct()
    {
        $this->_init(CreateReviewTable::TABLE_NAME, 'id');
    }
}
