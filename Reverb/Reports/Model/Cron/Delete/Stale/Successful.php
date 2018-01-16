<?php
namespace Reverb\Reports\Model\Cron\Delete\Stale;
class Successful
{
    const CRON_UNCAUGHT_EXCEPTION = 'Error deleting stale success reports from the Reverb Reports Table: %s';

    private $_reverbLogger;

    private $_reverbreportResource;

    public function __construct(
        \Reverb\ReverbSync\Model\Log $reverbLogger,
        \Reverb\Reports\Model\Resource\Reverbreport $reverbreportResource
    ){
        $this->_reverbLogger = $reverbLogger;
        $this->_reverbreportResource = $reverbreportResource;
    }
    public function deleteStaleSuccessfulReports()
    {
        try
        {
            $this->_reverbreportResource->deleteStaleSuccessfulReports();
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::CRON_UNCAUGHT_EXCEPTION, $e->getMessage());
            $this->_reverbLogger->logReverbMessage($message);       
        }
    }
}
