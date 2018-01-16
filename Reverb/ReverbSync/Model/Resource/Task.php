<?php
namespace Reverb\ReverbSync\Model\Resource;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
abstract class Task extends AbstractDb
{
    protected $_helperSyncImage;

    protected $_datetime;

    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
        )
    {
        $this->_datetime = $datetime;
        parent::__construct($context);
    }

    abstract public function getTaskCode();

    public function _construct()
    {
        $this->_init('reverb_process_queue_task','task_id');
    }

    public function deleteAllTasks()
    {
        $task_code = $this->getTaskCode();
        if (empty($task_code))
        {
            // Should never happen
            return 0;
        }
        $where_condition_array = array('code=?' => $task_code);
        $rows_deleted = $this->_getWriteAdapter()->delete($this->getMainTable(), $where_condition_array);
        return $rows_deleted;
    }

    protected function _getInsertColumnsArray()
    {
        return array('code', 'status', 'object', 'method', 'serialized_arguments_object', 'subject_id');
    }

    protected function _getInsertDataArrayTemplate($object, $method, $subject_id = null)
    {
        $task_code = $this->getTaskCode();
        $data_array_template = array('code' => $task_code, 'status' => \Reverb\ProcessQueue\Model\Task::STATUS_PENDING,
                                    'object' => $object, 'method' => $method);

        if (!is_null($subject_id))
        {
            $data_array_template['subject_id'] = $subject_id;
        }

        $data_array_template['created_at'] = $this->_datetime->gmtDate();

        return $data_array_template;
    }
}
