<?php
namespace Reverb\Reports\Observer;
use Magento\Framework\Event\ObserverInterface;
 
class BulkReport implements ObserverInterface
{
    protected $_reportHelper;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Reverb\Reports\Helper\BulkData $reportHelper
    ) {
        $this->_reportHelper = $reportHelper;
    }
 
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        {
            $reverbListingWrapper = $observer->getReverbListing();
            $this->_reportHelper->logListingSyncReport($reverbListingWrapper);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            exit;
        }
    }
}