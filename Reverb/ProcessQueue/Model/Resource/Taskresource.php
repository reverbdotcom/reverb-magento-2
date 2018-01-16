<?php
namespace Reverb\ProcessQueue\Model\Resource;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Taskresource extends AbstractDb{

    const LISTING_TASK_CODE = 'listing_sync';

    const SERIALIZED_ARGUMENTS_OBJECT = 'O:8:"stdClass":1:{s:10:"product_id";i:##PRODUCT_ID##;}';

    public function _construct()
    {
        $this->_init('reverb_process_queue_task','task_id');
    }

    protected $currentdatetime;
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $currentdatetime
    ) {
        parent::__construct($context);
        $this->currentdatetime = $currentdatetime;
    }

    public function selectForUpdate(\Reverb\ProcessQueue\Model\Task\Interfaceclass $taskObject)
    {
        $task_id = $taskObject->getId();
        if (empty($task_id))
        {
            // $taskObject must be an existing/loaded object in order to lock it
            return false;
        }

        $uniquefield = 'task_id';
        if($taskObject instanceOf \Reverb\ProcessQueue\Model\Task\Unique){
            $uniquefield = 'unique_id';   
        }

        $select = $this->getConnection()->select()
                        ->from(array('process_queue' => $this->getMainTable()))
                        ->where($uniquefield.'=?', $task_id)
                        ->where('status=?', \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING)
                        ->forUpdate(true);

        $selected = $this->getConnection()->fetchOne($select);
        return $selected;
    }

    public function attemptUpdatingRowAsProcessing(\Reverb\ProcessQueue\Model\Task\Interfaceclass $taskObject)
    {
        $task_id = $taskObject->getId();
        if (empty($task_id))
        {
            // $taskObject must be an existing/loaded object in order to lock it
            return false;
        }
        
        $uniquefield = 'task_id';
        if($taskObject instanceOf \Reverb\ProcessQueue\Model\Task\Unique){
            $uniquefield = 'unique_id';   
        }

        // Status here can be PENDING or ERROR
        $current_status = $taskObject->getStatus();
        if (\Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING == $current_status)
        {
            // Assume another execution thread is actively processing this task
            return false;
        }
        $current_gmt_datetime = $this->currentdatetime->gmtDate();

        // First, attempt to update the row based on id and status. If no rows are updated, another thread has already
        //  begun processing this row. Also we want to do this outside of any transactions so that we know other mysql
        //  connections will see that this row is already processing

        $update_bind_array = array('status' => \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING,
                                    'status_message' => null,
                                    'last_executed_at' => $current_gmt_datetime);
        $where_conditions_array = array($uniquefield.'=?' => $task_id,
                                        'status=?' => $current_status,
                                        // As an additional safety measure, don't update any rows already in processing state
                                        'status<>?' => \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING);

        $rows_updated = $this->getConnection()->update($this->getMainTable(), $update_bind_array, $where_conditions_array);
        return $rows_updated;
    }

    public function setExecutionStatusForTask($execution_status, \Reverb\ProcessQueue\Model\Task\Interfaceclass $taskObject, $status_message = null)
    {
        $task_id = $taskObject->getId();
        if (empty($task_id))
        {
            // TODO Some logging here
            return 0;
        }
        $uniquefield = 'task_id';
        if($taskObject instanceOf \Reverb\ProcessQueue\Model\Task\Unique){
            $uniquefield = 'unique_id';   
        }

        if ($taskObject->isStatusValid($execution_status))
        {
            $update_bind_array = array('status' => $execution_status);
            if (!is_null($status_message))
            {
                $update_bind_array['status_message'] = $status_message;
            }
            $task_id = $taskObject->getId();
            $where_conditions_array = array($uniquefield.'=?' => $task_id);
            $rows_updated = $this->getConnection()->update($this->getMainTable(), $update_bind_array, $where_conditions_array);
            return $rows_updated;
        }

        // TODO Log error in this case
        return 0;
    }

    /**
     * @param null|string|array $task_code
     * @param null|string $last_executed_date - Expected to be a date in 'Y-m-d H:i:s' format
     * @return int - Number of rows deleted
     */
    public function deleteSuccessfulTasks($task_code = null, $last_executed_date = null)
    {
        $where_condition_array = array('status=?' => \Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE);
        if (!empty($task_code))
        {
            if (!is_array($task_code))
            {
                $task_code = array($task_code);
            }
            $where_condition_array['code in (?)'] = $task_code;
        }
        if (!empty($last_executed_date))
        {
            $where_condition_array['last_executed_at < ?'] = $last_executed_date;
        }
        $rows_deleted = $this->getConnection()->delete($this->getMainTable(), $where_condition_array);
        return $rows_deleted;
    }

    public function deleteAllTasks($task_code = null, $statuses_to_delete = array())
    {
        if(!empty($task_code))
        {
            if (!is_array($task_code))
            {
                $task_code = array($task_code);
            }

            $where_condition_array = array('code in (?)' => $task_code);

            if (!empty($statuses_to_delete))
            {
                $where_condition_array['status in (?)'] = $statuses_to_delete;
            }

            $rows_deleted = $this->getConnection()->delete($this->getMainTable(), $where_condition_array);
        }
        else
        {
            $rows_deleted = $this->getConnection()->delete($this->getMainTable());
        }

        return $rows_deleted;
    }

    public function setTaskAsCompleted(\Reverb\ProcessQueue\Model\Task\Interfaceclass $taskObject, $success_message = null)
    {
        return $this->setExecutionStatusForTask(\Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE, $taskObject, $success_message);
    }

    public function setTaskAsErrored(\Reverb\ProcessQueue\Model\Task\Interfaceclass $taskObject, $error_message = null)
    {
        return $this->setExecutionStatusForTask(\Reverb\ProcessQueue\Model\Task::STATUS_ERROR, $taskObject, $error_message);
    }

    public function updateLastExecutedAtToCurrentTime(array $task_ids)
    {
        $current_gmt_datetime = $this->currentdatetime->gmtDate();
        $update_bind_array = array('last_executed_at' => $current_gmt_datetime);
        $where_conditions_array = array('task_id IN (?)' => $task_ids);
        $rows_updated = $this->getConnection()->update($this->getMainTable(), $update_bind_array, $where_conditions_array);
        return $rows_updated;
    }

    public function queueListingSyncsByProductIds(array $product_ids_in_system)
    {
        $insert_data_array_template = $this->_getInsertDataArrayTemplate();
        $data_array_to_insert = array();
        foreach ($product_ids_in_system as $product_id)
        {
            $product_data_array = $insert_data_array_template;
            $serialized_arguments = str_replace('##PRODUCT_ID##', $product_id, self::SERIALIZED_ARGUMENTS_OBJECT);
            $product_data_array['serialized_arguments_object'] = $serialized_arguments;

            $data_array_to_insert[] = $product_data_array;
        }
        
        $columns_array = $this->_getInsertColumnsArray();
        $number_of_created_rows = $this->getConnection()->insertArray(
            $this->getMainTable(), $columns_array, $data_array_to_insert
        );
        return $number_of_created_rows;
    }

    public function deleteAllListingSyncTasks()
    {
        $where_condition_array = array('code=?' => self::LISTING_TASK_CODE);
        $rows_deleted = $this->getConnection()->delete($this->getMainTable(), $where_condition_array);
        return $rows_deleted;
    }
/*
    public function deleteSuccessfulTasks()
    {
        return Mage::getResourceSingleton('reverb_process_queue/task')->deleteSuccessfulTasks(self::LISTING_TASK_CODE);
    }*/

    protected function _getInsertColumnsArray()
    {
        return array('code', 'status', 'object', 'method', 'serialized_arguments_object');
    }

    protected function _getInsertDataArrayTemplate()
    {
        return array(
            'code' => self::LISTING_TASK_CODE,
            'status' => \Reverb\ProcessQueue\Model\Task::STATUS_PENDING,
            'object' => '\Reverb\ReverbSync\Model\Sync\Product',
            'method' => 'executeQueuedIndividualProductDataSync'
        );
    }
}