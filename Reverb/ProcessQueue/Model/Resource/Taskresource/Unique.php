<?php
namespace Reverb\ProcessQueue\Model\Resource\Taskresource;
class Unique extends \Reverb\ProcessQueue\Model\Resource\Taskresource
{
    public function _construct()
    {
        $this->_init('reverb_process_queue_task_unique', 'unique_id');
    }
}
