<?php

/**
 * Reverb Report collection resource model
 *
 * @category    Reverb
 * @package     Reverb_Reports
 */
namespace Reverb\Reports\Model\Resource\Reverbreport;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection{

   /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Reverb\Reports\Model\Reverbreport', 'Reverb\Reports\Model\Resource\Reverbreport');
    }
}
