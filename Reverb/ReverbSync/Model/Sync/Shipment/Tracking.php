<?php
namespace Reverb\ReverbSync\Model\Sync\Shipment;
class Tracking extends \Reverb\ProcessQueue\Model\Task
{
    const ERROR_INSUFFICIENT_DATA = "Insufficient data is set on a queue task object for Reverb Shipment Tracking Sync:\nReverb_Order Id: %s\nCarrier Code: %s\nTracking Number: %s";
    const ERROR_SEND_TRACKING_DATA = 'An error occurred while sending tracking data to Reverb: %s';
    const SUCCESS_SHIPMENT_TRACKING_SYNC = 'Shipment Tracking has been synced with Reverb';

    const JOB_CODE = 'shipment_tracking_sync';

    protected $_default_shipping_provider = 'Other';

    protected $_taskResult;

    protected $_datetime;

    protected $_shipmentTrackingSync;

    public function __construct(
        \Reverb\ProcessQueue\Model\Task\Result $taskResult,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Reverb\ReverbSync\Helper\Shipment\Tracking\Sync $shipmentTrackingSync
    ) {
        $this->_registry = $registry;
        $this->_taskResult = $taskResult;
        $this->_datetime = $datetime;
        $this->_shipmentTrackingSync = $shipmentTrackingSync;
        parent::__construct($taskResult, $datetime, $context, $registry);
    }

    protected $_carrier_code_to_provider_mapping = array(
        'fedex' => 'FedEx',
        'dhlint' => 'DHL',
        'dhl' => 'DHL',
        'ups' => 'UPS',
        'usps' => 'USPS',
        'canada_post' => 'Canada Post',
    );

    public function transmitTrackingDataToReverb($argumentsObject)
    {
        try
        {
            $reverb_order_id = isset($argumentsObject->reverb_order_id) ? $argumentsObject->reverb_order_id : null;
            $tracking_number = isset($argumentsObject->track_number) ? $argumentsObject->track_number : null;
            // As of 2015/09/22 senc_notification should always be true
            $send_notification = true;
            $carrier_code = isset($argumentsObject->carrier_code) ? $argumentsObject->carrier_code : null;
            // Ensure that we have the necessary data for transmission
            if (empty($reverb_order_id) || empty($tracking_number) || empty($carrier_code))
            {
                $error_message = __(sprintf(self::ERROR_INSUFFICIENT_DATA, $reverb_order_id, $carrier_code, $tracking_number));
                throw new \Reverb\ReverbSync\Model\Exception\Data\Shipment\Tracking($error_message);
            }
            $shipping_provider = $this->getReverbShippingProviderByMagentoCarrierCode($carrier_code);

            $api_call_response = $this->_shipmentTrackingSync->sendShipmentTrackingDataToReverb($reverb_order_id, $tracking_number,$shipping_provider, $send_notification);
        }
        catch(\Reverb\ReverbSync\Model\Exception\Data\Shipment\Tracking $e)
        {
            // Since we have insufficient data to process an API call, do not try this queue task again
            $error_message = __(sprintf(self::ERROR_SEND_TRACKING_DATA, $e->getMessage()));
            return $this->_returnAbortCallbackResult($error_message);
        }
        catch(Exception $e)
        {
            $error_message = __(sprintf(self::ERROR_SEND_TRACKING_DATA, $e->getMessage()));
            return $this->_returnErrorCallbackResult($error_message);
        }

        return $this->_returnSuccessCallbackResult(self::SUCCESS_SHIPMENT_TRACKING_SYNC);
    }

    public function getReverbShippingProviderByMagentoCarrierCode($carrier_code)
    {
        if (isset($this->_carrier_code_to_provider_mapping[$carrier_code]))
        {
            return $this->_carrier_code_to_provider_mapping[$carrier_code];
        }

        return $this->_default_shipping_provider;
    }
}
