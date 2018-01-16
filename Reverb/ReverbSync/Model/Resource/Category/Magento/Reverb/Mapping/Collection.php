<?php
namespace Reverb\ReverbSync\Model\Resource\Category\Magento\Reverb\Mapping;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Reverb\ReverbSync\Model\Category\Magento\Reverb\Mapping','Reverb\ReverbSync\Model\Resource\Category\Magento\Reverb\Mapping');
    }
}
