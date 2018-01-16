<?php
namespace Reverb\ReverbSync\Model\Resource\Task\Order;
class Update extends \Reverb\ReverbSync\Model\Resource\Task
{
    const ORDER_UPDATE_OBJECT = '\Reverb\ReverbSync\Model\Sync\Order\Update';
    const ORDER_UPDATE_METHOD = 'updateReverbOrderInMagento';

    protected $_task_code = 'order_update';

    public function queueOrderUpdateByReverbOrderDataObject($orderDataObject)
    {
        $order_number = $orderDataObject->order_number;

        $insert_data_array = $this->_getInsertDataArrayTemplate(self::ORDER_UPDATE_OBJECT,
                                                                self::ORDER_UPDATE_METHOD, $order_number);

        $serialized_arguments_object = serialize($orderDataObject);
        $insert_data_array['serialized_arguments_object'] = $serialized_arguments_object;


        $number_of_inserted_rows = $this->getConnection()->insert($this->getMainTable(), $insert_data_array);
        return $number_of_inserted_rows;
    }

    public function getTaskCode()
    {
        return $this->_task_code;
    }
}
