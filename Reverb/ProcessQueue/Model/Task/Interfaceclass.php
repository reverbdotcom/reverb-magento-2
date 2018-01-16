<?php
/**
 * Author: Sean Dunagan
 * Created: 8/13/15
 */
namespace Reverb\ProcessQueue\Model\Task;
interface Interfaceclass
{
    public function getId();

    public function getStatus();

    public function attemptUpdatingRowAsProcessing();

    public function selectForUpdate();

    /**
     * Should return data regarding the execution of the Task. For now, only status is set
     *
     * @return Reverb_ProcessQueue_Model_Task_Result_Interface
     */
    public function executeTask();

    public function actOnTaskResult(\Reverb\ProcessQueue\Model\Task\Result\Interfaceclass $taskExecutionResult);

    public function setTaskAsErrored();
}