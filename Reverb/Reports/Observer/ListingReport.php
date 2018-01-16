<?php
namespace Reverb\Reports\Observer;
use Magento\Framework\Event\ObserverInterface;
 
class ListingReport implements ObserverInterface
{
    protected $_reportHelper;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Reverb\Reports\Helper\Data $reportHelper
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
        try
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