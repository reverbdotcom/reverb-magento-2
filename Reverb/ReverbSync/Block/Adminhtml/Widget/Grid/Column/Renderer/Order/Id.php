<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order;
use Magento\Framework\DataObject;
class Id extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    const ANCHOR_TAG_TEMPLATE = '<a href="%s">%s</a>';

    public function render(DataObject $row)
    {
    }

    public function getHtmlAnchorLinkToViewOrderPageByReverbOrderId($reverb_order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('\Reverb\ReverbSync\Model\Resource\Order');

        $magento_entity_id = $model->getMagentoOrderEntityIdByReverbOrderNumber($reverb_order_id);

        if (empty($magento_entity_id))
        {
            return $this->escapeHtml($reverb_order_id);
        }

        $escaped_label = $this->escapeHtml($reverb_order_id);
        $view_order_id = $this->getUrl('sales/order/view', array('order_id' => $magento_entity_id));
        return sprintf(self::ANCHOR_TAG_TEMPLATE, $view_order_id, $escaped_label);
    }
}