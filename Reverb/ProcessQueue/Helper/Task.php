<?php
namespace Reverb\ProcessQueue\Helper;
class Task extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * It's not optimal that the configuration setting for this Reverb_ProcessQueue module class is in the ReverbSync
     *  module's configuration setting, but creating a Reverb_ProcessQueue module-specific system.xml tab and
     *  section seemed like sub-optimal UX. As such, this setting is in the already-existing ReverbSync module's
     *  system.xml structure.
     */
    const STALE_TASK_TIME_LAPSE_CONFIG_PATH = 'ReverbSync/stale_task_deletion/stale_period_in_days';
    const STALE_TASK_TIME_LAPSE_SPRINTF_TEMPLATE = '-%s days';

    private $_scopeConfig;

    private $_datetime;

    private $_taskResource;

    private $_taskresourceUnique;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Reverb\ProcessQueue\Model\Resource\Taskresource $taskResource,
        \Reverb\ProcessQueue\Model\Resource\Taskresource\Unique $taskresourceUnique
    ){
        $this->_scopeConfig = $scopeConfig;
        $this->_datetime = $datetime;
        $this->_taskResource = $taskResource;
        $this->_taskresourceUnique = $taskresourceUnique;
    }
    /**
     * The calling block is expected to catch exceptions
     *
     * @param null|string $task_code
     * @return int - Number of rows deleted
     */
    public function deleteStaleSuccessfulTasks($task_code = null)
    {
        $current_gmt_timestamp = $this->_datetime->gmtTimestamp();
        $stale_task_time_lapse_strtotime_param = $this->_getStaleTaskTimeLapseInDaysStrToTimeParam();
        $stale_timestamp = strtotime($stale_task_time_lapse_strtotime_param, $current_gmt_timestamp);
        $stale_date = date('Y-m-d H:i:s', $stale_timestamp);

        $taskResourceSingleton = $this->_taskResource;
        $rows_deleted = $taskResourceSingleton->deleteSuccessfulTasks($task_code, $stale_date);
        $uniqueTaskResourceSingleton = $this->_taskresourceUnique;
        $unique_rows_deleted = $uniqueTaskResourceSingleton->deleteSuccessfulTasks($task_code, $stale_date);
        return ($rows_deleted + $unique_rows_deleted);
    }

    /**
     * Returns the first parameter for strtotime signifying the time in days that tasks should be completed for
     *  in order to be considered stale
     *
     * @return string
     */
    protected function _getStaleTaskTimeLapseInDaysStrToTimeParam()
    {
        $stale_task_time_lapse_in_days = $this->_scopeConfig->getValue(self::STALE_TASK_TIME_LAPSE_CONFIG_PATH);
        $stale_task_time_lapse_in_days = intval($stale_task_time_lapse_in_days);
        $stale_task_time_lapse_strtotime_param
            = sprintf(self::STALE_TASK_TIME_LAPSE_SPRINTF_TEMPLATE, $stale_task_time_lapse_in_days);

        return $stale_task_time_lapse_strtotime_param;
    }
}
