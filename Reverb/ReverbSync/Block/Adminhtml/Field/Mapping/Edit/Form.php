<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Field\Mapping\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
		\Reverb\ReverbSync\Model\Source\Product\Attribute $productAttribute
	) {
		$this->_productAttribute = $productAttribute;
		parent::__construct($context,$registry,$formFactory);
	}
	/**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reverbsync_field_mapping');
        $this->setTitle(__('Field Mapping Information'));
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('reverbsync/reverbsync_field/save'),
                    'method' => 'post',
					'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $form->setUseContainer(true);
		$model = $this->_coreRegistry->registry('reverbsync_field_mapping');
		$fieldset =  $form->addFieldset(
            'base_fieldset',
            array('legend' => __('Magento-Reverb Field Mapping'), 'class'=>'fieldset-wide')
        );
		
		if ($model->getMappingId()) {
            $fieldset->addField(
				'mapping_id',
				'hidden', 
				['name' => 'mapping_id']
			);
        }
		$fieldset->addField(
            'magento_attribute_code',
            'select',
            [
				'name' => 'magento_attribute_code', 
				'label' => __('Magento Attribute'), 
				'title' => __('Magento Attribute Code'),
				'values' => $this->_productAttribute->toOptionArray(),
				'required' => true
			]
        );
		$fieldset->addField(
            'reverb_api_field',
            'text',
            [
				'name' => 'reverb_api_field', 
				'label' => __('Reverb API Field'), 
				'title' => __('Reverb API Field'), 
				'required' => true
			]
        );
		$form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
