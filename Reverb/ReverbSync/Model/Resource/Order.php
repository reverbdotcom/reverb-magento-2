<?php
namespace Reverb\ReverbSync\Model\Resource;
class Order extends \Magento\Sales\Model\ResourceModel\Order
{
    public function getMagentoOrderEntityIdByReverbOrderNumber($reverb_order_number)
    {
        $table_name = $this->getMainTable();
        $resourceConnection = $this->getConnection();

        $select = $resourceConnection
                    ->select()
                    ->from($table_name, array('entity_id'))
                    ->where('reverb_order_id = ?', $reverb_order_number);

        $order_entity_id = $resourceConnection->fetchOne($select);

        return $order_entity_id;
    }

    public function getReverbOrderIdByMagentoOrderEntityId($magento_order_entity_id)
    {
        $table_name = $this->getMainTable();
        $resourceConnection = $this->getConnection();

        $select = $resourceConnection
                    ->select()
                    ->from($table_name, array('reverb_order_id'))
                    ->where('entity_id = ?', $magento_order_entity_id);

        $order_entity_id = $resourceConnection->fetchOne($select);

        return $order_entity_id;
    }

    public function getOrderItemSkuAndNameByMagentoOrderEntityId($magento_order_entity_id)
    {
        // We are working under the functional spec that Reverb orders only allow one product per order
        $table_name = $this->getTable('sales_order_item');
        $resourceConnection = $this->getConnection();

        $select = $resourceConnection
                    ->select()
                    ->from($table_name, array('sku', 'name'))
                    ->where('order_id = ?', $magento_order_entity_id);

        $sku_and_name = $resourceConnection->fetchRow($select);

        return $sku_and_name;
    }

    public function updateReverbOrderStatusByMagentoEntityId($magento_entity_id, $reverb_order_status)
    {
        $update_bind_array = array('reverb_order_status' => $reverb_order_status);
        $where_conditions_array = array('entity_id=?' => $magento_entity_id);

        $rows_updated = $this->getConnection()
                            ->update($this->getMainTable(), $update_bind_array, $where_conditions_array);
        return $rows_updated;
    }

    public function setReverbStoreNameByReverbOrderId($orderId, $reverb_order_id, $store_name)
    {
        $update_bind_array = array('store_name' => $store_name,'reverb_order_id' => $reverb_order_id);
        $where_conditions_array = array('entity_id=?' => $orderId);

        $rows_updated = $this->getConnection()
                            ->update($this->getMainTable(), $update_bind_array, $where_conditions_array);
        return $rows_updated;
    }
}
