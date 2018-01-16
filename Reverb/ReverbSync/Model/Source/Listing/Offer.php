<?php
namespace Reverb\ReverbSync\Model\Source\Listing;
class Offer extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const VALUE_CONFIG = '0';
    const VALUE_YES = 1;
    const VALUE_NO = 2;

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'label' => __('Use Config'),
                    'value' => self::VALUE_CONFIG
                ),
                array(
                    'label' => __('Yes'),
                    'value' => self::VALUE_YES
                ),
                array(
                    'label' => __('No'),
                    'value' => self::VALUE_NO
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


    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}
