<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Category;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
class Updatecategories extends \Magento\Backend\App\Action
{
	const EXCEPTION_UPDATING_REVERB_CATEGORIES = 'An exception occurred while updating the Reverb categories in the system: %s';
    const SUCCESS_UPDATED_LISTINGS = 'Reverb category update completed';
	
	 /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_categoryhelper;

    protected $_;
    
    public function __construct(Context $context, PageFactory $resultPageFactory,
        \Reverb\ReverbSync\Helper\Category\Remap $remapcategory,
        \Reverb\ReverbSync\Helper\Category $categoryHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_remapCategory = $remapcategory;
        $this->_categoryhelper = $categoryHelper;
        $this->_messageManager = $messageManager;
    }

    public function execute(){

        /*$categoryUpdateSyncHelper = Mage::helper('ReverbSync/sync_category_update');
        $categoryUpdateSyncHelper->updateReverbCategoriesFromApi();*/
        $this->_remapCategory->remapReverbCategories();
        $this->_categoryhelper->removeCategoriesWithoutUuid();
        $this->_messageManager->addSuccess(__(self::SUCCESS_UPDATED_LISTINGS));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}