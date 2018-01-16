<?php
namespace Reverb\ReverbSync\Helper\Sync;
class Product  extends \Magento\Framework\App\Helper\AbstractHelper
{
    const UNCAUGHT_EXCEPTION_INDIVIDUAL_PRODUCT_SYNC = 'Error attempting to sync product with id %s with Reverb: %s';
    const PRODUCT_EXCLUDED_FROM_SYNC = 'The "Sync to Reverb" value for this product has been set to "No"; this product can not be synced to Reverb as a result';
    const ERROR_INVALID_PRODUCT_TYPE = "Only %s products can be synced.";
    const ERROR_INVALID_PRODUCT = 'An attempt was made to sync an unloaded product to Reverb';
    const ERROR_NOT_SIMPLE_PRODUCT = 'Product with sku %s is not a simple product';

    protected $_allowed_product_types_for_sync = array(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,'configurable');

    protected $_reverbAdminHelper = null;
    protected $_productHelper = null;
    protected $_listing_creation_is_enabled = null;

    protected $_taskProcessor;

    protected $_productRepository;

    protected $reverbSyncDatahelper;

    protected $_baseHelperProduct;

    protected $_adminHelper;

    public function __construct(
        \Reverb\ReverbSync\Helper\Task\Processor $taskProcessor,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Reverb\ReverbSync\Helper\Data $reverbSyncDatahelper,
        \Reverb\Base\Helper\Product $baseHelperProduct,
        \Reverb\ReverbSync\Helper\Admin $adminHelper,
        \Reverb\ProcessQueue\Model\Resource\Taskresource $taskResource ,
        \Reverb\Reports\Model\Resource\Reverbreport $reverbreportResource
    ) {
        $this->_taskProcessor = $taskProcessor;
        $this->_productRepository = $productRepository;
        $this->reverbSyncDatahelper = $reverbSyncDatahelper;
        $this->_baseHelperProduct = $baseHelperProduct;
        $this->_adminHelper = $adminHelper;
        $this->_taskResource = $taskResource;
        $this->_reverbreportResource = $reverbreportResource;
    }


    public function queueUpBulkProductDataSync()
    {
        // We want this to throw an exception to the calling block if module is not enabled
        $this->_verifyModuleIsEnabled();

        $product_ids_in_system = $this->getReverbSyncEligibleProductIds();

        return $this->_taskProcessor->queueListingsSyncByProductIds($product_ids_in_system);
    }

    public function queueUpProductDataSync(array $product_ids_to_queue)
    {
        $this->_verifyModuleIsEnabled();

        return $this->_taskProcessor->queueListingsSyncByProductIds($product_ids_to_queue);
    }

    public function executeBulkProductDataSync()
    {
        $errors_array = array();

        // We want this to throw an exception to the calling block if module is not enabled
        $this->_verifyModuleIsEnabled();

        $product_ids_in_system = $this->getReverbSyncEligibleProductIds();

        foreach ($product_ids_in_system as $product_id)
        {
            try
            {
                $this->executeIndividualProductDataSync($product_id);
            }
            catch(Exception $e)
            {
                $errors_array[] = __(self::UNCAUGHT_EXCEPTION_INDIVIDUAL_PRODUCT_SYNC, $product_id, $e->getMessage());
            }
        }

        return $errors_array;
    }

    public function getReverbSyncEligibleProductIds()
    {
        $products = $this->_productRepository->getCollection()
                        ->addFieldToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
                        ->addFieldToFilter('reverb_sync', true);
        $ids = $products->getAllIds();

        return $ids;
    }

    /**
     * Calling block is expected to catch Exceptions. This allows more flexibility in terms of logging any exceptions
     *  as well as redirecting off of them
     *
     * @param $product_id
     * @return array - Array of Reverb_ReverbSync_Model_Wrapper_Listing
     * @throws Exception
     */
    public function executeIndividualProductDataSync($product_id, $do_not_allow_creation = false)
    {
        // We want this to throw an exception to the calling block if module is not enabled
        $this->_verifyModuleIsEnabled();
        //load the product
        $product = $this->_productRepository->getById($product_id);
        $productType = $product->getTypeID();
        if (!in_array($productType, $this->_allowed_product_types_for_sync))
        {
            $allowed_product_types = implode(', ', $this->_allowed_product_types_for_sync);
            $error_message = sprintf(self::ERROR_INVALID_PRODUCT_TYPE, $allowed_product_types);
            throw new \Reverb\ReverbSync\Model\Exception\Product\Excluded($error_message);
        }
        if (!$product->getReverbSync())
        {
            throw new \Reverb\ReverbSync\Model\Exception\Product\Excluded(self::PRODUCT_EXCLUDED_FROM_SYNC);
        }

        $listings_wrapper_array = array();
        if ($productType == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        {
            $listings_wrapper_array[] = $this->executeSimpleProductSync($product, $do_not_allow_creation);
        }
        else
        {
            $child_products = $this->_getProductHelper()->getSimpleProductsForConfigurableProduct($product);
            foreach($child_products as $simpleChildProduct)
            {
                $listings_wrapper_array[] = $this->executeSimpleProductSync($simpleChildProduct, $do_not_allow_creation);
            }
        }

        return $listings_wrapper_array;
    }

    /**
     * @param $simpleProduct
     * @param bool $do_not_allow_creation
     * @return Reverb_ReverbSync_Model_Wrapper_Listing
     * @throws Reverb_ReverbSync_Model_Exception_Product_Excluded
     * @throws Reverb_ReverbSync_Model_Exception_Product_Validation
     */
    public function executeSimpleProductSync($simpleProduct, $do_not_allow_creation = false)
    {
        if ((!is_object($simpleProduct)) || (!$simpleProduct->getId()))
        {
            $error_message = $this->__(self::ERROR_INVALID_PRODUCT);
            throw new Reverb_ReverbSync_Model_Exception_Product_Validation($error_message);
        }

        $productType = $simpleProduct->getTypeId();
        if ($productType == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        {
            //pass the data to create or update the product in Reverb
            $reverbSyncHelper = $this->reverbSyncDatahelper;
            /* @var $reverbSyncHelper Reverb_ReverbSync_Helper_Data */
            $listingWrapper = $reverbSyncHelper->createOrUpdateReverbListing($simpleProduct, $do_not_allow_creation);
            return $listingWrapper;
        }

        $product_sku = $simpleProduct->getSku();
        $error_message = $this->__(self::ERROR_NOT_SIMPLE_PRODUCT, $product_sku);
        throw new Reverb_ReverbSync_Model_Exception_Product_Excluded($error_message);
    }

    public function deleteAllListingSyncTasks()
    {
        $resourceSingleton = $this->_taskResource;
        return $resourceSingleton->deleteAllListingSyncTasks();
    }

    public function deleteAllReverbReportRows()
    {
        $resourceSingleton = $this->_reverbreportResource;
        return $resourceSingleton->deleteAllReverbReportRows();
    }

    protected function _verifyModuleIsEnabled()
    {
        return $this->reverbSyncDatahelper->verifyModuleIsEnabled();
    }

    protected function _setAdminSessionErrorMessage($error_message)
    {
        return $this->_getAdminHelper()->addAdminErrorMessage($error_message);
    }

    /**
     * @return Reverb_Base_Helper_Product
     */
    protected function _getProductHelper()
    {
        $this->_productHelper = $this->_baseHelperProduct;
        return $this->_productHelper;
    }

    protected function _getAdminHelper()
    {
        $this->_reverbAdminHelper = $this->_adminHelper;
        return $this->_reverbAdminHelper;
    }
}
