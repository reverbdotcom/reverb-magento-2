<?php
namespace Reverb\ReverbSync\Model\Source;
class Revurl implements \Magento\Framework\Option\ArrayInterface
{
    const PRODUCTION_URL = 'https://reverb.com';
    const PRODUCTION_LABEL = 'Reverb.com (Production)';
    const SANDBOX_URL = 'https://sandbox.reverb.com';
    const SANDBOX_LABEL = 'Reverb Sandbox (Testing)';

    /**
     * Returns the Reverb production API endpoint
     *
     * @return string
     */
    public function getProductionUrl()
    {
        return self::PRODUCTION_URL;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::PRODUCTION_URL, 'label' => __(self::PRODUCTION_LABEL)],
            ['value' => self::SANDBOX_URL, 'label' => __(self::SANDBOX_LABEL)],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::PRODUCTION_URL => __(self::PRODUCTION_LABEL),
            self::SANDBOX_URL => __(self::SANDBOX_LABEL),
        );
    }
}
