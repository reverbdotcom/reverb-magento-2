<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\ReverbSync\Field;

class Grid extends \Magento\Backend\App\Action
{
	public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }
	
    /**
     * Managing newsletter grid
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
