<?php
namespace Reverb\ReverbSync\Helper\Orders;
class Creation extends \Reverb\ReverbSync\Helper\Orders\Creation\Helper
{
    const ERROR_AMOUNT_PRODUCT_MISSING = 'The amount_product object, which is supposed contain the product\'s price, was not found';
    const ERROR_AMOUNT_TAX_MISSING = 'The amount_tax object, which is supposed contain the product\'s tax amount, was not found';
    const ERROR_INVALID_SKU = 'An attempt was made to create an order in magento for a Reverb order which had an invalid sku %s';
    const INVALID_CURRENCY_CODE = 'An invalid currency code %s was defined.';
    const EXCEPTION_UPDATE_STORE_NAME = 'An error occurred while setting the store name to %s for order with Reverb Order Id #%s: %s';
    const EXCEPTION_CONFIGURED_STORE_ID = 'An exception occurred while attempting to load the store with the configured store id of %s: %s';
    const EXCEPTION_REVERB_ORDER_CREATION_EVENT_OBSERVER = 'An exception occurred while firing the reverb_order_creation event for order with Reverb Order Number #%s: %s';

    const STORE_TO_SYNC_ORDERS_TO_CONFIG_PATH = 'ReverbSync/orders_sync/store_to_sync_order_to';

    const REVERB_ORDER_STORE_NAME = 'Reverb';

    protected $_ordersSyncHelper;

    protected $_scopeconfig;

    protected $_sourceStore;

    protected $_reverbLogger;

    protected $_customerHelper;

    protected $_addressHelper;

    protected $_productFactory;
    
    protected $_quoteManagement;
    protected $_customerFactory;
    protected $_customerRepository;
    protected $_orderService;

    protected $_productRepository;

    protected $_cartRepositoryInterface;
    protected $_cartManagementInterface;
    protected $_shippingRate;

    protected $_cartmodel;

    protected $_currencyHelper;

    protected $_currencyModel;

    protected $_eventManager;

    protected $_orderModel;

    public function __construct(
        \Reverb\ReverbSync\Helper\Orders\Sync $ordersSyncHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeconfig,
        \Reverb\ReverbSync\Model\Source\Store $sourceStore,
        \Reverb\ReverbSync\Model\Log $reverblogger,
        \Reverb\ReverbSync\Helper\Orders\Creation\Address $addressHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Customer $customerHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Shipping $shippingHelper,
        \Reverb\ReverbSync\Helper\Orders\Creation\Payment $paymentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerfactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Checkout\Model\Cart $cartmodel,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Framework\Registry $registry,
        \Reverb\ReverbSync\Helper\Orders\Creation\Currency $currencyHelper,
        \Magento\Directory\Model\Currency $currencyModel,
        \Reverb\ReverbSync\Model\Resource\Order $syncResourceOrder,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Model\Order $orderModel
    ) {
        parent::__construct($reverblogger, $shippingHelper, $paymentHelper, $addressHelper, $customerHelper, $registry);
        $this->_ordersSyncHelper = $ordersSyncHelper;
        $this->_scopeconfig = $scopeconfig;
        $this->_sourceStore = $sourceStore;
        $this->_reverbLogger = $reverblogger;
        $this->_customerHelper = $customerHelper;
        $this->_addressHelper = $addressHelper;
        $this->_storeManager = $storeManager;
        $this->_productFactory = $productFactory;
        $this->_productRepository = $productRepository;
        $this->_quoteManagement = $quoteManagement;
        $this->_customerFactory = $customerfactory;
        $this->_customerRepository = $customerRepository;
        $this->_orderService = $orderService;
        $this->_cartmodel = $cartmodel;
        $this->_cartRepositoryInterface = $cartRepositoryInterface;
        $this->_cartManagementInterface = $cartManagementInterface;
        $this->_shippingRate = $shippingRate;
        $this->_currencyHelper = $currencyHelper;
        $this->_currencyModel = $currencyModel;
        $this->_syncResourceOrder = $syncResourceOrder;
        $this->_eventManager = $eventManager;
        $this->_registry = $registry;
        $this->_orderModel = $orderModel;
    }
    
