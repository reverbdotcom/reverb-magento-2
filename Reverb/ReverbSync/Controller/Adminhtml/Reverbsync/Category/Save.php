<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\Reverbsync\Category;

class Save extends \Magento\Backend\App\Action
{
	const ERROR_SUBMISSION_NOT_POST = 'There was an error with your submission. Please try again.';
    const EXCEPTION_CATEGORY_MAPPING = 'An error occurred while attempting to set the Reverb-Magento category mapping: %s';
	
	/**
     * @param \Magento\Backend\App\Action\Context $context
     
    */
	
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Reverb\ReverbSync\Helper\Sync\Category $categorySyncHelper
    ) {
		$this->_categorySyncHelper = $categorySyncHelper;
        parent::__construct($context);
    }
	
	/*
    * Send all selected customers to emma , send all if none selected
    */
    public function execute()
    {
		$data = $this->getRequest()->getPostValue();
		if (!$this->getRequest()->getPostValue())
        {
            $error_message = self::ERROR_SUBMISSION_NOT_POST;
			$this->messageManager->addError($error_message);
        }

        $post_array = $this->getRequest()->getPostValue();

        try
        {
            $category_map_form_element_name = $this->_categorySyncHelper
                                                    ->getMagentoReverbCategoryMapElementArrayName();
            $category_mapping_array = isset($post_array[$category_map_form_element_name])
                                        ? $post_array[$category_map_form_element_name] : null;
            if (!is_array($category_mapping_array) || empty($category_mapping_array))
            {
                // This shouldn't occur, but account for the fact where it does
                $error_message = self::ERROR_SUBMISSION_NOT_POST;
				$this->messageManager->addError($error_message);
            }
            $this->_categorySyncHelper->processMagentoReverbCategoryMapping($category_mapping_array);
			$resultRedirect = $this->resultRedirectFactory->create();
			return $resultRedirect->setPath('reverbsync/reverbsync_category/sync');
		}
        catch(Exception $e)
        {
            $error_message = sprintf(self::EXCEPTION_CATEGORY_MAPPING, $e->getMessage());

            Mage::getSingleton('reverbSync/log')->logCategoryMappingError($error_message);
            $this->_setSessionErrorAndRedirect($error_message);
        }

        $this->_redirect('*/*/index');
	}
}