<?php
namespace Reverb\Process\Model\Locked\File;
use Reverb\Process\Model\Locked\Interfaceclass as ModelLockedInterfaceclass;
interface Interfaceclass extends ModelLockedInterfaceclass
{
    /**
     * The 2 methods below required to be implemented by child classes, no abstract class
     * implements these currently
     *
     * @return mixed
     */
    public function getLockFileDirectory();

    public function getLockFileName();
}