    public function createMagentoOrder(\stdClass $reverbOrderObject)
    {
        // Including this check here just to ensure that orders aren't synced if the setting is disabled
        if (!$this->_ordersSyncHelper->isOrderSyncEnabled())
        {
            $exception_message = $this->_ordersSyncHelper->getOrderSyncIsDisabledMessage();
            throw new \Reverb\ReverbSync\Model\Exception\Deactivated\Order\Sync($exception_message);
        }
       
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        $cart_id = $this->_cartManagementInterface->createEmptyCart();
        $cart = $this->_cartRepositoryInterface->get($cart_id);
        $cart->setStore($store);

        $reverb_order_number = $reverbOrderObject->order_number;
        

        if ($this->_ordersSyncHelper->isOrderSyncSuperModeEnabled())
        {
            // Process this quote as though we were an admin in the admin panel
            $cart->setIsSuperMode(true);
        }

        $productToAddToQuote = $this->_getProductToAddToQuote($reverbOrderObject);
        $qty = $reverbOrderObject->quantity;
        if (empty($qty))
        {
            $qty = 1;
        }
        $qty = intval($qty);
        $cart->addProduct($productToAddToQuote, $qty);


        try{
          
            $customerid = $this->_customerHelper->addCustomerToQuote($reverbOrderObject, $cart);

            $customer= $this->_customerRepository->getById($customerid);
            $cart->setCurrency();
            $cart->assignCustomer($customer); 

            $this->_getAddressHelper()->addOrderAddressAsShippingAndBillingToQuote($reverbOrderObject, $cart);
    
            $this->_getShippingHelper()->setShippingMethodAndRateOnQuote($reverbOrderObject, $cart);
            
            $this->_getPaymentHelper()->setPaymentMethodOnQuote($reverbOrderObject, $cart);
            
            $cart->setInventoryProcessed(false);
            $cart->collectTotals();
            $cart->setReverbOrderId($reverb_order_number);
            $cart->save();

            $this->_addReverbItemLinkToQuoteItem($cart, $reverbOrderObject);
            $this->_addTaxAndCurrencyToQuoteItem($cart, $reverbOrderObject);
            
            $cartnew = $this->_cartRepositoryInterface->get($cart->getId());
            $cartnew->setReverbOrderId($reverb_order_number);
            $order_id = $this->_cartManagementInterface->placeOrder($cartnew->getId());

            $order = $this->_orderModel->load($order_id);

        }catch(\Exception $e){
            echo 'customererror = ';
            echo $e->getMessage();
            exit;
            $error_message = 'Reverb Order Create Error = '.$e->getMessage();
            $this->_logOrderSyncError($error_message);
        }

        try
        {
            // Update store name as adapter query for performance consideration purposes
            $this->_syncResourceOrder->setReverbStoreNameByReverbOrderId($order->getId(),$reverb_order_number, self::REVERB_ORDER_STORE_NAME);
        }
        catch(\Exception $e)
        {
            // Log the exception but don't stop execution
            $error_message = __(self::EXCEPTION_UPDATE_STORE_NAME, self::REVERB_ORDER_STORE_NAME, $reverb_order_number, $e->getMessage());
            $this->_logOrderSyncError($error_message);
        }

        try
        {
            // Dispatch an event for clients to hook in to regarding order creation
            $this->_eventManager->dispatch('reverb_order_created',
                                array('magento_order_object' => $order, 'reverb_order_object' => $reverbOrderObject)
            );
        }
        catch(\Exception $e)
        {
            // Log the exception but don't stop execution
            $error_message = __(self::EXCEPTION_REVERB_ORDER_CREATION_EVENT_OBSERVER, $reverb_order_number,
                                        $e->getMessage());
            $this->_logOrderSyncError($error_message);
        }

         $this->_registry->unregister(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY);

        return $order;
    }

