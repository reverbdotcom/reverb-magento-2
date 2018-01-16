<?php
/**
 * Reverb Report admin block
 *
 * @category    Reverb
 * @package     Reverb_ReverbSync
 */

 namespace Reverb\ReverbSync\Controller\Adminhtml\ReverbSync\Field;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Reverb_ReverbSync::mapping')
            ->addBreadcrumb(__('Manage Field Mapping'), __('Manage Field Mapping'))
            ->addBreadcrumb(__('Manage Field Mapping'), __('Manage Field Mapping'));
        return $resultPage;
    }

    /**
     * Edit Field Mapping
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('mapping_id');
        $model = $this->_objectManager->create('Reverb\ReverbSync\Model\Field\Mapping');

        if ($id) {
            $model->load($id);
            if (!$model->getMappingId()) {
                $this->messageManager->addError(__('This field mapping no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('reverbsync_field_mapping', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Field Mapping') : __('Add New Field Mapping'),
            $id ? __('Edit Field Mapping') : __('Add New Field Mapping')
        );
        //$resultPage->getConfig()->getTitle()->prepend(__('Field Mapping5'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getMappingId() ? 'Edit Existing Magento-Reverb Field Mapping with Id '.$model->getMappingId() : __('Add New Field Mapping'));

        return $resultPage;
    }
}
