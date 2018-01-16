<?php
namespace Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento;

class Xref extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const EXCEPTION_REDEFINING_CATEGORY_MAPPING = 'An exception occurred while redefining the Reverb-Magento category mapping in the database: %s';

    protected $_database_insert_columns_array
        = array(\Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::MAGENTO_CATEGORY_ID_FIELD,
                \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::REVERB_CATEGORY_UUID_FIELD);

    public function _construct()
    {
        $this->_init('reverb_magento_category_xref','xref_id');
    }

    /**
     * @return array
     */
    public function getArrayMappingMagentoCategoryIdToReverbCategoryUuid()
    {
        $readConnection = $this->getConnection();

        $select = $readConnection
                    ->select()
                    ->from($this->getMainTable(),
                        array(\Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::MAGENTO_CATEGORY_ID_FIELD,
                              \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::REVERB_CATEGORY_UUID_FIELD));

        $magento_category_id_to_reverb_category_id_mapping_array = $readConnection->fetchPairs($select);
        return $magento_category_id_to_reverb_category_id_mapping_array;
    }

    /**
     * @param array $magento_category_ids
     * @return array
     */
    public function getReverbCategoryUuidsByMagentoCategoryIds($magento_category_ids)
    {
        $table_name = $this->getMainTable();
        $readConnection = $this->getConnection();

        $where_clause = \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::MAGENTO_CATEGORY_ID_FIELD . ' in (?)';
        $select = $readConnection
                    ->select()
                    ->from($table_name, \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::REVERB_CATEGORY_UUID_FIELD)
                    ->where($where_clause, $magento_category_ids);

        $reverb_category_uuids = $readConnection->fetchCol($select);
        return $reverb_category_uuids;
    }

    /**
     * @param array $magento_reverb_category_mapping
     * @return int
     * @throws Reverb_ReverbSync_Model_Exception_Category_Mapping
     */
    public function redefineCategoryMapping(array $magento_reverb_category_mapping)
    {
        $this->beginTransaction();

        try
        {
            $this->_truncateTable();

            array_walk($magento_reverb_category_mapping,
                        '\Reverb\ReverbSync\Model\Resource\Category\Reverb\Magento\Xref::convertToArray');

            $number_of_rows_inserted
                = $this->loadMagentoReverbCategoryMappingArrayIntoDatabase($magento_reverb_category_mapping);

            $this->commit();
        }
        catch(Exception $e)
        {
            $this->rollBack();
            $error_message = __(self::EXCEPTION_REDEFINING_CATEGORY_MAPPING, $e->getMessage());
            Mage::getSingleton('reverbSync/log')->logCategoryMappingError($error_message);
            throw new Reverb_ReverbSync_Model_Exception_Category_Mapping($error_message);
        }

        return $number_of_rows_inserted;
    }

    /**
     * @param string $reverb_category_uuid - Passed in BY REFERENCE as the Reverb category UUID
     * @param int $magento_category_entity_id - The Magento category entity id mapped to the Reverb category
     *
     * $reverb_category_uuid is transformed into an array with two indices:
     *      \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::MAGENTO_CATEGORY_ID_FIELD
     *      \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::REVERB_CATEGORY_UUID_FIELD
     * Each of these indices points to the $reverb_category_uuid and $magento_category_entity_id respectively
     */
    public static function convertToArray(&$reverb_category_uuid, $magento_category_entity_id)
    {
        $reverb_category_uuid
            = array(\Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::MAGENTO_CATEGORY_ID_FIELD => $magento_category_entity_id,
                    \Reverb\ReverbSync\Model\Category\Reverb\Magento\Xref::REVERB_CATEGORY_UUID_FIELD => $reverb_category_uuid);
    }

    /**
     * Inserts the new mapping into the database
     *
     * @param array $magento_reverb_category_mapping
     * @return int
     */
    public function loadMagentoReverbCategoryMappingArrayIntoDatabase(array $magento_reverb_category_mapping)
    {
        return $this->getConnection()->insertArray($this->getMainTable(), $this->_database_insert_columns_array,
                                                        $magento_reverb_category_mapping);
    }

    /**
     * Truncates the table
     *
     * @return int
     */
    protected function _truncateTable()
    {
        $rows_deleted = $this->getConnection()->delete($this->getMainTable());
        return $rows_deleted;
    }
}
