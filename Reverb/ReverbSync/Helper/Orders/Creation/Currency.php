<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation;
class Currency extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_currencyModel = null;

    protected $_code = 'reverbpayment';

    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\CurrencySymbol\Model\System\Currencysymbol $currencySymbol
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_currencySymbol = $currencySymbol;
    }

    public function isValidCurrencyCode($currency_code)
    {
        $allowed_currency_symbols_csv_list =
            $this->_scopeConfig->getValue(\Magento\CurrencySymbol\Model\System\Currencysymbol::XML_PATH_ALLOWED_CURRENCIES);
        $allowed_currency_symbols_array = explode(',', $allowed_currency_symbols_csv_list);

        return in_array($currency_code, $allowed_currency_symbols_array);
    }

    public function getDefaultCurrencyCode()
    {
        $default_currency_code = $this->_scopeConfig->getValue('currency/options/base');
        return $default_currency_code;
    }

    protected function _getCurrencyModel()
    {
        return $this->_currencySymbol;
    }
}
