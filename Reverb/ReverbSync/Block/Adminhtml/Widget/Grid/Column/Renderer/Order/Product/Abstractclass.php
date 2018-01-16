<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Product;
use Magento\Framework\DataObject;
class Abstractclass extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    const ANCHOR_TAG_TEMPLATE = '<a href="%s">%s</a>';

    protected function _getMagentoProductForRow(DataObject $row)
    {
        $magentoProduct = $row->getReverbMagentoProduct();
        if (is_object($magentoProduct) && $magentoProduct->getId())
        {
            return $magentoProduct;
        }

        $argumentsObject = $row->getArgumentsObject(true);
        if (isset($argumentsObject->sku))
        {
            $sku = $argumentsObject->sku;
            return $this->_getAndCacheMagentoProductBySku($sku, $row);
        }
        elseif (isset($argumentsObject->order_id))
        {
            // This could occur with shipment tracking sync rows
            $magento_entity_id = $argumentsObject->order_id;
            if (!empty($magento_entity_id))
            {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $model = $objectManager->create('\Reverb\ReverbSync\Model\Resource\Order');
                $product_sku_and_name = $model->getOrderItemSkuAndNameByMagentoOrderEntityId($magento_entity_id);
                $sku = isset($product_sku_and_name['sku']) ? $product_sku_and_name['sku'] : null;
                if (!empty($sku))
                {
                    return $this->_getAndCacheMagentoProductBySku($sku, $row);
                }
            }
        }
        // This case should not happen
        return null;
    }

    protected function _getAndCacheMagentoProductBySku($sku, $row)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('\Magento\Catalog\Model\Product');
        $product_id = $model->getIdBySku($sku);
        $magentoProduct = $model->load($product_id);
        // Cache the product on the row object
        $row->setReverbMagentoProduct($magentoProduct);
        return $magentoProduct;
    }

   
    public function getHtmlAnchorLinkToProductEditPage($label, $magento_product_entity_id)
    {
        $escaped_label = $this->escapeHtml($label);
        $product_edit_url = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $magento_product_entity_id));
        return sprintf(self::ANCHOR_TAG_TEMPLATE, $product_edit_url, $escaped_label);
    }

}
