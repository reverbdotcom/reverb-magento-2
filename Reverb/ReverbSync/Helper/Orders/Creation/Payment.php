<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation;
class Payment extends \Magento\Framework\App\Helper\AbstractHelper//extends \Reverb\ReverbSync\Helper\Orders\Creation\Sync
{
    protected $_payment_method_code = 'reverbpayment';

    protected $_registry;

    public function __construct(
            \Magento\Framework\Registry $registry,
            \Reverb\ReverbSync\Model\Log $reverblogger
        ) {
        $this->_registry = $registry;
        $this->_reverblogger = $reverblogger;
    }

    public function setPaymentMethodOnQuote($reverbOrderObject, $quoteToBuild)
    {
        try{
            $this->_registry->unregister(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY);
            $this->_registry->register(\Reverb\ReverbSync\Helper\Orders\Creation\Helper::ORDER_BEING_SYNCED_REGISTRY_KEY, $reverbOrderObject);

            $quoteToBuild->getShippingAddress()->setPaymentMethod($this->_payment_method_code);
            $quoteToBuild->getShippingAddress()->setCollectShippingRates(true);
            $quoteToBuild->setPaymentMethod($this->_payment_method_code);
            
            $quoteToBuild->getPayment()->importData(['method' => $this->_payment_method_code]);
            $quoteToBuild->setTotalsCollectedFlag(false);
            $quoteToBuild->collectTotals();
            $quoteToBuild->save();

        } catch(\Exception $e){
            $this->_getLogModel()->logReverbMessage('FILE =='.__FILE__.',method = '.__FUNCTION__.', message = '.$e->getMessage());
        }
    }
}
