<?php
namespace Reverb\Reports\Controller\Adminhtml\Reports;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Reverbajaxgrid extends \Magento\Backend\App\Action{
   protected $resultPageFactory;
    
    public function __construct(Context $context, PageFactory $resultPageFactory) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(){
    	$resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}