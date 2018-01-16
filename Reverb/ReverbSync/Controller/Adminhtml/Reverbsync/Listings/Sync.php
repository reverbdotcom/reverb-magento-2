<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\ReverbSync\Listings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory; 
class Sync extends \Magento\Backend\App\Action{

    const BULK_SYNC_EXCEPTION = 'Error executing the Reverb Bulk Product Sync via the admin panel: %s';
    const EXCEPTION_CLEARING_ALL_LISTING_TASKS = 'An error occurred while clearing all listing tasks from the system: %s';
    const ERROR_CLEARING_SUCCESSFUL_SYNC = 'An error occurred while clearing successful listing syncs: %s';
    const SUCCESS_BULK_SYNC_QUEUED_UP = '%s products have been queued for sync. Please wait a few minutes and refresh this page...';
    const EXCEPTION_STOP_BULK_SYNC = 'Error attempting to stop all reverb listing sync tasks: %s';
    const SUCCESS_STOPPED_LISTING_SYNCS = 'Stopped all pending Reverb Listing Sync tasks';
    const SUCCESS_CLEAR_LISTING_SYNCS = 'All listing sync tasks have been deleted';
    const SUCCESS_CLEAR_SUCCESSFUL_LISTING_SYNCS = 'All successful listing sync tasks have been deleted';

    protected $_adminHelper = null;


     /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_taskResource;

    protected $_reverbReportResource;

    protected $_backendUrl;

    protected $resultRedirect;

    protected $_syncProductHelper;
    
    public function __construct(Context $context, PageFactory $resultPageFactory,
        \Reverb\ProcessQueue\Model\Resource\Taskresource $taskResource,
        \Reverb\Reports\Model\Resource\Reverbreport $reverbreportResource,
        \Reverb\ReverbSync\Helper\Admin $adminHelper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Reverb\ReverbSync\Helper\Sync\Product $syncProductHelper
    ) {
        parent::__construct($context);
        $this->_taskResource = $taskResource;
        $this->_request = $context->getRequest();
        $this->resultPageFactory = $resultPageFactory;
        $this->_reverbReportResource = $reverbreportResource;
        $this->_adminHelper = $adminHelper;
        $this->_backendUrl = $backendUrl;
        $this->_syncProductHelper = $syncProductHelper;
    }
    public function execute(){
        $action = $this->_request->getParam('action');
        if($action=='clearAllTasks'){
            $this->clearAllTasksAction();
        } else if($action=='clearSuccessfulTasks'){
            $this->clearSuccessfulTasksAction();
        } else if($action=='stopBulkSync'){
            $this->stopBulkSyncAction();
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    public function getRedirectUrl(){
        return $this->_backendUrl->getUrl('admin/reports/reverbreport');
    }

    public function bulkSyncAction()
    {
        try
        {
            $this->_syncProductHelper->deleteAllListingSyncTasks();
            $number_of_syncs_queued_up = $this->_syncProductHelper->queueUpBulkProductDataSync();
        }
        catch(\Reverb\ReverbSync\Model\Exception\Redirect $redirectException)
        {
            // Error message should already have been logged and redirect should already have been set in
            // Reverb_ReverbSync_Helper_Admin::throwRedirectException(). Throw the exception again
            // so that the Magento Front controller dispatch() method can handle redirect
            throw $redirectException;
        }
        catch(\Exception $e)
        {
            // We don't know what caused this exception. Log it and throw redirect exception
            $error_message = __(sprintf(self::BULK_SYNC_EXCEPTION, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $success_message = __(sprintf(self::SUCCESS_BULK_SYNC_QUEUED_UP, $number_of_syncs_queued_up));
        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    public function stopBulkSyncAction()
    {
        try
        {
            $rows_deleted = $this->_syncProductHelper->deleteAllListingSyncTasks();
        }
        catch(\Reverb\ReverbSync\Model\Exception\Redirect $redirectException)
        {
            // Error message should already have been logged and redirect should already have been set in
            // Reverb_ReverbSync_Helper_Admin::throwRedirectException(). Throw the exception again
            // so that the Magento Front controller dispatch() method can handle redirect
            throw $redirectException;
        }
        catch(\Exception $e)
        {
            // We don't know what caused this exception. Log it and throw redirect exception
            $error_message = __(sprintf(self::EXCEPTION_STOP_BULK_SYNC, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $success_message = __(sprintf(self::SUCCESS_STOPPED_LISTING_SYNCS));
        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    public function clearAllTasksAction()
    {
        try
        {
            $listing_sync_rows_deleted = $this->_syncProductHelper->deleteAllListingSyncTasks();
            $reverb_report_rows_deleted = $this->_syncProductHelper->deleteAllReverbReportRows();
        }
        catch(\Exception $e)
        {
            $error_message = __(sprintf(self::EXCEPTION_CLEARING_ALL_LISTING_TASKS, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $success_message = __(sprintf(self::SUCCESS_CLEAR_LISTING_SYNCS));
        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    public function clearSuccessfulTasksAction()
    {
        try
        {
            $listing_tasks_deleted = $this->_taskResource->deleteSuccessfulTasks();
            $reverb_report_rows_deleted = $this->_reverbReportResource->deleteSuccessfulSyncs();
        }
        catch(\Exception $e)
        {
            $error_message = __(sprintf(self::ERROR_CLEARING_SUCCESSFUL_SYNC, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $success_message = __(sprintf(self::SUCCESS_CLEAR_SUCCESSFUL_LISTING_SYNCS));
        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    public function getBlockToShow()
    {
        $are_product_syncs_pending = $this->areProductSyncsPending();
        $index_block = $are_product_syncs_pending ? '/adminhtml_listings_index_syncing' : '/adminhtml_listings_index';
        return $this->getModuleBlockGroupname() . $index_block;
    }

    public function areProductSyncsPending()
    {
        $outstandingListingSyncTasksCollection = Mage::helper('reverb_process_queue/task_processor')
                                                    ->getQueueTasksForProgressScreen('listing_sync');
        $outstanding_tasks_array = $outstandingListingSyncTasksCollection->getItems();

        return (!empty($outstanding_tasks_array));
    }

    public function getControllerDescription()
    {
        return "Reverb Product Sync";
    }

    public function getControllerActiveMenuPath()
    {
        return 'reverb/reverb_listings_sync';
    }

    protected function _getRedirectPath() {
        return 'adminhtml/reports_reverbreport/index';
    }
}
