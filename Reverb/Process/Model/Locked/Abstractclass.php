<?php
namespace Reverb\Process\Model\Locked;
use \Reverb\Process\Model\Locked\Interfaceclass as Lockedinterface;
abstract class Abstractclass implements Lockedinterface
{
    abstract public function attemptLock();

    abstract public function releaseLock();

    protected function _logError($error_message)
    {
    	echo 'abstract log error === > ';
    	echo $error_message;
    	exit;
    }
}
