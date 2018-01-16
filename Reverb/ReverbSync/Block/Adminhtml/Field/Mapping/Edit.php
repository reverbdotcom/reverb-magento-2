<?php
/**
 * Reverb Report admin block
 *
 * @category    Reverb
 * @package     Reverb_ReverbSync
 */

namespace Reverb\ReverbSync\Block\Adminhtml\Field\Mapping;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'mapping_id';
        $this->_controller = 'adminhtml_field_mapping';
        $this->_blockGroup = 'Reverb_ReverbSync';

        parent::_construct();
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $item = $this->_coreRegistry->registry('reverbsync_field_mapping');
        if ($item->getMappingId()) {
            return __("Edit Field Mapping '%1'", $this->escapeHtml($item->getMappingId()));
        } else {
            return __('New Field Mapping');
        }
    }
}
