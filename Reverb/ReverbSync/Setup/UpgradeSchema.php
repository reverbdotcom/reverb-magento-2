<?php
namespace Reverb\ReverbSync\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup,ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
		if (version_compare($context->getVersion(), '1.0.2', '<'))
		{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$remapHelper = $objectManager->create('Reverb\ReverbSync\Helper\Category\Remap')->remapReverbCategories();
			$objectManager->create('Reverb\ReverbSync\Helper\Category')->removeCategoriesWithoutUuid();
		}

        $installer->endSetup();
    }
}