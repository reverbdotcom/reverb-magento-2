<?php
namespace Reverb\ProcessQueue\Block\Adminhtml\Unique;
abstract class Index extends \Reverb\ProcessQueue\Block\Adminhtml\Index
{
    protected function _getTaskProcessorHelper()
    {
        return $this->_getTaskProcessorUniqueHelper();
    }
}
