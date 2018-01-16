<?php
/**
 * Reverb_ProcessQueue extension
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category  Reverb
 * @package   Reverb_ProcessQueue
 * @copyright Copyright (c) 2017
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Reverb\ReverbSync\Setup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {

         $installer = $setup;
         $installer->startSetup();
         $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'reverb_order_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 11,
                    'nullable' => true,
                    'comment' => 'Reverb Order Id'
                ]
            );

        $installer->getConnection()->addIndex(
            $installer->getTable('sales_order'),
            'reverb_order_id',
            array('reverb_order_id'),
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'reverb_order_status',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 30,
                'nullable' => true,
                'comment' => 'Reverb Order Status'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_item'),
            'reverb_item_link',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Reverb Item Link'
            ]
        );
        
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_item'),
            'reverb_item_link',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Reverb Item Link'
            ]
        );

   
        $reverb_category_table_name = $installer->getTable('reverb_categories');

        if (!$installer->tableExists($reverb_category_table_name)) {
            $table = $installer->getConnection()->newTable(
                $reverb_category_table_name
            )->addColumn(
                    'reverb_category_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    array('nullable'  => false, 'unsigned' => true, 'primary' => true, 'identity'  => true),
                    'Primary Key for the Table'
                )->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    array('nullable'  => false),
                    'The name of the category'
                )->addColumn(
                    'reverb_product_type_slug',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '50',
                    array('nullable'  => true),
                    'Product Type Slug'
                )->addColumn(
                    'reverb_category_slug',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '50',
                    array('nullable'  => true),
                    'Category Slug'
                )->addColumn(
                    'uuid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '50',
                    array('nullable'  => true),
                    'UUID'
                )->addColumn(
                    'parent_uuid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '50',
                    array('nullable'  => true),
                    'parent UUID'
                )->addIndex(
                    $installer->getIdxName('reverb_categories', array('reverb_category_slug')),
                    array('reverb_category_slug'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->addIndex(
                    $installer->getIdxName('reverb_categories', array('reverb_product_type_slug')),
                    array('reverb_product_type_slug'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->addIndex(
                    $installer->getIdxName('reverb_categories', array('uuid')),
                    array('uuid'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->setComment('Table Holding the Reverb Categories');
            $installer->getConnection()->createTable($table); 
        }

        $reverb_magento_categories = $installer->getTable('reverb_magento_categories');

        if (!$installer->tableExists($reverb_magento_categories)) {
            $table_categories = $installer->getConnection()->newTable(
                $reverb_magento_categories
            )->addColumn(
            'xref_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            array('nullable'  => false, 'unsigned' => true, 'primary' => true, 'identity'  => true),
            'Primary Key for the Table'
            )->addColumn(
                'magento_category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                array('nullable'  => false,'unsigned' => true),
                'Magento category entity id'
            )->addColumn(
                'reverb_category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                array('nullable'  => false,'unsigned' => true),
                'Id corresponding to a reverb_category_id column value in the reverb_categories table'
            )->addIndex(
                $installer->getIdxName($reverb_magento_categories, array('magento_category_id')),
                array('magento_category_id'),
                array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
            )->addForeignKey(
                $installer->getFkName($reverb_magento_categories, 'magento_category_id', 'catalog_category_entity', 'entity_id'),
                'magento_category_id',
                $installer->getTable('catalog_category_entity'), 'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName($reverb_magento_categories, 'reverb_category_id', 'reverb_categories', 'reverb_category_id'),
                'reverb_category_id',
                $installer->getTable('reverb_categories'), 'reverb_category_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Table mapping Magento to Reverb Categories');
            $installer->getConnection()->createTable($table_categories); 
        }

        $reverb_magento_category_xref_table = $installer->getTable('reverb_magento_category_xref');

        if (!$installer->tableExists($reverb_magento_category_xref_table)) {
            $table_category_xref = $installer->getConnection()->newTable(
                $reverb_magento_category_xref_table
            )->addColumn(
            'xref_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            array('nullable' => false, 'unsigned' => true, 'primary' => true, 'identity' => true),
            'Primary Key for the Table'
            )->addColumn(
                'reverb_category_uuid',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                array('nullable' => false),
                'The UUID value uniquely identifying the Reverb Category'
            )->addColumn(
                'magento_category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '10',
                array('nullable' => false, 'unsigned' => true),
                'The category entity id in the Magento system mapped to the Reverb category'
            )->addIndex(
                $installer->getIdxName($reverb_magento_category_xref_table, array('magento_category_id')),
                array('magento_category_id'),
                array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
            )->addIndex(
                $installer->getIdxName($reverb_magento_category_xref_table, array('reverb_category_uuid')),
                array('reverb_category_uuid'),
                array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
            )->addForeignKey(
                $installer->getFkName($reverb_magento_category_xref_table, 'magento_category_id', 'catalog_category_entity', 'entity_id'),
                'magento_category_id',
                $installer->getTable('catalog_category_entity'), 'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName($reverb_magento_category_xref_table, 'reverb_category_uuid', $installer->getTable('reverb_categories'), 'uuid'),
                'reverb_category_uuid',
                $installer->getTable('reverb_categories'), 'uuid',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE, \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Table mapping Magento categories to Reverb categories');
            $installer->getConnection()->createTable($table_category_xref);
        }


         $reverb_magento_field_mapping = $installer->getTable('reverb_magento_field_mapping');

        if (!$installer->tableExists($reverb_magento_field_mapping)) {
            $table_field_mapping = $installer->getConnection()->newTable(
                $reverb_magento_field_mapping
            )->addColumn(
            'mapping_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            array('nullable' => false, 'unsigned' => true, 'primary' => true, 'identity' => true),
            'Primary Key for the Table'
            )->addColumn(
                'magento_attribute_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                array('nullable' => false),
                'Attribute code for the Magento attribute'
            )->addColumn(
                'reverb_api_field',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                array('nullable' => false),
                'Reverb Listing API field'
            )->addIndex(
                $installer->getIdxName('reverbSync/magento_reverb_field_mapping', array('magento_attribute_code')),
                array('magento_attribute_code'),
                array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
            )->addIndex(
                $installer->getIdxName('reverbSync/magento_reverb_field_mapping', array('reverb_api_field')),
                array('reverb_api_field'),
                array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
            )->setComment('Table abstracting a mapping from Magento attributes to Reverb fields');
            $installer->getConnection()->createTable($table_field_mapping);
        }
        $installer->endSetup();

    }
}
