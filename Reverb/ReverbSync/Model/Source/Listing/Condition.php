<?php
namespace Reverb\ReverbSync\Model\Source\Listing;
class Condition extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    // TODO: These IDs shouldn't be varchars.
    const NONE = '';
    const NON_FUNCTIONING = 'Non Functioning';
    const POOR = 'Poor';
    const FAIR = 'Fair';
    const GOOD = 'Good';
    const VERY_GOOD = 'Very Good';
    const EXCELLENT = 'Excellent';
    const MINT = 'Mint';
    const B_STOCK = 'B-Stock';
    const BRAND_NEW = 'Brand New';

    protected $_options;

    protected $_valid_conditions_array = array(
        self::NONE, self::NON_FUNCTIONING, self::POOR, self::FAIR, self::GOOD, self::VERY_GOOD, self::EXCELLENT,
        self::MINT, self::B_STOCK, self::BRAND_NEW,
    );

    public function isValidConditionValue($condition_value)
    {
        return in_array($condition_value, $this->_valid_conditions_array);
    }

    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                [
                    'label' => __(self::NONE),
                    'value' => self::NONE
                ],
                [
                    'label' => __(self::NON_FUNCTIONING),
                    'value' => self::NON_FUNCTIONING
                ],
                [
                    'label' => __(self::POOR),
                    'value' => self::POOR
                ],
                [
                    'label' => __(self::FAIR),
                    'value' => self::FAIR
                ],
                [
                    'label' => __(self::GOOD),
                    'value' => self::GOOD
                ],
                [
                    'label' => __(self::VERY_GOOD),
                    'value' => self::VERY_GOOD
                ],
                [
                    'label' => __(self::EXCELLENT),
                    'value' => self::EXCELLENT
                ],
                [
                    'label' => __(self::MINT),
                    'value' => self::MINT
                ],
                [
                    'label' => __(self::B_STOCK),
                    'value' => self::B_STOCK
                ],
                [
                    'label' => __(self::BRAND_NEW),
                    'value' => self::BRAND_NEW
                ]
            ];
        }
        return $this->_options;
    }


    /**
     * Get options array for system configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
 
}
