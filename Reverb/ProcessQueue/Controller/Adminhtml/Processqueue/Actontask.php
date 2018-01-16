<?php
namespace Reverb\ProcessQueue\Controller\Adminhtml\Processqueue;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Actontask extends \Magento\Backend\App\Action
{

     /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_modelTaskUnique;

    protected $_processqueueTaskProcessor;
    
    protected $_adminHelper;
    
    public function __construct(Context $context, 
        PageFactory $resultPageFactory,
        \Reverb\ProcessQueue\Model\Task\Unique $modelTaskUnique,
        \Reverb\ProcessQueue\Model\Task $modelTask,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskProcessor,
        \Reverb\ReverbSync\Helper\Admin $adminhelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_modelTaskUnique = $modelTaskUnique;
        $this->_modelTask = $modelTask;
        $this->_processqueueTaskProcessor = $taskProcessor;
        $this->_adminHelper = $adminhelper;
    }

    public function execute(){
        $_controller_description = 'Processqueue Actontask';
        $_quote_task = $this->_modelTaskUnique;

        $task_id = $this->getRequest()->getParam('task_id');
        $type = $this->getRequest()->getParam('type');
        $redirecturl = 'reverbsync/reverbsync_listings/imagesync';
        if($type=='order_update'){
            $_quote_task = $this->_modelTask;
            $_controller_description = $_controller_description.' '.$type;
            $redirecturl = 'reverbsync/reverbsync_orders/sync';
        }
        if($type=='shipment_tracking_sync'){
            $_controller_description = $_controller_description.' '.$type;
            $redirecturl = 'reverbsync/reverbsync_orders_sync/unique';
        }
        $_quote_task = $_quote_task->load($task_id);
        
        if ((!is_object($_quote_task)) || (!$_quote_task->getId())) {
            $error_message = __(\Reverb\ProcessQueue\Controller\Adminhtml\Processqueue\Index::EXCEPTION_INVALID_TASK_ID, $_controller_description, $task_id);
            $this->_adminHelper->addAdminErrorMessage($error_message);
        }

        try {
            // This shouldn't fail based on Processor code.
            $this->_processqueueTaskProcessor->processQueueTask($_quote_task);
        } catch(\Exception $e) {
            $error_message = sprintf(\Reverb\ProcessQueue\Controller\Adminhtml\Processqueue\Index::EXCEPTION_ACT_ON_TASK, $_controller_description, $task_id, $e->getMessage());
            $this->_adminHelper->addAdminErrorMessage($error_message);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($redirecturl);
            return $resultRedirect;
        }

        $action_text = $_quote_task->getActionText();
        $notice_message = sprintf(\Reverb\ProcessQueue\Controller\Adminhtml\Processqueue\Index::NOTICE_TASK_ACTION, $_controller_description, $task_id); //,$action_text
        $this->_adminHelper->addNotice($notice_message);
        
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($redirecturl);
        return $resultRedirect;
    }
}