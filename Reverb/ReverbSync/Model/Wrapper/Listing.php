<?php
namespace Reverb\ReverbSync\Model\Wrapper;
class Listing extends \Magento\Framework\DataObject
{
    protected $_api_call_content_data = null;
    protected $_magentoProduct = null;
    
    public function wasCallSuccessful()
    {
        $status = $this->getStatus();
        return (\Reverb\ReverbSync\Helper\Data::LISTING_STATUS_SUCCESS == $status);
    }

    public function getMagentoProduct()
    {
        return $this->_magentoProduct;
    }

    public function setMagentoProduct($magentoProduct)
    {
        $this->_magentoProduct = $magentoProduct;
        return $this;
    }

    public function getApiCallContentData()
    {
        return $this->_api_call_content_data;
    }

    public function setApiCallContentData($api_call_content_data)
    {
        $this->_api_call_content_data = $api_call_content_data;
        return $this;
    }
}
