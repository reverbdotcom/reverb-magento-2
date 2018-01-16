<?php
namespace Reverb\ProcessQueue\Model;
use Magento\Framework\Model\AbstractModel;
use \Reverb\ProcessQueue\Model\Task\Interfaceclass as ModelTaskInterface;
class Task extends AbstractModel implements ModelTaskInterface
{
    const ERROR_INVALID_OBJECT_CLASS = 'The specified object class %s does not refer to any existing classes in the system';
    const ERROR_METHOD_DOES_NOT_EXIST = 'Method %s does not exist on object of class %s';

    const STATUS_PENDING = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_ERROR = 4;
    const STATUS_ABORTED = 5;

    protected $_valid_statuses = array(self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETE, self::STATUS_ERROR, self::STATUS_ABORTED);

    protected $_argumentsObject = null;

    protected $_taskResult;

    protected $_datetime;

    public function __construct(
        \Reverb\ProcessQueue\Model\Task\Result $taskResult,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->_registry = $registry;
        $this->_taskResult = $taskResult;
        $this->_datetime = $datetime;
        parent::__construct($context, $registry);
    }

    protected function _construct()
    {
        $this->_init(\Reverb\ProcessQueue\Model\Resource\Taskresource::class);
    }

    public function getStatus()
    {
        return parent::getStatus();
    }

    public function attemptUpdatingRowAsProcessing()
    {
        return $this->getResource()->attemptUpdatingRowAsProcessing($this);
    }

    public function selectForUpdate()
    {
        return $this->getResource()->selectForUpdate($this);
    }

    public function executeTask()
    {
        $object_class = $this->getObject();

        $objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        
        if($object_class =='\Reverb\ReverbSync\Model\Sync\Product'){
            $object = $objectmanager->create('Reverb\ReverbSync\Model\Sync\Product');
        } else if($object_class =='\Reverb\ReverbSync\Model\Sync\Listing\Image'){
            $object = $objectmanager->create('Reverb\ReverbSync\Model\Sync\Listing\Image');
        } else {
            $object = $objectmanager->create($object_class);
        }
        
        if (!is_object($object))
        {
            $error_message = __(self::ERROR_INVALID_OBJECT_CLASS, $object_class);
            throw new \Exception($error_message);
        }

        $method = $this->getMethod();
        if (!method_exists($object, $method))
        {
            $error_message = __(self::ERROR_METHOD_DOES_NOT_EXIST, $method, $object_class);
            throw new \Exception($error_message);
        }

      /*  echo 'testingone'. get_class($object);
        echo $method;
        exit; */
        $argumentsObject = $this->getArgumentsObject();

        // We don't know for sure what is being returned here
            $methodCallbackResult = $object->$method($argumentsObject);
           /* echo '<pre>';
            print_r($methodCallbackResult);
            exit;*/ 
        // If the method didn't return a Task_Result object
        if (!($methodCallbackResult instanceof \Reverb\ProcessQueue\Model\Task\Result\Interfaceclass))
        {
            $methodCallbackResultToReturn = $this->_taskResult;
            // Treat whatever was returned by the callback as the resulting message
            $methodCallbackResultToReturn->setMethodCallbackResult($methodCallbackResult);
            // Assume completion
            $methodCallbackResultToReturn->setTaskStatus(\Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE);

            return $methodCallbackResultToReturn;
        }
        // Else return the Task_Result $methodCallbackResult
        return $methodCallbackResult;
    }

    public function actOnTaskResult(\Reverb\ProcessQueue\Model\Task\Result\Interfaceclass $taskExecutionResult)
    {
        $execution_status = $taskExecutionResult->getTaskStatus();
        if (!$this->isStatusValid($execution_status))
        {
            // Assume completion
            $execution_status = self::STATUS_COMPLETE;
        }

        $status_message = $taskExecutionResult->getTaskStatusMessage();
        $this->getResource()->setExecutionStatusForTask($execution_status, $this, $status_message);
        // TODO Log error message if $execution_status isn't a valid status
    }

    public function setTaskAsErrored($error_message = null)
    {
        return $this->getResource()->setTaskAsErrored($this, $error_message);
    }

   

    public function isStatusValid($status)
    {
        return in_array($status, $this->_valid_statuses);
    }

    protected function _beforeSave()
    {
        $status = $this->getStatus();
        if (!$this->isStatusValid($status))
        {
            // Default to Pending, assume task is just being created
            $this->setStatus(self::STATUS_PENDING);
        }

        $created_at = $this->getCreatedAt();
        if (empty($created_at))
        {
            $current_gmt_timestamp = $this->_datetime->gmtTimestamp();
            $this->setCreatedAt($current_gmt_timestamp);
        }

        return parent::_beforeSave();
    }

    public function getArgumentsObject($use_cached = false)
    {
        if ($use_cached)
        {
            return $this->_getCachedArgumentsObject();
        }

        return $this->convertSerializedArgumentsIntoObject();
    }

    protected function _getCachedArgumentsObject()
    {
        if (is_null($this->_argumentsObject))
        {
            $this->_argumentsObject = $this->convertSerializedArgumentsIntoObject();
        }

        return $this->_argumentsObject;
    }

    public function convertSerializedArgumentsIntoObject()
    {
        $serialized_arguments_object_string = $this->getSerializedArgumentsObject();
        $argumentsObject = unserialize($serialized_arguments_object_string);

        if (is_array($argumentsObject))
        {
            $argumentsObjectToReturn = new \stdClass();
            foreach ($argumentsObject as $key => $value)
            {
                $argumentsObjectToReturn->$key = $value;
            }

            return $argumentsObjectToReturn;
        }

        if (!is_object($argumentsObject))
        {
            $argumentsObjectToReturn = new \stdClass();
            $argumentsObjectToReturn->value = $argumentsObject;

            return $argumentsObjectToReturn;
        }

        return $argumentsObject;
    }

    public function setTaskAsCompleted($success_message = null)
    {
        return $this->getResource()->setTaskAsCompleted($this, $success_message);
    }

    public function getActionText()
    {
        $status = $this->getStatus();
        if (!$this->isStatusValid($status))
        {
            return '';
        }

        switch($status)
        {
            case self::STATUS_ERROR:
                return 'Retry';
            case self::STATUS_PENDING:
                return 'Execute';
            default:
                return '';
        }

        return '';
    }

    protected function _returnSuccessCallbackResult($success_message)
    {
        $methodCallbackResultToReturn = $this->_taskResult;
        $methodCallbackResultToReturn->setTaskStatusMessage($success_message);
        $methodCallbackResultToReturn->setTaskStatus(\Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE);

        return $methodCallbackResultToReturn;
    }

    protected function _returnErrorCallbackResult($error_message)
    {
        $methodCallbackResultToReturn = $this->_taskResult;
        $methodCallbackResultToReturn->setTaskStatusMessage($error_message);
        $methodCallbackResultToReturn->setTaskStatus(\Reverb\ProcessQueue\Model\Task::STATUS_ERROR);

        return $methodCallbackResultToReturn;
    }

    protected function _returnAbortCallbackResult($error_message)
    {
        $methodCallbackResultToReturn = $this->_taskResult;
        $methodCallbackResultToReturn->setTaskStatusMessage($error_message);
        $methodCallbackResultToReturn->setTaskStatus(\Reverb\ProcessQueue\Model\Task::STATUS_ABORTED);

        return $methodCallbackResultToReturn;
    }
}
