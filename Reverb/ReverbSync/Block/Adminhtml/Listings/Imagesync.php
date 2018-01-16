<?php
/**
 * Reverb Report admin block
 *
 * @category    Reverb
 * @package     Reverb_Reports
 */
namespace Reverb\ReverbSync\Block\Adminhtml\Listings;

class Imagesync extends \Magento\Backend\Block\Widget\Grid\Container{

    const BUTTON_ACTION_TEMPLATE = "confirmSetLocation('%s', '%s')";

    protected $_backendUrl;
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Catalog\Model\Product\TypeFactory $typeFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_backendUrl = $backendUrl;
        $this->_addClearTasksButtons();
    }

     /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('add');
        $this->_blockGroup = 'Reverb_ReverbSync';
        $this->_controller = 'adminhtml_listings_imagesync';
    }


    /**
     * Prepare button and grid
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product
     */
    protected function _prepareLayout()
    {
        /*$addButtonProps = [
            'id' => 'clear_all_tasks',
            'label' => __('Clear all tasks'),
            'class' => 'clear_task button btn',
            'button_class' => 'button'
        ];
        $this->buttonList->add('clear_all_tasks', $addButtonProps);*/

        return parent::_prepareLayout();
    }

    public function _addClearTasksButtons()
    {
        $buttons_to_render = array();
        $task_code_param = 'listing_image_sync';
        $clear_all_tasks_action = 'reverbprocessqueue/processqueue/index';

        $clear_all_tasks_button = array(
            'action_url' => $this->_backendUrl->getUrl($clear_all_tasks_action,
                                                                        array('task_codes' => $task_code_param,'type'=>'clearAllTasksAction')
                                                                    ),
            'label' => 'Clear All Tasks',
            'confirm_message' => 'Are you sure you want to clear all tasks?'
        );

        $clear_successful_tasks_action = 'reverbprocessqueue/processqueue/index';
        $clear_successful_tasks_button = array(
            'action_url' => $this->_backendUrl->getUrl($clear_successful_tasks_action,
                                                                        array('task_codes' => $task_code_param,'type'=>'clearSuccessfulTasksAction')
                                                                    ),
            'label' => 'Clear Successful Sync Tasks',
            'confirm_message' => 'Are you sure you want to clear all successful tasks?'
        );

        $buttons_to_render['clear_all_sync_tasks'] = $clear_all_tasks_button;
        $buttons_to_render['clear_successful_sync_tasks'] = $clear_successful_tasks_button;

        foreach ($buttons_to_render as $button_id => $button)
        {
            $label = __($button['label']);
            $confirm_message = __($button['confirm_message']);
            $action_url = $button['action_url'];
            $onclick = sprintf(self::BUTTON_ACTION_TEMPLATE, $confirm_message, $action_url);

            $this->addButton(
                $button_id, array(
                    'label' => __($label),
                    'onclick' => $onclick,
                    'level' => -1
                )
            );
        }
    }
}
