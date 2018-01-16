<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\ReverbSync\Field;

class Save extends \Magento\Backend\App\Action
{
	const ERROR_FIELD_ALREADY_MAPPED_IN_SYSTEM = "Reverb Field `%1` is already mapped to Magento attribute `%2`";
    const ERROR_ATTRIBUTE_DOES_NOT_EXIST_IN_THE_SYSTEM = 'Product attribute `%1` does not exist in the system';
	
    /**
     * @param Action\Context $context
     */
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Eav\Model\Config $eavConfig,
		\Reverb\ReverbSync\Model\Field\Mapping $fieldMapping
	){
		$this->_eavConfig = $eavConfig;
		$this->_fieldMapping = $fieldMapping;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Reverb\ReverbSync\Model\Field\Mapping $model */
            $model = $this->_objectManager->create('Reverb\ReverbSync\Model\Field\Mapping');

            $id = $this->getRequest()->getParam('mapping_id');
            if ($id) {
                $model->load($id);
            }
            $model->setData($data);

            try {
				$magentoAttributeCode = $this->getRequest()->getParam('magento_attribute_code');
				$reverbFieldApi = $this->getRequest()->getParam('reverb_api_field');
				$this->validateMagentoAttribute($magentoAttributeCode);
				$this->validateReverbField($data);
                $model->save();
                $this->messageManager->addSuccess(__('Magento-Reverb Field Mapping has been successfully created.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['mapping_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('reverbsync/reverbsync_field/mapping');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the field.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['mapping_id' => $this->getRequest()->getParam('mapping_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
	
	public function validateMagentoAttribute($magentoAttributeCode)
	{
		$attribute = $this->_eavConfig->getAttribute('catalog_product', $magentoAttributeCode);
		if (!$attribute->getId()) {
			$error_message = __(self::ERROR_ATTRIBUTE_DOES_NOT_EXIST_IN_THE_SYSTEM, $magentoAttributeCode);
			throw new \Magento\Framework\Exception\LocalizedException($error_message);
		}
	}
	
	public function validateReverbField($mappingObjectToValidate)
	{
		$varienObject = new \Magento\Framework\DataObject();
		$mappingObjectToValidate = $varienObject->setData($mappingObjectToValidate);
		
        $reverb_api_field = $mappingObjectToValidate->getReverbApiField();
        $mappedReverbFieldObject = $this->_fieldMapping->load($reverb_api_field, 'reverb_api_field');

        /* @var $mappedReverbFieldObject Reverb_ReverbSync_Model_Field_Mapping */
        if ((!is_object($mappedReverbFieldObject)) || (!$mappedReverbFieldObject->getId()))
        {
            // There is no mapping in the system mapped to this Reverb API field.
            return true;
        }
        // Check whether the $mappingObjectToValidate passed in already exists in the system or not
        $mapping_object_id_to_validate = $mappingObjectToValidate->getId();
        if ($mapping_object_id_to_validate)
        {
            // This object already exists in the system. Verify that the primary key value for $mappedReverbFieldObject
            //      matches the primary key value for the $mappingObjectToValidate passed in
            $mapped_field_object_id = $mappedReverbFieldObject->getId();
            if ($mapped_field_object_id != $mapping_object_id_to_validate)
            {
                $mapped_magento_attribute = $mappedReverbFieldObject->getMagentoAttributeCode();
                $error_message = __(self::ERROR_FIELD_ALREADY_MAPPED_IN_SYSTEM, $reverb_api_field,$mapped_magento_attribute);
                throw new \Magento\Framework\Exception\LocalizedException($error_message);
            }
        }
        else
        {
            // There is already a mapping to this reverb field in the system, and the user is attempting to create a new
            //      mapping to this Reverb field. Throw an exception
            $mapped_magento_attribute = $mappedReverbFieldObject->getMagentoAttributeCode();
            $error_message = __(self::ERROR_FIELD_ALREADY_MAPPED_IN_SYSTEM, $reverb_api_field,$mapped_magento_attribute);
            throw new \Magento\Framework\Exception\LocalizedException($error_message);
        }
	}
}