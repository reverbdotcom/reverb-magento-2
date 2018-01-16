<?php
namespace Reverb\Reverbsync\Controller\Adminhtml\Reverbsync;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Staledelete extends \Magento\Backend\App\Action{

    protected $resultPageFactory;
    
    public function __construct(Context $context, PageFactory $resultPageFactory) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(){

    	try {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$syncobj = $objectManager->create('\Reverb\ProcessQueue\Model\Cron\Delete\Stale\Successful');
        $syncobj->deleteStaleSuccessfulQueueTasks();
        echo 'success';
    	exit; 
    	} catch(\Exception $e){
    		echo 'logging error = ';
    		echo $e->getMessage();
    	}
    }
}
?>