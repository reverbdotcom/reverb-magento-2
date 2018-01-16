<?php
namespace Reverb\ReverbSync\Model\Resource\Field;

class Mapping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('reverb_magento_field_mapping','mapping_id');
    }
}