    protected function _getProductToAddToQuote(\stdClass $reverbOrderObject)
    {
        $sku = $reverbOrderObject->sku;
        $product = $this->_productRepository->get($sku);
        
        if ((!is_object($product)) || (!$product->getId()))
        {
            $error_message = __(self::ERROR_INVALID_SKU, $sku);
            throw new \Exception($error_message);
        }

        $amountProductObject = $reverbOrderObject->amount_product;
        if (!is_object($amountProductObject))
        {
            $error_message = __(self::ERROR_AMOUNT_PRODUCT_MISSING);
            throw new \Exception($error_message);
        }

        $amount = $amountProductObject->amount;
        if (empty($amount))
        {
            $amount = "0.00";
        }
        $product_cost = floatval($amount);
        $product->setPrice($product_cost);

        return $product;
    }

    protected function _addReverbItemLinkToQuoteItem($quoteToBuild, $reverbOrderObject)
    {
        $items = $quoteToBuild->getAllVisibleItems();
        if(is_array($items)){
            foreach ($items as $key => $item) {
                if($item->getSku()==$reverbOrderObject->sku){
                    if (isset($reverbOrderObject->_links->listing->href))
                    {
                        $listing_api_url_path = $reverbOrderObject->_links->listing->href;
                        $item->setReverbItemLink($listing_api_url_path);
                        $item->save();
                    }        
                }
            }
        }
    }

    protected function _addTaxAndCurrencyToQuoteItem($quoteToBuild, $reverbOrderObject)
    {
        if (property_exists($reverbOrderObject, 'amount_tax'))
        {
            $amountTaxObject = $reverbOrderObject->amount_tax;
            if (is_object($amountTaxObject))
            {
                $tax_amount = $amountTaxObject->amount;
                if (empty($tax_amount))
                {
                    $tax_amount = "0.00";
                }
            }
            else
            {
                $tax_amount = "0.00";
            }
        }
        else
        {
            $tax_amount = "0.00";
        }

        $totalBaseTax = floatval($tax_amount);

        $quoteItem = $quoteToBuild->getItemsCollection()->getFirstItem();
        $quoteItem->setBaseTaxAmount($totalBaseTax);
        //$totalTax = $quoteToBuild->getStore()->convertPrice($totalBaseTax);
        $quoteItem->setTaxAmount($totalBaseTax);
        $amountProductObject = $reverbOrderObject->amount_product;
        $currency_code = $amountProductObject->currency;
        
        if (!empty($currency_code))
        {
            if (!$this->_currencyHelper->isValidCurrencyCode($currency_code))
            {
                $error_message = __(self::INVALID_CURRENCY_CODE, $currency_code);
                throw new \Exception($error_message);
            }
        }
        else
        {
            $currency_code = $this->_currencyHelper->getDefaultCurrencyCode();
        }
        $currencyToForce = $this->_currencyModel->load($currency_code);
        $quoteToBuild->setForcedCurrency($currencyToForce);
    }

    protected function _getStore()
    {
        // Check to see if the system configured store id is valid
        $system_configured_store_id = $this->_getSystemConfigurationStoreId();
        if ((!is_null($system_configured_store_id)) && ($system_configured_store_id !== false))
        {
            return $system_configured_store_id;
        }

        return false;
        
    }

    protected function _getSystemConfigurationStoreId()
    {
        try
        {
            $configured_store_id = $this->_scopeconfig->getValue(self::STORE_TO_SYNC_ORDERS_TO_CONFIG_PATH);
            if ($this->_sourceStore->isAValidStoreId($configured_store_id))
            {
                return $configured_store_id;
            }
        }
        catch(\Exception $e)
        {
            $error_message = __(self::EXCEPTION_CONFIGURED_STORE_ID, $configured_store_id, $e->getMessage());
            $this->_logOrderSyncError($error_message);
        }

        return false;
    }

    public function _logOrderSyncError($error_message)
    {
        $this->_reverbLogger->logOrderSyncError($error_message);
    }
}
