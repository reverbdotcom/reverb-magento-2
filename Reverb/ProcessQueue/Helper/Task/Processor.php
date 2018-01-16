<?php
namespace Reverb\ProcessQueue\Helper\Task;
class Processor extends \Magento\Framework\App\Helper\AbstractHelper
{
    const EXCEPTION_UPDATE_AS_PROCESSING = 'Error attempting to update queue task with id %s as processing: %s';
    const EXCEPTION_SELECT_FOR_UPDATE = 'Error attempting to select queue task with id %s for update: %s';
    const ERROR_FAILED_TO_SELECT_FOR_UPDATE = 'Failed to select queue task with id %s for update';
    const EXCEPTION_EXECUTING_TASK = 'Error executing task for queue task object with id %s: %s';
    const EXCEPTION_ACTING_ON_TASK_RESULT = 'Error acting on the task result for task with id %s: %s';
    const EXCEPTION_COMMITTING_TRANSACTION = 'An uncaught exception occurred when attempting to commit the transaction for process queue object with id %s: %s';

    protected $_moduleName = 'reverb_process_queue';
    protected $_task_model_classname = 'reverb_process_queue_task';
    protected $_taskResourceSingleton = null;
    protected $_logModel = null;

    // TODO Refactor this
    protected $_batch_size = 2500;

    protected $_taskCollection;

    protected $_taskresource;

    protected $_imageSyncCollection;

    protected $_taskresourceUnique;

    public function __construct(
        \Reverb\ProcessQueue\Model\Resource\Taskresource\Unique\Collection $_imageSyncCollection,
        \Reverb\ProcessQueue\Model\Resource\Taskresource\Collection $taskCollection,
        \Reverb\ProcessQueue\Model\Resource\Taskresource $taskresource,
        \Reverb\ProcessQueue\Model\Resource\Taskresource\Unique $taskresourceUnique,
        \Reverb\ReverbSync\Model\Logger $logger
    ) {
        $this->_taskCollection = $taskCollection;
        $this->_taskresource = $taskresource;
        $this->_imageSyncCollection = $_imageSyncCollection;
        $this->_logger = $logger;
        $this->_taskresourceUnique = $taskresourceUnique;
    }

    // TODO Create separate database connection for queue task resource Singleton
    public function processQueueTasks($code = null)
    {
        $processQueuetaskCollection = $this->getQueueTasksForProcessing($code);
       
        // Update the last_executed_at value for these task rows so that the next cron iteration will pick up a different
        //  set of BATCH_SIZE rows from the call to $this->getQueueTasksForProcessing($code); above
        $this->updateLastExecutedAtToCurrentTime($processQueuetaskCollection);
        //try{
        foreach ($processQueuetaskCollection as $processQueueTaskObject)
        {   
            $this->processQueueTask($processQueueTaskObject);
        }
        /*} catch(\Execution $e){
            echo $e->getMessage();
            exit;
        }*/
    }

    /**
     * Executes the following:
     *  - Attempts to update task object's row as processing
     *  - If successful, Begins a database transaction
     *  - Attempts to select that row for update
     *  - If successful, attempts to execute method callback defined in row, returning a result object
     *  - Updates the task object based on the resulting object
     *  - Commits the database transaction
     *
     * @param \Reverb\ProcessQueue\Model\Task_Interface $processQueueTaskObject
     */
    public function processQueueTask(\Reverb\ProcessQueue\Model\Task\Interfaceclass $processQueueTaskObject)
    {

        try 
        {
            $able_to_lock_for_processing = $processQueueTaskObject->attemptUpdatingRowAsProcessing();
            if (!$able_to_lock_for_processing)
            {
                // Assume another thread of execution is already processing this task
                return;
            }
        }
        catch(\Exception $e)
        {
            $error_message = __(sprintf(self::EXCEPTION_UPDATE_AS_PROCESSING, $processQueueTaskObject->getId(), $e->getMessage()));
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
            return;
        }

        // At this point, start transaction and lock row for update to ensure exclusive access
        $taskResourceSingleton = $processQueueTaskObject->getResource();
        $taskResourceSingleton->beginTransaction();
        try
        {
           
            $selected = $processQueueTaskObject->selectForUpdate();
            if (!$selected)
            {
                // Assume another thread has already locked this task object's row, although this shouldn't happen
                $taskResourceSingleton->rollBack();
                $error_message = __(sprintf(self::ERROR_FAILED_TO_SELECT_FOR_UPDATE, $processQueueTaskObject->getId()));
                $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
                return;
            }
        }
        catch(\Exception $e)
        {
            
            $taskResourceSingleton->rollBack();
            $error_message = __(sprintf(self::EXCEPTION_SELECT_FOR_UPDATE, $processQueueTaskObject->getId(), $e->getMessage()));
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
            return;
        }

        try
        {
            $taskExecutionResult = $processQueueTaskObject->executeTask();
        }
        catch(\Exception $e)
        {
            $taskResourceSingleton->rollBack();
            $error_message = __(sprintf(self::EXCEPTION_EXECUTING_TASK, $processQueueTaskObject->getId(), $e->getMessage()));
            $processQueueTaskObject->setTaskAsErrored($error_message);
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
            return;
        }

        try
        {
            $processQueueTaskObject->actOnTaskResult($taskExecutionResult);
        }
        catch(\Exception $e)
        {
            // At this point, we would assume that the task has been performed successfully since executeTask() did not
            //  throw any exceptions. As such, log the exception but commit the transaction. Even if this leaves a row
            //  in the PROCESSING state, it's better than leaving parts of the database out of sync with external resources
            $error_message = __(sprintf(self::EXCEPTION_ACTING_ON_TASK_RESULT, $processQueueTaskObject->getId(), $e->getMessage()));
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
        }

        try
        {
            $taskResourceSingleton->commit();
        }
        catch(\Exception $e)
        {
            // If an exception occurs here, rollback
            $taskResourceSingleton->rollback();
            $processQueueTaskObject->setTaskAsErrored();
            $error_message = __(sprintf(self::EXCEPTION_COMMITTING_TRANSACTION, $processQueueTaskObject->getId(), $e->getMessage()));
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
        }
    }

