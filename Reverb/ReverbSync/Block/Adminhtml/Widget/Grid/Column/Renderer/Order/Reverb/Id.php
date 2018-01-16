<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Reverb;
use Magento\Framework\DataObject;
class Id extends \Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Id
{
    public function render(DataObject $row)
    {
         $argumentsObject = $row->getArgumentsObject(true);
        if (isset($argumentsObject->order_number))
        {
            $reverb_order_number = $argumentsObject->order_number;
            return $this->getHtmlAnchorLinkToViewOrderPageByReverbOrderId($reverb_order_number);
        }
        return '';
    }
}
