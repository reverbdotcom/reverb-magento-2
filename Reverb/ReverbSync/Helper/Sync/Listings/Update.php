<?php
/**
 * Author: Sean Dunagan
 * Created: 9/28/15
 */
namespace Reverb\ReverbSync\Helper\Sync\Listings;
class Update extends \Magento\Framework\App\Helper\AbstractHelper
{
    const UPDATE_FIELD_SWITCH_TITLE = 'ReverbSync/listings_update_switches/title';
    const UPDATE_FIELD_SWITCH_PRICE = 'ReverbSync/listings_update_switches/price';
    const UPDATE_FIELD_SWITCH_DESCRIPTION = 'ReverbSync/listings_update_switches/description';
    const UPDATE_FIELD_SWITCH_INVENTORY_QTY = 'ReverbSync/listings_update_switches/inventory_qty';

    protected $_reverb_listing_update_fields = array('sku', 'reverb_sync');

    protected $_scopeConfig;
    
    public function __construct(
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
       $this->_scopeConfig = $scopeConfig; 
    }



    public function shouldMassProductUpdateTriggerProductListingsSync($attribute_data, $inventory_data)
    {
        return ($this->shouldMassAttributeUpdateTriggerProductListingsSync($attribute_data)
                || $this->shouldMassInventoryUpdateTriggerProductListingsSync($inventory_data));
    }

    /**
     * @param $attributes_data
     * @return bool
     */
    public function shouldMassAttributeUpdateTriggerProductListingsSync($mass_attribute_update_data)
    {
        $magento_attributes = $this->getMassUpdateMagentoAttributesRelevantToReverbListingUpdates();

        $attributes_being_updated = array_keys($mass_attribute_update_data);
        $magento_attributes_being_updated = array_intersect($magento_attributes, $attributes_being_updated);

        return (!empty($magento_attributes_being_updated));
    }

    public function shouldMassInventoryUpdateTriggerProductListingsSync($inventory_data)
    {
        if ($this->isInventoryQtyUpdateEnabled())
        {
            if (isset($inventory_data['qty']))
            {
                return true;
            }
        }

        return false;
    }

    public function getMassUpdateMagentoAttributesRelevantToReverbListingUpdates()
    {
        $magento_attributes = $this->_reverb_listing_update_fields;

        if ($this->isTitleUpdateEnabled())
        {
            $magento_attributes[] = 'name';
        }

        if ($this->isDescriptionUpdateEnabled())
        {
            $magento_attributes[] = 'description';
        }

        $reverbProductMapper = Mage::getSingleton('reverbSync/mapper_product');
        /* @var $reverbProductMapper Reverb_ReverbSync_Model_Mapper_Product */

        if ($this->isPriceUpdateEnabled())
        {
            $magento_price_attribute = $reverbProductMapper->getMagentoPriceAttributeToMapToReverbPrice();
            $magento_attributes[] = $magento_price_attribute;
        }

        $mapped_magento_attributes = $reverbProductMapper->getMagentoAttributesMappedToReverbAttributes();
        $magento_attributes = array_merge($magento_attributes, $mapped_magento_attributes);

        $reverb_condition_attribute = $reverbProductMapper->getReverbConditionAttribute();
        $magento_attributes[] = $reverb_condition_attribute;

        return $magento_attributes;
    }

    public function isTitleUpdateEnabled()
    {
        return $this->_scopeConfig->getValue(self::UPDATE_FIELD_SWITCH_TITLE);
    }

    public function isPriceUpdateEnabled()
    {
        return $this->_scopeConfig->getValue(self::UPDATE_FIELD_SWITCH_PRICE);
    }

    public function isInventoryQtyUpdateEnabled()
    {
        return $this->_scopeConfig->getValue(self::UPDATE_FIELD_SWITCH_INVENTORY_QTY);
    }

    public function isDescriptionUpdateEnabled()
    {
        return $this->_scopeConfig->getValue(self::UPDATE_FIELD_SWITCH_DESCRIPTION);
    }
}
