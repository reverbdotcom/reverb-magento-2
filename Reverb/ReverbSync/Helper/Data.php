<?php
namespace Reverb\ReverbSync\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper implements \Reverb\ReverbSync\Helper\Api\Adapter\Interfaceclass
{
    const MODULE_NOT_ENABLED = 'The Reverb Module is not enabled, so products can not be synced with Reverb. Please enable this functionality in System -> Configuration -> Reverb Configuration -> Reverb Extension';
    const ERROR_LISTING_CREATION_IS_NOT_ENABLED = 'Reverb listing creation has not been enabled.';
    const ERROR_EMPTY_RESPONSE = 'The API call returned an empty response. Curl error message: %s';
    const ERROR_RESPONSE_ERROR = "The API call response contained errors: %s\nCurl error message: %s";
    const ERROR_API_STATUS_NOT_OK = "The API call returned an HTTP status that was not 200: %s.\nURL: %s\nContent: %s\nCurl Error Message: %s";
    const ERROR_LISTING_IMAGE_SYNC = 'An error occurred while queueing listing image syncs for the product: %s';

    const LISTING_CREATION_ENABLED_CONFIG_PATH = 'ReverbSync/reverbDefault/enable_listing_creation';

    // In the event that no configuration value was returned for the base url, default to the sandbox URL
    // It's better to make erroneous calls to the sandbox than to production
    const DEFAULT_REVERB_BASE_API_URL = 'https://reverb.com';

    const API_CALL_LOG_TEMPLATE = "\n%s\n%s\n%s\n%s\n";

    const HTTP_STATUS_OK = 200;
    const HTTP_STATUS_SUCCESS_REGEX = '/^2[0-9]{2}$/';

    const LISTING_STATUS_ERROR = 0;
    const LISTING_STATUS_SUCCESS = 1;


    /**
     * @var null|Reverb_ReverbSync_Model_Log
     */
    protected $_getLogSingleton = null;

    protected $_modelWrapperListing;

    protected $_adapterCurl;

    protected $_scopeConfig;

    protected $_syncLog;

    protected $_mapperProduct;

    protected $_syncImage;

    protected $eventManager;

    protected $_logger;

    public function __construct(
        \Reverb\ReverbSync\Model\Wrapper\Listing $modelWrapperListing,
        \Reverb\ReverbSync\Model\Adapter\Curl $adapterCurl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Reverb\ReverbSync\Model\Log $syncLog,
        \Reverb\ReverbSync\Model\Mapper\Product $mapperProduct,
        \Reverb\ReverbSync\Helper\Sync\Image $syncImage,
        \Magento\Framework\Event\Manager $eventManager,
        \Reverb\ReverbSync\Model\Logger $logger
    ) {
        $this->_modelWrapperListing = $modelWrapperListing;
        $this->_adapterCurl = $adapterCurl;
        $this->_scopeConfig = $scopeConfig;
        $this->_syncLog = $syncLog;
        $this->_mapperProduct = $mapperProduct;
        $this->_syncImage = $syncImage;
        $this->eventManager = $eventManager;
        $this->_logger = $logger;
    }

