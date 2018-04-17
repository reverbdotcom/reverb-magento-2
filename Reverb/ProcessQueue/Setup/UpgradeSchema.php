<?php
namespace Reverb\ProcessQueue\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
//ALTER TABLE EMP_DTLS MODIFY COLUMN EMP_ID INT(10) FIRST
        $installer->startSetup();
        if (version_compare($context->getVersion(), '0.0.7', '<')) {
         /* $installer->getConnection()->addColumn(
                $installer->getTable('reverb_process_queue_task_unique'),
                'task_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 11,
                    'nullable'  => false,
                    'unsigned' => true,
                    'primary' => true,
                    'identity'  => true,
                    'comment' => 'Task Id'
                ]
            );*/
            $sql = "ALTER TABLE " . $installer->getTable('reverb_process_queue_task_unique') . " ADD COLUMN task_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
            $installer->getConnection()->query($sql);
        }
         if (version_compare($context->getVersion(), '0.0.8', '<')) {
            $installer->getConnection()->changeColumn(
                           $installer->getTable('reverb_process_queue_task_unique'),
                           'unique_id',
                           'unique_id',
                           [
                                           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                           'length' => 255,
                                           'nullable'  => false,
                                           'comment' =>'Unique ID'
                           ]
           );
           $installer->getConnection()->changeColumn(
                           $installer->getTable('reverb_process_queue_task_unique'),
                           'subject_id',
                           'subject_id',
                           [
                                           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                           'length' => 255,
                                           'nullable'  => false,
                                           'comment' =>'Subject ID'
                           ]
           );
        }
        $installer->endSetup();
    }
}