<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Task;
use Magento\Framework\DataObject;
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    const CONFIRM_TEMPLATE = 'Are you sure you want to manually %s this order sync task?';

    public function render(DataObject $row)
    {
        $task_action_text = __('Execute');//$row->getActionText();
        if (empty($task_action_text))
        {
            return '';
        }
        $actionurl = "<a href='" . $this->getUrl('reverbprocessqueue/processqueue/actontask',['task_id'=> $row->getId(),'type'=>$row->getCode()]) . "'>";
        $actionurl .= __('Execute');
        $actionurl .= "</a>";
        return $actionurl;
    }
}
