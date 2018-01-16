<?php
namespace Reverb\ReverbSync\Model\Cron\Listings;
use \Reverb\Process\Model\Locked\File\Cronprocess\Abstractclass as Cronabstract;
use \Reverb\Process\Model\Locked\File\Cronprocess\Interfaceclass as Croninterface;
class Sync extends Cronabstract implements Croninterface
{ 
    const CRON_UNCAUGHT_EXCEPTION = 'Error processing the Reverb Listing Sync Process Queue: %s';

    protected $_suppress_failed_lock_attempt_error_messages = true;

    protected $_dir;

    protected $_taskProcessor;

    protected $_logger;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Reverb\Io\Model\Io\File $getIoAdapter,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskprocessor,
        \Reverb\ReverbSync\Model\Logger $reverblogger,
        array $data = []
    ) {
        parent::__construct($getIoAdapter, $dir, $data);
        $this->getIoAdapter = $getIoAdapter;
        $this->_taskProcessor = $taskprocessor;
        $this->_dir = $dir;
        $this->_logger = $reverblogger;
    }

    public function executeCron()
    {
        try
        {
            $this->_logError('check listing cron is running');
            $this->_taskProcessor->processQueueTasks('listing_sync');
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::CRON_UNCAUGHT_EXCEPTION, $e->getMessage());
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
            /*$exceptionToLog = new Exception($error_message);
            Mage::logException($exceptionToLog);*/
        }
    }

    public function getParallelThreadCount()
    {
        return 4;
    }

    public function getLockFileName()
    {
        return 'reverb_listing_sync';
    }

    public function getLockFileDirectory()
    {
        return $this->_dir->getPath('var') . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR . 'reverb_listing_sync';
    }

    public function getCronCode()
    {
        return 'reverb_listing_sync';
    }

    protected function _logError($error_message)
    {
        $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
    }
} 
