<?php
namespace Reverb\ProcessQueue\Model\Task;
use \Reverb\ProcessQueue\Model\Task as Taskmodel;
use \Reverb\ProcessQueue\Model\Task\Interfaceclass as ModelTaskInterfaceclass;
class Unique extends Taskmodel implements ModelTaskInterfaceclass
{
    protected function _construct()
    {
        $this->_init(\Reverb\ProcessQueue\Model\Resource\Taskresource\Unique::class);
    }
}
