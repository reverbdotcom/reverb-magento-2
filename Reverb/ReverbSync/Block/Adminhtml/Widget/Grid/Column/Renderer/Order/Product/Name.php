<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Product;
use Magento\Framework\DataObject;
class Name extends \Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Product\Abstractclass
{
    public function render(DataObject $row)
    {
        $magentoProduct = $this->_getMagentoProductForRow($row);
        if ((!is_object($magentoProduct)) || (!$magentoProduct->getId()))
        {
            return null;
        }

        return $this->getHtmlAnchorLinkToProductEditPage($magentoProduct->getName(), $magentoProduct->getId());
    }
}
