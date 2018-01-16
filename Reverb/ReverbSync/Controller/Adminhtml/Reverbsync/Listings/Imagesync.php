<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Listings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Imagesync extends \Magento\Backend\App\Action
{

	const NOTICE_TASK_ACTION = 'The attempt to sync image file %s for product %s on Reverb has completed.';
	/**
	 * @var PageFactory
	 */
	protected $resultPageFactory;
	
	public function __construct(Context $context, PageFactory $resultPageFactory) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute(){
		$resultPage = $this->resultPageFactory->create();
		$resultPage->setActiveMenu('Reverb_ReverbSync::reverb_listings_sync');
		$resultPage->getConfig()->getTitle()->prepend((__('Reverb Listings Image Sync Tasks')));
		return $resultPage;
	}
}
?>