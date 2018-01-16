<?php
namespace Reverb\ReverbSync\Helper\Category;

class Remap extends \Magento\Framework\App\Helper\AbstractHelper
{
    const EXCEPTION_CATEGORY_FETCH_API = 'An exception occurred while attempting to fetch the updated Reverb categories json: %s';

    protected $_categoryUpdateHelper = null;
	
	/**
     * @var \Reverb\ReverbSync\Helper\Api\Adapter\Category
     */
    protected $categoryAdapterHelper;
	
	/**
     * @var \Reverb\ReverbSync\Model\Category\Reverb
     */
    protected $reverbCategoryModel;
	
	/**
     * @var \Reverb\ReverbSync\Model\Category\Magento\Reverb\Mapping
     */
    protected $reverbCategoryMappingModel;
	
	/**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Reverb\ReverbSync\Helper\Api\Adapter\Category $categoryAdapterHelper
     * @param \Reverb\ReverbSync\Model\Category\Reverb $reverbCategoryModel
     * @param \Reverb\ReverbSync\Model\Category\Magento\Reverb\Mapping $reverbCategoryMappingModel
     */
	public static $i=0; 

	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Reverb\ReverbSync\Helper\Api\Adapter\Category $categoryAdapterHelper,
		\Reverb\ReverbSync\Model\Category\Reverb $reverbCategoryModel,
        \Reverb\ReverbSync\Model\Category\ReverbFactory $reverbCategoryFactory,
		\Reverb\ReverbSync\Model\Category\Magento\Reverb\Mapping $reverbCategoryMappingModel,
        \Reverb\ReverbSync\Model\Log $reverbLogger
    ) {
		$this->_categoryAdapterHelper = $categoryAdapterHelper;
		$this->_reverbCategoryModel = $reverbCategoryModel;
        $this->_reverbCategoryFactory = $reverbCategoryFactory;
		$this->_reverbCategoryMappingModel = $reverbCategoryMappingModel;
        $this->_reverbLogger = $reverbLogger;
        parent::__construct($context);
    }

    public function remapReverbCategories()
    {
        try
        {
            // Attempt to retrieve the updated Reverb categories list
            $reverb_categories_as_array = $this->_categoryAdapterHelper->executeCategoryAPIFetch();
        }
        catch(\Exception $e)
        {
            // This should not occur, but in the event that it does the client will have to manually remap
            //      their categories after clicking the "Update Reverb Categories" button in the admin panel
            $error_message = __(sprintf(self::EXCEPTION_CATEGORY_FETCH_API, $e->getMessage()));
            $this->_reverbLogger->logCategoryMappingError($error_message);
        }
        /*echo 'test';
        echo count($reverb_categories_as_array);
        echo 'test';
        echo '<pre><br/>';
        $i=0;*/
        foreach($reverb_categories_as_array as $reverb_category_data_array)
        {
            try
            {
             /*   $i++;
                echo $reverb_category_data_array['id'].' ,name= '.$reverb_category_data_array['name'];
                echo '<br/>';*/
                $this->_processCategory($reverb_category_data_array);
            }
            catch(\Exception $e)
            {
                // Do nothing in this case
            }
        }
    }

    /**
     * @param $reverb_category_data_array
     * @throws Exception
     */
    protected function _processCategory($reverb_category_data_array)
    {
        $reverbCategory = $this->_getReverbCategoryBySlugs($reverb_category_data_array);

        $query_category_mappings_to_preserve = $this->_updateOrCreateReverbCategory($reverbCategory,
                                                                                    $reverb_category_data_array);
        if ($query_category_mappings_to_preserve)
        {
            //$this->_preserveCategoryMapping($reverbCategory);
        }
    }

    protected function _preserveCategoryMapping($reverbCategory)
    {
        $reverb_category_id = $reverbCategory->getId();
        // Retrieve all magento categories mapped to this reverb category id
        $legacy_reverb_magento_category_mappings_to_preserve
            = $this->_getMagentoCategoriesMappedToReverbCategory($reverb_category_id);
        if (!empty($legacy_reverb_magento_category_mappings_to_preserve))
        {
            foreach($legacy_reverb_magento_category_mappings_to_preserve as $categoryMappingObject)
            {
                /* @var $categoryMappingObject Reverb_ReverbSync_Model_Category_Magento_Reverb_Mapping */
                $magento_category_id = $categoryMappingObject->getMagentoCategoryId();
                try
                {
                    $reverb_category_uuid = $reverbCategory->getUuid();
                    $this->_createNewCategoryMapping($reverb_category_uuid, $magento_category_id);
                }
                catch(\Exception $e)
                {
                    // Do nothing in this event since this will occur during a migration script
                    // In this event the client will have to manually re-map their category
                }
            }
        }
    }

    /**
     * @param $reverbCategory
     * @param $reverb_category_data_array
     * @return bool
     * @throws Exception
     */
    protected function _updateOrCreateReverbCategory($reverbCategory, $reverb_category_data_array)
    {
        $reverb_category_uuid = isset($reverb_category_data_array['uuid']) ? $reverb_category_data_array['uuid'] : null;
        if(empty($reverb_category_uuid))
        {
            throw new \Exception('Missing uuid');
        }
        $reverb_parent_category_uuid = isset($reverb_category_data_array['root_uuid'])
                                        ? $reverb_category_data_array['root_uuid'] : null;
        if (!strcmp($reverb_parent_category_uuid, $reverb_category_uuid))
        {
            // If this category is a root category, the parent uuid field should be null
            $reverb_parent_category_uuid = null;
        }
        $category_name = isset($reverb_category_data_array['full_name']) ? $reverb_category_data_array['full_name'] : '';

        if (is_object($reverbCategory) && $reverbCategory->getId())
        {
            // Update the existing Reverb Category Row
            $reverbCategory->setUuid($reverb_category_uuid);
            $reverbCategory->setParentUuid($reverb_parent_category_uuid);
            $reverbCategory->setName($category_name);
            $query_category_mappings_to_preserve = true;
        }
        else
        {
            // Create a new Reverb Category Row
            $reverbCategory = $this->_reverbCategoryFactory->create();
            $reverbCategory->setData(\Reverb\ReverbSync\Model\Category\Reverb::NAME_FIELD, $category_name);
            $product_type_slug = isset($reverb_category_data_array['product_type_slug'])
                                    ? $reverb_category_data_array['product_type_slug'] : '';
            $reverbCategory->setData(\Reverb\ReverbSync\Model\Category\Reverb::PRODUCT_TYPE_SLUG_FIELD, $product_type_slug);
            $category_slug = isset($reverb_category_data_array['slug']) ? $reverb_category_data_array['slug'] : '';
            $reverbCategory->setData(\Reverb\ReverbSync\Model\Category\Reverb::CATEGORY_SLUG_FIELD, $category_slug);
            $reverbCategory->setData(\Reverb\ReverbSync\Model\Category\Reverb::UUID_FIELD, $reverb_category_uuid);
            $reverbCategory->setData(\Reverb\ReverbSync\Model\Category\Reverb::PARENT_UUID_FIELD, $reverb_parent_category_uuid);
            // Since we are going to create this category for the first time, there won't be any category
            //      mappings to it in the system
            $query_category_mappings_to_preserve = false;
        }
        $reverbCategory->save();
        
        return $query_category_mappings_to_preserve;
    }

    protected function _getReverbCategoryBySlugs($reverb_category_data_array)
    {
        $reverb_product_type_slug = isset($reverb_category_data_array['product_type_slug'])
                                        ? $reverb_category_data_array['product_type_slug'] : null;
        $reverb_category_slug = isset($reverb_category_data_array['slug']) ? $reverb_category_data_array['slug'] : null;

		$reverbCategory = $this->_reverbCategoryModel
                            ->getCollection()
                            ->addFieldToFilter('reverb_product_type_slug', $reverb_product_type_slug)
                            ->addFieldToFilter('reverb_category_slug', $reverb_category_slug)
                            ->getFirstItem();

        /* @var $reverbCategory Reverb_ReverbSync_Model_Category_Reverb */
        if ((!is_object($reverbCategory)) || (!$reverbCategory->getId()))
        {
            // The category may have a NULL value for reverb_product_type_slug
            $reverbCategory = $this->_reverbCategoryModel
                                ->getCollection()
                                ->addFieldToFilter('reverb_product_type_slug', array('null' => true))
                                ->addFieldToFilter('reverb_category_slug', $reverb_category_slug)
                                ->getFirstItem();

            /* @var $reverbCategory Reverb_ReverbSync_Model_Category_Reverb */
            if ((!is_object($reverbCategory)) || (!$reverbCategory->getId()))
            {
                return null;
            }
        }

        return $reverbCategory;
    }

    /**
     * @param string $reverb_category_uuid - Reverb category UUID from the import csv data file
     * @param int $magento_category_id - Entity id for the Magento category
     */
    protected function _createNewCategoryMapping($reverb_category_uuid, $magento_category_id)
    {
        $newCategoryMappingObject = Mage::getModel('reverbSync/category_reverb_magento_xref');
        /* @var $newCategoryMappingObject Reverb_ReverbSync_Model_Category_Reverb_Magento_Xref */
        $newCategoryMappingObject->setData('reverb_category_uuid', $reverb_category_uuid);
        $newCategoryMappingObject->setData('magento_category_id', $magento_category_id);
        $newCategoryMappingObject->save();
    }

    /**
     * @param int $reverb_category_id
     * @return array
     */
    protected function _getMagentoCategoriesMappedToReverbCategory($reverb_category_id)
    {
        $legacy_reverb_magento_category_mappings = $this->_reverbCategoryMappingModel
                                                        ->getCollection()
                                                        ->addFieldToFilter('reverb_category_id', $reverb_category_id)
                                                        ->getItems();

        return $legacy_reverb_magento_category_mappings;
    }

    /**
     * @return Reverb_ReverbSync_Helper_Sync_Category_Update
     */
    protected function _getCategoryUpdateHelper()
    {
        if (is_null($this->_categoryUpdateHelper))
        {
            $this->_categoryUpdateHelper = Mage::helper('ReverbSync/sync_category_update');
        }

        return $this->_categoryUpdateHelper;
    }
}
