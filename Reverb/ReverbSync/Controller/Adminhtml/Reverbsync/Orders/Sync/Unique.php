<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Orders\Sync;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Unique extends \Magento\Backend\App\Action
{

	const EXCEPTION_SYNC_SHIPMENT_TRACKING = 'An error occurred while attempting to sync shipment tracking data with Reverb: %s';
    const NOTICE_SYNC_SHIPMENT_TRACKING = 'The attempt to sync shipment tracking data with Reverb has completed';
	
	protected $resultPageFactory;

    protected $_taskProcessor;
	
	public function __construct(Context $context, PageFactory $resultPageFactory,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskProcessor,
        \Reverb\ReverbSync\Helper\Admin $adminhelper
    ) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
        $this->_adminHelper = $adminhelper;
        $this->_taskProcessor = $taskProcessor;
	}

	public function execute(){
        $action = $this->getRequest()->getParam('action', null);
        if($action=='syncShipmentTrackingAction'){
            $this->syncShipmentTrackingAction();
            $redirecturl = 'reverbsync/reverbsync_orders_sync/unique'; 
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($redirecturl);
            return $resultRedirect;
        }
		$resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Reverb_ReverbSync::reverbsync_orders_sync_unique');
		$resultPage->getConfig()->getTitle()->prepend((__('Reverb Shipment Tracking Sync Tasks')));
		return $resultPage;
	}
	
	public function syncShipmentTrackingAction()
    {
        try
        {
            $this->_taskProcessor->processQueueTasks(\Reverb\ReverbSync\Model\Sync\Shipment\Tracking::JOB_CODE);
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::EXCEPTION_SYNC_SHIPMENT_TRACKING, $e->getMessage());
            $this->_adminHelper->addAdminErrorMessage($error_message);
        }
        $this->_adminHelper->addAdminSuccessMessage(__(self::NOTICE_SYNC_SHIPMENT_TRACKING));
    }
}

