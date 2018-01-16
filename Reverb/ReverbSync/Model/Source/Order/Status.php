<?php
namespace Reverb\ReverbSync\Model\Source\Order;
class Status
{
    const PAID_ORDER_STATUSES_CONFIG_PATH = 'ReverbSync/orders_sync/paid_order_statuses';

    /**
     * @var null|array
     */
    protected $_paid_order_statuses_array = null;

    protected $scopeconfig;

    public function __construct(
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeconfig
    ) {
        $this->scopeconfig = $scopeconfig;
    }

    /**
     * Returns an array containing the Reverb Order statuses which have been configured as being "paid" in the system
     *
     * @return array
     */
    public function getPaidOrderStatusesArray()
    {
        if (is_null($this->_paid_order_statuses_array))
        {
            $paid_order_statuses_array = $this->scopeconfig->getValue(self::PAID_ORDER_STATUSES_CONFIG_PATH);
            if (!is_array($paid_order_statuses_array))
            {
                // If no statuses have been configured as paid, return an empty array
                return array();
            }
            $this->_paid_order_statuses_array = array_keys($paid_order_statuses_array);
        }

        return $this->_paid_order_statuses_array;
    }
}
