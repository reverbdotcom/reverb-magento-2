<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Category\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

 
class Form extends Generic
{
    protected $reverb_category_options_array = null;
    protected $_translationHelper = null;
    protected $_categorySyncHelper = null;
    protected $_magento_reverb_category_mapping_array = null;

    /**
     * @var \Reverb\ReverbSync\Helper\Data;
     */
    protected $revrbsyncHelperData;
	
	/**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
     */
    protected $categoryCollection;
	
	/**
     * @var \Reverb\ReverbSync\Helper\Sync\Category;
     */
    protected $revrbsyncCategoryHelper;
	
	/**
     * @var \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref;
     */
    protected $reverbMagentoCategory;
	
	/**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Reverb\ReverbSync\Helper\Data $revrbsyncHelperData
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection
     * @param \Reverb\ReverbSync\Helper\Sync\Category $revrbsyncCategoryHelper
     * @param \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref $reverbMagentoCategory
     */
	 
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
		\Reverb\ReverbSync\Helper\Data $revrbsyncHelperData,
		\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
		\Reverb\ReverbSync\Helper\Sync\Category $revrbsyncCategoryHelper,
		\Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref $reverbMagentoCategory
	) {
		$this->_reverbsyncHelper = $revrbsyncHelperData;
		$this->_categoryCollection = $categoryCollection;
		$this->_revrbsyncCategoryHelper = $revrbsyncCategoryHelper;
		$this->_reverbMagentoCategory = $reverbMagentoCategory;
		parent::__construct($context,$registry,$formFactory);
	}
    
	protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'action' => $this->getUrl("*/*/save"),
                    'method' => 'post'
                ]
            ]
        );
        $form->setUseContainer(true);
		$html_id_prefix = 'ReverbSync_';
        $form->setHtmlIdPrefix($html_id_prefix);
		
		$fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => __('Magento to Reverb Category Mapping'), 'class'=>'fieldset-wide')
        );
		
        $this->setForm($form);
 
		$to_return = parent::_prepareForm();
		$this->populateFormFieldset($fieldset);

        //return parent::_prepareForm();
        return $to_return;
    }
	/*protected function _prepareForm()
    {
        $helper = $this->_reverbsyncHelper;

        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getActionUrl(), 'method' => 'post'));
        $form->setUseContainer(true);
        $html_id_prefix = 'ReverbSync_';
        $form->setHtmlIdPrefix($html_id_prefix);

        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => $helper->__('Magento to Reverb Category Mapping'), 'class'=>'fieldset-wide')
        );

        $this->setForm($form);
        $to_return = parent::_prepareForm();

        $this->populateFormFieldset($fieldset);

        return $to_return;
    }*/

    public function populateFormFieldset($fieldset)
    {
        $magentoCategoryCollection = $this->_categoryCollection->create()
                                        //->getCollection()
                                        ->addFieldToFilter('level', array('gt' => 0))
                                        ->addAttributeToSelect('name');

        foreach ($magentoCategoryCollection->getItems() as $magentoCategory)
        {
            $this->addMagentoCategorySelect($fieldset, $magentoCategory);
        }
    }

    public function addMagentoCategorySelect($fieldset, $magentoCategory)
    {
        $name = $magentoCategory->getName();
        $magento_category_id = $magentoCategory->getId();
        $element_id = 'magento_category_select_' . $magento_category_id;
        $reverb_category_options_array = $this->_getReverbCategoryOptionsArray();
        $reverb_category_uuid = $this->_getReverbCategoryUuidByMagentoCategoryId($magento_category_id);

        //$helper = $this->_getTranslationHelper();

        $fieldset->addField($element_id, 'select', array(
            'name'  => $this->_revrbsyncCategoryHelper->getReverbCategoryMapFormElementName($magento_category_id),
            'label' => __($name),
            'title' => __($name),
            'value'  => $reverb_category_uuid,
            'values'   => $reverb_category_options_array,
            'required' => false
        ));
    }

    public function getActionUrl()
    {
        $uri_path = $this->getAction()->getUriPathForAction('save');
        return $this->getUrl($uri_path);
    }

    protected function _getReverbCategoryUuidByMagentoCategoryId($category_entity_id)
    {
        $mapping_array = $this->_getMagentoReverbCategoryMapping();
        return isset($mapping_array[$category_entity_id]) ? $mapping_array[$category_entity_id] : '';
    }

    protected function _getMagentoReverbCategoryMapping()
    {
        if (is_null($this->_magento_reverb_category_mapping_array))
        { 
            $this->_magento_reverb_category_mapping_array =
                $this->_reverbMagentoCategory
                    ->getArrayMappingMagentoCategoryIdToReverbCategoryUuid(); 
        }

        return $this->_magento_reverb_category_mapping_array;
    }

    protected function _getReverbCategoryOptionsArray()
    {
        if (is_null($this->reverb_category_options_array))
        {
            $this->reverb_category_options_array = $this->_revrbsyncCategoryHelper
                                                        ->getReverbCategorySelectOptionsArray();
        }

        return $this->reverb_category_options_array;
    }

    /*protected function _getTranslationHelper()
    {
        if (is_null($this->_translationHelper))
        {
            $this->_translationHelper = Mage::helper('ReverbSync');
        }

        return $this->_translationHelper;
    }*/

    /**
     * @return Reverb_ReverbSync_Helper_Sync_Category
     */
    /*protected function _getCategorySyncHelper()
    {
        if (is_null($this->_categorySyncHelper))
        {
            $this->_categorySyncHelper = Mage::helper('ReverbSync/sync_category');
        }

        return $this->_categorySyncHelper;
    }*/
}
