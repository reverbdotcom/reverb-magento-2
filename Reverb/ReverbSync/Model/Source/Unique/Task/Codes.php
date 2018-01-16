<?php
/**
 * Author: Sean Dunagan
 * Created: 9/22/15
 */
namespace Reverb\ReverbSync\Model\Source\Unique\Task;
class Codes
{
    const ORDER_CREATION_CODE = 'order_creation';

    protected $_options = null;

    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'label' => __(self::ORDER_CREATION_CODE),
                    'value' => self::ORDER_CREATION_CODE
                ),
                array(
                    'label' => __(\Reverb\ReverbSync\Model\Sync\Shipment\Tracking::JOB_CODE),
                    'value' => \Reverb\ReverbSync\Model\Sync\Shipment\Tracking::JOB_CODE
                )
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
