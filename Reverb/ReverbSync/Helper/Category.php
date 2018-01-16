<?php
namespace Reverb\ReverbSync\Helper;

class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    const EXCEPTION_REMOVING_CATEGORY = 'An exception occurred while attempting to remove Reverb category with id %s: %s';

    public function __construct(
        \Reverb\ReverbSync\Model\Category\Reverb $reverbCategoryModel,
        \Reverb\ReverbSync\Model\Logger $logger
    ) {
        $this->_reverbCategoryModel = $reverbCategoryModel;
        $this->_logger = $logger;
    }
	
	public function removeCategoriesWithoutUuid()
    {
        $categoriesWithoutUuid = $this->_reverbCategoryModel
                                    ->getCollection()
                                    ->addFieldToFilter(\Reverb\ReverbSync\Model\Category\Reverb::UUID_FIELD, '');

        foreach($categoriesWithoutUuid->getItems() as $categoryWithoutUuid)
        {
            try
            {
                $categoryWithoutUuid->delete();
            }
            catch(Exception $e)
            {
                $error_message = $this->__(self::EXCEPTION_REMOVING_CATEGORY, $categoryWithoutUuid->getId(),
                                           $e->getMessage());
				$this->_logger->info($error_message);
            }
        }
    }
}
