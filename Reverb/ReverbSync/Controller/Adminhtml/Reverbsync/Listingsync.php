<?php
/**
 * Reverb Report admin controller
 *
 * @category    Reverb
 * @package     Reverb_Reports
 */
namespace Reverb\Reverbsync\Controller\Adminhtml\Reverbsync;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

    class Listingsync extends \Magento\Backend\App\Action{

    	 /**
	     * @var PageFactory
	     */
	    protected $resultPageFactory;
	    
	    public function __construct(Context $context, PageFactory $resultPageFactory) {
	        parent::__construct($context);
	        $this->resultPageFactory = $resultPageFactory;
	    }

        public function execute(){

        	try {
        	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        	//$syncobj = $objectManager->create('Reverb\Process\Model\Locked\File\Cronprocess\Abstractclass');
        	//$syncobj = $objectManager->create('Magento\Catalog\Model\Product');
        	$syncobj = $objectManager->create('Reverb\ReverbSync\Model\Cron\Listings\Sync');
        	$syncobj->attemptCronExecution();
            echo 'success';
        	exit; 
        	} catch(\Exception $e){
        		echo 'logging error = ';
        		echo $e->getMessage();
        	}


            /*$resultPage = $this->resultPageFactory->create();
	        $resultPage->setActiveMenu('Reverb_ReverbSync::reverb_listings_sync');
	        $resultPage->getConfig()->getTitle()->prepend((__('Reverb Listing Sync')));
	        return $resultPage;*/
        }
    }
?>