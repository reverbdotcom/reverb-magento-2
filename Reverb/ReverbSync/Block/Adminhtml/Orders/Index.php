<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Orders;
class Index extends \Magento\Backend\Block\Widget\Container
{
    const LAST_EXECUTED_AT_TEMPLATE = '<h3>The last Sync Task was executed at %s</h3>';

    protected $_outstandingTasksCollection = null;
    protected $_completedAndAllQueueTasks = null;

    protected $_view_html = '';

    protected $_status_to_detail_label_mapping = array(
        \Reverb\ProcessQueue\Model\Task::STATUS_PENDING => 'In Progress',
        \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING => 'In Progress',
        \Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE => 'Completed',
        \Reverb\ProcessQueue\Model\Task::STATUS_ERROR => 'Awaiting Retry',
        \Reverb\ProcessQueue\Model\Task::STATUS_ABORTED => 'Failed'
    );

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

        $this->_objectId = 'reverb_orders_sync_container';

        parent::__construct($context, $data);

        $this->_controller = 'adminhtml_order_index';//$this->getAction()->getIndexBlockName();

        $this->setTemplate('Reverb_ReverbSync::reverbsync/sales/order/index/container.phtml');


        $block_module_groupname = "ReverbSync";

        $order_sync_actions_controller = 'reverbsync/reverbsync_orders/sync';
        $bulk_sync_action_url = $this->_backendurl->getUrl($order_sync_actions_controller,
                                             array('action' => 'bulkSyncAction'));

        $bulk_orders_sync_process_button = array(
            'action_url' => $bulk_sync_action_url,
            'label' => $this->_retrieveAndProcessTasksButtonLabel()
        );

        $process_downloaded_tasks_action_url = $this->_backendurl->getUrl($order_sync_actions_controller, array('action' => 'syncDownloadedAction'));
        $process_downloaded_tasks_button = array(
            'action_url' => $process_downloaded_tasks_action_url,
            'label' => $this->_processDownloadedTasksButtonLabel()
        );

        $action_buttons_array['bulk_orders_sync'] = $bulk_orders_sync_process_button;
        $action_buttons_array['sync_downloaded_tasks'] = $process_downloaded_tasks_button;

        $clear_all_tasks_action = 'reverbprocessqueue/processqueue/index';
        $task_code_param = $this->_getTaskCode();
        $clear_all_tasks_button = array(
            'action_url' => $this->_backendurl->getUrl($clear_all_tasks_action,
                                                                        array('task_codes' => $task_code_param,'type'=>'clearAllTasksAction')
                                                                    ),
            'label' => 'Clear All Tasks',
            'confirm_message' => 'Are you sure you want to clear all tasks?'
        );

        $clear_successful_tasks_button = array(
            'action_url' => $this->_backendurl->getUrl($clear_all_tasks_action,
                                                                        array('task_codes' => $task_code_param,'type'=>'clearSuccessfulTasksAction')
                                                                    ),
            'label' => 'Clear Successful Tasks',
            'confirm_message' => 'Are you sure you want to clear all successful tasks?'
        );

        $action_buttons_array['clear_all_sync_tasks'] = $clear_all_tasks_button;
        $action_buttons_array['clear_successful_sync_tasks'] = $clear_successful_tasks_button;

        foreach ($action_buttons_array as $button_id => $button_data)
        {
            $button_action_url = isset($button_data['action_url']) ? $button_data['action_url'] : '';
            if (empty($button_action_url))
            {
                // Require label to be defined
                continue;
            }

            $button_label = isset($button_data['label']) ? $button_data['label'] : '';
            if (empty($button_label))
            {
                // Require label to be defined
                continue;
            }

            $this->addButton(
                $button_id, array(
                    'label' => __($button_label),
                    'onclick' => "document.location='" .$button_action_url . "'",
                    'level' => -1
                )
            );
        }
    }

    protected function _retrieveAndProcessTasksButtonLabel()
    {
        return 'Download and Process Order Updates';
    }

    protected function _processDownloadedTasksButtonLabel()
    {
        return 'Process Downloaded Order Updates';
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
                                    // This case should never occur, but if it doesn, it's likely because something went
                                    // very wrong with the task's execution
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
        $last_sync_message = sprintf(self::LAST_EXECUTED_AT_TEMPLATE, $locale_most_recent_executed_at_date);
        return $last_sync_message;
    }

    public function areTasksOutstanding()
    {
        $outstandingTasksCollection = $this->_getOutstandingTasksCollection();
        return ($outstandingTasksCollection->count() > 0);
    }

    protected function _getCompletedAndAllQueueTasks()
    {
        $this->_completedAndAllQueueTasks = $this->_getTaskProcessorHelper()
                                                        ->getCompletedAndAllQueueTasks($this->_getTaskCode());
        return $this->_completedAndAllQueueTasks;
    }

    protected function _getOutstandingTasksCollection()
    {
            $this->_outstandingTasksCollection = $this->_getTaskProcessorHelper()
                                                        ->getQueueTasksForProgressScreen($this->_getTaskCode());
        return $this->_outstandingTasksCollection;
    }

    protected function _getHeaderTextTemplate()
    {
        return '%s of %s Reverb Order Update Tasks have completed syncing with Magento';
    }

    protected function _getTaskCode()
    {
        return 'order_update';
    }

    /**
     * @return Reverb_ProcessQueue_Helper_Task_Processor
     */
    protected function _getTaskProcessorHelper()
    {
        return $this->_taskprocessorHelper;
    }

    protected function _getTaskProcessorUniqueHelper(){
        return $this->_taskprocessorUniqueHelper;
    }
    public function getViewHtml()
    {
        return $this->_view_html;
    }
}
