<?php
namespace Reverb\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
 
class Reverb extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    protected $_code = 'reverbshipping';

    protected $_registry;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_registry = $registry;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @param RateRequest $request
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active'))
        {
            return false;
        }

          /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();
 
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();
 
        $transportObject = $this->_shouldMethodBeAllowed($request);
        if($transportObject->getShouldBeAllowed())
        {
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));

            $method->setPrice($transportObject->getShippingPrice());
            $method->setCost($transportObject->getShippingCost());

            $result->append($method);
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('reverbshipping' => $this->getConfigData('name'));
    }

    /**
     * @return Varien_Object
     */
    protected function _shouldMethodBeAllowed(RateRequest $request)
    {
        $orderBeingSynced  = $this->_registry->registry(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY);
        
        $transportObject = new \Magento\Framework\DataObject();
        $transportObject->setShouldBeAllowed(false);
        $transportObject->setShippingPrice(0.00);
        $transportObject->setShippingCost(0.00);

        if (isset($orderBeingSynced) && is_object($orderBeingSynced))
        {
            $shippingObject = $orderBeingSynced->shipping;
            if (is_object($shippingObject))
            {
                $shipping_amount = $shippingObject->amount;
                $shipping_amount_float = floatval($shipping_amount);
                $transportObject->setData('shipping_price', $shipping_amount_float);
            }

            $transportObject->setData('should_be_allowed', true);
        }

        return $transportObject;
    }
}
