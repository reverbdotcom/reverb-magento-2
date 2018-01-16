<?php
namespace Reverb\ReverbSync\Helper\Orders\Creation\Task;
class Processor extends \Reverb\ProcessQueue\Helper\Task\Processor\Unique
{
    // Batch size reduced to avoid memory issues
    protected $_batch_size = 100;
}
