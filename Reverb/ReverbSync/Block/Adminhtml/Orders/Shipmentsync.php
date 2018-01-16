<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Orders;

class Shipmentsync extends \Magento\Backend\Block\Widget\Grid\Container{

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

     /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('add');
        $this->_blockGroup = 'Reverb_ReverbSync';
        $this->_controller = 'adminhtml_orders_shipmentsync';
    }


    /**
     * Prepare button and grid
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product
     */
    protected function _prepareLayout()
    {
        /*$addButtonProps = [
            'id' => 'clear_all_tasks',
            'label' => __('Clear all tasks'),
            'class' => 'clear_task button btn',
            'button_class' => 'button'
        ];
        $this->buttonList->add('clear_all_tasks', $addButtonProps);*/

        return parent::_prepareLayout();
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
