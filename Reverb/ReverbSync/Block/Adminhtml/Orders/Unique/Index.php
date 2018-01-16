<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Orders\Unique;
class Index extends \Reverb\ReverbSync\Block\Adminhtml\Orders\Index
{
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskprocessorHelper,
        \Reverb\ProcessQueue\Helper\Task\Processor\Unique $taskprocessorUniqueHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        array $data = []
    )
    {
        $this->_backendUrl = $backendUrl;
        parent::__construct($context, $taskprocessorHelper, $taskprocessorUniqueHelper, $backendUrl, $datetime, $data);

        $this->removeButton('bulk_orders_sync', 'sync_downloaded_tasks');

        $sync_shipment_tracking_action_url = $this->_backendUrl->getUrl('reverbsync/reverbsync_orders_sync/unique',array('action'=>'syncShipmentTrackingAction'));

        $this->addButton('sync_shipment_tracking', array(
                'label' => __('Sync Shipment Tracking Data With Reverb'),
                'onclick' => "document.location='" .$sync_shipment_tracking_action_url . "'",
                'level' => -1
            )
        );
    }

    protected function _retrieveAndProcessTasksButtonLabel()
    {
        return '';
    }

    protected function _processDownloadedTasksButtonLabel()
    {
        return '';
    }

    protected function _getHeaderTextTemplate()
    {
        return '%s of %s Reverb Shipment Tracking Tasks have completed syncing with Magento';
    }

    protected function _getTaskCode()
    {
        return \Reverb\ReverbSync\Model\Sync\Shipment\Tracking::JOB_CODE;
    }

    protected function _getTaskProcessorHelper()
    {
        return $this->_getTaskProcessorUniqueHelper();
    }
}
