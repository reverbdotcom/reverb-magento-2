<?php
/**
 * Reverb Report resource model
 *
 * @category    Reverb
 * @package     Reverb_Reports

 */
namespace Reverb\Reports\Model\Resource;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Reverbreport extends AbstractDb{

    const STALE_TIME_LAPSE = '-2 weeks';

    protected $currentdatetime;
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $currentdatetime
    ) {
        parent::__construct($context);
        $this->currentdatetime = $currentdatetime;
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('reverb_reports_reverbreport', 'entity_id');
    }

    public function deleteStaleSuccessfulReports()
    {
        $current_gmt_timestamp = $this->currentdatetime->gmtTimestamp();
        $stale_timestamp = strtotime(self::STALE_TIME_LAPSE, $current_gmt_timestamp);
        $stale_date = date('Y-m-d H:i:s', $stale_timestamp);
        $where_condition_array = array('last_synced < ?' => $stale_date);
        $rows_deleted = $this->getConnection()->delete($this->getMainTable(), $where_condition_array);
        return $rows_deleted;
    }

    public function deleteAllReverbReportRows()
    {
        $rows_deleted = $this->getConnection()->delete($this->getMainTable());
        return $rows_deleted;
    }

    public function deleteSuccessfulSyncs()
    {
        $where_condition_array = array('status=?' => \Reverb\Reports\Model\Reverbreport::STATUS_SUCCESS);
        $rows_deleted = $this->getConnection()->delete($this->getMainTable(), $where_condition_array);
        return $rows_deleted;
    }
}
