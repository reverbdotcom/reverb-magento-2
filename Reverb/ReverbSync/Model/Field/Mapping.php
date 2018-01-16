<?php
namespace Reverb\ReverbSync\Model\Field;

class Mapping extends \Magento\Framework\Model\AbstractModel
{
    const MAGENTO_ATTRIBUTE_FIELD = 'magento_attribute_code';
    const REVERB_API_FIELD_FIELD = 'reverb_api_field';

    public function getMagentoAttributeCode()
    {
        return $this->getData(self::MAGENTO_ATTRIBUTE_FIELD);
    }

    public function getReverbApiField()
    {
        return $this->getData(self::REVERB_API_FIELD_FIELD);
    }

    protected function _construct()
    {
        $this->_init('Reverb\ReverbSync\Model\Resource\Field\Mapping');
    }
}
