<?php
namespace Reverb\ReverbSync\Model\Resource\Task;
abstract class Unique extends \Reverb\ReverbSync\Model\Resource\Task
{
    public function _construct()
    {
        $this->_init('reverb_process_queue_task_unique','unique_id');
    }

    protected function _getInsertColumnsArray()
    {
        return array('code', 'unique_id', 'status', 'object', 'method', 'serialized_arguments_object', 'subject_id');
    }

    protected function _getUniqueInsertDataArrayTemplate($object, $method, $unique_id, $subject_id = null)
    {
        $insert_data_array_template = parent::_getInsertDataArrayTemplate($object, $method, $subject_id);
        $insert_data_array_template['unique_id'] = $unique_id;
        return $insert_data_array_template;
    }

    public function getPrimaryKeyByUniqueId($unique_id)
    {
        $table_name = $this->getMainTable();
        $connection = $this->getConnection();

        $select = $connection
                    ->select()
                    ->from($table_name, array('unique_id'))
                    ->where('unique_id = ?', $unique_id);

        $unique_task_primary_key = $connection->fetchOne($select);

        return $unique_task_primary_key;
    }
}
