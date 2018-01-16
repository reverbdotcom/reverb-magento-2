<?php
namespace Reverb\Process\Model\Locked\File\Cronprocess;
use \Reverb\Process\Model\Locked\Cron\Abstractclass as ModelLockedcronabstractclass;
use \Reverb\Process\Model\Locked\File\Cronprocess\Interfaceclass as ModelLockedFileCronInterfaceclass;
abstract class Abstractclass extends ModelLockedcronabstractclass implements ModelLockedFileCronInterfaceclass
{
    const ERROR_EXCEPTION_WHILE_OPENING_LOCK_FILE = 'Error attempting to open the lock file %s: %s';
    const ERROR_EXCEPTION_WHILE_SECURING_LOCK_FILE = 'Error attempting to secure the lock file %s: %s';
    const ERROR_EXCEPTION_WHILE_CHANGING_DIRECTORY = 'Error attempting to change to directory %s to lock file %s: %s';
    const ERROR_EXCEPTION_CHECKING_AND_CREATING_FOLDER = 'Exception occurred while checking existence of directory %s: %s';

    const LOCK_FILE_PERMISSIONS = 0700;

    protected $getIoAdapter = null;

    abstract public function getLockFileDirectory();

    abstract public function getLockFileName();

    protected $_getIoAdapter;

    public function __construct(
        \Reverb\Io\Model\Io\File $getIoAdapter,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->getIoAdapter = $getIoAdapter;
        $this->_dir = $dir;
    }

    public function attemptLockForThread($thread_number)
    {
        $ioAdapter = $this->_getIoAdapter();
        $lock_file_directory = $this->getLockFileDirectory();
        $lock_file_name = $this->getLockFileName();
        $full_lock_file_name = $lock_file_name . '_' . $thread_number . '.lock';


        try
        {
            $ioAdapter->setAllowCreateFolders(true);
            $ioAdapter->open(array('path' => $lock_file_directory));

        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::ERROR_EXCEPTION_WHILE_CHANGING_DIRECTORY, $lock_file_directory, $full_lock_file_name, $e->getMessage());
            $this->_logError($error_message);
            return false;
        }


        $lock_file_path = $lock_file_directory . DIRECTORY_SEPARATOR . $full_lock_file_name;

        try
        {
            $ioAdapter->streamOpen($lock_file_path, 'w+', self::LOCK_FILE_PERMISSIONS);
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::ERROR_EXCEPTION_WHILE_OPENING_LOCK_FILE, $lock_file_path, $e->getMessage());
            $this->_logError($error_message);
            return false;
        }

        try
        {
            if(!$ioAdapter->streamLock(true))
            {
                return false;
            }
        }
        catch(\Exception $e)
        {
            $error_message = sprintf(self::ERROR_EXCEPTION_WHILE_SECURING_LOCK_FILE, $lock_file_path, $e->getMessage());
            $this->_logError($error_message);

            return false;
        }

        return true;
    }

    public function releaseLock()
    {
        $ioAdapter = $this->_getIoAdapter();

        try
        {
            $ioAdapter->streamUnlock();
            $ioAdapter->streamClose();
        }
        catch(\Exception $e)
        {
            return false;
        }

        return true;
    }

    protected function _getIoAdapter()
    {
        /*if (is_null($this->_ioAdapter))
        {
            $this->_ioAdapter = Mage::getModel('reverb_io/io_file')->setAllowCreateFolders(true);
        }*/
        return $this->getIoAdapter;
    }

    public function getCheckCreateLogFile(){
        $path = $this->_dir->getPath('var') . DIRECTORY_SEPARATOR . 'log';
        $filepath = $path.DIRECTORY_SEPARATOR.'reverb_sync.log';
        if(!file_exists($filepath)){
            $this->getIoAdapter->mkdir($path, 0775);
            $fp = fopen($filepath,'a+');
            fclose($fp);
            chmod($filepath,0777);
        }
    }
}
