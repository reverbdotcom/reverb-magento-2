<?php
/**
 * Reverb Report admin block
 *
 * @category    Reverb
 * @package     Reverb_Reports
 */
namespace Reverb\Reports\Block\Adminhtml;
class Reverbreport extends \Magento\Backend\Block\Widget\Grid\Container{

 /**
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $_typeFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_typeFactory = $typeFactory;

        parent::__construct($context, $data);
    }

     /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('add');
        $this->_blockGroup = 'Reverb_Reports';
        $this->_controller = 'adminhtml_reverbreport';
    }


    /**
     * Prepare button and grid
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product
     */
    protected function _prepareLayout()
    {
       /* $addButtonProps = [
            'id' => 'clear_all_tasks',
            'label' => __('Clear all tasks'),
            'class' => 'clear_task button btn',
            'onclick' => "setLocation('" . $this->_getProductCreateUrl('simple') . "')",
            'button_class' => 'button',
          //  'class_name' => 'Magento\Backend\Block\Widget\Button\SplitButton',
           // 'options' => $this->_getAddProductButtonOptions(),
        ];
        $this->buttonList->add('clear_all_tasks', $addButtonProps);
        */
        return parent::_prepareLayout();
    }

    /**
     * Retrieve options for 'Add Product' split button
     *
     * @return array
     */
    protected function _getAddProductButtonOptions()
    {
        $splitButtonOptions = [];
        $types = $this->_typeFactory->create()->getTypes();
        uasort(
            $types,
            function ($elementOne, $elementTwo) {
                return ($elementOne['sort_order'] < $elementTwo['sort_order']) ? -1 : 1;
            }
        );

        foreach ($types as $typeId => $type) {
            $splitButtonOptions[$typeId] = [
                'label' => __($type['label']),
                'onclick' => "setLocation('" . $this->_getProductCreateUrl($typeId) . "')",
                'default' => \Magento\Catalog\Model\Product\Type::DEFAULT_TYPE == $typeId,
            ];
        }

        return $splitButtonOptions;
    }

    /**
     * Retrieve product create url by specified product type
     *
     * @param string $type
     * @return string
     */
    protected function _getProductCreateUrl($type)
    {
        return $this->getUrl(
            'catalog/*/new',
            ['set' => $this->_productFactory->create()->getDefaultAttributeSetId(), 'type' => $type]
        );
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
   /* public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }*/

}