    public function getQueueTasksForProcessing($code = null)
    {
        $processQueuetaskCollection = $this->_gettaskCollectionModel($code)
                                        ->addOpenForProcessingFilter()
                                        ->sortByLeastRecentlyExecuted()
                                        ->setPageSize($this->_batch_size);

        if (!empty($code))
        {
            $processQueuetaskCollection->addCodeFilter($code);
        }

        return $processQueuetaskCollection;
    }

    public function getQueueTasksForProgressScreen($code = null)
    {
        $processQueuetaskCollection = $this->_gettaskCollectionModel($code)
                                        ->addOpenForProcessingFilter()
                                        ->sortByLeastRecentlyExecuted()
                                        ->setPageSize($this->_batch_size);

        if (!empty($code))
        {
            $processQueuetaskCollection->addCodeFilter($code);
        }

        return $processQueuetaskCollection;
    }

    public function getCompletedAndAllQueueTasks($code = null)
    {
        $allProcessQueuetaskCollection = $this->_gettaskCollectionModel($code)
                                                ->setOrder('last_executed_at',\Zend_Db_Select::SQL_DESC);;

        if (!empty($code))
        {
            $allProcessQueuetaskCollection->addCodeFilter($code);
        }

        $all_process_queue_tasks = $allProcessQueuetaskCollection->getItems();

        $completedTasksCollection = $this->_gettaskCollectionModel($code)
                                            ->addStatusFilter(\Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE)
                                            ->setOrder('last_executed_at',\Zend_Db_Select::SQL_DESC);

        /*if (!empty($code))
        {
            $completedTasksCollection->addCodeFilter($code);
        }*/

        $completed_queue_tasks = $completedTasksCollection->getItems();

        return array($completed_queue_tasks, $all_process_queue_tasks);
    }

    public function updateLastExecutedAtToCurrentTime(\Reverb\ProcessQueue\Model\Resource\Taskresource\Collection $processQueuetaskCollection)
    {
        $records = $processQueuetaskCollection->getData();
        if(count($records) > 0 ){
            $task_ids = array();
            foreach ($records as $taskObject)
            {
                if(isset($taskObject['task_id'])){
                    $task_ids[] = $taskObject['task_id'];
                }
            }
            //$rows_updated = $processQueuetaskCollection->getResource()->updateLastExecutedAtToCurrentTime($task_ids);

            $rows_updated = $this->_taskresource->updateLastExecutedAtToCurrentTime($task_ids);
            return $rows_updated;
        }
    }

    public function deleteAllTasks($task_codes, $type="")
    {
        if($type =="listing_image_sync" || $type=="shipment_tracking_sync"){
            $rows_deleted = $this->_getTaskResourceUnique()->deleteAllTasks($task_codes);
            return $rows_deleted;
        } else if($type == "order_update"){
            $rows_deleted = $this->_getTaskResourceSingleton()->deleteAllTasks($task_codes);
            return $rows_deleted;
        } else {
            $rows_deleted = $this->_getTaskResourceSingleton()->deleteAllTasks($task_codes);
            return $rows_deleted;
        }
    }

    public function deleteSuccessfulTasks($task_codes, $type="")
    {
        if($type=="listing_image_sync" || $type=="shipment_tracking_sync"){
            $rows_deleted = $this->_getTaskResourceUnique()->deleteSuccessfulTasks($task_codes);
            return $rows_deleted;
        } else if($type=="order_update"){
            $rows_deleted = $this->_getTaskResourceSingleton()->deleteSuccessfulTasks($task_codes);
            return $rows_deleted;
        } else {
            $rows_deleted = $this->_getTaskResourceSingleton()->deleteSuccessfulTasks($task_codes);
            return $rows_deleted;
        }
    }

    protected function _gettaskCollectionModel($code=null)
    {
        if(!empty($code) && $code=='listing_image_sync'){
            return $this->_imageSyncCollection;
        } else if(!empty($code) && $code=='shipment_tracking_sync'){
            return $this->_imageSyncCollection;
        } else {
            return $this->_taskCollection;
        }
    }

    /**
     * @return Reverb_ProcessQueue_Model_Mysql4_Task
     */
    protected function _getTaskResourceSingleton()
    {
        if (is_null($this->_taskResourceSingleton))
        {
            $this->_taskResourceSingleton = $this->_taskresource;
        }

        return $this->_taskResourceSingleton;
    }

    public function _getTaskResourceUnique(){
        return $this->_taskresourceUnique;
    }
}
