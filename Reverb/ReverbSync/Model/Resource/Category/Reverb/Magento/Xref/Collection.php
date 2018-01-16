<?php
namespace Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
class Collection extends AbstractCollection;
{
    protected function _construct()
    {
        $this->_init('\Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref','\Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref');
    }
}
