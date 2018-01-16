<?php
namespace Reverb\ReverbSync\Helper\Task;
class Processor extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BATCH_SIZE = 1000;

    protected $_listingsSyncProcessQueueResource = null;

    protected $_taskresource;

    public function __construct(
        \Reverb\ProcessQueue\Model\Resource\Taskresource $taskresource
    ) {
        $this->_taskresource = $taskresource;
    }


    public function queueListingsSyncByProductIds(array $product_ids_in_system)
    {
        $number_of_successfully_queued_syncs = 0;
        $number_of_products = count($product_ids_in_system);
        $number_of_products_queued = 0;
        while($number_of_products_queued < $number_of_products)
        {
            $products_remaining_to_queue = $number_of_products - $number_of_products_queued;
            $number_of_products_to_queue = min($products_remaining_to_queue, self::BATCH_SIZE);

            $batch_product_ids = array_slice($product_ids_in_system, $number_of_products_queued, $number_of_products_to_queue);

            $successfully_queued_syncs = $this->_getListingsSyncProcessQueueResource()
                                                    ->queueListingSyncsByProductIds($batch_product_ids);

            $number_of_successfully_queued_syncs += $successfully_queued_syncs;
            $number_of_products_queued += $number_of_products_to_queue;
        }

        return $number_of_successfully_queued_syncs;
    }

    /**
     * @return Reverb_ReverbSync_Model_Mysql4_Task_Listing
     */
    protected function _getListingsSyncProcessQueueResource()
    {
        if (is_null($this->_listingsSyncProcessQueueResource))
        {
            $this->_listingsSyncProcessQueueResource = $this->_taskresource;
        }

        return $this->_listingsSyncProcessQueueResource;
    }
}
