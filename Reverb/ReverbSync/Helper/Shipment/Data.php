<?php
namespace Reverb\ReverbSync\Helper\Shipment;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const EXCEPTION_QUEUE_TRACKING_SYNC = 'An error occurred while attempting to queue shipment tracking sync with Reverb for tracking object with id %s: %s';
    const EXCEPTION_GET_REVERB_ORDER_ID = 'An error occurred while trying to obtain the Reverb Order Id for shipment tracking object with id %s: %s';
    const ERROR_NO_CARRIER_CODE_OR_TRACKING_NUMBER = 'An attempt was made to obtain the queue task unique id for a Reverb Shipment Tracking sync on a tracking object with insufficient data. Reverb Order Id was %s, Carrier Code was %s, and tracking number was %s.';
    const ERROR_CREATE_TRACKING_SYNC_QUEUE_OBJECT = 'System was unable to create a queue task object for shipment tracking object with id %s';

    protected $_moduleName = 'ReverbSync';

    protected $_objectManager;

    protected $_resourceOrder;

    protected $_shipmentTracking;

    protected $_resourceOrderSync;

    protected $_taskUnique;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Reverb\ReverbSync\Model\Resource\Order $resourceOrder,
        \Reverb\ReverbSync\Model\Log $reverblogger,
        \Reverb\ReverbSync\Model\Resource\Task\Shipment\Tracking $shipmentTracking,
        \Reverb\ReverbSync\Helper\Orders\Sync $resourceOrderSync,
        \Reverb\ProcessQueue\Model\Task\Unique $taskUnique
    ) {
        $this->_resourceOrder = $resourceOrder;
        $this->_reverbLogger = $reverblogger;
        $this->_shipmentTracking = $shipmentTracking;
        $this->_resourceOrderSync = $resourceOrderSync;
        $this->_taskUnique = $taskUnique;
    }
   
    /**
     * @param \Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject
     * @return Reverb_ProcessQueue_Model_Task_Unique|null
     */
    public function queueShipmentTrackingSyncIfReverbOrder(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        try
        {
            if (!$this->isTrackingForAReverbOrderShipment($shipmentTrackingObject))
            {
                return null;
            }

            $trackingSyncQueueTaskObject = $this->queueShipmentTrackingSyncWithReverb($shipmentTrackingObject);
            return $trackingSyncQueueTaskObject;
        }
        catch(\Exception $e)
        {
            // We shouldn't be catching anything here. If we do, a PHP level exception likely occurred
            $tracking_id = is_object($shipmentTrackingObject) ? $shipmentTrackingObject->getId() : null;
            $error_message = __(sprintf(self::EXCEPTION_QUEUE_TRACKING_SYNC, $tracking_id, $e->getMessage()));
            $this->logError($error_message);
        }

        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject
     *
     * @return Reverb_ProcessQueue_Model_Task_Unique
     */
    public function queueShipmentTrackingSyncWithReverb(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        // Ensure we haven't already created a queue task for this tracking number
        $unique_id_key = $this->getTrackingSyncQueueTaskUniqueId($shipmentTrackingObject);
        $task_primary_key = $this->_shipmentTracking->getQueueTaskIdForShipmentTrackingObject($shipmentTrackingObject, $unique_id_key);

        if (!empty($task_primary_key))
        {
            // This shipment tracking object already has an associated sync queue task
            return null;
        }
        
        $reverb_order_id = $this->getReverbOrderIdForMagentoShipmentTrackingObject($shipmentTrackingObject);
        $number_of_rows_inserted = $this->_shipmentTracking->queueShipmentTrackingTransmission($shipmentTrackingObject, $unique_id_key, $reverb_order_id, $this->_resourceOrderSync);
        if (!empty($number_of_rows_inserted))
        {
            $queueTaskObject = $this->_taskUnique->load($unique_id_key, 'unique_id');
            if (is_object($queueTaskObject) && $queueTaskObject->getId())
            {
                return $queueTaskObject;
            }
        }
        $error_message = __(sprintf(self::ERROR_CREATE_TRACKING_SYNC_QUEUE_OBJECT, $shipmentTrackingObject->getId()));
        $this->logError($error_message);
        throw new \Exception($error_message);
    }

    public function getTrackingSyncQueueTaskUniqueId(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        /* Even if we already ran this method in the Reverb_ReverbSync_Helper_Shipment_Data::isTrackingForAReverbOrderShipment()
             check, the necessary objects should have been cached on the tracking and shipment objects, which will prevent
             redundant database calls being made */
        $reverb_order_id = $this->getReverbOrderIdForMagentoShipmentTrackingObject($shipmentTrackingObject);
        $carrier_code = $shipmentTrackingObject->getCarrierCode();
        $tracking_number = $shipmentTrackingObject->getTrackNumber();

        if (empty($carrier_code) || empty($tracking_number))
        {
            $error_message = __(sprintf(self::ERROR_NO_CARRIER_CODE_OR_TRACKING_NUMBER, $reverb_order_id, $carrier_code, $tracking_number));
            throw new Reverb_ReverbSync_Model_Exception_Data_Shipment_Tracking($error_message);
        }

        return $reverb_order_id . '_' . $carrier_code . '_' . $tracking_number;
    }

    public function isTrackingForAReverbOrderShipment(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        $magento_entity_id = $this->getMagentoOrderEntityIdForTrackingObject($shipmentTrackingObject);
        if(empty($magento_entity_id))
        {
            return false;
        }
        $reverb_order_id = $this->getReverbOrderIdForMagentoShipmentTrackingObject($shipmentTrackingObject);
        if(empty($reverb_order_id))
        {
            return false;
        }

        return true;
    }

    public function getReverbOrderIdForMagentoShipmentTrackingObject(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        try
        {
            if (!is_object($shipmentTrackingObject))
            {
                // If tracking object is not persisted to the
                return null;
            }

            $shipmentObject = $shipmentTrackingObject->getShipment();
            if (is_object($shipmentTrackingObject) && $shipmentTrackingObject->getId())
            {
                $magentoOrder = $shipmentObject->getOrder();
                if (is_object($magentoOrder) && $magentoOrder->getId())
                {
                    return $magentoOrder->getReverbOrderId();
                }
            }

            $magento_entity_order_id = $this->getMagentoOrderEntityIdForTrackingObject($shipmentTrackingObject);
            if (!empty($magento_entity_order_id))
            {
                // Use an adapter query for performance considerations, and because we are likely in an aftersave event
                // observer right now
                $reverb_order_id = $this->_resourceOrder->getReverbOrderIdByMagentoOrderEntityId($magento_entity_order_id);
                return $reverb_order_id;
            }
        }
        catch(\Exception $e)
        {
            $tracking_shipment_id = $shipmentTrackingObject->getId();
            $error_message = __(sprintf(self::EXCEPTION_GET_REVERB_ORDER_ID, $tracking_shipment_id, $e->getMessage()));
            $this->logError($error_message);
        }

        return null;
    }

    public function getMagentoOrderEntityIdForTrackingObject(\Magento\Sales\Model\Order\Shipment\Track $shipmentTrackingObject)
    {
        $magento_order_id = $shipmentTrackingObject->getOrderId();
        if(!empty($magento_order_id))
        {
            // This should always be set for a tracking object which has been persisted to the database
            return $magento_order_id;
        }
        // Handle cases where the order_id is not set
        $shipmentObject = $shipmentTrackingObject->getShipment();
        if (is_object($shipmentObject) && $shipmentObject->getId())
        {
            return $shipmentObject->getOrderId();
        }

        return null;
    }

    public function logError($error_message)
    {
        $this->_reverbLogger->logShipmentTrackingSyncError($error_message);
    }
}
