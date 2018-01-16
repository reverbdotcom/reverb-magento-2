<?php
namespace Reverb\Reports\Block\Adminhtml\Reverbreport;

use Magento\Store\Model\Store;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory]
     */
    protected $_setsFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_status;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;
     /**
     * @var \Reverb\Reports\Model\Resource\Reverbreport\Collection
     */
    protected $_reportcollection;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Reverb\Reports\Model\Resource\Reverbreport\Collection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\Framework\Module\Manager $moduleManager,
        \Reverb\Reports\Model\Resource\Reverbreport\Collection $reportcollection,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_type = $type;
        $this->_status = $status;
        $this->_visibility = $visibility;
        $this->moduleManager = $moduleManager;
        $this->_reportcollection = $reportcollection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reverbreportGrid');
        $this->setDefaultSort('entity_id');
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
        $collection = $this->_reportcollection;
        $this->setCollection($collection);
        
        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'index' => 'product_sku',
                'class' => 'xxx'
            ]
        );

        $this->addColumn(
            'inventory',
            [
                'header' => __('Inventory'),
                'type' => 'number',
                'index' => 'inventory',
                 'filter' => false,
            ]
        );
      /*  $this->addColumn('title', array(
            'header'    => Mage::helper('reverb_reports')->__('Title'),
            'align'     => 'left',
            'index'=>'title',
            'product_id' => 'product_id',
             'type'=> 'text',
             'renderer' =>  'Reverb_Reports_Block_Adminhtml_Reverbreport_Render',                                        
        ));
      */  
      
        $this->addColumn('rev_url', array(
            'header'=> __('Reverb URL'),
            'index' => 'rev_url',
            'type'=> 'text',
            'filter' => false,
            /*'renderer' =>  'Reverb_Reports_Block_Adminhtml_Reverbreport_Render',*/
        ));
        $this->addColumn('status', array(
            'header'    => __('Sync Status'),
            'index'        => 'status',
            'type'        => 'options',
            'options'    => array(
                '1' => __('success'),
                '0' => __('failed_reverb_sync'),
            )
        ));
        
    
        $this->addColumn('sync_details', array(
            'header'=> __('Sync Details'),
            'index' => 'sync_details',
            'type'=> 'text',
           'filter' => false,
        ));
       
        $this->addColumn('last_synced', array(
            'header'    => __('Last Synced'),
            'index'     => 'last_synced',
            'width'     => '120px',
            'type'      => 'datetime',
            'filter' => false,
        ));
      
        return parent::_prepareColumns();
    }

    public function getGridUrl(){
            return $this->getUrl('*/*/reverbajaxGrid', array('_current'=>true));
    }
}
