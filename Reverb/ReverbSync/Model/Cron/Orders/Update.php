<?php
namespace Reverb\ReverbSync\Model\Cron\Orders;
use \Reverb\Process\Model\Locked\File\Cronprocess\Abstractclass as Cronabstract;
use \Reverb\Process\Model\Locked\File\Cronprocess\Interfaceclass as Croninterface;
class Update extends Cronabstract implements Croninterface
{
    const CRON_UNCAUGHT_EXCEPTION = 'Error processing the Reverb Order Update Process Queue: %s';


    protected $_dir;

    protected $_taskprocessor;

    protected $_retrievalUpdate;

    protected $_orderSyncHelper;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Reverb\ProcessQueue\Helper\Task\Processor $taskprocessor,
        \Reverb\ReverbSync\Model\Logger $logger,
        \Reverb\Io\Model\Io\File $getIoAdapter,
        \Reverb\ReverbSync\Helper\Orders\Sync $orderSyncHelper,
        \Reverb\ReverbSync\Helper\Orders\Retrieval\Update $retrievalUpdate,
        array $data = []
    ) {
        parent::__construct($getIoAdapter, $dir, $data);
        $this->getIoAdapter = $getIoAdapter;
        $this->_taskprocessor = $taskprocessor;
        $this->_orderSyncHelper = $orderSyncHelper;
        $this->_retrievalUpdate = $retrievalUpdate;
        $this->_dir = $dir;
        $this->_logger = $logger;
    }

    public function executeCron()
    {
        try
        {
            $this->_logError('check Order Update cron is running');
            if (!$this->_orderSyncHelper->isOrderSyncEnabled())
            {
                return false;
            }

            $this->_retrievalUpdate->queueReverbOrderSyncActions();
            $this->_taskprocessor->processQueueTasks('order_update');
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::CRON_UNCAUGHT_EXCEPTION, $e->getMessage());
            $this->_logError($error_message);
        }
    }

    public function getParallelThreadCount()
    {
        return 1;
    }

    public function getLockFileName()
    {
        return 'reverb_order_update';
    }

    public function getLockFileDirectory()
    {
        return $this->_dir->getPath('var') . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR . 'reverb_order_update';
    }

    public function getCronCode()
    {
        return 'reverb_order_update';
    }

    protected function _logError($error_message)
    {
        $this->_logger->info('file: '.__FILE__.',function = '.__FUNCTION__.', error = '.$error_message);
    }
}
