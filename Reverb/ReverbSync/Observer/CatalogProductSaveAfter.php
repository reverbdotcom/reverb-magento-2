<?php
namespace Reverb\ReverbSync\Observer;
use Magento\Framework\Event\ObserverInterface;
 
class CatalogProductSaveAfter implements ObserverInterface
{
   const EXCEPTION_LISTING_SYNC_ON_PRODUCT_SAVE = 'An exception occurred while attempting to queue a background Reverb listing sync task on product save for product with id %s: %s';

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    protected $_taskprocessor;
 
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Reverb\ReverbSync\Helper\Task\Processor $taskprocessor,
        \Reverb\ReverbSync\Model\Log $reverblogger

    ) {
        $this->_objectManager = $objectManager;
        $this->_taskprocessor = $taskprocessor;
        $this->_reverbLogger = $reverblogger;
    }
 
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        $product_id = $product->getId();

        $reverbSyncTaskProcessor = $this->_taskprocessor;
        
        try
        {
            $test = $reverbSyncTaskProcessor->queueListingsSyncByProductIds(array($product_id));
        }
        catch(\Exception $e)
        {
            $error_message = $reverbSyncTaskProcessor->__(sprintf(self::EXCEPTION_LISTING_SYNC_ON_PRODUCT_SAVE,
                                                            $product_id, $e->getMessage()));
            $this->_reverbLogger->logListingSyncError($error_message);
        }
    }
}