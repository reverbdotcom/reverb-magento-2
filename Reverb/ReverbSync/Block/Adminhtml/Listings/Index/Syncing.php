<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Listings\Index;
class Syncing extends \Magento\Backend\Block\Widget\Container
{
    const HEADER_TEXT_TEMPLATE = '%s of %s product listings have completed syncing with Reverb';

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskprocessorHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        array $data = []
    )
    {
        $this->_taskprocessorHelper = $taskprocessorHelper;
        $this->_backendurl = $backendUrl;
        $block_module_groupname = "ReverbSync";

        $this->_objectId = 'reverb_stop_product_sync_container';
        $this->setTemplate('widget/view/container.phtml');
        $this->_headerText = 'testtt';

        parent::__construct($context,$data);

        $bulk_sync_process_button = array(
            'action_url' => $this->_backendurl->getUrl('reverbsync/reverbsync_listings/sync',array('action'=>'stopBulkSync')),
            'label' => 'Stop Bulk Sync'
        );

        $clear_all_tasks_button = array(
            'action_url' => $this->_backendurl->getUrl('reverbsync/reverbsync_listings/sync',array('action'=>'clearAllTasks')),
            'label' => 'Clear All Sync Tasks'
        );

        $clear_successful_tasks_button = array(
            'action_url' => $this->_backendurl->getUrl('reverbsync/reverbsync_listings/sync',array('action'=>'clearSuccessfulTasks')),
            'label' => 'Clear Successful Sync Tasks'
        );

        $action_buttons_array['clear_all_sync_tasks'] = $clear_all_tasks_button;
        $action_buttons_array['clear_successful_sync_tasks'] = $clear_successful_tasks_button;
        $action_buttons_array['bulk_product_sync'] = $bulk_sync_process_button;

        foreach ($action_buttons_array as $button_id => $button_data)
        {
            $button_action_url = isset($button_data['action_url']) ? $button_data['action_url'] : '';
            if (empty($button_action_url))
            {
                // Url must be defined
                continue;
            }

            $button_label = isset($button_data['label']) ? $button_data['label'] : '';
            if (empty($button_label))
            {
                // Label must be defined
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

    protected function _setHeaderText()
    {
        list($completed_queue_tasks, $all_process_queue_tasks) = $this->_taskprocessorHelper->getCompletedAndAllQueueTasks('listing_sync');

        $completed_tasks_count = count($completed_queue_tasks);echo '<br/>';
        $all_tasks_count = count($all_process_queue_tasks);
        $header_text = __(sprintf(self::HEADER_TEXT_TEMPLATE, $completed_tasks_count, $all_tasks_count));
        return $header_text;
    }
}
