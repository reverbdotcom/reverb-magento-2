<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Orders;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Sync extends \Magento\Backend\App\Action
{
    const EXCEPTION_BULK_ORDERS_SYNC = 'Error executing a Reverb bulk orders sync: %s';
    const EXCEPTION_PROCESSING_DOWNLOADED_TASKS = 'Error processing downloaded Reverb Order Sync tasks: %s';
    const ERROR_DENIED_ORDER_CREATION_STATUS_UPDATE = 'You do not have permissions to update this task\'s status';
    const NOTICE_QUEUED_ORDERS_FOR_SYNC = 'Order sync in progress. Please wait a few minutes and refresh this page...';
    const NOTICE_PROCESSING_DOWNLOADED_TASKS = 'Processing downloaded orders. Please wait a few minutes and refresh this page...';

    /**
	 * @var PageFactory
	 */
	protected $resultPageFactory;
	
	public function __construct(Context $context, PageFactory $resultPageFactory,
        \Reverb\ReverbSync\Helper\Data $syncHelper,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskProcessor,
        \Reverb\ReverbSync\Helper\Orders\Retrieval\Update $orderRetrievalUpdate,
        \Reverb\ReverbSync\Helper\Admin $adminhelper
    ) {
		parent::__construct($context);
        $this->_taskprocessor = $taskProcessor;
		$this->resultPageFactory = $resultPageFactory;
        $this->_orderRetrievalUpdate = $orderRetrievalUpdate;
        $this->_syncHelper = $syncHelper;
        $this->_adminHelper = $adminhelper;
	}

	public function execute(){
        $action = $this->getRequest()->getParam('action', null);
        if($action!=''){
            if($action=='bulkSyncAction'){
                $this->bulkSyncAction();
            } else if($action=='syncDownloadedAction'){
                $this->syncDownloadedAction();
            }
            $redirecturl = 'reverbsync/reverbsync_orders/sync';
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($redirecturl);
            return $resultRedirect;
        }
        $resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Reverb_ReverbSync::reverbsync_orders_sync');
		$resultPage->getConfig()->getTitle()->prepend((__('Reverb Order Update Sync Tasks')));
		return $resultPage;
	}
	
	/*->_addContent($this->getLayout()->createBlock('ReverbSync/adminhtml_orders_index'))
      ->_addContent($this->getLayout()->createBlock('ReverbSync/adminhtml_orders_task_index'))
    */

    public function saveAction()
    {
        if (!$this->canAdminUpdateStatus())
        {
            $task_param_name = $this->getObjectParamName();
            $task_id = $this->getRequest()->getParam($task_param_name);
            $error_message = sprintf(self::ERROR_DENIED_ORDER_CREATION_STATUS_UPDATE);

            // TODO:
            $this->_getAdminHelper()->throwRedirectException($error_message,
                                                             'adminhtml/ReverbSync_orders_sync/edit',
                                                             array($task_param_name => $task_id)
                                                             );
        }

        parent::saveAction();
    }

    public function bulkSyncAction()
    {
        try
        {
            $this->_syncHelper->verifyModuleIsEnabled();

            $this->_orderRetrievalUpdate->queueReverbOrderSyncActions();
            $this->_taskprocessor->processQueueTasks('order_update');
        }
        catch(\Exception $e)
        {
            $error_message = $this->__(self::EXCEPTION_BULK_ORDERS_SYNC, $e->getMessage());
            $this->_getAdminHelper()->throwRedirectException($error_message);
        }
        $this->_adminHelper->addAdminSuccessMessage(__(self::NOTICE_QUEUED_ORDERS_FOR_SYNC));
        
        //$this->_redirectBasedOnRequestParameter();
    }

    public function syncDownloadedAction()
    {
        try
        {
            $this->_syncHelper->verifyModuleIsEnabled();
            /*$this->_orderRetrievalUpdate->queueReverbOrderSyncActions();*/
            $this->_taskprocessor->processQueueTasks('order_update');
        }
        catch(\Exception $e)
        {
            $error_message = $this->__(self::EXCEPTION_PROCESSING_DOWNLOADED_TASKS, $e->getMessage());
            $this->_getAdminHelper()->throwRedirectException($error_message);
        }
        $this->_adminHelper->addAdminSuccessMessage(__(self::NOTICE_PROCESSING_DOWNLOADED_TASKS));
    }
}
