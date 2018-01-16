<?php
namespace Reverb\ReverbSync\Observer;
use Magento\Framework\Event\ObserverInterface;
class Shipment implements ObserverInterface
{
    const ERROR_SEND_TRACKING_INFO_TO_REVERB = 'An error occurred while attempting to send tracking shipment data to Reverb: %s';

    protected $_reverbShipmentHelper = null;

    protected $_taskprocessorUnique;

    protected $_reverbLogger;

    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Reverb\ProcessQueue\Helper\Task\Processor\Unique $taskprocessorUnique,
        \Reverb\ReverbSync\Model\Log $reverblogger,
        \Reverb\ReverbSync\Helper\Shipment\Data $shipmentHelper
    ) {
        $this->_objectManager = $objectManager;
        $this->_taskprocessorUnique = $taskprocessorUnique;
        $this->_reverbLogger = $reverblogger;
        $this->_reverbShipmentHelper = $shipmentHelper;
    }
   
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try
        {
            $shipmentTrackingObject = $observer->getTrack();
            $trackingSyncQueueTaskObject = $this->_getReverbShipmentHelper()->queueShipmentTrackingSyncIfReverbOrder($shipmentTrackingObject);
            if (is_object($trackingSyncQueueTaskObject) && $trackingSyncQueueTaskObject->getId())
            {
                // Attempt to execute the task object
                $this->_taskprocessorUnique->processQueueTask($trackingSyncQueueTaskObject);
            }
        }
        catch(\Exception $e)
        {
            $error_message = __(sprintf(self::ERROR_SEND_TRACKING_INFO_TO_REVERB, $e->getMessage()));
            $this->_getReverbShipmentHelper()->logError($error_message);
        }
    }

    protected function _getReverbShipmentHelper()
    {
        return $this->_reverbShipmentHelper;
    }
}
