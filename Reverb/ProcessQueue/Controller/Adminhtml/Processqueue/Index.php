<?php
namespace Reverb\ProcessQueue\Controller\Adminhtml\Processqueue;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Index extends \Magento\Backend\App\Action
//extends \Reverb\Base\Controller\Adminhtml\Form\Abstract
//implements \Reverb\Base\Controller\Adminhtml\Form\Interface
{
    const ERROR_CLEARING_ALL_TASKS = 'An error occurred while clearing all tasks with job code %s: %s';
    const ERROR_CLEARING_SUCCESSFUL_TASKS = 'An error occurred while clearing all successful tasks with job code %s: %s';
    const SUCCESS_CLEARED_ALL_TASKS_WITH_CODE = 'Successfully cleared all tasks with code %s';
    const SUCCESS_CLEARED_SUCCESSFUL_TASKS_WITH_CODE = 'Successfully cleared all successful tasks with code %s';
    const SUCCESS_CLEARED_ALL_TASKS = 'Successfully cleared all tasks';
    const SUCCESS_CLEARED_SUCCESSFUL_TASKS = 'Successfully cleared all successful tasks';
    const EXCEPTION_INVALID_TASK_ID = '%s. Invalid Task ID=%s.';
    const EXCEPTION_ACT_ON_TASK = '%s. Error while trying to run Task ID=%s: %s.';
    const NOTICE_TASK_ACTION = '%s. Task ID=%s is queued.';

    protected $_adminHelper = null;

    /**
     * Allow Queue Tasks to be created via these forms
     *
     * @param $objectToCreate
     * @param $posted_object_data
     * @return mixed
     */
     /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_modelTaskUnique;

    protected $_processqueueTaskProcessor;

    public function __construct(Context $context, PageFactory $resultPageFactory,
        \Reverb\ProcessQueue\Model\Task\Unique $modelTaskUnique, \Reverb\ProcessQueue\Helper\Task\Processor $taskProcessor,
            \Reverb\ReverbSync\Helper\Admin $adminhelper
        ) {
        parent::__construct($context);
        $this->_modelTaskUnique = $modelTaskUnique;
        $this->resultPageFactory = $resultPageFactory;
        $this->_processqueueTaskProcessor = $taskProcessor;
        $this->_adminHelper = $adminhelper;
    }

    public function execute(){
        $task_code = $this->getRequest()->getParam('task_codes', null);
        $action = $this->getRequest()->getParam('type', null);
        if($action=='clearSuccessfulTasksAction'){
            $this->clearSuccessfulTasksAction();
        } else if($action=='clearAllTasksAction'){
            $this->clearAllTasksAction();
        }
        
        $redirecturl = 'reverbsync/reverbsync_listings/imagesync'; 
        if($task_code=="listing_image_sync"){
            $redirecturl = 'reverbsync/reverbsync_listings/imagesync'; 
        } else if($task_code=="order_update"){
            $redirecturl = 'reverbsync/reverbsync_orders/sync'; 
        } else if($task_code=='shipment_tracking_sync'){
            $redirecturl = 'reverbsync/reverbsync_orders_sync/unique';
        }
        
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($redirecturl);
        return $resultRedirect;
        
    }
    
    public function validateDataAndCreateObject($objectToCreate, $posted_object_data)
    {
        $objectToCreate->setLastExecutedAt(null);
        return $objectToCreate->addData($posted_object_data);
    }

    public function validateDataAndUpdateObject($objectToUpdate, $posted_object_data)
    {
        // Only the status field should have been passed
        $new_status = isset($posted_object_data['status']) ? $posted_object_data['status'] : null;
        if (!is_null($new_status))
        {
            $objectToUpdate->setStatus($new_status);
        }

        return $objectToUpdate;
    }

    public function clearAllTasksAction()
    {
        $task_codes = $this->_getTaskCodesParam();
        $task_code = $this->getRequest()->getParam('task_codes', null);
        try
        {
            $rows_deleted = $this->_getTaskProcessor()->deleteAllTasks($task_codes,$task_code);
        }
        catch(\Exception $e)
        {
            $task_codes_string = implode(', ', $task_codes);
            $error_message = __(sprintf(self::ERROR_CLEARING_ALL_TASKS, $task_codes_string, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $redirecturl = 'reverbsync/reverbsync_listings/imagesync'; 
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($redirecturl);
            return $resultRedirect;
        }

        if (!empty($task_code))
        {
            $success_message = __(sprintf(self::SUCCESS_CLEARED_ALL_TASKS_WITH_CODE, $task_code));
        }
        else
        {
            $success_message = __(self::SUCCESS_CLEARED_ALL_TASKS);
        }
        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    public function clearSuccessfulTasksAction()
    {
        $task_codes = $this->_getTaskCodesParam();
        $task_code = $this->getRequest()->getParam('task_codes', null);
        try
        {
            $rows_deleted = $this->_getTaskProcessor()->deleteSuccessfulTasks($task_codes, $task_code);
        }
        catch(\Exception $e)
        {
            $task_codes_string = implode(', ', $task_codes);
            $error_message = __(sprintf(self::ERROR_CLEARING_SUCCESSFUL_TASKS, $task_codes_string, $e->getMessage()));
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $redirecturl = 'reverbsync/reverbsync_listings/imagesync'; 
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($redirecturl);
            return $resultRedirect;
        }

        if (!empty($task_code))
        {
            $success_message = __(sprintf(self::SUCCESS_CLEARED_SUCCESSFUL_TASKS_WITH_CODE, $task_code));
        }
        else
        {
            $success_message = __(self::SUCCESS_CLEARED_SUCCESSFUL_TASKS);
        }

        $this->_adminHelper->addAdminSuccessMessage($success_message);
    }

    /**
     * @return array|null
     */
    protected function _getTaskCodesParam()
    {
        $task_codes_param = $this->getRequest()->getParam('task_codes', null);
        $task_codes = explode(';', $task_codes_param);
        if (!is_array($task_codes) || empty($task_codes))
        {
            return null;
        }

        return $task_codes;
    }

    public function getModuleGroupname()
    {
        return '\Reverb\ProcessQueue';
    }

    public function getControllerActiveMenuPath()
    {
        return 'system/reverb_process_queue';
    }

    public function getModuleInstanceDescription()
    {
        return 'Process Queue Tasks';
    }

    public function getIndexBlockName()
    {
        return 'adminhtml_task_index';
    }

    public function getObjectParamName()
    {
        return 'task_id';
    }

    public function getObjectDescription()
    {
        return 'Task';
    }

    public function getModuleInstance()
    {
        return 'Task';
    }

    public function getFormBlockName()
    {
        return 'adminhtml_task';
    }

    public function getIndexActionsController()
    {
        return 'ProcessQueue_index';
    }

    /**
     * @return Reverb_ProcessQueue_Helper_Task_Processor
     */
    protected function _getTaskProcessor()
    {
        return $this->_processqueueTaskProcessor;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Admin
     */
    protected function _getAdminHelper()
    {
        if (is_null($this->_adminHelper))
        {
            $this->_adminHelper = Mage::helper('ReverbSync/admin');
        }

        return $this->_adminHelper;
    }

    /**
    * Init layout, menu and breadcrumb
    *
    * @return Reverb_ProcessQueue_Adminhtml_ProcessQueue_Unique_IndexController
    */
    protected function _initAction()
    {
        $module_groupname = $this->getModuleGroupname();
        $module_description = $this->getModuleInstanceDescription();

        $this->loadLayout()
            ->_setActiveMenuValue()
            ->_setSetupTitle(Mage::helper($module_groupname)->__($module_description))
            ->_addBreadcrumb()
            ->_addBreadcrumb(Mage::helper($module_groupname)->__($module_description), Mage::helper($module_groupname)->__($module_description));
            
        return $this;
    }
}
