<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Orders;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Orderajaxgrid extends \Magento\Backend\App\Action
{
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
        $resultPage = $this->resultPageFactory->create();
		return $resultPage;
	}
}
