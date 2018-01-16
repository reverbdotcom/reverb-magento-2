<?php
namespace Reverb\ReverbSync\Model\Category\Magento\Reverb;
 
class Mapping extends \Magento\Framework\Model\AbstractModel
{
    const MAGENTO_CATEGORY_ID_FIELD = 'magento_category_id';
    const REVERB_CATEGORY_ID_FIELD = 'reverb_category_id';

    protected function _construct()
    {
        $this->_init('Reverb\ReverbSync\Model\Resource\Category\Magento\Reverb\Mapping');
    }
}
