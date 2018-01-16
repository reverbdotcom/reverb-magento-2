<?php
/**
 * Author: Sean Dunagan
 * Created: 9/14/15
 */
namespace Reverb\ProcessQueue\Model\Source\Task;
class Status
{
    protected $_options = null;

    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'label' => __('Pending'),
                    'value' => \Reverb\ProcessQueue\Model\Task::STATUS_PENDING
                ),
                array(
                    'label' => __('Processing'),
                    'value' => \Reverb\ProcessQueue\Model\Task::STATUS_PROCESSING
                ),
                array(
                    'label' => __('Complete'),
                    'value' => \Reverb\ProcessQueue\Model\Task::STATUS_COMPLETE
                ),
                array(
                    'label' => __('Error'),
                    'value' => \Reverb\ProcessQueue\Model\Task::STATUS_ERROR
                ),
                array(
                    'label' => __('Failed'),
                    'value' => \Reverb\ProcessQueue\Model\Task::STATUS_ABORTED
                ),
            );
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = array();
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    public function getLabelByOptionValue($option_value)
    {
        foreach ($this->getAllOptions() as $option)
        {
            $value = $option['value'];
            if($value == $option_value)
            {
                return $option['label'];
            }
        }
        return '';
    }
} 