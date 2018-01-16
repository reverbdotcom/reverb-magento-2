<?php
namespace Reverb\ReverbSync\Helper\Sync;
class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ERROR_NO_REVERB_CATEGORIES_MAPPED = 'Skipping; no category map';
    const FORM_ELEMENT_FIELD_NAME_TEMPLATE = '%s[%s]';
    const REVERB_CATEGORY_MAP_ELEMENT_NAME = 'reverb_category_map';
    const NO_CATEGORY_CHOSEN_OPTION = 'none';

    const PRODUCT_TYPE_FIELD_NAME = 'product_type';
    const CATEGORY_UUIDS_FIELD_NAME = 'category_uuids';

    const REVERB_CATEGORY_MAPPING_REQUIRED_FOR_LISTING = 'ReverbSync/reverbDefault/require_reverb_category_definition';

    protected $_moduleName = 'ReverbSync';

    protected $_reverb_category_options_array = null;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;
	
	/**
     * @var \Reverb\ReverbSync\Model\Category\Reverb
     */
    protected $reverbCategoryCollection;
	
	/**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Reverb\ReverbSync\Model\Category\Reverb $reverbCategoryCollection
     */
	protected $_reverbCategoryCollection;

    protected $_categoryMagentoXref;

    protected $_scopeConfig;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Reverb\ReverbSync\Model\Category\Reverb $reverbCategoryCollection,
        \Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref $categoryMagentoXref,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_reverbCategoryCollection = $reverbCategoryCollection;
       $this->_categoryMagentoXref = $categoryMagentoXref;
       $this->_scopeConfig = $scopeConfig; 
        parent::__construct($context);
    }

	
	public function getMagentoReverbCategoryMapElementArrayName()
    {
        return self::REVERB_CATEGORY_MAP_ELEMENT_NAME;
    }

    public function getReverbCategoryMapFormElementName($field)
    {
        return sprintf(self::FORM_ELEMENT_FIELD_NAME_TEMPLATE, self::REVERB_CATEGORY_MAP_ELEMENT_NAME, $field);
    }

    public function getNoCategoryChosenOption()
    {
        return self::NO_CATEGORY_CHOSEN_OPTION;
    }

    public function processMagentoReverbCategoryMapping(array $reverb_magento_category_mapping)
    {

	   // Filter out all values in the array which are set to the NO_CATEGORY_CHOSEN_OPTION value
        $defined_category_mapping
            = array_filter($reverb_magento_category_mapping,
                             'Reverb\ReverbSync\Helper\Sync\Category::filter_out_no_category_chosen_option');

        if (!empty($defined_category_mapping))
        {
            $this->_categoryMagentoXref->redefineCategoryMapping($defined_category_mapping);
        }
    }

    static public function filter_out_no_category_chosen_option($mapped_value)
    {
        return strcmp($mapped_value, self::NO_CATEGORY_CHOSEN_OPTION);
    }

    public function addCategoriesToListingFieldsArray(array $fieldsArray, \Magento\Catalog\Model\Product $product)
    {
        $product_reverb_category_objects_array = $this->getReverbCategoryObjectsByProduct($product);
        if (empty($product_reverb_category_objects_array))
        {
            if ($this->reverbCategoriesAreRequiredForListing())
            {
                $error_message = $this->__(self::ERROR_NO_REVERB_CATEGORIES_MAPPED);
                throw new \Reverb\ReverbSync\Model\Exception\Category\Mapping($error_message);
            }
            // Return without modifying the $fieldsArray
            return $fieldsArray;
        }
        // If the user has mapped a product to both a subcategory and a toplevel category, the subcategory information wins.
        $sorted_reverb_categories_desc = $this->sortReverbCategoriesByLevelDescending($product_reverb_category_objects_array);
        $deepestReverbCategory = array_shift($sorted_reverb_categories_desc);

        $reverb_category_uuid = $deepestReverbCategory->getUuid();
        $fieldsArray[self::CATEGORY_UUIDS_FIELD_NAME] = array($reverb_category_uuid);

        return $fieldsArray;
    }

    public function sortReverbCategoriesByLevelDescending(array $product_reverb_category_objects_array)
    {
        usort($product_reverb_category_objects_array, '\Reverb\ReverbSync\Helper\Sync\Category::compareReverbCategoryLevelDescending');
        return $product_reverb_category_objects_array;
    }

    static public function compareReverbCategoryLevelDescending($reverbCategoryA, $reverbCategoryB)
    {
        $category_a_is_subcategory = \Reverb\ReverbSync\Helper\Sync\Category::isReverbCategoryASubcategory($reverbCategoryA);
        $category_b_is_subcategory = \Reverb\ReverbSync\Helper\Sync\Category::isReverbCategoryASubcategory($reverbCategoryB);

        return ($category_a_is_subcategory < $category_b_is_subcategory);
    }

    /**
     * Subcategories are identified by the fact that they ... have both "slug" and "product_type_slug".
     * They also have a " > " in their title, but that's not a reliable way to check for them being subcategories.
     *
     * @param $reverbCategoryA
     * @return int
     */
    static public function isReverbCategoryASubcategory($reverbCategory)
    {
        $reverb_category_parent_uuid = $reverbCategory->getParentUuidField();
        return (empty($reverb_category_parent_uuid) ? 0 : 1);
    }

    public function getReverbCategoryObjectsByProduct(\Magento\Catalog\Model\Product $magentoProduct)
    {
        $magento_category_ids = $magentoProduct->getCategoryIds();
        $reverb_category_uuids = $this->_categoryMagentoXref->getReverbCategoryUuidsByMagentoCategoryIds($magento_category_ids);

        if(empty($reverb_category_uuids))
        {
            // Return an empty array
            return array();
        }

        $reverb_category_uuids = array_unique($reverb_category_uuids);

        $reverbCategoryCollection = $this->_reverbCategoryCollection
                                            ->getCollection()
                                        ->addCategoryUuidFilter($reverb_category_uuids);

        return $reverbCategoryCollection->getItems();
    }

    public function reverbCategoriesAreRequiredForListing()
    {
        return $this->_scopeConfig->getValue(self::REVERB_CATEGORY_MAPPING_REQUIRED_FOR_LISTING);
    }

    public function getReverbCategorySelectOptionsArray()
    {
        $reverb_category_select_options_array = array();
        $reverb_category_select_options_array[self::NO_CATEGORY_CHOSEN_OPTION] = '';
        $reverb_categories = $this->getReverbCategoriesArray();

        foreach($reverb_categories as $reverbCategory)
        {
            $reverb_category_select_options_array[$reverbCategory->getUuid()] = $reverbCategory->getName();
        }

        return $reverb_category_select_options_array;
    }

    public function getReverbCategoriesArray()
    {
        if (is_null($this->_reverb_category_options_array))
        {
            $reverbCategoryCollection = $this->_reverbCategoryCollection
                                            ->getCollection();

            $this->_reverb_category_options_array = $reverbCategoryCollection->getItems();
        }

        return $this->_reverb_category_options_array;
    }
}
