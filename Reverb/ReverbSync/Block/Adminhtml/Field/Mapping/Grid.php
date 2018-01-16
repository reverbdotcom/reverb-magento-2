<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Field\Mapping;

use Magento\Store\Model\Store;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	
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
     * @var \Reverb\ReverbSync\Model\Resource\Field\Mapping\Collection
     */
    protected $fieldMappingCollection;
	
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
     * @param \Reverb\ReverbSync\Model\Resource\Field\Mapping\Collection $fieldMappingCollection
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
        \Reverb\ReverbSync\Model\Resource\Field\Mapping\Collection $fieldMappingCollection,
        array $data = []
    ) {
        $this->_websiteFactory = $websiteFactory;
        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_type = $type;
        $this->_status = $status;
        $this->_visibility = $visibility;
        $this->moduleManager = $moduleManager;
        $this->_fieldMappingCollection = $fieldMappingCollection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reverbfieldmappingGrid');
        $this->setDefaultSort('mapping_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
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
        $collection = $this->_fieldMappingCollection;
        $this->setCollection($collection);
        
        parent::_prepareCollection();
        return $this;
    }
	
	protected function _prepareColumns()
    {
		$this->addColumn(
            'reverb_api_field',
            [
                'header' => __('Reverb API Field'),
                'align'  => 'left',
                'index'  => \Reverb\ReverbSync\Model\Field\Mapping::REVERB_API_FIELD_FIELD,
                'type'   => 'text'
            ]
        );

        $this->addColumn(
            'magento_attribute_code',
            [
                'header' => __('Magento Attribute Code'),
                'align'  => 'left',
                'index'  => \Reverb\ReverbSync\Model\Field\Mapping::MAGENTO_ATTRIBUTE_FIELD,
                'type'   => 'text'
            ]
        );
		
		//return $this;
        return parent::_prepareColumns();
    }
	
	/**
     * @return string
     */
    public function getGridUrl()
	{
		return $this->getUrl('*/*/mapping', array('_current'=>true));
    }
	
	public function getRowUrl($item)
    {
        return $this->getUrl('*/*/edit', ['mapping_id' => $item->getId()]);
    }
}
