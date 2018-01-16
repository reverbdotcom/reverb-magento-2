<?php
namespace Reverb\ReverbSync\Helper\Orders\Retrieval;
/*use Magento\Framework\DataObject;*/
class Update extends \Reverb\ReverbSync\Helper\Orders\Retrieval
{
    const ORDERS_UPDATE_RETRIEVAL_URL_TEMPLATE = '/api/my/orders/selling/all?updated_start_date=%s';

    // Reach back a day for updates
    const MINUTES_IN_PAST_FOR_UPDATE_QUERY = 1440;

    protected $_orderUpdateTaskResourceSingleton = null;

     protected $_orderSyncHelper;

    protected $_reverblogger;

    protected $_datetime;

    protected $_reverbSyncHelper;

    public function __construct(
        \Reverb\ReverbSync\Model\Log $reverblogger,
        \Reverb\ReverbSync\Helper\Orders\Sync $orderSyncHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Reverb\ReverbSync\Helper\Data $reverbSyncHelper,
        \Reverb\ReverbSync\Model\Resource\Task\Order\Update $orderUpdateTaskResourceSingleton
    ) {
        parent::__construct($reverblogger, $orderSyncHelper, $datetime, $reverbSyncHelper);
        $this->_orderSyncHelper = $orderSyncHelper;
        $this->_reverblogger = $reverblogger;
        $this->_orderUpdateTaskResourceSingleton = $orderUpdateTaskResourceSingleton;
    }

    public function queueOrderActionByReverbOrderDataObject($orderDataObject)
    {
        return $this->_getOrderUpdateTaskResourceSingleton()->queueOrderUpdateByReverbOrderDataObject($orderDataObject);
    }

    protected function _getAPICallUrlPathTemplate()
    {
        return self::ORDERS_UPDATE_RETRIEVAL_URL_TEMPLATE;
    }

    protected function _getMinutesInPastForAPICall()
    {
        return self::MINUTES_IN_PAST_FOR_UPDATE_QUERY;
    }

    public function getAPICallDescription()
    {
        return 'retrieveOrderUpdates';
    }

    public function getOrderSyncAction()
    {
        return 'update';
    }

    /**
     * @return Reverb_ReverbSync_Model_Mysql4_Task_Order_Update
     */
    protected function _getOrderUpdateTaskResourceSingleton()
    {
        return $this->_orderUpdateTaskResourceSingleton;
    }
}
