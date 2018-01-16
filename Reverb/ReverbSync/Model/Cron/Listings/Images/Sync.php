<?php
namespace Reverb\ReverbSync\Model\Cron\Listings\Images;
use \Reverb\Process\Model\Locked\File\Cronprocess\Abstractclass as Cronabstract;
use \Reverb\Process\Model\Locked\File\Cronprocess\Interfaceclass as Croninterface;
class Sync extends Cronabstract implements Croninterface
{
    const CRON_UNCAUGHT_EXCEPTION = 'Error processing the Reverb Listing Image Sync Process Queue: %s';

    protected $_suppress_failed_lock_attempt_error_messages = true;

     protected $_dir;

    protected $_taskprocessorUnique;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Reverb\ProcessQueue\Helper\Task\Processor\Unique $taskprocessorUnique,
        \Reverb\ReverbSync\Model\Logger $logger,
         \Reverb\Io\Model\Io\File $getIoAdapter,
        array $data = []
    ) {
        parent::__construct($getIoAdapter, $dir, $data);
        $this->getIoAdapter = $getIoAdapter;
        $this->_taskprocessorUnique = $taskprocessorUnique;
        $this->_dir = $dir;
        $this->_logger = $logger;
    }

    public function executeCron()
    {
        try
        {
            $this->_logError('check Image sync cron is running');
            $this->_taskprocessorUnique->processQueueTasks('listing_image_sync');
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::CRON_UNCAUGHT_EXCEPTION, $e->getMessage());
            $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
        }
    }

    public function getParallelThreadCount()
    {
        return 4;
    }

    public function getLockFileName()
    {
        return 'reverb_listing_image_sync';
    }

    public function getLockFileDirectory()
    {
        return $this->_dir->getPath('var') . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR . 'reverb_listing_image_sync';
    }

    public function getCronCode()
    {
        return 'reverb_listing_image_sync';
    }

    protected function _logError($error_message)
    {
        $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
    }
} 
