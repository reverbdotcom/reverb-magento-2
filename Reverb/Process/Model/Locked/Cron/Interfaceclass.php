<?php
namespace Reverb\Process\Model\Locked\Cron;
use \Reverb\Process\Model\Locked\Interfaceclass as Modellockedinterface;
interface Interfaceclass extends Modellockedinterface
{
    /**
     * Implemented by Reverb_Process_Model_Locked_Cron_Abstract
     * This is the method which should be called by cron's calling block
     *
     * @return mixed
     */
    public function attemptCronExecution();

    /**
     * The 2 methods below required to be implemented by leaf subclasses of Reverb_Process_Model_Locked_Cron_Abstract
     *
     * @return mixed
     */
    public function executeCron();

    public function getCronCode();

    public function getParallelThreadCount();

    public function attemptLockForThread($thread_number);
}
