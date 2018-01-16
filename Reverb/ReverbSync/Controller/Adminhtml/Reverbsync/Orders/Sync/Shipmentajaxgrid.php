<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Orders\Sync;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Shipmentajaxgrid extends \Magento\Backend\App\Action
{

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
        $resultPage = $this->resultPageFactory->create();
		return $resultPage;
	}
}

