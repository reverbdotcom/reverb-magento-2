<?php
namespace Reverb\Process\Model\Locked\File\Cronprocess;
use \Reverb\Process\Model\Locked\File\Interfaceclass as Lockedfileinterface;
/*use Reverb\Process\Model\Locked\Cron\Interfaceclass as Lockedcroninterface;*/
interface Interfaceclass extends Lockedfileinterface
{
    public function getLockFileDirectory();

    public function getLockFileName();
}
