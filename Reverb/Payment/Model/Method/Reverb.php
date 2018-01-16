<?php
namespace Reverb\Payment\Model\Method;
class Reverb extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_isInitializeNeeded = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;

    protected $_code = 'reverbpayment';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_registry = $registry;
        $this->_paymentData = $paymentData;
        $this->_scopeConfig = $scopeConfig;
        $this->logger = $logger;
        /*$this->initializeData($data);*/
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $orderBeingSynced  = $this->_registry->registry(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY);

        $transportObject = new \Magento\Framework\DataObject();
        $transportObject->setShouldBeAllowed(false);

        if (isset($orderBeingSynced) && is_object($orderBeingSynced))
        {
            $transportObject->setData('should_be_allowed', true);
        }

        if(!$transportObject->getShouldBeAllowed()){
            return false;
        }
        return parent::isAvailable($quote);
    }
}
