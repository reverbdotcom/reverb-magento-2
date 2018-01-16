<?php
namespace Reverb\ReverbSync\Model\Resource\Category\Reverb;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('\Reverb\ReverbSync\Model\Category\Reverb','\Reverb\ReverbSync\Model\Resource\Category\Reverb');
        //$this->_init('reverbSync/category_reverb');
    }

    public function addReverbCategoryIdFilter(array $reverb_category_ids)
    {
        $this->addFieldToFilter('reverb_category_id', $reverb_category_ids);
        return $this;
    }

    public function addCategoryUuidFilter($category_uuid)
    {
        if (!is_array($category_uuid))
        {
            $category_uuid = array($category_uuid);
        }

        $this->addFieldToFilter(\Reverb\ReverbSync\Model\Category\Reverb::UUID_FIELD, array('in' => $category_uuid));
        return $this;
    }
}
