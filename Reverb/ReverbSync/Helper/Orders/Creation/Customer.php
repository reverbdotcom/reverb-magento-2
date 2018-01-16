<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation;
class Customer extends \Magento\Framework\App\Helper\AbstractHelper//extends \Reverb\ReverbSync\Helper\Orders\Creation\Sync
{
    const EXCEPTION_ADDING_CUSTOMER_NAME = 'An error occurred while trying to add the buyer\'s name to the quote while creating order with Reverb id #%s: %s';

    const BUYER_EMAIL_TEMPLATE = '%s@orders.reverb.com';

    protected $_storeManager;

    protected $_customerFactory;

    protected $_creationHelper;

    protected $_customerRepositoryInterface;

    protected $_reverbLogger;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerfactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Reverb\ReverbSync\Model\Log $reverblogger
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerfactory;
       $this->_reverbLogger = $reverblogger;
       $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }
    

    public function addCustomerToQuote($reverbOrderObject, $quoteToBuild)
    {
        $websiteId  = $this->_storeManager->getStore()->getWebsiteId();
        $magentoCustomerObject = $this->_customerFactory->create();
        $magentoCustomerObject->setWebsiteId($websiteId);
         if (isset($reverbOrderObject->buyer_id) && (!empty($reverbOrderObject->buyer_id)))
        {
            $buyer_id = $reverbOrderObject->buyer_id;
            $hashed_buyer_id = md5($buyer_id);
            $customer_email = sprintf(self::BUYER_EMAIL_TEMPLATE, $hashed_buyer_id);
        }
        $magentoCustomerObject->loadByEmail($customer_email);
        
        $reverb_order_number = $reverbOrderObject->order_number;
        try
        {

            if(!$magentoCustomerObject->getId()){
                if (isset($reverbOrderObject->buyer_name))
                {
                    $buyer_name_string = $reverbOrderObject->buyer_name;
                    list($first_name, $middle_name, $last_name) = $this->getExplodedNameFields($buyer_name_string);
                    $magentoCustomerObject->setFirstname($first_name);
                    $magentoCustomerObject->setMiddlename($middle_name);
                    $magentoCustomerObject->setLastname($last_name);
                }

                if (isset($reverbOrderObject->buyer_id) && (!empty($reverbOrderObject->buyer_id)))
                {
                    $buyer_id = $reverbOrderObject->buyer_id;
                    $hashed_buyer_id = md5($buyer_id);
                    $customer_email = sprintf(self::BUYER_EMAIL_TEMPLATE, $hashed_buyer_id);
                    $magentoCustomerObject->setEmail($customer_email);
                    $magentoCustomerObject->setPassword($hashed_buyer_id);
                }
                $magentoCustomerObject->save();
                return $magentoCustomerObject->getId();
            } else {
                return $magentoCustomerObject->getId();
            }
            //$customerrepository = $this->_customerRepositoryInterface->get($customer_email);
        }
        catch(\Exception $e)
        {
            $error_message = __(self::EXCEPTION_ADDING_CUSTOMER_NAME, $reverb_order_number, $e->getMessage());
             $this->_logOrderSyncError($error_message);
        }
        return false;
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
}
