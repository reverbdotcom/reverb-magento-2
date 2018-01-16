<?php
namespace Reverb\ReverbSync\Controller\Adminhtml\ReverbSync\Field;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('mapping_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create('Reverb\ReverbSync\Model\Field\Mapping');
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('Successfully deleted Magento-Reverb Field Mapping'));
                return $resultRedirect->setPath('reverbsync/reverbsync_field/mapping');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['mapping_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a mapping field to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}