<?php
namespace Reverb\ProcessQueue\Block\Adminhtml;
class Index extends \Magento\Backend\Block\Widget\Container
{
    public function getTaskCodeToFilterBy(){
        return 'listing_image_sync';
    }

    protected $_outstandingTasksCollection = null;
    protected $_completedAndAllQueueTasks = null;

    protected $_status_to_detail_label_mapping = array(
        \Reverb\ProcessQueue\Model\Task::STATUS_PENDING => 'In Progress',
        \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING => 'In Progress',
        \Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE => 'Completed',
        \Reverb\ProcessQueue\Model\Task::STATUS_ERROR => 'Awaiting Retry',
        \Reverb\ProcessQueue\Model\Task::STATUS_ABORTED => 'Failed'
    );

    protected function _getLastExecutedAtTemplate()
    {
        return '<h3>The last Sync Task was executed at %s</h3>';
    }

     public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskprocessorHelper,
        \Reverb\ProcessQueue\Helper\Task\Processor\Unique $taskprocessorUniqueHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        array $data = []
    )
    {
        $this->_taskprocessorHelper = $taskprocessorHelper;
        $this->_taskprocessorUniqueHelper = $taskprocessorUniqueHelper;
        $this->_backendurl = $backendUrl;
        $this->_datetime = $datetime;
        $this->_setHeaderText();

        $this->_objectId = 'reverb_processqueue_task_index_container';

        parent::__construct($context, $data);

        $this->_controller = $this->getTaskCodeToFilterBy();//$this->getAction()->getIndexBlockName();

        $this->setTemplate('Reverb_ReverbSync::reverbsync/processqueue/task/index/container.phtml');
    }

    protected function _setHeaderText()
    {
        list($completed_queue_tasks, $all_process_queue_tasks) = $this->_getCompletedAndAllQueueTasks();

        $completed_tasks_count = count($completed_queue_tasks);
        $all_tasks_count = count($all_process_queue_tasks);
        $header_text = __(sprintf($this->_getHeaderTextTemplate(), $completed_tasks_count, $all_tasks_count));
        $this->_headerText = $header_text;
    }

    public function getTaskCountsByStatusDetailLabel()
    {
        list($completed_queue_tasks, $all_process_queue_tasks) = $this->_getCompletedAndAllQueueTasks();
        
        $task_counts_by_status_detail = array();
        // Initialize all labels as having 0 tasks
        foreach ($this->_status_to_detail_label_mapping as $status => $status_detail_label)
        {
            $task_counts_by_status_detail[$status_detail_label] = 0;
        }

        foreach ($all_process_queue_tasks as $task)
        {
            $status = $task->getStatus();
            $status_detail_label = isset($this->_status_to_detail_label_mapping[$status])
                ? $this->_status_to_detail_label_mapping[$status]
                // This case should never occur, but if it does, it's likely because something went
                //      very wrong with the task's execution
                : $this->_status_to_detail_label_mapping[\Reverb\ProcessQueue\Model\Task::STATUS_ABORTED];

            $task_counts_by_status_detail[$status_detail_label] = $task_counts_by_status_detail[$status_detail_label] + 1;
        }
        return $task_counts_by_status_detail;
    }

    public function getMostRecentTaskMessaging()
    {
        list($completed_queue_tasks, $all_process_queue_tasks) = $this->_getCompletedAndAllQueueTasks();
        $mostRecentTask = reset($all_process_queue_tasks);
        if (!is_object($mostRecentTask))
        {
            return '';
        }

        $gmt_most_recent_executed_at_date = $mostRecentTask->getLastExecutedAt();
        $locale_most_recent_executed_at_date = $this->_datetime->date(null, $gmt_most_recent_executed_at_date);
        $last_sync_message = sprintf($this->_getLastExecutedAtTemplate(), $locale_most_recent_executed_at_date);
        return $last_sync_message;
    }

    public function areTasksOutstanding()
    {
        $outstandingTasksCollection = $this->_getOutstandingTasksCollection();
        return ($outstandingTasksCollection->count() > 0);
    }

    protected function _getCompletedAndAllQueueTasks()
    {
        if (is_null($this->_completedAndAllQueueTasks))
        {
            $this->_completedAndAllQueueTasks = $this->_getTaskProcessorUniqueHelper()->getCompletedAndAllQueueTasks($this->getTaskCodeToFilterBy());
        }

        return $this->_completedAndAllQueueTasks;
    }

    protected function _getOutstandingTasksCollection()
    {
        if (is_null($this->_outstandingTasksCollection))
        {
            $this->_outstandingTasksCollection = $this->_getTaskProcessorUniqueHelper()
                ->getQueueTasksForProgressScreen($this->getTaskCodeToFilterBy());
        }

        return $this->_outstandingTasksCollection;
    }

    protected function _getHeaderTextTemplate()
    {
        return '%s of %s Tasks have completed processing';
    }

    protected function _getExpediteTasksButtonActionUrl()
    {
        return $this->getUrl('*/*/expedite');//$this->getAction()->getUriPathForAction('expedite');
    }

    protected function _getTaskProcessorHelper()
    {
        return $this->_taskprocessorHelper;
    }

    public function _getTaskProcessorUniqueHelper()
    {
        return $this->_taskprocessorUniqueHelper;
    }

    protected function _expediteTasksButtonLabel()
    {
        return 'Expedite Tasks';
    }
}
