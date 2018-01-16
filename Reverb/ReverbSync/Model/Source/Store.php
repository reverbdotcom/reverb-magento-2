<?php
namespace Reverb\ReverbSync\Model\Source;
class Store implements \Magento\Framework\Option\ArrayInterface
{
    protected $_store_option_hash = [];

    protected $systemStore;

    public function __construct(
         \Magento\Store\Model\StoreManagerInterface $storemanager,
         \Magento\Store\Model\System\Store $systemStore
    ) {
        $this->storemanager = $storemanager;
        $this->systemStore = $systemStore;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    { 
        return $store_option_hash = $this->_getStoreOptionHash();
        /*$translated_store_to_option_array = array();
        foreach($store_option_hash as $store_id => $store_name)
        {
            $translated_store_to_option_array[] = array(
                'value' => $store_id,
                'label' => __($store_name),
            );
        }

        return $translated_store_to_option_array;*/
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $store_option_hash = $this->_getStoreOptionHash();
        $translated_store_option_hash = array();
        foreach($store_option_hash as $store_id => $store_name)
        {
            $translated_store_option_hash[$store_id] = __($store_name);
        }

        return $translated_store_option_hash;
    }

    public function isAValidStoreId($store_id)
    {
        $store_id = intval($store_id);
        $store_option_hash = $this->_getStoreOptionHash();
        return (isset($store_option_hash[$store_id]));
    }

    protected function _getStoreOptionHash()
    {
        $this->_store_option_hash = $this->systemStore->getStoreValuesForForm(false, true);//$this->storemanager->getStores(true);
        return $this->_store_option_hash;
    }
}
