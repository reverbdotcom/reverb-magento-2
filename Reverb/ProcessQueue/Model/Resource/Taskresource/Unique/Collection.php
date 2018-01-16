<?php
namespace Reverb\ProcessQueue\Model\Resource\Taskresource\Unique;
class Collection extends \Reverb\ProcessQueue\Model\Resource\Taskresource\Collection
{
    public function _construct()
    {
        $this->_init('\Reverb\ProcessQueue\Model\Task\Unique','\Reverb\ProcessQueue\Model\Resource\Taskresource\Unique');
    }
}
