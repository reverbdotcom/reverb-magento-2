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
namespace Reverb\ProcessQueue\Setup;

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
        if (!$installer->tableExists('reverb_process_queue_task')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('reverb_process_queue_task')
            )
          ->addColumn(
            'task_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            array('nullable'  => false, 'unsigned' => true, 'primary' => true, 'identity'  => true),
            'Primary Key for the Table'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            50,
            array('nullable'  => false),
            'Code representing the process this task is an instance of'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            3,
            array('nullable'  => false),
            'Status of this task'
        )->addColumn(
            'object',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            array('nullable'  => false),
            'The object to call the task\'s method on. This can be a magento classname or an actuall object class'
        )->addColumn(
            'method',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            array('nullable'  => false),
            'The method to call.'
        )->addColumn(
            'serialized_arguments_object',
            \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
            null,
            array(),
            'A serialized object which will be passed as the only argument to the method'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            array('null' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT),
            'The date time this task was entered into the queue'
        )->addColumn(
            'last_executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            array('null' => false, 'default' => '0000-00-00 00:00:00'),
            'The date time this task was last attempted to be processed'
         )->addColumn(
            'status_message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            array('null' => false),
            'status_message'
        )->addColumn(
            'subject_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            array('null' => false),
            'subject_id'
        )->addIndex(
            $installer->getIdxName('reverb_process_queue_task', array('code', 'status', 'last_executed_at')),
            array('code', 'status', 'last_executed_at'),
            array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
        )->addIndex(
            $installer->getIdxName('reverb_process_queue_task', array('status', 'last_executed_at')),
            array('status', 'last_executed_at'),
            array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
         )->addIndex(
            $installer->getIdxName('reverb_process_queue_task', array('last_executed_at')),
            array('last_executed_at'),
            array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
        )->setComment('Table abstracting background tasks to be processed via crontab');

        $installer->getConnection()->createTable($table);


            if (!$installer->tableExists('reverb_process_queue_task_unique')) {
            $table2 = $installer->getConnection()->newTable($installer->getTable('reverb_process_queue_task_unique'))
                ->addColumn(
                    'code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    array('nullable'  => false),
                    'Code representing the process this task is an instance of'
                )->addColumn(
                    'unique_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    array('nullable'  => false),
                    'Unique Identifier regarding this task'
                )->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    3,
                    array('nullable'  => false),
                    'Status of this task'
                )->addColumn(
                    'object',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    array('nullable'  => false),
                    'The object to call the task\'s method on. This can be a magento classname or an actuall object class'
                )->addColumn(
                    'method',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    array('nullable'  => false),
                    'The method to call.'
                )->addColumn(
                    'serialized_arguments_object',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
                    null,
                    array(),
                    'A serialized object which will be passed as the only argument to the method'
                )->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    array('null' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT),
                    'The date time this task was entered into the queue'
                )->addColumn(
                    'last_executed_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    array('null' => false, 'default' => '0000-00-00 00:00:00'),
                    'The date time this task was last attempted to be processed'
                )->addColumn(
                    'status_message',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    array('null' => false),
                    'status_message'
                )->addColumn(
                    'subject_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    array('null' => false),
                    'subject_id'
                )->addIndex(
                    $installer->getIdxName('reverb_process_queue_task_unique', array('code', 'status', 'last_executed_at')),
                    array('code', 'status', 'last_executed_at'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->addIndex(
                    $installer->getIdxName('reverb_process_queue_task_unique', array('status', 'last_executed_at')),
                    array('status', 'last_executed_at'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->addIndex(
                    $installer->getIdxName('reverb_process_queue_task_unique', array('code', 'unique_id')),
                    array('code', 'unique_id'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
                )->addIndex(
                    $installer->getIdxName('reverb_process_queue_task_unique', array('last_executed_at')),
                    array('last_executed_at'),
                    array('type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX)
                )->setComment('Table abstracting background tasks to be processed via crontab');
            }
            $installer->getConnection()->createTable($table2);

        }

    }
}
