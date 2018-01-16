<?php
namespace Reverb\Base\Helper;
class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ERROR_PRODUCT_NOT_CONFIGURABLE = 'Product with sku %s is not a configurable product';

    protected $_configurableProductTypeModel = null;

    protected $_productRepository;
    
    protected $_typeConfigurable;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeConfigurable
    ) {
        $this->_productRepository = $productRepository;
        $this->_typeConfigurable = $typeConfigurable;
    }


    /**
     * @param $product
     * @return Mage_Catalog_Model_Product|null
     */
    public function getParentProductIfChild($product)
    {
        if ($product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        {
            return null;
        }
        $parent_id_array = $this->_getConfigurableProductTypeModel()->getParentIdsByChild($product->getId());
        if (!empty($parent_id_array))
        {
            $parent_id = reset($parent_id_array);
            $parentProduct = $this->_productRepository->getById($parent_id);
            return $parentProduct;
        }

        return null;
    }

    /**
     * @param Mage_Catalog_Model_Product $configurableProduct
     * @return array
     * @throws Exception
     */
    public function getSimpleProductsForConfigurableProduct($configurableProduct)
    {
        if ($configurableProduct->getTypeId() != 'configurable')
        {
            $error_message = __(self::ERROR_PRODUCT_NOT_CONFIGURABLE, $configurableProduct->getSku());
            throw new Exception($error_message);
        }

        $parent_id = $configurableProduct->getId();
        $child_product_ids_return = $this->_getConfigurableProductTypeModel()->getChildrenIds($parent_id);
        $child_product_ids = reset($child_product_ids_return);
        $child_products_array = array();
        foreach($child_product_ids as $child_product_id)
        {
            $childProduct = $this->_productRepository->getById($child_product_id);
            if (is_object($childProduct) && $childProduct->getId())
            {
                $child_products_array[] = $childProduct;
            }
        }

        return $child_products_array;
    }

    /**
     * @return \Magento\Catalog\Model\Product\Type_Configurable
     */
    protected function _getConfigurableProductTypeModel()
    {
       
        $this->_configurableProductTypeModel = $this->_typeConfigurable;
        return $this->_configurableProductTypeModel;
    }
}
