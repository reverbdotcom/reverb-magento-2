<?php
namespace Reverb\ProcessQueue\Model\Resource\Taskresource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
class Collection extends AbstractCollection{
    const DEFAULT_MINUTES_IN_PAST_THRESHOLD = 120;

    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Reverb\ProcessQueue\Model\Task','Reverb\ProcessQueue\Model\Resource\Taskresource');
    }

    public function addOpenForProcessingFilter()
    {
        $pending_status = \Reverb\ProcessQueue\Model\Task::STATUS_PENDING;
        $error_status = \Reverb\ProcessQueue\Model\Task::STATUS_ERROR;
        $open_for_processing_states = array($pending_status, $error_status);

        $this->addFieldToFilter('status', array('in' => $open_for_processing_states));
        return $this;
    }

    public function addLastExecutedAtThreshold($minutes_in_past = self::DEFAULT_MINUTES_IN_PAST_THRESHOLD)
    {
        $current_gmt_timestamp = Mage::getSingleton('core/date')->gmtTimestamp();
        $second_in_past = $minutes_in_past * 60;
        $last_executed_at_threshold = $current_gmt_timestamp - $second_in_past;

        $this->addFieldToFilter('last_executed_at', array('lt' => $last_executed_at_threshold));
        return $this;
    }

    public function addCodeFilter($code)
    {
        if (is_array($code))
        {
            $code = array('in' => $code);
        }
        $this->addFieldToFilter('code', $code);
        return $this;
    }

    public function addStatusFilter($status)
    {
        $this->addFieldToFilter('status', $status);
        return $this;
    }

    public function sortByLeastRecentlyExecuted()
    {
        $this->getSelect()->order('last_executed_at ' . \Zend_Db_Select::SQL_ASC);
        return $this;
    }
}
