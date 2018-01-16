<?php
namespace Reverb\ReverbSync\Model\Category\Reverb\Magento;

class Xref extends \Magento\Framework\Model\AbstractModel
{
    const REVERB_CATEGORY_UUID_FIELD = 'reverb_category_uuid';
    const MAGENTO_CATEGORY_ID_FIELD = 'magento_category_id';

    protected function _construct()
    {
        $this->_init('Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref');
        //$this->_init('reverbSync/category_reverb_magento_xref');
    }
}
