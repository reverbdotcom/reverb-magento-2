<?php
namespace Reverb\ReverbSync\Model\Resource\Field\Mapping;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Reverb\ReverbSync\Model\Field\Mapping','Reverb\ReverbSync\Model\Resource\Field\Mapping');
    }
}
