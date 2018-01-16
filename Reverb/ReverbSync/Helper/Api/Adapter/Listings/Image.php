<?php
namespace Reverb\ReverbSync\Helper\Api\Adapter\Listings;
class Image implements \Reverb\ReverbSync\Helper\Api\Adapter\Interfaceclass// extends \Reverb\ReverbSync\Helper\Data 
{
    const PHOTOS_PARAM_NAME = 'photos';

    protected $_adapterListingsFetch;

    protected $_reverbLog;

    public function __construct(
        \Reverb\ReverbSync\Helper\Api\Adapter\Listings\Fetch $adapterListingsFetch,
        \Reverb\ReverbSync\Model\Log $reverbLog,
        \Reverb\ReverbSync\Helper\Data $reverbSyncHelper
    ) {
        $this->_adapterListingsFetch = $adapterListingsFetch;
        $this->_reverbLog = $reverbLog;
        $this->_reverbSyncHelper = $reverbSyncHelper;
    }

    public function transmitGalleryImageToReverb($magento_sku, $image_url)
    {
        // Get the API Endpoint URL for this sku
        $api_endpoint_url = $this->_adapterListingsFetch->getUpdatePutLinkBySku($magento_sku);

        // Get API Request body content
        $api_params = array(self::PHOTOS_PARAM_NAME => array($image_url));
        $post_fields_content = json_encode($api_params);
        // Get cURL Resource
        $curlResource = $this->_reverbSyncHelper->getCurlResource($api_endpoint_url);
        // Execute the API request
        $post_response_as_json = $curlResource->executePutRequest($post_fields_content);
        // If there are any errors with the api call or response, the method below will throw an Exception
        $response_as_array = $this->_reverbSyncHelper->processCurlRequestResponse($post_response_as_json, $curlResource, $post_fields_content);
        // If the response did contain errors, the above method would have thrown an Exception
        /*echo '<pre>';
        print_r($response_as_array);
        exit;*/
        return $response_as_array;
    }

    /**
     * A string description of the API call. It will be used to specify which file to log the curl requests to
     *
     * @return mixed
     */
    public function getApiLogFileSuffix()
    {
        return 'listings_image';
    }

    /**
     * This method should return the exception object specific to an API call if one exists. Otherwise, it should return
     *  an object of type Reverb_ReverbSync_Model_Exception_Api
     *
     * @param string $error_message - Exception message
     * @return Reverb_ReverbSync_Model_Exception_Api
     */
    public function getExceptionObject($error_message)
    {
        $exceptionObject = new Reverb_ReverbSync_Model_Exception_Api_Listings_Image($error_message);
        return $exceptionObject;
    }

    /**
     * Used to allow for logging errors to specific files. By default, it should call
     *      Mage::getSingleton('reverbSync/log')->logSyncError($error_message);
     *      if no specific log file exists for a specific API call
     *
     * @param $error_message
     */
    public function logError($error_message)
    {
        $this->_reverbLog->logListingImageSyncError($error_message);
    }
}
