<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation;
class Shipping extends \Magento\Framework\App\Helper\AbstractHelper//extends \Reverb\ReverbSync\Helper\Orders\Creation\Sync
{
    const ERROR_INVALID_SHIPPING_METHOD_CODE = 'Unable to get a rate for the Reverb Shipping Method';
    protected $_shipping_method_code = 'reverbshipping_reverbshipping';

    protected $_registry;

    protected $_shippingRate;

    public function __construct(
            \Magento\Framework\Registry $registry,
            \Magento\Quote\Model\Quote\Address\Rate $shippingRate
        ) {
        $this->_registry = $registry;
        $this->_shippingRate = $shippingRate;
    }
    /**
     * @param stdClass $reverbOrderObject
     * @param \Magento\Sales\Model\Quote $quoteToBuild
     * @throws Exception
     */
    public function setShippingMethodAndRateOnQuote($reverbOrderObject, $quoteToBuild)
    {
        $this->_registry->unregister(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY);
        $this->_registry->register(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY, $reverbOrderObject);

        $this->_shippingRate
                ->setCode($this->_shipping_method_code)
                ->getPrice(1);

        $shippingAddress = $quoteToBuild->getShippingAddress();
    
        $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($this->_shipping_method_code);

        $shipping_method_code = $this->_shipping_method_code;
        $rate = $quoteToBuild->getShippingAddress()->getShippingRateByCode($shipping_method_code);
      
        $quoteToBuild->getShippingAddress()->setShippingMethod($shipping_method_code);
        $quoteToBuild->setTotalsCollectedFlag(false);
        $quoteToBuild->collectTotals();
        $quoteToBuild->save();
        $quoteToBuild->getShippingAddress()->addShippingRate($this->_shippingRate);

    }

    /**
     * @param stdClass $reverbOrderObject
     * @param Mage_Customer_Model_Address $customerAddress
     * @param \Magento\Sales\Model\Quote $quoteToBuild
     * @throws Exception
     */
    public function addShippingAddressToQuote($reverbOrderObject, $customerAddress, $quoteToBuild)
    {
        $shippingQuoteAddress = Mage::getModel('sales/quote_address');
        /* @var \Magento\Sales\Model\Quote\Address $shippingQuoteAddress */
        $shippingQuoteAddress->setAddressType(\Magento\Sales\Model\Quote\Address::TYPE_SHIPPING);
        $quoteToBuild->addAddress($shippingQuoteAddress);

        $shippingQuoteAddress->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
        $addressForm = Mage::getModel('customer/form');
        /* @var Mage_Customer_Model_Form $addressForm */
        $addressForm->setFormCode('customer_address_edit')
                    ->setEntityType('customer_address')
                    ->setIsAjaxRequest(false);
        $addressForm->setEntity($shippingQuoteAddress);
        $addressErrors = $addressForm->validateData($shippingQuoteAddress->getData());
        if ($addressErrors !== true)
        {
            $address_errors_message = implode(', ', $addressErrors);
            $serialized_data_array = serialize($shippingQuoteAddress->getData());
            $error_message = sprintf(\Reverb\ReverbSync\Helper\Orders\Creation\Address::ERROR_VALIDATING_QUOTE_ADDRESS,
                                        $serialized_data_array, $address_errors_message);
            throw new \Exception($error_message);
        }

        $shippingQuoteAddress->implodeStreetAddress();
        $shippingQuoteAddress->setCollectShippingRates(true);
        if (($address_validation_errors_array = $shippingQuoteAddress->validate()) !== true)
        {
            $serialized_data_array = serialize($shippingQuoteAddress->getData());
            $address_errors_message = implode(', ', $address_validation_errors_array);
            $error_message = sprintf(\Reverb\ReverbSync\Helper\Orders\Creation\Address::ERROR_VALIDATING_QUOTE_ADDRESS,
                                        $serialized_data_array, $address_errors_message);
            throw new \Exception($error_message);
        }

        $this->_setOrderBeingSyncedInRegistry($reverbOrderObject);

        $quoteToBuild->setTotalsCollectedFlag(false);
        $quoteToBuild->collectTotals()->save();
    }
}
