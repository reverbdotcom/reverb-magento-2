<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Orders\Ordersync;
use Magento\Store\Model\Store;
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	
	 /**
     * @var \Reverb\ProcessQueue\Model\Resource\Task\Collection
     */
    protected $_orderSyncCollection;
	
	/**
     * @var \Reverb\ProcessQueue\Model\Source\Task\Status
     */
    protected $_taskStatus;
	
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Reverb\ProcessQueue\Model\Resource\Task\Collection
     * @param \Reverb\ProcessQueue\Model\Source\Task\Status
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Reverb\ProcessQueue\Model\Resource\Taskresource\CollectionFactory $_orderSyncCollection,
        \Reverb\ProcessQueue\Model\Source\Task\Status $_taskStatus,
        array $data = []
    ) {
        $this->_orderSyncCollection = $_orderSyncCollection;
		$this->_taskStatus = $_taskStatus;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reverbordersyncGrid');
        $this->setDefaultSort('task_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //$this->setVarNameFilter('product_filter');
    }

    /**
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = $this->_orderSyncCollection->create();
        $collection->addFieldToFilter('code',array('eq'=>'order_update'));
        $this->setCollection($collection);
        
        parent::_prepareCollection();
        return $this;
    }
	
	protected function _prepareColumns()
    {
        $this->addColumn(
			'reverb_order_id',
			[
				'header'    => __('Reverb Order ID'),
				'width'     => 50,
				'align'     => 'left',
				'type'      => 'text',
				'renderer'  => '\Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Reverb\Id',
				'filter'    => false,
				'sortable'  => false
			]
		);

        $this->addColumn(
			'status',
			[
				'header'    => __('Status'),
				'align'     => 'left',
				'index'     => 'status',
				'type'      => 'options',
				'options'   => $this->_taskStatus->getOptionArray()
			]
		);

        $this->addColumn(
			'sku',
			[
				'header'    => __('Sku'),
				'align'     => 'left',
				'type'      => 'text',
				'renderer'  => '\Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Product\Sku',
				'filter'    => false,
				'sortable'  => false
			]
		);

        $this->addColumn(
			'name',
			[
				'header'    => __('Name'),
				'align'     => 'left',
				'type'      => 'text',
				'renderer'  => '\Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Product\Name',
				'filter'    => false,
				'sortable'  => false
			]
		);

        $this->addColumn(
			'status_message',
			[
				'header'    => __('Status Message'),
				'align'     => 'left',
				'index'     => 'status_message',
				'type'      => 'text'
			]
		);

        $this->addColumn(
			'created_at',
			[
				'header'    => __('Created At'),
				'align'     => 'left',
				'index'     => 'created_at',
				'type'      => 'datetime'
			]
		);

        $this->addColumn(
			'last_executed_at',
			[
				'header'    => __('Last Executed At'),
				'align'     => 'left',
				'index'     => 'last_executed_at',
				'type'      => 'datetime'
			]
		);

        $this->addColumn(
			'action',
			[
				'header'    => __('Action'),
				'width'     => '50px',
				'type'      => 'action',
				'getter'    => 'getId',
				'renderer'  => '\Reverb\ReverbSync\Block\Adminhtml\Widget\Grid\Column\Renderer\Order\Task\Action',
				'filter'    => false,
				'sortable'  => false,
				'task_controller' => 'ReverbSync_orders_sync'
			]
		);

        return parent::_prepareColumns();
    }
	/**
     * @return string
     */
    public function getGridUrl()
	{
		return $this->getUrl('*/*/orderajaxgrid', array('_current'=>true));
    }
}
