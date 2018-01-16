<?php
namespace Reverb\ReverbSync\Helper\Orders;
class Sync extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ORDER_SYNC_DISABLED_MESSAGE = 'Order Sync Is Not Enabled in System -> Configuration -> Reverb Configuration -> Order Sync -> Enable Order Sync';
    const ORDER_SYNC_ENABLED_CONFIG_PATH = 'ReverbSync/orders_sync/enabled';
    const ORDER_SYNC_SUPER_MODE_ENABLED_CONFIG_PATH = 'ReverbSync/orders_sync/super_mode_enabled';

    const ORDER_UPDATE_SYNC_ACL_PATH = 'reverb/reverb_order_task_sync_update';
    const ORDER_CREATION_SYNC_ACL_PATH = 'reverb/reverb_order_unique_task_sync_update';

    protected $_moduleName = 'ReverbSync';

    protected $_order_sync_is_disabled_message = null;

    protected $_reverblogger;

    protected $_scopeconfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeconfig,
        \Reverb\ReverbSync\Model\Log $reverblogger
    ) {
        $this->_scopeconfig = $scopeconfig;
        $this->_reverblogger = $reverblogger;
    }


    public function getReverbOrderItemByOrder($reverbOrder)
    {
        if ((!is_object($reverbOrder)) || (!$reverbOrder->getId()))
        {
            return false;
        }

        $order_items = $reverbOrder->getAllVisibleItems();
        // There should only be one item in any Reverb Order
        $orderItem = reset($order_items);
        if ((!is_object($orderItem)) || (!$orderItem->getId()))
        {
            return false;
        }

        return $orderItem;
    }

    public function canAdminChangeOrderCreationSyncStatus()
    {
        return Mage::getSingleton('admin/session')->isAllowed(self::ORDER_CREATION_SYNC_ACL_PATH);
    }

    public function canAdminChangeOrderUpdateSyncStatus()
    {
        return Mage::getSingleton('admin/session')->isAllowed(self::ORDER_UPDATE_SYNC_ACL_PATH);
    }

    public function isOrderSyncEnabled()
    {
        return $this->_scopeconfig->getValue(self::ORDER_SYNC_ENABLED_CONFIG_PATH);
    }

    public function isOrderSyncSuperModeEnabled()
    {
        return $this->_scopeconfig->getValue(self::ORDER_SYNC_SUPER_MODE_ENABLED_CONFIG_PATH);
    }

    /**
     * @return string
     */
    public function logOrderSyncDisabledMessage()
    {
        $error_message = $this->getOrderSyncIsDisabledMessage();
        $this->_logger->logOrderSyncError($error_message);
        return $error_message;
    }

    /**
     * @return string
     */
    public function getOrderSyncIsDisabledMessage()
    {
        return  __(self::ORDER_SYNC_DISABLED_MESSAGE);
    }
}
