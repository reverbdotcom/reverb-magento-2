<?php
namespace Reverb\ReverbSync\Model\Resource\Catalog;
use Magento\Catalog\Model\ResourceModel\Product as ResourcemodelProduct;
class Product extends ResourcemodelProduct
{
    public function getSkuById($product_id)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from($this->getEntityTable(), 'sku')
            ->where('entity_id = :entity_id');

        $bind = array(':entity_id' => (string)$product_id);

        return $adapter->fetchOne($select, $bind);
    }
} 