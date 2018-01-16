<?php
/**
 * Reverb_ReverbSync extension
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category  Reverb
 * @package   Reverb_ReverbSync
 * @copyright Copyright (c) 2017
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Reverb\Reports\Setup;

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
        if (!$installer->tableExists('reverb_reports_reverbreport')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('reverb_reports_reverbreport')
            )
            ->addColumn('entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
            'unsigned' => true,
            ], 'Reverb Report ID')
            ->addColumn('product_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
            'nullable'  => false,
            ], 'Product Id')

        ->addColumn('title', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
            'nullable'  => false,
            ], 'Product Name')

        ->addColumn('product_sku', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
            'nullable'  => false,
            ], 'Product Sku')
        ->addColumn('inventory', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
            'unsigned'  => true,
            ], 'Inventory')
            
        ->addColumn('rev_url', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
            ], 'REV URL')
        ->addColumn('status', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
            ], 'Reverb Report Status')
        ->addColumn('sync_details', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [
            ], 'Sync Details')
        ->addColumn('last_synced', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [
                ], 'Reverb Report Modification Time')
        ->setComment('Reverb Report Table');
            
        $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable('reverb_reports_reverbreport'),
                $setup->getIdxName(
                    $installer->getTable('reverb_reports_reverbreport'),
                    ['product_sku', 'last_synced'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['product_sku', 'last_synced'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );
        }

    }
}
