<?php
namespace Reverb\ReverbSync\Helper;
class Admin extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_moduleName = 'ReverbSync';

    protected $_reverbreport;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
         $this->messageManager = $messageManager;
    }

    public function addAdminSuccessMessage($success_message)
    {
        return  $this->messageManager->addSuccess(__($success_message));
    }

    public function addAdminErrorMessage($error_message)
    {
        return  $this->messageManager->addError(__($error_message));
    }
    public function addNotice($error_message)
    {
        return  $this->messageManager->addNotice(__($error_message));
    }
}
