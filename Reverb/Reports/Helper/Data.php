<?php
namespace Reverb\Reports\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_reverbreport;

    public function __construct(
        \Reverb\Reports\Model\Reverbreport $reverbreport
    ) {
        $this->_reverbreport = $reverbreport;
    }

    public function logListingSyncReport($listingWrapper)
    {
        try
        {
            // Currently, there should only be one row in the table per product_id
            $magentoProduct = $listingWrapper->getMagentoProduct();
            if ((!is_object($magentoProduct)) || (!$magentoProduct->getId()))
            {
                // This likely occurs as a result of an exception during a sync attempt
                // This should not occur, but we should handle the case where it does
                return;
            }
            $product_id = (int)$magentoProduct->getId();
            if($product_id){
                $reverbReportObject = $this->_reverbreport->load((int)$product_id);
                $reverbReportObject->populateWithDataFromListingWrapper($listingWrapper);
                $reverbReportObject->save();
            }
        }
        catch(Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }
}