    /**
     * @param $product
     * @param bool $do_not_allow_creation
     * @return false|Reverb_ReverbSync_Model_Wrapper_Listing
     */
    public function createOrUpdateReverbListing($product, $do_not_allow_creation = false)
    {
        // Create empty wrapper in the event an exception is thrown
        $listingWrapper = $this->_modelWrapperListing;
        /* @var $listingWrapper Reverb_ReverbSync_Model_Wrapper_Listing */
        $listingWrapper->setMagentoProduct($product);

        try
        {
            $magento_sku = $product->getSku();
            $reverb_listing_url = $this->findReverbListingUrlByMagentoSku($magento_sku);
            if ($reverb_listing_url)
            {
                $listingWrapper = $this->_mapperProduct->getUpdateListingWrapper($product);
                $reverb_web_url = $this->updateObject($listingWrapper, $reverb_listing_url);
            }
            else if(!$do_not_allow_creation)
            {
                $listingWrapper = $this->_mapperProduct->getCreateListingWrapper($product);
                $reverb_web_url = $this->createObject($listingWrapper);

                // If we are here, we know that the creation sync attempt worked. Attempt to queue image sync for
                //  all of the product's gallery images
                try
                {
                    $this->_syncImage->queueImageSyncForProductGalleryImages($product);
                }
                catch(\Exception $e)
                {
                    $listingWrapper->setReverbWebUrl($reverb_web_url);
                    $error_message = __(sprintf(self::ERROR_LISTING_IMAGE_SYNC, $e->getMessage()));
                    throw new \Reverb\ReverbSync\Model\Exception\Listing\Image\Sync($error_message);
                }
            }
            else
            {
                // On order placement, only listing update should be allowed, not creation
                return false;
            }

            $listingWrapper->setReverbWebUrl($reverb_web_url);
        }
        catch(\Reverb\ReverbSync\Model\Exception\Status\Error $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());

            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);
        }
        catch(\Reverb\ReverbSync\Model\Exception\Listing\Image\Sync $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());

            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            // In this event, the actual listing creation succeeded, so set the status to success
            $listingWrapper->setStatus(self::LISTING_STATUS_SUCCESS);
        }
        catch(\Exception $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());
            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);
        }
        $this->eventManager->dispatch('reverb_listing_synced', ['reverb_listing' => $listingWrapper]);
        return $listingWrapper;
    }

	public function createOrUpdateReverbBulkListing($product, $do_not_allow_creation = false)
    {
        // Create empty wrapper in the event an exception is thrown
        $listingWrapper = $this->_modelWrapperListing;
        /* @var $listingWrapper Reverb_ReverbSync_Model_Wrapper_Listing */
        $listingWrapper->setMagentoProduct($product);

        try
        {
            $magento_sku = $product->getSku();
            $reverb_listing_url = $this->findReverbListingUrlByMagentoSku($magento_sku);
            if ($reverb_listing_url)
            {
                $listingWrapper = $this->_mapperProduct->getUpdateListingWrapper($product);
                $reverb_web_url = $this->updateObject($listingWrapper, $reverb_listing_url);
            }
            else if(!$do_not_allow_creation)
            {
                $listingWrapper = $this->_mapperProduct->getCreateListingWrapper($product);
                $reverb_web_url = $this->createObject($listingWrapper);

                // If we are here, we know that the creation sync attempt worked. Attempt to queue image sync for
                //  all of the product's gallery images
                try
                {
                    $this->_syncImage->queueImageSyncForProductGalleryImages($product);
                }
                catch(\Exception $e)
                {
                    $listingWrapper->setReverbWebUrl($reverb_web_url);
                    $error_message = __(sprintf(self::ERROR_LISTING_IMAGE_SYNC, $e->getMessage()));
                    throw new \Reverb\ReverbSync\Model\Exception\Listing\Image\Sync($error_message);
                }
            }
            else
            {
                // On order placement, only listing update should be allowed, not creation
                return false;
            }

            $listingWrapper->setReverbWebUrl($reverb_web_url);
        }
        catch(\Reverb\ReverbSync\Model\Exception\Status\Error $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());

            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);
        }
        catch(\Reverb\ReverbSync\Model\Exception\Listing\Image\Sync $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());

            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            // In this event, the actual listing creation succeeded, so set the status to success
            $listingWrapper->setStatus(self::LISTING_STATUS_SUCCESS);
        }
        catch(\Exception $e)
        {
            $this->_getLogSingleton()->logReverbMessage($e->getMessage());
            // Log Exception on reports row
            $listingWrapper->setSyncDetails($e->getMessage());
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);
        }
        $this->eventManager->dispatch('reverb_listing_synced', ['reverb_listing' => $listingWrapper]);
        return $listingWrapper;
    }

    /**
     * @param $fieldsArray
     * @param $entityType - Being passed in as 'listings'
     * @return mixed
     * @throws Exception
     */
    public function createObject($listingWrapper)
    {
        // Ensure that listing creation is enabled
        $listing_creation_is_enabled = $this->isListingCreationEnabled();
        if (!$listing_creation_is_enabled)
        {
            throw new \Reverb\ReverbSync\Model\Exception\Status\Error(self::ERROR_LISTING_CREATION_IS_NOT_ENABLED);
        }

        // Construct URL for API Request
        $rev_url = $this->_getReverbAPIBaseUrl();
        $url = $rev_url . "/api/listings";
        // Get post body content for API Request
        $fieldsArray = $listingWrapper->getApiCallContentData();
        $content = json_encode($fieldsArray);
        // Execute API Request via CURL
        $curlResource = $this->_getCurlResource($url);
        $post_response_as_json = $curlResource->executePostRequest($content);
        $status = $curlResource->getRequestHttpCode();
        // Need to grab any potential errors before closing the resource
        $curl_error_message = $curlResource->getCurlErrorMessage();
        $curlResource->logRequest();
        // Close the CURL Resource
        $curlResource->close();

        //comment log
        $this->_logApiCall($content, $post_response_as_json, 'createObject', $status);

        $response = json_decode($post_response_as_json, true);

        if (is_null($response))
        {
            $response = array();
            $response['message'] = 'The response could not be decoded as a json.';
        }

        if (!$this->_isStatusSuccessful($status))
        {
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);

            if (isset($response['errors'])) {
                $errors_messaging = $response['message'] . $response['errors'][key($response['errors'])][0];
                $listingWrapper->setSyncDetails($errors_messaging);
                throw new Exception($errors_messaging);
            } else {
                if (!empty($curl_error_message))
                {
                    $listingWrapper->setSyncDetails($curl_error_message);
                    throw new \Exception($curl_error_message);
                }
                $error_message = $response['message'];
                $listingWrapper->setSyncDetails($error_message);
                throw new \Exception($error_message);
            }
        }

        $listingWrapper->setStatus(self::LISTING_STATUS_SUCCESS);
        $listingWrapper->setSyncDetails(null);
        $listing_response = isset($response['listing']) ? $response['listing'] : array();
        $web_url = $this->_getWebUrlFromListingResponseArray($listing_response);

        return $web_url;
    }

    protected function _getListingsApiEndpoint($magento_sku)
    {
        $rev_url = $this->_getReverbAPIBaseUrl();
        $escaped_sku = urlencode($magento_sku);
        $params = "state=all&sku=" . $escaped_sku;
        $url = $rev_url . "/api/my/listings?" . $params;
        return $url;
    }

    /**
     * /api/my/listings?sku=#{CGI.escape(sku)}&
     *
     * Returns self listing link if returned, null otherwise
     *
     * @param $magento_sku
     * @return string|null
     * @throws Exception
     */
    public function findReverbListingUrlByMagentoSku($magento_sku)
    {
        // Execute API Request via CURL
        $curlResource = $this->_getCurlResource($this->_getListingsApiEndpoint($magento_sku));
        $json_response = $curlResource->read();
        $status = $curlResource->getRequestHttpCode();
        // Need to grab any potential errors before closing the resource
        $curl_error_message = $curlResource->getCurlErrorMessage();
        $curlResource->logRequest();
        // Close the CURL Resource
        $curlResource->close();
        // Log the response
        $params = "state=all&sku=" . $magento_sku;
        //comment log
        $this->_logApiCall($params, $json_response, 'findReverbListingUrlByMagentoSku', $status);

        $response = json_decode($json_response, true);

        if (is_null($response))
        {
            $response = array();
            $response['message'] = 'The response could not be decoded as a json.';
        }

        if (!$this->_isStatusSuccessful($status))
        {
            if (isset($response['errors'])) {
                throw new Exception($response['message'] . $response['errors'][key($response['errors'])][0]);
            } else {
                if (!empty($curl_error_message))
                {
                    throw new Exception($curl_error_message);
                }
                throw new Exception($response['message']);
            }

        }

        $listings_array = isset($response['listings']) ? ($response['listings']) : array();
        if (empty($listings_array))
        {
            // If no listings were returned, return null
            return null;
        }
        // The listing we are looking for should be the first element in the $listings_array (index 0)
        $listing_array = is_array($listings_array) ? reset($listings_array) : array();
        $self_links_href = $this->_getUpdatePutLinksHrefFromListingResponseArray($listing_array);

        return $self_links_href;
    }

    protected function _getWebUrlFromListingResponseArray(array $listing_response)
    {
        return isset($listing_response['_links']['web']['href'])
            ? $listing_response['_links']['web']['href'] : null;
    }

    protected function _getUpdatePutLinksHrefFromListingResponseArray(array $listing_response)
    {
        return isset($listing_response['_links']['self']['href'])
                ? $listing_response['_links']['self']['href'] : null;
    }

    public function updateObject($listingWrapper, $url_to_put)
    {
        // Construct the URL for the API call
        $rev_url = $this->_getReverbAPIBaseUrl();
        $rev_url_to_put  = $rev_url . $url_to_put;
        // Get the PUT content for the API call
        $fieldsArray = $listingWrapper->getApiCallContentData();
        $content = json_encode($fieldsArray);
        // Exeucte the API PUT Request as CURL
        $curlResource = $this->_getCurlResource($rev_url_to_put);
        $put_response_as_json = $curlResource->executePutRequest($content);
        $status = $curlResource->getRequestHttpCode();
        // Need to grab any potential errors before closing the resource
        $curl_error_message = $curlResource->getCurlErrorMessage();
        $curlResource->logRequest();
        // Close the CURL Resource
        $curlResource->close();

        //comment log
        $this->_logApiCall($content, $put_response_as_json, 'updateObject', $status);

        $response = json_decode($put_response_as_json, true);

        if (!$this->_isStatusSuccessful($status))
        {
            $listingWrapper->setStatus(self::LISTING_STATUS_ERROR);
            if (!empty($curl_error_message))
            {
                $listingWrapper->setSyncDetails($curl_error_message);
                throw new \Exception($curl_error_message);
            }

            $error_message = $response['message'];
            $listingWrapper->setSyncDetails($error_message);

            throw new \Exception($error_message);
        }

        $listingWrapper->setSyncDetails(null);
        $listingWrapper->setStatus(self::LISTING_STATUS_SUCCESS);
        $listing_response = isset($response['listing']) ? $response['listing'] : array();
        $web_url = $this->_getWebUrlFromListingResponseArray($listing_response);

        return $web_url;
    }

    public function verifyModuleIsEnabled()
    {
        $isEnabled = $this->_scopeConfig->getValue('ReverbSync/extensionOption_group/module_select');
        if (!$isEnabled)
        {
             $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
            die(self::MODULE_NOT_ENABLED);
            //throw new \Reverb\ReverbSync\Model\Exception\Deactivated(self::MODULE_NOT_ENABLED);
        }
        return true;
    }

    protected function _processCurlRequestResponse($response_as_json, $curlResource, $content_body = null)
    {
        $status = $curlResource->getRequestHttpCode();
        // Need to grab any potential errors before closing the resource
        $curl_error_message = $curlResource->getCurlErrorMessage();
        $curlResource->logRequest();
        // Close the CURL Resource
        $curlResource->close();

        //comment log
        $this->_logApiCall($content_body, $response_as_json, $this->getApiLogFileSuffix(), $status);


        $response_as_array = json_decode($response_as_json, true);
        // Ensure the status code is of the form 2xx
        if (!$this->_isStatusSuccessful($status))
        {
            $api_url = $curlResource->getOption(CURLOPT_URL);
            $error_message = __(sprintf(self::ERROR_API_STATUS_NOT_OK, $status, $api_url, $content_body, $curl_error_message));
            $this->_logErrorAndThrowException($error_message);
        }
        // Ensure that the response was not empty
        if (empty($response_as_json))
        {
            $error_message = __(sprintf(self::ERROR_EMPTY_RESPONSE, $curl_error_message));
            $this->_logErrorAndThrowException($error_message);
        }
        // Ensure that the response did not signal any errors occurred
        if (isset($response_as_array['errors']) && !empty($response_as_array['errors']))
        {
            $errors_as_string = json_encode($response_as_array['errors']);
            $error_message = __(sprintf(self::ERROR_RESPONSE_ERROR, $errors_as_string, $curl_error_message));
            $this->_logErrorAndThrowException($error_message);
        }

        return $response_as_array;
    }

    public function processCurlRequestResponse($response_as_json, $curlResource, $content_body = null)
    {
        return $this->_processCurlRequestResponse($response_as_json, $curlResource, $content_body = null);
    }

    protected function _isStatusSuccessful($status)
    {
        return preg_match(self::HTTP_STATUS_SUCCESS_REGEX, $status);
    }

    protected function _logErrorAndThrowException($error_message)
    {
        $this->logError($error_message);
        $exceptionToThrow = $this->getExceptionObject($error_message);
        throw $exceptionToThrow;
    }

    /**
     * This method can be overwritten to return api-call specific exception objects
     *
     * @param string $error_message - Exception message
     * @return Reverb_ReverbSync_Model_Exception_Api
     */
    public function getExceptionObject($error_message)
    {
        throw new \Exception($error_message);
    }

    /**
     * This method expected to be overridden by subclasses to target api-call specific log files
     * @param $error_message
     */
    public function logError($error_message)
    {
        $this->_getLogSingleton()->logReverbMessage($error_message);
    }

    /**
     * This method expected to be overridden by subclasses to target api-call specific log files
     * @return string
     */
    public function getApiLogFileSuffix()
    {
        return 'curl_request';
    }

    protected function _getReverbAPIBaseUrl()
    {
        $base_url = $this->_scopeConfig->getValue('ReverbSync/extension/revUrl');
        if (empty($base_url))
        {
            $base_url = self::DEFAULT_REVERB_BASE_API_URL;
        }

        return $base_url;
    }

    public function getReverbBaseUrl()
    {
        return $this->_getReverbAPIBaseUrl();
    }

    /**
     * @param $url
     * @param array $options_array
     * @return \Reverb\ReverbSync\Model\Adapter\Curl
     */
    protected function _getCurlResource($url, $options_array = array())
    {
        $curlResource = $this->_adapterCurl;
        $options_array[CURLOPT_SSL_VERIFYHOST] = 0;
        $options_array[CURLOPT_SSL_VERIFYPEER] = 0;
        $options_array[CURLOPT_HEADER] = 0;
        $options_array[CURLOPT_RETURNTRANSFER] = 1;

        $x_auth_token = $this->_scopeConfig->getValue('ReverbSync/extension/api_token');
        $options_array[CURLOPT_HTTPHEADER] = array("Authorization: Bearer $x_auth_token", "Content-type: application/hal+json");

        $options_array[CURLOPT_URL] = $url;

        $curlResource->setOptions($options_array);

        return $curlResource;
    }

    public function getCurlResource($url, $options_array = array()){
        return $this->_getCurlResource($url, $options_array = array());
    }

    protected function _logApiCall($request, $response, $api_request, $status)
    {
        $message = sprintf(self::API_CALL_LOG_TEMPLATE, $api_request, $request, $status, $response);
        $file = 'reverb_sync_' . $api_request . '.log';
        $this->_getLogSingleton()->logReverbMessage($message, $file);
    }

    /**
     * @return Reverb_ReverbSync_Model_Log
     */
    protected function _getLogSingleton()
    {
        return $this->_syncLog;
    }

    public function isListingCreationEnabled()
    {
        $listing_creation_enabled = $this->_scopeConfig->getValue(self::LISTING_CREATION_ENABLED_CONFIG_PATH);
        $this->_listing_creation_is_enabled = (!empty($listing_creation_enabled));
        return $this->_listing_creation_is_enabled;
    }
}
