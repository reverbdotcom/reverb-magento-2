<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation;
class Helper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_BEING_SYNCED_REGISTRY_KEY = 'current_reverb_sync_order';

    protected $_moduleName = 'ReverbSync';

    protected $_shippingHelper = null;
    protected $_paymentHelper = null;
    protected $_addressHelper = null;
    protected $_customerHelper = null;

    protected $_reverbLogger;

    protected $_registry;

    public function __construct(
        \Reverb\ReverbSync\Model\Log $reverblogger,
        \Reverb\ReverbSync\Helper\Orders\Creation\Shipping $shippingHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Payment $paymentHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Address $addressHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Customer $customerHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->_reverbLogger = $reverblogger;
        $this->_shippingHelper = $shippingHelper;
        $this->_paymentHelper = $paymentHelper;
        $this->_addressHelper = $addressHelper;
        $this->_customerHelper = $customerHelper;
        $this->_registry = $registry;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Orders_Creation_Shipping
     */
    protected function _getShippingHelper()
    {
        return $this->_shippingHelper;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Orders_Creation_Payment
     */
    protected function _getPaymentHelper()
    {
        return $this->_paymentHelper;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Orders_Creation_Address
     */
    protected function _getAddressHelper()
    {
        return $this->_addressHelper;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Orders_Creation_Customer
     */
    protected function _getCustomerHelper()
    {
        return $this->_customerHelper;
    }

    public function getExplodedNameFields($name_as_string)
    {
        $exploded_name = explode(' ', $name_as_string);
        $first_name = array_shift($exploded_name);
        if (empty($exploded_name))
        {
            // Only one word was provided in the name field, default last name to "Customer"
            $last_name = "Customer";
            $middle_name = '';
        }
        else if (count($exploded_name) > 1)
        {
            // Middle name was provided
            $middle_name = array_shift($exploded_name);
            $last_name = implode(' ', $exploded_name);
        }
        else
        {
            $middle_name = '';
            $last_name = implode(' ', $exploded_name);
        }

        return array($first_name, $middle_name, $last_name);
    }

    public function _logOrderSyncError($error_message)
    {
        $this->_reverbLogger->logOrderSyncError($error_message);
    }

    public function _setOrderBeingSyncedInRegistry($reverbOrderObject)
    {
        $this->unsetOrderBeingSynced();
        $this->_registry->register(self::ORDER_BEING_SYNCED_REGISTRY_KEY, $reverbOrderObject);
    }

    public function getOrderBeingSyncedInRegistry()
    {
        return $this->_registry->registry(self::ORDER_BEING_SYNCED_REGISTRY_KEY);
    }

    public function unsetOrderBeingSynced()
    {
        $this->_registry->unregister(self::ORDER_BEING_SYNCED_REGISTRY_KEY);
    }
}
